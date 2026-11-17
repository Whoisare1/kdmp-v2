<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'permintaan_antar_entitas_gudang';

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id('id_permintaan_gudang');
                $table->foreignId('id_entitas_pengaju')->constrained('entitas', 'id_entitas')->restrictOnDelete();
                $table->foreignId('id_entitas_penerima')->constrained('entitas', 'id_entitas')->restrictOnDelete();
                // Hasil kalkulasi saat ini belum memiliki primary/unique key di database.
                // Simpan ID sumber sebagai referensi tanpa FK sampai struktur asal diperbaiki.
                $table->unsignedBigInteger('id_hasil_pengaju');
                $table->unsignedBigInteger('id_hasil_penerima');
                $table->string('jenis_permintaan', 30);
                $table->string('nama_komoditas', 150);
                $table->unsignedSmallInteger('tahun');
                $table->unsignedTinyInteger('bulan');
                $table->decimal('qty_rekomendasi', 18, 4);
                $table->string('satuan', 20);
                $table->string('status', 30)->default('terkirim');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['id_entitas_penerima', 'status', 'created_at'], 'perm_gudang_penerima_status');
                $table->index(['id_entitas_pengaju', 'status', 'created_at'], 'perm_gudang_pengaju_status');
                $table->unique(['id_hasil_pengaju', 'id_hasil_penerima'], 'perm_gudang_hasil_pair_unique');
            });

            return;
        }

        $kolomWajib = [
            'id_permintaan_gudang', 'id_entitas_pengaju', 'id_entitas_penerima',
            'id_hasil_pengaju', 'id_hasil_penerima', 'jenis_permintaan', 'nama_komoditas',
            'tahun', 'bulan', 'qty_rekomendasi', 'satuan', 'status', 'created_by',
            'created_at', 'updated_at',
        ];
        $kolomHilang = array_diff($kolomWajib, Schema::getColumnListing($tableName));
        if ($kolomHilang !== []) {
            throw new RuntimeException(
                'Tabel permintaan antar entitas Gudang sudah ada tetapi strukturnya belum lengkap: '
                .implode(', ', $kolomHilang).'. Periksa database sebelum melanjutkan.'
            );
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (! Schema::hasIndex($tableName, 'perm_gudang_penerima_status')) {
                $table->index(['id_entitas_penerima', 'status', 'created_at'], 'perm_gudang_penerima_status');
            }
            if (! Schema::hasIndex($tableName, 'perm_gudang_pengaju_status')) {
                $table->index(['id_entitas_pengaju', 'status', 'created_at'], 'perm_gudang_pengaju_status');
            }
            if (! Schema::hasIndex($tableName, 'perm_gudang_hasil_pair_unique')) {
                $table->unique(['id_hasil_pengaju', 'id_hasil_penerima'], 'perm_gudang_hasil_pair_unique');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('permintaan_antar_entitas_gudang')
            && DB::table('permintaan_antar_entitas_gudang')->exists()) {
            throw new RuntimeException(
                'Permintaan antar entitas Gudang masih memiliki data; tangani data sebelum rollback.'
            );
        }

        Schema::dropIfExists('permintaan_antar_entitas_gudang');
    }
};
