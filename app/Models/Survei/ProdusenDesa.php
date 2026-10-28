<?php

namespace App\Models\Survei;

use Illuminate\Database\Eloquent\Model;

class ProdusenDesa extends Model
{
    protected $table = 'produsen_desas';

    protected $fillable = [
        'id_wilayah', 'kategori', 'sub_kategori', 'nama_anggota', 'no_wa'
    ];

    public function wilayah()
    {
        return $this->belongsTo(\App\Models\Tenant\Wilayah::class, 'id_wilayah');
    }
}
