<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Peran;
use Tests\TestCase;

class PemisahanPeranPenggunaTest extends TestCase
{
    private Pengguna $manajer;
    private Pengguna $kasir;
    private Pengguna $gudang;
    private Pengguna $surveyor;

    protected function setUp(): void
    {
        parent::setUp();

        if (Pengguna::where('email', 'manajer@kdmp-a.test')->doesntExist()) {
            $this->seed();
        }

        $this->manajer  = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $this->kasir    = Pengguna::where('email', 'kasir@kdmp-a.test')->firstOrFail();
        $this->gudang   = Pengguna::where('email', 'gudang@kdmp-a.test')->firstOrFail();
        $this->surveyor = Pengguna::where('email', 'surveyor@kdmp-a.test')->firstOrFail();

        // Pastikan kasir hanya memiliki peran kasir di awal test
        $idKasir = Peran::where('kode', 'kasir')->value('id_peran');
        if ($idKasir) {
            $this->kasir->peran()->sync([$idKasir]);
            $this->kasir->load('peran');
        }
    }

    public function test_kasir_dapat_akses_penjualan_tapi_ditolak_di_gudang_dan_akuntansi(): void
    {
        // 1. Kasir boleh buka penjualan
        $resJual = $this->actingAs($this->kasir)->get('/penjualan/penjualan');
        $this->assertEquals(200, $resJual->status(), 'Kasir harusnya bisa mengakses /penjualan/penjualan');

        // 2. Kasir ditolak di gudang
        $resGudang = $this->actingAs($this->kasir)->get('/gudang/stok');
        $this->assertEquals(403, $resGudang->status(), 'Kasir harus ditolak 403 saat mengakses /gudang/stok');

        // 3. Kasir ditolak di akuntansi
        $resAkuntansi = $this->actingAs($this->kasir)->get('/akuntansi/jurnal');
        $this->assertEquals(403, $resAkuntansi->status(), 'Kasir harus ditolak 403 saat mengakses /akuntansi/jurnal');
    }

    public function test_admin_gudang_dapat_akses_gudang_tapi_ditolak_di_penjualan_dan_akuntansi(): void
    {
        // 1. Admin gudang boleh buka posisi stok
        $resGudang = $this->actingAs($this->gudang)->get('/gudang/stok');
        $this->assertEquals(200, $resGudang->status(), 'Admin Gudang harusnya bisa mengakses /gudang/stok');

        // 2. Admin gudang ditolak di kasir / pos
        $resJual = $this->actingAs($this->gudang)->get('/penjualan/penjualan');
        $this->assertEquals(403, $resJual->status(), 'Admin Gudang harus ditolak 403 saat mengakses /penjualan/penjualan');

        // 3. Admin gudang ditolak di tutup bulan
        $resTutup = $this->actingAs($this->gudang)->get('/akuntansi/tutup-bulan');
        $this->assertEquals(403, $resTutup->status(), 'Admin Gudang harus ditolak 403 saat mengakses /akuntansi/tutup-bulan');
    }

    public function test_surveyor_dapat_akses_survei_tapi_ditolak_di_gudang_dan_penjualan(): void
    {
        // 1. Surveyor boleh buka sesi survei
        $resSurvei = $this->actingAs($this->surveyor)->get('/survei/sesi');
        $this->assertEquals(200, $resSurvei->status(), 'Surveyor harusnya bisa mengakses /survei/sesi');

        // 2. Surveyor ditolak di kasir / pos
        $resJual = $this->actingAs($this->surveyor)->get('/penjualan/penjualan');
        $this->assertEquals(403, $resJual->status(), 'Surveyor harus ditolak 403 saat mengakses /penjualan/penjualan');

        // 3. Surveyor ditolak di gudang
        $resGudang = $this->actingAs($this->surveyor)->get('/gudang/stok');
        $this->assertEquals(403, $resGudang->status(), 'Surveyor harus ditolak 403 saat mengakses /gudang/stok');
    }

    public function test_manajer_dapat_akses_seluruh_modul_desa_dan_kelola_staf(): void
    {
        // Manajer adalah Admin Desa -> bisa buka semuanya
        $this->assertEquals(200, $this->actingAs($this->manajer)->get('/survei/sesi')->status());
        $this->assertEquals(200, $this->actingAs($this->manajer)->get('/perencanaan/dashboard')->status());
        $this->assertEquals(200, $this->actingAs($this->manajer)->get('/gudang/stok')->status());
        $this->assertEquals(200, $this->actingAs($this->manajer)->get('/penjualan/penjualan')->status());
        $this->assertEquals(200, $this->actingAs($this->manajer)->get('/akuntansi/jurnal')->status());
        $this->assertEquals(200, $this->actingAs($this->manajer)->get('/master/pengguna')->status());
    }

    public function test_staf_dengan_peran_ganda_kasir_dan_gudang_dapat_akses_keduanya(): void
    {
        // Berikan peran ganda ke kasir: kasir + admin_gudang
        $idPeranGudang = Peran::where('kode', 'admin_gudang')->value('id_peran');
        $this->kasir->peran()->syncWithoutDetaching([$idPeranGudang]);
        $this->kasir->unsetRelation('peran');

        // Kasir yang merangkap gudang sekarang bisa buka keduanya!
        $this->assertEquals(200, $this->actingAs($this->kasir)->get('/penjualan/penjualan')->status());
        $this->assertEquals(200, $this->actingAs($this->kasir)->get('/gudang/stok')->status());

        // Tapi tetap ditolak di akuntansi
        $this->assertEquals(403, $this->actingAs($this->kasir)->get('/akuntansi/jurnal')->status());
    }
}

