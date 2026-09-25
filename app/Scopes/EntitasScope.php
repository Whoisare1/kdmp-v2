<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Isolasi tenant di level baris.
 *
 * Tenant aktif diambil dari container binding 'entitas_aktif' yang diisi
 * middleware SetEntitasAktif. Kalau tidak ada tenant aktif (mis. artisan
 * command atau pengguna tingkat pusat), scope tidak dipasang.
 *
 * JANGAN gunakan withoutGlobalScope di luar modul pelaporan konsolidasi.
 */
class EntitasScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $id = app()->bound('entitas_aktif') ? app('entitas_aktif') : null;

        if ($id !== null) {
            $builder->where($model->getTable().'.id_entitas', $id);
        }
    }
}

