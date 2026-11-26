<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Peran;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GudangPencocokanSurplusDefisitTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $penggunaDefisit;
    private string $namaKomoditas;
    private string $namaWilayahSurplus;
    private Pengguna $penggunaSurplus;
    private int $idHasilDefisit;
    private int $idHasilSurplus;
    private int $idKomoditas;
    private int $idBarang;
    private int $idEntitasDefisit;
    private int $idEntitasSurplus;
    private int $idGudangDefisit;
    private int $idGudangSurplus;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = strtoupper(bin2hex(random_bytes(4)));
        $tahun = (int) now()->year;
        $bulan = (int) now()->month;
        $this->namaKomoditas = 'Komoditas Dummy '.$suffix;
        $this->namaWilayahSurplus = 'Surplus '.$suffix;

        $idWilayahDefisit = $this->buatWilayah('Defisit '.$suffix);
        $idWilayahSurplus = $this->buatWilayah($this->namaWilayahSurplus);
        $this->idEntitasDefisit = $this->buatEntitas($idWilayahDefisit, 'D'.$suffix, 'Defisit');
        $this->idEntitasSurplus = $this->buatEntitas($idWilayahSurplus, 'S'.$suffix, 'Surplus');

        $this->penggunaDefisit = Pengguna::query()->create([
            'id_entitas' => $this->idEntitasDefisit,
            'nama' => 'Pengguna Dummy Defisit',
            'email' => 'defisit-'.$suffix.'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $this->penggunaSurplus = Pengguna::query()->create([
            'id_entitas' => $this->idEntitasSurplus,
            'nama' => 'Pengguna Dummy Surplus',
            'email' => 'surplus-'.$suffix.'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $peranGudang = Peran::firstOrCreate(['kode' => 'admin_gudang'], ['nama' => 'Admin Gudang']);
        $this->penggunaDefisit->peran()->attach($peranGudang->id_peran);
        $this->penggunaSurplus->peran()->attach($peranGudang->id_peran);

        $this->idKomoditas = DB::table('komoditas')->insertGetId([
            'kategori' => 'pangan',
            'nama' => $this->namaKomoditas,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $idSatuan = DB::table('satuan')->insertGetId([
            'kode_satuan' => 'KG-'.$suffix,
            'is_active' => true,
        ]);

        $kodeAkun = 'T'.$suffix;
        DB::table('master_coa')->insert([
            'kode_anak' => $kodeAkun,
            'nama_rekening' => 'Akun Dummy '.$suffix,
            'posisi_normal' => 'D',
            'is_transaction' => 'T',
            'kelompok' => 'Aktiva',
            'is_kontra' => false,
            'level' => 3,
            'urutan_laporan' => 1,
            'is_active' => true,
        ]);
        $idUnitUsaha = DB::table('master_unit_usaha')->insertGetId([
            'kode_unit_usaha' => 'U'.$suffix,
            'nama_unit_usaha' => 'Unit Dummy '.$suffix,
            'kode_akun_persediaan' => $kodeAkun,
            'kode_akun_pendapatan_anggota' => $kodeAkun,
            'kode_akun_pendapatan_non_anggota' => $kodeAkun,
            'kode_akun_hpp' => $kodeAkun,
        ]);
        $this->idBarang = DB::table('master_barang')->insertGetId([
            'kode_barang' => 'B'.$suffix,
            'id_komoditas' => $this->idKomoditas,
            'id_unit_usaha' => $idUnitUsaha,
            'nama_barang' => 'Barang Dummy '.$suffix,
            'id_satuan_dasar' => $idSatuan,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->idGudangDefisit = $this->buatGudang($this->idEntitasDefisit, 'D'.$suffix);
        $this->buatGudang($this->idEntitasDefisit, 'D2'.$suffix);
        $this->idGudangSurplus = $this->buatGudang($this->idEntitasSurplus, 'S'.$suffix);
        DB::table('stok')->insert([
            [
                'id_gudang' => $this->idGudangDefisit,
                'id_barang' => $this->idBarang,
                'qty_on_hand' => 20,
                'qty_reserved' => 0,
                'hpp_rata2' => 0,
                'nilai_persediaan' => 0,
                'updated_at' => now(),
            ],
            [
                'id_gudang' => $this->idGudangSurplus,
                'id_barang' => $this->idBarang,
                'qty_on_hand' => 100,
                'qty_reserved' => 10,
                'hpp_rata2' => 0,
                'nilai_persediaan' => 0,
                'updated_at' => now(),
            ],
        ]);
        DB::table('barang_per_koperasi')->insert([
            'id_entitas' => $this->idEntitasDefisit,
            'id_barang' => $this->idBarang,
            'stok_minimum' => 25,
            'is_dijual' => false,
        ]);
        DB::table('barang_per_koperasi')->insert([
            'id_entitas' => $this->idEntitasSurplus,
            'id_barang' => $this->idBarang,
            'is_dijual' => true,
        ]);

        foreach ([
            [$idWilayahDefisit, 'defisit', -100, 'idHasilDefisit'],
            [$idWilayahSurplus, 'surplus', 90, 'idHasilSurplus'],
        ] as [$idWilayah, $status, $selisih, $property]) {
            DB::table('kebutuhan_komoditas')->insert([
                'id_wilayah' => $idWilayah,
                'id_komoditas' => $this->idKomoditas,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'kelompok_umur' => 'dewasa',
                'jumlah_penduduk' => 1,
                'per_kapita_harian' => 0,
                'faktor_musiman' => 1,
                'kebutuhan_bulanan' => abs($selisih),
                'satuan' => 'KG-'.$suffix,
                'created_at' => now(),
            ]);
            $this->{$property} = DB::table('hasil_kalkulasi')->insertGetId([
                'id_wilayah' => $idWilayah,
                'id_komoditas' => $this->idKomoditas,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'total_kebutuhan' => abs($selisih),
                'total_ketersediaan' => 0,
                'selisih' => $selisih,
                'status_surplus_defisit' => $status,
                'persentase_kecukupan' => 0,
                'versi' => 1,
                'status' => 'disetujui',
                'created_at' => now(),
            ]);
        }
    }

    public function test_halaman_mencocokkan_neraca_dengan_stok_tersedia_kedua_entitas(): void
    {
        $response = $this->actingAs($this->penggunaDefisit)
            ->get(route('gudang.pencocokan-surplus-defisit.index', [
                'tahun' => now()->year,
                'bulan' => now()->month,
            ]));

        $response->assertOk();
        $response->assertSee($this->namaKomoditas);
        $response->assertSee('Kebutuhan yang belum terpenuhi stok gudang');
        $response->assertSee('80,00');
        $response->assertSee('90,00');
        $response->assertSee('Potensi jumlah cocok');
        $response->assertSee($this->namaWilayahSurplus);
    }

    public function test_kalkulasi_baru_berstatus_draft_tidak_menggantikan_hasil_disetujui(): void
    {
        DB::table('hasil_kalkulasi')->insert([
            'id_wilayah' => DB::table('hasil_kalkulasi')
                ->where('id_hasil', $this->idHasilDefisit)
                ->value('id_wilayah'),
            'id_komoditas' => $this->idKomoditas,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'total_kebutuhan' => 5,
            'total_ketersediaan' => 0,
            'selisih' => -5,
            'status_surplus_defisit' => 'defisit',
            'persentase_kecukupan' => 0,
            'versi' => 2,
            'status' => 'draft',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->penggunaDefisit)
            ->get(route('gudang.pencocokan-surplus-defisit.index', [
                'tahun' => now()->year,
                'bulan' => now()->month,
            ]));

        $response->assertOk();
        $response->assertSee('80,00');
        $response->assertSee('Potensi jumlah cocok');
        $response->assertSee($this->namaWilayahSurplus);
    }

    public function test_stok_batch_kedaluwarsa_tidak_dihitung_sebagai_stok_tersedia(): void
    {
        DB::table('barang_per_koperasi')
            ->where('id_entitas', $this->idEntitasSurplus)
            ->where('id_barang', $this->idBarang)
            ->update(['lacak_batch' => true]);

        DB::table('stok_batch')->insert([
            [
                'id_gudang' => $this->idGudangSurplus,
                'id_barang' => $this->idBarang,
                'kode_batch' => 'KEDALUWARSA-DUMMY',
                'tanggal_kedaluwarsa' => now()->subDay()->toDateString(),
                'qty_on_hand' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_gudang' => $this->idGudangSurplus,
                'id_barang' => $this->idBarang,
                'kode_batch' => 'LAYAK-DUMMY',
                'tanggal_kedaluwarsa' => now()->addMonth()->toDateString(),
                'qty_on_hand' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $tersedia = app(\App\Services\Gudang\StokPerencanaanService::class)
            ->tersedia(
                $this->idEntitasSurplus,
                $this->idKomoditas,
                (string) DB::table('kebutuhan_komoditas')->where('id_komoditas', $this->idKomoditas)->value('satuan'),
                hanyaDapatDijual: true,
            );

        // Batch layak 60 dikurangi stok yang sudah dipesan 10.
        $this->assertSame(50.0, $tersedia);
    }

    public function test_surplus_komoditas_dengan_nama_sama_tetapi_id_berbeda_tidak_dipasangkan(): void
    {
        $idKomoditasLain = DB::table('komoditas')->insertGetId([
            'kategori' => 'pangan',
            'nama' => $this->namaKomoditas,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $idWilayahSurplus = DB::table('hasil_kalkulasi')
            ->where('id_hasil', $this->idHasilSurplus)
            ->value('id_wilayah');

        DB::table('hasil_kalkulasi')->where('id_hasil', $this->idHasilSurplus)
            ->update(['id_komoditas' => $idKomoditasLain]);
        DB::table('kebutuhan_komoditas')
            ->where('id_wilayah', $idWilayahSurplus)
            ->where('id_komoditas', $this->idKomoditas)
            ->update(['id_komoditas' => $idKomoditasLain]);
        DB::table('master_barang')->where('id_barang', $this->idBarang)
            ->update(['id_komoditas' => $idKomoditasLain]);

        $response = $this->actingAs($this->penggunaDefisit)
            ->get(route('gudang.pencocokan-surplus-defisit.index', [
                'tahun' => now()->year,
                'bulan' => now()->month,
            ]));

        $response->assertOk();
        $response->assertSee($this->namaKomoditas);
        $response->assertDontSee('Potensi jumlah cocok');
        $response->assertDontSee($this->namaWilayahSurplus);
    }

    public function test_alur_lengkap_kirim_notifikasi_dan_respons_tidak_mengubah_stok(): void
    {
        $urlPencocokan = route('gudang.pencocokan-surplus-defisit.index', [
            'tahun' => now()->year,
            'bulan' => now()->month,
        ]);
        $stokSebelum = DB::table('stok')->select('id_gudang', 'id_barang', 'qty_on_hand', 'qty_reserved')
            ->orderBy('id_gudang')->get()->toArray();

        $kirim = $this->actingAs($this->penggunaDefisit)
            ->from($urlPencocokan)
            ->post(route('gudang.permintaan-antar-entitas.store'), [
                'id_hasil_pengaju' => $this->idHasilDefisit,
                'id_hasil_penerima' => $this->idHasilSurplus,
            ]);

        $kirim->assertRedirect($urlPencocokan);
        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_hasil_pengaju' => $this->idHasilDefisit,
            'id_hasil_penerima' => $this->idHasilSurplus,
            'jenis_permintaan' => 'permintaan_beli',
            'qty_rekomendasi' => 80,
            'status' => 'terkirim',
        ]);

        $halamanPenerima = $this->actingAs($this->penggunaSurplus)->get($urlPencocokan);
        $halamanPenerima->assertOk();
        $halamanPenerima->assertSee('1 menunggu tindak lanjut');
        $halamanPenerima->assertSee('Entitas Dummy Defisit');

        $idPermintaan = (int) DB::table('permintaan_antar_entitas_gudang')
            ->where('id_hasil_pengaju', $this->idHasilDefisit)
            ->where('id_hasil_penerima', $this->idHasilSurplus)
            ->value('id_permintaan_gudang');
        $respons = $this->actingAs($this->penggunaSurplus)
            ->from($urlPencocokan)
            ->patch(route('gudang.permintaan-antar-entitas.respond', $idPermintaan), [
                'status' => 'disetujui',
            ]);

        $respons->assertRedirect($urlPencocokan);
        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $idPermintaan,
            'status' => 'disetujui',
        ]);
        $stokSesudah = DB::table('stok')->select('id_gudang', 'id_barang', 'qty_on_hand', 'qty_reserved')
            ->orderBy('id_gudang')->get()->toArray();
        $this->assertEquals($stokSebelum, $stokSesudah);
    }

    public function test_peringatan_stok_minimum_merangkum_satu_barang_dari_semua_gudang(): void
    {
        $response = $this->actingAs($this->penggunaDefisit)
            ->get(route('gudang.stok.index', ['status' => 'menipis']));

        $response->assertOk();
        $response->assertSee('Semua gudang');
        $response->assertSee('Minimum entitas: 25');
        $this->assertSame(1, substr_count($response->getContent(), 'Semua gudang'));
    }

    public function test_pencocokan_dan_permintaan_mengonversi_stok_ke_satuan_perencanaan(): void
    {
        $satuanKarung = 'KARUNG-DUMMY';
        $idSatuanKarung = DB::table('satuan')->insertGetId([
            'kode_satuan' => $satuanKarung,
            'is_active' => true,
        ]);
        DB::table('konversi_satuan')->insert([
            'id_barang' => $this->idBarang,
            'id_satuan' => $idSatuanKarung,
            'faktor_ke_dasar' => 25,
            'is_default_beli' => false,
            'is_default_jual' => false,
        ]);

        DB::table('kebutuhan_komoditas')
            ->where('id_komoditas', $this->idKomoditas)
            ->update(['satuan' => $satuanKarung]);
        DB::table('hasil_kalkulasi')->where('id_hasil', $this->idHasilDefisit)->update(['selisih' => -4]);
        DB::table('hasil_kalkulasi')->where('id_hasil', $this->idHasilSurplus)->update(['selisih' => 3.6]);

        $urlPencocokan = route('gudang.pencocokan-surplus-defisit.index', [
            'tahun' => now()->year,
            'bulan' => now()->month,
        ]);
        $halaman = $this->actingAs($this->penggunaDefisit)->get($urlPencocokan);

        $halaman->assertOk();
        $halaman->assertSee('3,20 '.$satuanKarung);
        $halaman->assertSee('3,60 '.$satuanKarung);

        $kirim = $this->actingAs($this->penggunaDefisit)
            ->from($urlPencocokan)
            ->post(route('gudang.permintaan-antar-entitas.store'), [
                'id_hasil_pengaju' => $this->idHasilDefisit,
                'id_hasil_penerima' => $this->idHasilSurplus,
            ]);

        $kirim->assertRedirect($urlPencocokan);
        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_hasil_pengaju' => $this->idHasilDefisit,
            'id_hasil_penerima' => $this->idHasilSurplus,
            'qty_rekomendasi' => 3.2,
            'satuan' => $satuanKarung,
            'status' => 'terkirim',
        ]);
    }

    public function test_barang_keluar_tidak_dapat_mengambil_jatah_stok_yang_sudah_dipesan(): void
    {
        DB::table('stok')->where('id_gudang', $this->idGudangDefisit)->where('id_barang', $this->idBarang)
            ->update(['qty_reserved' => 15]);
        $this->actingAs($this->penggunaDefisit);
        app()->instance('entitas_aktif', $this->idEntitasDefisit);

        try {
            app(\App\Services\StokService::class)->keluar(
                entitasId: $this->idEntitasDefisit,
                gudangId: $this->idGudangDefisit,
                barangId: $this->idBarang,
                qty: '6',
                refTipe: 'KERUSAKAN',
                refId: 900001,
            );
            $this->fail('Mutasi keluar seharusnya menolak jumlah yang melebihi stok tersedia.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Stok tidak mencukupi', $exception->getMessage());
        }

        $this->assertDatabaseHas('stok', [
            'id_gudang' => $this->idGudangDefisit,
            'id_barang' => $this->idBarang,
            'qty_on_hand' => 20,
            'qty_reserved' => 15,
        ]);
        $this->assertDatabaseMissing('kartu_stok', [
            'id_gudang' => $this->idGudangDefisit,
            'id_barang' => $this->idBarang,
            'ref_tipe' => 'KERUSAKAN',
            'ref_id' => 900001,
        ]);
    }

    public function test_opname_menolak_qty_fisik_di_bawah_stok_yang_dipesan(): void
    {
        DB::table('stok')->where('id_gudang', $this->idGudangDefisit)->where('id_barang', $this->idBarang)
            ->update(['qty_reserved' => 15]);
        $this->actingAs($this->penggunaDefisit);
        app()->instance('entitas_aktif', $this->idEntitasDefisit);

        try {
            app(\App\Services\StokService::class)->sesuaikanOpname(
                entitasId: $this->idEntitasDefisit,
                gudangId: $this->idGudangDefisit,
                barangId: $this->idBarang,
                qtyFisik: '10',
                opnameId: 900002,
            );
            $this->fail('Opname seharusnya menolak saldo fisik di bawah jumlah yang dipesan.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('lebih kecil dari stok yang sudah dipesan', $exception->getMessage());
        }

        $this->assertDatabaseHas('stok', [
            'id_gudang' => $this->idGudangDefisit,
            'id_barang' => $this->idBarang,
            'qty_on_hand' => 20,
            'qty_reserved' => 15,
        ]);
        $this->assertDatabaseMissing('kartu_stok', [
            'id_gudang' => $this->idGudangDefisit,
            'id_barang' => $this->idBarang,
            'ref_tipe' => 'OPNAME',
            'ref_id' => 900002,
        ]);
    }

    public function test_penerimaan_memperbarui_saldo_hpp_rata_rata_dan_kartu_stok(): void
    {
        DB::table('stok')->where('id_gudang', $this->idGudangDefisit)->where('id_barang', $this->idBarang)
            ->update([
                'hpp_rata2' => 2,
                'nilai_persediaan' => 40,
            ]);
        $this->actingAs($this->penggunaDefisit);
        app()->instance('entitas_aktif', $this->idEntitasDefisit);

        $kartu = app(\App\Services\StokService::class)->masuk(
            entitasId: $this->idEntitasDefisit,
            gudangId: $this->idGudangDefisit,
            barangId: $this->idBarang,
            qty: '10',
            hargaSatuan: '4',
            refTipe: 'PENERIMAAN',
            refId: 900003,
        );

        $this->assertDatabaseHas('stok', [
            'id_gudang' => $this->idGudangDefisit,
            'id_barang' => $this->idBarang,
            'qty_on_hand' => 30,
            'qty_reserved' => 0,
            'hpp_rata2' => 2.6666,
            'nilai_persediaan' => 80,
        ]);
        $this->assertDatabaseHas('kartu_stok', [
            'id_kartu' => $kartu->id_kartu,
            'ref_tipe' => 'PENERIMAAN',
            'ref_id' => 900003,
            'qty_masuk' => 10,
            'saldo_qty' => 30,
            'saldo_nilai' => 80,
            'hpp_rata2_setelah' => 2.6666,
        ]);
    }

    private function buatWilayah(string $nama): int
    {
        return DB::table('wilayah')->insertGetId([
            'tingkat' => 'desa',
            'nama' => $nama,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buatEntitas(int $idWilayah, string $kode, string $nama): int
    {
        return DB::table('entitas')->insertGetId([
            'kode_entitas' => $kode,
            'nama_entitas' => 'Entitas Dummy '.$nama,
            'jenis_entitas' => 'Koperasi Desa',
            'id_wilayah' => $idWilayah,
            'tahun_buku_awal' => now()->year,
            'is_active' => true,
            'status' => 'APPROVED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buatGudang(int $idEntitas, string $kode): int
    {
        return DB::table('gudang')->insertGetId([
            'id_entitas' => $idEntitas,
            'kode_gudang' => $kode,
            'nama_gudang' => 'Gudang Dummy '.$kode,
            'is_active' => true,
        ]);
    }
}
