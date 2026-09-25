<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('wilayah');
            $t->enum('tingkat', ['prov', 'kab', 'kec', 'desa', 'dusun', 'rw', 'rt']);
            $t->string('nama');
            $t->string('kode_bps')->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->timestamps();
        });

        Schema::create('entitas', function (Blueprint $t) {
            $t->id('id_entitas');
            $t->string('kode_entitas', 20)->unique();
            $t->string('nama_entitas', 150);
            $t->enum('jenis_entitas', ['BUMDes', 'Koperasi Desa', 'Kelompok Tani', 'Gapoktan', 'Perorangan', 'Lainnya'])->default('Lainnya');
            $t->foreignId('id_wilayah')->unique()->constrained('wilayah');
            $t->string('badan_hukum_no', 50)->nullable();
            $t->date('tgl_berdiri')->nullable();
            $t->year('tahun_buku_awal');
            $t->boolean('is_active')->default(true);
            $t->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $t->timestamps();
        });

        // Bulan 13 = periode penyesuaian, dipakai untuk tutup buku Jan-Mar tahun N+1
        Schema::create('periode_akuntansi', function (Blueprint $t) {
            $t->id('id_periode');
            $t->foreignId('id_entitas')->constrained('entitas', 'id_entitas');
            $t->year('tahun');
            $t->unsignedTinyInteger('bulan');
            $t->enum('status', ['OPEN', 'CLOSED', 'LOCKED'])->default('OPEN');
            $t->dateTime('tgl_tutup')->nullable();
            $t->unsignedBigInteger('ditutup_oleh')->nullable();
            $t->unique(['id_entitas', 'tahun', 'bulan']);
        });

        Schema::create('konfigurasi', function (Blueprint $t) {
            $t->foreignId('id_entitas')->constrained('entitas', 'id_entitas');
            $t->string('kunci', 50);
            $t->string('nilai');
            $t->string('keterangan')->nullable();
            $t->primary(['id_entitas', 'kunci']);
        });

        Schema::create('pengguna', function (Blueprint $t) {
            $t->id();
            $t->foreignId('id_entitas')->nullable()->constrained('entitas', 'id_entitas')
              ->comment('NULL = pengguna tingkat pusat/pengawas');
            $t->string('nama');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->rememberToken();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('peran', function (Blueprint $t) {
            $t->id('id_peran');
            $t->string('kode', 30)->unique();
            $t->string('nama', 100);
        });

        Schema::create('pengguna_peran', function (Blueprint $t) {
            $t->foreignId('id_pengguna')->constrained('pengguna');
            $t->foreignId('id_peran')->constrained('peran', 'id_peran');
            $t->primary(['id_pengguna', 'id_peran']);
        });

        Schema::create('audit_log', function (Blueprint $t) {
            $t->id('id_log');
            $t->unsignedBigInteger('id_entitas')->nullable();
            $t->unsignedBigInteger('id_pengguna')->nullable();
            $t->string('tabel', 50);
            $t->unsignedBigInteger('record_id');
            $t->enum('aksi', ['INSERT', 'UPDATE', 'DELETE', 'POST', 'REVERSE', 'APPROVE']);
            $t->json('data_lama')->nullable();
            $t->json('data_baru')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['tabel', 'record_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('pengguna_peran');
        Schema::dropIfExists('peran');
        Schema::dropIfExists('pengguna');
        Schema::dropIfExists('konfigurasi');
        Schema::dropIfExists('periode_akuntansi');
        Schema::dropIfExists('entitas');
        Schema::dropIfExists('wilayah');
    }
};

