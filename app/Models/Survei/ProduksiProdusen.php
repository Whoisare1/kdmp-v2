<?php

namespace App\Models\Survei;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\Wilayah;

class ProduksiProdusen extends Model
{
    protected $table = 'produksi_produsen';

    protected $fillable = [
        'id_sesi',
        'id_wilayah',
        'nama_responden',
        'kategori',
        'sub_kategori',
        'nama_komoditas',
        'jumlah_produksi',
        'satuan',
        'bulan_produksi',
        'tahun_produksi',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_produksi' => 'decimal:2',
            'bulan_produksi'  => 'integer',
            'tahun_produksi'  => 'integer',
        ];
    }

    public function sesi()
    {
        return $this->belongsTo(SesiSurvei::class, 'id_sesi');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }

    /** Label bulan dalam bahasa Indonesia */
    public function getNamaBulanAttribute(): string
    {
        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return $bulans[$this->bulan_produksi] ?? '-';
    }
}
