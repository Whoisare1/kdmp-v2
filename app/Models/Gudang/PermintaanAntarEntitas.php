<?php

namespace App\Models\Gudang;

use App\Models\Pengguna;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Tenant\Entitas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanAntarEntitas extends Model
{
    protected $table = 'permintaan_antar_entitas_gudang';
    protected $primaryKey = 'id_permintaan_gudang';

    protected $fillable = [
        'id_entitas_pengaju', 'id_entitas_penerima', 'id_hasil_pengaju', 'id_hasil_penerima',
        'jenis_permintaan', 'nama_komoditas', 'tahun', 'bulan', 'qty_rekomendasi', 'satuan',
        'status', 'created_by', 'id_persetujuan_pengaju', 'tgl_persetujuan_pengaju',
        'id_persetujuan_penerima', 'tgl_persetujuan_penerima', 'disetujui_penuh_pada',
        'qty_dialokasikan',
    ];

    protected function casts(): array
    {
        return [
            'qty_rekomendasi' => 'decimal:4',
            'qty_dialokasikan' => 'decimal:4',
            'tahun' => 'integer',
            'bulan' => 'integer',
            'tgl_persetujuan_pengaju' => 'datetime',
            'tgl_persetujuan_penerima' => 'datetime',
            'disetujui_penuh_pada' => 'datetime',
        ];
    }

    public function pengaju()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas_pengaju', 'id_entitas');
    }

    public function penerima()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas_penerima', 'id_entitas');
    }

    public function hasilPengaju()
    {
        return $this->belongsTo(HasilKalkulasi::class, 'id_hasil_pengaju', 'id_hasil');
    }

    public function hasilPenerima()
    {
        return $this->belongsTo(HasilKalkulasi::class, 'id_hasil_penerima', 'id_hasil');
    }

    public function pembuat()
    {
        return $this->belongsTo(Pengguna::class, 'created_by', 'id');
    }

    public function rekomendasiGudang(): HasMany
    {
        return $this->hasMany(RekomendasiSurplusDefisit::class, 'id_pengajuan_gudang', 'id_permintaan_gudang');
    }
}
