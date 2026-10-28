<?php

namespace App\Models\Survei;

use Illuminate\Database\Eloquent\Model;

class KonsumsiKeluarga extends Model
{
    protected $fillable = [
        'id_masyarakat',
        'nama_komoditas',
        'jumlah',
        'satuan',
        'sumber_pemenuhan',
        'lokasi_pemenuhan',
        'harga',
        'tipe_harga',
    ];

    public function masyarakat()
    {
        return $this->belongsTo(MasyarakatDesa::class, 'id_masyarakat');
    }
}
