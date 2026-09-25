<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Tenant\KoperasiDesa;
use App\Models\Tenant\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal: 2 koperasi desa contoh lengkap dengan gudang, kas/bank, unit
 * usaha, satu pengguna login per desa, dan pihak "koperasi_desa_lain" antar
 * keduanya (dibutuhkan modul Konsinyasi).
 *
 * CATATAN: seeder ini SENGAJA tidak memakai JurnalService/PeriodeService —
 * kerangka ini belum menyertakan service Finance. Baris periode_akuntansi
 * dibuat langsung lewat DB::table() sebagai pengganti sementara
 * PeriodeService::bukaTahun() yang akan dibangun saat pendalaman modul
 * Finance.
 */
class DataAwalSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('master_unit_usaha')->updateOrInsert(
            ['kode_unit_usaha' => 'SEMBAKO'],
            [
                'nama_unit_usaha' => 'Sembako',
                'kode_akun_persediaan' => '1123',
                'kode_akun_pendapatan_anggota' => '411',
                'kode_akun_pendapatan_non_anggota' => '412',
                'kode_akun_hpp' => '511',
            ]
        );

        DB::table('master_unit_usaha')->updateOrInsert(
            ['kode_unit_usaha' => 'APOTEK'],
            [
                'nama_unit_usaha' => 'Apotek',
                'kode_akun_persediaan' => '1124',
                'kode_akun_pendapatan_anggota' => '413',
                'kode_akun_pendapatan_non_anggota' => '414',
                'kode_akun_hpp' => '512',
            ]
        );

        $satuanKg = DB::table('satuan')->updateOrInsert(
            ['kode_satuan' => 'KG'],
            ['alias_json' => json_encode(['kilogram']), 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
        );
        $satuanKgId = DB::table('satuan')->where('kode_satuan', 'KG')->value('id');

        $satuanKarungId = DB::table('satuan')->updateOrInsert(
            ['kode_satuan' => 'KARUNG'],
            ['alias_json' => json_encode(['karung']), 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
        );
        $satuanKarungId = DB::table('satuan')->where('kode_satuan', 'KARUNG')->value('id');

        $beras = DB::table('komoditas')->updateOrInsert(
            ['nama' => 'Beras'],
            ['kategori' => 'pangan', 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
        );
        $berasId = DB::table('komoditas')->where('nama', 'Beras')->value('id');
        $sembakoId = DB::table('master_unit_usaha')->where('kode_unit_usaha', 'SEMBAKO')->value('id_unit_usaha');

        DB::table('master_barang')->updateOrInsert(
            ['kode_barang' => 'BRG-BERAS-MEDIUM'],
            [
                'id_komoditas' => $berasId,
                'id_unit_usaha' => $sembakoId,
                'nama_barang' => 'Beras Medium',
                'id_satuan_dasar' => $satuanKgId,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $berasBarangId = DB::table('master_barang')->where('kode_barang', 'BRG-BERAS-MEDIUM')->value('id_barang');
        DB::table('konversi_satuan')->updateOrInsert(
            ['id_barang' => $berasBarangId, 'id_satuan' => $satuanKgId],
            ['faktor_ke_dasar' => 1, 'is_default_beli' => true, 'is_default_jual' => true]
        );
        DB::table('konversi_satuan')->updateOrInsert(
            ['id_barang' => $berasBarangId, 'id_satuan' => $satuanKarungId],
            ['faktor_ke_dasar' => 25, 'is_default_beli' => false, 'is_default_jual' => false]
        );

        $desaContoh = [
            ['kode' => 'KDMP-A', 'nama' => 'Koperasi Desa Merah Putih Mekar Jaya', 'wilayah' => 'Desa Mekar Jaya'],
            ['kode' => 'KDMP-B', 'nama' => 'Koperasi Desa Merah Putih Sukamaju', 'wilayah' => 'Desa Sukamaju'],
        ];

        foreach ($desaContoh as $desa) {
            $wilayah = Wilayah::firstOrCreate(
                ['nama' => $desa['wilayah']],
                ['tingkat' => 'desa']
            );

            $koperasi = KoperasiDesa::updateOrCreate(
                ['kode_koperasi' => $desa['kode']],
                [
                    'nama_koperasi' => $desa['nama'],
                    'id_wilayah' => $wilayah->id,
                    'tahun_buku_awal' => 2026,
                    'is_active' => true,
                ]
            );

            DB::table('gudang')->updateOrInsert(
                ['id_koperasi' => $koperasi->id_koperasi, 'kode_gudang' => 'UTAMA'],
                ['nama_gudang' => $desa['kode'] === 'KDMP-A'
                    ? 'Gudang Koperasi Desa Mekar Jaya'
                    : 'Gudang Koperasi Desa Sukamaju', 'is_active' => true]
            );

            DB::table('master_kas_bank')->updateOrInsert(
                ['id_koperasi' => $koperasi->id_koperasi, 'jenis' => 'kas', 'nama' => 'Kas Utama'],
                ['kode_akun' => '1111', 'is_default' => true, 'is_active' => true]
            );

            DB::table('master_kas_bank')->updateOrInsert(
                ['id_koperasi' => $koperasi->id_koperasi, 'jenis' => 'bank', 'nama' => 'Bank A'],
                ['kode_akun' => '11121', 'is_default' => false, 'is_active' => true]
            );

            DB::table('master_kas_bank')->updateOrInsert(
                ['id_koperasi' => $koperasi->id_koperasi, 'jenis' => 'kas', 'nama' => 'Kas Demo'],
                ['kode_akun' => '1111', 'is_default' => false, 'is_active' => true]
            );

            DB::table('master_kas_bank')->updateOrInsert(
                ['id_koperasi' => $koperasi->id_koperasi, 'jenis' => 'bank', 'nama' => 'Bank Demo'],
                ['kode_akun' => '11121', 'is_default' => false, 'is_active' => true]
            );

            DB::table('master_pihak')->updateOrInsert(
                ['id_koperasi' => $koperasi->id_koperasi, 'nama' => 'Warga Contoh'],
                [
                    'jenis_pihak' => 'warga',
                    'is_anggota' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('master_pihak')->updateOrInsert(
                ['id_koperasi' => $koperasi->id_koperasi, 'nama' => 'Toko Sembako Sumber Rejeki'],
                [
                    'jenis_pihak' => 'supplier',
                    'is_anggota' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Pengganti sementara PeriodeService::bukaTahun(): 13 baris periode
            // (bulan 1-12 operasional + bulan 13 penyesuaian), semua OPEN.
            for ($bulan = 1; $bulan <= 13; $bulan++) {
                DB::table('periode_akuntansi')->updateOrInsert(
                    ['id_koperasi' => $koperasi->id_koperasi, 'tahun' => 2026, 'bulan' => $bulan],
                    ['status' => 'OPEN']
                );
            }

            $emailLogin = 'manajer@'.strtolower(str_replace(' ', '', $desa['kode'])).'.test';

            $pengguna = Pengguna::updateOrCreate(
                ['email' => $emailLogin],
                [
                    'id_koperasi' => $koperasi->id_koperasi,
                    'nama' => 'Manajer '.$desa['wilayah'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );

            $idPeranManajer = Peran::where('kode', 'manajer')->value('id_peran');
            DB::table('pengguna_peran')->updateOrInsert([
                'id_pengguna' => $pengguna->id,
                'id_peran' => $idPeranManajer,
            ]);
        }

        // Setiap desa butuh pihak 'koperasi_desa_lain' yang menunjuk ke desa
        // mitranya — dipakai modul Konsinyasi untuk resolusi id_pihak piutang/hutang.
        $semuaKoperasi = KoperasiDesa::whereIn('kode_koperasi', array_column($desaContoh, 'kode'))->get();

        foreach ($semuaKoperasi as $koperasi) {
            foreach ($semuaKoperasi as $mitra) {
                if ($koperasi->id_koperasi === $mitra->id_koperasi) {
                    continue;
                }

                DB::table('master_pihak')->updateOrInsert(
                    ['id_koperasi' => $koperasi->id_koperasi, 'id_koperasi_mitra' => $mitra->id_koperasi],
                    [
                        'jenis_pihak' => 'koperasi_desa_lain',
                        'nama' => $mitra->nama_koperasi,
                        'is_anggota' => false,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
