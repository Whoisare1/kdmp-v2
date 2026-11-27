<?php

namespace App\Models\Pendataan;

use App\Models\Master\ItemKomoditas;
use App\Models\Tenant\Wilayah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Petani extends Model
{
    use HasFactory;

    protected $table = 'petani';

    protected $fillable = [
        'id_wilayah',
        'nama_lengkap',
        'nomor_hp',
        'alamat',
        'rt',
        'rw',
        'desa',
        'kecamatan',
        'kabupaten',
    ];

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(ItemKomoditas::class, 'potensi_produksi_petani', 'petani_id', 'item_komoditas_id')
            ->withPivot('id', 'luas_lahan_ha', 'estimasi_volume_panen', 'satuan', 'periode_panen', 'harga_satuan', 'total_nilai', 'keterangan')
            ->withTimestamps();
    }

    public function potensiProduksi(): HasMany
    {
        return $this->hasMany(PotensiProduksiPetani::class, 'petani_id');
    }
}

