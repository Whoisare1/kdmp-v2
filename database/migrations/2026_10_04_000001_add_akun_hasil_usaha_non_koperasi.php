<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah akun ekuitas hasil usaha untuk entitas non-koperasi
 * (BUMDes, Kelompok Tani/Gapoktan, UMKM/Perorangan).
 * Dipakai oleh ProfilPenutupan saat Tutup Tahun.
 */
return new class extends Migration
{
    private array $akun = [
        ['344', 'Laba Ditahan'],
        ['345', 'Saldo Kas Kelompok'],
        ['346', 'Modal Pemilik'],
    ];

    public function up(): void
    {
        // DB baru: master_coa kosong, akun ini disediakan CoaSeeder. Lewati agar FK tidak gagal.
        if (!DB::table('master_coa')->where('kode_anak', '34')->exists()) {
            return;
        }

        // Sisipkan urutan laporan tepat setelah akun 342 agar tampil berdekatan.
        $urutan = (int) (DB::table('master_coa')->where('kode_anak', '342')->value('urutan_laporan') ?? 0);

        foreach ($this->akun as $i => [$kode, $nama]) {
            DB::table('master_coa')->updateOrInsert(
                ['kode_anak' => $kode],
                [
                    'kode_induk'     => '34',
                    'nama_rekening'  => $nama,
                    'posisi_normal'  => 'K',
                    'is_transaction' => 'T',
                    'kelompok'       => 'Modal',
                    'is_kontra'      => 0,
                    'level'          => 3,
                    'urutan_laporan' => $urutan + $i + 1,
                    'is_active'      => true,
                ]
            );
        }
    }

    public function down(): void
    {
        $kode = array_column($this->akun, 0);

        // Jangan hapus akun yang sudah dipakai di config_shu / jurnal.
        foreach ($kode as $k) {
            $dipakai = DB::table('config_shu')->where('kode_akun', $k)->exists()
                || DB::table('jurnal_detail')->where('kode_anak', $k)->exists();

            if (!$dipakai) {
                DB::table('master_coa')->where('kode_anak', $k)->delete();
            }
        }
    }
};
