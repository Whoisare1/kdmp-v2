<?php

namespace App\Models\Survei;

use App\Models\Master\Komoditas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProduksiNarasumber extends Model
{
    protected $table = 'produksi_narasumber';
    protected $primaryKey = 'id_produksi_ns';

    protected $fillable = [
        'id_sesi',
        'id_narasumber',
        'id_komoditas',
        'jumlah_produksi',
        'satuan',
        'jumlah_produksi_kg',
        'periode',
        'bulan_panen_json',
        'jumlah_dijual',
        'jumlah_dikonsumsi_sendiri',
        'kendala_json',
        'kendala_produksi',
        'sumber_data',
        'tanggal_data',
        'keterangan_sumber',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_data'              => 'date',
            'jumlah_produksi'          => 'float',
            'jumlah_produksi_kg'       => 'float',
            'jumlah_dijual'            => 'float',
            'jumlah_dikonsumsi_sendiri' => 'float',
            'bulan_panen_json'         => 'array',
            'kendala_json'             => 'array',
        ];
    }

    const KATEGORI_KOMODITAS_LIST = [
        'Pertanian',
        'Perkebunan',
        'Perikanan',
        'Peternakan',
    ];

    const SATUAN_LIST = [
        'kg',
        'ton',
        'kuintal',
        'liter',
        'ekor',
        'buah',
        'unit',
    ];

    const BULAN_PANEN_LIST = [
        'Jan' => 'Januari',
        'Feb' => 'Februari',
        'Mar' => 'Maret',
        'Apr' => 'April',
        'Mei' => 'Mei',
        'Jun' => 'Juni',
        'Jul' => 'Juli',
        'Agu' => 'Agustus',
        'Sep' => 'September',
        'Okt' => 'Oktober',
        'Nov' => 'November',
        'Des' => 'Desember',
    ];

    const POLA_PANEN_LIST = [
        'Beberapa bulan tertentu',
        'Musiman',
        'Tidak menentu',
    ];

    const KENDALA_LIST = [
        'Hama',
        'Gulma',
        'Penyakit',
        'Kesulitan pupuk',
        'Kekurangan air/irigasi',
        'Cuaca',
        'Bencana',
        'Modal',
        'Tenaga kerja',
        'Bibit',
        'Pakan',
        'Pemasaran',
        'Gelombang/ kondisi perairan',
        'Lainnya',
        'Tidak ada kendala',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiSurvei::class, 'id_sesi');
    }

    public function narasumber(): BelongsTo
    {
        return $this->belongsTo(NarasumberSesi::class, 'id_narasumber', 'id_narasumber');
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'id_komoditas');
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /**
     * Hitung sisa produksi: Jumlah Produksi - Jumlah Dijual - Jumlah Dikonsumsi
     */
    public function getSisaProduksiAttribute(): float
    {
        return max(0, $this->jumlah_produksi - ($this->jumlah_dijual ?? 0) - ($this->jumlah_dikonsumsi_sendiri ?? 0));
    }

    /**
     * Konversi jumlah produksi dari berbagai satuan ke Kg (perkiraan standar).
     */
    public static function konversiKeKg(?float $jumlah, ?string $satuan): float
    {
        if (!$jumlah) {
            return 0.0;
        }

        return match (strtolower($satuan ?? 'kg')) {
            'ton'     => $jumlah * 1000.0,
            'kuintal' => $jumlah * 100.0,
            'kg'      => $jumlah,
            'liter'   => $jumlah * 1.0,   // Perkiraan 1L ≈ 1Kg
            default   => $jumlah,
        };
    }

    /**
     * Format array bulan panen menjadi string yang mudah dibaca.
     */
    public function getBulanPanenFormatAttribute(): string
    {
        if (empty($this->bulan_panen_json)) {
            return '-';
        }

        $bulanList = [];
        foreach ($this->bulan_panen_json as $b) {
            $bulanList[] = self::BULAN_PANEN_LIST[$b] ?? $b;
        }

        return implode(', ', $bulanList);
    }

    /**
     * Format kendala menjadi string.
     */
    public function getKendalaFormatAttribute(): string
    {
        if (!empty($this->kendala_json)) {
            return implode(', ', $this->kendala_json);
        }

        return $this->kendala_produksi ?: '-';
    }
}
