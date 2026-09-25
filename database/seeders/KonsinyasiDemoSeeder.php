<?php

namespace Database\Seeders;

use App\Services\StokService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KonsinyasiDemoSeeder extends Seeder
{
    public function run(): void
    {
        $satuanId = DB::table('satuan')->where('kode_satuan', 'KG')->value('id');
        if (! $satuanId) {
            $satuanId = DB::table('satuan')->insertGetId([
                'kode_satuan' => 'KG',
                'alias_json' => json_encode(['kilogram', 'kg']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $komoditasId = DB::table('komoditas')->where('nama', 'Beras')->value('id');
        if (! $komoditasId) {
            $komoditasId = DB::table('komoditas')->insertGetId([
                'kategori' => 'pangan',
                'nama' => 'Beras',
                'alias_json' => json_encode(['beras']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $unitUsahaId = DB::table('master_unit_usaha')->where('kode_unit_usaha', 'SEMBAKO')->value('id_unit_usaha');
        if (! $unitUsahaId) {
            $this->command?->warn('Unit usaha SEMBAKO belum tersedia. Jalankan db:seed terlebih dahulu.');
            return;
        }

        $barangId = DB::table('master_barang')->where('kode_barang', 'BRG-BERAS-DEMO')->value('id_barang');
        if (! $barangId) {
            $barangId = DB::table('master_barang')->insertGetId([
                'kode_barang' => 'BRG-BERAS-DEMO',
                'id_komoditas' => $komoditasId,
                'id_unit_usaha' => $unitUsahaId,
                'nama_barang' => 'Beras Demo Konsinyasi',
                'id_satuan_dasar' => $satuanId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('konversi_satuan')->updateOrInsert(
            ['id_barang' => $barangId, 'id_satuan' => $satuanId],
            ['faktor_ke_dasar' => 1, 'is_default_beli' => true, 'is_default_jual' => true]
        );

        $koperasiA = DB::table('koperasi_desa')->where('kode_koperasi', 'KDMP-A')->first();
        $koperasiB = DB::table('koperasi_desa')->where('kode_koperasi', 'KDMP-B')->first();
        if (! $koperasiA || ! $koperasiB) {
            $this->command?->warn('Koperasi demo belum tersedia. Jalankan db:seed terlebih dahulu.');
            return;
        }

        $gudangA = DB::table('gudang')->where('id_koperasi', $koperasiA->id_koperasi)->where('kode_gudang', 'UTAMA')->first();
        $gudangB = DB::table('gudang')->where('id_koperasi', $koperasiB->id_koperasi)->where('kode_gudang', 'UTAMA')->first();
        if (! $gudangA || ! $gudangB) {
            $this->command?->warn('Gudang demo belum tersedia. Jalankan db:seed terlebih dahulu.');
            return;
        }

        foreach ([$koperasiA->id_koperasi, $koperasiB->id_koperasi] as $koperasiId) {
            DB::table('barang_per_koperasi')->updateOrInsert(
                ['id_koperasi' => $koperasiId, 'id_barang' => $barangId],
                ['stok_minimum' => 0, 'stok_maksimum' => 1000, 'harga_jual_standar' => 13000, 'is_dijual' => true]
            );
        }

        $saldoAwalRef = 900001;
        $sudahAda = DB::table('kartu_stok')
            ->where('ref_tipe', 'SALDO_AWAL')
            ->where('ref_id', $saldoAwalRef)
            ->where('id_gudang', $gudangA->id_gudang)
            ->where('id_barang', $barangId)
            ->exists();

        if (! $sudahAda) {
            app(StokService::class)->masuk(
                koperasiId: $koperasiA->id_koperasi,
                gudangId: $gudangA->id_gudang,
                barangId: $barangId,
                qty: '100',
                hargaSatuan: '8000',
                refTipe: 'SALDO_AWAL',
                refId: $saldoAwalRef,
            );
        }

        $this->command?->info('Data demo konsinyasi siap: Desa A memiliki 100 kg beras @ Rp8.000.');
    }
}
