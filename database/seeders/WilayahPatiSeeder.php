<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WilayahPatiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat Kabupaten Pati
        $kabupaten = \App\Models\Tenant\Wilayah::firstOrCreate(
            ['tingkat' => 'kab', 'nama' => 'Pati'],
            ['parent_id' => null]
        );

        // 2. Baca file JSON
        $jsonPath = base_path('scratch_wilayah.json');
        if (!file_exists($jsonPath)) {
            $this->command->error("File scratch_wilayah.json tidak ditemukan!");
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        // 3. Looping Kecamatan dan Desa
        foreach ($data as $namaKecamatan => $desas) {
            $kecamatan = \App\Models\Tenant\Wilayah::firstOrCreate(
                [
                    'parent_id' => $kabupaten->id,
                    'tingkat' => 'kec',
                    'nama' => $namaKecamatan,
                ]
            );

            foreach ($desas as $namaDesa) {
                \App\Models\Tenant\Wilayah::firstOrCreate(
                    [
                        'parent_id' => $kecamatan->id,
                        'tingkat' => 'desa',
                        'nama' => $namaDesa,
                    ]
                );
            }
        }

        $this->command->info("Berhasil mengimpor wilayah Kabupaten Pati!");
    }
}
