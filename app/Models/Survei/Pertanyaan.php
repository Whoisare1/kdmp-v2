<?php

namespace App\Models\Survei;

use Illuminate\Database\Eloquent\Model;

class Pertanyaan extends Model
{
    protected $table = 'pertanyaan';

    protected $fillable = [
        'id_modul', 'jenis_pendataan', 'id_kategori_komoditas', 'id_master_komoditas',
        'kode_pertanyaan', 'teks_pertanyaan', 'tipe_jawaban', 'satuan',
        'wajib_diisi', 'aturan_validasi_json', 'urutan', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'wajib_diisi' => 'boolean',
            'aturan_validasi_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function modul()
    {
        return $this->belongsTo(ModulSurvei::class, 'id_modul');
    }

    public function kategoriKomoditas()
    {
        return $this->belongsTo(\App\Models\Master\KategoriKomoditas::class, 'id_kategori_komoditas');
    }

    public function masterKomoditas()
    {
        return $this->belongsTo(\App\Models\Master\MasterKomoditas::class, 'id_master_komoditas');
    }
}
