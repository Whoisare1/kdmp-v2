<?php

namespace App\Models\Gudang;

use App\Models\Concerns\BelongsToKoperasi;
use App\Models\Master\Barang;
use App\Models\Master\Gudang as GudangModel;
use Illuminate\Database\Eloquent\Model;

class KerusakanBarang extends Model
{
    use BelongsToKoperasi;

    protected $table = 'kerusakan_barang';
    protected $primaryKey = 'id_kerusakan';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_koperasi', 'id_gudang', 'id_barang', 'tanggal', 'qty', 'hpp_rata2',
        'nilai_kerugian', 'jenis_kejadian', 'keterangan', 'foto_bukti', 'status', 'approved_by',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function gudang()
    {
        return $this->belongsTo(GudangModel::class, 'id_gudang', 'id_gudang');
    }
}