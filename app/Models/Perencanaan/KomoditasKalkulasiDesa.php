<?php

namespace App\Models\Perencanaan;

use App\Models\Master\Komoditas;
use App\Models\Tenant\Wilayah;
use Illuminate\Database\Eloquent\Model;

class KomoditasKalkulasiDesa extends Model
{
    protected $table = 'komoditas_kalkulasi_desa';

    const UPDATED_AT = null;

    protected $fillable = ['id_wilayah', 'id_komoditas'];

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }

    public function komoditas()
    {
        return $this->belongsTo(Komoditas::class, 'id_komoditas');
    }
}
