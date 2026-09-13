<?php

namespace Survei\Models;

use App\Models\Master\Komoditas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel standar_konsumsi.
 * Menyimpan standar konsumsi per orang berdasarkan komoditas × kelompok umur × gender.
 */
class StandarKonsumsi extends Model
{
    protected $table      = 'standar_konsumsi';
    protected $primaryKey = 'id_standar';

    protected $fillable = [
        'id_sesi',
        'id_komoditas',
        'kategori_gender',
        'kategori_umur',
        'nilai_konsumsi',
        'satuan',
        'periode',
        'nilai_per_tahun_standar',
        'sumber_standar',
        'periode_standar',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'nilai_konsumsi'         => 'decimal:4',
            'nilai_per_tahun_standar'=> 'decimal:4',
        ];
    }

    // ─── Konstanta ────────────────────────────────────────────────────────────

    const KELOMPOK_UMUR = ['Balita', 'Anak', 'Remaja', 'Dewasa', 'Lansia'];

    const KATEGORI_GENDER = [
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
    ];

    const PERIODE_LIST = [
        'harian'   => 'Harian',
        'mingguan' => 'Mingguan',
        'bulanan'  => 'Bulanan',
        'tahunan'  => 'Tahunan',
    ];

    const SATUAN_LIST = [
        'kg/orang/bulan',
        'kg/orang/tahun',
        'gram/orang/hari',
        'liter/orang/bulan',
        'butir/orang/hari',
        'butir/orang/bulan',
        'porsi/orang/hari',
    ];

    const SUMBER_LIST = [
        'Angka Kecukupan Gizi (AKG) Kemenkes',
        'Standar Konsumsi BPS',
        'Penelitian Gizi Lokal',
        'Data Survei SUSENAS',
        'Standar FAO/WHO',
        'Data Dinas Pertanian',
        'Kesepakatan Komunitas',
        'Lainnya',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'id_komoditas');
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    /** Label gender yang lebih ramah tampilan */
    public function getLabelGenderAttribute(): string
    {
        return self::KATEGORI_GENDER[$this->kategori_gender] ?? $this->kategori_gender;
    }

    /**
     * Hitung nilai_per_tahun_standar berdasarkan nilai_konsumsi + periode.
     * Hasil: nilai per orang per tahun dalam satuan yang sama.
     */
    public static function konversiKeTahunan(float $nilai, string $periode): float
    {
        return match ($periode) {
            'harian'   => $nilai * 365,
            'mingguan' => $nilai * 52,
            'bulanan'  => $nilai * 12,
            'tahunan'  => $nilai,
            default    => $nilai * 12,
        };
    }
}
