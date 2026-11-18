<?php

namespace Tests\Feature;

use App\Models\Akuntansi\Coa;
use App\Models\Akuntansi\JurnalHeader;
use App\Models\Master\Barang;
use App\Models\Master\Komoditas;
use App\Models\Master\Satuan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use App\Services\Finance\JurnalService;
use App\Services\Finance\TutupBulanService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * End-to-End Test Suite: Alur Flow Studi Kasus Lengkap KDMP v2
 * Dari Registrasi User/Entitas Baru -> Approval Super Admin -> Master Data ->
 * Perencanaan Pangan -> Pembelian & FIFO Gudang -> Penjualan POS -> Akuntansi & Tutup Bulan.
 */
class AlurFlowStudiKasusLengkapTest extends TestCase
{
    use DatabaseTruncation;

    private JurnalService $jurnalService;
    private TutupBulanService $tutupBulanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jurnalService = app(JurnalService::class);
        $this->tutupBulanService = app(TutupBulanService::class);

        // Inisialisasi COA Standar
        $this->seedStandardCoa();

        // Inisialisasi Super Admin
        $superAdmin = Pengguna::updateOrCreate(
            ['email' => 'superadmin@ngobar.id'],
            [
                'nama' => 'Super Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $roleSuperAdmin = Peran::firstOrCreate(['kode' => 'super_admin'], ['nama' => 'Super Admin']);
        DB::table('pengguna_peran')->updateOrInsert([
            'id_pengguna' => $superAdmin->id,
            'id_peran' => $roleSuperAdmin->id_peran,
        ]);
    }

    private function seedStandardCoa(): void
    {
        $coas = [
            ['kode_anak' => '1111', 'nama_rekening' => 'Kas Utama', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D'],
            ['kode_anak' => '11121', 'nama_rekening' => 'Bank A', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D'],
            ['kode_anak' => '1123', 'nama_rekening' => 'Persediaan Barang Sembako', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D'],
            ['kode_anak' => '1131', 'nama_rekening' => 'Piutang Usaha', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D'],
            ['kode_anak' => '2111', 'nama_rekening' => 'Hutang Usaha', 'kelompok' => 'Kewajiban', 'posisi_normal' => 'K'],
            ['kode_anak' => '311', 'nama_rekening' => 'Simpanan Pokok', 'kelompok' => 'Modal', 'posisi_normal' => 'K'],
            ['kode_anak' => '312', 'nama_rekening' => 'Simpanan Wajib', 'kelompok' => 'Modal', 'posisi_normal' => 'K'],
            ['kode_anak' => '411', 'nama_rekening' => 'Penjualan Sembako - Anggota', 'kelompok' => 'Pendapatan', 'posisi_normal' => 'K'],
            ['kode_anak' => '412', 'nama_rekening' => 'Penjualan Sembako - Non Anggota', 'kelompok' => 'Pendapatan', 'posisi_normal' => 'K'],
            ['kode_anak' => '511', 'nama_rekening' => 'HPP Sembako', 'kelompok' => 'HPP', 'posisi_normal' => 'D'],
            ['kode_anak' => '611', 'nama_rekening' => 'Biaya Operasional', 'kelompok' => 'Biaya', 'posisi_normal' => 'D'],
            ['kode_anak' => '811', 'nama_rekening' => 'Ikhtisar Laba Rugi', 'kelompok' => 'Modal', 'posisi_normal' => 'K'],
        ];

        foreach ($coas as $c) {
            DB::table('master_coa')->updateOrInsert(
                ['kode_anak' => $c['kode_anak']],
                array_merge($c, [
                    'is_transaction' => 'T',
                    'level' => 3,
                    'is_kontra' => 0,
                    'urutan_laporan' => 10,
                    'is_active' => 1,
                ])
            );
        }

        DB::table('master_unit_usaha')->updateOrInsert(
            ['kode_unit_usaha' => 'SEMBAKO'],
            [
                'nama_unit_usaha' => 'Sembako',
                'kode_akun_persediaan' => '1123',
                'kode_akun_pendapatan_anggota' => '411',
                'kode_akun_pendapatan_non_anggota' => '412',
                'kode_akun_hpp' => '511',
            ]
        );
    }

    /**
     * @test
     * Skenario Penuh Alur Flow Sistem KDMP v2:
     */
    public function test_alur_lengkap_studi_kasus_dari_registrasi_hingga_tutup_bulan(): void
    {
        // ---------------------------------------------------------------------
        // STEP 1: Registrasi User & Koperasi Baru (Desa Sukatani - KDMP-C)
        // ---------------------------------------------------------------------
        $wilayah = Wilayah::create(['nama' => 'Desa Sukatani', 'tingkat' => 'desa']);
        $entitasBaru = Entitas::create([
            'kode_entitas' => 'KDMP-C',
            'nama_entitas' => 'Koperasi Desa Sukatani Makmur',
            'jenis_entitas' => 'Koperasi Desa',
            'id_wilayah' => $wilayah->id,
            'tahun_buku_awal' => 2026,
            'status' => 'PENDING',
            'is_active' => false,
        ]);

        $calonManajer = Pengguna::create([
            'id_entitas' => $entitasBaru->id_entitas,
            'nama' => 'Budi Santoso',
            'email' => 'manajer@kdmp-c.test',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $this->assertEquals('PENDING', $entitasBaru->status);
        $this->assertFalse((bool) $calonManajer->is_active);

        // ---------------------------------------------------------------------
        // STEP 2: Approval oleh Super Admin
        // ---------------------------------------------------------------------
        \App\Http\Controllers\Master\EntitasController::provisionDefaultMasterData($entitasBaru);
        $entitasBaru->update(['status' => 'APPROVED', 'is_active' => true]);
        $calonManajer->update(['is_active' => true]);

        $roleManajer = Peran::firstOrCreate(['kode' => 'manajer'], ['nama' => 'Manajer']);
        DB::table('pengguna_peran')->updateOrInsert([
            'id_pengguna' => $calonManajer->id,
            'id_peran' => $roleManajer->id_peran,
        ]);

        // Verifikasi auto-provisioning
        $this->assertDatabaseHas('gudang', ['id_entitas' => $entitasBaru->id_entitas, 'kode_gudang' => 'UTAMA']);
        $this->assertDatabaseHas('master_kas_bank', ['id_entitas' => $entitasBaru->id_entitas, 'kode_akun' => '1111']);
        $this->assertDatabaseHas('periode_akuntansi', ['id_entitas' => $entitasBaru->id_entitas, 'tahun' => 2026, 'bulan' => 1]);

        // Set context aktif
        app()->instance('entitas_aktif', $entitasBaru->id_entitas);
        $idEntitas = $entitasBaru->id_entitas;

        // ---------------------------------------------------------------------
        // STEP 3: Setup Master Data Spesifik Entitas (Kustomisasi Bank & Barang)
        // ---------------------------------------------------------------------
        // Kustomisasi akun Bank 11121 khusus KDMP-C
        DB::table('master_kas_bank')->updateOrInsert(
            ['id_entitas' => $idEntitas, 'kode_akun' => '11121'],
            [
                'nama' => 'Bank BRI Unit Sukatani',
                'no_rekening' => '0123-01-000456-50-1',
                'jenis' => 'bank',
                'is_default' => false,
                'is_active' => true,
            ]
        );
        $bankMap = Coa::bankMapForEntitas($idEntitas);
        $this->assertArrayHasKey('11121', $bankMap);
        $this->assertEquals('Bank BRI Unit Sukatani', $bankMap['11121']->nama);

        // Satuan KG otomatis tersedia via provisionDefaultMasterData; tambah Komoditas Lokal
        $satuanKg = Satuan::firstOrCreate(
            ['kode_satuan' => 'KG', 'id_entitas' => $idEntitas],
            ['is_active' => true]
        );
        $komoditasBeras = Komoditas::create([
            'nama' => 'Beras Raja Lele',
            'kategori' => 'pangan_pokok',
            'id_entitas' => $idEntitas,
            'is_active' => true,
        ]);
        $unitSembako = DB::table('master_unit_usaha')->where('kode_unit_usaha', 'SEMBAKO')->first();

        // Tambah Master Barang & Pengaturan Niaga
        $barang = Barang::create([
            'kode_barang' => 'BRG-LELE-01',
            'nama_barang' => 'Beras Raja Lele Super 50kg',
            'id_komoditas' => $komoditasBeras->id,
            'id_satuan_dasar' => $satuanKg->id,
            'id_unit_usaha' => $unitSembako->id_unit_usaha,
            'id_entitas' => $idEntitas,
            'is_active' => true,
        ]);

        DB::table('barang_per_koperasi')->insert([
            'id_entitas' => $idEntitas,
            'id_barang' => $barang->id_barang,
            'harga_jual_standar' => 15000,
            'stok_minimum' => 10,
            'stok_maksimum' => 500,
            'is_dijual' => true,
        ]);

        // Tambah Pihak Mitra Tani
        $idPetani = DB::table('master_pihak')->insertGetId([
            'id_entitas' => $idEntitas,
            'nama' => 'Poktan Tani Sejahtera Sukatani',
            'jenis_pihak' => 'petani',
            'is_anggota' => true,
            'no_anggota' => 'ANG-ST-001',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ---------------------------------------------------------------------
        // STEP 3.5: Setoran Modal Awal (Simpanan Pokok Anggota Rp 5.000.000)
        // Agar Saldo Kas Entitas Positif dan Tidak Menghasilkan Anomali
        // ---------------------------------------------------------------------
        $this->jurnalService->postingManual(
            header: [
                'id_entitas' => $idEntitas,
                'tanggal_jurnal' => '2026-01-02',
                'jenis_jurnal' => 'MANUAL',
                'kode_transaksi' => null,
                'no_referensi' => 'MODAL-AWAL-001',
                'keterangan' => 'Setoran Simpanan Pokok Pendirian Koperasi',
            ],
            baris: [
                ['kode_anak' => '1111', 'posisi' => 'D', 'nilai' => 5000000, 'keterangan' => 'Penerimaan Kas Simpanan Pokok'],
                ['kode_anak' => '311', 'posisi' => 'K', 'nilai' => 5000000, 'keterangan' => 'Simpanan Pokok Anggota'],
            ]
        );

        // ---------------------------------------------------------------------
        // STEP 4: Pembelian Stok Barang (100 KG @ Rp 11.000 = Rp 1.100.000)
        // ---------------------------------------------------------------------
        $gudangUtama = DB::table('gudang')->where('id_entitas', $idEntitas)->first();

        $idPembelian = DB::table('pembelian')->insertGetId([
            'id_entitas' => $idEntitas,
            'kode_pembelian' => 'PO-ST-2026-001',
            'tanggal_transaksi' => '2026-01-10',
            'id_pihak' => $idPetani,
            'id_unit_usaha' => $unitSembako->id_unit_usaha,
            'id_gudang' => $gudangUtama->id_gudang,
            'total_pembelian' => 1100000,
            'jenis_pembayaran' => 'tunai',
            'status' => 'selesai',
            'status_posting' => 'T',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simpan Kartu Stok FIFO (100 KG masuk @ 11.000)
        DB::table('kartu_stok')->insert([
            'id_entitas' => $idEntitas,
            'id_gudang' => $gudangUtama->id_gudang,
            'id_barang' => $barang->id_barang,
            'tanggal' => '2026-01-10',
            'jenis_mutasi' => 'IN',
            'qty_masuk' => 100,
            'qty_keluar' => 0,
            'saldo_qty' => 100,
            'harga_satuan' => 11000,
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => $idPembelian,
            'created_at' => now(),
        ]);

        // Auto Jurnal Pembelian: (D) Persediaan 1123 Rp 1.100.000, (K) Kas Utama 1111 Rp 1.100.000
        $this->jurnalService->postingManual(
            header: [
                'id_entitas' => $idEntitas,
                'tanggal_jurnal' => '2026-01-10',
                'jenis_jurnal' => 'MANUAL',
                'kode_transaksi' => null,
                'no_referensi' => 'PO-ST-2026-001',
                'keterangan' => 'Pembelian 100 KG Beras Raja Lele dari Poktan Tani Sejahtera',
            ],
            baris: [
                ['kode_anak' => '1123', 'posisi' => 'D', 'nilai' => 1100000, 'keterangan' => 'Persediaan Beras'],
                ['kode_anak' => '1111', 'posisi' => 'K', 'nilai' => 1100000, 'keterangan' => 'Kas Keluar'],
            ]
        );

        // ---------------------------------------------------------------------
        // STEP 5: Penjualan POS (40 KG @ Rp 15.000 = Rp 600.000)
        // ---------------------------------------------------------------------
        // HPP FIFO: 40 KG x Rp 11.000 = Rp 440.000
        // Laba Kotor: Rp 600.000 - Rp 440.000 = Rp 160.000
        $idPenjualan = DB::table('penjualan')->insertGetId([
            'id_entitas' => $idEntitas,
            'kode_penjualan' => 'POS-ST-2026-001',
            'tanggal_transaksi' => '2026-01-15',
            'id_pihak' => $idPetani,
            'id_unit_usaha' => $unitSembako->id_unit_usaha,
            'id_gudang' => $gudangUtama->id_gudang,
            'is_pembeli_anggota' => true,
            'total_bruto' => 600000,
            'total_bayar' => 600000,
            'total_hpp' => 440000,
            'metode_bayar' => 'tunai',
            'status' => 'selesai',
            'status_posting' => 'T',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update Kartu Stok (Keluar 40 KG, Sisa 60 KG)
        DB::table('kartu_stok')->insert([
            'id_entitas' => $idEntitas,
            'id_gudang' => $gudangUtama->id_gudang,
            'id_barang' => $barang->id_barang,
            'tanggal' => '2026-01-15',
            'jenis_mutasi' => 'OUT',
            'qty_masuk' => 0,
            'qty_keluar' => 40,
            'saldo_qty' => 60,
            'harga_satuan' => 11000,
            'ref_tipe' => 'PENJUALAN',
            'ref_id' => $idPenjualan,
            'created_at' => now(),
        ]);

        // Update Posisi Stok Fisik di Gudang (Sisa 60 KG @ 11.000 = Rp 660.000)
        DB::table('stok')->updateOrInsert(
            ['id_gudang' => $gudangUtama->id_gudang, 'id_barang' => $barang->id_barang],
            [
                'qty_on_hand' => 60,
                'hpp_rata2' => 11000,
                'nilai_persediaan' => 660000,
                'updated_at' => now(),
            ]
        );
        $this->jurnalService->postingManual(
            header: [
                'id_entitas' => $idEntitas,
                'tanggal_jurnal' => '2026-01-15',
                'jenis_jurnal' => 'MANUAL',
                'kode_transaksi' => null,
                'no_referensi' => 'POS-ST-2026-001',
                'keterangan' => 'Penjualan POS 40 KG Beras ke Anggota',
            ],
            baris: [
                ['kode_anak' => '1111', 'posisi' => 'D', 'nilai' => 600000, 'keterangan' => 'Kas Masuk'],
                ['kode_anak' => '411', 'posisi' => 'K', 'nilai' => 600000, 'keterangan' => 'Pendapatan Penjualan Anggota'],
                ['kode_anak' => '511', 'posisi' => 'D', 'nilai' => 440000, 'keterangan' => 'HPP Sembako FIFO'],
                ['kode_anak' => '1123', 'posisi' => 'K', 'nilai' => 440000, 'keterangan' => 'Pengurangan Persediaan'],
            ]
        );

        // ---------------------------------------------------------------------
        // STEP 6: Verifikasi Laporan Keuangan (Laba Rugi & Neraca Saldo)
        // ---------------------------------------------------------------------
        $jurnals = JurnalHeader::where('id_entitas', $idEntitas)->with('detail')->get();
        $this->assertCount(3, $jurnals);

        // Cek keseimbangan buku besar (Debet = Kredit)
        $totalDebet = DB::table('jurnal_detail as d')
            ->join('jurnal_header as h', 'h.id_jurnal', '=', 'd.id_jurnal')
            ->where('h.id_entitas', $idEntitas)
            ->sum('d.debet');

        $totalKredit = DB::table('jurnal_detail as d')
            ->join('jurnal_header as h', 'h.id_jurnal', '=', 'd.id_jurnal')
            ->where('h.id_entitas', $idEntitas)
            ->sum('d.kredit');

        $this->assertEquals($totalDebet, $totalKredit, 'Jurnal harus selalu balance (Total Debet = Total Kredit)');
        $this->assertEquals(7140000, $totalDebet);

        // ---------------------------------------------------------------------
        // STEP 7: Eksekusi Tutup Bulan (Bulan 1 Tahun 2026)
        // ---------------------------------------------------------------------
        $periode = $this->tutupBulanService->tutupBulan(2026, 1);
        $this->assertEquals('CLOSED', $periode->status);

        // Verifikasi Periode Bulan 2 Otomatis Siap
        $periodeBulan2 = DB::table('periode_akuntansi')
            ->where('id_entitas', $idEntitas)
            ->where('tahun', 2026)
            ->where('bulan', 2)
            ->first();
        $this->assertNotNull($periodeBulan2);
        $this->assertEquals('OPEN', $periodeBulan2->status);
    }
}
