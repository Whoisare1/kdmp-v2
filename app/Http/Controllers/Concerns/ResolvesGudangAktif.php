<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Satu gudang per desa (README). Modul Gudang di Fase 2 mengasumsikan
 * pengguna yang login adalah pengguna desa (id_entitas terisi) â€” pengguna
 * pusat (id_entitas NULL, mode konsolidasi) belum didukung di sini.
 */
trait ResolvesGudangAktif
{
    private function gudangAktif(): object
    {
        $entitasId = auth()->user()->id_entitas
            ?? throw new HttpException(403, 'Modul Gudang hanya untuk pengguna desa, bukan pengguna pusat.');

        $gudang = DB::table('gudang')->where('id_entitas', $entitasId)->first();

        if (! $gudang) {
            throw new HttpException(404, 'Koperasi ini belum punya gudang.');
        }

        return $gudang;
    }
}


