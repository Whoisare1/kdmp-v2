<?php

namespace App\Models\Konsinyasi;

use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Tenant\KoperasiDesa;
use Illuminate\Database\Eloquent\Model;

/**
 * Stok titipan di gudang penerima — TERPISAH dari tabel stok karena BUKAN
 * aset desa penerima. Invariant: qty_titip = terjual + retur + susut + sisa.
 */
class StokKonsinyasi extends Model
{
    protected $table = 'stok_konsinyasi';
    protected $primaryKey = 'id_stok_konsinyasi';

    protected $fillable = [
        'id_kiriman', 'id_koperasi_pemilik', 'id_koperasi_penerima', 'id_gudang_penerima',
        'id_barang', 'qty_titip', 'qty_terjual', 'qty_retur', 'qty_susut', 'qty_sisa',
        'harga_titip_satuan', 'harga_jual_satuan', 'hpp_pemilik', 'status',
    ];

    protected function casts(): array
    {
        return [
            'qty_titip' => 'decimal:4',
            'qty_terjual' => 'decimal:4',
            'qty_retur' => 'decimal:4',
            'qty_susut' => 'decimal:4',
            'qty_sisa' => 'decimal:4',
            'harga_titip_satuan' => 'decimal:2',
            'harga_jual_satuan' => 'decimal:2',
            'hpp_pemilik' => 'decimal:4',
        ];
    }

    public function kiriman()
    {
        return $this->belongsTo(PengirimanKonsinyasi::class, 'id_kiriman', 'id_kiriman');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}
