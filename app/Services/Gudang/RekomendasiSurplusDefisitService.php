<?php

namespace App\Services\Gudang;

use App\Models\Gudang\PermintaanAntarEntitas;
use App\Models\Gudang\RekomendasiSurplusDefisit;
use Illuminate\Support\Facades\DB;

class RekomendasiSurplusDefisitService
{
    public function lepaskanReservasiKedaluwarsa(): int
    {
        return DB::transaction(function (): int {
            $reservasiKedaluwarsa = RekomendasiSurplusDefisit::query()
                ->where('jenis_rekomendasi', 'penjualan')
                ->whereIn('status', ['siap_dilempar', 'menunggu'])
                ->where('tgl_berlaku_sampai', '<=', now())
                ->orderBy('id_rekomendasi')
                ->lockForUpdate()
                ->get();

            foreach ($reservasiKedaluwarsa as $reservasi) {
                $stok = DB::table('stok')
                    ->where('id_gudang', $reservasi->id_gudang)
                    ->where('id_barang', $reservasi->id_barang)
                    ->lockForUpdate()
                    ->first();

                if ($stok) {
                    $qtyReserved = bcsub(
                        (string) $stok->qty_reserved,
                        (string) $reservasi->qty_disetujui,
                        4,
                    );

                    DB::table('stok')
                        ->where('id_gudang', $reservasi->id_gudang)
                        ->where('id_barang', $reservasi->id_barang)
                        ->update([
                            'qty_reserved' => bccomp($qtyReserved, '0', 4) < 0 ? '0' : $qtyReserved,
                            'updated_at' => now(),
                        ]);
                }

                RekomendasiSurplusDefisit::query()
                    ->where('id_pengajuan_gudang', $reservasi->id_pengajuan_gudang)
                    ->where('id_barang', $reservasi->id_barang)
                    ->whereIn('status', ['siap_dilempar', 'menunggu'])
                    ->update(['status' => 'kadaluarsa']);

                PermintaanAntarEntitas::query()
                    ->where('id_permintaan_gudang', $reservasi->id_pengajuan_gudang)
                    ->whereIn('status', ['disetujui', 'dilempar'])
                    ->update(['status' => 'kadaluarsa']);
            }

            return $reservasiKedaluwarsa->count();
        });
    }
}
