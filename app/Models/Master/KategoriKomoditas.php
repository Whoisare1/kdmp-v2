<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriKomoditas extends Model
{
    use HasFactory;

    protected $table = 'kategori_komoditas';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function masterKomoditas(): HasMany
    {
        return $this->hasMany(MasterKomoditas::class, 'id_kategori');
    }

    public function itemKomoditas()
    {
        return $this->hasManyThrough(ItemKomoditas::class, MasterKomoditas::class, 'id_kategori', 'id_master_komoditas');
    }
}

