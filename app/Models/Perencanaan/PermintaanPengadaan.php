<?php

namespace App\Models\Perencanaan;

use App\Models\Concerns\BelongsToKoperasi;
use App\Models\Master\Pihak;
use App\Models\Pengguna;
use App\Models\Tenant\KoperasiDesa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PermintaanPengadaan extends Model
{
    use BelongsToKoperasi;

    protected $table = 'permintaan_pengadaan';
    protected $primaryKey = 'id_permintaan';

    protected $fillable = [
        'id_koperasi',
        'kode_permintaan',
        'id_pihak',
        'tgl_pengajuan',
        'total_nilai',
        'status',
        'catatan',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pengajuan' => 'date',
            'total_nilai' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function koperasi()
    {
        return $this->belongsTo(KoperasiDesa::class, 'id_koperasi', 'id_koperasi');
    }

    public function pihak()
    {
        return $this->belongsTo(Pihak::class, 'id_pihak', 'id_pihak');
    }

    public function pembuat()
    {
        return $this->belongsTo(Pengguna::class, 'created_by', 'id');
    }

    public function penyetuju()
    {
        return $this->belongsTo(Pengguna::class, 'approved_by', 'id');
    }

    public function detail()
    {
        return $this->hasMany(PermintaanPengadaanDetail::class, 'id_permintaan', 'id_permintaan');
    }

    /**
     * Label status bahasa Indonesia yang ramah.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft Rencana',
            'diajukan' => 'Menunggu Verifikasi',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'jadi_pembelian' => 'Diproses Pembelian (M5)',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

    /**
     * Kelas CSS badge status Ngobar.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'bg-paper-200 text-ink-700 border-paper-300',
            'diajukan' => 'bg-amber-100 text-amber-800 border-amber-300',
            'disetujui' => 'bg-sawah-100 text-sawah-800 border-sawah-300',
            'ditolak' => 'bg-merah-100 text-merah-800 border-merah-300',
            'jadi_pembelian' => 'bg-sky-100 text-sky-800 border-sky-300',
            'dibatalkan' => 'bg-paper-300 text-ink-600 border-paper-400',
            default => 'bg-paper-100 text-ink-800 border-paper-300',
        };
    }

    /**
     * Generate nomor rencana otomatis: PR-YYYYMM-XXXX
     */
    public static function generateKode(int $idKoperasi): string
    {
        $prefix = 'PR-' . date('Ym') . '-';
        $last = static::where('id_koperasi', $idKoperasi)
            ->where('kode_permintaan', 'like', $prefix . '%')
            ->orderByDesc('kode_permintaan')
            ->value('kode_permintaan');

        $nextSeq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
