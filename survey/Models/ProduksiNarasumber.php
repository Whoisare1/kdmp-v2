<?php

namespace Survei\Models;

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

    const PERIODE_LIST = [
        'Mingguan',
        'Bulanan',
        'Musiman',
        'Tahunan',
        'Lainnya',
    ];

    const SUMBER_DATA_LIST = [
        'Wawancara Narasumber / Pelaku Produksi',
        'Data Kelompok Tani / Nelayan',
        'Pendataan Petugas Lapangan',
        'Data Pengepul / Koperasi',
        'Data Penyuluh / Perangkat Desa',
        'Lainnya',
    ];

    /** Helper Konversi ke Satuan Dasar (kg / unit standar) */
    public static function konversiKeKg(float $jumlah, string $satuan): float
    {
        $s = strtolower(trim($satuan));
        return match($s) {
            'ton'     => $jumlah * 1000,
            'kuintal' => $jumlah * 100,
            default   => $jumlah,
        };
    }

    /** Mapping rekomendasi kategori komoditas berdasarkan kategori narasumber */
    public static function getSuggestedKategoriForNarasumber(string $kategoriNs): array
    {
        $k = strtolower(trim($kategoriNs));
        if (str_contains($k, 'ternak')) {
            return ['Peternakan'];
        }
        if (str_contains($k, 'tani') || str_contains($k, 'petani') || str_contains($k, 'penyuluh')) {
            return ['Pertanian', 'Perkebunan'];
        }
        if (str_contains($k, 'nelayan') || str_contains($k, 'budidaya') || str_contains($k, 'tambak')) {
            return ['Perikanan'];
        }
        return self::KATEGORI_KOMODITAS_LIST;
    }

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

    // ─── Accessor ────────────────────────────────────────────────────────────

    /** Kalkulasi Sisa Produksi = Jumlah Produksi - (Jumlah Dijual + Jumlah Dikonsumsi) */
    public function getSisaProduksiAttribute(): float
    {
        $sisa = $this->jumlah_produksi - ($this->jumlah_dijual + $this->jumlah_dikonsumsi_sendiri);
        return max(0, $sisa);
    }

    /** Kalkulasi Sisa Produksi Satuan Dasar (kg) */
    public function getSisaProduksiKgAttribute(): float
    {
        $prodKg = $this->jumlah_produksi_kg ?: self::konversiKeKg($this->jumlah_produksi, $this->satuan);
        $dijualKg = self::konversiKeKg($this->jumlah_dijual, $this->satuan);
        $konsumsiKg = self::konversiKeKg($this->jumlah_dikonsumsi_sendiri, $this->satuan);

        $sisaKg = $prodKg - ($dijualKg + $konsumsiKg);
        return max(0, $sisaKg);
    }
}
