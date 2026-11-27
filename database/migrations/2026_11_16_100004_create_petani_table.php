<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('petani')) {
            Schema::create('petani', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_wilayah')->constrained('wilayah')->cascadeOnDelete();
                $table->string('nama_lengkap', 150);
                $table->string('nomor_hp', 30)->nullable();
                $table->text('alamat')->nullable();
                $table->string('rt', 10)->nullable();
                $table->string('rw', 10)->nullable();
                $table->string('desa', 100)->nullable();
                $table->string('kecamatan', 100)->nullable();
                $table->string('kabupaten', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('petani');
    }
};

