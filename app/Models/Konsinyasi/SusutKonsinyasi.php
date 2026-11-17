<?php

namespace App\Models\Konsinyasi;

use App\Models\Pengguna;
use App\Models\Tenant\Entitas;
use Illuminate\Database\Eloquent\Model;

class SusutKonsinyasi extends Model
{
    protected $table = 'susut_konsinyasi';
    protected $primaryKey = 'id_susut_konsinyasi';

    protected $fillable = [
        'kode_susut', 'id_stok_konsinyasi', 'id_entitas_pemilik',
        'id_entitas_pencatat', 'id_pengguna', 'tanggal', 'jenis_kejadian',
        'qty', 'hpp_pemilik', 'nilai_susut', 'penanggung_susut', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'qty' => 'decimal:4',
            'hpp_pemilik' => 'decimal:4',
            'nilai_susut' => 'decimal:2',
        ];
    }

    public function stokKonsinyasi()
    {
        return $this->belongsTo(StokKonsinyasi::class, 'id_stok_konsinyasi', 'id_stok_konsinyasi');
    }

    public function entitasPemilik()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas_pemilik', 'id_entitas');
    }

    public function entitasPencatat()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas_pencatat', 'id_entitas');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id');
    }
}
