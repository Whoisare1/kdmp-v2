<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('item_komoditas')) {
            Schema::create('item_komoditas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_master_komoditas')->constrained('master_komoditas')->cascadeOnDelete();
                $table->string('kode', 30)->unique();
                $table->string('nama', 150);
                $table->string('satuan_standar', 30)->default('kg');
                $table->text('deskripsi')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_komoditas');
    }
};

