<?php

namespace Database\Seeders;

use App\Models\Master\ItemKomoditas;
use App\Models\Master\KategoriKomoditas;
use App\Models\Master\MasterKomoditas;
use App\Models\Pendataan\PendudukDesa;
use App\Models\Pendataan\Petani;
use App\Models\Tenant\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KomoditasHierarkiSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan kategori perkebunan lama dipindahkan ke pertanian jika ada
        $perkebunanOld = KategoriKomoditas::where('kode', 'PERKEBUNAN')->first();
        $pertanianCat = KategoriKomoditas::where('kode', 'PERTANIAN')->first();
        if ($perkebunanOld && $pertanianCat) {
            MasterKomoditas::where('id_kategori', $perkebunanOld->id)->update(['id_kategori' => $pertanianCat->id]);
            $perkebunanOld->delete();
        }

        $kategoriData = [
            [
                'kode' => 'PERTANIAN',
                'nama' => 'Pertanian',
                'deskripsi' => 'Komoditas tanaman pangan, hortikultura, dan perkebunan',
                'masters' => [
                    [
                        'kode' => 'BERAS',
                        'nama' => 'Beras',
                        'items' => [
                            ['kode' => 'BERAS-MENTIK', 'nama' => 'Beras Mentik', 'satuan' => 'kg'],
                            ['kode' => 'BERAS-LELE', 'nama' => 'Beras Lele', 'satuan' => 'kg'],
                            ['kode' => 'BERAS-IR64', 'nama' => 'Beras IR64', 'satuan' => 'kg'],
                        ]
                    ],
                    [
                        'kode' => 'CABAI',
                        'nama' => 'Cabai',
                        'items' => [
                            ['kode' => 'CABAI-RAWIT', 'nama' => 'Cabai Rawit', 'satuan' => 'kg'],
                            ['kode' => 'CABAI-KERITING', 'nama' => 'Cabai Merah Keriting', 'satuan' => 'kg'],
                        ]
                    ],
                    [
                        'kode' => 'BAWANG',
                        'nama' => 'Bawang',
                        'items' => [
                            ['kode' => 'BAWANG-MERAH', 'nama' => 'Bawang Merah', 'satuan' => 'kg'],
                            ['kode' => 'BAWANG-PUTIH', 'nama' => 'Bawang Putih', 'satuan' => 'kg'],
                        ]
                    ],
                    [
                        'kode' => 'KEDELAI',
                        'nama' => 'Kedelai',
                        'items' => [
                            ['kode' => 'KEDELAI-KUNING', 'nama' => 'Kedelai Kuning', 'satuan' => 'kg'],
                        ]
                    ],
                    [
                        'kode' => 'KOPI',
                        'nama' => 'Kopi',
                        'items' => [
                            ['kode' => 'KOPI-ROBUSTA', 'nama' => 'Kopi Robusta', 'satuan' => 'kg'],
                            ['kode' => 'KOPI-ARABIKA', 'nama' => 'Kopi Arabika', 'satuan' => 'kg'],
                        ]
                    ],
                    [
                        'kode' => 'GULA',
                        'nama' => 'Gula',
                        'items' => [
                            ['kode' => 'GULA-TEBU', 'nama' => 'Gula Tebu', 'satuan' => 'kg'],
                            ['kode' => 'GULA-KELAPA', 'nama' => 'Gula Kelapa / Semut', 'satuan' => 'kg'],
                        ]
                    ],
                ]
            ],
            [
                'kode' => 'PETERNAKAN',
                'nama' => 'Peternakan',
                'deskripsi' => 'Komoditas peternakan unggas dan ternak',
                'masters' => [
                    [
                        'kode' => 'AYAM',
                        'nama' => 'Ayam',
                        'items' => [
                            ['kode' => 'AYAM-BROILER', 'nama' => 'Ayam Broiler', 'satuan' => 'kg'],
                            ['kode' => 'AYAM-KAMPUNG', 'nama' => 'Ayam Kampung', 'satuan' => 'kg'],
                        ]
                    ],
                    [
                        'kode' => 'TELUR',
                        'nama' => 'Telur',
                        'items' => [
                            ['kode' => 'TELUR-RAS', 'nama' => 'Telur Ayam Ras', 'satuan' => 'kg'],
                            ['kode' => 'TELUR-KAMPUNG', 'nama' => 'Telur Ayam Kampung', 'satuan' => 'kg'],
                        ]
                    ],
                ]
            ],
            [
                'kode' => 'PERIKANAN',
                'nama' => 'Perikanan dan Kelautan',
                'deskripsi' => 'Hasil perikanan budidaya dan laut',
                'masters' => [
                    [
                        'kode' => 'GARAM',
                        'nama' => 'Garam',
                        'items' => [
                            ['kode' => 'GARAM-KONSUMSI', 'nama' => 'Garam Konsumsi Beryodium', 'satuan' => 'kg'],
                            ['kode' => 'GARAM-KROSOK', 'nama' => 'Garam Krosok Industri', 'satuan' => 'kg'],
                        ]
                    ],
                    [
                        'kode' => 'IKAN',
                        'nama' => 'Ikan',
                        'items' => [
                            ['kode' => 'IKAN-NILA', 'nama' => 'Ikan Nila', 'satuan' => 'kg'],
                            ['kode' => 'IKAN-LELE', 'nama' => 'Ikan Lele', 'satuan' => 'kg'],
                            ['kode' => 'IKAN-BANDENG', 'nama' => 'Ikan Bandeng', 'satuan' => 'kg'],
                        ]
                    ],
                ]
            ],
            [
                'kode' => 'EKRAF_PANGAN',
                'nama' => 'Ekonomi Kreatif Pangan',
                'deskripsi' => 'Produk olahan pangan berbasis kearifan lokal',
                'masters' => [
                    [
                        'kode' => 'OLAHAN_PANGAN',
                        'nama' => 'Olahan Pangan Desa',
                        'items' => [
                            ['kode' => 'KERIPIK-SINGKONG', 'nama' => 'Keripik Singkong Balado', 'satuan' => 'unit'],
                            ['kode' => 'TAPE-KETAN', 'nama' => 'Tape Ketan Hijau', 'satuan' => 'unit'],
                        ]
                    ]
                ]
            ],
            [
                'kode' => 'EKRAF_NON_PANGAN',
                'nama' => 'Ekonomi Kreatif Non-Pangan',
                'deskripsi' => 'Produk kerajinan, anyaman, dan kriya desa',
                'masters' => [
                    [
                        'kode' => 'KERAJINAN',
                        'nama' => 'Kerajinan Desa',
                        'items' => [
                            ['kode' => 'ANYAMAN-BAMBU', 'nama' => 'Anyaman Bambu / Besek', 'satuan' => 'unit'],
                            ['kode' => 'KRIYA-KUNINGAN', 'nama' => 'Kerajinan Kriya Logam', 'satuan' => 'unit'],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($kategoriData as $kat) {
            $kategori = KategoriKomoditas::updateOrCreate(
                ['kode' => $kat['kode']],
                ['nama' => $kat['nama'], 'deskripsi' => $kat['deskripsi'], 'is_active' => true]
            );

            foreach ($kat['masters'] as $m) {
                $master = MasterKomoditas::updateOrCreate(
                    ['id_kategori' => $kategori->id, 'kode' => $m['kode']],
                    ['nama' => $m['nama'], 'is_active' => true]
                );

                foreach ($m['items'] as $item) {
                    ItemKomoditas::updateOrCreate(
                        ['id_master_komoditas' => $master->id, 'kode' => $item['kode']],
                        ['nama' => $item['nama'], 'satuan_standar' => $item['satuan'], 'is_active' => true]
                    );
                }
            }
        }

        // Pastikan standardisasi satuan menyeluruh: Ekraf = 'unit', Non-Ekraf = 'kg'
        \Illuminate\Support\Facades\DB::table('item_komoditas')
            ->whereIn('id_master_komoditas', function ($q) {
                $q->select('master_komoditas.id')
                    ->from('master_komoditas')
                    ->join('kategori_komoditas', 'master_komoditas.id_kategori', '=', 'kategori_komoditas.id')
                    ->where('kategori_komoditas.kode', 'LIKE', '%EKRAF%')
                    ->orWhere('kategori_komoditas.nama', 'LIKE', '%kreatif%');
            })
            ->update(['satuan_standar' => 'unit']);

        \Illuminate\Support\Facades\DB::table('item_komoditas')
            ->whereNotIn('id_master_komoditas', function ($q) {
                $q->select('master_komoditas.id')
                    ->from('master_komoditas')
                    ->join('kategori_komoditas', 'master_komoditas.id_kategori', '=', 'kategori_komoditas.id')
                    ->where('kategori_komoditas.kode', 'LIKE', '%EKRAF%')
                    ->orWhere('kategori_komoditas.nama', 'LIKE', '%kreatif%');
            })
            ->update(['satuan_standar' => 'kg']);

        \Illuminate\Support\Facades\DB::table('potensi_produksi_petani')
            ->whereIn('item_komoditas_id', function ($q) {
                $q->select('item_komoditas.id')
                    ->from('item_komoditas')
                    ->join('master_komoditas', 'item_komoditas.id_master_komoditas', '=', 'master_komoditas.id')
                    ->join('kategori_komoditas', 'master_komoditas.id_kategori', '=', 'kategori_komoditas.id')
                    ->where('kategori_komoditas.kode', 'LIKE', '%EKRAF%')
                    ->orWhere('kategori_komoditas.nama', 'LIKE', '%kreatif%');
            })
            ->update(['satuan' => 'unit']);

        \Illuminate\Support\Facades\DB::table('potensi_produksi_petani')
            ->whereNotIn('item_komoditas_id', function ($q) {
                $q->select('item_komoditas.id')
                    ->from('item_komoditas')
                    ->join('master_komoditas', 'item_komoditas.id_master_komoditas', '=', 'master_komoditas.id')
                    ->join('kategori_komoditas', 'master_komoditas.id_kategori', '=', 'kategori_komoditas.id')
                    ->where('kategori_komoditas.kode', 'LIKE', '%EKRAF%')
                    ->orWhere('kategori_komoditas.nama', 'LIKE', '%kreatif%');
            })
            ->update(['satuan' => 'kg']);

        // Ambil desa sampel dari wilayah
        $desaSamples = Wilayah::where('tingkat', 'desa')->limit(3)->get();
        if ($desaSamples->count() > 0) {
            $desa1 = $desaSamples->first();
            $kec1 = $desa1->parent?->nama ?? 'Juwana';
            $kab1 = $desa1->parent?->parent?->nama ?? 'Pati';

            // Seed Petani & Produsen Lengkap untuk Seluruh Komoditas
            $daftarPetaniSeeder = [
                [
                    'petani' => ['nama' => 'Sutrisno Hartono', 'hp' => '081234567890', 'alamat' => 'Dusun Krajan RT 02 RW 01', 'rt' => '02', 'rw' => '01'],
                    'komoditas' => [
                        ['kode' => 'BERAS-MENTIK', 'volume' => 6500, 'satuan' => 'kg', 'harga' => 14500, 'siklus' => '3 - 4 bulan', 'ket' => 'Sawah irigasi teknis'],
                        ['kode' => 'CABAI-RAWIT', 'volume' => 1200, 'satuan' => 'kg', 'harga' => 35000, 'siklus' => '3 - 4 bulan', 'ket' => 'Tumpang sari'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Bambang Triyono', 'hp' => '082198765432', 'alamat' => 'Dusun Sawah Baru RT 04 RW 02', 'rt' => '04', 'rw' => '02'],
                    'komoditas' => [
                        ['kode' => 'CABAI-RAWIT', 'volume' => 2000, 'satuan' => 'kg', 'harga' => 35000, 'siklus' => '3 - 4 bulan', 'ket' => 'Lahan tegalan'],
                        ['kode' => 'BAWANG-MERAH', 'volume' => 3500, 'satuan' => 'kg', 'harga' => 28000, 'siklus' => '2 - 3 bulan', 'ket' => 'Bawang merah varietas lokal'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'H. Warsito', 'hp' => '081325123401', 'alamat' => 'Dusun Krajan RT 01 RW 01', 'rt' => '01', 'rw' => '01'],
                    'komoditas' => [
                        ['kode' => 'BERAS-LELE', 'volume' => 8000, 'satuan' => 'kg', 'harga' => 13500, 'siklus' => '3 - 4 bulan', 'ket' => 'Padi varietas Ciherang/Lele unggul'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Slamet Riyadi', 'hp' => '081325123402', 'alamat' => 'Dusun Sumberrejo RT 01 RW 02', 'rt' => '01', 'rw' => '02'],
                    'komoditas' => [
                        ['kode' => 'BERAS-IR64', 'volume' => 10000, 'satuan' => 'kg', 'harga' => 12500, 'siklus' => '3 - 4 bulan', 'ket' => 'Padi sawah irigasi teknis'],
                        ['kode' => 'BERAS-LELE', 'volume' => 5500, 'satuan' => 'kg', 'harga' => 13500, 'siklus' => '3 - 4 bulan', 'ket' => 'Lahan sawah tadah hujan'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Suwandi', 'hp' => '081325123403', 'alamat' => 'Dusun Sumberrejo RT 02 RW 02', 'rt' => '02', 'rw' => '02'],
                    'komoditas' => [
                        ['kode' => 'CABAI-KERITING', 'volume' => 2500, 'satuan' => 'kg', 'harga' => 32000, 'siklus' => '3 - 4 bulan', 'ket' => 'Cabai merah keriting mulsa plastik'],
                        ['kode' => 'CABAI-RAWIT', 'volume' => 1500, 'satuan' => 'kg', 'harga' => 35000, 'siklus' => '3 - 4 bulan', 'ket' => 'Tumpang sari'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Joko Prayitno', 'hp' => '081325123404', 'alamat' => 'Dusun Sumberrejo RT 03 RW 02', 'rt' => '03', 'rw' => '02'],
                    'komoditas' => [
                        ['kode' => 'BAWANG-MERAH', 'volume' => 4000, 'satuan' => 'kg', 'harga' => 28000, 'siklus' => '2 - 3 bulan', 'ket' => 'Bawang merah varietas Bauji'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Sunaryo', 'hp' => '081325123405', 'alamat' => 'Dusun Karanganyar RT 01 RW 03', 'rt' => '01', 'rw' => '03'],
                    'komoditas' => [
                        ['kode' => 'BAWANG-PUTIH', 'volume' => 2000, 'satuan' => 'kg', 'harga' => 38000, 'siklus' => '3 - 4 bulan', 'ket' => 'Lahan dataran sedang'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Karsono', 'hp' => '081325123406', 'alamat' => 'Dusun Karanganyar RT 02 RW 03', 'rt' => '02', 'rw' => '03'],
                    'komoditas' => [
                        ['kode' => 'KEDELAI-KUNING', 'volume' => 3000, 'satuan' => 'kg', 'harga' => 11000, 'siklus' => '3 bulan', 'ket' => 'Rotasi setelah panen padi'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Ahmad Fauzi', 'hp' => '081325123407', 'alamat' => 'Dusun Karanganyar RT 03 RW 03', 'rt' => '03', 'rw' => '03'],
                    'komoditas' => [
                        ['kode' => 'KOPI-ROBUSTA', 'volume' => 2800, 'satuan' => 'kg', 'harga' => 45000, 'siklus' => '6 - 7 bulan', 'ket' => 'Kebun lereng bukit'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Supriyanto', 'hp' => '081325123408', 'alamat' => 'Dusun Karanganyar RT 04 RW 03', 'rt' => '04', 'rw' => '03'],
                    'komoditas' => [
                        ['kode' => 'KOPI-ARABIKA', 'volume' => 1500, 'satuan' => 'kg', 'harga' => 75000, 'siklus' => '6 - 8 bulan', 'ket' => 'Kopi petik merah premium'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Sugeng Raharjo', 'hp' => '081325123409', 'alamat' => 'Dusun Krajan RT 04 RW 01', 'rt' => '04', 'rw' => '01'],
                    'komoditas' => [
                        ['kode' => 'GULA-TEBU', 'volume' => 15000, 'satuan' => 'kg', 'harga' => 15000, 'siklus' => '10 - 12 bulan', 'ket' => 'Kebun tebu kemitraan PG'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Mbah Sastro Diharjo', 'hp' => '081325123410', 'alamat' => 'Dusun Sukomulyo RT 01 RW 04', 'rt' => '01', 'rw' => '04'],
                    'komoditas' => [
                        ['kode' => 'GULA-KELAPA', 'volume' => 1800, 'satuan' => 'kg', 'harga' => 22000, 'siklus' => '1 bulan', 'ket' => 'Penderes nira kelapa murni'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Hendro Prabowo', 'hp' => '081325123411', 'alamat' => 'Dusun Sukomulyo RT 02 RW 04', 'rt' => '02', 'rw' => '04'],
                    'komoditas' => [
                        ['kode' => 'AYAM-BROILER', 'volume' => 6000, 'satuan' => 'kg', 'harga' => 24000, 'siklus' => '1 - 2 bulan', 'ket' => 'Kandang closed house'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Ibu Sumiati', 'hp' => '081325123412', 'alamat' => 'Dusun Sukomulyo RT 03 RW 04', 'rt' => '03', 'rw' => '04'],
                    'komoditas' => [
                        ['kode' => 'AYAM-KAMPUNG', 'volume' => 1200, 'satuan' => 'kg', 'harga' => 55000, 'siklus' => '2 - 3 bulan', 'ket' => 'Ayam kampung umbaran pakan alami'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Budi Santoso', 'hp' => '081325123413', 'alamat' => 'Dusun Sukomulyo RT 04 RW 04', 'rt' => '04', 'rw' => '04'],
                    'komoditas' => [
                        ['kode' => 'TELUR-RAS', 'volume' => 5000, 'satuan' => 'kg', 'harga' => 26500, 'siklus' => '1 bulan', 'ket' => 'Peternakan ayam petelur layer'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Ibu Maryani', 'hp' => '081325123414', 'alamat' => 'Dusun Krajan RT 01 RW 01', 'rt' => '01', 'rw' => '01'],
                    'komoditas' => [
                        ['kode' => 'TELUR-KAMPUNG', 'volume' => 800, 'satuan' => 'kg', 'harga' => 48000, 'siklus' => '1 bulan', 'ket' => 'Telur ayam buras segar'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'H. Darmo Kusumo', 'hp' => '081325123415', 'alamat' => 'Dusun Tambak Sari RT 01 RW 05', 'rt' => '01', 'rw' => '05'],
                    'komoditas' => [
                        ['kode' => 'GARAM-KONSUMSI', 'volume' => 20000, 'satuan' => 'kg', 'harga' => 8500, 'siklus' => '4 - 5 bulan', 'ket' => 'Tambak garam geomembran beryodium'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Sukirno', 'hp' => '081325123416', 'alamat' => 'Dusun Tambak Sari RT 02 RW 05', 'rt' => '02', 'rw' => '05'],
                    'komoditas' => [
                        ['kode' => 'GARAM-KROSOK', 'volume' => 35000, 'satuan' => 'kg', 'harga' => 4500, 'siklus' => '4 - 5 bulan', 'ket' => 'Garam krosok pengasinan ikan'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Agus Setiawan', 'hp' => '081325123417', 'alamat' => 'Dusun Tambak Sari RT 03 RW 05', 'rt' => '03', 'rw' => '05'],
                    'komoditas' => [
                        ['kode' => 'IKAN-NILA', 'volume' => 3500, 'satuan' => 'kg', 'harga' => 28000, 'siklus' => '3 - 4 bulan', 'ket' => 'Kolam terpal air mengalir'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Doni Prasetyo', 'hp' => '081325123418', 'alamat' => 'Dusun Tambak Sari RT 04 RW 05', 'rt' => '04', 'rw' => '05'],
                    'komoditas' => [
                        ['kode' => 'IKAN-LELE', 'volume' => 4500, 'satuan' => 'kg', 'harga' => 21000, 'siklus' => '2 - 3 bulan', 'ket' => 'Budidaya kolam bioflok'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Masruri', 'hp' => '081325123419', 'alamat' => 'Dusun Tambak Sari RT 01 RW 05', 'rt' => '01', 'rw' => '05'],
                    'komoditas' => [
                        ['kode' => 'IKAN-BANDENG', 'volume' => 4000, 'satuan' => 'kg', 'harga' => 32000, 'siklus' => '4 - 5 bulan', 'ket' => 'Tambak bandeng air payau'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Ibu Sri Wahyuni', 'hp' => '081325123420', 'alamat' => 'Dusun Sentra Kuliner RT 01 RW 06', 'rt' => '01', 'rw' => '06'],
                    'komoditas' => [
                        ['kode' => 'KERIPIK-SINGKONG', 'volume' => 2500, 'satuan' => 'unit', 'harga' => 15000, 'siklus' => '1 bulan', 'ket' => 'Keripik singkong balado kemasan 250gr'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Ibu Rohimah', 'hp' => '081325123421', 'alamat' => 'Dusun Sentra Kuliner RT 02 RW 06', 'rt' => '02', 'rw' => '06'],
                    'komoditas' => [
                        ['kode' => 'TAPE-KETAN', 'volume' => 1500, 'satuan' => 'unit', 'harga' => 20000, 'siklus' => '1 bulan', 'ket' => 'Tape ketan daun katuk legit'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Mbah Parto Suwito', 'hp' => '081325123422', 'alamat' => 'Dusun Kriya Mulya RT 01 RW 07', 'rt' => '01', 'rw' => '07'],
                    'komoditas' => [
                        ['kode' => 'ANYAMAN-BAMBU', 'volume' => 3000, 'satuan' => 'unit', 'harga' => 8000, 'siklus' => '1 bulan', 'ket' => 'Besek bambu ramah lingkungan'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Sukardi', 'hp' => '081325123423', 'alamat' => 'Dusun Kriya Mulya RT 02 RW 07', 'rt' => '02', 'rw' => '07'],
                    'komoditas' => [
                        ['kode' => 'ANYAMAN-BAMBU', 'volume' => 2000, 'satuan' => 'unit', 'harga' => 8000, 'siklus' => '1 bulan', 'ket' => 'Wadah hantaran bambu motif'],
                    ]
                ],
                [
                    'petani' => ['nama' => 'Tri Wahyudi', 'hp' => '081325123424', 'alamat' => 'Dusun Kriya Mulya RT 03 RW 07', 'rt' => '03', 'rw' => '07'],
                    'komoditas' => [
                        ['kode' => 'KRIYA-KUNINGAN', 'volume' => 500, 'satuan' => 'unit', 'harga' => 120000, 'siklus' => '1 - 2 bulan', 'ket' => 'Ornamen kriya ukir logam & kuningan'],
                    ]
                ],
            ];

            foreach ($daftarPetaniSeeder as $dp) {
                $pData = $dp['petani'];
                $petani = Petani::updateOrCreate(
                    ['nama_lengkap' => $pData['nama'], 'id_wilayah' => $desa1->id],
                    [
                        'nomor_hp' => $pData['hp'],
                        'alamat' => $pData['alamat'],
                        'rt' => $pData['rt'],
                        'rw' => $pData['rw'],
                        'desa' => $desa1->nama,
                        'kecamatan' => $kec1,
                        'kabupaten' => $kab1,
                    ]
                );

                foreach ($dp['komoditas'] as $kom) {
                    $item = ItemKomoditas::where('kode', $kom['kode'])->first();
                    if ($item) {
                        $totalNilai = round($kom['volume'] * $kom['harga']);
                        $petani->items()->syncWithoutDetaching([
                            $item->id => [
                                'estimasi_volume_panen' => $kom['volume'],
                                'satuan' => $kom['satuan'],
                                'harga_satuan' => $kom['harga'],
                                'total_nilai' => $totalNilai,
                                'periode_panen' => $kom['siklus'],
                                'keterangan' => $kom['ket'],
                            ]
                        ]);
                    }
                }
            }

            // Seed Penduduk Desa
            PendudukDesa::updateOrCreate(
                ['id_wilayah' => $desa1->id, 'tahun' => date('Y')],
                [
                    'balita' => 120,
                    'anak' => 240,
                    'remaja' => 380,
                    'dewasa' => 1150,
                    'lansia' => 210,
                    'sumber_data' => 'Input Admin',
                    'keterangan' => 'Data sensus potensi desa tahun berjalan',
                ]
            );
        }
    }
}

