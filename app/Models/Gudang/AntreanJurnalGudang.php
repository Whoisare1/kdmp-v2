<?php

namespace App\Models\Gudang;

use App\Models\Akuntansi\JurnalHeader;
use App\Models\Concerns\BelongsToEntitas;
use App\Models\Master\UnitUsaha;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntreanJurnalGudang extends Model
{
    use BelongsToEntitas;

    protected $table = 'antrean_jurnal_gudang';
    protected $primaryKey = 'id_antrean';

    protected $fillable = [
        'id_entitas',
        'jenis_transaksi',
        'ref_tipe',
        'ref_id',
        'no_dokumen',
        'id_unit_usaha',
        'tanggal',
        'nilai',
        'payload',
        'status',
        'id_jurnal',
        'no_jurnal',
        'kode_error',
        'pesan_error',
        'jumlah_percobaan',
        'diproses_pada',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nilai' => 'decimal:2',
            'payload' => 'array',
            'diproses_pada' => 'datetime',
            'jumlah_percobaan' => 'integer',
        ];
    }

    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class, 'id_unit_usaha', 'id_unit_usaha');
    }

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(JurnalHeader::class, 'id_jurnal', 'id_jurnal');
    }
}

