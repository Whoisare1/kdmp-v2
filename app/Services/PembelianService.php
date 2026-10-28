<?php

namespace App\Services;

use App\Models\Pembelian\Pembelian;
use App\Models\Pembelian\DetailPembelian;
use App\Models\Pembelian\ReturPembelian;
use App\Models\Pembelian\ReturPembelianDetail;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Master\Barang;
use App\Models\Master\KasBank;
use App\Models\Gudang\PenerimaanBarang;
use App\Models\Gudang\PenerimaanBarangDetail;
use App\Services\Finance\JurnalService;
use Illuminate\Support\Facades\DB;

class PembelianService
{
    /**
     * Create Purchase Order (PO) from approved PR.
     *
     * @param PermintaanPengadaan $pr Approved PR
     * @param int $idPihak Supplier ID
     * @param string $jenisPembayaran 'tunai', 'transfer', or 'kredit'
     * @param int|null $idKasBank For tunai/transfer
     * @param string|null $tglJatuhTempo For kredit
     * @return Pembelian
     * @throws \Exception
     */
    public static function createFromPR(
        PermintaanPengadaan $pr,
        int $idPihak,
        string $jenisPembayaran,
        ?int $idKasBank = null,
        ?string $tglJatuhTempo = null,
    ): Pembelian {
        if ($pr->status !== 'disetujui') {
            throw new \Exception('PR harus berstatus "disetujui" sebelum dibuat PO.');
        }

        if ($jenisPembayaran === 'kredit' && !$tglJatuhTempo) {
            throw new \Exception('Jatuh tempo harus diisi untuk pembayaran kredit.');
        }

        if (in_array($jenisPembayaran, ['tunai', 'transfer'], true) && !$idKasBank) {
            throw new \Exception('Kas/Bank harus dipilih untuk pembayaran tunai/transfer.');
        }

        DB::beginTransaction();

        try {
            $kodePembelian = self::generatePurchaseCode(
                $pr->id_koperasi,
                $pr->tanggal_permintaan,
            );

            $totalPembelian = 0;

            foreach ($pr->detail as $item) {
                $totalPembelian += $item->total_estimasi ?? 0;
            }

            $pembelian = Pembelian::create([
                'id_koperasi' => $pr->id_koperasi,
                'kode_pembelian' => $kodePembelian,
                'id_permintaan' => $pr->id_permintaan,
                'id_pihak' => $idPihak,
                'id_unit_usaha' => $pr->id_unit_usaha,
                'id_gudang' => $pr->id_gudang,
                'tanggal_transaksi' => now()->toDateString(),
                'jenis_pembayaran' => $jenisPembayaran,
                'id_kas_bank' => $idKasBank,
                'tgl_jatuh_tempo' => $tglJatuhTempo,
                'total_pembelian' => $totalPembelian,
                'status' => 'draft',
                'status_posting' => 'F',
                'created_by' => auth()->id(),
            ]);

            foreach ($pr->detail as $prDetail) {
                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $prDetail->id_barang,
                    'id_satuan_input' => $prDetail->id_satuan_dasar,
                    'qty_input' => $prDetail->qty_dibutuhkan,
                    'faktor_konversi' => 1,
                    'qty_dasar' => $prDetail->qty_dibutuhkan,
                    'harga_satuan_input' => $prDetail->harga_estimasi ?? 0,
                    'subtotal' => ($prDetail->qty_dibutuhkan ?? 0)
                        * ($prDetail->harga_estimasi ?? 0),
                ]);
            }

            $pr->update([
                'status' => 'jadi_pembelian',
            ]);

            DB::commit();

            return $pembelian;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create quick purchase.
     *
     * Tunai  -> BPT
     * Transfer -> BTF
     *
     * @param int $kooperasiId
     * @param int $idPihak Farmer ID
     * @param int $idUnitUsaha
     * @param int $idGudang
     * @param array $items
     * @param string $jenisPembayaran 'tunai' or 'transfer'
     * @param int $idKasBank
     * @return Pembelian
     * @throws \Exception
     */
    public static function createQuickPurchase(
        int $kooperasiId,
        int $idPihak,
        int $idUnitUsaha,
        int $idGudang,
        array $items,
        string $jenisPembayaran,
        int $idKasBank,
    ): Pembelian {
        if (!in_array($jenisPembayaran, ['tunai', 'transfer'], true)) {
            throw new \Exception('Pembelian cepat hanya support tunai atau transfer.');
        }

        if (empty($items)) {
            throw new \Exception('Minimal satu item harus ditambahkan.');
        }

        if (!$idKasBank) {
            throw new \Exception('Kas/Bank harus dipilih untuk pembelian cepat.');
        }

        DB::beginTransaction();

        try {
            $kodePembelian = self::generatePurchaseCode(
                $kooperasiId,
                now()->toDateString(),
            );

            $totalPembelian = 0;

            foreach ($items as $item) {
                $totalPembelian +=
                    ($item['qty_dasar'] ?? 0)
                    * ($item['harga_satuan'] ?? 0);
            }

            $pembelian = Pembelian::create([
                'id_koperasi' => $kooperasiId,
                'kode_pembelian' => $kodePembelian,
                'id_permintaan' => null,
                'id_pihak' => $idPihak,
                'id_unit_usaha' => $idUnitUsaha,
                'id_gudang' => $idGudang,
                'tanggal_transaksi' => now()->toDateString(),
                'jenis_pembayaran' => $jenisPembayaran,
                'id_kas_bank' => $idKasBank,
                'tgl_jatuh_tempo' => null,
                'total_pembelian' => $totalPembelian,
                'status' => 'draft',
                'status_posting' => 'F',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $item['id_barang'],
                    'id_satuan_input' => $item['id_satuan'],
                    'qty_input' => $item['qty_dasar'],
                    'faktor_konversi' => 1,
                    'qty_dasar' => $item['qty_dasar'],
                    'harga_satuan_input' => $item['harga_satuan'],
                    'subtotal' =>
                        $item['qty_dasar'] * $item['harga_satuan'],
                ]);
            }

            DB::commit();

            return $pembelian;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve dan post pembelian ke jurnal.
     *
     * PR:
     * - tunai    -> BTU
     * - transfer -> BTF
     * - kredit   -> BKR
     *
     * Quick Purchase:
     * - tunai    -> BPT
     * - transfer -> BTF
     *
     * @param Pembelian $pembelian
     * @return Pembelian
     * @throws \Exception
     */
    public static function approvePembelian(Pembelian $pembelian): Pembelian
    {
        if ($pembelian->status !== 'draft') {
            throw new \Exception('Hanya pembelian draft yang bisa disetujui.');
        }

        DB::beginTransaction();

        try {
            $pembelian->loadMissing('unitUsaha');

            $transactionCode = match (true) {
                is_null($pembelian->id_permintaan)
                    && $pembelian->jenis_pembayaran === 'tunai'
                    => 'BPT',

                is_null($pembelian->id_permintaan)
                    && $pembelian->jenis_pembayaran === 'transfer'
                    => 'BTF',

                $pembelian->jenis_pembayaran === 'tunai'
                    => 'BTU',

                $pembelian->jenis_pembayaran === 'transfer'
                    => 'BTF',

                $pembelian->jenis_pembayaran === 'kredit'
                    => 'BKR',

                default => throw new \Exception(
                    'Jenis pembayaran pembelian tidak valid.'
                ),
            };

            $payload = [
                'tanggal_jurnal' => $pembelian->tanggal_transaksi,
                'nomor_nota' => $pembelian->kode_pembelian,
                'total_pembelian' => (float) $pembelian->total_pembelian,
                'id_kas_bank' => $pembelian->id_kas_bank,
                'kode_unit' => $pembelian->unitUsaha?->kode_unit_usaha,
                'id_pihak' => $pembelian->id_pihak,
            ];

            $jurnal = app(JurnalService::class)->posting(
                $transactionCode,
                $payload,
                Pembelian::class,
                (int) $pembelian->id_pembelian,
                "Pembelian {$pembelian->kode_pembelian}",
            );

            $pembelian->update([
                'status' => 'disetujui',
                'status_posting' => 'T',
                'id_jurnal' => $jurnal->id_jurnal,
            ]);

            DB::commit();

            return $pembelian->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create GRN (Goods Receipt Note) dan update stok.
     *
     * GRN tidak melakukan posting jurnal.
     * Posting jurnal pembelian dilakukan saat approvePembelian().
     *
     * @param Pembelian $pembelian
     * @param array $items
     * @return PenerimaanBarang
     * @throws \Exception
     */
    public static function createGRN(
        Pembelian $pembelian,
        array $items,
    ): PenerimaanBarang {
        if ($pembelian->status !== 'disetujui') {
            throw new \Exception('Pembelian harus disetujui terlebih dahulu.');
        }

        if (empty($items)) {
            throw new \Exception('Minimal satu item harus diterima.');
        }

        DB::beginTransaction();

        try {
            $kodePenerimaan = self::generateReceiptCode(
                $pembelian->id_koperasi,
                now()->toDateString(),
            );

            $penerimaan = PenerimaanBarang::create([
                'id_koperasi' => $pembelian->id_koperasi,
                'kode_penerimaan' => $kodePenerimaan,
                'id_pembelian' => $pembelian->id_pembelian,
                'id_pihak' => $pembelian->id_pihak,
                'id_gudang' => $pembelian->id_gudang,
                'tanggal_terima' => now()->toDateString(),
                'status' => 'diposting',
                'catatan' => "GRN untuk {$pembelian->kode_pembelian}",
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $detailPembelian = $pembelian
                    ->detail()
                    ->find($item['id_detail']);

                if (!$detailPembelian) {
                    throw new \Exception(
                        "Detail pembelian {$item['id_detail']} tidak ditemukan."
                    );
                }

                $qtyLayak = (float) ($item['qty_layak'] ?? 0);
                $qtyTidakLayak = (float) ($item['qty_tidak_layak'] ?? 0);
                $qtyTotalDiterima = $qtyLayak + $qtyTidakLayak;
                $hargaSatuan = (float) ($item['harga_satuan'] ?? 0);

                if ($qtyTotalDiterima <= 0) {
                    throw new \Exception(
                        "Qty diterima untuk detail {$detailPembelian->id_detail} harus lebih dari 0."
                    );
                }

                if ($qtyTotalDiterima > $detailPembelian->qty_dasar) {
                    throw new \Exception(
                        "Qty diterima untuk detail {$detailPembelian->id_detail} melebihi qty pesanan."
                    );
                }

                PenerimaanBarangDetail::create([
                    'id_penerimaan' => $penerimaan->id_penerimaan,
                    'id_barang' => $detailPembelian->id_barang,
                    'id_satuan_input' => $detailPembelian->id_satuan_input,
                    'qty_input' => $qtyTotalDiterima,
                    'faktor_konversi' => $detailPembelian->faktor_konversi,
                    'qty_dasar' => $qtyTotalDiterima,
                    'qty_layak' => $qtyLayak,
                    'qty_tidak_layak' => $qtyTidakLayak,
                    'harga_satuan_dasar' => $hargaSatuan,
                    'subtotal' => $qtyLayak * $hargaSatuan,
                    'alasan_tidak_layak' =>
                        $qtyTidakLayak > 0
                            ? 'Barang tidak layak'
                            : null,
                ]);

                if ($qtyLayak > 0) {
                    StokService::masukBarang(
                        $pembelian->id_koperasi,
                        $pembelian->id_gudang,
                        $detailPembelian->id_barang,
                        $qtyLayak,
                        $hargaSatuan,
                        $penerimaan->id_penerimaan,
                        'PENERIMAAN',
                    );
                }

                if ($qtyTidakLayak > 0) {
                    $retur = self::createRetur(
                        pembelian: $pembelian,
                        items: [[
                            'id_barang' => $detailPembelian->id_barang,
                            'qty_dasar' => $qtyTidakLayak,
                            'hpp_rata2' => $hargaSatuan,
                        ]],
                        jenisPenyelesaian: 'potong_hutang',
                        alasan: 'Barang tidak layak setelah sortir gudang.',
                    );

                    $retur->update([
                        'id_penerimaan' => $penerimaan->id_penerimaan,
                    ]);
                }
            }

            $pembelian->update([
                'status' => 'diterima',
            ]);

            $penerimaan->update([
                'status' => 'diposting',
            ]);

            DB::commit();

            return $penerimaan;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create purchase return.
     *
     * @param Pembelian $pembelian
     * @param array $items
     * @param string $jenisPenyelesaian
     * @param string $alasan
     * @return ReturPembelian
     * @throws \Exception
     */
    public static function createRetur(
        Pembelian $pembelian,
        array $items,
        string $jenisPenyelesaian,
        string $alasan,
    ): ReturPembelian {
        if (!in_array($pembelian->status, [
            'disetujui',
            'diterima',
            'selesai',
        ], true)) {
            throw new \Exception(
                'Hanya pembelian yang sudah disetujui atau diterima bisa di-retur.'
            );
        }

        if (empty($items)) {
            throw new \Exception('Minimal satu item harus di-retur.');
        }

        if (!in_array($jenisPenyelesaian, [
            'uang',
            'potong_hutang',
            'ganti_barang',
        ], true)) {
            throw new \Exception('Jenis penyelesaian tidak valid.');
        }

        DB::beginTransaction();

        try {
            $kodeRetur = self::generateReturnCode(
                $pembelian->id_koperasi,
                now()->toDateString(),
            );

            $totalNilai = 0;

            foreach ($items as $item) {
                $totalNilai +=
                    ($item['qty_dasar'] ?? 0)
                    * ($item['hpp_rata2'] ?? 0);
            }

            $retur = ReturPembelian::create([
                'id_koperasi' => $pembelian->id_koperasi,
                'kode_retur' => $kodeRetur,
                'id_pembelian' => $pembelian->id_pembelian,
                'tgl_retur' => now()->toDateString(),
                'jenis_penyelesaian' => $jenisPenyelesaian,
                'total_nilai' => $totalNilai,
                'alasan' => $alasan,
                'status' => 'diajukan',
                'status_posting' => 'F',
            ]);

            foreach ($items as $item) {
                ReturPembelianDetail::create([
                    'id_retur' => $retur->id_retur,
                    'id_barang' => $item['id_barang'],
                    'qty_dasar' => $item['qty_dasar'],
                    'hpp_rata2' => $item['hpp_rata2'],
                    'nilai' =>
                        $item['qty_dasar'] * $item['hpp_rata2'],
                ]);
            }

            DB::commit();

            return $retur;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve purchase return dan post jurnal.
     *
     * uang          -> RBU
     * potong_hutang -> RBH
     *
     * ganti_barang belum tersedia pada template jurnal baru.
     *
     * @param ReturPembelian $retur
     * @param int|null $idKasBank
     * @return ReturPembelian
     * @throws \Exception
     */
    public static function approveRetur(
        ReturPembelian $retur,
        ?int $idKasBank = null,
    ): ReturPembelian {
        if ($retur->status !== 'diajukan') {
            throw new \Exception(
                'Hanya retur yang diajukan yang bisa disetujui.'
            );
        }

        if (
            $retur->jenis_penyelesaian === 'uang'
            && !$idKasBank
        ) {
            throw new \Exception(
                'Kas/Bank harus dipilih untuk pengembalian uang.'
            );
        }

        if ($retur->jenis_penyelesaian === 'ganti_barang') {
            throw new \Exception(
                'Retur ganti barang belum dapat diposting karena template jurnal RGB belum tersedia.'
            );
        }

        DB::beginTransaction();

        try {
            $retur->loadMissing('pembelian.unitUsaha');

            $transactionCode = match ($retur->jenis_penyelesaian) {
                'uang' => 'RBU',
                'potong_hutang' => 'RBH',
                default => throw new \Exception(
                    'Jenis penyelesaian tidak dikenal.'
                ),
            };

            $pembelian = $retur->pembelian;

            if (!$pembelian) {
                throw new \Exception(
                    'Pembelian untuk retur tidak ditemukan.'
                );
            }

            $payload = [
                'tanggal_jurnal' => $retur->tgl_retur,
                'nomor_nota' => $retur->kode_retur,
                'total_nilai' => (float) $retur->total_nilai,
                'id_kas_bank' => $idKasBank,
                'kode_unit' => $pembelian->unitUsaha?->kode_unit_usaha,
                'id_pihak' => $pembelian->id_pihak,
            ];

            $jurnal = app(JurnalService::class)->posting(
                $transactionCode,
                $payload,
                ReturPembelian::class,
                (int) $retur->id_retur,
                "Retur {$retur->jenis_penyelesaian} untuk {$pembelian->kode_pembelian}",
            );

            $retur->update([
                'status' => 'selesai',
                'status_posting' => 'T',
                'id_jurnal' => $jurnal->id_jurnal,
            ]);

            DB::commit();

            return $retur->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private static function generatePurchaseCode(
        int $kooperasiId,
        string $tanggal
    ): string {
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $last = Pembelian::where('id_koperasi', $kooperasiId)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('id_pembelian')
            ->first();

        $seq = 1;

        if (
            $last
            && preg_match(
                '/(\d+)$/',
                $last->kode_pembelian,
                $matches
            )
        ) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf(
            'PO/%04d%02d/%04d',
            $tahun,
            $bulan,
            $seq
        );
    }

    private static function generateReceiptCode(
        int $kooperasiId,
        string $tanggal
    ): string {
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $last = PenerimaanBarang::where('id_koperasi', $kooperasiId)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('id_penerimaan')
            ->first();

        $seq = 1;

        if (
            $last
            && preg_match(
                '/(\d+)$/',
                $last->kode_penerimaan,
                $matches
            )
        ) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf(
            'GRN/%04d%02d/%04d',
            $tahun,
            $bulan,
            $seq
        );
    }

    private static function generateReturnCode(
        int $kooperasiId,
        string $tanggal
    ): string {
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $last = ReturPembelian::where('id_koperasi', $kooperasiId)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('id_retur')
            ->first();

        $seq = 1;

        if (
            $last
            && preg_match(
                '/(\d+)$/',
                $last->kode_retur,
                $matches
            )
        ) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf(
            'RET/%04d%02d/%04d',
            $tahun,
            $bulan,
            $seq
        );
    }
}
