<?php

namespace Tests\Feature;

use App\Http\Controllers\Master\EntitasController;
use App\Models\Master\Komoditas;
use App\Models\Master\Satuan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EntitasProvisioningTest extends TestCase
{
    use DatabaseTruncation;

    private Pengguna $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('master_coa')->insertOrIgnore([
            ['kode_anak' => '1111', 'nama_rekening' => 'Kas', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 4, 'is_kontra' => 0, 'urutan_laporan' => 1, 'is_active' => 1],
            ['kode_anak' => '11121', 'nama_rekening' => 'Bank A', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 5, 'is_kontra' => 0, 'urutan_laporan' => 2, 'is_active' => 1],
            ['kode_anak' => '1123', 'nama_rekening' => 'Persediaan Sembako', 'kelompok' => 'Aktiva', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 4, 'is_kontra' => 0, 'urutan_laporan' => 3, 'is_active' => 1],
            ['kode_anak' => '411', 'nama_rekening' => 'Penjualan Anggota', 'kelompok' => 'Pendapatan', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 10, 'is_active' => 1],
            ['kode_anak' => '412', 'nama_rekening' => 'Penjualan Non Anggota', 'kelompok' => 'Pendapatan', 'posisi_normal' => 'K', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 11, 'is_active' => 1],
            ['kode_anak' => '511', 'nama_rekening' => 'HPP Sembako', 'kelompok' => 'Biaya', 'posisi_normal' => 'D', 'is_transaction' => 'T', 'level' => 3, 'is_kontra' => 0, 'urutan_laporan' => 20, 'is_active' => 1],
        ]);

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

        foreach (['super_admin' => 'Super Administrator', 'manajer' => 'Manajer'] as $kode => $nama) {
            Peran::firstOrCreate(['kode' => $kode], ['nama' => $nama]);
        }

        $sfx = uniqid();
        $idAdmin = DB::table('pengguna')->insertGetId([
            'id_entitas' => null,
            'nama' => 'Super Admin Uji',
            'email' => 'sa-' . $sfx . '@test.local',
            'password' => Hash::make('password'),
            'is_active' => 1,
            'created_at' => now(),
        ]);
        $this->superAdmin = Pengguna::withoutGlobalScopes()->findOrFail($idAdmin);
    }

    public function test_approve_entitas_baru_mengisi_satuan_dan_komoditas_default_tanpa_barang(): void
    {
        $sfx = uniqid();
        $wilayah = Wilayah::create(['tingkat' => 'desa', 'nama' => 'Desa Baru ' . $sfx]);

        $entitas = Entitas::create([
            'kode_entitas' => 'NEW-' . $sfx,
            'nama_entitas' => 'Koperasi Desa Harapan',
            'jenis_entitas' => 'Koperasi Desa',
            'id_wilayah' => $wilayah->id,
            'tahun_buku_awal' => 2026,
            'status' => 'PENDING',
            'is_active' => false,
        ]);

        $manajer = Pengguna::create([
            'id_entitas' => $entitas->id_entitas,
            'nama' => 'Manajer Baru',
            'email' => 'manajer-baru-' . $sfx . '@test.local',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.approve', $entitas->id_entitas), ['action' => 'approve'])
            ->assertRedirect();

        $idEntitas = $entitas->id_entitas;

        // Data wajib operasional terbuat
        $this->assertDatabaseHas('gudang', ['id_entitas' => $idEntitas, 'kode_gudang' => 'UTAMA']);
        $this->assertDatabaseHas('master_kas_bank', ['id_entitas' => $idEntitas, 'kode_akun' => '1111']);
        $this->assertSame(13, DB::table('periode_akuntansi')->where('id_entitas', $idEntitas)->count());

        // Satuan & Komoditas dasar terbuat untuk entitas baru
        $this->assertSame(
            ['BOX', 'BUTIR', 'KARUNG', 'KG', 'LTR', 'PCS', 'UNIT'],
            Satuan::forEntitas($idEntitas)->orderBy('kode_satuan')->pluck('kode_satuan')->all()
        );
        $this->assertSame(
            ['Beras', 'Cabai Merah', 'Daging Sapi', 'Gula Pasir', 'Jagung', 'Minyak Goreng', 'Telur Ayam'],
            Komoditas::forEntitas($idEntitas)->orderBy('nama')->pluck('nama')->all()
        );

        // Master Barang tetap kosong ("tanpa barang")
        $this->assertSame(0, DB::table('master_barang')->where('id_entitas', $idEntitas)->count());

        // Manajer baru langsung melihat opsi Komoditas & Satuan saat membuka form Tambah Barang
        $manajer->refresh();
        $this->actingAs($manajer)
            ->get(route('master.barang.create'))
            ->assertOk()
            ->assertSee('Beras')
            ->assertSee('KG')
            ->assertSee('KARUNG')
            ->assertSee('UNIT');

        // Idempoten dan tidak menimpa kustomisasi pengguna saat dipanggil ulang
        DB::table('satuan')
            ->where('id_entitas', $idEntitas)
            ->where('kode_satuan', 'BUTIR')
            ->update(['is_active' => false]);

        EntitasController::provisionDefaultMasterData($entitas->fresh());

        $this->assertSame(7, Satuan::forEntitas($idEntitas)->count());
        $this->assertSame(7, Komoditas::forEntitas($idEntitas)->count());
        $this->assertFalse((bool) DB::table('satuan')
            ->where('id_entitas', $idEntitas)
            ->where('kode_satuan', 'BUTIR')
            ->value('is_active'));
    }

    public function test_tambah_entitas_approved_lewat_super_admin_mengisi_satuan_dan_komoditas_tanpa_barang(): void
    {
        $sfx = uniqid();
        $res = $this->actingAs($this->superAdmin)->post(route('master.entitas.store'), [
            'kode_entitas' => 'SA-' . $sfx,
            'nama_entitas' => 'BUMDes Mandiri Baru',
            'jenis_entitas' => 'BUMDes',
            'nama_wilayah_baru' => 'Desa Mandiri ' . $sfx,
            'tahun_buku_awal' => 2026,
            'status' => 'APPROVED',
            'is_active' => 1,
            'admin_nama' => 'Admin BUMDes Mandiri',
            'admin_email' => 'admin-bumdes-' . $sfx . '@test.local',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ]);

        $res->assertRedirect(route('master.entitas.index'));

        $entitas = Entitas::where('kode_entitas', 'SA-' . $sfx)->firstOrFail();

        $this->assertSame(7, Satuan::forEntitas($entitas->id_entitas)->count());
        $this->assertSame(7, Komoditas::forEntitas($entitas->id_entitas)->count());
        $this->assertSame(0, DB::table('master_barang')->where('id_entitas', $entitas->id_entitas)->count());
    }
}

