<?php

namespace Tests\Feature;

use App\Models\Master\AsetTetap;
use App\Models\Pengguna;
use App\Services\Finance\TutupBulanService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AsetTetapDanKasTransaksiTest extends TestCase
{
    use DatabaseTruncation;

    private int $idEntitas;
    private int $idEntitasLain;
    private Pengguna $user;
    private TutupBulanService $tutupBulanService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutupBulanService = app(TutupBulanService::class);

        $sfx = uniqid();
        $idWilayah1 = DB::table('wilayah')->insertGetId([
            'tingkat'    => 'desa',
            'nama'       => 'Desa Test Aset 1 ' . $sfx,
            'created_at' => now(),
        ]);

        $idWilayah2 = DB::table('wilayah')->insertGetId([
            'tingkat'    => 'desa',
            'nama'       => 'Desa Test Aset 2 ' . $sfx,
            'created_at' => now(),
        ]);

        $this->idEntitas = DB::table('entitas')->insertGetId([
            'kode_entitas'    => 'AT1-' . $sfx,
            'nama_entitas'    => 'BUMDes Aset Test',
            'jenis_entitas'   => 'bumdes',
            'status'          => 'APPROVED',
            'id_wilayah'      => $idWilayah1,
            'tahun_buku_awal' => 2026,
            'is_active'       => 1,
            'created_at'      => now(),
        ]);

        $this->idEntitasLain = DB::table('entitas')->insertGetId([
            'kode_entitas'    => 'AT2-' . $sfx,
            'nama_entitas'    => 'BUMDes Tetangga',
            'jenis_entitas'   => 'bumdes',
            'status'          => 'APPROVED',
            'id_wilayah'      => $idWilayah2,
            'tahun_buku_awal' => 2026,
            'is_active'       => 1,
            'created_at'      => now(),
        ]);

        app()->instance('koperasi_aktif', $this->idEntitas);
        app()->instance('entitas_aktif', $this->idEntitas);

        $idUser = DB::table('pengguna')->insertGetId([
            'nama'       => 'Akuntan Test',
            'email'      => 'akuntan-' . $sfx . '@test.local',
            'password'   => bcrypt('password'),
            'id_entitas' => $this->idEntitas,
            'is_active'  => 1,
            'created_at' => now(),
        ]);

        foreach (['akuntan' => 'Akuntan', 'kasir' => 'Kasir'] as $kode => $nama) {
            DB::table('peran')->updateOrInsert(['kode' => $kode], ['nama' => $nama]);
            $idPeran = DB::table('peran')->where('kode', $kode)->value('id_peran');
            DB::table('pengguna_peran')->insertOrIgnore([
                'id_pengguna' => $idUser,
                'id_peran'    => $idPeran,
            ]);
        }

        $this->user = Pengguna::withoutGlobalScopes()->findOrFail($idUser);

        DB::table('master_coa')->insertOrIgnore([
            ['kode_anak' => '1111', 'nama_rekening' => 'Kas', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 4, 'is_kontra' => 0, 'urutan_laporan' => 1, 'is_active' => 1],
            ['kode_anak' => '121', 'nama_rekening' => 'Aset Tetap (Header)', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'F', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 10, 'is_active' => 1],
            ['kode_anak' => '1211', 'nama_rekening' => 'Tanah', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 4, 'is_kontra' => 0, 'urutan_laporan' => 11, 'is_active' => 1],
            ['kode_anak' => '1212', 'nama_rekening' => 'Gedung & Bangunan', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 4, 'is_kontra' => 0, 'urutan_laporan' => 12, 'is_active' => 1],
            ['kode_anak' => '1214', 'nama_rekening' => 'Peralatan & Elektronik', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 4, 'is_kontra' => 0, 'urutan_laporan' => 14, 'is_active' => 1],
            ['kode_anak' => '1224', 'nama_rekening' => 'Akum. Penyusutan Peralatan', 'kelompok' => 'Aktiva', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 4, 'is_kontra' => 1, 'urutan_laporan' => 24, 'is_active' => 1],
            ['kode_anak' => '611', 'nama_rekening' => 'Biaya Gaji & Upah', 'kelompok' => 'Biaya', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 61, 'is_active' => 1],
            ['kode_anak' => '633', 'nama_rekening' => 'Biaya Penyusutan Peralatan', 'kelompok' => 'Biaya', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 63, 'is_active' => 1],
            ['kode_anak' => '699', 'nama_rekening' => 'Biaya Nonaktif', 'kelompok' => 'Biaya', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 69, 'is_active' => 0],
        ]);

        DB::table('master_transaksi')->updateOrInsert(
            ['kode_transaksi' => 'PNY'],
            ['nama_transaksi' => 'Penyusutan Aset Tetap', 'modul' => 'penyesuaian', 'is_active' => true]
        );

        DB::table('master_kas_bank')->insert([
            'id_entitas' => $this->idEntitas,
            'jenis'      => 'kas',
            'nama'       => 'Kas Utama BUMDes',
            'kode_akun'  => '1111',
            'is_default' => 1,
            'is_active'  => 1,
        ]);

        DB::table('periode_akuntansi')->insertOrIgnore([
            ['id_entitas' => $this->idEntitas, 'tahun' => 2026, 'bulan' => 11, 'status' => 'OPEN'],
            ['id_entitas' => $this->idEntitas, 'tahun' => 2026, 'bulan' => 12, 'status' => 'OPEN'],
        ]);
    }

    public function test_form_aset_tetap_tanpa_kebocoran_tenant_dan_dukung_tanah_serta_peralatan(): void
    {
        $createRes = $this->actingAs($this->user)->get(route('akuntansi.aset-tetap.create'));
        $createRes->assertOk()
            ->assertSee('Peralatan &amp; Elektronik (1214)', false)
            ->assertSee('Tanah (1211 - Tidak Disusutkan)')
            ->assertSee('value="1211"', false)
            ->assertSee('value="1224"', false)
            ->assertSee('value="633"', false)
            ->assertDontSee('value="121"', false)
            ->assertDontSee('Biaya Nonaktif');

        // Simpan Tanah: umur_bulan=0 dan kode_akun_akum/biaya kosong harus lolos dan dinormalisasi null
        $tanahRes = $this->actingAs($this->user)->post(route('akuntansi.aset-tetap.store'), [
            'kode_aset'       => 'AST-TNH-01',
            'nama_aset'       => 'Tanah Gudang Kasir',
            'kategori'        => 'tanah',
            'tgl_perolehan'   => '2026-11-01',
            'nilai_perolehan' => 50000000,
            'nilai_residu'    => 0,
            'umur_bulan'      => '',
            'kode_akun_aset'  => '1211',
            'kode_akun_akum'  => '',
            'kode_akun_biaya' => '',
        ]);
        $tanahRes->assertRedirect(route('akuntansi.aset-tetap.index'));

        $this->assertDatabaseHas('aset_tetap', [
            'id_entitas'      => $this->idEntitas,
            'kode_aset'       => 'AST-TNH-01',
            'kategori'        => 'tanah',
            'umur_bulan'      => 0,
            'kode_akun_akum'  => null,
            'kode_akun_biaya' => null,
            'status'          => 'aktif',
        ]);

        // Simpan Peralatan
        $alatRes = $this->actingAs($this->user)->post(route('akuntansi.aset-tetap.store'), [
            'kode_aset'       => 'AST-PRL-01',
            'nama_aset'       => 'Komputer Kasir POS',
            'kategori'        => 'peralatan',
            'tgl_perolehan'   => '2026-11-02',
            'nilai_perolehan' => 6000000,
            'nilai_residu'    => 0,
            'umur_bulan'      => 48,
            'kode_akun_aset'  => '1214',
            'kode_akun_akum'  => '1224',
            'kode_akun_biaya' => '633',
        ]);
        $alatRes->assertRedirect(route('akuntansi.aset-tetap.index'));

        // Pastikan pencarian ?q=Kasir pada entitas aktif tidak membocorkan aset entitas lain
        DB::table('aset_tetap')->insert([
            'id_entitas'      => $this->idEntitasLain,
            'kode_aset'       => 'AST-LAIN-01',
            'nama_aset'       => 'Printer Kasir Tetangga',
            'kategori'        => 'peralatan',
            'tgl_perolehan'   => '2026-11-02',
            'nilai_perolehan' => 2000000,
            'nilai_residu'    => 0,
            'umur_bulan'      => 24,
            'akum_penyusutan' => 0,
            'kode_akun_aset'  => '1214',
            'kode_akun_akum'  => '1224',
            'kode_akun_biaya' => '633',
            'status'          => 'aktif',
        ]);

        $indexRes = $this->actingAs($this->user)->get(route('akuntansi.aset-tetap.index', ['q' => 'Kasir']));
        $indexRes->assertOk()
            ->assertSee('Komputer Kasir POS')
            ->assertSee('Tanah Gudang Kasir')
            ->assertDontSee('Printer Kasir Tetangga');

        // Edit & update peralatan (termasuk edit Blade & enum status lowercase)
        $alat = AsetTetap::withoutGlobalScopes()
            ->where('id_entitas', $this->idEntitas)
            ->where('kode_aset', 'AST-PRL-01')
            ->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('akuntansi.aset-tetap.edit', $alat->id_aset))
            ->assertOk()
            ->assertSee('Edit Aset Tetap')
            ->assertSee('AST-PRL-01');

        $updateRes = $this->actingAs($this->user)->put(route('akuntansi.aset-tetap.update', $alat->id_aset), [
            'nama_aset'       => 'Komputer Kasir POS Utama',
            'kategori'        => 'peralatan',
            'nilai_residu'    => 200000,
            'umur_bulan'      => 36,
            'kode_akun_aset'  => '1214',
            'kode_akun_akum'  => '1224',
            'kode_akun_biaya' => '633',
            'status'          => 'aktif',
        ]);
        $updateRes->assertRedirect(route('akuntansi.aset-tetap.index'));

        $this->assertDatabaseHas('aset_tetap', [
            'id_aset'      => $alat->id_aset,
            'nama_aset'    => 'Komputer Kasir POS Utama',
            'nilai_residu' => 200000,
            'umur_bulan'   => 36,
            'status'       => 'aktif',
        ]);
    }

    public function test_form_kas_transaksi_hanya_menampilkan_akun_transaksi_aktif(): void
    {
        $res = $this->actingAs($this->user)->get(route('keuangan.kas-transaksi.create'));

        $res->assertOk()
            ->assertSee('Kas Utama BUMDes')
            ->assertSee('611 - Biaya Gaji &amp; Upah', false)
            ->assertSee('633 - Biaya Penyusutan Peralatan', false)
            ->assertDontSee('Aset Tetap (Header)')
            ->assertDontSee('Biaya Nonaktif');
    }

    public function test_tutup_bulan_penyusutan_aset_multi_bulan_skip_tanah_dan_tgl_masa_depan(): void
    {
        $idTanah = DB::table('aset_tetap')->insertGetId([
            'id_entitas'      => $this->idEntitas,
            'kode_aset'       => 'TNH-01',
            'nama_aset'       => 'Tanah Kantor',
            'kategori'        => 'tanah',
            'tgl_perolehan'   => '2026-11-01',
            'nilai_perolehan' => 50000000,
            'nilai_residu'    => 0,
            'umur_bulan'      => 0,
            'akum_penyusutan' => 0,
            'kode_akun_aset'  => '1211',
            'kode_akun_akum'  => null,
            'kode_akun_biaya' => null,
            'status'          => 'aktif',
        ]);

        // Disusutkan (1.200.000 - 200.000) / 2 bulan = 500.000 per bulan; bulan 2 menjadi habis_susut
        $idTimbangan = DB::table('aset_tetap')->insertGetId([
            'id_entitas'      => $this->idEntitas,
            'kode_aset'       => 'PRL-01',
            'nama_aset'       => 'Timbangan Digital',
            'kategori'        => 'peralatan',
            'tgl_perolehan'   => '2026-11-05',
            'nilai_perolehan' => 1200000,
            'nilai_residu'    => 200000,
            'umur_bulan'      => 2,
            'akum_penyusutan' => 0,
            'kode_akun_aset'  => '1214',
            'kode_akun_akum'  => '1224',
            'kode_akun_biaya' => '633',
            'status'          => 'aktif',
        ]);

        // Perolehan Desember: harus dilewati saat tutup bulan November, baru susut Desember (200.000/bulan)
        $idEtalase = DB::table('aset_tetap')->insertGetId([
            'id_entitas'      => $this->idEntitas,
            'kode_aset'       => 'PRL-02',
            'nama_aset'       => 'Etalase Kaca',
            'kategori'        => 'peralatan',
            'tgl_perolehan'   => '2026-12-05',
            'nilai_perolehan' => 2400000,
            'nilai_residu'    => 0,
            'umur_bulan'      => 12,
            'akum_penyusutan' => 0,
            'kode_akun_aset'  => '1214',
            'kode_akun_akum'  => '1224',
            'kode_akun_biaya' => '633',
            'status'          => 'aktif',
        ]);

        $this->actingAs($this->user);

        // Tutup Bulan 1: November 2026
        $periodeNov = $this->tutupBulanService->tutupBulan(2026, 11);
        $this->assertEquals('CLOSED', $periodeNov->status);

        $this->assertDatabaseHas('aset_tetap', ['id_aset' => $idTanah, 'akum_penyusutan' => 0, 'status' => 'aktif']);
        $this->assertDatabaseHas('aset_tetap', ['id_aset' => $idEtalase, 'akum_penyusutan' => 0, 'status' => 'aktif']);
        $this->assertDatabaseHas('aset_tetap', ['id_aset' => $idTimbangan, 'akum_penyusutan' => 500000, 'status' => 'aktif']);

        $this->assertDatabaseHas('jurnal_header', [
            'id_entitas'     => $this->idEntitas,
            'periode_tahun'  => 2026,
            'periode_bulan'  => 11,
            'kode_transaksi' => 'PNY',
            'jenis_jurnal'   => 'PENYESUAIAN',
            'source_type'    => 'ASET_TETAP_2026_11',
            'source_id'      => $idTimbangan,
            'total_debet'    => 500000,
            'status'         => 'POSTED',
        ]);

        // Tutup Bulan 2: Desember 2026 (Timbangan susut kedua -> habis_susut, Etalase mulai susut)
        $periodeDes = $this->tutupBulanService->tutupBulan(2026, 12);
        $this->assertEquals('CLOSED', $periodeDes->status);

        $this->assertDatabaseHas('aset_tetap', ['id_aset' => $idTanah, 'akum_penyusutan' => 0, 'status' => 'aktif']);
        $this->assertDatabaseHas('aset_tetap', ['id_aset' => $idTimbangan, 'akum_penyusutan' => 1000000, 'status' => 'habis_susut']);
        $this->assertDatabaseHas('aset_tetap', ['id_aset' => $idEtalase, 'akum_penyusutan' => 200000, 'status' => 'aktif']);

        $this->assertDatabaseHas('jurnal_header', [
            'id_entitas'     => $this->idEntitas,
            'periode_tahun'  => 2026,
            'periode_bulan'  => 12,
            'kode_transaksi' => 'PNY',
            'source_type'    => 'ASET_TETAP_2026_12',
            'source_id'      => $idTimbangan,
            'total_debet'    => 500000,
            'status'         => 'POSTED',
        ]);

        $this->assertDatabaseHas('jurnal_header', [
            'id_entitas'     => $this->idEntitas,
            'periode_tahun'  => 2026,
            'periode_bulan'  => 12,
            'kode_transaksi' => 'PNY',
            'source_type'    => 'ASET_TETAP_2026_12',
            'source_id'      => $idEtalase,
            'total_debet'    => 200000,
            'status'         => 'POSTED',
        ]);

        $jumlahPnyTanah = DB::table('jurnal_header')
            ->where('id_entitas', $this->idEntitas)
            ->where('kode_transaksi', 'PNY')
            ->where('source_id', $idTanah)
            ->count();
        $this->assertSame(0, $jumlahPnyTanah);
    }

    public function test_edit_perolehan_terbuka_sebelum_susut_dan_terkunci_setelah_susut(): void
    {
        $idTanah = DB::table('aset_tetap')->insertGetId([
            'id_entitas'      => $this->idEntitas,
            'kode_aset'       => 'AST-TNH-EDIT',
            'nama_aset'       => 'Tanah Awal Lupa Nilai',
            'kategori'        => 'tanah',
            'tgl_perolehan'   => '2026-01-07',
            'nilai_perolehan' => 0,
            'nilai_residu'    => 0,
            'umur_bulan'      => 0,
            'akum_penyusutan' => 0,
            'kode_akun_aset'  => '1211',
            'kode_akun_akum'  => null,
            'kode_akun_biaya' => null,
            'status'          => 'aktif',
        ]);

        // Belum pernah disusutkan -> tanggal & nilai perolehan terbuka dan bisa disimpan
        $this->actingAs($this->user)
            ->get(route('akuntansi.aset-tetap.edit', $idTanah))
            ->assertOk()
            ->assertSee('name="tgl_perolehan"', false)
            ->assertSee('name="nilai_perolehan"', false);

        $this->actingAs($this->user)
            ->put(route('akuntansi.aset-tetap.update', $idTanah), [
                'nama_aset'       => 'Tanah Lahan 200m2',
                'kategori'        => 'tanah',
                'tgl_perolehan'   => '2026-01-02',
                'nilai_perolehan' => 100000000,
                'nilai_residu'    => 100000000,
                'umur_bulan'      => 0,
                'kode_akun_aset'  => '1211',
                'kode_akun_akum'  => '',
                'kode_akun_biaya' => '',
                'status'          => 'aktif',
            ])
            ->assertRedirect(route('akuntansi.aset-tetap.index'));

        $this->assertDatabaseHas('aset_tetap', [
            'id_aset'         => $idTanah,
            'nama_aset'       => 'Tanah Lahan 200m2',
            'tgl_perolehan'   => '2026-01-02',
            'nilai_perolehan' => 100000000,
            'nilai_residu'    => 100000000,
        ]);

        // Aset sudah disusutkan -> tanggal & nilai perolehan terkunci di form maupun update()
        $idAlat = DB::table('aset_tetap')->insertGetId([
            'id_entitas'      => $this->idEntitas,
            'kode_aset'       => 'AST-PRL-SUSUT',
            'nama_aset'       => 'Mesin Kasir',
            'kategori'        => 'peralatan',
            'tgl_perolehan'   => '2026-11-01',
            'nilai_perolehan' => 4800000,
            'nilai_residu'    => 0,
            'umur_bulan'      => 48,
            'akum_penyusutan' => 100000,
            'kode_akun_aset'  => '1214',
            'kode_akun_akum'  => '1224',
            'kode_akun_biaya' => '633',
            'status'          => 'aktif',
        ]);

        $this->actingAs($this->user)
            ->get(route('akuntansi.aset-tetap.edit', $idAlat))
            ->assertOk()
            ->assertDontSee('name="tgl_perolehan"', false)
            ->assertDontSee('name="nilai_perolehan"', false);

        $this->actingAs($this->user)
            ->put(route('akuntansi.aset-tetap.update', $idAlat), [
                'nama_aset'       => 'Mesin Kasir Revisi',
                'kategori'        => 'peralatan',
                'tgl_perolehan'   => '2026-05-01',
                'nilai_perolehan' => 99999999,
                'nilai_residu'    => 100000,
                'umur_bulan'      => 48,
                'kode_akun_aset'  => '1214',
                'kode_akun_akum'  => '1224',
                'kode_akun_biaya' => '633',
                'status'          => 'aktif',
            ])
            ->assertRedirect(route('akuntansi.aset-tetap.index'));

        $this->assertDatabaseHas('aset_tetap', [
            'id_aset'         => $idAlat,
            'nama_aset'       => 'Mesin Kasir Revisi',
            'tgl_perolehan'   => '2026-11-01',
            'nilai_perolehan' => 4800000,
        ]);
    }
}

