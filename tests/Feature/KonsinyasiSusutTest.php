<?php

namespace Tests\Feature;

use App\Models\Konsinyasi\PengirimanKonsinyasi;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KonsinyasiSusutTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $penggunaPenerima;
    private Pengguna $penggunaLain;
    private int $idEntitasPemilik;
    private int $idEntitasPenerima;
    private int $idGudangPenerima;
    private int $idBarang;
    private int $idKiriman;
    private int $idStokKonsinyasi;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = strtoupper(bin2hex(random_bytes(4)));
        $this->idEntitasPemilik = $this->buatEntitas('A'.$suffix, 'Pemilik '.$suffix);
        $this->idEntitasPenerima = $this->buatEntitas('B'.$suffix, 'Penerima '.$suffix);
        $idEntitasLain = $this->buatEntitas('C'.$suffix, 'Lain '.$suffix);
        $this->penggunaPenerima = $this->buatPengguna($this->idEntitasPenerima, 'penerima-'.$suffix.'@example.test');
        $this->penggunaLain = $this->buatPengguna($idEntitasLain, 'lain-'.$suffix.'@example.test');

        $idGudangPemilik = $this->buatGudang($this->idEntitasPemilik, 'A'.$suffix);
        $this->idGudangPenerima = $this->buatGudang($this->idEntitasPenerima, 'B'.$suffix);
        $this->idBarang = $this->buatBarang($suffix);

        $this->idKiriman = (int) PengirimanKonsinyasi::query()->create([
            'kode_kiriman' => 'KON-'.$suffix,
            'jenis_pemilik' => 'entitas',
            'id_entitas_pemilik' => $this->idEntitasPemilik,
            'id_entitas_penerima' => $this->idEntitasPenerima,
            'id_gudang_asal' => $idGudangPemilik,
            'id_gudang_tujuan' => $this->idGudangPenerima,
            'tgl_kirim' => now()->subDay()->toDateString(),
            'model_imbalan' => 'selisih_harga',
            'persen_komisi' => 0,
            'penanggung_susut' => 'penerima',
            'total_nilai_titip' => 100,
            'total_hpp_pemilik' => 40,
            'status' => 'dikirim',
            'status_posting' => 'T',
        ])->id_kiriman;

        $this->idStokKonsinyasi = DB::table('stok_konsinyasi')->insertGetId([
            'id_kiriman' => $this->idKiriman,
            'id_entitas_pemilik' => $this->idEntitasPemilik,
            'id_entitas_penerima' => $this->idEntitasPenerima,
            'id_gudang_penerima' => $this->idGudangPenerima,
            'id_barang' => $this->idBarang,
            'qty_titip' => 10,
            'qty_terjual' => 0,
            'qty_retur' => 0,
            'qty_susut' => 0,
            'qty_sisa' => 10,
            'harga_titip_satuan' => 10,
            'harga_jual_satuan' => 12,
            'hpp_pemilik' => 4,
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_penerima_mencatat_susut_dan_saldo_titipan_berkurang(): void
    {
        $this->actingAs($this->penggunaPenerima)
            ->post(route('konsinyasi.susut.store'), [
                'id_stok_konsinyasi' => $this->idStokKonsinyasi,
                'tanggal' => now()->toDateString(),
                'jenis_kejadian' => 'rusak',
                'qty' => '2.5',
                'catatan' => 'Kemasan rusak saat penyimpanan.',
            ])
            ->assertRedirect(route('konsinyasi.susut.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stok_konsinyasi', [
            'id_stok_konsinyasi' => $this->idStokKonsinyasi,
            'qty_susut' => 2.5,
            'qty_sisa' => 7.5,
            'status' => 'aktif',
        ]);
        $this->assertDatabaseHas('susut_konsinyasi', [
            'id_stok_konsinyasi' => $this->idStokKonsinyasi,
            'id_entitas_pencatat' => $this->idEntitasPenerima,
            'jenis_kejadian' => 'rusak',
            'qty' => 2.5,
            'hpp_pemilik' => 4,
            'nilai_susut' => 10,
            'penanggung_susut' => 'penerima',
        ]);
        $this->assertDatabaseHas('kartu_konsinyasi', [
            'id_stok_konsinyasi' => $this->idStokKonsinyasi,
            'jenis_mutasi' => 'SUSUT',
            'ref_tipe' => 'SUSUT_KONSINYASI',
            'qty' => 2.5,
            'saldo_qty' => 7.5,
        ]);
    }

    public function test_susut_melebihi_sisa_ditolak_tanpa_mengubah_stok(): void
    {
        $this->actingAs($this->penggunaPenerima)
            ->from(route('konsinyasi.susut.create'))
            ->post(route('konsinyasi.susut.store'), [
                'id_stok_konsinyasi' => $this->idStokKonsinyasi,
                'tanggal' => now()->toDateString(),
                'jenis_kejadian' => 'hilang',
                'qty' => '11',
            ])
            ->assertRedirect(route('konsinyasi.susut.create'))
            ->assertSessionHasErrors('qty');

        $this->assertDatabaseHas('stok_konsinyasi', [
            'id_stok_konsinyasi' => $this->idStokKonsinyasi,
            'qty_susut' => 0,
            'qty_sisa' => 10,
        ]);
        $this->assertDatabaseMissing('susut_konsinyasi', [
            'id_stok_konsinyasi' => $this->idStokKonsinyasi,
        ]);
    }

    public function test_entitas_lain_tidak_dapat_mencatat_susut(): void
    {
        $this->actingAs($this->penggunaLain)
            ->post(route('konsinyasi.susut.store'), [
                'id_stok_konsinyasi' => $this->idStokKonsinyasi,
                'tanggal' => now()->toDateString(),
                'jenis_kejadian' => 'hilang',
                'qty' => '1',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('stok_konsinyasi', [
            'id_stok_konsinyasi' => $this->idStokKonsinyasi,
            'qty_sisa' => 10,
            'qty_susut' => 0,
        ]);
    }

    private function buatEntitas(string $kode, string $nama): int
    {
        $idWilayah = DB::table('wilayah')->insertGetId([
            'tingkat' => 'desa',
            'nama' => $nama,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('entitas')->insertGetId([
            'kode_entitas' => $kode,
            'nama_entitas' => $nama,
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
            'kode_gudang' => 'G'.$kode,
            'nama_gudang' => 'Gudang '.$kode,
            'is_active' => true,
        ]);
    }

    private function buatBarang(string $suffix): int
    {
        $idSatuan = DB::table('satuan')->insertGetId([
            'kode_satuan' => 'KG-'.$suffix,
            'is_active' => true,
        ]);
        $idKomoditas = DB::table('komoditas')->insertGetId([
            'kategori' => 'pangan',
            'nama' => 'Komoditas '.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $kodeAkun = 'T'.$suffix;
        DB::table('master_coa')->insert([
            'kode_anak' => $kodeAkun,
            'nama_rekening' => 'Akun '.$suffix,
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
            'nama_unit_usaha' => 'Unit '.$suffix,
            'kode_akun_persediaan' => $kodeAkun,
            'kode_akun_pendapatan_anggota' => $kodeAkun,
            'kode_akun_pendapatan_non_anggota' => $kodeAkun,
            'kode_akun_hpp' => $kodeAkun,
        ]);

        return DB::table('master_barang')->insertGetId([
            'kode_barang' => 'B'.$suffix,
            'id_komoditas' => $idKomoditas,
            'id_unit_usaha' => $idUnitUsaha,
            'nama_barang' => 'Barang '.$suffix,
            'id_satuan_dasar' => $idSatuan,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buatPengguna(int $idEntitas, string $email): Pengguna
    {
        return Pengguna::query()->create([
            'id_entitas' => $idEntitas,
            'nama' => 'Petugas '.$email,
            'email' => $email,
            'password' => 'password',
            'is_active' => true,
        ]);
    }
}
