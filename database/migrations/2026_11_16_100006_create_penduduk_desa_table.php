<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('penduduk_desa')) {
            Schema::create('penduduk_desa', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_wilayah')->constrained('wilayah')->cascadeOnDelete();
                $table->year('tahun');
                $table->unsignedInteger('balita')->default(0)->comment('0-4 tahun');
                $table->unsignedInteger('anak')->default(0)->comment('5-9 tahun');
                $table->unsignedInteger('remaja')->default(0)->comment('10-19 tahun');
                $table->unsignedInteger('dewasa')->default(0)->comment('20-59 tahun');
                $table->unsignedInteger('lansia')->default(0)->comment('60+ tahun');
                $table->unsignedInteger('total_penduduk')->default(0);
                $table->string('sumber_data', 100)->default('Input Admin');
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->unique(['id_wilayah', 'tahun'], 'penduduk_desa_tahun_unik');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penduduk_desa');
    }
};

