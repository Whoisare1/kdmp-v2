<?php

namespace App\Models\Master;

use App\Models\Tenant\Entitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Komoditas extends Model
{
    protected $table = 'komoditas';

    protected $fillable = ['id_entitas', 'kategori', 'nama', 'alias_json', 'is_active'];

    protected function casts(): array
    {
        return ['alias_json' => 'array', 'is_active' => 'boolean'];
    }

    public static function hasEntitasColumn(): bool
    {
        return false;
    }

    public function scopeForEntitas(Builder $query, ?int $idEntitas = null): Builder
    {
        if (static::hasEntitasColumn()) {
            $query->where(function (Builder $q) use ($idEntitas) {
                $q->whereNull($this->getTable() . '.id_entitas');
                if ($idEntitas !== null) {
                    $q->orWhere($this->getTable() . '.id_entitas', $idEntitas);
                }
            });
        }

        return $query;
    }

    public function entitas()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas', 'id_entitas');
    }

    public function barang()
    {
        return $this->hasMany(Barang::class, 'id_komoditas');
    }
}
