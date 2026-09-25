<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeranSeeder extends Seeder
{
    public function run(): void
    {
        $peran = [
            ['surveyor', 'Surveyor'],
            ['admin_gudang', 'Admin Gudang'],
            ['kasir', 'Kasir'],
            ['pembelian', 'Pembelian'],
            ['akuntan', 'Akuntan'],
            ['manajer', 'Manajer'],
            ['super_admin', 'Super Admin'],
        ];

        foreach ($peran as [$kode, $nama]) {
            DB::table('peran')->updateOrInsert(['kode' => $kode], ['nama' => $nama]);
        }
    }
}
