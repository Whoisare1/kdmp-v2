<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produsen_desas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_wilayah')->constrained('wilayah')->cascadeOnDelete();
            $table->enum('kategori', ['Kelompok Tani', 'Ekraf']);
            $table->string('nama_anggota');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produsen_desas');
    }
};
