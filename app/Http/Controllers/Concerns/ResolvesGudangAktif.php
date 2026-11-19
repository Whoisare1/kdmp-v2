<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Satu gudang per desa (README). Modul Gudang mendukung pengguna desa
 * maupun Super Admin melalui entitas aktif session/default.
 */
trait ResolvesGudangAktif
{
    private function gudangAktif(): object
    {
        $user = auth()->user();
        $entitasId = $user?->id_entitas
            ?? session('entitas_aktif_pilihan')
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? DB::table('gudang')->value('id_entitas')
            ?? DB::table('entitas')->value('id_entitas');

        $gudang = null;
        if ($entitasId) {
            $gudang = DB::table('gudang')->where('id_entitas', $entitasId)->first();
        }

        if (! $gudang) {
            $gudang = DB::table('gudang')->first();
        }

        if (! $gudang) {
            throw new HttpException(404, 'Koperasi ini belum punya gudang.');
        }

        return $gudang;
    }
}
