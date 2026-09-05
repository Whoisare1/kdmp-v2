<?php

namespace App\Services;

use App\Models\Gudang\KartuStok;
use App\Models\Gudang\Stok;
use Illuminate\Support\Facades\DB;
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
                'tanggal' => now()->toDateString(),
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
        ): KartuStok {
            $stok = Stok::query()
                ->where('id_gudang', $gudangId)
                ->where('id_barang', $barangId)
                ->lockForUpdate()
                ->first();

            if (! $stok || bccomp((string) $stok->qty_on_hand, $qty, 4) < 0) {
                throw new RuntimeException('Stok tidak mencukupi.');
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
                'tanggal' => now()->toDateString(),
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