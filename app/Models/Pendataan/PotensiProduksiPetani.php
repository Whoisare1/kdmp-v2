<?php

namespace App\Models\Pendataan;

use App\Models\Master\ItemKomoditas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PotensiProduksiPetani extends Model
{
    use HasFactory;

    protected $table = 'potensi_produksi_petani';

    protected $fillable = [
        'petani_id',
        'item_komoditas_id',
        'luas_lahan_ha',
        'estimasi_volume_panen',
        'satuan',
        'periode_panen',
        'harga_satuan',
        'total_nilai',
        'keterangan',
    ];

    protected $casts = [
        'luas_lahan_ha' => 'decimal:2',
        'estimasi_volume_panen' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'total_nilai' => 'decimal:2',
    ];

    public function petani(): BelongsTo
    {
        return $this->belongsTo(Petani::class, 'petani_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemKomoditas::class, 'item_komoditas_id');
    }
}

