<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komoditas_kalkulasi_desa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_wilayah')->constrained('wilayah');
            $table->foreignId('id_komoditas')->constrained('komoditas');
            $table->timestamp('created_at')->nullable();
            $table->unique(['id_wilayah', 'id_komoditas'], 'komoditas_kalkulasi_desa_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komoditas_kalkulasi_desa');
    }
};
