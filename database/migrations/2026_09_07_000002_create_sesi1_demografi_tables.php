<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── NARASUMBER SESI 1 ────────────────────────────────────────────────
        // Narasumber yang diwawancarai petugas untuk mengisi data demografi desa.
        // Terikat ke sesi_survei (bukan tabel survei lama).

        Schema::create('narasumber_sesi', function (Blueprint $t) {
            $t->id('id_narasumber');
            $t->foreignId('id_sesi')->constrained('sesi_survei')->cascadeOnDelete();
            $t->string('nama_narasumber', 100);
            $t->enum('kategori', [
                'Kepala Desa',
                'Perangkat Desa',
                'Kepala Dusun',
                'Ketua RW',
                'Ketua RT',
                'Lainnya',
            ]);
            $t->string('nomor_kontak', 30)->nullable();
            $t->text('keterangan')->nullable();
            $t->enum('status', ['belum_diisi', 'draft', 'lengkap', 'terverifikasi'])
              ->default('belum_diisi');
            $t->timestamps();
        });

        // ─── DATA DEMOGRAFI PER NARASUMBER ────────────────────────────────────
        // Setiap narasumber mengisi jumlah KK dan data penduduk per kelompok umur × gender.
        // Rekap desa dihitung dari agregasi seluruh narasumber (bukan input langsung).

        Schema::create('demografi_narasumber', function (Blueprint $t) {
            $t->id('id_demografi_ns');
            $t->foreignId('id_narasumber')
              ->constrained('narasumber_sesi', 'id_narasumber')
              ->cascadeOnDelete();
            $t->foreignId('id_sesi')->constrained('sesi_survei')->cascadeOnDelete();
            $t->unsignedInteger('jumlah_kk')->default(0)
              ->comment('Jumlah kepala keluarga yang dilaporkan narasumber ini');
            $t->enum('kelompok_umur', ['Balita', 'Anak', 'Remaja', 'Dewasa', 'Lansia']);
            $t->unsignedInteger('jumlah_laki')->default(0);
            $t->unsignedInteger('jumlah_perempuan')->default(0);
            $t->enum('sumber_data', [
                'Wawancara Narasumber',
                'Data RT/RW',
                'Data Perangkat Desa',
                'Pendataan Lapangan',
                'Lainnya',
            ])->default('Wawancara Narasumber');
            $t->date('tanggal_data')->nullable();
            $t->text('keterangan_sumber')->nullable();
            $t->timestamps();

            // Satu baris per kombinasi narasumber × kelompok umur
            $t->unique(['id_narasumber', 'kelompok_umur'], 'demografi_ns_unik');
        });

        // ─── ALTER SESI_SURVEI — tambah kolom khusus Sesi 1 ──────────────────
        Schema::table('sesi_survei', function (Blueprint $t) {
            $t->enum('status_sesi1', ['belum_diisi', 'sedang_diisi', 'selesai', 'terverifikasi'])
              ->default('belum_diisi')
              ->after('status');
            $t->unsignedInteger('jumlah_kk_verifikasi')
              ->nullable()
              ->after('status_sesi1')
              ->comment('Jumlah KK yang sudah diverifikasi petugas');
            $t->timestamp('selesai_sesi1_at')
              ->nullable()
              ->after('jumlah_kk_verifikasi');
            $t->unsignedBigInteger('diselesaikan_sesi1_oleh')
              ->nullable()
              ->after('selesai_sesi1_at');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_survei', function (Blueprint $t) {
            $t->dropColumn([
                'status_sesi1',
                'jumlah_kk_verifikasi',
                'selesai_sesi1_at',
                'diselesaikan_sesi1_oleh',
            ]);
        });

        Schema::dropIfExists('demografi_narasumber');
        Schema::dropIfExists('narasumber_sesi');
    }
};
