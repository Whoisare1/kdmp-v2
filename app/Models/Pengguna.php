<?php

namespace App\Models;

use App\Models\Tenant\Entitas;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use Notifiable;

    protected $table = 'pengguna';

    protected $fillable = [
        'id_entitas', 'nama', 'email', 'password', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** NULL = pengguna tingkat pusat/pengawas, bukan milik satu desa. */
    public function entitas()
    {
        return $this->belongsTo(Entitas::class, 'id_entitas', 'id_entitas');
    }

    public function peran()
    {
        return $this->belongsToMany(Peran::class, 'pengguna_peran', 'id_pengguna', 'id_peran');
    }

    public function hasPeran(string $kode): bool
    {
        return $this->peran->contains('kode', $kode);
    }
}

