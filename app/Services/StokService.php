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
use InvalidArgumentException;
use RuntimeException;

class StokService
{
    public function masuk(
        int $koperasiId,
        int $gudangId,
        int $barangId,
        string $qty,
        string $hargaSatuan,
        string $refTipe,
        int $refId,
        ?int $createdBy = null,
        ?string $tanggal = null,
    ): KartuStok {
        $this->pastikanPositif($qty, 'qty');
        $this->pastikanTidakNegatif($hargaSatuan, 'harga satuan');

        return DB::transaction(function () use (
            $koperasiId,
            $gudangId,
            $barangId,
            $qty,
            $hargaSatuan,
            $refTipe,
            $refId,
            $createdBy,
            $tanggal,
        ): KartuStok {
            $stok = Stok::query()
                ->where('id_gudang', $gudangId)
                ->where('id_barang', $barangId)
                ->lockForUpdate()
                ->first();

            if (! $stok) {
                $stok = new Stok([
                    'id_gudang' => $gudangId,
                    'id_barang' => $barangId,
                    'qty_on_hand' => '0',
                    'qty_reserved' => '0',
                    'hpp_rata2' => '0',
                    'nilai_persediaan' => '0',
                ]);
            }

            $nilaiMasuk = bcmul($qty, $hargaSatuan, 2);
            $qtyBaru = bcadd((string) $stok->qty_on_hand, $qty, 4);
            $nilaiBaru = bcadd((string) $stok->nilai_persediaan, $nilaiMasuk, 2);
            $hppBaru = bcdiv($nilaiBaru, $qtyBaru, 4);

            Stok::query()->updateOrInsert(
                [
                    'id_gudang' => $gudangId,
                    'id_barang' => $barangId,
                ],
                [
                    'qty_on_hand' => $qtyBaru,
                    'qty_reserved' => $stok->qty_reserved ?? '0',
                    'hpp_rata2' => $hppBaru,
                    'nilai_persediaan' => $nilaiBaru,
                    'updated_at' => now(),
                ],
            );

            return KartuStok::create([
                'id_koperasi' => $koperasiId,
                'id_gudang' => $gudangId,
                'id_barang' => $barangId,
                'tanggal' => $tanggal ?? now()->toDateString(),
                'jenis_mutasi' => 'IN',
                'ref_tipe' => $refTipe,
                'ref_id' => $refId,
                'qty_masuk' => $qty,
                'qty_keluar' => '0',
                'harga_satuan' => $hargaSatuan,
                'nilai_mutasi' => $nilaiMasuk,
                'saldo_qty' => $qtyBaru,
                'saldo_nilai' => $nilaiBaru,
                'hpp_rata2_setelah' => $hppBaru,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function keluar(
        int $koperasiId,
        int $gudangId,
        int $barangId,
        string $qty,
        string $refTipe,
        int $refId,
        ?int $createdBy = null,
        ?string $tanggal = null,
    ): KartuStok {
        $this->pastikanPositif($qty, 'qty');

        return DB::transaction(function () use (
            $koperasiId,
            $gudangId,
            $barangId,
            $qty,
            $refTipe,
            $refId,
            $createdBy,
            $tanggal,
        ): KartuStok {
            $stok = Stok::query()
                ->where('id_gudang', $gudangId)
                ->where('id_barang', $barangId)
                ->lockForUpdate()
                ->first();

            if (! $stok || bccomp((string) $stok->qty_on_hand, $qty, 4) < 0) {
                $tersedia = $stok?->qty_on_hand ?? '0';
                throw new RuntimeException("Stok tidak mencukupi. Tersedia {$tersedia}, diminta {$qty}.");
            }

            $nilaiKeluar = bcmul($qty, (string) $stok->hpp_rata2, 2);
            $qtyBaru = bcsub((string) $stok->qty_on_hand, $qty, 4);
            $nilaiBaru = bcsub((string) $stok->nilai_persediaan, $nilaiKeluar, 2);
            $hppTetap = (string) $stok->hpp_rata2;

            Stok::query()
                ->where('id_gudang', $gudangId)
                ->where('id_barang', $barangId)
                ->update([
                    'qty_on_hand' => $qtyBaru,
                    'nilai_persediaan' => $nilaiBaru,
                    'updated_at' => now(),
                ]);

            return KartuStok::create([
                'id_koperasi' => $koperasiId,
                'id_gudang' => $gudangId,
                'id_barang' => $barangId,
                'tanggal' => $tanggal ?? now()->toDateString(),
                'jenis_mutasi' => 'OUT',
                'ref_tipe' => $refTipe,
                'ref_id' => $refId,
                'qty_masuk' => '0',
                'qty_keluar' => $qty,
                'harga_satuan' => $hppTetap,
                'nilai_mutasi' => $nilaiKeluar,
                'saldo_qty' => $qtyBaru,
                'saldo_nilai' => $nilaiBaru,
                'hpp_rata2_setelah' => $hppTetap,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function hppSaatIni(int $gudangId, int $barangId): string
    {
        return (string) (DB::table('stok')
            ->where('id_gudang', $gudangId)
            ->where('id_barang', $barangId)
            ->value('hpp_rata2') ?? '0');
    }

    public function tersedia(int $gudangId, int $barangId): string
    {
        return (string) (DB::table('stok')
            ->where('id_gudang', $gudangId)
            ->where('id_barang', $barangId)
            ->value('qty_on_hand') ?? '0');
    }

    private function pastikanPositif(string $value, string $field): void
    {
        if (bccomp($value, '0', 4) <= 0) {
            throw new InvalidArgumentException("{$field} harus lebih besar dari nol.");
        }
    }

    private function pastikanTidakNegatif(string $value, string $field): void
    {
        if (bccomp($value, '0', 4) < 0) {
            throw new InvalidArgumentException("{$field} tidak boleh negatif.");
        }
    }
}
