<?php

namespace App\Models\Gudang;

use App\Models\Concerns\BelongsToEntitas;
use App\Models\Master\UnitUsaha;
use Illuminate\Database\Eloquent\Model;

class AntreanJurnalGudang extends Model
{
    use BelongsToEntitas;

    protected $table = 'antrean_jurnal_gudang';
    protected $primaryKey = 'id_antrean';

    protected $fillable = [
        'id_entitas', 'jenis_transaksi', 'ref_tipe', 'ref_id', 'no_dokumen',
        'id_unit_usaha', 'tanggal', 'nilai', 'payload',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'payload' => 'array',
        ];
    }

    public function unitUsaha()
    {
        return $this->belongsTo(UnitUsaha::class, 'id_unit_usaha', 'id_unit_usaha');
    }
}
