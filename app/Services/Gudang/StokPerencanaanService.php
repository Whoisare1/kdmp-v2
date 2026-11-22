<?php

namespace App\Services\Gudang;

use Illuminate\Support\Facades\DB;

class StokPerencanaanService
{
    /**
     * Mengubah stok dasar setiap barang ke satuan yang dipakai perencanaan.
     * Barang yang tidak memiliki konversi valid tidak ikut dijumlahkan.
     */
    public function tersedia(int $entitasId, int $komoditasId, string $satuanPerencanaan, bool $hanyaDapatDijual = false): float
    {
        if ($entitasId <= 0 || $komoditasId <= 0 || trim($satuanPerencanaan) === '') {
            return 0.0;
        }

        $query = DB::table('stok')
            ->join('gudang', 'gudang.id_gudang', '=', 'stok.id_gudang')
            ->join('master_barang', 'master_barang.id_barang', '=', 'stok.id_barang')
            ->join('satuan as satuan_dasar', 'satuan_dasar.id', '=', 'master_barang.id_satuan_dasar')
            ->join('satuan as satuan_perencanaan', function ($join) use ($satuanPerencanaan): void {
                $join->whereRaw('UPPER(TRIM(satuan_perencanaan.kode_satuan)) = ?', [strtoupper(trim($satuanPerencanaan))]);
            })
            ->leftJoin('konversi_satuan', function ($join): void {
                $join->on('konversi_satuan.id_barang', '=', 'master_barang.id_barang')
                    ->on('konversi_satuan.id_satuan', '=', 'satuan_perencanaan.id');
            })
            ->where('gudang.id_entitas', $entitasId)
            ->where('master_barang.is_active', true)
            ->where('master_barang.id_komoditas', $komoditasId)
            ->where(function ($query): void {
                $query->whereColumn('satuan_dasar.id', 'satuan_perencanaan.id')
                    ->orWhere(function ($konversi): void {
                        $konversi->whereNotNull('konversi_satuan.id_konversi')
                            ->where('konversi_satuan.faktor_ke_dasar', '>', 0);
                    });
            });

        $query->leftJoin('barang_per_koperasi as pengaturan_stok', function ($join): void {
            $join->on('pengaturan_stok.id_entitas', '=', 'gudang.id_entitas')
                ->on('pengaturan_stok.id_barang', '=', 'stok.id_barang');
        });

        if ($hanyaDapatDijual) {
            $query->where('pengaturan_stok.is_dijual', true);
        }

        $pelacakanBatchTersedia = true;
        if ($pelacakanBatchTersedia) {
            $query->leftJoinSub(
                DB::table('stok_batch')
                    ->select('id_gudang', 'id_barang')
                    ->selectRaw('SUM(qty_on_hand) as qty_batch_belum_kedaluwarsa')
                    ->where('qty_on_hand', '>', 0)
                    ->where(function ($batch): void {
                        $batch->whereNull('tanggal_kedaluwarsa')
                            ->orWhereDate('tanggal_kedaluwarsa', '>=', now()->toDateString());
                    })
                    ->groupBy('id_gudang', 'id_barang'),
                'batch_belum_kedaluwarsa',
                function ($join): void {
                    $join->on('batch_belum_kedaluwarsa.id_gudang', '=', 'stok.id_gudang')
                        ->on('batch_belum_kedaluwarsa.id_barang', '=', 'stok.id_barang');
                },
            );
        }

        $qtyStokLayak = $pelacakanBatchTersedia
            ? 'CASE WHEN COALESCE(pengaturan_stok.lacak_batch, 0) = 1 '
                .'THEN COALESCE(batch_belum_kedaluwarsa.qty_batch_belum_kedaluwarsa, 0) '
                .'ELSE stok.qty_on_hand END'
            : 'stok.qty_on_hand';

        $qty = $query->selectRaw('
            SUM(
                GREATEST(('.$qtyStokLayak.') - stok.qty_reserved, 0)
                / CASE WHEN satuan_dasar.id = satuan_perencanaan.id
                    THEN 1
                    ELSE NULLIF(konversi_satuan.faktor_ke_dasar, 0)
                  END
            ) as qty_tersedia
        ')->value('qty_tersedia');

        return (float) ($qty ?? 0);
    }
}
