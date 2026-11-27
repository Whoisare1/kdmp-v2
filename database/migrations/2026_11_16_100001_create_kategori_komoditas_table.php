<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kategori_komoditas')) {
            Schema::create('kategori_komoditas', function (Blueprint $table) {
                $table->id();
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
        Schema::dropIfExists('kategori_komoditas');
    }
};

