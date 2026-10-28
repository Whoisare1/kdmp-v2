<?php

namespace Survei\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemografiNarasumber extends Model
{
    protected $table = 'demografi_narasumber';
    protected $primaryKey = 'id_demografi_ns';

    protected $fillable = [
        'id_narasumber',
        'id_sesi',
        'jumlah_kk',
        'kelompok_umur',
        'jumlah_laki',
        'jumlah_perempuan',
        'sumber_data',
        'tanggal_data',
        'keterangan_sumber',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_data'    => 'date',
            'jumlah_kk'       => 'integer',
            'jumlah_laki'     => 'integer',
            'jumlah_perempuan' => 'integer',
        ];
    }

    // ─── Konstanta ───────────────────────────────────────────────────────────

    const KELOMPOK_UMUR = ['Balita', 'Anak', 'Remaja', 'Dewasa', 'Lansia'];

    const SUMBER_DATA = [
        'Wawancara Narasumber',
        'Data RT/RW',
        'Data Perangkat Desa',
        'Pendataan Lapangan',
        'Lainnya',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function narasumber(): BelongsTo
    {
        return $this->belongsTo(NarasumberSesi::class, 'id_narasumber', 'id_narasumber');
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiSurvei::class, 'id_sesi');
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /** Total penduduk kelompok umur ini (L + P). */
    public function getTotalAttribute(): int
    {
        return $this->jumlah_laki + $this->jumlah_perempuan;
    }
}
