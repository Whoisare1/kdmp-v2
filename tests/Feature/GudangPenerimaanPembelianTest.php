<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GudangPenerimaanPembelianTest extends TestCase
{
    use DatabaseTransactions;

    public function test_grn_terkait_po_mencatat_stok_tanpa_membuat_antrean_jurnal(): void
    {
        $suffix = strtoupper(bin2hex(random_bytes(4)));
        $idWilayah = DB::table('wilayah')->insertGetId([
            'tingkat' => 'desa',
            'nama' => 'Wilayah GRN '.$suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $idEntitas = DB::table('entitas')->insertGetId([
            'kode_entitas' => 'GRN'.$suffix,
            'nama_entitas' => 'Entitas GRN '.$suffix,
            'jenis_entitas' => 'Koperasi Desa',
            'id_wilayah' => $idWilayah,
            'tahun_buku_awal' => now()->year,
            'is_active' => true,
            'status' => 'APPROVED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pengguna = Pengguna::query()->create([
            'id_entitas' => $idEntitas,
            'nama' => 'Petugas GRN '.$suffix,
            'email' => 'grn-'.$suffix.'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $idGudang = DB::table('gudang')->insertGetId([
            'id_entitas' => $idEntitas,
            'kode_gudang' => 'G'.$suffix,
            'nama_gudang' => 'Gudang GRN '.$suffix,
            'is_active' => true,
        ]);
        $idPihak = DB::table('master_pihak')->insertGetId([
            'id_entitas' => $idEntitas,
            'jenis_pihak' => 'supplier',
            'nama' => 'Supplier GRN '.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $idSatuan = DB::table('satuan')->insertGetId([
            'kode_satuan' => 'KG-'.$suffix,
            'is_active' => true,
        ]);
        $idKomoditas = DB::table('komoditas')->insertGetId([
            'kategori' => 'pangan',
            'nama' => 'Komoditas GRN '.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $kodeAkun = 'G'.$suffix;
        DB::table('master_coa')->insert([
            'kode_anak' => $kodeAkun,
            'nama_rekening' => 'Akun GRN '.$suffix,
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
            'nama_unit_usaha' => 'Unit GRN '.$suffix,
            'kode_akun_persediaan' => $kodeAkun,
            'kode_akun_pendapatan_anggota' => $kodeAkun,
            'kode_akun_pendapatan_non_anggota' => $kodeAkun,
            'kode_akun_hpp' => $kodeAkun,
        ]);
        $idBarang = DB::table('master_barang')->insertGetId([
            'kode_barang' => 'B'.$suffix,
            'id_komoditas' => $idKomoditas,
            'id_unit_usaha' => $idUnitUsaha,
            'nama_barang' => 'Barang GRN '.$suffix,
            'id_satuan_dasar' => $idSatuan,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $idPembelian = DB::table('pembelian')->insertGetId([
            'id_entitas' => $idEntitas,
            'kode_pembelian' => 'PO-'.$suffix,
            'id_pihak' => $idPihak,
            'id_unit_usaha' => $idUnitUsaha,
            'id_gudang' => $idGudang,
            'tanggal_transaksi' => now()->toDateString(),
            'jenis_pembayaran' => 'kredit',
            'tgl_jatuh_tempo' => now()->addMonth()->toDateString(),
            'total_pembelian' => 50,
            'status' => 'disetujui',
            'status_posting' => 'F',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('detail_pembelian')->insert([
            'id_pembelian' => $idPembelian,
            'id_barang' => $idBarang,
            'id_satuan_input' => $idSatuan,
            'qty_input' => 10,
            'faktor_konversi' => 1,
            'qty_dasar' => 10,
            'harga_satuan_input' => 5,
            'subtotal' => 50,
        ]);

        $response = $this->actingAs($pengguna)->post(route('gudang.penerimaan.store'), [
            'id_pembelian' => $idPembelian,
            'id_gudang' => $idGudang,
            'id_pihak' => $idPihak,
            'kode_penerimaan' => 'GRN-'.$suffix,
            'tanggal_terima' => now()->toDateString(),
            'items' => [[
                'id_barang' => $idBarang,
                'id_satuan_input' => $idSatuan,
                'qty' => '10',
                'qty_layak' => '10',
                'harga_satuan' => '5',
                'faktor_konversi' => '1',
            ]],
        ]);

        $response->assertRedirect(route('gudang.penerimaan.index'));
        $response->assertSessionHas('success', 'Penerimaan terkait pembelian berhasil dicatat dan informasinya dikirim ke modul Pembelian.');

        $this->assertDatabaseHas('penerimaan_barang', [
            'id_entitas' => $idEntitas,
            'id_pembelian' => $idPembelian,
            'kode_penerimaan' => 'GRN-'.$suffix,
            'status' => 'diposting',
        ]);
        $this->assertDatabaseHas('stok', [
            'id_gudang' => $idGudang,
            'id_barang' => $idBarang,
            'qty_on_hand' => 10,
        ]);

        if (Schema::hasTable('antrean_jurnal_gudang')) {
            $this->assertDatabaseMissing('antrean_jurnal_gudang', [
                'id_entitas' => $idEntitas,
                'ref_tipe' => 'PENERIMAAN',
                'ref_id' => DB::table('penerimaan_barang')
                    ->where('kode_penerimaan', 'GRN-'.$suffix)
                    ->value('id_penerimaan'),
            ]);
        }
    }
}
