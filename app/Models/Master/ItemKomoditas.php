<?php

namespace App\Models\Master;

use App\Models\Pendataan\Petani;
use App\Models\Pendataan\PotensiProduksiPetani;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemKomoditas extends Model
{
    use HasFactory;

    protected $table = 'item_komoditas';

    protected $fillable = [
        'id_master_komoditas',
        'kode',
        'nama',
        'satuan_standar',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function masterKomoditas(): BelongsTo
    {
        return $this->belongsTo(MasterKomoditas::class, 'id_master_komoditas');
    }

    public function petani(): BelongsToMany
    {
        return $this->belongsToMany(Petani::class, 'potensi_produksi_petani', 'item_komoditas_id', 'petani_id')
            ->withPivot('id', 'luas_lahan_ha', 'estimasi_volume_panen', 'satuan', 'periode_panen', 'harga_satuan', 'total_nilai', 'keterangan')
            ->withTimestamps();
    }

    public function potensiProduksi(): HasMany
    {
        return $this->hasMany(PotensiProduksiPetani::class, 'item_komoditas_id');
    }
}

