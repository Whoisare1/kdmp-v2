<?php

namespace App\Models\Survei;

use Illuminate\Database\Eloquent\Model;

class AnggotaKeluarga extends Model
{
    protected $table = 'anggota_keluargas';

    protected $fillable = [
        'id_masyarakat', 'nama', 'umur', 'jenis_kelamin'
    ];

    public function masyarakat()
    {
        return $this->belongsTo(\App\Models\Survei\MasyarakatDesa::class, 'id_masyarakat');
    }
}
