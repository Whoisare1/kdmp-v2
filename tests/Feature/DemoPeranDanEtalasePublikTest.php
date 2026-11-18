<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Tests\TestCase;

class DemoPeranDanEtalasePublikTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Pengguna::where('email', 'manajer@kdmp-a.test')->doesntExist()) {
            $this->seed();
        }
    }

    public function test_etalase_publik_komoditas_dapat_diakses_tanpa_login(): void
    {
        $response = $this->get(route('publik.komoditas'));

        $response->assertOk()
            ->assertSee('Katalog Komoditas')
            ->assertSee('Direktori Entitas Koperasi Desa Terdaftar')
            ->assertSee('Data Publik (Bisa Dilihat Semua Entitas)')
            ->assertSee('Data Internal Terproteksi');
    }

    public function test_switch_peran_demo_otomatis_login_dan_sinkron_dasbor(): void
    {
        $peranUji = [
            'surveyor' => 'Surveyor Lapangan',
            'manajer' => 'Manajer / Perencana Koperasi',
            'admin_gudang' => 'Admin Gudang & Logistik',
            'pembelian' => 'Staf Pembelian (Purchasing)',
            'kasir' => 'Kasir / Penjualan (POS)',
            'akuntan' => 'Akuntan & Bendahara Koperasi',
            'super_admin' => 'Super Admin Pusat',
        ];

        foreach ($peranUji as $kode => $label) {
            $res = $this->post(route('demo.switch-peran'), [
                'peran' => $kode,
                'mode' => 'login',
            ]);

            $res->assertRedirect();
            $this->assertAuthenticated();
            $this->assertEquals($kode, session('demo_peran_aktif'));

            $dash = $this->get(route('dashboard', ['peran' => $kode]));
            $dash->assertOk()
                ->assertSee($label)
                ->assertSee('Simulator Peran');
        }
    }
}

