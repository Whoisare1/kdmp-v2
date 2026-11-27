<?php

namespace App\Models\Pendataan;

use App\Models\Tenant\Wilayah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendudukDesa extends Model
{
    use HasFactory;

    protected $table = 'penduduk_desa';

    protected $fillable = [
        'id_wilayah',
        'tahun',
        'balita',
        'anak',
        'remaja',
        'dewasa',
        'lansia',
        'total_penduduk',
        'sumber_data',
        'keterangan',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'balita' => 'integer',
        'anak' => 'integer',
        'remaja' => 'integer',
        'dewasa' => 'integer',
        'lansia' => 'integer',
        'total_penduduk' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            $model->total_penduduk = (int)$model->balita + (int)$model->anak + (int)$model->remaja + (int)$model->dewasa + (int)$model->lansia;
        });
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }
}

