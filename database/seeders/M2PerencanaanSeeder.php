<?php

namespace Database\Seeders;

use App\Models\Master\Komoditas;
use App\Models\Master\Satuan;
use App\Models\Perencanaan\StandarKebutuhanKomoditas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class M2PerencanaanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Satuan Dasar
        $satuanList = [
            ['kode' => 'KG', 'alias' => ['Kilogram', 'kg']],
            ['kode' => 'LTR', 'alias' => ['Liter', 'ltr', 'l']],
            ['kode' => 'BUTIR', 'alias' => ['Butir', 'btr']],
        ];

        foreach ($satuanList as $s) {
            DB::table('satuan')->updateOrInsert(
                ['kode_satuan' => $s['kode']],
                [
                    'alias_json' => json_encode($s['alias']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Master Komoditas Pokok
        $komoditasList = [
            ['kategori' => 'pangan_pokok', 'nama' => 'Beras'],
            ['kategori' => 'sembako', 'nama' => 'Gula Pasir'],
            ['kategori' => 'sembako', 'nama' => 'Minyak Goreng'],
            ['kategori' => 'hewani', 'nama' => 'Telur Ayam'],
            ['kategori' => 'hewani', 'nama' => 'Daging Sapi'],
        ];

        $komoditasIds = [];
        foreach ($komoditasList as $k) {
            $record = Komoditas::updateOrCreate(
                ['nama' => $k['nama']],
                [
                    'kategori' => $k['kategori'],
                    'is_active' => true,
                ]
            );
            $komoditasIds[$k['nama']] = $record->id;
        }

        // 3. Standar Kebutuhan Komoditas (Konsumsi harian per kapita)
        // Sumber: Standar Gizi AKG Kemenkes / BPS
        $standarGizi = [
            'Beras' => [
                'satuan' => 'KG',
                'nilai' => [
                    'balita' => 0.120000,
                    'anak' => 0.200000,
                    'remaja' => 0.280000,
                    'dewasa' => 0.300000,
                    'lansia' => 0.220000,
                ],
            ],
            'Gula Pasir' => [
                'satuan' => 'KG',
                'nilai' => [
                    'balita' => 0.015000,
                    'anak' => 0.025000,
                    'remaja' => 0.035000,
                    'dewasa' => 0.030000,
                    'lansia' => 0.020000,
                ],
            ],
            'Minyak Goreng' => [
                'satuan' => 'LTR',
                'nilai' => [
                    'balita' => 0.010000,
                    'anak' => 0.020000,
                    'remaja' => 0.030000,
                    'dewasa' => 0.025000,
                    'lansia' => 0.020000,
                ],
            ],
            'Telur Ayam' => [
                'satuan' => 'KG',
                'nilai' => [
                    'balita' => 0.040000,
                    'anak' => 0.050000,
                    'remaja' => 0.060000,
                    'dewasa' => 0.050000,
                    'lansia' => 0.040000,
                ],
            ],
            'Daging Sapi' => [
                'satuan' => 'KG',
                'nilai' => [
                    'balita' => 0.010000,
                    'anak' => 0.015000,
                    'remaja' => 0.025000,
                    'dewasa' => 0.020000,
                    'lansia' => 0.015000,
                ],
            ],
        ];

        foreach ($standarGizi as $namaKomoditas => $data) {
            $idKom = $komoditasIds[$namaKomoditas];
            foreach ($data['nilai'] as $umur => $perKapita) {
                DB::table('standar_kebutuhan_komoditas')->updateOrInsert(
                    [
                        'id_komoditas' => $idKom,
                        'kelompok_umur' => $umur,
                        'berlaku_mulai' => '2026-01-01',
                    ],
                    [
                        'sektor' => 'pangan',
                        'per_kapita_harian' => $perKapita,
                        'satuan' => $data['satuan'],
                        'sumber' => 'AKG Kemenkes & BPS 2026',
                        'berlaku_sampai' => null,
                        'created_at' => now(),
                    ]
                );
            }
        }

    }
}
