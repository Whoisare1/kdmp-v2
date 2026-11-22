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
    private function wilayahAktifId(): int
    {
        $user = auth()->user();

        // 1. Cek id_entitas user desa jika terikat langsung
        $entitasId = $user?->id_entitas;

        // 2. Jika Super Admin (id_entitas null), baca dari pilihan session, binding, atau default entitas
        if (! $entitasId) {
            $entitasId = session('entitas_aktif_pilihan')
                ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
                ?? DB::table('entitas')->whereNotNull('id_wilayah')->where('status', 'APPROVED')->value('id_entitas')
                ?? DB::table('entitas')->whereNotNull('id_wilayah')->value('id_entitas');
        }

        $idWilayah = null;
        if ($entitasId) {
            $idWilayah = DB::table('entitas')->where('id_entitas', $entitasId)->value('id_wilayah');
        }

        // 3. Fallback ke wilayah pertama di database jika entitas belum punya wilayah
        if (! $idWilayah) {
            $idWilayah = DB::table('wilayah')->value('id');
        }

        if (! $idWilayah) {
            throw new HttpException(404, 'Belum ada data wilayah desa yang terdaftar di sistem.');
        }

        return (int) $idWilayah;
    }
}
