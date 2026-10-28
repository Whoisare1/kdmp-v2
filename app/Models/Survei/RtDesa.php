<?php

namespace App\Models\Survei;

use Illuminate\Database\Eloquent\Model;

class RtDesa extends Model
{
    protected $table = 'rt_desas';

    protected $fillable = [
        'id_wilayah', 'id_sesi', 'dusun', 'rw', 'nama_rt', 'nama_ketua_rt', 'no_hp', 'total_kk_baseline', 'total_jiwa_baseline'
    ];

    public function sesi()
    {
        return $this->belongsTo(SesiSurvei::class, 'id_sesi');
    }

    public function wilayah()
    {
        return $this->belongsTo(\App\Models\Tenant\Wilayah::class, 'id_wilayah');
    }
}
