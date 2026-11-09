<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Tenant\Entitas;
use Tests\TestCase;

class SuperAdminAksesSemuaModulTest extends TestCase
{
    private Pengguna $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        if (Pengguna::whereNull('id_entitas')->doesntExist()) {
            $this->seed();
        }

        $this->superAdmin = Pengguna::whereNull('id_entitas')->firstOrFail();
    }

    public function test_superadmin_dapat_mengakses_seluruh_modul_tanpa_error_403(): void
    {
        $routes = [
            // Super Admin & Master
            '/admin/dashboard',
            '/master/entitas',
            '/master/coa',
            '/master/kas-bank',
            '/master/komoditas',
            '/master/satuan',
            '/master/barang',
            '/master/barang/stok-minimum',
            '/master/pihak',
            '/master/gudang',
            '/master/periode',

            // M1 Survei
            '/survei/sesi',
            '/survei/dashboard',
            '/survei/rt',
            '/survei/produsen',
            '/survei/validasi',
            '/survei/pertanyaan',

            // M2 & M3 Perencanaan
            '/perencanaan/dashboard',
            '/perencanaan/demografi',
            '/perencanaan/potensi-produksi',
            '/perencanaan/standar-kebutuhan',
            '/perencanaan/kebutuhan-komoditas',
            '/perencanaan/neraca-komoditas',
            '/perencanaan/laporan',
            '/perencanaan/permintaan-pengadaan',
            '/perencanaan/perbandingan-harga',

            // M4 Gudang
            '/gudang',
            '/gudang/stok',
            '/gudang/penerimaan',
            '/gudang/batch',
            '/gudang/kerusakan',
            '/gudang/opname',
            '/gudang/kartu-stok',

            // M5 & M6 Pembelian & Penjualan
            '/pembelian/pembelian',
            '/pembelian/retur',
            '/penjualan/penjualan',
            '/penjualan/retur',

            // M7 Konsinyasi
            '/konsinyasi/marketplace',
            '/konsinyasi/pengiriman',
            '/konsinyasi/stok',
            '/konsinyasi/rekonsiliasi',
            '/konsinyasi/setoran',

            // M8 & M9 Keuangan
            '/keuangan/piutang',
            '/keuangan/hutang',
            '/keuangan/pelunasan',
            '/keuangan/kas-transaksi',

            // M10 Akuntansi
            '/akuntansi/saldo-awal',
            '/akuntansi/jurnal',
            '/akuntansi/aset-tetap',
            '/akuntansi/tutup-bulan',
            '/akuntansi/tutup-tahun',
            '/akuntansi/laporan/neraca-saldo',
            '/akuntansi/laporan/buku-besar',
            '/akuntansi/laporan/neraca',
            '/akuntansi/laporan/laba-rugi',
            '/akuntansi/laporan/arus-kas',
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);

            $this->assertNotEquals(
                403,
                $response->status(),
                "Super Admin terkena 403 Forbidden saat mengakses: {$url}"
            );

            $this->assertLessThan(
                500,
                $response->status(),
                "Super Admin mengalami Server Error ({$response->status()}) saat mengakses: {$url}"
            );
        }
    }
}

