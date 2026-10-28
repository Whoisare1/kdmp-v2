<?php

namespace App\Models\Gudang;

use App\Models\Concerns\BelongsToEntitas;
use App\Models\Master\Gudang as GudangModel;
use App\Models\Master\Pihak;
use Illuminate\Database\Eloquent\Model;

class PenerimaanBarang extends Model
{
    use BelongsToEntitas;

    protected $table = 'penerimaan_barang';
    protected $primaryKey = 'id_penerimaan';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_entitas', 'id_gudang', 'kode_penerimaan', 'id_pembelian', 'id_pihak',
        'tanggal_terima', 'status', 'catatan', 'created_by',
    ];

    protected function casts(): array
    {
        return ['tanggal_terima' => 'date'];
    }

    public function gudang()
    {
        return $this->belongsTo(GudangModel::class, 'id_gudang', 'id_gudang');
    }

    public function pihak()
    {
        return $this->belongsTo(Pihak::class, 'id_pihak', 'id_pihak');
    }

    public function detail()
    {
        return $this->hasMany(PenerimaanBarangDetail::class, 'id_penerimaan', 'id_penerimaan');
    }
}

