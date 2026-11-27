<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pertanyaan', function (Blueprint $table) {
            if (!Schema::hasColumn('pertanyaan', 'jenis_pendataan')) {
                $table->enum('jenis_pendataan', ['penduduk', 'komoditas'])->default('komoditas')->after('id_modul');
            }
            if (!Schema::hasColumn('pertanyaan', 'id_kategori_komoditas')) {
                $table->foreignId('id_kategori_komoditas')->nullable()->after('jenis_pendataan')->constrained('kategori_komoditas')->nullOnDelete();
            }
            if (!Schema::hasColumn('pertanyaan', 'id_master_komoditas')) {
                $table->foreignId('id_master_komoditas')->nullable()->after('id_kategori_komoditas')->constrained('master_komoditas')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pertanyaan', function (Blueprint $table) {
            if (Schema::hasColumn('pertanyaan', 'id_master_komoditas')) {
                $table->dropForeign(['id_master_komoditas']);
                $table->dropColumn('id_master_komoditas');
            }
            if (Schema::hasColumn('pertanyaan', 'id_kategori_komoditas')) {
                $table->dropForeign(['id_kategori_komoditas']);
                $table->dropColumn('id_kategori_komoditas');
            }
            if (Schema::hasColumn('pertanyaan', 'jenis_pendataan')) {
                $table->dropColumn('jenis_pendataan');
            }
        });
    }
};

