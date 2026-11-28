<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Pembelian\Pembelian;
use App\Services\Pembelian\PurchaseRecommendationProvider;
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
        $this->beriPeranGudang($this->penggunaDefisit);
        $this->beriPeranGudang($this->penggunaSurplus);

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
            'stok_maksimum' => 40,
            'is_dijual' => false,
        ]);
        DB::table('barang_per_koperasi')->insert([
            'id_entitas' => $this->idEntitasSurplus,
            'id_barang' => $this->idBarang,
            'is_dijual' => true,
            'harga_jual_standar' => 2,
        ]);
        DB::table('master_pihak')->insert([
            [
                'id_entitas' => $this->idEntitasDefisit,
                'jenis_pihak' => 'entitas_lain',
                'id_entitas_mitra' => $this->idEntitasSurplus,
                'nama' => 'Entitas Surplus Dummy',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_entitas' => $this->idEntitasSurplus,
                'jenis_pihak' => 'entitas_lain',
                'id_entitas_mitra' => $this->idEntitasDefisit,
                'nama' => 'Entitas Defisit Dummy',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
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

    public function test_halaman_gudang_hanya_menampilkan_permintaan_masuk_dan_keluar(): void
    {
        $response = $this->actingAs($this->penggunaDefisit)
            ->get(route('gudang.pencocokan-surplus-defisit.index', [
                'tahun' => now()->year,
                'bulan' => now()->month,
            ]));

        $response->assertOk();
        $response->assertSee('Permintaan Masuk');
        $response->assertSee('Permintaan Keluar');
        $response->assertDontSee('Neraca Kebutuhan');
        $response->assertDontSee('Hasil M2 surplus');
        $response->assertDontSee('Hasil M2 defisit');
    }

    public function test_permintaan_lama_tanpa_catatan_persetujuan_tidak_menampilkan_tombol_lempar(): void
    {
        $this->actingAs($this->penggunaDefisit)
            ->post(route('gudang.permintaan-antar-entitas.store'), [
                'id_hasil_pengaju' => $this->idHasilDefisit,
                'id_hasil_penerima' => $this->idHasilSurplus,
            ]);

        $idPermintaan = (int) DB::table('permintaan_antar_entitas_gudang')
            ->where('id_hasil_pengaju', $this->idHasilDefisit)
            ->where('id_hasil_penerima', $this->idHasilSurplus)
            ->value('id_permintaan_gudang');
        DB::table('permintaan_antar_entitas_gudang')
            ->where('id_permintaan_gudang', $idPermintaan)
            ->update(['status' => 'disetujui']);

        $response = $this->actingAs($this->penggunaDefisit)
            ->get(route('gudang.pencocokan-surplus-defisit.index'));

        $response->assertOk();
        $response->assertSee('Persetujuan lama belum tercatat lengkap');
        $response->assertSee('Persetujuan ulang kedua entitas diperlukan');
        $response->assertDontSee('Lempar ke Penjualan/Pembelian');
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

    public function test_persetujuan_kedua_entitas_menreservasi_stok_dan_lempar_hanya_mengaktifkan_rekomendasi(): void
    {
        $urlPencocokan = route('gudang.pencocokan-surplus-defisit.index', [
            'tahun' => now()->year,
            'bulan' => now()->month,
        ]);

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
        $halamanPenerima->assertSee('1 Perlu Ditanggapi');
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
            'status' => 'terkirim',
        ]);
        $this->assertNotNull(DB::table('permintaan_antar_entitas_gudang')
            ->where('id_permintaan_gudang', $idPermintaan)
            ->value('tgl_persetujuan_penerima'));

        $this->actingAs($this->penggunaDefisit)
            ->from($urlPencocokan)
            ->patch(route('gudang.permintaan-antar-entitas.respond', $idPermintaan), [
                'status' => 'disetujui',
            ])
            ->assertRedirect($urlPencocokan);

        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $idPermintaan,
            'status' => 'disetujui',
            'qty_dialokasikan' => 80,
        ]);
        $this->assertDatabaseHas('stok', [
            'id_gudang' => $this->idGudangSurplus,
            'id_barang' => $this->idBarang,
            'qty_on_hand' => 100,
            'qty_reserved' => 90,
        ]);
        $this->assertDatabaseHas('rekomendasi_transaksi', [
            'id_pengajuan_gudang' => $idPermintaan,
            'jenis_rekomendasi' => 'penjualan',
            'status' => 'siap_dilempar',
            'id_gudang' => $this->idGudangSurplus,
        ]);
        $this->assertDatabaseHas('rekomendasi_transaksi', [
            'id_pengajuan_gudang' => $idPermintaan,
            'jenis_rekomendasi' => 'pembelian',
            'status' => 'siap_dilempar',
            'id_gudang' => $this->idGudangDefisit,
        ]);

        DB::table('permintaan_antar_entitas_gudang')
            ->where('id_permintaan_gudang', $idPermintaan)
            ->update(['status' => 'terkirim']);
        $halamanSiapDilempar = $this->actingAs($this->penggunaDefisit)->get($urlPencocokan);
        $halamanSiapDilempar->assertSee('Disetujui kedua entitas');
        $halamanSiapDilempar->assertSee('Lempar ke Penjualan/Pembelian');

        $lempar = $this->actingAs($this->penggunaDefisit)
            ->from($urlPencocokan)
            ->patch(route('gudang.permintaan-antar-entitas.dispatch', $idPermintaan));
        $lempar->assertRedirect($urlPencocokan)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $idPermintaan,
            'status' => 'dilempar',
        ]);
        $this->assertSame(2, DB::table('rekomendasi_transaksi')
            ->where('id_pengajuan_gudang', $idPermintaan)
            ->where('status', 'menunggu')
            ->count());
        $this->assertSame(90.0, (float) DB::table('stok')
            ->where('id_gudang', $this->idGudangSurplus)
            ->where('id_barang', $this->idBarang)
            ->value('qty_reserved'));
    }

    public function test_pasangan_desa_bisa_diajukan_kembali_setelah_permohonan_lama_ditolak(): void
    {
        $dataPengajuan = [
            'id_hasil_pengaju' => $this->idHasilDefisit,
            'id_hasil_penerima' => $this->idHasilSurplus,
        ];

        $this->actingAs($this->penggunaDefisit)
            ->post(route('gudang.permintaan-antar-entitas.store'), $dataPengajuan)
            ->assertRedirect();
        $idPermintaanLama = (int) DB::table('permintaan_antar_entitas_gudang')
            ->where('id_hasil_pengaju', $this->idHasilDefisit)
            ->where('id_hasil_penerima', $this->idHasilSurplus)
            ->value('id_permintaan_gudang');

        $this->actingAs($this->penggunaSurplus)
            ->patch(route('gudang.permintaan-antar-entitas.respond', $idPermintaanLama), [
                'status' => 'ditolak',
            ])
            ->assertRedirect();

        $this->actingAs($this->penggunaDefisit)
            ->post(route('gudang.permintaan-antar-entitas.store'), $dataPengajuan)
            ->assertRedirect();

        $this->assertSame(2, DB::table('permintaan_antar_entitas_gudang')
            ->where('id_hasil_pengaju', $this->idHasilDefisit)
            ->where('id_hasil_penerima', $this->idHasilSurplus)
            ->count());
    }

    public function test_reservasi_fifo_mengalokasikan_sisa_stok_ke_permohonan_berikutnya(): void
    {
        $suffix = strtoupper(bin2hex(random_bytes(4)));
        $idWilayahKedua = $this->buatWilayah('Defisit Kedua '.$suffix);
        $idEntitasKedua = $this->buatEntitas($idWilayahKedua, 'D2'.$suffix, 'Defisit Kedua');
        $penggunaKedua = Pengguna::query()->create([
            'id_entitas' => $idEntitasKedua,
            'nama' => 'Pengguna Dummy Defisit Kedua',
            'email' => 'defisit-kedua-'.$suffix.'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $this->beriPeranGudang($penggunaKedua);
        $this->buatGudang($idEntitasKedua, 'D2G'.$suffix);
        DB::table('barang_per_koperasi')->insert([
            'id_entitas' => $idEntitasKedua,
            'id_barang' => $this->idBarang,
            'is_dijual' => false,
        ]);
        DB::table('master_pihak')->insert([
            [
                'id_entitas' => $idEntitasKedua,
                'jenis_pihak' => 'entitas_lain',
                'id_entitas_mitra' => $this->idEntitasSurplus,
                'nama' => 'Entitas Surplus Dummy',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_entitas' => $this->idEntitasSurplus,
                'jenis_pihak' => 'entitas_lain',
                'id_entitas_mitra' => $idEntitasKedua,
                'nama' => 'Entitas Defisit Kedua Dummy',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('kebutuhan_komoditas')->insert([
            'id_wilayah' => $idWilayahKedua,
            'id_komoditas' => $this->idKomoditas,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'kelompok_umur' => 'dewasa',
            'jumlah_penduduk' => 1,
            'per_kapita_harian' => 0,
            'faktor_musiman' => 1,
            'kebutuhan_bulanan' => 50,
            'satuan' => DB::table('satuan')->where('id', DB::table('master_barang')
                ->where('id_barang', $this->idBarang)->value('id_satuan_dasar'))->value('kode_satuan'),
            'created_at' => now(),
        ]);
        $idHasilKedua = DB::table('hasil_kalkulasi')->insertGetId([
            'id_wilayah' => $idWilayahKedua,
            'id_komoditas' => $this->idKomoditas,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'total_kebutuhan' => 50,
            'total_ketersediaan' => 0,
            'selisih' => -50,
            'status_surplus_defisit' => 'defisit',
            'persentase_kecukupan' => 0,
            'versi' => 1,
            'status' => 'disetujui',
            'created_at' => now(),
        ]);

        $idPermintaanPertama = $this->buatPermintaanManual(
            $this->idEntitasDefisit,
            $this->idEntitasSurplus,
            $this->idHasilDefisit,
            $this->idHasilSurplus,
            60,
        );
        $idPermintaanKedua = $this->buatPermintaanManual(
            $idEntitasKedua,
            $this->idEntitasSurplus,
            $idHasilKedua,
            $this->idHasilSurplus,
            50,
        );

        $this->actingAs($this->penggunaDefisit)->patch(
            route('gudang.permintaan-antar-entitas.respond', $idPermintaanPertama),
            ['status' => 'disetujui'],
        )->assertRedirect();
        $this->actingAs($this->penggunaSurplus)->patch(
            route('gudang.permintaan-antar-entitas.respond', $idPermintaanPertama),
            ['status' => 'disetujui'],
        )->assertRedirect();
        $this->actingAs($penggunaKedua)->patch(
            route('gudang.permintaan-antar-entitas.respond', $idPermintaanKedua),
            ['status' => 'disetujui'],
        )->assertRedirect();
        $this->actingAs($this->penggunaSurplus)->patch(
            route('gudang.permintaan-antar-entitas.respond', $idPermintaanKedua),
            ['status' => 'disetujui'],
        )->assertRedirect();

        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $idPermintaanPertama,
            'qty_dialokasikan' => 60,
        ]);
        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $idPermintaanKedua,
            'qty_rekomendasi' => 50,
            'qty_dialokasikan' => 30,
        ]);
        $this->assertSame(100.0, (float) DB::table('stok')
            ->where('id_gudang', $this->idGudangSurplus)
            ->where('id_barang', $this->idBarang)
            ->value('qty_reserved'));
    }

    public function test_reservasi_kedaluwarsa_dilepas_dan_rekomendasi_ditandai_kedaluwarsa(): void
    {
        $idPermintaan = $this->buatPermintaanManual(
            $this->idEntitasDefisit,
            $this->idEntitasSurplus,
            $this->idHasilDefisit,
            $this->idHasilSurplus,
            5,
            'dilempar',
        );
        DB::table('stok')->where('id_gudang', $this->idGudangSurplus)
            ->where('id_barang', $this->idBarang)
            ->update(['qty_reserved' => 15]);

        $idPihakPembeli = (int) DB::table('master_pihak')
            ->where('id_entitas', $this->idEntitasDefisit)
            ->where('id_entitas_mitra', $this->idEntitasSurplus)
            ->value('id_pihak');
        $idPihakPenjual = (int) DB::table('master_pihak')
            ->where('id_entitas', $this->idEntitasSurplus)
            ->where('id_entitas_mitra', $this->idEntitasDefisit)
            ->value('id_pihak');
        $dataRekomendasi = [
            'id_pengajuan_gudang' => $idPermintaan,
            'id_entitas_asal' => $this->idEntitasSurplus,
            'id_entitas_tujuan' => $this->idEntitasDefisit,
            'id_barang' => $this->idBarang,
            'nama_barang' => 'Barang Dummy',
            'id_satuan' => DB::table('master_barang')->where('id_barang', $this->idBarang)->value('id_satuan_dasar'),
            'qty_disetujui' => 5,
            'harga_satuan_kesepakatan' => 2,
            'id_unit_usaha' => DB::table('master_barang')->where('id_barang', $this->idBarang)->value('id_unit_usaha'),
            'tgl_berlaku_sampai' => now()->subMinute(),
            'status' => 'menunggu',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('rekomendasi_transaksi')->insert([
            $dataRekomendasi + [
                'jenis_rekomendasi' => 'penjualan',
                'id_pihak_mitra' => $idPihakPenjual,
                'id_gudang' => $this->idGudangSurplus,
            ],
            $dataRekomendasi + [
                'jenis_rekomendasi' => 'pembelian',
                'id_pihak_mitra' => $idPihakPembeli,
                'id_gudang' => $this->idGudangDefisit,
            ],
        ]);

        $jumlahDilepas = app(\App\Services\Gudang\RekomendasiSurplusDefisitService::class)
            ->lepaskanReservasiKedaluwarsa();

        $this->assertSame(1, $jumlahDilepas);
        $this->assertDatabaseHas('stok', [
            'id_gudang' => $this->idGudangSurplus,
            'id_barang' => $this->idBarang,
            'qty_reserved' => 10,
        ]);
        $this->assertSame(2, DB::table('rekomendasi_transaksi')
            ->where('id_pengajuan_gudang', $idPermintaan)
            ->where('status', 'kadaluarsa')
            ->count());
        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $idPermintaan,
            'status' => 'kadaluarsa',
        ]);
    }

    public function test_peringatan_stok_minimum_merangkum_satu_barang_dari_semua_gudang(): void
    {
        $response = $this->actingAs($this->penggunaDefisit)
            ->get(route('gudang.stok.index', ['status' => 'menipis']));

        $response->assertOk();
        $response->assertSee('Semua gudang');
        $response->assertSee('Minimum entitas: 25');
        $response->assertSee('Saran menuju stok maksimum: 20');
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

    public function test_provider_pembelian_memetakan_rekomendasi_menunggu_ke_satuan_kg_dan_membatasi_entitas(): void
    {
        $idSatuanKg = $this->buatKonversiKg();
        $idRekomendasi = $this->buatRekomendasiPembelian();
        $this->buatRekomendasiPembelian('siap_dilempar');
        $this->buatRekomendasiPembelian('menunggu', now()->subMinute()->toDateTimeString());

        $provider = app(PurchaseRecommendationProvider::class);
        $recommendations = $provider->pendingForEntity($this->idEntitasDefisit);

        $this->assertCount(1, $recommendations);
        $recommendation = $recommendations->first();
        $this->assertSame((string) $idRekomendasi, $recommendation->id);
        $this->assertSame($this->idEntitasDefisit, $recommendation->idEntitasTujuan);
        $this->assertSame($idSatuanKg, $recommendation->idSatuan);
        $this->assertSame(40.0, $recommendation->quantity);
        $this->assertSame($this->idGudangDefisit, (int) $recommendation->gudang->id_gudang);
        $this->assertSame('desa_lain', $recommendation->supplierCandidates->first()->sourceType);
        $this->assertSame('KG', $recommendation->supplierCandidates->first()->unit);
        $this->assertSame(40.0, $recommendation->supplierCandidates->first()->availableQuantity);
        $this->assertSame(4.0, $recommendation->supplierCandidates->first()->standardSellingPrice);
        $this->assertSame(
            'Entitas Dummy Surplus',
            $recommendation->supplierCandidates->first()->originName,
        );
        $this->assertTrue($provider->pendingForEntity($this->idEntitasSurplus)->isEmpty());
    }

    public function test_provider_pembelian_menandai_rekomendasi_idempoten_setelah_po_tersimpan(): void
    {
        $this->buatKonversiKg();
        $idRekomendasi = $this->buatRekomendasiPembelian();
        $pembelian = new Pembelian();
        $pembelian->setAttribute('id_pembelian', 987654321);
        $pembelian->setAttribute('id_entitas', $this->idEntitasDefisit);

        $provider = app(PurchaseRecommendationProvider::class);
        $provider->markProcessed($this->idEntitasDefisit, (string) $idRekomendasi, $pembelian);
        $provider->markProcessed($this->idEntitasDefisit, (string) $idRekomendasi, $pembelian);

        $this->assertDatabaseHas('rekomendasi_transaksi', [
            'id_rekomendasi' => $idRekomendasi,
            'status' => 'diproses',
            'id_transaksi_terbentuk' => 987654321,
        ]);
    }

    public function test_entitas_dapat_menghubungkan_barang_master_yang_dipakai_mitra_aktif(): void
    {
        DB::table('barang_per_koperasi')
            ->where('id_entitas', $this->idEntitasDefisit)
            ->where('id_barang', $this->idBarang)
            ->delete();

        $this->actingAs($this->penggunaDefisit)
            ->get(route('master.barang.hubungkan.create'))
            ->assertOk()
            ->assertSee('Hubungkan Barang Mitra')
            ->assertSee('tanpa mengubah data master');

        $kodeBarang = (string) DB::table('master_barang')
            ->where('id_barang', $this->idBarang)
            ->value('kode_barang');
        $jumlahMasterSebelum = DB::table('master_barang')
            ->where('kode_barang', $kodeBarang)
            ->count();

        $this->actingAs($this->penggunaDefisit)
            ->post(route('master.barang.hubungkan.store'), ['kode_barang' => $kodeBarang])
            ->assertRedirect(route('master.barang.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barang_per_koperasi', [
            'id_entitas' => $this->idEntitasDefisit,
            'id_barang' => $this->idBarang,
            'harga_jual_standar' => 0,
            'is_dijual' => false,
        ]);
        $this->assertDatabaseHas('barang_per_koperasi', [
            'id_entitas' => $this->idEntitasSurplus,
            'id_barang' => $this->idBarang,
            'harga_jual_standar' => 2,
            'is_dijual' => true,
        ]);
        $this->assertSame(
            $jumlahMasterSebelum,
            DB::table('master_barang')->where('kode_barang', $kodeBarang)->count(),
        );
    }

    public function test_entitas_tidak_dapat_menghubungkan_kode_barang_yang_tidak_ditemukan(): void
    {
        $this->actingAs($this->penggunaDefisit)
            ->from(route('master.barang.hubungkan.create'))
            ->post(route('master.barang.hubungkan.store'), ['kode_barang' => 'KODE-TIDAK-ADA'])
            ->assertRedirect(route('master.barang.hubungkan.create'))
            ->assertSessionHasErrors('kode_barang');

        $this->assertDatabaseMissing('master_barang', ['kode_barang' => 'KODE-TIDAK-ADA']);
    }

    public function test_entitas_tidak_dapat_menghubungkan_barang_yang_tidak_digunakan_mitra_aktif(): void
    {
        $kodeBarang = 'LAIN-'.strtoupper(bin2hex(random_bytes(3)));
        $idBarangLain = DB::table('master_barang')->insertGetId([
            'kode_barang' => $kodeBarang,
            'id_komoditas' => $this->idKomoditas,
            'id_unit_usaha' => DB::table('master_barang')
                ->where('id_barang', $this->idBarang)
                ->value('id_unit_usaha'),
            'nama_barang' => 'Barang yang tidak digunakan mitra',
            'id_satuan_dasar' => DB::table('master_barang')
                ->where('id_barang', $this->idBarang)
                ->value('id_satuan_dasar'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->penggunaDefisit)
            ->from(route('master.barang.hubungkan.create'))
            ->post(route('master.barang.hubungkan.store'), ['kode_barang' => $kodeBarang])
            ->assertRedirect(route('master.barang.hubungkan.create'))
            ->assertSessionHasErrors('kode_barang');

        $this->assertDatabaseMissing('barang_per_koperasi', [
            'id_entitas' => $this->idEntitasDefisit,
            'id_barang' => $idBarangLain,
        ]);
    }

    public function test_persetujuan_satu_per_dua_berhasil_setelah_penerima_menghubungkan_kode_barang_penjual(): void
    {
        DB::table('barang_per_koperasi')
            ->where('id_entitas', $this->idEntitasDefisit)
            ->where('id_barang', $this->idBarang)
            ->delete();
        DB::table('barang_per_koperasi')
            ->where('id_entitas', $this->idEntitasSurplus)
            ->where('id_barang', $this->idBarang)
            ->update(['lacak_batch' => true]);
        DB::table('stok_batch')->insert([
            'id_gudang' => $this->idGudangSurplus,
            'id_barang' => $this->idBarang,
            'kode_batch' => 'BATCH-TELUR-DUMMY',
            'tanggal_kedaluwarsa' => now()->toDateString(),
            'qty_on_hand' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $barangPenjual = DB::table('master_barang')
            ->where('id_barang', $this->idBarang)
            ->first();
        $idBarangTerpisahPembeli = DB::table('master_barang')->insertGetId([
            'kode_barang' => $barangPenjual->kode_barang.'-DUP',
            'id_komoditas' => $this->idKomoditas,
            'id_unit_usaha' => $barangPenjual->id_unit_usaha,
            'nama_barang' => $barangPenjual->nama_barang,
            'id_satuan_dasar' => $barangPenjual->id_satuan_dasar,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('barang_per_koperasi')->insert([
            'id_entitas' => $this->idEntitasDefisit,
            'id_barang' => $idBarangTerpisahPembeli,
            'harga_jual_standar' => 22000,
            'is_dijual' => true,
        ]);

        $idPermintaan = $this->buatPermintaanManual(
            $this->idEntitasSurplus,
            $this->idEntitasDefisit,
            $this->idHasilSurplus,
            $this->idHasilDefisit,
            40,
        );
        DB::table('permintaan_antar_entitas_gudang')
            ->where('id_permintaan_gudang', $idPermintaan)
            ->update([
                'jenis_permintaan' => 'tawaran_jual',
                'id_persetujuan_pengaju' => $this->penggunaSurplus->id,
                'tgl_persetujuan_pengaju' => now(),
            ]);

        $urlPencocokan = route('gudang.pencocokan-surplus-defisit.index');

        $this->actingAs($this->penggunaDefisit)
            ->from($urlPencocokan)
            ->patch(route('gudang.permintaan-antar-entitas.respond', $idPermintaan), ['status' => 'disetujui'])
            ->assertRedirect($urlPencocokan)
            ->assertSessionHasErrors('status');

        $this->actingAs($this->penggunaDefisit)
            ->post(route('master.barang.hubungkan.store'), ['kode_barang' => $barangPenjual->kode_barang])
            ->assertRedirect(route('master.barang.index'))
            ->assertSessionHas('success');

        $this->actingAs($this->penggunaDefisit)
            ->from($urlPencocokan)
            ->patch(route('gudang.permintaan-antar-entitas.respond', $idPermintaan), ['status' => 'disetujui'])
            ->assertRedirect($urlPencocokan)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('permintaan_antar_entitas_gudang', [
            'id_permintaan_gudang' => $idPermintaan,
            'status' => 'disetujui',
            'qty_dialokasikan' => 40,
        ]);
        $this->assertDatabaseHas('stok', [
            'id_gudang' => $this->idGudangSurplus,
            'id_barang' => $this->idBarang,
            'qty_reserved' => 50,
        ]);
        $this->assertDatabaseHas('rekomendasi_transaksi', [
            'id_pengajuan_gudang' => $idPermintaan,
            'jenis_rekomendasi' => 'penjualan',
            'id_barang' => $this->idBarang,
            'qty_disetujui' => 40,
            'status' => 'siap_dilempar',
        ]);
        $this->assertDatabaseHas('rekomendasi_transaksi', [
            'id_pengajuan_gudang' => $idPermintaan,
            'jenis_rekomendasi' => 'pembelian',
            'id_barang' => $this->idBarang,
            'qty_disetujui' => 40,
            'status' => 'siap_dilempar',
        ]);
    }

    private function buatKonversiKg(): int
    {
        $idSatuanKg = DB::table('satuan')->where('kode_satuan', 'KG')->value('id');
        if (! $idSatuanKg) {
            $idSatuanKg = DB::table('satuan')->insertGetId([
                'kode_satuan' => 'KG',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('konversi_satuan')->insert([
            'id_barang' => $this->idBarang,
            'id_satuan' => $idSatuanKg,
            'faktor_ke_dasar' => 2,
            'is_default_beli' => true,
            'is_default_jual' => false,
        ]);

        return (int) $idSatuanKg;
    }

    private function buatRekomendasiPembelian(
        string $status = 'menunggu',
        ?string $berlakuSampai = null,
    ): int {
        $idPihak = DB::table('master_pihak')
            ->where('id_entitas', $this->idEntitasDefisit)
            ->where('id_entitas_mitra', $this->idEntitasSurplus)
            ->value('id_pihak');

        return DB::table('rekomendasi_transaksi')->insertGetId([
            'id_pengajuan_gudang' => random_int(100000, 999999),
            'jenis_rekomendasi' => 'pembelian',
            'id_entitas_asal' => $this->idEntitasSurplus,
            'id_entitas_tujuan' => $this->idEntitasDefisit,
            'id_pihak_mitra' => $idPihak,
            'id_barang' => $this->idBarang,
            'nama_barang' => 'Barang Dummy',
            'id_satuan' => DB::table('master_barang')->where('id_barang', $this->idBarang)->value('id_satuan_dasar'),
            'qty_disetujui' => 80,
            'harga_satuan_kesepakatan' => 2,
            'id_unit_usaha' => DB::table('master_barang')->where('id_barang', $this->idBarang)->value('id_unit_usaha'),
            'id_gudang' => $this->idGudangDefisit,
            'id_transaksi_terbentuk' => null,
            'tgl_berlaku_sampai' => $berlakuSampai ?? now()->addHours(24),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
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

    private function buatPermintaanManual(
        int $idEntitasPengaju,
        int $idEntitasPenerima,
        int $idHasilPengaju,
        int $idHasilPenerima,
        float $qty,
        string $status = 'terkirim',
    ): int {
        return DB::table('permintaan_antar_entitas_gudang')->insertGetId([
            'id_entitas_pengaju' => $idEntitasPengaju,
            'id_entitas_penerima' => $idEntitasPenerima,
            'id_hasil_pengaju' => $idHasilPengaju,
            'id_hasil_penerima' => $idHasilPenerima,
            'jenis_permintaan' => 'permintaan_beli',
            'nama_komoditas' => $this->namaKomoditas,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'qty_rekomendasi' => $qty,
            'satuan' => DB::table('kebutuhan_komoditas')
                ->where('id_wilayah', DB::table('hasil_kalkulasi')
                    ->where('id_hasil', $idHasilPengaju)
                    ->value('id_wilayah'))
                ->where('id_komoditas', $this->idKomoditas)
                ->value('satuan'),
            'status' => $status,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function beriPeranGudang(Pengguna $pengguna): void
    {
        $idPeran = DB::table('peran')->where('kode', 'admin_gudang')->value('id_peran');
        if ($idPeran === null) {
            $idPeran = DB::table('peran')->insertGetId([
                'kode' => 'admin_gudang',
                'nama' => 'Admin Gudang',
            ]);
        }

        DB::table('pengguna_peran')->insertOrIgnore([
            'id_pengguna' => $pengguna->id,
            'id_peran' => $idPeran,
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
