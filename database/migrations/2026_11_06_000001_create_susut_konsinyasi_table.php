<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A previous MySQL attempt may have created the table before a later
        // foreign-key statement failed. Keep that table and let Laravel record
        // this migration instead of trying to create it again.
        if (Schema::hasTable('susut_konsinyasi')) {
            return;
        }

        Schema::create('susut_konsinyasi', function (Blueprint $table): void {
            $table->id('id_susut_konsinyasi');
            $table->string('kode_susut', 40)->unique();
            $table->foreignId('id_stok_konsinyasi')
                ->constrained('stok_konsinyasi', 'id_stok_konsinyasi');
            $table->foreignId('id_entitas_pemilik')->nullable()
                ->constrained('entitas', 'id_entitas');
            $table->foreignId('id_entitas_pencatat')
                ->constrained('entitas', 'id_entitas');
            // pengguna.id is not indexed in the current local database, so a
            // foreign key cannot be created there. The optional user relation
            // is still available through Eloquent using this stored ID.
            $table->unsignedBigInteger('id_pengguna')->nullable();
            $table->date('tanggal');
            $table->enum('jenis_kejadian', ['rusak', 'susut_alami', 'hilang', 'kadaluarsa']);
            $table->decimal('qty', 18, 4);
            $table->decimal('hpp_pemilik', 18, 4);
            $table->decimal('nilai_susut', 18, 2);
            $table->enum('penanggung_susut', ['pemilik', 'penerima']);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['id_entitas_pencatat', 'tanggal'], 'susut_kon_pencatat_tanggal');
            $table->index(['id_entitas_pemilik', 'tanggal'], 'susut_kon_pemilik_tanggal');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('susut_konsinyasi') && DB::table('susut_konsinyasi')->exists()) {
            throw new RuntimeException('Riwayat susut konsinyasi masih ada; data harus ditangani sebelum rollback.');
        }

        Schema::dropIfExists('susut_konsinyasi');
    }
};
