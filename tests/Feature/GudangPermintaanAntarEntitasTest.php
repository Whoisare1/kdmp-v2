<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GudangPermintaanAntarEntitasTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $pengaju;
    private Pengguna $penerima;
    private int $idPermintaan;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = bin2hex(random_bytes(5));
        $idWilayahPengaju = DB::table('wilayah')->insertGetId([
            'tingkat' => 'desa',
            'nama' => 'Dummy Pengaju '.$suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $idWilayahPenerima = DB::table('wilayah')->insertGetId([
            'tingkat' => 'desa',
            'nama' => 'Dummy Penerima '.$suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $idEntitasPengaju = DB::table('entitas')->insertGetId([
            'kode_entitas' => 'DMP-'.$suffix,
            'nama_entitas' => 'Entitas Dummy Pengaju',
            'jenis_entitas' => 'Koperasi Desa',
            'id_wilayah' => $idWilayahPengaju,
            'tahun_buku_awal' => now()->year,
            'is_active' => true,
            'status' => 'APPROVED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $idEntitasPenerima = DB::table('entitas')->insertGetId([
            'kode_entitas' => 'DMR-'.$suffix,
            'nama_entitas' => 'Entitas Dummy Penerima',
            'jenis_entitas' => 'Koperasi Desa',
            'id_wilayah' => $idWilayahPenerima,
            'tahun_buku_awal' => now()->year,
            'is_active' => true,
            'status' => 'APPROVED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->pengaju = Pengguna::query()->create([
            'id_entitas' => $idEntitasPengaju,
            'nama' => 'Pengguna Dummy Pengaju',
            'email' => 'pengaju-'.$suffix.'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $this->penerima = Pengguna::query()->create([
            'id_entitas' => $idEntitasPenerima,
            'nama' => 'Pengguna Dummy Penerima',
            'email' => 'penerima-'.$suffix.'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->idPermintaan = DB::table('permintaan_antar_entitas_gudang')->insertGetId([
            'id_entitas_pengaju' => $idEntitasPengaju,
            'id_entitas_penerima' => $idEntitasPenerima,
            'id_hasil_pengaju' => 900000001,
            'id_hasil_penerima' => 900000002,
            'jenis_permintaan' => 'tawaran_jual',
            'nama_komoditas' => 'Beras Dummy',
            'tahun' => now()->year,
            'bulan' => now()->month,
            'qty_rekomendasi' => 10,
            'satuan' => 'KG',
            'status' => 'terkirim',
            'created_by' => $this->pengaju->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_entitas_penerima_dapat_menyetujui_permintaan_tanpa_mengubah_stok(): void
    {
        $jumlahBarisStokSebelum = DB::table('stok')->count();

        $response = $this->actingAs($this->penerima)
            ->from('/gudang/pencocokan-surplus-defisit')
            ->patch(route('gudang.permintaan-antar-entitas.respond', $this->idPermintaan), [
                'status' => 'disetujui',
            ]);

        $response->assertRedirect('/gudang/pencocokan-surplus-defisit');
        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $this->idPermintaan,
            'status' => 'disetujui',
        ]);
        $this->assertSame($jumlahBarisStokSebelum, DB::table('stok')->count());
    }

    public function test_entitas_pengaju_tidak_dapat_menanggapi_permintaan_miliknya(): void
    {
        $response = $this->actingAs($this->pengaju)
            ->patch(route('gudang.permintaan-antar-entitas.respond', $this->idPermintaan), [
                'status' => 'disetujui',
            ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $this->idPermintaan,
            'status' => 'terkirim',
        ]);
    }

    public function test_status_yang_tidak_dikenal_ditolak(): void
    {
        $response = $this->actingAs($this->penerima)
            ->patch(route('gudang.permintaan-antar-entitas.respond', $this->idPermintaan), [
                'status' => 'diposting',
            ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $this->idPermintaan,
            'status' => 'terkirim',
        ]);
    }

    public function test_filter_status_membatasi_riwayat_permintaan(): void
    {
        DB::table('permintaan_antar_entitas_gudang')
            ->where('id_permintaan_gudang', $this->idPermintaan)
            ->update(['status' => 'ditolak']);

        $menunggu = $this->actingAs($this->pengaju)
            ->get(route('gudang.pencocokan-surplus-defisit.index', [
                'status_permintaan' => 'terkirim',
                'tahun' => now()->year,
                'bulan' => now()->month,
            ]));
        $menunggu->assertOk();
        $menunggu->assertSee('Tidak ada permintaan dengan status Terkirim.');
        $menunggu->assertDontSee('Beras Dummy');

        $ditolak = $this->get(route('gudang.pencocokan-surplus-defisit.index', [
            'status_permintaan' => 'ditolak',
            'tahun' => now()->year,
            'bulan' => now()->month,
        ]));
        $ditolak->assertOk();
        $ditolak->assertSee('Beras Dummy');
        $ditolak->assertSee('Ditolak');
    }
}
