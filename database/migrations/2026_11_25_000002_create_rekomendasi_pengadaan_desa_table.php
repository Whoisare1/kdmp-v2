<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekomendasi_pengadaan_desa', function (Blueprint $table) {
            $table->id('id_rekomendasi');
            $table->foreignId('id_hasil_defisit')->constrained('hasil_kalkulasi', 'id_hasil');
            $table->foreignId('id_hasil_surplus')->constrained('hasil_kalkulasi', 'id_hasil');
            $table->foreignId('id_pengaju')->constrained('pengguna');
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('pengguna');
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak'])->default('diajukan');
            $table->text('catatan_penyetuju')->nullable();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->timestamps();

            $table->index(['id_hasil_defisit', 'status'], 'rekomendasi_defisit_status_idx');
            $table->index(['status', 'created_at'], 'rekomendasi_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_pengadaan_desa');
    }
};
