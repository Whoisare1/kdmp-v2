<?php

namespace Tests\Unit;

use App\Models\Akuntansi\JurnalDetail;
use App\Models\Akuntansi\JurnalHeader;
use App\Models\Akuntansi\MasterDetailTransaksi;
use App\Models\Akuntansi\MasterTransaksi;
use App\Models\Gudang\AntreanJurnalGudang;
use App\Models\Gudang\KartuStok;
use App\Models\Master\Barang;
use App\Models\Master\Entitas;
use App\Models\Master\Gudang;
use App\Models\Master\UnitUsaha;
use App\Services\Finance\GudangJurnalService;
use App\Services\Finance\JurnalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GudangJurnalIntegrationTest extends TestCase
{
    protected GudangJurnalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        // Skema tabel minimal untuk pengujian unit
        Schema::create('entitas', function (Blueprint $t) {
            $t->increments('id_entitas');
            $t->string('kode_entitas')->nullable();
            $t->string('nama_entitas')->nullable();
            $t->timestamps();
        });

        Schema::create('master_unit_usaha', function (Blueprint $t) {
            $t->increments('id_unit_usaha');
            $t->string('kode_unit_usaha');
            $t->string('nama_unit_usaha');
            $t->string('kode_akun_persediaan');
            $t->string('kode_akun_hpp')->nullable();
            $t->timestamps();
        });

        Schema::create('master_transaksi', function (Blueprint $t) {
            $t->string('kode_transaksi')->primary();
            $t->string('nama_transaksi');
            $t->string('modul');
            $t->boolean('is_active')->default(true);
        });

        Schema::create('master_detail_transaksi', function (Blueprint $t) {
            $t->increments('id');
            $t->string('kode_transaksi');
            $t->integer('urutan');
            $t->string('kode_anak')->nullable();
            $t->string('akun_dinamis')->nullable();
            $t->char('posisi', 1);
            $t->string('sumber_variabel');
            $t->boolean('is_optional')->default(false);
        });

        Schema::create('jurnal_header', function (Blueprint $t) {
            $t->increments('id_jurnal');
            $t->unsignedBigInteger('id_entitas');
            $t->string('no_jurnal');
            $t->string('nomor_nota')->nullable();
            $t->date('tanggal_jurnal');
            $t->unsignedInteger('periode_tahun');
            $t->unsignedInteger('periode_bulan');
            $t->string('kode_transaksi')->nullable();
            $t->string('jenis_jurnal');
            $t->string('source_type')->nullable();
            $t->unsignedBigInteger('source_id')->nullable();
            $t->text('keterangan')->nullable();
            $t->decimal('total_debet', 18, 2)->default(0);
            $t->decimal('total_kredit', 18, 2)->default(0);
            $t->string('status')->default('POSTED');
            $t->unsignedBigInteger('id_jurnal_asal')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('posted_by')->nullable();
            $t->dateTime('posted_at')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('jurnal_detail', function (Blueprint $t) {
            $t->increments('id_detail');
            $t->unsignedBigInteger('id_jurnal');
            $t->unsignedInteger('urutan');
            $t->string('kode_anak');
            $t->decimal('debet', 18, 2)->default(0);
            $t->decimal('kredit', 18, 2)->default(0);
            $t->string('keterangan')->nullable();
            $t->unsignedBigInteger('id_pihak')->nullable();
            $t->timestamps();
        });

        Schema::create('penomoran_jurnal', function (Blueprint $t) {
            $t->unsignedBigInteger('id_entitas');
            $t->string('prefix');
            $t->unsignedBigInteger('nomor_terakhir')->default(0);
            $t->primary(['id_entitas', 'prefix']);
        });

        Schema::create('periode_akuntansi', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedBigInteger('id_entitas');
            $t->integer('tahun');
            $t->integer('bulan');
            $t->string('status')->default('OPEN');
        });

        Schema::create('buku_besar_periode', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedBigInteger('id_entitas');
            $t->unsignedInteger('periode_tahun');
            $t->unsignedInteger('periode_bulan');
            $t->string('kode_anak');
            $t->decimal('saldo_awal_debet', 18, 2)->default(0);
            $t->decimal('saldo_awal_kredit', 18, 2)->default(0);
            $t->decimal('mutasi_debet', 18, 2)->default(0);
            $t->decimal('mutasi_kredit', 18, 2)->default(0);
            $t->decimal('saldo_akhir_debet', 18, 2)->default(0);
            $t->decimal('saldo_akhir_kredit', 18, 2)->default(0);
            $t->dateTime('dihitung_pada')->nullable();
        });

        Schema::create('antrean_jurnal_gudang', function (Blueprint $t) {
            $t->increments('id_antrean');
            $t->unsignedBigInteger('id_entitas');
            $t->string('jenis_transaksi');
            $t->string('ref_tipe');
            $t->unsignedBigInteger('ref_id');
            $t->string('no_dokumen');
            $t->unsignedBigInteger('id_unit_usaha');
            $t->date('tanggal');
            $t->decimal('nilai', 18, 2);
            $t->json('payload')->nullable();
            $t->string('status')->default('MENUNGGU');
            $t->unsignedBigInteger('id_jurnal')->nullable();
            $t->string('no_jurnal')->nullable();
            $t->string('kode_error')->nullable();
            $t->text('pesan_error')->nullable();
            $t->unsignedSmallInteger('jumlah_percobaan')->default(0);
            $t->dateTime('diproses_pada')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('kartu_stok', function (Blueprint $t) {
            $t->increments('id_kartu');
            $t->unsignedBigInteger('id_entitas');
            $t->unsignedBigInteger('id_gudang');
            $t->unsignedBigInteger('id_barang');
            $t->date('tanggal');
            $t->string('jenis_mutasi');
            $t->string('ref_tipe');
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->decimal('qty_masuk', 18, 4)->default(0);
            $t->decimal('qty_keluar', 18, 4)->default(0);
            $t->decimal('harga_satuan', 18, 4)->default(0);
            $t->decimal('nilai_mutasi', 18, 2)->default(0);
            $t->decimal('saldo_qty', 18, 4)->default(0);
            $t->decimal('saldo_nilai', 18, 2)->default(0);
            $t->decimal('hpp_rata2_setelah', 18, 4)->default(0);
            $t->unsignedBigInteger('id_jurnal')->nullable();
            $t->timestamps();
        });

        // Seed template transaksi
        DB::table('master_transaksi')->insert([
            ['kode_transaksi' => 'KRG', 'nama_transaksi' => 'Kerusakan Persediaan', 'modul' => 'gudang', 'is_active' => true],
            ['kode_transaksi' => 'OPK', 'nama_transaksi' => 'Opname Selisih Kurang', 'modul' => 'gudang', 'is_active' => true],
            ['kode_transaksi' => 'OPL', 'nama_transaksi' => 'Opname Selisih Lebih', 'modul' => 'gudang', 'is_active' => true],
            ['kode_transaksi' => 'PNB', 'nama_transaksi' => 'Penerimaan Non Pembelian', 'modul' => 'gudang', 'is_active' => true],
        ]);

        DB::table('master_detail_transaksi')->insert([
            // KRG: Dr Biaya Kerusakan (641) / Cr Persediaan Unit
            ['kode_transaksi' => 'KRG', 'urutan' => 1, 'kode_anak' => '641', 'akun_dinamis' => null, 'posisi' => 'D', 'sumber_variabel' => 'nilai_kerugian', 'is_optional' => false],
            ['kode_transaksi' => 'KRG', 'urutan' => 2, 'kode_anak' => null, 'akun_dinamis' => 'PERSEDIAAN_UNIT', 'posisi' => 'K', 'sumber_variabel' => 'nilai_kerugian', 'is_optional' => false],

            // OPK: Dr Biaya Kerusakan (641) / Cr Persediaan Unit
            ['kode_transaksi' => 'OPK', 'urutan' => 1, 'kode_anak' => '641', 'akun_dinamis' => null, 'posisi' => 'D', 'sumber_variabel' => 'nilai_selisih', 'is_optional' => false],
            ['kode_transaksi' => 'OPK', 'urutan' => 2, 'kode_anak' => null, 'akun_dinamis' => 'PERSEDIAAN_UNIT', 'posisi' => 'K', 'sumber_variabel' => 'nilai_selisih', 'is_optional' => false],

            // OPL: Dr Persediaan Unit / Cr Pendapatan Selisih Opname (712)
            ['kode_transaksi' => 'OPL', 'urutan' => 1, 'kode_anak' => null, 'akun_dinamis' => 'PERSEDIAAN_UNIT', 'posisi' => 'D', 'sumber_variabel' => 'nilai_selisih', 'is_optional' => false],
            ['kode_transaksi' => 'OPL', 'urutan' => 2, 'kode_anak' => '712', 'akun_dinamis' => null, 'posisi' => 'K', 'sumber_variabel' => 'nilai_selisih', 'is_optional' => false],

            // PNB: Dr Persediaan Unit / Cr Pendapatan Lain-lain (711)
            ['kode_transaksi' => 'PNB', 'urutan' => 1, 'kode_anak' => null, 'akun_dinamis' => 'PERSEDIAAN_UNIT', 'posisi' => 'D', 'sumber_variabel' => 'nilai_penerimaan', 'is_optional' => false],
            ['kode_transaksi' => 'PNB', 'urutan' => 2, 'kode_anak' => '711', 'akun_dinamis' => null, 'posisi' => 'K', 'sumber_variabel' => 'nilai_penerimaan', 'is_optional' => false],
        ]);

        DB::table('master_unit_usaha')->insert([
            'id_unit_usaha' => 1,
            'kode_unit_usaha' => 'SMB',
            'nama_unit_usaha' => 'Sembako',
            'kode_akun_persediaan' => '1123',
            'kode_akun_hpp' => '511',
        ]);

        $this->service = app(GudangJurnalService::class);
    }

    public function test_memproses_antrean_kerusakan_barang_berhasil(): void
    {
        $kartu = KartuStok::create([
            'id_entitas' => 1,
            'id_gudang' => 1,
            'id_barang' => 10,
            'tanggal' => '2026-11-09',
            'jenis_mutasi' => 'OUT',
            'ref_tipe' => 'KERUSAKAN',
            'ref_id' => 99,
            'nilai_mutasi' => 60000,
        ]);

        $antrean = AntreanJurnalGudang::create([
            'id_entitas' => 1,
            'jenis_transaksi' => 'KERUSAKAN',
            'ref_tipe' => 'KERUSAKAN',
            'ref_id' => 99,
            'no_dokumen' => 'KRS-00099',
            'id_unit_usaha' => 1,
            'tanggal' => '2026-11-09',
            'nilai' => 60000,
            'payload' => [
                'items' => [
                    ['id_barang' => 10, 'qty' => 5, 'hpp_satuan' => 12000, 'nilai' => 60000, 'id_kartu' => $kartu->id_kartu],
                ],
            ],
            'status' => 'MENUNGGU',
        ]);

        $hasil = $this->service->prosesItem($antrean);

        $this->assertTrue($hasil);

        $antrean->refresh();
        $this->assertEquals('DIPOSTING', $antrean->status);
        $this->assertNotNull($antrean->id_jurnal);
        $this->assertNotNull($antrean->no_jurnal);

        // Verifikasi kartu stok tertaut
        $kartu->refresh();
        $this->assertEquals($antrean->id_jurnal, $kartu->id_jurnal);

        // Verifikasi detail jurnal: Dr 641 (60.000) / Cr 1123 (60.000)
        $detail = JurnalDetail::where('id_jurnal', $antrean->id_jurnal)->orderBy('urutan')->get();
        $this->assertCount(2, $detail);
        $this->assertEquals('641', $detail[0]->kode_anak);
        $this->assertEquals(60000, (float) $detail[0]->debet);
        $this->assertEquals('1123', $detail[1]->kode_anak);
        $this->assertEquals(60000, (float) $detail[1]->kredit);
    }

    public function test_memproses_antrean_opname_kurang_dan_lebih(): void
    {
        // 1. Opname Kurang
        $antreanKurang = AntreanJurnalGudang::create([
            'id_entitas' => 1,
            'jenis_transaksi' => 'OPNAME_KURANG',
            'ref_tipe' => 'OPNAME',
            'ref_id' => 101,
            'no_dokumen' => 'OPN-00101',
            'id_unit_usaha' => 1,
            'tanggal' => '2026-11-09',
            'nilai' => 45000,
            'payload' => ['items' => []],
            'status' => 'MENUNGGU',
        ]);

        $this->assertTrue($this->service->prosesItem($antreanKurang));
        $antreanKurang->refresh();
        $this->assertEquals('DIPOSTING', $antreanKurang->status);

        // 2. Opname Lebih
        $antreanLebih = AntreanJurnalGudang::create([
            'id_entitas' => 1,
            'jenis_transaksi' => 'OPNAME_LEBIH',
            'ref_tipe' => 'OPNAME',
            'ref_id' => 101,
            'no_dokumen' => 'OPN-00101',
            'id_unit_usaha' => 1,
            'tanggal' => '2026-11-09',
            'nilai' => 30000,
            'payload' => ['items' => []],
            'status' => 'MENUNGGU',
        ]);

        $this->assertTrue($this->service->prosesItem($antreanLebih));
        $antreanLebih->refresh();
        $this->assertEquals('DIPOSTING', $antreanLebih->status);
    }

    public function test_memproses_antrean_penerimaan_terkait_pembelian_tidak_dobel_jurnal(): void
    {
        // Siapkan fake jurnal dari modul pembelian
        $jurnalBeli = JurnalHeader::create([
            'id_entitas' => 1,
            'no_jurnal' => 'JRN-2026-11-0001',
            'nomor_nota' => 'BELI-001',
            'tanggal_jurnal' => '2026-11-09',
            'periode_tahun' => 2026,
            'periode_bulan' => 11,
            'kode_transaksi' => 'BTU',
            'jenis_jurnal' => 'OTOMATIS',
            'total_debet' => 500000,
            'total_kredit' => 500000,
            'status' => 'POSTED',
        ]);

        Schema::create('pembelian', function (Blueprint $t) {
            $t->increments('id_pembelian');
            $t->unsignedBigInteger('id_entitas');
            $t->string('kode_pembelian');
            $t->unsignedBigInteger('id_jurnal')->nullable();
            $t->timestamps();
        });

        DB::table('pembelian')->insert([
            'id_pembelian' => 55,
            'id_entitas' => 1,
            'kode_pembelian' => 'PO-2026-0055',
            'id_jurnal' => $jurnalBeli->id_jurnal,
        ]);

        $kartu = KartuStok::create([
            'id_entitas' => 1,
            'id_gudang' => 1,
            'id_barang' => 10,
            'tanggal' => '2026-11-09',
            'jenis_mutasi' => 'IN',
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => 77,
            'nilai_mutasi' => 500000,
        ]);

        $antreanPenerimaan = AntreanJurnalGudang::create([
            'id_entitas' => 1,
            'jenis_transaksi' => 'PENERIMAAN',
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => 77,
            'no_dokumen' => 'PNR-00077',
            'id_unit_usaha' => 1,
            'tanggal' => '2026-11-09',
            'nilai' => 500000,
            'payload' => [
                'id_pembelian' => 55,
                'items' => [
                    ['id_barang' => 10, 'qty' => 50, 'hpp_satuan' => 10000, 'nilai' => 500000, 'id_kartu' => $kartu->id_kartu],
                ],
            ],
            'status' => 'MENUNGGU',
        ]);

        $totalJurnalSebelumnya = JurnalHeader::count();

        $this->assertTrue($this->service->prosesItem($antreanPenerimaan));

        $antreanPenerimaan->refresh();
        $this->assertEquals('DIPOSTING', $antreanPenerimaan->status);
        $this->assertEquals($jurnalBeli->id_jurnal, $antreanPenerimaan->id_jurnal);
        $this->assertEquals('JRN-2026-11-0001', $antreanPenerimaan->no_jurnal);

        // Pastikan TIDAK membuat jurnal baru (jumlah jurnal tidak bertambah)
        $this->assertEquals($totalJurnalSebelumnya, JurnalHeader::count());

        // Kartu stok tertaut ke id_jurnal pembelian
        $kartu->refresh();
        $this->assertEquals($jurnalBeli->id_jurnal, $kartu->id_jurnal);
    }

    public function test_antrean_penerimaan_pembelian_tanpa_jurnal_ditandai_gagal_bukan_pnb(): void
    {
        Schema::create('pembelian', function (Blueprint $t) {
            $t->increments('id_pembelian');
            $t->unsignedBigInteger('id_entitas');
            $t->string('kode_pembelian');
            $t->unsignedBigInteger('id_jurnal')->nullable();
            $t->timestamps();
        });

        DB::table('pembelian')->insert([
            'id_pembelian' => 56,
            'id_entitas' => 1,
            'kode_pembelian' => 'PO-2026-0056',
            'id_jurnal' => null,
        ]);

        $antrean = AntreanJurnalGudang::create([
            'id_entitas' => 1,
            'jenis_transaksi' => 'PENERIMAAN',
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => 78,
            'no_dokumen' => 'PNR-00078',
            'id_unit_usaha' => 1,
            'tanggal' => '2026-11-09',
            'nilai' => 250000,
            'payload' => [
                'sumber_penerimaan' => 'pembelian',
                'id_pembelian' => 56,
                'items' => [],
            ],
            'status' => 'MENUNGGU',
        ]);

        $this->assertFalse($this->service->prosesItem($antrean));

        $antrean->refresh();
        $this->assertEquals('GAGAL', $antrean->status);
        $this->assertEquals('JURNAL_BELI_BELUM_ADA', $antrean->kode_error);
        $this->assertNull($antrean->id_jurnal);
        $this->assertEquals(0, JurnalHeader::count());
    }

    public function test_antrean_penerimaan_saldo_awal_menautkan_jurnal_jop_tanpa_jurnal_baru(): void
    {
        $jurnalJop = JurnalHeader::create([
            'id_entitas' => 1,
            'no_jurnal' => 'JOP-2026-0001',
            'nomor_nota' => 'JOP-2026',
            'tanggal_jurnal' => '2026-01-01',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'kode_transaksi' => 'JOP',
            'jenis_jurnal' => 'SALDO_AWAL',
            'total_debet' => 300000,
            'total_kredit' => 300000,
            'status' => 'POSTED',
        ]);

        $kartu = KartuStok::create([
            'id_entitas' => 1,
            'id_gudang' => 1,
            'id_barang' => 11,
            'tanggal' => '2026-11-09',
            'jenis_mutasi' => 'IN',
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => 79,
            'nilai_mutasi' => 300000,
        ]);

        $antrean = AntreanJurnalGudang::create([
            'id_entitas' => 1,
            'jenis_transaksi' => 'PENERIMAAN',
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => 79,
            'no_dokumen' => 'PNR-00079',
            'id_unit_usaha' => 1,
            'tanggal' => '2026-11-09',
            'nilai' => 300000,
            'payload' => [
                'sumber_penerimaan' => 'saldo_awal',
                'items' => [
                    ['id_barang' => 11, 'qty' => 30, 'hpp_satuan' => 10000, 'nilai' => 300000, 'id_kartu' => $kartu->id_kartu],
                ],
            ],
            'status' => 'MENUNGGU',
        ]);

        $totalJurnalSebelumnya = JurnalHeader::count();

        $this->assertTrue($this->service->prosesItem($antrean));

        $antrean->refresh();
        $this->assertEquals('DIPOSTING', $antrean->status);
        $this->assertEquals($jurnalJop->id_jurnal, $antrean->id_jurnal);
        $this->assertEquals('JOP-2026-0001', $antrean->no_jurnal);
        $this->assertEquals($totalJurnalSebelumnya, JurnalHeader::count());

        $kartu->refresh();
        $this->assertEquals($jurnalJop->id_jurnal, $kartu->id_jurnal);
    }

    public function test_antrean_penerimaan_saldo_awal_tanpa_jop_ditandai_gagal(): void
    {
        $antrean = AntreanJurnalGudang::create([
            'id_entitas' => 1,
            'jenis_transaksi' => 'PENERIMAAN',
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => 80,
            'no_dokumen' => 'PNR-00080',
            'id_unit_usaha' => 1,
            'tanggal' => '2026-11-09',
            'nilai' => 150000,
            'payload' => [
                'sumber_penerimaan' => 'saldo_awal',
                'items' => [],
            ],
            'status' => 'MENUNGGU',
        ]);

        $this->assertFalse($this->service->prosesItem($antrean));

        $antrean->refresh();
        $this->assertEquals('GAGAL', $antrean->status);
        $this->assertEquals('SALDO_AWAL_BELUM_ADA', $antrean->kode_error);
        $this->assertEquals(0, JurnalHeader::count());
    }

    public function test_antrean_penerimaan_non_pembelian_membuat_jurnal_pnb(): void
    {
        $kartu = KartuStok::create([
            'id_entitas' => 1,
            'id_gudang' => 1,
            'id_barang' => 12,
            'tanggal' => '2026-11-09',
            'jenis_mutasi' => 'IN',
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => 81,
            'nilai_mutasi' => 120000,
        ]);

        $antrean = AntreanJurnalGudang::create([
            'id_entitas' => 1,
            'jenis_transaksi' => 'PENERIMAAN',
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => 81,
            'no_dokumen' => 'PNR-00081',
            'id_unit_usaha' => 1,
            'tanggal' => '2026-11-09',
            'nilai' => 120000,
            'payload' => [
                'sumber_penerimaan' => 'non_pembelian',
                'items' => [
                    ['id_barang' => 12, 'qty' => 12, 'hpp_satuan' => 10000, 'nilai' => 120000, 'id_kartu' => $kartu->id_kartu],
                ],
            ],
            'status' => 'MENUNGGU',
        ]);

        $this->assertTrue($this->service->prosesItem($antrean));

        $antrean->refresh();
        $this->assertEquals('DIPOSTING', $antrean->status);
        $this->assertNotNull($antrean->id_jurnal);

        $detail = JurnalDetail::where('id_jurnal', $antrean->id_jurnal)->orderBy('urutan')->get();
        $this->assertCount(2, $detail);
        $this->assertEquals('1123', $detail[0]->kode_anak);
        $this->assertEquals(120000, (float) $detail[0]->debet);
        $this->assertEquals('711', $detail[1]->kode_anak);
        $this->assertEquals(120000, (float) $detail[1]->kredit);

        $kartu->refresh();
        $this->assertEquals($antrean->id_jurnal, $kartu->id_jurnal);
    }
}
