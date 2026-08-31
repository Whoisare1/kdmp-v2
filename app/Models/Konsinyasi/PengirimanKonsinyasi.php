<?php

namespace App\Models\Konsinyasi;

use App\Models\Master\Gudang;
use App\Models\Tenant\KoperasiDesa;
use Illuminate\Database\Eloquent\Model;

/**
 * Barang titipan TETAP MILIK desa pengirim sampai laku. Jurnal HANYA di
 * desa pengirim saat kirim — desa penerima tidak menjurnal apa pun.
 */
class PengirimanKonsinyasi extends Model
{
    protected $table = 'pengiriman_konsinyasi';
    protected $primaryKey = 'id_kiriman';

    protected $fillable = [
        'kode_kiriman', 'id_penawaran_barter', 'id_koperasi_pemilik', 'id_koperasi_penerima',
        'id_gudang_asal', 'id_gudang_tujuan', 'tgl_kirim', 'tgl_terima', 'tgl_batas_titip',
        'model_imbalan', 'persen_komisi', 'penanggung_susut', 'total_nilai_titip',
        'total_hpp_pemilik', 'status', 'status_posting', 'id_jurnal_kirim',
        'catatan_pengiriman', 'catatan_penerimaan',
    ];

    protected function casts(): array
    {
        return [
            'tgl_kirim' => 'date',
            'tgl_terima' => 'date',
            'tgl_batas_titip' => 'date',
            'persen_komisi' => 'decimal:2',
            'total_nilai_titip' => 'decimal:2',
            'total_hpp_pemilik' => 'decimal:2',
        ];
    }

    public function koperasiPemilik()
    {
        return $this->belongsTo(KoperasiDesa::class, 'id_koperasi_pemilik', 'id_koperasi');
    }

    public function koperasiPenerima()
    {
        return $this->belongsTo(KoperasiDesa::class, 'id_koperasi_penerima', 'id_koperasi');
    }

    public function gudangAsal()
    {
        return $this->belongsTo(Gudang::class, 'id_gudang_asal', 'id_gudang');
    }

    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'id_gudang_tujuan', 'id_gudang');
    }

    public function detail()
    {
        return $this->hasMany(PengirimanKonsinyasiDetail::class, 'id_kiriman', 'id_kiriman');
    }
}
