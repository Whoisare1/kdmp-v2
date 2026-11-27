<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('master_komoditas')) {
            Schema::create('master_komoditas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_kategori')->constrained('kategori_komoditas')->cascadeOnDelete();
                $table->string('kode', 30)->unique();
                $table->string('nama', 100);
                $table->text('deskripsi')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_komoditas');
    }
};

