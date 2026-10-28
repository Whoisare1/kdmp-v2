<?php

namespace Survei\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NarasumberSesi extends Model
{
    protected $table = 'narasumber_sesi';
    protected $primaryKey = 'id_narasumber';

    protected $fillable = [
        'id_sesi',
        'nama_narasumber',
        'kategori',
        'nomor_kontak',
        'keterangan',
        'status',
    ];

    // ─── Konstanta Status ────────────────────────────────────────────────────

    const STATUS_BELUM  = 'belum_diisi';
    const STATUS_DRAFT  = 'draft';
    const STATUS_LENGKAP = 'lengkap';
    const STATUS_TERVERIFIKASI = 'terverifikasi';

    const LABEL_STATUS = [
        'belum_diisi'   => 'Belum Diisi',
        'draft'         => 'Draft',
        'lengkap'       => 'Lengkap',
        'terverifikasi' => 'Terverifikasi',
    ];

    const KATEGORI_LIST = [
        'Kepala Desa',
        'Perangkat Desa',
        'Kepala Dusun',
        'Ketua RW',
        'Ketua RT',
        'Lainnya',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiSurvei::class, 'id_sesi');
    }

    public function demografi(): HasMany
    {
        return $this->hasMany(DemografiNarasumber::class, 'id_narasumber', 'id_narasumber');
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /** Cek apakah narasumber sudah memiliki data demografi (semua 5 kelompok umur). */
    public function sudahLengkap(): bool
    {
        return $this->demografi()->count() >= 5;
    }

    /** Total penduduk dari semua kelompok umur narasumber ini. */
    public function totalPenduduk(): int
    {
        return (int) $this->demografi()->selectRaw('SUM(jumlah_laki + jumlah_perempuan) as total')->value('total');
    }
}
