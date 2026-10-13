<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Services\Finance\JurnalService;
use App\Services\Finance\TutupBulanService;
use App\Services\Finance\TutupTahunService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tutup Tahun untuk entitas non-koperasi (tanpa config_shu / pembagian SHU).
 */
class TutupTahunNonKoperasiTest extends TestCase
{
    use DatabaseTruncation;

    private int $idEntitas;
    private Pengguna $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('master_coa')->insertOrIgnore([
            ['kode_anak' => '111', 'nama_rekening' => 'Kas', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 1, 'is_active' => 1],
            ['kode_anak' => '411', 'nama_rekening' => 'Pendapatan Jasa', 'kelompok' => 'Pendapatan', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 10, 'is_active' => 1],
            ['kode_anak' => '511', 'nama_rekening' => 'Biaya Operasional', 'kelompok' => 'Biaya', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 20, 'is_active' => 1],
            ['kode_anak' => '344', 'nama_rekening' => 'Laba Ditahan', 'kelompok' => 'Modal', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 30, 'is_active' => 1],
            ['kode_anak' => '345', 'nama_rekening' => 'Saldo Kas Kelompok', 'kelompok' => 'Modal', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 31, 'is_active' => 1],
            ['kode_anak' => '346', 'nama_rekening' => 'Modal Pemilik', 'kelompok' => 'Modal', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 32, 'is_active' => 1],
            ['kode_anak' => '811', 'nama_rekening' => 'Ikhtisar Laba Rugi', 'kelompok' => 'Ikhtisar', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 2, 'is_kontra' => 0, 'urutan_laporan' => 99, 'is_active' => 1],
        ]);
    }

    private function siapkanEntitas(string $jenis): void
    {
        $sfx = uniqid();
        $idWilayah = DB::table('wilayah')->insertGetId([
            'tingkat' => 'desa', 'nama' => 'Desa Tutup Tahun ' . $sfx, 'created_at' => now(),
        ]);

        $this->idEntitas = DB::table('entitas')->insertGetId([
            'kode_entitas'    => 'TT-' . $sfx,
            'nama_entitas'    => "Entitas {$jenis}",
            'jenis_entitas'   => $jenis,
            'id_wilayah'      => $idWilayah,
            'tahun_buku_awal' => 2026,
            'is_active'       => 1,
            'created_at'      => now(),
        ]);
        app()->instance('entitas_aktif', $this->idEntitas);

        $idUser = DB::table('pengguna')->insertGetId([
            'nama' => 'User TT', 'email' => "tt-{$sfx}@test.local",
            'password' => bcrypt('password'), 'id_entitas' => $this->idEntitas, 'created_at' => now(),
        ]);
        $this->user = Pengguna::withoutGlobalScopes()->find($idUser);
        $this->actingAs($this->user);

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            DB::table('periode_akuntansi')->insert([
                'id_entitas' => $this->idEntitas, 'tahun' => 2026, 'bulan' => $bulan,
                'status' => $bulan === 12 ? 'OPEN' : 'CLOSED',
            ]);
        }
    }

    private function posting(int $kas, int $biaya = 0): void
    {
        $jurnal = app(JurnalService::class);

        $jurnal->postingManual(
            header: ['tanggal_jurnal' => '2026-12-10', 'jenis_jurnal' => 'MANUAL', 'keterangan' => 'Pendapatan'],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => $kas],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => $kas],
            ]
        );

        if ($biaya > 0) {
            $jurnal->postingManual(
                header: ['tanggal_jurnal' => '2026-12-11', 'jenis_jurnal' => 'MANUAL', 'keterangan' => 'Biaya'],
                baris: [
                    ['kode_anak' => '511', 'posisi' => 'D', 'nilai' => $biaya],
                    ['kode_anak' => '111', 'posisi' => 'K', 'nilai' => $biaya],
                ]
            );
        }
    }

    private function saldoAkhirKredit(string $kode): float
    {
        return (float) DB::table('buku_besar_periode')
            ->where('id_entitas', $this->idEntitas)
            ->where('periode_tahun', 2026)
            ->where('kode_anak', $kode)
            ->sum(DB::raw('saldo_akhir_kredit - saldo_akhir_debet'));
    }

    /** @test */
    public function bumdes_tanpa_config_shu_bisa_tutup_tahun_dengan_laba(): void
    {
        $this->siapkanEntitas('BUMDes');
        $this->posting(2_000_000);

        app(TutupBulanService::class)->tutupBulan(2026, 12);

        $service = app(TutupTahunService::class);
        $this->assertTrue(collect($service->cekPraKondisi(2026))->every(fn ($p) => $p['lulus']));

        $service->tutupTahun(2026);
        app(JurnalService::class)->bangunBukuBesar(2026, 12);

        $this->assertEquals(2_000_000, $this->saldoAkhirKredit('344'));
        $this->assertEquals(0, $this->saldoAkhirKredit('811'));
        $this->assertEquals(0, $this->saldoAkhirKredit('411'));
        $this->assertTrue(DB::table('periode_akuntansi')->where('id_entitas', $this->idEntitas)->where('tahun', 2026)->where('status', '!=', 'LOCKED')->doesntExist());
    }

    /** @test */
    public function kelompok_tani_yang_rugi_tetap_bisa_tutup_tahun(): void
    {
        $this->siapkanEntitas('Kelompok Tani');
        // Modal awal agar kas tidak negatif saat biaya > pendapatan
        app(JurnalService::class)->postingManual(
            header: ['tanggal_jurnal' => '2026-12-01', 'jenis_jurnal' => 'MANUAL', 'keterangan' => 'Modal awal'],
            baris: [
                ['kode_anak' => '111', 'posisi' => 'D', 'nilai' => 5_000_000],
                ['kode_anak' => '344', 'posisi' => 'K', 'nilai' => 5_000_000],
            ]
        );
        $this->posting(1_000_000, 1_500_000); // rugi 500.000

        app(TutupBulanService::class)->tutupBulan(2026, 12);
        app(TutupTahunService::class)->tutupTahun(2026);
        app(JurnalService::class)->bangunBukuBesar(2026, 12);

        // Ekuitas 345 menerima rugi: saldo kredit 345 = -500.000
        $this->assertEquals(-500_000, $this->saldoAkhirKredit('345'));
        $this->assertEquals(0, $this->saldoAkhirKredit('811'));
    }

    /** @test */
    public function entitas_tanpa_transaksi_nominal_tetap_bisa_dikunci(): void
    {
        $this->siapkanEntitas('Perorangan');

        app(TutupBulanService::class)->tutupBulan(2026, 12);
        app(TutupTahunService::class)->tutupTahun(2026);

        $this->assertEquals(0, DB::table('jurnal_header')->where('id_entitas', $this->idEntitas)->where('jenis_jurnal', 'PENUTUP')->count());
        $this->assertTrue(DB::table('periode_akuntansi')->where('id_entitas', $this->idEntitas)->where('tahun', 2026)->where('status', '!=', 'LOCKED')->doesntExist());
    }

    /** @test */
    public function koperasi_tanpa_config_shu_tetap_diblokir(): void
    {
        $this->siapkanEntitas('Koperasi Desa');
        $this->posting(1_000_000);

        $pra = app(TutupTahunService::class)->cekPraKondisi(2026);

        $this->assertFalse($pra[0]['lulus']);
    }

    /** @test */
    public function tutup_ulang_ditolak(): void
    {
        $this->siapkanEntitas('BUMDes');
        app(TutupBulanService::class)->tutupBulan(2026, 12);

        $service = app(TutupTahunService::class);
        $service->tutupTahun(2026);

        $this->expectException(\LogicException::class);
        $service->tutupTahun(2026);
    }
}
