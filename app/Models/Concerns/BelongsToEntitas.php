<?php

namespace App\Models\Concerns;

use App\Scopes\EntitasScope;

trait BelongsToEntitas
{
    public static function bootBelongsToEntitas(): void
    {
        static::addGlobalScope(new EntitasScope);

        // Isi id_entitas otomatis saat membuat record baru
        static::creating(function ($model) {
            if (empty($model->id_entitas) && app()->bound('entitas_aktif')) {
                $model->id_entitas = app('entitas_aktif');
            }
        });
    }
}


