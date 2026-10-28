<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Konfigurasi extends Model
{
    protected $table = 'konfigurasi';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['id_entitas', 'kunci', 'nilai', 'keterangan'];
}

