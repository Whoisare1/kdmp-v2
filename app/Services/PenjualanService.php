<?php

namespace App\Services;

use App\Models\Master\Gudang;
use App\Models\Master\Pihak;
use App\Models\Keuangan\Piutang;
use App\Models\Penjualan\DetailPenjualan;
use App\Models\Penjualan\Penjualan;
use App\Models\Penjualan\ReturPenjualan;
use App\Models\Penjualan\ReturPenjualanDetail;
use App\Services\Finance\JurnalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use RuntimeException;

class PenjualanService
{
    public static function createSale(
        int $idEntitas,
        ?int $idPihak,
        int $idUnitUsaha,
        string $metodeBayar,
        array $items,
        ?int $idKasBank = null,
        array $transferDetails = [],
        ?string $tglJatuhTempo = null,
        ?string $namaPembeli = null,
        float $diskon = 0,
    ): Penjualan {
        if (! in_array($metodeBayar, ['tunai', 'transfer', 'kredit'], true)) {
            throw new RuntimeException('Metode pembayaran tidak valid.');
        }

        if ($diskon < 0) {
            throw new RuntimeException('Diskon penjualan tidak boleh bernilai negatif.');
        }

        if ($metodeBayar === 'transfer' && (
            $idKasBank === null
            || blank($transferDetails['pengirim_nama'] ?? null)
            || blank($transferDetails['pengirim_bank'] ?? null)
            || blank($transferDetails['pengirim_rekening'] ?? null)
            || blank($transferDetails['no_referensi'] ?? null)
            || ($transferDetails['dikonfirmasi'] ?? false) !== true
        )) {
            throw new RuntimeException('Lengkapi rekening tujuan dan data transfer, lalu konfirmasi dana telah diterima.');
        }

        if ($metodeBayar === 'kredit' && (
            blank($tglJatuhTempo)
            || Carbon::parse($tglJatuhTempo)->startOfDay()->lt(today())
        )) {
            throw new RuntimeException('Tanggal jatuh tempo kredit wajib diisi dan tidak boleh sebelum hari ini.');
        }

        if ($items === []) {
            throw new RuntimeException('Keranjang penjualan masih kosong.');
        }

        foreach ($items as $item) {
            if (($item['qty_dasar'] ?? 0) <= 0) {
                throw new RuntimeException('Jumlah barang harus lebih besar dari nol.');
            }
        }
        return DB::transaction(function () use ($idEntitas, $idPihak, $idUnitUsaha, $metodeBayar, $items, $idKasBank, $transferDetails, $tglJatuhTempo, $namaPembeli, $diskon) {
            if ($idPihak === null && filled($namaPembeli)) {
                $targetNama = trim($namaPembeli);
                $pembeli = Pihak::query()->firstOrCreate(
                    [
                        'id_entitas' => $idEntitas,
                        'nama' => $targetNama,
                    ],
                    [
                        'jenis_pihak' => 'entitas_lain',
                        'is_anggota' => false,
                        'is_active' => true,
                    ],
                );
                if (! $pembeli->is_active) {
                    $pembeli->update(['is_active' => true]);
                }
            } else {
                $pembeli = Pihak::query()
                    ->where('id_pihak', $idPihak)
                    ->where('is_active', true)
                    ->firstOrFail();
            }

            $kasBank = null;
            if ($metodeBayar !== 'kredit') {
                $kasBank = DB::table('master_kas_bank')
                    ->where('id_entitas', $idEntitas)
                    ->where('jenis', $metodeBayar === 'tunai' ? 'kas' : 'bank')
                    ->where('is_active', true)
                    ->when($metodeBayar === 'transfer', fn ($query) => $query->whereNotNull('no_rekening')->where('no_rekening', '<>', ''))
                    ->when($metodeBayar === 'transfer', fn ($query) => $query->where('id_kas_bank', $idKasBank))
                    ->orderByDesc('is_default')
                    ->orderBy('id_kas_bank')
                    ->first();
            }

            if ($metodeBayar !== 'kredit' && ! $kasBank) {
                throw new RuntimeException(
                    $metodeBayar === 'transfer'
                        ? 'Rekening bank tujuan tidak aktif atau bukan milik entitas ini.'
                        : 'Rekening kas aktif untuk entitas ini belum dikonfigurasi.',
                );
            }

            $idGudang = Gudang::query()
                ->where('id_entitas', $idEntitas)
                ->where('is_active', true)
                ->orderBy('id_gudang')
                ->value('id_gudang');

            if ($idGudang === null) {
                throw new RuntimeException('Gudang aktif untuk entitas ini belum dikonfigurasi.');
            }

            $itemsByBarang = collect($items)
                ->groupBy('id_barang')
                ->map(fn ($rows) => round((float) $rows->sum('qty_dasar'), 4));

            $products = DB::table('master_barang as barang')
                ->join('barang_per_koperasi as setting', function ($join) use ($idEntitas) {
                    $join->on('setting.id_barang', '=', 'barang.id_barang')
                        ->where('setting.id_entitas', $idEntitas)
                        ->where('setting.is_dijual', true);
                })
                ->where('barang.is_active', true)
                ->where('barang.id_unit_usaha', $idUnitUsaha)
                ->whereIn('barang.id_barang', $itemsByBarang->keys())
                ->select([
                    'barang.id_barang',
                    'barang.nama_barang',
                    'barang.id_satuan_dasar',
                    'setting.harga_jual_standar',
                ])
                ->lockForUpdate()
                ->get()
                ->keyBy('id_barang');

            if ($products->count() !== $itemsByBarang->count()) {
                throw new RuntimeException('Barang tidak aktif, bukan milik unit usaha yang dipilih, atau tidak tersedia untuk dijual.');
            }

            $stokPerBarang = DB::table('stok')
                ->where('id_gudang', $idGudang)
                ->whereIn('id_barang', $itemsByBarang->keys())
                ->orderBy('id_barang')
                ->lockForUpdate()
                ->get()
                ->keyBy('id_barang');

            $total = 0.0;
            foreach ($itemsByBarang as $idBarang => $quantity) {
                if ($quantity <= 0) {
                    throw new RuntimeException('Jumlah barang harus lebih besar dari nol.');
                }

                $stok = $stokPerBarang->get($idBarang);
                $tersedia = $stok
                    ? bcsub((string) $stok->qty_on_hand, (string) $stok->qty_reserved, 4)
                    : '0';
                $qtyDiminta = number_format($quantity, 4, '.', '');

                if (bccomp($tersedia, $qtyDiminta, 4) < 0) {
                    $namaBarang = $products->get($idBarang)->nama_barang;
                    throw new RuntimeException("Stok gudang {$namaBarang} tidak mencukupi. Tersedia {$tersedia}, diminta {$qtyDiminta}.");
                }

                $total += round($quantity * (float) $products->get($idBarang)->harga_jual_standar, 2);
            }
            $total = round($total, 2);
            $diskon = round($diskon, 2);
            if ($diskon < 0 || $diskon > $total) {
                throw new RuntimeException('Diskon penjualan tidak boleh melebihi total harga barang.');
            }
            $totalBayar = round($total - $diskon, 2);

            $sale = Penjualan::create([
                'id_entitas' => $idEntitas,
                'kode_penjualan' => self::generateSaleCode($idEntitas),
                'id_pihak' => $pembeli->id_pihak,
                'nama_pembeli' => filled($namaPembeli) ? trim($namaPembeli) : $pembeli->nama,
                'id_unit_usaha' => $idUnitUsaha,
                'id_gudang' => $idGudang,
                'tanggal_transaksi' => now()->toDateString(),
                'tgl_jatuh_tempo' => $metodeBayar === 'kredit' ? $tglJatuhTempo : null,
                'is_pembeli_anggota' => $pembeli->is_anggota,
                'ada_baris_konsinyasi' => false,
                'id_kas_bank' => $kasBank?->id_kas_bank,
                'metode_bayar' => $metodeBayar,
                'transfer_pengirim_nama' => $metodeBayar === 'transfer' ? trim($transferDetails['pengirim_nama']) : null,
                'transfer_pengirim_bank' => $metodeBayar === 'transfer' ? trim($transferDetails['pengirim_bank']) : null,
                'transfer_pengirim_rekening' => $metodeBayar === 'transfer' ? trim($transferDetails['pengirim_rekening']) : null,
                'transfer_no_referensi' => $metodeBayar === 'transfer' ? trim($transferDetails['no_referensi']) : null,
                'transfer_penerima_nama' => $metodeBayar === 'transfer' ? $kasBank->nama : null,
                'transfer_penerima_rekening' => $metodeBayar === 'transfer' ? $kasBank->no_rekening : null,
                'transfer_dikonfirmasi_pada' => $metodeBayar === 'transfer' ? now() : null,
                'transfer_dikonfirmasi_oleh' => $metodeBayar === 'transfer' ? auth()->id() : null,
                'total_bruto' => $total,
                'diskon' => $diskon,
                'total_bayar' => $totalBayar,
                'total_hpp' => 0,
                'status' => 'selesai',
                'status_posting' => 'F',
                'id_jurnal' => null,
                'created_by' => auth()->id(),
            ]);

            $totalHpp = 0.0;
            $stokService = app(StokService::class);
            foreach ($itemsByBarang as $idBarang => $quantity) {
                $product = $products->get($idBarang);
                $price = (float) $product->harga_jual_standar;
                $kartuStok = $stokService->keluar(
                    entitasId: $idEntitas,
                    gudangId: (int) $idGudang,
                    barangId: (int) $idBarang,
                    qty: number_format($quantity, 4, '.', ''),
                    refTipe: 'PENJUALAN',
                    refId: (int) $sale->id_penjualan,
                    createdBy: auth()->id(),
                );
                $hpp = (float) $kartuStok->harga_satuan;
                if ($hpp <= 0) {
                    throw new RuntimeException("HPP {$product->nama_barang} belum valid. Penjualan dibatalkan.");
                }

                $lineHpp = (float) $kartuStok->nilai_mutasi;
                $totalHpp += $lineHpp;

                DetailPenjualan::create([
                    'id_penjualan' => $sale->id_penjualan,
                    'id_barang' => (int) $idBarang,
                    'id_stok_konsinyasi' => null,
                    'id_satuan_input' => (int) $product->id_satuan_dasar,
                    'qty_input' => $quantity,
                    'faktor_konversi' => 1,
                    'qty_dasar' => $quantity,
                    'harga_satuan' => $price,
                    'subtotal' => round($quantity * $price, 2),
                    'hpp_satuan_dasar' => $hpp,
                    'total_hpp' => $lineHpp,
                    'harga_titip_satuan' => 0,
                ]);
            }

            $sale->update(['total_hpp' => $totalHpp]);

            // Realtime Auto-Posting Jurnal Akuntansi (Opsi A)
            $kodeTransaksi = match ($metodeBayar) {
                'transfer' => 'JTFW',
                'kredit' => 'JKW',
                default => 'JTW',
            };
            $unitUsaha = DB::table('master_unit_usaha')->where('id_unit_usaha', $idUnitUsaha)->first();

            $idJurnal = null;
            $statusPosting = 'F';

            try {
                if (class_exists(JurnalService::class)) {
                    $jurnal = app(JurnalService::class)->posting(
                        $kodeTransaksi,
                        [
                            'tanggal_jurnal' => $sale->tanggal_transaksi ? Carbon::parse($sale->tanggal_transaksi)->toDateString() : now()->toDateString(),
                            'nomor_nota' => $sale->kode_penjualan,
                            'total_bayar' => (float) $sale->total_bayar,
                            'total_hpp' => (float) $totalHpp,
                            'id_kas_bank' => $sale->id_kas_bank,
                            'kode_unit' => $unitUsaha?->kode_unit_usaha,
                            'is_anggota' => (bool) $pembeli->is_anggota,
                            'id_pihak' => $pembeli->id_pihak,
                        ],
                        Penjualan::class,
                        (int) $sale->id_penjualan,
                        "Penjualan {$sale->kode_penjualan} ({$pembeli->nama})",
                        $idEntitas,
                    );
                    $idJurnal = $jurnal?->id_jurnal;
                    $statusPosting = $idJurnal ? 'T' : 'F';
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Gagal posting jurnal penjualan {$sale->kode_penjualan}: " . $e->getMessage());
                throw $e;
            }

            $sale->update([
                'status_posting' => $statusPosting,
                'id_jurnal' => $idJurnal,
            ]);

            if ($metodeBayar === 'kredit') {
                Piutang::updateOrCreate(
                    ['sumber_tipe' => 'PENJUALAN', 'sumber_id' => $sale->id_penjualan],
                    [
                        'id_entitas' => $idEntitas,
                        'id_pihak' => $pembeli->id_pihak,
                        'kode_akun' => '1132',
                        'tanggal' => $sale->tanggal_transaksi,
                        'tgl_jatuh_tempo' => $tglJatuhTempo,
                        'nilai_awal' => $sale->total_bayar,
                        'nilai_terbayar' => 0,
                        'status' => 'belum_lunas',
                    ],
                );
            }

            return $sale->load(['pihak', 'detail.barang.satuanDasar']);
        });
    }

    public static function createReturn(
        Penjualan $sale,
        array $items,
        string $jenisPenyelesaian,
        string $alasan,
    ): ReturPenjualan {
        if ($sale->status !== 'selesai') {
            throw new RuntimeException('Retur hanya bisa dibuat untuk transaksi penjualan yang selesai.');
        }

        if (! in_array($jenisPenyelesaian, ['uang', 'potong_piutang', 'ganti_barang'], true)) {
            throw new RuntimeException('Catatan penyelesaian retur tidak valid.');
        }

        if ($items === []) {
            throw new RuntimeException('Isi jumlah minimal satu barang yang akan diretur.');
        }

        return DB::transaction(function () use ($sale, $items, $jenisPenyelesaian, $alasan) {
            $sale = Penjualan::with(['detail', 'returs.detail'])
                ->whereKey($sale->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $returnDetails = $sale->returs
                ->flatMap(fn ($retur) => $retur->detail);
            $returnedByBarang = $returnDetails
                ->groupBy('id_barang')
                ->map(fn ($details) => (float) $details->sum('qty_dasar'));
            $returnedValueCentsByBarang = $returnDetails
                ->groupBy('id_barang')
                ->map(fn ($details) => self::toCents((float) $details->sum('nilai')));
            $requestedByBarang = collect($items)
                ->groupBy('id_barang')
                ->map(fn ($rows) => round((float) $rows->sum('qty_dasar'), 4));

            foreach ($requestedByBarang as $idBarang => $quantity) {
                $sold = (float) $sale->detail->where('id_barang', (int) $idBarang)->sum('qty_dasar');
                $returned = (float) $returnedByBarang->get($idBarang, 0);

                if ($quantity <= 0 || $sold <= 0 || $returned + $quantity > $sold + 0.0000001) {
                    throw new RuntimeException('Jumlah retur melebihi sisa barang pada transaksi ini.');
                }
            }

            $saleLinesByBarang = $sale->detail->groupBy('id_barang');
            $grossValueCentsByBarang = $saleLinesByBarang
                ->map(fn ($lines) => self::toCents((float) $lines->sum('subtotal')))
                ->all();
            $totalGrossCents = array_sum($grossValueCentsByBarang);
            $discountCents = min(
                $totalGrossCents,
                max(0, self::toCents((float) $sale->diskon)),
            );
            $discountCentsByBarang = self::allocateDiscountCents(
                $grossValueCentsByBarang,
                $discountCents,
            );

            $returnValueCentsByBarang = [];
            $totalValueCents = 0;
            $totalHpp = 0.0;
            foreach ($requestedByBarang as $idBarang => $quantity) {
                $lines = $saleLinesByBarang->get($idBarang);
                $soldQuantity = (float) $lines->sum('qty_dasar');
                $returnedQuantity = (float) $returnedByBarang->get($idBarang, 0);
                $cumulativeReturnedQuantity = min($soldQuantity, $returnedQuantity + $quantity);
                $netLineValueCents = max(
                    0,
                    $grossValueCentsByBarang[$idBarang] - $discountCentsByBarang[$idBarang],
                );
                $targetCumulativeValueCents = $cumulativeReturnedQuantity >= $soldQuantity - 0.0000001
                    ? $netLineValueCents
                    : (int) round($netLineValueCents * $cumulativeReturnedQuantity / $soldQuantity);
                $returnValueCents = max(
                    0,
                    $targetCumulativeValueCents - (int) $returnedValueCentsByBarang->get($idBarang, 0),
                );
                $returnValueCentsByBarang[$idBarang] = $returnValueCents;
                $totalValueCents += $returnValueCents;
                $line = $lines->first();
                $totalHpp += $quantity * (float) $line->hpp_satuan_dasar;
            }
            $totalValue = $totalValueCents / 100;

            $return = ReturPenjualan::create([
                'id_entitas' => $sale->id_entitas,
                'kode_retur' => self::generateReturnCode($sale->id_entitas),
                'id_penjualan' => $sale->id_penjualan,
                'tgl_retur' => now()->toDateString(),
                'jenis_penyelesaian' => $jenisPenyelesaian,
                'total_nilai' => $totalValue,
                'total_hpp' => $totalHpp,
                'alasan' => $alasan,
                'status' => 'diajukan',
                'status_posting' => 'F',
                'id_jurnal' => null,
            ]);

            foreach ($requestedByBarang as $idBarang => $quantity) {
                $line = $saleLinesByBarang->get($idBarang)->first();
                $price = (float) $line->harga_satuan;
                $hpp = (float) $line->hpp_satuan_dasar;
                ReturPenjualanDetail::create([
                    'id_retur' => $return->id_retur,
                    'id_barang' => (int) $idBarang,
                    'qty_dasar' => $quantity,
                    'harga_satuan' => $price,
                    'nilai' => $returnValueCentsByBarang[$idBarang] / 100,
                    'hpp_satuan' => $hpp,
                    'total_hpp' => round($quantity * $hpp, 2),
                ]);
            }

            return $return->load(['penjualan', 'detail.barang']);
        });
    }

    public static function completeReturn(ReturPenjualan $return): ReturPenjualan
    {
        return DB::transaction(function () use ($return) {
            $return = ReturPenjualan::query()
                ->whereKey($return->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($return->status !== 'diajukan') {
                throw new RuntimeException('Hanya retur yang diajukan yang dapat diselesaikan.');
            }

            $return->load(['detail', 'penjualan.unitUsaha', 'penjualan.pihak']);
            $sale = $return->penjualan;

            if ($return->detail->isEmpty()) {
                throw new RuntimeException('Retur tanpa detail barang tidak dapat diselesaikan.');
            }

            $stokService = app(StokService::class);
            foreach ($return->detail as $line) {
                $stokService->masuk(
                    entitasId: (int) $return->id_entitas,
                    gudangId: (int) $sale->id_gudang,
                    barangId: (int) $line->id_barang,
                    qty: number_format((float) $line->qty_dasar, 4, '.', ''),
                    hargaSatuan: number_format((float) $line->hpp_satuan, 4, '.', ''),
                    refTipe: 'RETUR_JUAL',
                    refId: (int) $return->id_retur,
                    createdBy: auth()->id(),
                    tanggal: $return->tgl_retur->toDateString(),
                );
            }

            $jurnal = null;
            if ($return->jenis_penyelesaian === 'uang') {
                $unitUsaha = $sale->unitUsaha ?? DB::table('master_unit_usaha')->where('id_unit_usaha', $sale->id_unit_usaha)->first();

                try {
                    if (class_exists(JurnalService::class)) {
                        $jurnal = app(JurnalService::class)->posting(
                            'RJU',
                            [
                                'tanggal_jurnal' => $return->tgl_retur ? Carbon::parse($return->tgl_retur)->toDateString() : now()->toDateString(),
                                'nomor_nota' => $return->kode_retur,
                                'total_nilai' => (float) $return->total_nilai,
                                'total_hpp' => (float) $return->total_hpp,
                                'kode_unit' => $unitUsaha?->kode_unit_usaha,
                                'id_pihak' => $sale->id_pihak,
                            ],
                            ReturPenjualan::class,
                            (int) $return->id_retur,
                            "Retur Penjualan {$return->kode_retur} untuk {$sale->kode_penjualan}",
                            $return->id_entitas,
                        );
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal posting jurnal retur penjualan {$return->kode_retur}: " . $e->getMessage());
                    throw $e;
                }
            }

            $return->update([
                'status' => 'selesai',
                'status_posting' => $jurnal ? 'T' : 'F',
                'id_jurnal' => $jurnal?->id_jurnal,
            ]);

            return $return->fresh();
        });
    }

    /**
     * @param  array<int|string, int>  $grossValueCentsByBarang
     * @return array<int|string, int>
     */
    private static function allocateDiscountCents(array $grossValueCentsByBarang, int $discountCents): array
    {
        $totalGrossCents = array_sum($grossValueCentsByBarang);
        $allocations = array_fill_keys(array_keys($grossValueCentsByBarang), 0);

        if ($totalGrossCents <= 0 || $discountCents <= 0) {
            return $allocations;
        }

        $discountCents = min($discountCents, $totalGrossCents);
        $remainders = [];
        $allocatedCents = 0;

        foreach ($grossValueCentsByBarang as $idBarang => $grossCents) {
            $exactShare = $discountCents * ($grossCents / $totalGrossCents);
            $shareCents = (int) floor($exactShare);
            $allocations[$idBarang] = $shareCents;
            $allocatedCents += $shareCents;
            $remainders[] = [
                'id_barang' => $idBarang,
                'remainder' => $exactShare - $shareCents,
            ];
        }

        usort($remainders, fn (array $left, array $right) =>
            ($right['remainder'] <=> $left['remainder'])
            ?: ($left['id_barang'] <=> $right['id_barang'])
        );

        $remainingCents = $discountCents - $allocatedCents;
        for ($index = 0; $index < $remainingCents; $index++) {
            $idBarang = $remainders[$index]['id_barang'];
            $allocations[$idBarang]++;
        }

        return $allocations;
    }

    private static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private static function generateSaleCode(int $idEntitas): string
    {
        $period = now()->format('Ym');
        $last = Penjualan::where('id_entitas', $idEntitas)
            ->where('kode_penjualan', 'like', "JUL-{$period}-%")
            ->orderByDesc('id_penjualan')
            ->value('kode_penjualan');
        $sequence = $last && preg_match('/(\d+)$/', $last, $matches) ? (int) $matches[1] + 1 : 1;
        return sprintf('JUL-%s-%04d', $period, $sequence);
    }

    private static function generateReturnCode(int $idEntitas): string
    {
        $period = now()->format('Ym');
        $last = ReturPenjualan::where('id_entitas', $idEntitas)
            ->where('kode_retur', 'like', "RJU-{$period}-%")
            ->orderByDesc('id_retur')
            ->value('kode_retur');
        $sequence = $last && preg_match('/(\d+)$/', $last, $matches) ? (int) $matches[1] + 1 : 1;
        return sprintf('RJU-%s-%04d', $period, $sequence);
    }
}
