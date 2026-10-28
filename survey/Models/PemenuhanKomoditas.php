<?php

namespace Survei\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Master\Komoditas;

class PemenuhanKomoditas extends Model
{
    protected $table = 'pemenuhan_komoditas';

    protected $fillable = [
        'id_sesi',
        'id_komoditas',
        'sumber_pemenuhan',
        'nama_tempat',
        'harga',
        'satuan_harga',
        'tanggal_survei',
        'ketersediaan',
        'keterangan',
    ];

    protected $casts = [
        'harga'          => 'decimal:2',
        'tanggal_survei' => 'date',
    ];

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiSurvei::class, 'id_sesi');
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'id_komoditas');
    }
}
