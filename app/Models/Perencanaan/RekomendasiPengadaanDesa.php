<?php

namespace App\Models\Perencanaan;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekomendasiPengadaanDesa extends Model
{
    protected $table = 'rekomendasi_pengadaan_desa';
    protected $primaryKey = 'id_rekomendasi';

    protected $fillable = [
        'id_hasil_defisit',
        'id_hasil_surplus',
        'id_pengaju',
        'diputuskan_oleh',
        'status',
        'catatan_penyetuju',
        'diputuskan_pada',
    ];

    protected function casts(): array
    {
        return ['diputuskan_pada' => 'datetime'];
    }

    public function hasilDefisit(): BelongsTo
    {
        return $this->belongsTo(HasilKalkulasi::class, 'id_hasil_defisit', 'id_hasil');
    }

    public function hasilSurplus(): BelongsTo
    {
        return $this->belongsTo(HasilKalkulasi::class, 'id_hasil_surplus', 'id_hasil');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengaju');
    }

    public function pemutus(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diputuskan_oleh');
    }
}
