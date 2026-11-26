<?php

namespace Tests\Feature;

use App\Models\Master\Komoditas;
use App\Models\Pengguna;
use App\Models\Perencanaan\KomoditasKalkulasiDesa;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Tenant\Wilayah;
use App\Services\Perencanaan\KalkulasiKebutuhanService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerDesaKalkulasiTest extends TestCase
{
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_master_komoditas_ditautkan_hanya_ke_desa_pengguna(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = DB::table('entitas')->where('id_entitas', $user->id_entitas)->value('id_wilayah');
        $komoditas = Komoditas::where('is_active', true)->firstOrFail();

        $response = $this->actingAs($user)->post(route('perencanaan.kebutuhan-komoditas.master'), [
            'master_komoditas_ids' => [$komoditas->id],
        ]);

        $response->assertRedirect(route('perencanaan.kebutuhan-komoditas.index'));
        $this->assertDatabaseHas('komoditas_kalkulasi_desa', [
            'id_wilayah' => $idWilayah,
            'id_komoditas' => $komoditas->id,
        ]);
        $this->assertSame(1, KomoditasKalkulasiDesa::where('id_wilayah', $idWilayah)->count());
    }

    public function test_halaman_kalkulasi_menyembunyikan_komoditas_di_dalam_akordeon_kategori(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = DB::table('entitas')->where('id_entitas', $user->id_entitas)->value('id_wilayah');
        $komoditas = Komoditas::where('is_active', true)->firstOrFail();
        KomoditasKalkulasiDesa::firstOrCreate(
            ['id_wilayah' => $idWilayah, 'id_komoditas' => $komoditas->id],
            ['created_at' => now()]
        );

        $response = $this->actingAs($user)->get(route('perencanaan.kebutuhan-komoditas.index'));

        $response->assertOk();
        $response->assertSee('berdasarkan Kategori');
        $response->assertSee('<details', false);
        $response->assertSee('Hitung kategori ini');
        $response->assertDontSee('<details open', false);
    }

    public function test_data_dasar_menampilkan_potensi_komoditi_dalam_dropdown_kategori(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = DB::table('entitas')->where('id_entitas', $user->id_entitas)->value('id_wilayah');
        $komoditas = Komoditas::where('is_active', true)->firstOrFail();

        KetersediaanKomoditas::create([
            'id_wilayah' => $idWilayah,
            'id_komoditas' => $komoditas->id,
            'tahun' => 2026,
            'bulan' => 4,
            'jumlah_produksi' => 25,
            'satuan' => 'KG',
        ]);

        $response = $this->actingAs($user)->get(route('perencanaan.potensi-produksi.index'));

        $response->assertOk()
            ->assertSee('Potensi Panen per Kategori')
            ->assertSee($komoditas->nama)
            ->assertSee('Desa:')
            ->assertSee('<details', false)
            ->assertDontSee('<details open', false);
    }

    public function test_kalkulasi_menolak_komoditas_di_luar_master_desa(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = DB::table('entitas')->where('id_entitas', $user->id_entitas)->value('id_wilayah');
        $master = Komoditas::where('is_active', true)->firstOrFail();
        $diLuarMaster = Komoditas::where('is_active', true)
            ->where('id', '!=', $master->id)
            ->whereNotIn('id', KomoditasKalkulasiDesa::where('id_wilayah', $idWilayah)->select('id_komoditas'))
            ->first()
            ?? Komoditas::create([
                'nama' => 'Komoditas Uji Luar Master',
                'kategori' => 'pertanian',
                'is_active' => true,
            ]);

        KomoditasKalkulasiDesa::firstOrCreate(
            [
                'id_wilayah' => $idWilayah,
                'id_komoditas' => $master->id,
            ],
            ['created_at' => now()]
        );
        $komoditasMaster = KomoditasKalkulasiDesa::where('id_wilayah', $idWilayah)
            ->pluck('id_komoditas')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->assertContains($master->id, $komoditasMaster);
        $this->assertNotContains($diLuarMaster->id, $komoditasMaster);
        $jumlahHasilSebelum = DB::table('hasil_kalkulasi')
            ->where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $diLuarMaster->id)
            ->where('tahun', 2026)
            ->count();

        $response = $this->actingAs($user)->from(route('perencanaan.dashboard'))->post(
            route('perencanaan.kebutuhan-komoditas.kalkulasi'),
            [
                'tahun' => 2026,
                'periode' => 'ganjil',
                'pilih_komoditas' => '1',
                'komoditas_ids' => [$diLuarMaster->id],
            ]
        );

        $response->assertSessionHasErrors('komoditas_ids');
        $this->assertSame($jumlahHasilSebelum, DB::table('hasil_kalkulasi')
            ->where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $diLuarMaster->id)
            ->where('tahun', 2026)
            ->count());
    }

    public function test_kalkulasi_kategori_memproses_semua_komoditas_master_dalam_kategori_terpilih(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = DB::table('entitas')->where('id_entitas', $user->id_entitas)->value('id_wilayah');
        $ikanA = Komoditas::create(['nama' => 'Ikan Uji A', 'kategori' => 'perikanan', 'is_active' => true]);
        $ikanB = Komoditas::create(['nama' => 'Ikan Uji B', 'kategori' => 'perikanan', 'is_active' => true]);
        $kopi = Komoditas::create(['nama' => 'Kopi Uji', 'kategori' => 'perkebunan', 'is_active' => true]);

        foreach ([$ikanA, $ikanB, $kopi] as $komoditas) {
            KomoditasKalkulasiDesa::create([
                'id_wilayah' => $idWilayah,
                'id_komoditas' => $komoditas->id,
                'created_at' => now(),
            ]);
        }
        $idKomoditasIkanMaster = KomoditasKalkulasiDesa::query()
            ->where('id_wilayah', $idWilayah)
            ->whereHas('komoditas', fn ($query) => $query->where('kategori', 'perikanan'))
            ->pluck('id_komoditas')
            ->map(fn ($id) => (int) $id)
            ->all();

        $service = \Mockery::mock(KalkulasiKebutuhanService::class);
        $service->shouldReceive('hitungKebutuhan')
            ->once()
            ->withArgs(function (int $wilayahId, int $tahun, string $periode, array $idKomoditas) use ($idWilayah, $idKomoditasIkanMaster): bool {
                sort($idKomoditas);
                $idIkan = $idKomoditasIkanMaster;
                sort($idIkan);

                return $wilayahId === $idWilayah && $tahun === 2026 && $periode === 'ganjil' && $idKomoditas === $idIkan;
            })
            ->andReturn(['total_baris_kebutuhan' => 10, 'total_hasil_kalkulasi' => 2]);
        $this->app->instance(KalkulasiKebutuhanService::class, $service);

        $response = $this->actingAs($user)->post(route('perencanaan.kebutuhan-komoditas.kalkulasi'), [
            'tahun' => 2026,
            'periode' => 'ganjil',
            'kategori' => 'perikanan',
            'redirect_to' => 'neraca',
        ]);

        $response->assertRedirect(route('perencanaan.neraca-komoditas.index', [
            'tahun' => 2026,
            'periode' => 'ganjil',
            'kategori' => 'perikanan',
        ]));
        $response->assertSessionHas('status');
    }

    public function test_dashboard_desa_lain_hanya_menampilkan_hasil_baca_saja(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayahAsal = DB::table('entitas')->where('id_entitas', $user->id_entitas)->value('id_wilayah');
        $wilayahLain = Wilayah::where('tingkat', 'desa')
            ->where('id', '!=', $idWilayahAsal)
            ->first()
            ?? Wilayah::create(['nama' => 'Desa Pembanding Uji', 'tingkat' => 'desa', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('perencanaan.dashboard', [
            'id_wilayah' => $wilayahLain->id,
        ]));

        $response->assertOk();
        $response->assertSee('Mode Komparasi');
        $response->assertSee('Mode lihat saja');
        $response->assertDontSee('id="modal-kalkulasi"');
    }

    public function test_perencanaan_tidak_fallback_ke_wilayah_pertama_jika_entitas_aktif_tidak_valid(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $user->id_entitas = PHP_INT_MAX;

        $this->actingAs($user)
            ->get(route('perencanaan.dashboard'))
            ->assertNotFound();
    }
}
