<?php

namespace Tests\Feature;

use App\Http\Controllers\Akuntansi\LaporanController;
use App\Models\Akuntansi\BukuBesarPeriode;
use App\Models\Akuntansi\JurnalHeader;
use App\Models\Pengguna;
use App\Models\Tenant\KoperasiDesa;
use App\Models\Tenant\PeriodeAkuntansi;
use App\Services\Finance\JurnalService;
use App\Services\Finance\TutupBulanService;
use App\Services\Finance\TutupTahunService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\Request;
use Tests\TestCase;
use DB;

class FinanceEndToEndTest extends TestCase
{
    use DatabaseTruncation;

    private JurnalService $jurnalService;
    private TutupBulanService $tutupBulanService;
    private TutupTahunService $tutupTahunService;
    private int $idKoperasi;
    private Pengguna $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jurnalService = app(JurnalService::class);
        $this->tutupBulanService = app(TutupBulanService::class);
        $this->tutupTahunService = app(TutupTahunService::class);

        // Setup Wilayah & Koperasi
        $sfx = uniqid();
        $idWilayah = DB::table('wilayah')->insertGetId([
            'tingkat' => 'desa', 'nama' => 'Desa Finance E2E ' . $sfx, 'created_at' => now(),
        ]);

        $this->idKoperasi = DB::table('koperasi_desa')->insertGetId([
            'kode_koperasi'   => 'FN-' . $sfx,
            'nama_koperasi'   => 'Koperasi Finance Test',
            'id_wilayah'      => $idWilayah,
            'tahun_buku_awal' => 2026,
            'is_active'       => 1,
            'created_at'      => now(),
        ]);
        app()->instance('koperasi_aktif', $this->idKoperasi);

        // Setup User
        $idUser = DB::table('pengguna')->insertGetId([
            'nama'        => 'User Finance',
            'email'       => 'finance-' . $sfx . '@test.local',
            'password'    => bcrypt('password'),
            'id_koperasi' => $this->idKoperasi,
            'created_at'  => now(),
        ]);
        $this->user = Pengguna::withoutGlobalScopes()->find($idUser);

        // Insert Master COA
        DB::table('master_coa')->insertOrIgnore([
            ['kode_anak' => '111', 'nama_rekening' => 'Kas', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 1, 'is_active' => 1],
            ['kode_anak' => '411', 'nama_rekening' => 'Pendapatan Jasa', 'kelompok' => 'Pendapatan', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 10, 'is_active' => 1],
            ['kode_anak' => '511', 'nama_rekening' => 'Biaya Operasional', 'kelompok' => 'Biaya', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 20, 'is_active' => 1],
            ['kode_anak' => '312', 'nama_rekening' => 'Laba Ditahan', 'kelompok' => 'Modal', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 30, 'is_active' => 1],
            ['kode_anak' => '313', 'nama_rekening' => 'Laba Tahun Berjalan', 'kelompok' => 'Modal', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 31, 'is_active' => 1],
            ['kode_anak' => '811', 'nama_rekening' => 'Ikhtisar Laba Rugi', 'kelompok' => 'Modal', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 99, 'is_active' => 1],
        ]);

        // Open Periode 2026-12 (Desember)
        DB::table('periode_akuntansi')->insert([
            'id_koperasi' => $this->idKoperasi,
            'tahun'       => 2026,
            'bulan'       => 12,
            'status'      => 'OPEN',
        ]);
        
        // Open Periode 2027-01 (Januari, untuk test Tutup Tahun ke tahun berikutnya)
        DB::table('periode_akuntansi')->insert([
            'id_koperasi' => $this->idKoperasi,
            'tahun'       => 2027,
            'bulan'       => 1,
            'status'      => 'OPEN',
        ]);
    }

    /**
     * @test
     * Skenario 1: Posting Jurnal berjalan sukses
     */
    public function it_can_post_jurnal_successfully()
    {
        $jurnal = $this->jurnalService->postingManual(
            header: [
                'tanggal_jurnal' => '2026-12-10',
                'jenis_jurnal'   => 'MANUAL',
                'keterangan'     => 'Catat Pendapatan',
            ],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 1000000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 1000000],
            ]
        );

        $this->assertEquals('POSTED', $jurnal->status);
        $this->assertEquals(1000000, $jurnal->total_debet);
    }

    /**
     * @test
     * Skenario 2: Laporan Laba Rugi mengkalkulasi saldo dengan benar
     */
    public function it_calculates_laba_rugi_correctly()
    {
        // 1. Posting Pendapatan 1.000.000
        $this->jurnalService->postingManual(
            header: ['tanggal_jurnal' => '2026-12-10', 'jenis_jurnal' => 'MANUAL', 'keterangan' => 'Pendapatan'],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 1000000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 1000000],
            ]
        );

        // 2. Posting Biaya 300.000
        $this->jurnalService->postingManual(
            header: ['tanggal_jurnal' => '2026-12-15', 'jenis_jurnal' => 'MANUAL', 'keterangan' => 'Biaya'],
            baris: [
                ['kode_anak' => '511', 'posisi' => 'D', 'nilai' => 300000],
                ['kode_anak' => '111', 'posisi' => 'K', 'nilai' => 300000],
            ]
        );

        // Rebuild Buku Besar (biasanya dijadwalkan / otomatis, tapi kita panggil manual untuk testing)
        $this->jurnalService->bangunBukuBesar(2026, 12);

        // 3. Panggil Controller Laporan
        $controller = new LaporanController();
        $request = Request::create('/laba-rugi', 'GET', ['tahun' => 2026, 'bulan_dari' => 12, 'bulan_sampai' => 12]);
        $response = $controller->labaRugi($request);
        $viewData = $response->getData();

        $this->assertEquals(1000000, $viewData['totalPendapatan']);
        $this->assertEquals(300000, $viewData['totalBiaya']);
        $this->assertEquals(700000, $viewData['labaBersih']);
    }

    /**
     * @test
     * Skenario 3: Tutup Bulan berjalan sukses jika validasi terpenuhi
     */
    public function it_can_tutup_bulan()
    {
        // Pastikan tidak ada jurnal draft dan balance. 
        // Kita tidak buat data hutang/piutang/persediaan sehingga otomatis balance 0 = 0.
        $this->jurnalService->postingManual(
            header: ['tanggal_jurnal' => '2026-12-25', 'jenis_jurnal' => 'MANUAL', 'keterangan' => 'Trx akhir tahun'],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 500000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 500000],
            ]
        );

        $this->actingAs($this->user);
        
        $periode = $this->tutupBulanService->tutupBulan(2026, 12);

        $this->assertEquals('CLOSED', $periode->status);
        $this->assertNotNull($periode->tgl_tutup);
        $this->assertEquals($this->user->id_pengguna ?? $this->user->id, $periode->ditutup_oleh);
    }

    /**
     * @test
     * Skenario 4: Tutup Tahun memindahkan saldo Laba Berjalan ke Laba Ditahan
     */
    public function it_can_tutup_tahun()
    {
        // SETUP Pra-Kondisi Tutup Tahun
        for ($bulan = 1; $bulan <= 11; $bulan++) {
            DB::table('periode_akuntansi')->insertOrIgnore([
                'id_koperasi' => $this->idKoperasi,
                'tahun'       => 2026,
                'bulan'       => $bulan,
                'status'      => 'CLOSED',
                'tgl_tutup'   => now(),
                'ditutup_oleh'=> $this->user->id_pengguna ?? $this->user->id,
            ]);
        }
        
        DB::table('config_shu')->insert([
            'id_koperasi' => $this->idKoperasi,
            'tahun'       => 2026,
            'pos'         => 'Laba Ditahan',
            'persentase'  => 100,
            'kode_akun'   => '312',
        ]);

        // 1. Posting Laba 2.000.000 di bulan Desember 2026
        $this->jurnalService->postingManual(
            header: ['tanggal_jurnal' => '2026-12-30', 'jenis_jurnal' => 'MANUAL', 'keterangan' => 'Pendapatan'],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 2000000],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 2000000],
            ]
        );

        $this->actingAs($this->user);
        
        // 2. Tutup Bulan Desember
        $this->tutupBulanService->tutupBulan(2026, 12);

        // 3. Tutup Tahun 2026
        $this->tutupTahunService->tutupTahun(2026);

        // Build ulang buku besar bulan 12 agar jurnal penutup (SHU) masuk ke saldo akhir
        $this->jurnalService->bangunBukuBesar(2026, 12);

        // Build buku besar untuk bulan pertama tahun berikutnya agar saldo awal di-rollover
        $this->jurnalService->bangunBukuBesar(2027, 1);

        // 4. Verifikasi Buku Besar Tahun 2027 Bulan 1
        // Akun 312 (Laba Ditahan) harus bertambah 2.000.000 (kredit) sebagai saldo awal
        // Akun 111 (Kas) harus memiliki saldo awal 2.000.000 (debet)
        $bbpLabaDitahan = BukuBesarPeriode::where('id_koperasi', $this->idKoperasi)
            ->where('periode_tahun', 2027)
            ->where('periode_bulan', 1)
            ->where('kode_anak', '312')
            ->first();

        $this->assertNotNull($bbpLabaDitahan, "Buku Besar Laba Ditahan tahun berikutnya harus terbuat");
        $this->assertEquals(2000000, $bbpLabaDitahan->saldo_awal_kredit);

        $bbpKas = BukuBesarPeriode::where('id_koperasi', $this->idKoperasi)
            ->where('periode_tahun', 2027)
            ->where('periode_bulan', 1)
            ->where('kode_anak', '111')
            ->first();

        $this->assertNotNull($bbpKas, "Buku Besar Kas tahun berikutnya harus terbuat");
        $this->assertEquals(2000000, $bbpKas->saldo_awal_debet);
        
        // Cek jurnal otomatis tutup tahun
        $jurnalTutup = JurnalHeader::where('id_koperasi', $this->idKoperasi)
            ->where('periode_tahun', 2026)
            ->where('jenis_jurnal', 'PENUTUP')
            ->first();
            
        $this->assertNotNull($jurnalTutup, "Jurnal pembalik tutup tahun harus terbuat");
    }
}
