<?php

namespace Tests\Feature;

use App\Models\Master\Komoditas;
use App\Models\Pengguna;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\StandarKebutuhanKomoditas;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use App\Services\Perencanaan\KalkulasiKebutuhanService;
use Tests\TestCase;

class KalkulasiAkgDanKomparasiDesaTest extends TestCase
{
    public function test_akg_balita_nol_tetap_dihitung_valid_tanpa_error(): void
    {
        $wilayah = Wilayah::firstOrCreate(
            ['nama' => 'Desa Uji Coba'],
            ['tingkat' => 'desa', 'is_active' => true]
        );

        $komoditas = Komoditas::firstOrCreate(
            ['nama' => 'Cabai Rawit Merah'],
            ['kategori' => 'pertanian', 'is_active' => true]
        );

        // Buat demografi lengkap
        foreach (['balita' => 50, 'anak' => 100, 'remaja' => 150, 'dewasa' => 300, 'lansia' => 80] as $umur => $jml) {
            DemografiDesa::updateOrCreate(
                ['id_wilayah' => $wilayah->id, 'tahun' => 2026, 'kelompok_umur' => $umur],
                ['jumlah_penduduk' => $jml]
            );
        }

        // Standar AKG: balita = 0 (karena bayi tidak makan cabai), kelompok lain > 0
        foreach (['balita' => 0.000000, 'anak' => 0.005000, 'remaja' => 0.015000, 'dewasa' => 0.020000, 'lansia' => 0.010000] as $umur => $perKapita) {
            StandarKebutuhanKomoditas::updateOrCreate(
                [
                    'id_komoditas' => $komoditas->id,
                    'kelompok_umur' => $umur,
                    'berlaku_mulai' => '2026-01-01',
                ],
                [
                    'sektor' => 'pertanian',
                    'per_kapita_harian' => $perKapita,
                    'satuan' => 'KG',
                    'satuan_periode' => 'harian',
                    'sumber' => 'Uji Coba AKG Balita 0',
                ]
            );
        }

        $service = app(KalkulasiKebutuhanService::class);
        $result = $service->hitungKebutuhan($wilayah->id, 2026, 'ganjil', $komoditas->id);

        $this->assertGreaterThan(0, $result['total_baris_kebutuhan']);
        $this->assertGreaterThan(0, $result['total_hasil_kalkulasi']);

        $hasil = HasilKalkulasi::where('id_wilayah', $wilayah->id)
            ->where('id_komoditas', $komoditas->id)
            ->where('tahun', 2026)
            ->first();

        $this->assertNotNull($hasil);
        $this->assertGreaterThan(0, (float) $hasil->total_kebutuhan);
    }

    public function test_dashboard_bisa_memilah_desa_lain_untuk_komparasi(): void
    {
        $desa1 = Wilayah::where('tingkat', 'desa')->first();
        $desa2 = Wilayah::where('tingkat', 'desa')->skip(1)->first();

        if (! $desa2) {
            $desa2 = Wilayah::create(['nama' => 'Desa Pembanding', 'tingkat' => 'desa', 'is_active' => true]);
        }

        $entitas = Entitas::first();
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first() ?? Pengguna::first();

        $response = $this->actingAs($user)->get(route('perencanaan.dashboard', [
            'id_wilayah' => $desa2->id,
        ]));

        $response->assertOk();
        $response->assertSee('Mode Komparasi');
        $response->assertSee($desa2->nama);
    }

    public function test_laporan_menampilkan_pemilahan_sektor_defisit_dan_surplus(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first() ?? Pengguna::first();

        $response = $this->actingAs($user)->get(route('perencanaan.laporan.index'));

        $response->assertOk();
        $response->assertSee('Komoditas Defisit');
        $response->assertSee('Komoditas Surplus');
        $response->assertSee('Sektor');
        $response->assertSee('Rincian lengkap neraca');
        $response->assertSee('<details', false);
        $response->assertDontSee('<details open', false);
    }

    public function test_laporan_menolak_filter_wilayah_yang_tidak_ada(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->first() ?? Pengguna::first();

        $response = $this->actingAs($user)->get(route('perencanaan.laporan.index', [
            'id_wilayah' => 999999,
        ]));

        $response->assertNotFound();
    }
}
