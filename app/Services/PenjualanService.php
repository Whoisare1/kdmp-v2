<?php

namespace App\Services;

use App\Models\Master\Gudang;
use App\Models\Master\Pihak;
use App\Models\Penjualan\DetailPenjualan;
use App\Models\Penjualan\Penjualan;
use App\Models\Penjualan\ReturPenjualan;
use App\Models\Penjualan\ReturPenjualanDetail;
use App\Services\Finance\JurnalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PenjualanService
{
    public const DEMO_STOCK_PER_ITEM = 100.0;

    public static function availableDemoStock(int $idEntitas, int $idBarang): float
    {
        $sold = (float) DB::table('detail_penjualan as detail')
            ->join('penjualan as sale', 'sale.id_penjualan', '=', 'detail.id_penjualan')
            ->where('sale.id_entitas', $idEntitas)
            ->where('sale.status', 'selesai')
            ->where('detail.id_barang', $idBarang)
            ->sum('detail.qty_dasar');

        $returned = (float) DB::table('retur_penjualan_detail as detail')
            ->join('retur_penjualan as retur', 'retur.id_retur', '=', 'detail.id_retur')
            ->join('penjualan as sale', 'sale.id_penjualan', '=', 'retur.id_penjualan')
            ->where('sale.id_entitas', $idEntitas)
            ->where('sale.status', 'selesai')
            ->where('retur.status', 'selesai')
            ->where('detail.id_barang', $idBarang)
            ->sum('detail.qty_dasar');

        return max(0, self::DEMO_STOCK_PER_ITEM - $sold + $returned);
    }

    public static function createSale(
        int $idEntitas,
        int $idPihak,
        int $idUnitUsaha,
        string $metodeBayar,
        array $items,
    ): Penjualan {
        if (! in_array($metodeBayar, ['tunai', 'transfer'], true)) {
            throw new RuntimeException('Metode pembayaran tidak valid.');
        }

        if ($items === []) {
            throw new RuntimeException('Keranjang penjualan masih kosong.');
        }

        return DB::transaction(function () use ($idEntitas, $idPihak, $idUnitUsaha, $metodeBayar, $items) {
            $pembeli = Pihak::query()
                ->where('id_pihak', $idPihak)
                ->where('is_active', true)
                ->firstOrFail();

            $idGudang = Gudang::query()
                ->where('id_entitas', $idEntitas)
                ->where('is_active', true)
                ->orderBy('id_gudang')
                ->value('id_gudang')
                ?? Gudang::value('id_gudang')
                ?? 1;

            $itemsByBarang = collect($items)
                ->groupBy('id_barang')
                ->map(fn ($rows) => (float) $rows->sum('qty_dasar'));

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
                    'barang.id_satuan_dasar',
                    'setting.harga_jual_standar',
                ])
                ->lockForUpdate()
                ->get()
                ->keyBy('id_barang');

            if ($products->count() !== $itemsByBarang->count()) {
                throw new RuntimeException('Barang tidak aktif, bukan milik unit usaha yang dipilih, atau tidak tersedia untuk dijual.');
            }

            $total = 0.0;
            foreach ($itemsByBarang as $idBarang => $quantity) {
                if ($quantity <= 0) {
                    throw new RuntimeException('Jumlah barang harus lebih besar dari nol.');
                }

                if ($quantity > self::availableDemoStock($idEntitas, (int) $idBarang)) {
                    throw new RuntimeException("Stok demo {$products->get($idBarang)->id_barang} tidak mencukupi.");
                }

                $total += $quantity * (float) $products->get($idBarang)->harga_jual_standar;
            }

            $sale = Penjualan::create([
                'id_entitas' => $idEntitas,
                'kode_penjualan' => self::generateSaleCode($idEntitas),
                'id_pihak' => $pembeli->id_pihak,
                'id_unit_usaha' => $idUnitUsaha,
                'id_gudang' => $idGudang,
                'tanggal_transaksi' => now()->toDateString(),
                'is_pembeli_anggota' => $pembeli->is_anggota,
                'ada_baris_konsinyasi' => false,
                'id_kas_bank' => DB::table('master_kas_bank')
                    ->where('id_entitas', $idEntitas)
                    ->where('jenis', $metodeBayar === 'tunai' ? 'kas' : 'bank')
                    ->where('is_active', true)
                    ->value('id_kas_bank')
                    ?? DB::table('master_kas_bank')->where('is_active', true)->value('id_kas_bank'),
                'metode_bayar' => $metodeBayar,
                'total_bruto' => $total,
                'diskon' => 0,
                'total_bayar' => $total,
                'total_hpp' => 0,
                'status' => 'selesai',
                'status_posting' => 'F',
                'id_jurnal' => null,
                'created_by' => auth()->id(),
            ]);

            $totalHpp = 0.0;
            foreach ($itemsByBarang as $idBarang => $quantity) {
                $product = $products->get($idBarang);
                $price = (float) $product->harga_jual_standar;

                $hpp = (float) (DB::table('stok')
                    ->where('id_gudang', $idGudang)
                    ->where('id_barang', (int) $idBarang)
                    ->value('hpp_rata2') ?? 0);

                if ($hpp <= 0) {
                    $hpp = round($price * 0.7, 2);
                }

                $lineHpp = $quantity * $hpp;
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
                    'subtotal' => $quantity * $price,
                    'hpp_satuan_dasar' => $hpp,
                    'total_hpp' => $lineHpp,
                    'harga_titip_satuan' => 0,
                ]);
            }

            $sale->update(['total_hpp' => $totalHpp]);

            // Realtime Auto-Posting Jurnal Akuntansi (Opsi A)
            $kodeTransaksi = $metodeBayar === 'transfer' ? 'JTFW' : 'JTW';
            $unitUsaha = DB::table('master_unit_usaha')->where('id_unit_usaha', $idUnitUsaha)->first();

            $jurnal = app(JurnalService::class)->posting(
                $kodeTransaksi,
                [
                    'tanggal_jurnal' => $sale->tanggal_transaksi,
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

            $sale->update([
                'status_posting' => 'T',
                'id_jurnal' => $jurnal->id_jurnal,
            ]);

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

            $returnedByBarang = $sale->returs
                ->flatMap(fn ($retur) => $retur->detail)
                ->groupBy('id_barang')
                ->map(fn ($details) => (float) $details->sum('qty_dasar'));
            $requestedByBarang = collect($items)
                ->groupBy('id_barang')
                ->map(fn ($rows) => (float) $rows->sum('qty_dasar'));

            foreach ($requestedByBarang as $idBarang => $quantity) {
                $sold = (float) $sale->detail->where('id_barang', (int) $idBarang)->sum('qty_dasar');
                $returned = (float) $returnedByBarang->get($idBarang, 0);

                if ($sold <= 0 || $returned + $quantity > $sold + 0.0000001) {
                    throw new RuntimeException('Jumlah retur melebihi sisa barang pada transaksi ini.');
                }
            }

            $itemsByBarang = $sale->detail->keyBy('id_barang');
            $totalValue = 0.0;
            $totalHpp = 0.0;
            foreach ($requestedByBarang as $idBarang => $quantity) {
                $line = $itemsByBarang->get($idBarang);
                $totalValue += $quantity * (float) $line->harga_satuan;
                $totalHpp += $quantity * (float) $line->hpp_satuan_dasar;
            }

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
                $line = $itemsByBarang->get($idBarang);
                $price = (float) $line->harga_satuan;
                $hpp = (float) $line->hpp_satuan_dasar;
                ReturPenjualanDetail::create([
                    'id_retur' => $return->id_retur,
                    'id_barang' => (int) $idBarang,
                    'qty_dasar' => $quantity,
                    'harga_satuan' => $price,
                    'nilai' => $quantity * $price,
                    'hpp_satuan' => $hpp,
                    'total_hpp' => $quantity * $hpp,
                ]);
            }

            return $return->load(['penjualan', 'detail.barang']);
        });
    }

    public static function completeReturn(ReturPenjualan $return): ReturPenjualan
    {
        if ($return->status !== 'diajukan') {
            throw new RuntimeException('Hanya retur yang diajukan yang dapat diselesaikan.');
        }

        return DB::transaction(function () use ($return) {
            $return->loadMissing(['penjualan.unitUsaha', 'penjualan.pihak']);
            $sale = $return->penjualan;

            $jurnal = null;
            if ($return->jenis_penyelesaian === 'uang') {
                $unitUsaha = $sale->unitUsaha ?? DB::table('master_unit_usaha')->where('id_unit_usaha', $sale->id_unit_usaha)->first();

                $jurnal = app(JurnalService::class)->posting(
                    'RJU',
                    [
                        'tanggal_jurnal' => $return->tgl_retur,
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

            $return->update([
                'status' => 'selesai',
                'status_posting' => $jurnal ? 'T' : 'F',
                'id_jurnal' => $jurnal?->id_jurnal,
            ]);

            return $return->fresh();
        });
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
