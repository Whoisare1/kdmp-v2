<?php

namespace App\Models\Gudang;

use Illuminate\Database\Eloquent\Model;

class RekomendasiSurplusDefisit extends Model
{
    protected $table = 'rekomendasi_transaksi';
    protected $primaryKey = 'id_rekomendasi';

    protected $fillable = [
        'id_pengajuan_gudang',
        'id_gudang',
        'jenis_rekomendasi',
        'id_entitas_asal',
        'id_entitas_tujuan',
        'id_pihak_mitra',
        'id_barang',
        'nama_barang',
        'id_satuan',
        'qty_disetujui',
        'harga_satuan_kesepakatan',
        'id_unit_usaha',
        'tgl_berlaku_sampai',
        'id_transaksi_terbentuk',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'qty_disetujui' => 'decimal:4',
            'harga_satuan_kesepakatan' => 'decimal:2',
            'tgl_berlaku_sampai' => 'datetime',
            'id_gudang' => 'integer',
            'id_transaksi_terbentuk' => 'integer',
        ];
    }
}
