<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Entitas extends Model
{
    protected $table = 'entitas';
    protected $primaryKey = 'id_entitas';

    protected $fillable = [
        'kode_entitas', 'nama_entitas', 'jenis_entitas', 'id_wilayah', 'badan_hukum_no',
        'tgl_berdiri', 'tahun_buku_awal', 'is_active', 'status'
    ];

    protected function casts(): array
    {
        return ['tgl_berdiri' => 'date', 'is_active' => 'boolean'];
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }

    public function periodeAkuntansi()
    {
        return $this->hasMany(PeriodeAkuntansi::class, 'id_entitas', 'id_entitas');
    }

    public function pengguna()
    {
        return $this->hasMany(\App\Models\Pengguna::class, 'id_entitas', 'id_entitas');
    }
}

