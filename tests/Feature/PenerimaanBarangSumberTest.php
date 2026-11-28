<?php

namespace Tests\Feature;

use App\Http\Controllers\Master\EntitasController;
use App\Models\Akuntansi\JurnalDetail;
use App\Models\Akuntansi\JurnalHeader;
use App\Models\Gudang\AntreanJurnalGudang;
use App\Models\Gudang\KartuStok;
use App\Models\Gudang\PenerimaanBarang;
use App\Models\Master\Barang;
use App\Models\Master\Komoditas;
use App\Models\Master\Satuan;
use App\Models\Pembelian\DetailPembelian;
use App\Models\Pembelian\Pembelian;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use App\Services\Finance\GudangJurnalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PenerimaanBarangSumberTest extends TestCase
{
    use RefreshDatabase;

    private Entitas $entitas;
    private Pengguna $adminGudang;
    private int $idGudang;
    private int $idPihak;
    private Barang $barang;
    private Satuan $satuanKg;
    private int $idUnitUsaha;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferensiMinimal();

        $wilayah = Wilayah::create(['nama' => 'Desa Uji Penerimaan', 'tingkat' => 'desa']);
        $this->entitas = Entitas::create([
            'kode_entitas' => 'KDMP-GRN',
            'nama_entitas' => 'Koperasi Uji Penerimaan',
            'jenis_entitas' => 'Koperasi Desa',
            'id_wilayah' => $wilayah->id,
            'tahun_buku_awal' => 2026,
            'status' => 'APPROVED',
            'is_active' => true,
        ]);

        EntitasController::provisionDefaultMasterData($this->entitas);
        app()->instance('entitas_aktif', $this->entitas->id_entitas);

        DB::table('periode_akuntansi')->updateOrInsert(
            ['id_entitas' => $this->entitas->id_entitas, 'tahun' => 2026, 'bulan' => 11],
            ['status' => 'OPEN']
        );

        $this->adminGudang = Pengguna::create([
            'id_entitas' => $this->entitas->id_entitas,
            'nama' => 'Admin Gudang Uji',
            'email' => 'gudang@kdmp-grn.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $peran = Peran::firstOrCreate(['kode' => 'admin_gudang'], ['nama' => 'Admin Gudang']);
        DB::table('pengguna_peran')->updateOrInsert([
            'id_pengguna' => $this->adminGudang->id,
            'id_peran' => $peran->id_peran,
        ]);

        $this->idGudang = (int) DB::table('gudang')
            ->where('id_entitas', $this->entitas->id_entitas)
            ->value('id_gudang');

        $this->idPihak = (int) DB::table('master_pihak')->insertGetId([
            'id_entitas' => $this->entitas->id_entitas,
            'nama' => 'Supplier Tani Jaya',
            'jenis_pihak' => 'supplier',
            'is_anggota' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->satuanKg = Satuan::firstOrCreate(
            ['kode_satuan' => 'KG', 'id_entitas' => $this->entitas->id_entitas],
            ['is_active' => true]
        );

        $komoditas = Komoditas::create([
            'nama' => 'Beras Uji',
            'kategori' => 'pangan_pokok',
            'id_entitas' => $this->entitas->id_entitas,
            'is_active' => true,
        ]);

        $this->barang = Barang::create([
            'kode_barang' => 'BRG-GRN-01',
            'nama_barang' => 'Beras Premium Uji',
            'id_komoditas' => $komoditas->id,
            'id_satuan_dasar' => $this->satuanKg->id,
            'id_unit_usaha' => $this->idUnitUsaha,
            'id_entitas' => $this->entitas->id_entitas,
            'is_active' => true,
        ]);
    }

    private function seedReferensiMinimal(): void
    {
        $coas = [
            ['kode_anak' => '1111', 'nama_rekening' => 'Kas Utama', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D'],
            ['kode_anak' => '11121', 'nama_rekening' => 'Bank A', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D'],
            ['kode_anak' => '1123', 'nama_rekening' => 'Persediaan Sembako', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D'],
            ['kode_anak' => '411', 'nama_rekening' => 'Penjualan Anggota', 'kelompok' => 'Pendapatan', 'posisi_normal' => 'K'],
            ['kode_anak' => '412', 'nama_rekening' => 'Penjualan Non Anggota', 'kelompok' => 'Pendapatan', 'posisi_normal' => 'K'],
            ['kode_anak' => '511', 'nama_rekening' => 'HPP Sembako', 'kelompok' => 'HPP', 'posisi_normal' => 'D'],
            ['kode_anak' => '711', 'nama_rekening' => 'Pendapatan Lain-lain', 'kelompok' => 'Pendapatan', 'posisi_normal' => 'K'],
        ];

        foreach ($coas as $coa) {
            DB::table('master_coa')->updateOrInsert(
                ['kode_anak' => $coa['kode_anak']],
                array_merge($coa, [
                    'is_transaction' => 'T',
                    'level' => 3,
                    'is_kontra' => 0,
                    'urutan_laporan' => 10,
                    'is_active' => 1,
                ])
            );
        }

        DB::table('master_unit_usaha')->updateOrInsert(
            ['kode_unit_usaha' => 'SMB'],
            [
                'nama_unit_usaha' => 'Sembako',
                'kode_akun_persediaan' => '1123',
                'kode_akun_pendapatan_anggota' => '411',
                'kode_akun_pendapatan_non_anggota' => '412',
                'kode_akun_hpp' => '511',
            ]
        );
        $this->idUnitUsaha = (int) DB::table('master_unit_usaha')
            ->where('kode_unit_usaha', 'SMB')
            ->value('id_unit_usaha');

        DB::table('master_transaksi')->updateOrInsert(
            ['kode_transaksi' => 'BTU'],
            ['nama_transaksi' => 'Pembelian Tunai', 'modul' => 'pembelian', 'is_active' => true]
        );
        DB::table('master_transaksi')->updateOrInsert(
            ['kode_transaksi' => 'JOP'],
            ['nama_transaksi' => 'Jurnal Pembuka Saldo Awal', 'modul' => 'akuntansi', 'is_active' => true]
        );
        DB::table('master_transaksi')->updateOrInsert(
            ['kode_transaksi' => 'PNB'],
            ['nama_transaksi' => 'Penerimaan Non Pembelian', 'modul' => 'gudang', 'is_active' => true]
        );

        DB::table('master_detail_transaksi')->updateOrInsert(
            ['kode_transaksi' => 'PNB', 'urutan' => 1],
            ['kode_anak' => null, 'akun_dinamis' => 'PERSEDIAAN_UNIT', 'posisi' => 'D', 'sumber_variabel' => 'nilai_penerimaan', 'is_optional' => false]
        );
        DB::table('master_detail_transaksi')->updateOrInsert(
            ['kode_transaksi' => 'PNB', 'urutan' => 2],
            ['kode_anak' => '711', 'akun_dinamis' => null, 'posisi' => 'K', 'sumber_variabel' => 'nilai_penerimaan', 'is_optional' => false]
        );
    }

    public function test_penerimaan_dari_pembelian_menautkan_jurnal_pembelian_tanpa_jurnal_ganda(): void
    {
        $jurnalPembelian = JurnalHeader::create([
            'id_entitas' => $this->entitas->id_entitas,
            'no_jurnal' => 'JRN-2026-11-0101',
            'nomor_nota' => 'PO-UJI-001',
            'tanggal_jurnal' => '2026-11-10',
            'periode_tahun' => 2026,
            'periode_bulan' => 11,
            'kode_transaksi' => 'BTU',
            'jenis_jurnal' => 'OTOMATIS',
            'total_debet' => 500000,
            'total_kredit' => 500000,
            'status' => 'POSTED',
        ]);

        $pembelian = Pembelian::create([
            'id_entitas' => $this->entitas->id_entitas,
            'kode_pembelian' => 'PO-UJI-001',
            'sumber_pembelian' => 'umum',
            'id_pihak' => $this->idPihak,
            'id_unit_usaha' => $this->idUnitUsaha,
            'id_gudang' => $this->idGudang,
            'tanggal_transaksi' => '2026-11-10',
            'jenis_pembayaran' => 'tunai',
            'total_pembelian' => 500000,
            'status' => 'disetujui',
            'status_posting' => 'T',
            'id_jurnal' => $jurnalPembelian->id_jurnal,
            'created_by' => $this->adminGudang->id,
        ]);

        DetailPembelian::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_barang' => $this->barang->id_barang,
            'id_satuan_input' => $this->satuanKg->id,
            'qty_input' => 50,
            'faktor_konversi' => 1,
            'qty_dasar' => 50,
            'harga_satuan_input' => 10000,
            'subtotal' => 500000,
        ]);

        $this->actingAs($this->adminGudang)
            ->get(route('gudang.penerimaan.create', ['id_pembelian' => $pembelian->id_pembelian]))
            ->assertOk()
            ->assertSee('PO-UJI-001')
            ->assertSee('Beras Premium Uji')
            ->assertSee('Sumber Penerimaan');

        $response = $this->actingAs($this->adminGudang)->post(route('gudang.penerimaan.store'), [
            'sumber_penerimaan' => 'pembelian',
            'id_pembelian' => $pembelian->id_pembelian,
            'kode_penerimaan' => 'PNR-UJI-001',
            'tanggal_terima' => '2026-11-10',
            'id_gudang' => $this->idGudang,
            'id_pihak' => $this->idPihak,
            'items' => [[
                'id_barang' => $this->barang->id_barang,
                'id_satuan_input' => $this->satuanKg->id,
                'qty' => 50,
                'qty_layak' => 50,
                'harga_satuan' => 10000,
                'faktor_konversi' => 1,
            ]],
        ]);

        $response->assertRedirect(route('gudang.penerimaan.index'));

        $penerimaan = PenerimaanBarang::where('kode_penerimaan', 'PNR-UJI-001')->firstOrFail();
        $this->assertEquals($pembelian->id_pembelian, $penerimaan->id_pembelian);

        $pembelian->refresh();
        $this->assertEquals('diterima', $pembelian->status);

        $totalJurnalSebelum = JurnalHeader::count();
        $diproses = app(GudangJurnalService::class)->prosesAntrean(50, $this->entitas->id_entitas);
        $this->assertEquals(1, $diproses);
        $this->assertEquals($totalJurnalSebelum, JurnalHeader::count());

        $antrean = AntreanJurnalGudang::where('ref_id', $penerimaan->id_penerimaan)->firstOrFail();
        $this->assertEquals('DIPOSTING', $antrean->status);
        $this->assertEquals($jurnalPembelian->id_jurnal, $antrean->id_jurnal);
        $this->assertEquals('JRN-2026-11-0101', $antrean->no_jurnal);

        $kartu = KartuStok::where('ref_tipe', 'PENERIMAAN')->where('ref_id', $penerimaan->id_penerimaan)->firstOrFail();
        $this->assertEquals($jurnalPembelian->id_jurnal, $kartu->id_jurnal);

        $this->actingAs($this->adminGudang)
            ->get(route('gudang.penerimaan.show', $penerimaan->id_penerimaan))
            ->assertOk()
            ->assertSee('PO-UJI-001')
            ->assertSee('DIPOSTING')
            ->assertSee('JRN-2026-11-0101');
    }

    public function test_penerimaan_saldo_awal_menautkan_jurnal_jop_dan_stok_bertambah(): void
    {
        $jurnalJop = JurnalHeader::create([
            'id_entitas' => $this->entitas->id_entitas,
            'no_jurnal' => 'JOP-2026-0001',
            'nomor_nota' => 'JOP-2026',
            'tanggal_jurnal' => '2026-01-01',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'kode_transaksi' => 'JOP',
            'jenis_jurnal' => 'SALDO_AWAL',
            'total_debet' => 250000,
            'total_kredit' => 250000,
            'status' => 'POSTED',
        ]);

        $response = $this->actingAs($this->adminGudang)->post(route('gudang.penerimaan.store'), [
            'sumber_penerimaan' => 'saldo_awal',
            'kode_penerimaan' => 'PNR-SAW-001',
            'tanggal_terima' => '2026-11-10',
            'id_gudang' => $this->idGudang,
            'id_pihak' => $this->idPihak,
            'items' => [[
                'id_barang' => $this->barang->id_barang,
                'id_satuan_input' => $this->satuanKg->id,
                'qty' => 25,
                'qty_layak' => 25,
                'harga_satuan' => 10000,
                'faktor_konversi' => 1,
            ]],
        ]);

        $response->assertRedirect(route('gudang.penerimaan.index'));

        $penerimaan = PenerimaanBarang::where('kode_penerimaan', 'PNR-SAW-001')->firstOrFail();
        $this->assertNull($penerimaan->id_pembelian);

        $totalJurnalSebelum = JurnalHeader::count();
        app(GudangJurnalService::class)->prosesAntrean(50, $this->entitas->id_entitas);
        $this->assertEquals($totalJurnalSebelum, JurnalHeader::count());

        $antrean = AntreanJurnalGudang::where('ref_id', $penerimaan->id_penerimaan)->firstOrFail();
        $this->assertEquals('DIPOSTING', $antrean->status);
        $this->assertEquals($jurnalJop->id_jurnal, $antrean->id_jurnal);
        $this->assertEquals('JOP-2026-0001', $antrean->no_jurnal);

        $this->actingAs($this->adminGudang)
            ->get(route('gudang.penerimaan.show', $penerimaan->id_penerimaan))
            ->assertOk()
            ->assertSee('Stok Awal / Saldo Awal (JOP)')
            ->assertSee('JOP-2026-0001');
    }

    public function test_penerimaan_tanpa_field_sumber_tetap_kompatibel_sebagai_non_pembelian(): void
    {
        $response = $this->actingAs($this->adminGudang)->post(route('gudang.penerimaan.store'), [
            'kode_penerimaan' => 'PNR-NON-001',
            'tanggal_terima' => '2026-11-10',
            'id_gudang' => $this->idGudang,
            'id_pihak' => $this->idPihak,
            'items' => [[
                'id_barang' => $this->barang->id_barang,
                'id_satuan_input' => $this->satuanKg->id,
                'qty' => 10,
                'qty_layak' => 10,
                'harga_satuan' => 12000,
                'faktor_konversi' => 1,
            ]],
        ]);

        $response->assertRedirect(route('gudang.penerimaan.index'));

        $penerimaan = PenerimaanBarang::where('kode_penerimaan', 'PNR-NON-001')->firstOrFail();
        $totalJurnalSebelum = JurnalHeader::count();

        app(GudangJurnalService::class)->prosesAntrean(50, $this->entitas->id_entitas);
        $this->assertEquals($totalJurnalSebelum + 1, JurnalHeader::count());

        $antrean = AntreanJurnalGudang::where('ref_id', $penerimaan->id_penerimaan)->firstOrFail();
        $this->assertEquals('DIPOSTING', $antrean->status);

        $details = JurnalDetail::where('id_jurnal', $antrean->id_jurnal)->orderBy('urutan')->get();
        $this->assertCount(2, $details);
        $this->assertEquals('1123', $details[0]->kode_anak);
        $this->assertEquals(120000, (float) $details[0]->debet);
        $this->assertEquals('711', $details[1]->kode_anak);
        $this->assertEquals(120000, (float) $details[1]->kredit);
    }

    public function test_penerimaan_menghitung_selisih_barang_tidak_layak_dan_menambah_stok_layak_saja(): void
    {
        $response = $this->actingAs($this->adminGudang)->post(route('gudang.penerimaan.store'), [
            'sumber_penerimaan' => 'non_pembelian',
            'kode_penerimaan' => 'PNR-SORTIR-001',
            'tanggal_terima' => '2026-11-10',
            'id_gudang' => $this->idGudang,
            'id_pihak' => $this->idPihak,
            'items' => [[
                'id_barang' => $this->barang->id_barang,
                'id_satuan_input' => $this->satuanKg->id,
                'qty' => '2000',
                'qty_layak' => '1995',
                'alasan_tidak_layak' => '5 kg rusak',
                'harga_satuan' => '12000',
                'faktor_konversi' => '1',
            ]],
        ]);

        $response->assertRedirect(route('gudang.penerimaan.index'));

        $penerimaan = PenerimaanBarang::where('kode_penerimaan', 'PNR-SORTIR-001')->firstOrFail();
        $detail = DB::table('penerimaan_barang_detail')
            ->where('id_penerimaan', $penerimaan->id_penerimaan)
            ->firstOrFail();

        $this->assertEquals(2000, (float) $detail->qty_dasar);
        $this->assertEquals(1995, (float) $detail->qty_layak);
        $this->assertEquals(5, (float) $detail->qty_tidak_layak);
        $this->assertEquals('5 kg rusak', $detail->alasan_tidak_layak);

        $stok = DB::table('stok')
            ->where('id_gudang', $this->idGudang)
            ->where('id_barang', $this->barang->id_barang)
            ->firstOrFail();
        $this->assertEquals(1995, (float) $stok->qty_on_hand);
    }
}
