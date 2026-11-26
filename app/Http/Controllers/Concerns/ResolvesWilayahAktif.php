<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * entitas.id_wilayah unik (satu desa = satu koperasi) — dipakai modul
 * Perencanaan (Fase 7) untuk mengaitkan demografi/kebutuhan/neraca ke
 * wilayah desa milik pengguna yang login atau entitas aktif untuk Super Admin.
 */
trait ResolvesWilayahAktif
{
    protected function entitasAktifId(): int
    {
        $user = auth()->user();
        $entitasId = $user?->id_entitas
            ?? session('entitas_aktif_pilihan')
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);

        if (! $entitasId) {
            throw new HttpException(403, 'Entitas aktif belum dipilih untuk akun ini.');
        }

        $entitasId = (int) $entitasId;
        if (! DB::table('entitas')->where('id_entitas', $entitasId)->exists()) {
            throw new HttpException(404, 'Entitas aktif tidak ditemukan.');
        }

        return $entitasId;
    }

    protected function wilayahAktifId(): int
    {
        $idWilayah = DB::table('entitas')
            ->join('wilayah', 'wilayah.id', '=', 'entitas.id_wilayah')
            ->where('entitas.id_entitas', $this->entitasAktifId())
            ->where('wilayah.tingkat', 'desa')
            ->value('wilayah.id');

        if (! $idWilayah) {
            throw new HttpException(404, 'Entitas aktif belum terhubung dengan wilayah desa.');
        }

        return (int) $idWilayah;
    }
}
