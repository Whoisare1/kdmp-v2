<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom Sesi 4 ke sesi_survei
        Schema::table('sesi_survei', function (Blueprint $t) {
            $t->enum('status_sesi4', ['belum_diisi', 'sedang_diisi', 'selesai', 'terverifikasi'])
                ->default('belum_diisi')
                ->after('status_sesi3');
            $t->timestamp('selesai_sesi4_at')->nullable()->after('selesai_sesi3_at');
            $t->unsignedBigInteger('diselesaikan_sesi4_oleh')->nullable()->after('diselesaikan_sesi3_oleh');
        });

        // 2. Buat tabel pemenuhan_komoditas
        Schema::create('pemenuhan_komoditas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('id_sesi')->constrained('sesi_survei', 'id')->cascadeOnDelete();
            $t->foreignId('id_komoditas')->constrained('komoditas');
            $t->string('sumber_pemenuhan', 100)
                ->comment('Contoh: Pasar Tradisional, Toko, Warung, Minimarket, dll');
            $t->string('nama_tempat', 255)
                ->comment('Contoh: Pasar Dawe, Toko ABC');
            $t->decimal('harga', 14, 2);
            $t->string('satuan_harga', 50)->default('kg');
            $t->date('tanggal_survei');
            $t->text('keterangan')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemenuhan_komoditas');

        Schema::table('sesi_survei', function (Blueprint $t) {
            $t->dropConstrainedForeignId('diselesaikan_sesi4_oleh');
            $t->dropColumn(['status_sesi4', 'selesai_sesi4_at']);
        });
    }
};
