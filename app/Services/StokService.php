<?php

namespace App\Services;

use App\Models\Gudang\KartuStok;
use App\Models\Gudang\Stok;
use Illuminate\Support\Facades\DB;

class StokService
{
    /**
     * Update stok saat penerimaan barang (GRN).
     * Calculate moving average HPP.
     *
     * @param int $kooperasiId
     * @param int $idGudang
     * @param int $idBarang
     * @param float $qtyMasuk Qty dasar (satuan terkecil)
     * @param float $hppSatuan HPP per satuan
     * @param int $idReferencePenerimaan
     * @param string $jenisMutasi 'PENERIMAAN', 'RETUR_PENJUALAN', etc.
     * @return Stok
     * @throws \Exception
     */
    public static function masukBarang(
        int $kooperasiId,
        int $idGudang,
        int $idBarang,
        float $qtyMasuk,
        float $hppSatuan,
        int $idReferencePenerimaan,
        string $jenisMutasi,
    ): Stok {
        if ($qtyMasuk <= 0) {
            throw new \Exception('Qty masuk harus lebih besar dari 0.');
        }

        DB::beginTransaction();
        try {
            $where = ['id_gudang' => $idGudang, 'id_barang' => $idBarang];

            $stok = DB::table('stok')->where($where)->first();
            if (!$stok) {
                DB::table('stok')->insert([
                    'id_gudang' => $idGudang,
                    'id_barang' => $idBarang,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'hpp_rata2' => 0,
                    'nilai_persediaan' => 0,
                ]);
                $stok = DB::table('stok')->where($where)->first();
            }

            // 2. Calculate moving average
            $qtyLama = (float) ($stok->qty_on_hand ?? 0);
            $hppLama = (float) ($stok->hpp_rata2 ?? 0);
            $nilaiLama = $qtyLama * $hppLama;
            $nilaiMasuk = $qtyMasuk * $hppSatuan;
            $qtyBaru = $qtyLama + $qtyMasuk;

            $hppBaru = $qtyBaru > 0 ? ($nilaiLama + $nilaiMasuk) / $qtyBaru : 0;

            // 3. Update stok
            DB::table('stok')->where($where)->update([
                'qty_on_hand' => $qtyBaru,
                'hpp_rata2' => $hppBaru,
                'nilai_persediaan' => $qtyBaru * $hppBaru,
            ]);

            $stok = Stok::where($where)->firstOrFail();

            // 4. Create kartu stok (detail mutasi)
            KartuStok::create([
                'id_koperasi' => $kooperasiId,
                'id_gudang' => $idGudang,
                'id_barang' => $idBarang,
                'tanggal' => now()->toDateString(),
                'jenis_mutasi' => 'IN',
                'ref_tipe' => $jenisMutasi,
                'ref_id' => $idReferencePenerimaan,
                'qty_masuk' => $qtyMasuk,
                'qty_keluar' => 0,
                'harga_satuan' => $hppSatuan,
                'nilai_mutasi' => $nilaiMasuk,
                'saldo_qty' => $qtyBaru,
                'saldo_nilai' => $qtyBaru * $hppBaru,
                'hpp_rata2_setelah' => $hppBaru,
            ]);

            DB::commit();

            return $stok;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update stok saat pengeluaran barang (penjualan, dll).
     * Gunakan HPP rata2 saat itu, JANGAN update HPP.
     *
     * @param int $kooperasiId
     * @param int $idGudang
     * @param int $idBarang
     * @param float $qtyKeluar
     * @param int $idReferencePenjualan
     * @return Stok
     * @throws \Exception
     */
    public static function keluarBarang(
        int $kooperasiId,
        int $idGudang,
        int $idBarang,
        float $qtyKeluar,
        int $idReferencePenjualan,
    ): Stok {
        if ($qtyKeluar <= 0) {
            throw new \Exception('Qty keluar harus lebih besar dari 0.');
        }

        DB::beginTransaction();
        try {
            // 1. Ambil stok
            $where = ['id_gudang' => $idGudang, 'id_barang' => $idBarang];
            $stok = DB::table('stok')->where($where)->firstOrFail();

            if ($stok->qty_on_hand < $qtyKeluar) {
                throw new \Exception(
                    "Stok tidak cukup. On hand: {$stok->qty_on_hand}, permintaan: {$qtyKeluar}"
                );
            }

            // 2. Gunakan HPP saat ini (JANGAN update HPP)
            $hppPakai = (float) ($stok->hpp_rata2 ?? 0);
            $nilaiKeluar = $qtyKeluar * $hppPakai;

            // 3. Update qty, TIDAK update HPP
            $qtyBaru = $stok->qty_on_hand - $qtyKeluar;
            $nilaiPersediaanBaru = $qtyBaru * $hppPakai;

            DB::table('stok')->where($where)->update([
                'qty_on_hand' => $qtyBaru,
                'nilai_persediaan' => $nilaiPersediaanBaru,
            ]);

            // 4. Create kartu stok
            KartuStok::create([
                'id_koperasi' => $kooperasiId,
                'id_gudang' => $idGudang,
                'id_barang' => $idBarang,
                'tanggal' => now()->toDateString(),
                'jenis_mutasi' => 'OUT',
                'ref_tipe' => 'PENJUALAN',
                'ref_id' => $idReferencePenjualan,
                'qty_masuk' => 0,
                'qty_keluar' => $qtyKeluar,
                'harga_satuan' => $hppPakai,
                'nilai_mutasi' => $nilaiKeluar,
                'saldo_qty' => $qtyBaru,
                'saldo_nilai' => $nilaiPersediaanBaru,
                'hpp_rata2_setelah' => $hppPakai,
            ]);

            DB::commit();

            return $stok;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse stock mutation (untuk pembatalan/koreksi).
     * Bayar perhatian: reversal hanya pada kartu stok, BUKAN update HPP.
     *
     * @param int $idKartuStok
     * @return void
     * @throws \Exception
     */
    public static function reverseMutasi(int $idKartuStok): void
    {
        DB::beginTransaction();
        try {
            $kartu = KartuStok::findOrFail($idKartuStok);

            // 1. Ambil stok
            $where = ['id_gudang' => $kartu->id_gudang, 'id_barang' => $kartu->id_barang];
            $stok = DB::table('stok')->where($where)->firstOrFail();

            // 2. Reverse: apa yang masuk jadi keluar, apa yang keluar jadi masuk
            $qtyBaru = $stok->qty_on_hand - $kartu->qty_masuk + $kartu->qty_keluar;

            // 3. Recalculate nilai (tergantung jenis reversal)
            if ($kartu->jenis_mutasi === 'PENERIMAAN') {
                // Penerimaan di-reverse: stok berkurang, tapi HPP tetap
                $hppBaru = $stok->hpp_rata2;
            } else {
                // Pengeluaran di-reverse: stok bertambah
                $hppBaru = $stok->hpp_rata2;
            }

            $nilaiPersediaanBaru = $qtyBaru * $hppBaru;

            // 4. Update stok
            DB::table('stok')->where($where)->update([
                'qty_on_hand' => $qtyBaru,
                'nilai_persediaan' => $nilaiPersediaanBaru,
            ]);

            // 5. Mark kartu stok as reversed
            $kartu->update(['is_reversed' => true]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate stok cocok antara sistem dan fisik (stock opname).
     *
     * @param int $idGudang
     * @param array $stokFisik [
     *     ['id_barang' => 1, 'qty_fisik' => 95],
     *     ...
     * ]
     * @return array Selisih per barang
     */
    public static function validateStockOpname(
        int $idGudang,
        array $stokFisik,
    ): array {
        $selisih = [];

        foreach ($stokFisik as $item) {
            $stok = Stok::where([
                'id_gudang' => $idGudang,
                'id_barang' => $item['id_barang'],
            ])->first();

            $qtySystem = $stok?->qty_on_hand ?? 0;
            $qtyFisik = $item['qty_fisik'] ?? 0;
            $diff = $qtyFisik - $qtySystem;

            if ($diff !== 0) {
                $selisih[] = [
                    'id_barang' => $item['id_barang'],
                    'qty_system' => $qtySystem,
                    'qty_fisik' => $qtyFisik,
                    'selisih' => $diff,
                    'jenis_selisih' => $diff > 0 ? 'LEBIH' : 'KURANG',
                ];
            }
        }

        return $selisih;
    }

    /**
     * Post stock opname adjustments ke kartu stok dan jurnal.
     *
     * @param int $idGudang
     * @param array $adjustments dari validateStockOpname
     * @return void
     */
    public static function postStockOpname(
        int $idGudang,
        array $adjustments,
    ): void {
        DB::beginTransaction();
        try {
            foreach ($adjustments as $adj) {
                $where = ['id_gudang' => $idGudang, 'id_barang' => $adj['id_barang']];
                $stok = DB::table('stok')->where($where)->firstOrFail();

                // Update qty
                $qtyBaru = $adj['qty_fisik'];
                $hppPakai = $stok->hpp_rata2;
                $nilaiPersediaanBaru = $qtyBaru * $hppPakai;

                DB::table('stok')->where($where)->update([
                    'qty_on_hand' => $qtyBaru,
                    'nilai_persediaan' => $nilaiPersediaanBaru,
                ]);

                // Create kartu stok untuk audit trail
                KartuStok::create([
                    'id_koperasi' => 1,
                    'id_gudang' => $idGudang,
                    'id_barang' => $adj['id_barang'],
                    'tanggal' => now()->toDateString(),
                    'jenis_mutasi' => $adj['jenis_selisih'] === 'LEBIH' ? 'ADJ_IN' : 'ADJ_OUT',
                    'ref_tipe' => 'OPNAME',
                    'ref_id' => 0,
                    'qty_masuk' => $adj['jenis_selisih'] === 'LEBIH' ? $adj['selisih'] : 0,
                    'qty_keluar' => $adj['jenis_selisih'] === 'KURANG' ? abs($adj['selisih']) : 0,
                    'harga_satuan' => $hppPakai,
                    'nilai_mutasi' => $adj['jenis_selisih'] === 'LEBIH'
                        ? $adj['selisih'] * $hppPakai
                        : abs($adj['selisih']) * $hppPakai,
                    'saldo_qty' => $qtyBaru,
                    'saldo_nilai' => $nilaiPersediaanBaru,
                    'hpp_rata2_setelah' => $hppPakai,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
