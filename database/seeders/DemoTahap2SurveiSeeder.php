<?php

namespace Database\Seeders;

use App\Models\Master\Komoditas;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Perencanaan\StandarKebutuhanKomoditas;
use App\Models\Survei\AnggotaKeluarga;
use App\Models\Survei\DemografiNarasumber;
use App\Models\Survei\Jawaban;
use App\Models\Survei\KonsumsiKeluarga;
use App\Models\Survei\MasyarakatDesa;
use App\Models\Survei\ModulSurvei;
use App\Models\Survei\NarasumberSesi;
use App\Models\Survei\Pertanyaan;
use App\Models\Survei\ProduksiNarasumber;
use App\Models\Survei\ProduksiProdusen;
use App\Models\Survei\ProdusenDesa;
use App\Models\Survei\RtDesa;
use App\Models\Survei\SesiSurvei;
use App\Models\Survei\StandarKonsumsi;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use App\Services\Survei\SurveySyncService;
use App\Services\Perencanaan\KalkulasiKebutuhanService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoTahap2SurveiSeeder extends Seeder
{
    /**
     * Mengisi data survei lapangan (Tahap 2 M1) lengkap untuk akun demo KDMP-DEMO:
     * - Data RT (RT 01 & RT 02 Dusun Krajan)
     * - Produsen Desa & Produksi (Kelompok Tani Makmur Beras 500 kg, Sumber Pangan Jagung 1000 kg)
     * - Responden Warga (4 KK, 14 jiwa lengkap dengan konsumsi pangan)
     * - Narasumber Sesi 1-3 & Rekap Demografi
     * - Jawaban Survei Sesi 1-3
     * - Sinkronisasi otomatis ke Modul Perencanaan M2/M3 (Demografi, Ketersediaan, Standar Kebutuhan)
     * - Perbandingan Harga & Master Pihak Supplier/Petani
     * - Kalkulasi Kebutuhan Awal (Beras Defisit -700 kg, Jagung Surplus +500 kg)
     */
    public function run(): void
    {
        $entitas = Entitas::where('kode_entitas', 'KDMP-DEMO')
            ->orWhere('id_entitas', 5)
            ->first();

        if (! $entitas) {
            $entitas = Entitas::where('status', 'APPROVED')->firstOrFail();
        }

        $idEntitas = (int) $entitas->id_entitas;
        $idWilayah = (int) ($entitas->id_wilayah ?? 130);
        $wilayah = Wilayah::find($idWilayah);

        $this->command?->info("Seeding data survei Tahap 2 untuk Entitas {$entitas->nama_entitas} (Wilayah {$wilayah?->nama})...");

        // Ambil atau buat sesi survei aktif untuk wilayah ini
        $sesi = SesiSurvei::where('id_wilayah', $idWilayah)->orderByDesc('id')->first();
        if (! $sesi) {
            $sesi = SesiSurvei::create([
                'id_wilayah' => $idWilayah,
                'tahun' => 2026,
                'bulan' => 11,
                'tanggal_survei' => '2026-11-17',
                'status' => 'disetujui',
                'token_rt' => Str::random(32),
                'token_masyarakat' => Str::random(32),
                'token_produsen' => Str::random(32),
                'id_petugas' => DB::table('pengguna')->where('id_entitas', $idEntitas)->value('id') ?? 1,
            ]);
        } else {
            $sesi->update(['status' => 'disetujui']);
        }

        // Resolusi Komoditas Pokok Global
        $komBeras = Komoditas::where('nama', 'Beras')->firstOrFail();
        $komJagung = Komoditas::where('nama', 'Jagung')->firstOrFail();

        // 1. Data RT Desa (Survei & Pemetaan ➔ Data RT)
        RtDesa::updateOrCreate(
            ['id_wilayah' => $idWilayah, 'id_sesi' => $sesi->id, 'nama_rt' => 'RT 01', 'rw' => 'RW 01', 'dusun' => 'Dusun Krajan'],
            [
                'nama_ketua_rt' => 'Bapak Suparno',
                'no_hp' => '081234567001',
                'total_kk_baseline' => 30,
                'total_jiwa_baseline' => 90,
            ]
        );

        RtDesa::updateOrCreate(
            ['id_wilayah' => $idWilayah, 'id_sesi' => $sesi->id, 'nama_rt' => 'RT 02', 'rw' => 'RW 01', 'dusun' => 'Dusun Krajan'],
            [
                'nama_ketua_rt' => 'Bapak Wagiman',
                'no_hp' => '081234567002',
                'total_kk_baseline' => 25,
                'total_jiwa_baseline' => 75,
            ]
        );

        // 2. Data Produsen Desa & Produksi (Survei & Pemetaan ➔ Produsen Desa)
        ProdusenDesa::updateOrCreate(
            ['id_wilayah' => $idWilayah, 'nama_anggota' => 'Kelompok Tani Makmur Jakenan'],
            [
                'id_sesi' => $sesi->id,
                'kategori' => 'Kelompok Tani',
                'sub_kategori' => 'Pangan Pokok',
                'no_wa' => '081234567890',
            ]
        );

        ProdusenDesa::updateOrCreate(
            ['id_wilayah' => $idWilayah, 'nama_anggota' => 'Kelompok Tani Sumber Pangan'],
            [
                'id_sesi' => $sesi->id,
                'kategori' => 'Kelompok Tani',
                'sub_kategori' => 'Palawija',
                'no_wa' => '081234567891',
            ]
        );

        ProduksiProdusen::updateOrCreate(
            ['id_sesi' => $sesi->id, 'id_wilayah' => $idWilayah, 'nama_responden' => 'Kelompok Tani Makmur Jakenan', 'nama_komoditas' => 'Beras'],
            [
                'kategori' => 'Kelompok Tani',
                'sub_kategori' => 'Pangan Pokok',
                'jumlah_produksi' => 500,
                'satuan' => 'KG',
                'harga' => 10000,
                'tipe_harga' => 'per_kg',
                'bulan_produksi' => 11,
                'tahun_produksi' => 2026,
                'keterangan' => 'Estimasi panen padi sawah irigasi musim tanam 3',
            ]
        );

        ProduksiProdusen::updateOrCreate(
            ['id_sesi' => $sesi->id, 'id_wilayah' => $idWilayah, 'nama_responden' => 'Kelompok Tani Sumber Pangan', 'nama_komoditas' => 'Jagung'],
            [
                'kategori' => 'Kelompok Tani',
                'sub_kategori' => 'Palawija',
                'jumlah_produksi' => 1000,
                'satuan' => 'KG',
                'harga' => 6500,
                'tipe_harga' => 'per_kg',
                'bulan_produksi' => 11,
                'tahun_produksi' => 2026,
                'keterangan' => 'Panen jagung pipil lahan kering',
            ]
        );

        // 3. Responden Warga Lapangan (Survei & Pemetaan ➔ Validasi Survei)
        $namaDesa = $wilayah?->nama ?? 'Jakenan';
        $sampleMasyarakat = [
            [
                'kepala' => 'Budi Santoso',
                'hp' => '0813900111',
                'umur' => 42,
                'jk' => 'Laki-laki',
                'rt' => '01',
                'rw' => '01',
                'dusun' => 'Krajan',
                'anggota' => [
                    ['nama' => 'Budi Santoso', 'umur' => 42, 'jk' => 'Laki-laki'],
                    ['nama' => 'Siti Aminah', 'umur' => 38, 'jk' => 'Perempuan'],
                    ['nama' => 'Doni Pratama', 'umur' => 12, 'jk' => 'Laki-laki'],
                    ['nama' => 'Maya Anisa', 'umur' => 4, 'jk' => 'Perempuan'],
                ],
                'konsumsi' => [
                    ['komoditas' => 'Beras', 'jumlah' => 30, 'satuan' => 'KG', 'harga' => 10000],
                    ['komoditas' => 'Minyak Goreng', 'jumlah' => 4, 'satuan' => 'LTR', 'harga' => 15000],
                    ['komoditas' => 'Gula Pasir', 'jumlah' => 3, 'satuan' => 'KG', 'harga' => 16000],
                    ['komoditas' => 'Telur Ayam', 'jumlah' => 4, 'satuan' => 'KG', 'harga' => 28000],
                ],
            ],
            [
                'kepala' => 'Joko Widodo',
                'hp' => '0813900112',
                'umur' => 45,
                'jk' => 'Laki-laki',
                'rt' => '01',
                'rw' => '01',
                'dusun' => 'Krajan',
                'anggota' => [
                    ['nama' => 'Joko Widodo', 'umur' => 45, 'jk' => 'Laki-laki'],
                    ['nama' => 'Rina Wulandari', 'umur' => 40, 'jk' => 'Perempuan'],
                    ['nama' => 'Bagas Pamungkas', 'umur' => 17, 'jk' => 'Laki-laki'],
                ],
                'konsumsi' => [
                    ['komoditas' => 'Beras', 'jumlah' => 25, 'satuan' => 'KG', 'harga' => 10000],
                    ['komoditas' => 'Jagung', 'jumlah' => 10, 'satuan' => 'KG', 'harga' => 6500],
                    ['komoditas' => 'Minyak Goreng', 'jumlah' => 3, 'satuan' => 'LTR', 'harga' => 15000],
                ],
            ],
            [
                'kepala' => 'Slamet Riyadi',
                'hp' => '0813900113',
                'umur' => 50,
                'jk' => 'Laki-laki',
                'rt' => '02',
                'rw' => '01',
                'dusun' => 'Krajan',
                'anggota' => [
                    ['nama' => 'Slamet Riyadi', 'umur' => 50, 'jk' => 'Laki-laki'],
                    ['nama' => 'Sri Rejeki', 'umur' => 46, 'jk' => 'Perempuan'],
                    ['nama' => 'Agus Salim', 'umur' => 10, 'jk' => 'Laki-laki'],
                    ['nama' => 'Mbah Karto', 'umur' => 72, 'jk' => 'Laki-laki'],
                ],
                'konsumsi' => [
                    ['komoditas' => 'Beras', 'jumlah' => 30, 'satuan' => 'KG', 'harga' => 10000],
                    ['komoditas' => 'Telur Ayam', 'jumlah' => 3, 'satuan' => 'KG', 'harga' => 28000],
                    ['komoditas' => 'Daging Sapi', 'jumlah' => 1, 'satuan' => 'KG', 'harga' => 125000],
                ],
            ],
            [
                'kepala' => 'Ahmad Fauzi',
                'hp' => '0813900114',
                'umur' => 35,
                'jk' => 'Laki-laki',
                'rt' => '02',
                'rw' => '01',
                'dusun' => 'Krajan',
                'anggota' => [
                    ['nama' => 'Ahmad Fauzi', 'umur' => 35, 'jk' => 'Laki-laki'],
                    ['nama' => 'Nur Hidayah', 'umur' => 32, 'jk' => 'Perempuan'],
                    ['nama' => 'Bayu Nugroho', 'umur' => 3, 'jk' => 'Laki-laki'],
                ],
                'konsumsi' => [
                    ['komoditas' => 'Beras', 'jumlah' => 20, 'satuan' => 'KG', 'harga' => 10000],
                    ['komoditas' => 'Minyak Goreng', 'jumlah' => 3, 'satuan' => 'LTR', 'harga' => 15000],
                ],
            ],
        ];

        foreach ($sampleMasyarakat as $dataM) {
            $masy = MasyarakatDesa::updateOrCreate(
                ['id_sesi' => $sesi->id, 'id_wilayah' => $idWilayah, 'nama_kepala_keluarga' => $dataM['kepala']],
                [
                    'nomor_hp' => $dataM['hp'],
                    'umur' => $dataM['umur'],
                    'jenis_kelamin' => $dataM['jk'],
                    'provinsi' => 'Jawa Tengah',
                    'kabupaten' => 'Pati',
                    'kecamatan' => 'Jakenan',
                    'desa' => $namaDesa,
                    'dusun' => $dataM['dusun'],
                    'rt' => $dataM['rt'],
                    'rw' => $dataM['rw'],
                    'nama_jalan' => 'Jl. Desa Jakenan',
                    'nomor_rumah' => '12',
                    'latitude' => -6.7500,
                    'longitude' => 111.1000,
                ]
            );

            AnggotaKeluarga::where('id_masyarakat', $masy->id)->delete();
            foreach ($dataM['anggota'] as $ak) {
                AnggotaKeluarga::create([
                    'id_masyarakat' => $masy->id,
                    'nama' => $ak['nama'],
                    'umur' => $ak['umur'],
                    'jenis_kelamin' => $ak['jk'],
                ]);
            }

            KonsumsiKeluarga::where('id_masyarakat', $masy->id)->delete();
            foreach ($dataM['konsumsi'] as $kk) {
                KonsumsiKeluarga::create([
                    'id_masyarakat' => $masy->id,
                    'nama_komoditas' => $kk['komoditas'],
                    'jumlah' => $kk['jumlah'],
                    'satuan' => $kk['satuan'],
                    'harga' => $kk['harga'],
                    'sumber_pemenuhan' => 'Warung/Toko Kelontong',
                    'lokasi_pemenuhan' => 'Dalam Desa',
                    'tipe_harga' => 'pasar',
                ]);
            }
        }

        // 4. Data Narasumber Sesi 1, 2, 3 (Survei Sesi 1 Demografi, Sesi 2 Produksi, Sesi 3 Konsumsi)
        $ns1 = NarasumberSesi::updateOrCreate(
            ['id_sesi' => $sesi->id, 'nama_narasumber' => 'Bapak Sudarsono'],
            [
                'kategori' => 'Kepala Dusun',
                'nomor_kontak' => '081211112222',
                'keterangan' => 'Narasumber data demografi RT 01-02 Dusun Krajan',
                'status' => 'terverifikasi',
            ]
        );

        DemografiNarasumber::where('id_sesi', $sesi->id)->where('id_narasumber', $ns1->id_narasumber)->delete();
        $demografiList = [
            ['umur' => 'Balita', 'l' => 10, 'p' => 10, 'kk' => 20],
            ['umur' => 'Anak',   'l' => 15, 'p' => 15, 'kk' => 25],
            ['umur' => 'Remaja', 'l' => 15, 'p' => 15, 'kk' => 25],
            ['umur' => 'Dewasa', 'l' => 30, 'p' => 30, 'kk' => 40],
            ['umur' => 'Lansia', 'l' => 12, 'p' => 13, 'kk' => 20],
        ];
        foreach ($demografiList as $dl) {
            DemografiNarasumber::create([
                'id_sesi' => $sesi->id,
                'id_narasumber' => $ns1->id_narasumber,
                'kelompok_umur' => $dl['umur'],
                'jumlah_laki' => $dl['l'],
                'jumlah_perempuan' => $dl['p'],
                'jumlah_kk' => $dl['kk'],
                'sumber_data' => 'Data RT/RW',
                'tanggal_data' => '2026-11-01',
            ]);
        }

        $ns2 = NarasumberSesi::updateOrCreate(
            ['id_sesi' => $sesi->id, 'nama_narasumber' => 'Bapak Subur Makmur'],
            [
                'kategori' => 'Kelompok Tani',
                'nomor_kontak' => '081233334444',
                'keterangan' => 'Narasumber potensi produksi panen kelompok tani desa',
                'status' => 'terverifikasi',
            ]
        );

        ProduksiNarasumber::where('id_sesi', $sesi->id)->where('id_narasumber', $ns2->id_narasumber)->delete();
        ProduksiNarasumber::create([
            'id_sesi' => $sesi->id,
            'id_narasumber' => $ns2->id_narasumber,
            'id_komoditas' => $komBeras->id,
            'jumlah_produksi' => 500,
            'satuan' => 'KG',
            'jumlah_produksi_kg' => 500,
            'periode' => 'Bulanan',
            'bulan_panen_json' => json_encode([11]),
            'jumlah_dijual' => 400,
            'jumlah_dikonsumsi_sendiri' => 100,
            'sumber_data' => 'Wawancara Narasumber',
            'tanggal_data' => '2026-11-01',
        ]);

        ProduksiNarasumber::create([
            'id_sesi' => $sesi->id,
            'id_narasumber' => $ns2->id_narasumber,
            'id_komoditas' => $komJagung->id,
            'jumlah_produksi' => 1000,
            'satuan' => 'KG',
            'jumlah_produksi_kg' => 1000,
            'periode' => 'Bulanan',
            'bulan_panen_json' => json_encode([11]),
            'jumlah_dijual' => 900,
            'jumlah_dikonsumsi_sendiri' => 100,
            'sumber_data' => 'Wawancara Narasumber',
            'tanggal_data' => '2026-11-01',
        ]);

        StandarKonsumsi::where('id_sesi', $sesi->id)->delete();
        $standarPerUmurList = [
            ['kom' => $komBeras->id, 'umur' => 'Balita', 'nilai' => 0.12, 'satuan' => 'KG'],
            ['kom' => $komBeras->id, 'umur' => 'Anak',   'nilai' => 0.20, 'satuan' => 'KG'],
            ['kom' => $komBeras->id, 'umur' => 'Remaja', 'nilai' => 0.28, 'satuan' => 'KG'],
            ['kom' => $komBeras->id, 'umur' => 'Dewasa', 'nilai' => 0.30, 'satuan' => 'KG'],
            ['kom' => $komBeras->id, 'umur' => 'Lansia', 'nilai' => 0.22, 'satuan' => 'KG'],
            ['kom' => $komJagung->id, 'umur' => 'Balita', 'nilai' => 0.05, 'satuan' => 'KG'],
            ['kom' => $komJagung->id, 'umur' => 'Anak',   'nilai' => 0.10, 'satuan' => 'KG'],
            ['kom' => $komJagung->id, 'umur' => 'Remaja', 'nilai' => 0.10, 'satuan' => 'KG'],
            ['kom' => $komJagung->id, 'umur' => 'Dewasa', 'nilai' => 0.12, 'satuan' => 'KG'],
            ['kom' => $komJagung->id, 'umur' => 'Lansia', 'nilai' => 0.08, 'satuan' => 'KG'],
        ];
        foreach ($standarPerUmurList as $sp) {
            StandarKonsumsi::create([
                'id_sesi' => $sesi->id,
                'id_komoditas' => $sp['kom'],
                'kategori_gender' => 'L',
                'kategori_umur' => $sp['umur'],
                'nilai_konsumsi' => $sp['nilai'],
                'satuan' => $sp['satuan'],
                'periode' => 'harian',
                'sumber_standar' => 'AKG Kemenkes & BPS 2026',
            ]);
        }

        // 5. Modul Survei & Pertanyaan & Jawaban (SurveySyncService bridge)
        $modulDemo = ModulSurvei::firstOrCreate(
            ['kode' => 'M1_DEMOGRAFI'],
            ['nama' => 'Sesi 1 Demografi Kependudukan', 'versi' => 'v1']
        );
        $modulProd = ModulSurvei::firstOrCreate(
            ['kode' => 'M1_PRODUKSI'],
            ['nama' => 'Sesi 2 Potensi Produksi Pertanian', 'versi' => 'v1']
        );
        $modulKonsum = ModulSurvei::firstOrCreate(
            ['kode' => 'M1_KONSUMSI'],
            ['nama' => 'Sesi 3 Standar Konsumsi Komoditas', 'versi' => 'v1']
        );

        $pDemo = Pertanyaan::firstOrCreate(
            ['id_modul' => $modulDemo->id, 'kode_pertanyaan' => 'DEMO_01'],
            ['teks_pertanyaan' => 'Data Demografi Per Kelompok Umur & Gender', 'tipe_jawaban' => 'json']
        );
        $pProd = Pertanyaan::firstOrCreate(
            ['id_modul' => $modulProd->id, 'kode_pertanyaan' => 'PROD_01'],
            ['teks_pertanyaan' => 'Jadwal dan Estimasi Panen Bulanan Komoditas', 'tipe_jawaban' => 'json']
        );
        $pKonsum = Pertanyaan::firstOrCreate(
            ['id_modul' => $modulKonsum->id, 'kode_pertanyaan' => 'KONSUM_01'],
            ['teks_pertanyaan' => 'Standar Konsumsi Komoditas Per Kapita', 'tipe_jawaban' => 'json']
        );

        // Jawaban Sesi 1 Demografi: Total 165 jiwa
        Jawaban::updateOrCreate(
            ['id_sesi' => $sesi->id, 'id_modul' => $modulDemo->id, 'id_pertanyaan' => $pDemo->id],
            [
                'nilai_json' => [
                    'balita' => ['laki_laki' => 10, 'perempuan' => 10],   // 20
                    'anak'   => ['laki_laki' => 15, 'perempuan' => 15],   // 30
                    'remaja' => ['laki_laki' => 15, 'perempuan' => 15],   // 30
                    'dewasa' => ['laki_laki' => 30, 'perempuan' => 30],   // 60
                    'lansia' => ['laki_laki' => 12, 'perempuan' => 13],   // 25
                ],
                'sumber' => 'manual',
            ]
        );

        // Jawaban Sesi 2 Produksi: Beras 500 kg/bln, Jagung 1.000 kg/bln
        Jawaban::updateOrCreate(
            ['id_sesi' => $sesi->id, 'id_modul' => $modulProd->id, 'id_pertanyaan' => $pProd->id],
            [
                'nilai_json' => [
                    [
                        'id_komoditas' => $komBeras->id,
                        'komoditas' => 'Beras',
                        'satuan' => 'KG',
                        'panen_bulanan' => [
                            1 => 500, 2 => 500, 3 => 500, 4 => 500, 5 => 500, 6 => 500,
                            7 => 500, 8 => 500, 9 => 500, 10 => 500, 11 => 500, 12 => 500,
                        ],
                    ],
                    [
                        'id_komoditas' => $komJagung->id,
                        'komoditas' => 'Jagung',
                        'satuan' => 'KG',
                        'panen_bulanan' => [
                            1 => 1000, 2 => 1000, 3 => 1000, 4 => 1000, 5 => 1000, 6 => 1000,
                            7 => 1000, 8 => 1000, 9 => 1000, 10 => 1000, 11 => 1000, 12 => 1000,
                        ],
                    ],
                ],
                'sumber' => 'manual',
            ]
        );

        // Jawaban Sesi 3 Konsumsi: Standar Bulanan kg/orang/bulan
        // Menghasilkan kebutuhan Beras = 1.209 kg/bln dan Jagung = 486 kg/bln
        Jawaban::updateOrCreate(
            ['id_sesi' => $sesi->id, 'id_modul' => $modulKonsum->id, 'id_pertanyaan' => $pKonsum->id],
            [
                'nilai_json' => [
                    [
                        'id_komoditas' => $komBeras->id,
                        'komoditas' => 'Beras',
                        'satuan' => 'kg/orang/bulan',
                        'standar' => [
                            'balita' => 3.6,
                            'anak'   => 6.0,
                            'remaja' => 8.4,
                            'dewasa' => 9.0,
                            'lansia' => 6.6,
                        ],
                    ],
                    [
                        'id_komoditas' => $komJagung->id,
                        'komoditas' => 'Jagung',
                        'satuan' => 'kg/orang/bulan',
                        'standar' => [
                            'balita' => 1.5,
                            'anak'   => 3.0,
                            'remaja' => 3.0,
                            'dewasa' => 3.6,
                            'lansia' => 2.4,
                        ],
                    ],
                ],
                'sumber' => 'manual',
            ]
        );

        // 6. Jalankan Sinkronisasi Survei ke Perencanaan M2/M3
        app()->instance('entitas_aktif', $idEntitas);
        $syncService = app(SurveySyncService::class);
        $syncService->syncSesi($sesi);

        // 7. Lengkapi Standar Kebutuhan Komoditas untuk komoditas entitas lainnya
        $standarDefaults = [
            'Beras'         => ['satuan' => 'KG',  'balita' => 0.12, 'anak' => 0.20, 'remaja' => 0.28, 'dewasa' => 0.30, 'lansia' => 0.22],
            'Jagung'        => ['satuan' => 'KG',  'balita' => 0.05, 'anak' => 0.10, 'remaja' => 0.10, 'dewasa' => 0.12, 'lansia' => 0.08],
            'Gula Pasir'    => ['satuan' => 'KG',  'balita' => 0.015, 'anak' => 0.025, 'remaja' => 0.035, 'dewasa' => 0.030, 'lansia' => 0.020],
            'Minyak Goreng' => ['satuan' => 'LTR', 'balita' => 0.010, 'anak' => 0.020, 'remaja' => 0.030, 'dewasa' => 0.025, 'lansia' => 0.020],
            'Telur Ayam'    => ['satuan' => 'KG',  'balita' => 0.040, 'anak' => 0.050, 'remaja' => 0.060, 'dewasa' => 0.050, 'lansia' => 0.040],
            'Daging Sapi'   => ['satuan' => 'KG',  'balita' => 0.010, 'anak' => 0.015, 'remaja' => 0.025, 'dewasa' => 0.020, 'lansia' => 0.015],
            'Cabai Merah'   => ['satuan' => 'KG',  'balita' => 0.005, 'anak' => 0.010, 'remaja' => 0.020, 'dewasa' => 0.025, 'lansia' => 0.015],
        ];

        $allKomoditas = Komoditas::where('is_active', true)->get();
        foreach ($allKomoditas as $kItem) {
            if (isset($standarDefaults[$kItem->nama])) {
                $st = $standarDefaults[$kItem->nama];
                foreach (['balita', 'anak', 'remaja', 'dewasa', 'lansia'] as $ku) {
                    StandarKebutuhanKomoditas::updateOrCreate(
                        [
                            'id_komoditas' => $kItem->id,
                            'kelompok_umur' => $ku,
                            'berlaku_mulai' => '2026-01-01',
                        ],
                        [
                            'sektor' => 'pangan',
                            'per_kapita_harian' => $st[$ku],
                            'satuan' => $st['satuan'],
                            'satuan_periode' => 'harian',
                            'sumber' => 'Standar Gizi BPS & AKG Kemenkes 2026',
                            'berlaku_sampai' => null,
                        ]
                    );
                }
            }
        }

        // 8. Perbandingan Harga (Tahap 3 Langkah 4)
        DB::table('perbandingan_harga')->updateOrInsert(
            [
                'id_entitas' => $idEntitas,
                'id_komoditas' => $komBeras->id,
                'id_wilayah_sumber' => $idWilayah,
                'tahun' => 2026,
                'bulan' => 11,
            ],
            [
                'harga_ditawarkan' => 10000,
                'jumlah_tersedia' => 500,
                'jarak_ke_gudang' => 0.0,
                'estimasi_ongkir' => 0,
                'harga_efektif' => 10000,
                'rank_harga' => 1,
                'dipilih' => true,
                'created_at' => now(),
            ]
        );

        $desaMitra = Wilayah::where('nama', 'Desa Sukamaju')->first();
        if ($desaMitra) {
            DB::table('perbandingan_harga')->updateOrInsert(
                [
                    'id_entitas' => $idEntitas,
                    'id_komoditas' => $komBeras->id,
                    'id_wilayah_sumber' => $desaMitra->id,
                    'tahun' => 2026,
                    'bulan' => 11,
                ],
                [
                    'harga_ditawarkan' => 10000,
                    'jumlah_tersedia' => 15000,
                    'jarak_ke_gudang' => 4.5,
                    'estimasi_ongkir' => 500,
                    'harga_efektif' => 10500,
                    'rank_harga' => 2,
                    'dipilih' => false,
                    'created_at' => now(),
                ]
            );
        }

        // 9. Master Pihak (Supplier/Petani) untuk Entitas
        DB::table('master_pihak')->updateOrInsert(
            ['id_entitas' => $idEntitas, 'nama' => 'Kelompok Tani Makmur Jakenan'],
            [
                'jenis_pihak' => 'petani',
                'is_anggota' => true,
                'no_anggota' => 'POKTAN-JKN-001',
                'alamat' => 'Dusun Krajan RT 01/01, Desa Jakenan',
                'no_hp' => '081234567890',
                'id_wilayah' => $idWilayah,
                'kualitas_rating' => 4.85,
                'estimasi_pengiriman' => '1 hari',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('master_pihak')->updateOrInsert(
            ['id_entitas' => $idEntitas, 'nama' => 'Toko Sembako Sumber Rejeki'],
            [
                'jenis_pihak' => 'warga',
                'is_anggota' => false,
                'alamat' => 'Jl. Raya Jakenan No. 12',
                'no_hp' => '081234567899',
                'id_wilayah' => $idWilayah,
                'kualitas_rating' => 4.9,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 10. Kalkulasi Awal Beras & Jagung untuk Tahap 3
        $kalkulasiService = app(KalkulasiKebutuhanService::class);
        $kalkulasiService->hitungKebutuhan($idWilayah, 2026, 11, $komBeras->id);
        $kalkulasiService->hitungKebutuhan($idWilayah, 2026, 11, $komJagung->id);

        // Selaraskan hasil kalkulasi Beras agar defisit pas 500 KG untuk skenario PO 500 KG Beras Mapan
        DB::table('hasil_kalkulasi')
            ->where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $komBeras->id)
            ->where('tahun', 2026)
            ->where('bulan', 11)
            ->update([
                'total_kebutuhan' => 1000,
                'total_ketersediaan' => 500,
                'selisih' => -500,
                'persentase_kecukupan' => 50.00,
                'status_surplus_defisit' => 'defisit',
                'alasan_rekomendasi' => 'Kebutuhan melebihi ketersediaan lokal; rekomendasi pengadaan 500 KG Beras Mapan.',
            ]);

        // Pastikan Beras Mapan terdaftar & aktif untuk Entitas ini
        $idSatuanKg = DB::table('master_satuan')->where('kode_satuan', 'KG')->value('id') 
            ?? DB::table('master_satuan')->where('kode_satuan', 'KG')->value('id_satuan') 
            ?? 1;
        $barangMapan = DB::table('master_barang')->where('kode_barang', 'BRS-MAPAN')->first();
        if (! $barangMapan) {
            $idBarangMapan = DB::table('master_barang')->insertGetId([
                'id_entitas' => $idEntitas,
                'kode_barang' => 'BRS-MAPAN',
                'id_komoditas' => $komBeras->id,
                'id_unit_usaha' => 1,
                'nama_barang' => 'Beras Mapan',
                'id_satuan_dasar' => $idSatuanKg,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $idBarangMapan = $barangMapan->id_barang;
        }

        DB::table('barang_per_koperasi')->updateOrInsert(
            ['id_entitas' => $idEntitas, 'id_barang' => $idBarangMapan],
            [
                'stok_minimum' => 100,
                'stok_maksimum' => 1000,
                'harga_jual_standar' => 18000,
                'is_dijual' => 1,
                'lacak_batch' => 0,
            ]
        );

        $this->command?->info("Seeder Tahap 2 Survei berhasil dieksekusi!");
    }
}

