<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('standar_konsumsi');
        // ─── Tabel Standar Konsumsi ───────────────────────────────────────────
        Schema::create('standar_konsumsi', function (Blueprint $t) {
            $t->id('id_standar');
            $t->foreignId('id_sesi')->constrained('sesi_survei', 'id')->cascadeOnDelete();
            $t->foreignId('id_komoditas')->constrained('komoditas');
            $t->enum('kategori_gender', ['L', 'P']);
            $t->enum('kategori_umur', ['Balita', 'Anak', 'Remaja', 'Dewasa', 'Lansia']);
            $t->decimal('nilai_konsumsi', 12, 4);
            $t->string('satuan', 30)->default('kg/orang/bulan');
            $t->enum('periode', ['harian', 'mingguan', 'bulanan', 'tahunan'])->default('bulanan');
            $t->decimal('nilai_per_tahun_standar', 14, 4)->nullable()
              ->comment('Konversi ke tahunan untuk kalkulasi kebutuhan M2');
            $t->string('sumber_standar', 255)->nullable()
              ->comment('Sumber referensi standar konsumsi, misal: WHO 2021, Kemenkes 2019');
            $t->string('periode_standar', 20)->nullable()
              ->comment('Tahun/periode dari sumber standar, misal: 2024');
            $t->text('keterangan')->nullable();
            $t->timestamps();

            // Satu standar unik per sesi × komoditas × gender × umur
            $t->unique(['id_sesi', 'id_komoditas', 'kategori_gender', 'kategori_umur'], 'standar_unik_per_sesi');
        });

        // ─── Tambah kolom Sesi 3 ke sesi_survei ──────────────────────────────
        Schema::table('sesi_survei', function (Blueprint $t) {
            $t->enum('status_sesi3', ['belum_diisi', 'sedang_diisi', 'selesai', 'terverifikasi'])
              ->default('belum_diisi')
              ->after('status_sesi2');
            $t->timestamp('selesai_sesi3_at')->nullable()->after('status_sesi3');
            $t->unsignedBigInteger('diselesaikan_sesi3_oleh')->nullable()->after('selesai_sesi3_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standar_konsumsi');

        Schema::table('sesi_survei', function (Blueprint $t) {
            $t->dropColumn(['status_sesi3', 'selesai_sesi3_at', 'diselesaikan_sesi3_oleh']);
        });
    }
};
