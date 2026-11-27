<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterKomoditas extends Model
{
    use HasFactory;

    protected $table = 'master_komoditas';

    protected $fillable = [
        'id_kategori',
        'kode',
        'nama',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriKomoditas::class, 'id_kategori');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemKomoditas::class, 'id_master_komoditas');
    }
}

