<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Master\Komoditas;
use App\Models\Peran;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Perencanaan\RekomendasiPengadaanDesa;
use App\Models\Survei\PemenuhanKomoditas;
use App\Models\Survei\SesiSurvei;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use App\Services\Perencanaan\PerbandinganHargaService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class M3PerencanaanTest extends TestCase
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

    public function test_store_pr_menyimpan_header_detail_dan_lokasi_tujuan_valid(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = $this->idWilayahAktif($user);
        $idGudang = $this->idGudangEntitas($user);
        $idBarang = $this->idBarangAktif();
        $idKomoditas = (int) DB::table('master_barang')->where('id_barang', $idBarang)->value('id_komoditas');
        $hasil = HasilKalkulasi::create([
            'id_wilayah' => $idWilayah,
            'id_komoditas' => $idKomoditas,
            'tahun' => 2026,
            'bulan' => 10,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->post(route('perencanaan.permintaan-pengadaan.store'), [
            'id_hasil' => $hasil->id_hasil,
            'id_barang' => $idBarang,
            'id_gudang' => $idGudang,
            'tgl_pengajuan' => '2026-10-30',
            'catatan' => 'Kebutuhan pengadaan test',
        ]);

        $response->assertRedirect(route('perencanaan.permintaan-pengadaan.index'));
        $this->assertDatabaseHas('permintaan_pengadaan', [
            'id_entitas' => $user->id_entitas,
            'id_unit_usaha' => 1,
            'id_gudang' => $idGudang,
            'status' => 'draft',
        ]);

        $pr = PermintaanPengadaan::where('id_entitas', $user->id_entitas)->latest('id_permintaan')->firstOrFail();
        $this->assertSame(1, $pr->detail()->count());
        $this->assertSame(75.0, (float) $pr->detail()->first()->jumlah_diminta);
        $this->assertSame($hasil->id_hasil, $pr->detail()->first()->id_hasil);
    }

    public function test_store_pr_menolak_gudang_milik_entitas_lain(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = $this->idWilayahAktif($user);
        $idGudangEntitasLain = (int) DB::table('gudang')
            ->where('id_entitas', '!=', $user->id_entitas)
            ->where('is_active', true)
            ->value('id_gudang');
        $hasil = HasilKalkulasi::create([
            'id_wilayah' => $idWilayah,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 10,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->post(route('perencanaan.permintaan-pengadaan.store'), [
            'id_hasil' => $hasil->id_hasil,
            'id_barang' => 1,
            'id_gudang' => $idGudangEntitasLain,
            'tgl_pengajuan' => '2026-10-30',
        ]);

        $response->assertNotFound();
        $this->assertSame(0, PermintaanPengadaan::where('id_entitas', $user->id_entitas)->count());
    }

    public function test_calon_supplier_mengambil_potensi_produksi_desa_dan_kosong_dengan_benar(): void
    {
        $service = app(PerbandinganHargaService::class);
        KetersediaanKomoditas::create([
            'id_wilayah' => 1,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 10,
            'jumlah_produksi' => 500,
            'satuan' => 'KG',
        ]);

        $candidates = $service->calonSupplier(1, 2026, 10);
        $this->assertCount(1, $candidates);
        $this->assertSame(500.0, (float) $candidates->first()->jumlah_produksi);
        $this->assertFalse(array_key_exists('stok_aktual', $candidates->first()->getAttributes()));
        $this->assertCount(0, $service->calonSupplier(1, 2026, 11));
    }

    public function test_perbandingan_harga_mengurutkan_desa_dan_mengabaikan_satuan_berbeda(): void
    {
        $petugas = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $sesiA = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => 1,
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-10',
            'status' => 'disetujui',
        ]);
        $sesiB = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => 2,
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-12',
            'status' => 'disetujui',
        ]);
        $sesiLain = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => 2,
            'tahun' => 2025,
            'tanggal_survei' => '2025-10-12',
            'status' => 'disetujui',
        ]);
        $sesiDraft = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => 1,
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-13',
            'status' => 'draft',
        ]);

        PemenuhanKomoditas::create([
            'id_sesi' => $sesiA->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar A',
            'harga' => 12000,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-10',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesiB->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar B',
            'harga' => 10000,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-12',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesiB->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar B',
            'harga' => 9,
            'satuan_harga' => 'LITER',
            'tanggal_survei' => '2026-10-12',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesiLain->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar Lama',
            'harga' => 1,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2025-10-12',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesiDraft->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar Draft',
            'harga' => 1,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-13',
        ]);

        $result = app(PerbandinganHargaService::class)->bandingkan(1, 1, 2026, 10);

        $this->assertSame(10000.0, $result['lowest']);
        $this->assertSame(12000.0, $result['highest']);
        $this->assertSame(2000.0, $result['difference']);
        $this->assertSame('KG', $result['satuan']);
        $this->assertSame([2, 1], $result['items']->pluck('id_wilayah_sumber')->all());
        $this->assertSame([1, 2], $result['items']->pluck('rank_harga')->all());
    }

    public function test_perbandingan_harga_mengembalikan_ringkasan_kosong(): void
    {
        $result = app(PerbandinganHargaService::class)->bandingkan(1, 5, 2026, 12);

        $this->assertCount(0, $result['items']);
        $this->assertNull($result['lowest']);
        $this->assertNull($result['highest']);
        $this->assertNull($result['difference']);
        $this->assertNull($result['satuan']);
    }

    public function test_store_pr_menggunakan_harga_survei_valid_dan_menghitung_subtotal(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = $this->idWilayahAktif($user);
        $idGudang = $this->idGudangEntitas($user);
        $idBarang = $this->idBarangAktif();
        $idKomoditas = (int) DB::table('master_barang')->where('id_barang', $idBarang)->value('id_komoditas');
        $sesi = SesiSurvei::create([
            'id_petugas' => $user->id,
            'id_wilayah' => $idWilayah,
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-10',
            'status' => 'disetujui',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesi->id,
            'id_komoditas' => $idKomoditas,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar A',
            'harga' => 12000,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-10',
        ]);
        $hasil = HasilKalkulasi::create([
            'id_wilayah' => $idWilayah,
            'id_komoditas' => $idKomoditas,
            'tahun' => 2026,
            'bulan' => 10,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -2,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->post(route('perencanaan.permintaan-pengadaan.store'), [
            'id_hasil' => $hasil->id_hasil,
            'id_barang' => $idBarang,
            'id_gudang' => $idGudang,
            'tgl_pengajuan' => '2026-10-30',
        ]);

        $response->assertRedirect(route('perencanaan.permintaan-pengadaan.index'));
        $pr = PermintaanPengadaan::where('id_entitas', $user->id_entitas)->latest('id_permintaan')->firstOrFail();
        $detail = $pr->detail()->firstOrFail();
        $this->assertSame(12000.0, (float) $detail->harga_perkiraan);
        $this->assertSame(24000.0, (float) $detail->subtotal);
        $this->assertSame(24000.0, (float) $pr->total_nilai);
    }

    public function test_index_perencanaan_menampilkan_kebutuhan_defisit_dari_kalkulasi(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        HasilKalkulasi::create([
            'id_wilayah' => $this->idWilayahAktif($user),
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 7,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get(route('perencanaan.permintaan-pengadaan.index'));

        $response->assertOk()
            ->assertSee('Rekomendasi Surplus dan Defisit Antar-Desa')
            ->assertSee('Belum ada pasangan untuk direkomendasikan')
            ->assertSee('Pengajuan masuk ke antrean Admin Gudang')
            ->assertDontSee('Buat draft PR')
            ->assertSee('Periode Genap')
            ->assertSee('komoditas');
    }

    public function test_halaman_rekomendasi_dapat_dibuka_dari_menu_sidebar_perencanaan(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $urlRekomendasi = route('perencanaan.rekomendasi-pengadaan.index');

        $this->actingAs($user)
            ->get($urlRekomendasi)
            ->assertOk()
            ->assertSee('Rekomendasi Pengadaan Antar-Desa')
            ->assertSee($urlRekomendasi, false);
    }

    public function test_rekomendasi_mengelompokkan_semua_kategori_komoditas(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $komoditas = Komoditas::create([
            'nama' => 'Ikan Uji Rekomendasi',
            'kategori' => 'perikanan',
            'is_active' => true,
        ]);
        $hasil = HasilKalkulasi::create([
            'id_wilayah' => $this->idWilayahAktif($user),
            'id_komoditas' => $komoditas->id,
            'tahun' => 2026,
            'bulan' => 7,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get(route('perencanaan.permintaan-pengadaan.index'));

        $response->assertOk()
            ->assertSee('Perikanan')
            ->assertSee('Pertanian')
            ->assertSee('Perkebunan')
            ->assertSee('Peternakan')
            ->assertSee('Belum ada hasil surplus atau defisit pada sektor ini')
            ->assertSee('Ikan Uji Rekomendasi')
            ->assertSee('Belum ada pasangan untuk direkomendasikan')
            ->assertDontSee(route('perencanaan.permintaan-pengadaan.from-hasil', $hasil->id_hasil))
            ->assertDontSee('Setujui PR')
            ->assertDontSee('<details open', false);
    }

    public function test_rekomendasi_menampilkan_nama_komoditas_satu_kali_dengan_dropdown_periode_panen(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $komoditas = Komoditas::create([
            'nama' => 'Komoditas Uji Periode Panen',
            'kategori' => 'pertanian',
            'is_active' => true,
        ]);

        foreach ([1, 7] as $bulan) {
            HasilKalkulasi::create([
                'id_wilayah' => $this->idWilayahAktif($user),
                'id_komoditas' => $komoditas->id,
                'tahun' => 2099,
                'bulan' => $bulan,
                'total_kebutuhan' => 100,
                'total_ketersediaan' => 25,
                'selisih' => -75,
                'status_surplus_defisit' => 'defisit',
                'id_unit_usaha_rekomendasi' => 1,
                'status' => 'disetujui',
            ]);
        }

        $response = $this->actingAs($user)->get(route('perencanaan.rekomendasi-pengadaan.index'));

        $response->assertOk()
            ->assertSee('2 periode panen')
            ->assertSee('Periode Ganjil · Tahun 2099')
            ->assertSee('Periode Genap · Tahun 2099');
        $this->assertSame(1, substr_count($response->getContent(), $komoditas->nama));
    }

    public function test_rekomendasi_mencocokkan_defisit_dengan_desa_surplus_seperiode(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = $this->idWilayahAktif($user);
        $desaSurplus = Wilayah::firstOrCreate(
            ['nama' => 'Desa Surplus Rekomendasi Uji'],
            ['tingkat' => 'desa', 'is_active' => true]
        );
        $desaBedaPeriode = Wilayah::firstOrCreate(
            ['nama' => 'Desa Surplus Beda Periode Uji'],
            ['tingkat' => 'desa', 'is_active' => true]
        );
        $this->buatEntitasDesaAktif($desaSurplus);
        $versiDefisit = (int) HasilKalkulasi::where('id_wilayah', $idWilayah)
            ->where('id_komoditas', 1)
            ->where('tahun', 2026)
            ->where('bulan', 7)
            ->max('versi') + 1;
        $versiSurplus = (int) HasilKalkulasi::where('id_wilayah', $desaSurplus->id)
            ->where('id_komoditas', 1)
            ->where('tahun', 2026)
            ->where('bulan', 7)
            ->max('versi') + 1;

        HasilKalkulasi::create([
            'id_wilayah' => $idWilayah,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 7,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'versi' => $versiDefisit,
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);
        HasilKalkulasi::create([
            'id_wilayah' => $desaSurplus->id,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 7,
            'total_kebutuhan' => 25,
            'total_ketersediaan' => 100,
            'selisih' => 75,
            'status_surplus_defisit' => 'surplus',
            'versi' => $versiSurplus,
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);
        HasilKalkulasi::create([
            'id_wilayah' => $desaBedaPeriode->id,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 1,
            'total_kebutuhan' => 25,
            'total_ketersediaan' => 100,
            'selisih' => 75,
            'status_surplus_defisit' => 'surplus',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get(route('perencanaan.permintaan-pengadaan.index'));

        $response->assertOk()
            ->assertSee($desaSurplus->nama)
            ->assertSee('Komoditas Defisit Desa Sendiri')
            ->assertSee('Periode Genap')
            ->assertSee('Tahun 2026')
            ->assertSee('Surplus Komoditas Desa Lain')
            ->assertSee('Pilih desa dengan status surplus')
            ->assertSee('Rekomendasikan ke Desa Lain')
            ->assertSee('Surplus 75,00')
            ->assertSee('Tersedia 100,00')
            ->assertDontSee($desaBedaPeriode->nama);
    }

    public function test_desa_dengan_surplus_dapat_merekomendasikan_ke_desa_defisit(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayahSendiri = $this->idWilayahAktif($user);
        DB::table('entitas')->where('id_entitas', $user->id_entitas)
            ->update(['is_active' => true, 'status' => 'APPROVED']);
        $desaDefisit = Wilayah::firstOrCreate(
            ['nama' => 'Desa Defisit Pasangan Surplus Uji'],
            ['tingkat' => 'desa', 'is_active' => true]
        );
        $this->buatEntitasDesaAktif($desaDefisit);
        $tahun = 2098;
        $versiSurplus = (int) HasilKalkulasi::where('id_wilayah', $idWilayahSendiri)
            ->where('id_komoditas', 1)
            ->where('tahun', $tahun)
            ->where('bulan', 1)
            ->max('versi') + 1;
        $versiDefisit = (int) HasilKalkulasi::where('id_wilayah', $desaDefisit->id)
            ->where('id_komoditas', 1)
            ->where('tahun', $tahun)
            ->where('bulan', 1)
            ->max('versi') + 1;
        $surplusSendiri = HasilKalkulasi::create([
            'id_wilayah' => $idWilayahSendiri,
            'id_komoditas' => 1,
            'tahun' => $tahun,
            'bulan' => 1,
            'total_kebutuhan' => 25,
            'total_ketersediaan' => 100,
            'selisih' => 75,
            'status_surplus_defisit' => 'surplus',
            'versi' => $versiSurplus,
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);
        $defisitDesaLain = HasilKalkulasi::create([
            'id_wilayah' => $desaDefisit->id,
            'id_komoditas' => 1,
            'tahun' => $tahun,
            'bulan' => 1,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'versi' => $versiDefisit,
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $this->assertDatabaseHas('entitas', [
            'id_entitas' => $user->id_entitas,
            'id_wilayah' => $idWilayahSendiri,
            'is_active' => true,
            'status' => 'APPROVED',
        ]);
        $this->actingAs($user)
            ->get(route('perencanaan.permintaan-pengadaan.index'))
            ->assertOk()
            ->assertSee('Komoditas Surplus Desa Sendiri')
            ->assertSee('Defisit Komoditas Desa Lain')
            ->assertSee($desaDefisit->nama)
            ->assertSee('Pilih desa dengan status defisit')
            ->assertSee('Rekomendasikan ke Desa Lain');

        $jumlahPrSebelumnya = PermintaanPengadaan::count();
        $response = $this->actingAs($user)->post(route('perencanaan.rekomendasi-pengadaan.store'), [
            'id_hasil_defisit' => $defisitDesaLain->id_hasil,
            'id_hasil_surplus' => $surplusSendiri->id_hasil,
        ]);
        $response->assertRedirect(route('perencanaan.rekomendasi-pengadaan.index'));

        $this->assertDatabaseHas('rekomendasi_pengadaan_desa', [
            'id_hasil_defisit' => $defisitDesaLain->id_hasil,
            'id_hasil_surplus' => $surplusSendiri->id_hasil,
            'id_pengaju' => $user->id,
            'status' => 'diajukan',
        ]);
        $this->assertSame($jumlahPrSebelumnya, PermintaanPengadaan::count());
    }

    public function test_desa_dapat_mengajukan_rekomendasi_tanpa_membuat_pr(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        [$defisit, $surplus] = $this->buatPasanganHasilRekomendasi();
        $jumlahPrSebelumnya = PermintaanPengadaan::count();

        $response = $this->actingAs($user)->post(route('perencanaan.rekomendasi-pengadaan.store'), [
            'id_hasil_defisit' => $defisit->id_hasil,
            'id_hasil_surplus' => $surplus->id_hasil,
        ]);

        $response->assertRedirect(route('perencanaan.rekomendasi-pengadaan.index'));
        $this->assertDatabaseHas('rekomendasi_pengadaan_desa', [
            'id_hasil_defisit' => $defisit->id_hasil,
            'id_hasil_surplus' => $surplus->id_hasil,
            'id_pengaju' => $user->id,
            'status' => 'diajukan',
        ]);
        $this->assertSame($jumlahPrSebelumnya, PermintaanPengadaan::count());
    }

    public function test_desa_defisit_dapat_mengirim_rekomendasi_ke_tiga_desa_surplus(): void
    {
        $pengaju = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        [$defisit, $surplusPertama] = $this->buatPasanganHasilRekomendasi();
        $surplusKedua = $this->buatSurplusTambahan($defisit, 'Desa Surplus Kedua Uji');
        $surplusKetiga = $this->buatSurplusTambahan($defisit, 'Desa Surplus Ketiga Uji');
        $manajerSurplus = [
            $this->buatManajerDesa((int) DB::table('entitas')->where('id_wilayah', $surplusPertama->id_wilayah)->value('id_entitas')),
            $this->buatManajerDesa((int) DB::table('entitas')->where('id_wilayah', $surplusKedua->id_wilayah)->value('id_entitas')),
            $this->buatManajerDesa((int) DB::table('entitas')->where('id_wilayah', $surplusKetiga->id_wilayah)->value('id_entitas')),
        ];

        $response = $this->actingAs($pengaju)->post(route('perencanaan.rekomendasi-pengadaan.store'), [
            'id_hasil_defisit' => $defisit->id_hasil,
            'id_hasil_surplus' => [$surplusPertama->id_hasil, $surplusKedua->id_hasil, $surplusKetiga->id_hasil],
        ]);

        $response->assertRedirect(route('perencanaan.rekomendasi-pengadaan.index'));
        $this->assertSame(3, RekomendasiPengadaanDesa::where('id_hasil_defisit', $defisit->id_hasil)->count());
        foreach ($manajerSurplus as $manajer) {
            $this->assertSame(1, $manajer->notifications()->count());
            $this->assertSame('rekomendasi_pengadaan', $manajer->notifications()->first()->data['type']);
        }
    }

    public function test_persetujuan_desa_surplus_menutup_alternatif_dan_tidak_membuat_pr(): void
    {
        $pengaju = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        [$defisit, $surplusTerpilih] = $this->buatPasanganHasilRekomendasi();
        $surplusAlternatif = $this->buatSurplusTambahan($defisit, 'Desa Surplus Alternatif Uji');
        $this->actingAs($pengaju)->post(route('perencanaan.rekomendasi-pengadaan.store'), [
            'id_hasil_defisit' => $defisit->id_hasil,
            'id_hasil_surplus' => [$surplusTerpilih->id_hasil, $surplusAlternatif->id_hasil],
        ])->assertRedirect();
        $rekomendasiTerpilih = RekomendasiPengadaanDesa::where('id_hasil_surplus', $surplusTerpilih->id_hasil)->firstOrFail();
        $rekomendasiAlternatif = RekomendasiPengadaanDesa::where('id_hasil_surplus', $surplusAlternatif->id_hasil)->firstOrFail();
        $entitasSurplus = (int) DB::table('entitas')->where('id_wilayah', $surplusTerpilih->id_wilayah)->value('id_entitas');
        $manajerSurplus = $this->buatManajerDesa($entitasSurplus);
        $jumlahPrSebelumnya = PermintaanPengadaan::count();

        $this->actingAs($manajerSurplus)
            ->post(route('perencanaan.rekomendasi-pengadaan.setujui', $rekomendasiTerpilih->id_rekomendasi))
            ->assertRedirect();

        $this->assertDatabaseHas('rekomendasi_pengadaan_desa', [
            'id_rekomendasi' => $rekomendasiTerpilih->id_rekomendasi,
            'status' => 'disetujui',
        ]);
        $this->assertDatabaseHas('rekomendasi_pengadaan_desa', [
            'id_rekomendasi' => $rekomendasiAlternatif->id_rekomendasi,
            'status' => 'ditolak',
            'catatan_penyetuju' => 'Ditutup otomatis karena rekomendasi desa lain telah disetujui lebih dahulu.',
        ]);
        $this->assertSame($jumlahPrSebelumnya, PermintaanPengadaan::count());
        $this->assertSame(1, $pengaju->notifications()->whereNull('read_at')->count());
    }

    public function test_admin_gudang_dapat_menyetujui_rekomendasi_tanpa_membuat_pr(): void
    {
        $pengaju = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        [$defisit, $surplus] = $this->buatPasanganHasilRekomendasi();
        $this->actingAs($pengaju)->post(route('perencanaan.rekomendasi-pengadaan.store'), [
            'id_hasil_defisit' => $defisit->id_hasil,
            'id_hasil_surplus' => $surplus->id_hasil,
        ])->assertRedirect();
        $rekomendasi = RekomendasiPengadaanDesa::where('id_hasil_defisit', $defisit->id_hasil)->firstOrFail();

        $adminGudang = $this->buatAdminGudang($pengaju);
        $jumlahPrSebelumnya = PermintaanPengadaan::count();

        $this->actingAs($adminGudang)
            ->get(route('gudang.rekomendasi-pengadaan.index'))
            ->assertOk()
            ->assertSee('Persetujuan Rekomendasi Pengadaan')
            ->assertSee('Desa Surplus Antrean Uji');

        $response = $this->actingAs($adminGudang)->post(route('gudang.rekomendasi-pengadaan.setujui', $rekomendasi->id_rekomendasi));

        $response->assertRedirect();
        $this->assertDatabaseHas('rekomendasi_pengadaan_desa', [
            'id_rekomendasi' => $rekomendasi->id_rekomendasi,
            'status' => 'disetujui',
            'diputuskan_oleh' => $adminGudang->id,
        ]);
        $this->assertSame($jumlahPrSebelumnya, PermintaanPengadaan::count());
    }

    public function test_admin_gudang_dapat_meninjau_rekomendasi_dari_desa_lain(): void
    {
        $pengaju = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        [$defisit, $surplus] = $this->buatPasanganHasilRekomendasi();
        $this->actingAs($pengaju)->post(route('perencanaan.rekomendasi-pengadaan.store'), [
            'id_hasil_defisit' => $defisit->id_hasil,
            'id_hasil_surplus' => $surplus->id_hasil,
        ])->assertRedirect();
        $rekomendasi = RekomendasiPengadaanDesa::where('id_hasil_defisit', $defisit->id_hasil)->firstOrFail();

        $wilayahGudang = Wilayah::create([
            'nama' => 'Desa Admin Gudang Lintas Wilayah Uji',
            'tingkat' => 'desa',
            'is_active' => true,
        ]);
        $entitasGudang = Entitas::create([
            'kode_entitas' => 'AG-'.strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'nama_entitas' => 'Entitas Admin Gudang Lintas Wilayah Uji',
            'id_wilayah' => $wilayahGudang->id,
            'tahun_buku_awal' => 2026,
            'is_active' => true,
            'status' => 'APPROVED',
        ]);
        $adminGudang = Pengguna::create([
            'id_entitas' => $entitasGudang->id_entitas,
            'nama' => 'Admin Gudang Lintas Wilayah Uji',
            'email' => 'admin-gudang-lintas-'.strtolower(substr(md5(uniqid('', true)), 0, 8)).'@kdmp.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $peran = Peran::firstOrCreate(['kode' => 'admin_gudang'], ['nama' => 'Admin Gudang']);
        $adminGudang->peran()->syncWithoutDetaching([$peran->id_peran]);

        $jumlahPrSebelumnya = PermintaanPengadaan::count();
        $this->actingAs($adminGudang)
            ->get(route('gudang.rekomendasi-pengadaan.index'))
            ->assertOk()
            ->assertSee($rekomendasi->hasilDefisit->wilayah->nama);

        $this->actingAs($adminGudang)
            ->post(route('gudang.rekomendasi-pengadaan.setujui', $rekomendasi->id_rekomendasi))
            ->assertRedirect();

        $this->assertDatabaseHas('rekomendasi_pengadaan_desa', [
            'id_rekomendasi' => $rekomendasi->id_rekomendasi,
            'status' => 'disetujui',
            'diputuskan_oleh' => $adminGudang->id,
        ]);
        $this->assertSame($jumlahPrSebelumnya, PermintaanPengadaan::count());
    }

    public function test_admin_gudang_dapat_menolak_rekomendasi_dengan_catatan_tanpa_membuat_pr(): void
    {
        $pengaju = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        [$defisit, $surplus] = $this->buatPasanganHasilRekomendasi();
        $this->actingAs($pengaju)->post(route('perencanaan.rekomendasi-pengadaan.store'), [
            'id_hasil_defisit' => $defisit->id_hasil,
            'id_hasil_surplus' => $surplus->id_hasil,
        ])->assertRedirect();
        $rekomendasi = RekomendasiPengadaanDesa::where('id_hasil_defisit', $defisit->id_hasil)->firstOrFail();
        $adminGudang = $this->buatAdminGudang($pengaju);
        $jumlahPrSebelumnya = PermintaanPengadaan::count();

        $response = $this->actingAs($adminGudang)->post(
            route('gudang.rekomendasi-pengadaan.tolak', $rekomendasi->id_rekomendasi),
            ['catatan_penyetuju' => 'Perlu koordinasi ulang.']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('rekomendasi_pengadaan_desa', [
            'id_rekomendasi' => $rekomendasi->id_rekomendasi,
            'status' => 'ditolak',
            'diputuskan_oleh' => $adminGudang->id,
            'catatan_penyetuju' => 'Perlu koordinasi ulang.',
        ]);
        $this->assertSame($jumlahPrSebelumnya, PermintaanPengadaan::count());
    }

    public function test_admin_gudang_tidak_dapat_mengajukan_rekomendasi_sebagai_desa(): void
    {
        $referensi = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $adminGudang = $this->buatAdminGudang($referensi);
        [$defisit, $surplus] = $this->buatPasanganHasilRekomendasi();

        $response = $this->actingAs($adminGudang)->post(route('perencanaan.rekomendasi-pengadaan.store'), [
            'id_hasil_defisit' => $defisit->id_hasil,
            'id_hasil_surplus' => $surplus->id_hasil,
        ]);

        $response->assertForbidden();
        $this->assertSame(0, RekomendasiPengadaanDesa::count());
    }

    public function test_pengaju_admin_gudang_tidak_dapat_menyetujui_rekomendasinya_sendiri(): void
    {
        $pengaju = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $peran = Peran::firstOrCreate(['kode' => 'admin_gudang'], ['nama' => 'Admin Gudang']);
        $pengaju->peran()->syncWithoutDetaching([$peran->id_peran]);
        [$defisit, $surplus] = $this->buatPasanganHasilRekomendasi();
        $this->actingAs($pengaju)->post(route('perencanaan.rekomendasi-pengadaan.store'), [
            'id_hasil_defisit' => $defisit->id_hasil,
            'id_hasil_surplus' => $surplus->id_hasil,
        ])->assertRedirect();
        $rekomendasi = RekomendasiPengadaanDesa::where('id_hasil_defisit', $defisit->id_hasil)->firstOrFail();

        $response = $this->actingAs($pengaju)->post(route('gudang.rekomendasi-pengadaan.setujui', $rekomendasi->id_rekomendasi));

        $response->assertForbidden();
        $this->assertDatabaseHas('rekomendasi_pengadaan_desa', [
            'id_rekomendasi' => $rekomendasi->id_rekomendasi,
            'status' => 'diajukan',
        ]);
    }

    private function buatSurplusTambahan(HasilKalkulasi $defisit, string $namaWilayah): HasilKalkulasi
    {
        $wilayah = Wilayah::create([
            'nama' => $namaWilayah,
            'tingkat' => 'desa',
            'is_active' => true,
        ]);
        $this->buatEntitasDesaAktif($wilayah);
        $versi = (int) HasilKalkulasi::query()
            ->where('id_wilayah', $wilayah->id)
            ->where('id_komoditas', $defisit->id_komoditas)
            ->where('tahun', $defisit->tahun)
            ->where('bulan', $defisit->bulan)
            ->max('versi') + 1;

        return HasilKalkulasi::create([
            'id_wilayah' => $wilayah->id,
            'id_komoditas' => $defisit->id_komoditas,
            'tahun' => $defisit->tahun,
            'bulan' => $defisit->bulan,
            'total_kebutuhan' => 25,
            'total_ketersediaan' => 100,
            'selisih' => 75,
            'status_surplus_defisit' => 'surplus',
            'versi' => $versi,
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);
    }

    private function buatManajerDesa(int $idEntitas): Pengguna
    {
        $suffix = strtolower(substr(md5(uniqid('', true)), 0, 10));
        $manajer = Pengguna::create([
            'id_entitas' => $idEntitas,
            'nama' => 'Manajer Desa Uji',
            'email' => 'manajer-uji-'.$suffix.'@kdmp.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $peran = Peran::firstOrCreate(['kode' => 'manajer'], ['nama' => 'Manajer']);
        $manajer->peran()->syncWithoutDetaching([$peran->id_peran]);

        return $manajer;
    }

    private function buatPasanganHasilRekomendasi(): array
    {
        $pengaju = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $idWilayah = $this->idWilayahAktif($pengaju);
        $desaSurplus = Wilayah::firstOrCreate(
            ['nama' => 'Desa Surplus Antrean Uji'],
            ['tingkat' => 'desa', 'is_active' => true]
        );
        $this->buatEntitasDesaAktif($desaSurplus);
        $tahun = 2097;
        $versiDefisit = (int) HasilKalkulasi::where('id_wilayah', $idWilayah)
            ->where('id_komoditas', 1)
            ->where('tahun', $tahun)
            ->where('bulan', 1)
            ->max('versi') + 1;
        $versiSurplus = (int) HasilKalkulasi::where('id_wilayah', $desaSurplus->id)
            ->where('id_komoditas', 1)
            ->where('tahun', $tahun)
            ->where('bulan', 1)
            ->max('versi') + 1;

        $defisit = HasilKalkulasi::create([
            'id_wilayah' => $idWilayah,
            'id_komoditas' => 1,
            'tahun' => $tahun,
            'bulan' => 1,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'versi' => $versiDefisit,
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);
        $surplus = HasilKalkulasi::create([
            'id_wilayah' => $desaSurplus->id,
            'id_komoditas' => 1,
            'tahun' => $tahun,
            'bulan' => 1,
            'total_kebutuhan' => 25,
            'total_ketersediaan' => 100,
            'selisih' => 75,
            'status_surplus_defisit' => 'surplus',
            'versi' => $versiSurplus,
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        return [$defisit, $surplus];
    }

    private function buatAdminGudang(Pengguna $pengguna): Pengguna
    {
        $adminGudang = Pengguna::create([
            'id_entitas' => $pengguna->id_entitas,
            'nama' => 'Admin Gudang Persetujuan Uji',
            'email' => 'admin-gudang-persetujuan-uji@kdmp-a.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $peran = Peran::firstOrCreate(['kode' => 'admin_gudang'], ['nama' => 'Admin Gudang']);
        $adminGudang->peran()->syncWithoutDetaching([$peran->id_peran]);

        return $adminGudang;
    }

    private function buatEntitasDesaAktif(Wilayah $wilayah): Entitas
    {
        return Entitas::updateOrCreate(
            ['id_wilayah' => $wilayah->id],
            [
                'kode_entitas' => 'UJI-'.strtoupper(substr(md5((string) $wilayah->id), 0, 8)),
                'nama_entitas' => 'Entitas '.$wilayah->nama,
                'tahun_buku_awal' => 2026,
                'is_active' => true,
                'status' => 'APPROVED',
            ]
        );
    }

    public function test_laporan_desa_sendiri_memiliki_tautan_ke_rekomendasi_pengadaan(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $hasil = HasilKalkulasi::create([
            'id_wilayah' => $this->idWilayahAktif($user),
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 1,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get(route('perencanaan.laporan.index', [
            'periode' => 'ganjil',
        ]));

        $response->assertOk()
            ->assertSee('Lihat rekomendasi pengadaan')
            ->assertSee(route('perencanaan.rekomendasi-pengadaan.index'))
            ->assertDontSee(route('perencanaan.permintaan-pengadaan.from-hasil', $hasil->id_hasil));
    }

    public function test_desa_aktif_tidak_bisa_membuat_pr_dari_hasil_desa_lain(): void
    {
        $user = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $wilayahLain = Wilayah::where('id', '!=', $this->idWilayahAktif($user))->firstOrFail();
        $hasilDesaLain = HasilKalkulasi::create([
            'id_wilayah' => $wilayahLain->id,
            'id_komoditas' => 1,
            'tahun' => 2026,
            'bulan' => 7,
            'total_kebutuhan' => 100,
            'total_ketersediaan' => 25,
            'selisih' => -75,
            'status_surplus_defisit' => 'defisit',
            'id_unit_usaha_rekomendasi' => 1,
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get(route('perencanaan.permintaan-pengadaan.from-hasil', $hasilDesaLain->id_hasil));

        $response->assertForbidden();
    }

    public function test_halaman_perbandingan_harga_menampilkan_data_dan_periode_yang_dipilih(): void
    {
        $petugas = Pengguna::where('email', 'manajer@kdmp-a.test')->firstOrFail();
        $sesi = SesiSurvei::create([
            'id_petugas' => $petugas->id,
            'id_wilayah' => $this->idWilayahAktif($petugas),
            'tahun' => 2026,
            'tanggal_survei' => '2026-10-10',
            'status' => 'disetujui',
        ]);
        PemenuhanKomoditas::create([
            'id_sesi' => $sesi->id,
            'id_komoditas' => 1,
            'sumber_pemenuhan' => 'Pasar',
            'nama_tempat' => 'Pasar A',
            'harga' => 12000,
            'satuan_harga' => 'KG',
            'tanggal_survei' => '2026-10-10',
        ]);

        $response = $this->actingAs($petugas)->get(route('perencanaan.perbandingan-harga.index', [
            'id_komoditas' => 1,
            'tahun' => 2026,
            'periode' => 'genap',
        ]));

        $response->assertOk()
            ->assertSee('Rp 12.000,00')
            ->assertSee('Genap (Juli–Desember)');
    }

    private function idWilayahAktif(Pengguna $user): int
    {
        return (int) DB::table('entitas')->where('id_entitas', $user->id_entitas)->value('id_wilayah');
    }

    private function idGudangEntitas(Pengguna $user): int
    {
        return (int) DB::table('gudang')
            ->where('id_entitas', $user->id_entitas)
            ->where('is_active', true)
            ->value('id_gudang');
    }

    private function idBarangAktif(): int
    {
        return (int) DB::table('master_barang')
            ->where('is_active', true)
            ->value('id_barang');
    }
}
