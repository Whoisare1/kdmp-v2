<?php

namespace App\Models\Master;

use App\Models\Concerns\BelongsToEntitas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AsetTetap extends Model
{
    use BelongsToEntitas;

    protected $table = 'aset_tetap';
    protected $primaryKey = 'id_aset';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas', 'kode_aset', 'nama_aset', 'kategori', 'tgl_perolehan',
        'nilai_perolehan', 'nilai_residu', 'umur_bulan', 'akum_penyusutan',
        'kode_akun_aset', 'kode_akun_akum', 'kode_akun_biaya', 'status',
    ];

    protected function casts(): array
    {
        return [
            'tgl_perolehan' => 'date',
            'nilai_perolehan' => 'decimal:2',
            'nilai_residu' => 'decimal:2',
            'akum_penyusutan' => 'decimal:2',
        ];
    }

    /**
     * Cek apakah aset sudah pernah disusutkan sehingga tanggal dan nilai perolehan harus dikunci.
     */
    public function sudahDisusutkan(): bool
    {
        if ((float) $this->akum_penyusutan > 0) {
            return true;
        }

        return DB::table('jurnal_header')
            ->where('id_entitas', $this->id_entitas)
            ->where('kode_transaksi', 'PNY')
            ->where('source_id', $this->id_aset)
            ->where('source_type', 'like', 'ASET_TETAP%')
            ->where('status', 'POSTED')
            ->exists();
    }
}

