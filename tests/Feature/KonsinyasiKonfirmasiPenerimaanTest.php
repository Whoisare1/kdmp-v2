<?php

namespace Tests\Feature;

use App\Models\Konsinyasi\PengirimanKonsinyasi;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KonsinyasiKonfirmasiPenerimaanTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $penggunaPenerima;
    private Pengguna $penggunaLain;
    private int $idEntitasPemilik;
    private int $idEntitasPenerima;
    private int $idKiriman;

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
        $idGudangPenerima = $this->buatGudang($this->idEntitasPenerima, 'B'.$suffix);

        $this->idKiriman = (int) PengirimanKonsinyasi::query()->create([
            'kode_kiriman' => 'KON-'.$suffix,
            'jenis_pemilik' => 'entitas',
            'id_entitas_pemilik' => $this->idEntitasPemilik,
            'id_entitas_penerima' => $this->idEntitasPenerima,
            'id_gudang_asal' => $idGudangPemilik,
            'id_gudang_tujuan' => $idGudangPenerima,
            'tgl_kirim' => now()->subDay()->toDateString(),
            'model_imbalan' => 'selisih_harga',
            'persen_komisi' => 0,
            'penanggung_susut' => 'pemilik',
            'total_nilai_titip' => 100,
            'total_hpp_pemilik' => 80,
            'status' => 'dikirim',
            'status_posting' => 'T',
        ])->id_kiriman;
    }

    public function test_entitas_penerima_mengonfirmasi_barang_dan_tanggal_terima(): void
    {
        $this->actingAs($this->penggunaPenerima)
            ->patch(route('konsinyasi.pengiriman.terima', $this->idKiriman), [
                'catatan_penerimaan' => 'Barang diterima lengkap.',
            ])
            ->assertRedirect(route('konsinyasi.pengiriman.show', $this->idKiriman))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengiriman_konsinyasi', [
            'id_kiriman' => $this->idKiriman,
            'status' => 'diterima',
            'tgl_terima' => now()->toDateString(),
            'catatan_penerimaan' => 'Barang diterima lengkap.',
        ]);
    }

    public function test_entitas_yang_tidak_terlibat_tidak_dapat_mengonfirmasi(): void
    {
        $this->actingAs($this->penggunaLain)
            ->patch(route('konsinyasi.pengiriman.terima', $this->idKiriman))
            ->assertForbidden();

        $this->assertDatabaseHas('pengiriman_konsinyasi', [
            'id_kiriman' => $this->idKiriman,
            'status' => 'dikirim',
            'tgl_terima' => null,
        ]);
    }

    public function test_filter_perhatian_hanya_menampilkan_kiriman_dengan_sisa_stok(): void
    {
        $this->actingAs($this->penggunaPenerima)
            ->get(route('konsinyasi.pengiriman.index', ['perhatian' => 'perlu']))
            ->assertOk()
            ->assertDontSee('KON-');
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
