<?php

namespace App\Models\Master;

use App\Models\Concerns\BelongsToEntitas;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use BelongsToEntitas;

    protected $table = 'gudang';
    protected $primaryKey = 'id_gudang';
    public $timestamps = false;

    protected $fillable = ['id_entitas', 'kode_gudang', 'nama_gudang', 'alamat', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}

