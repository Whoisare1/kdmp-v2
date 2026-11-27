<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('potensi_produksi_petani')) {
            Schema::create('potensi_produksi_petani', function (Blueprint $table) {
                $table->id();
                $table->foreignId('petani_id')->constrained('petani')->cascadeOnDelete();
                $table->foreignId('item_komoditas_id')->constrained('item_komoditas')->cascadeOnDelete();
                $table->decimal('luas_lahan_ha', 10, 2)->nullable();
                $table->decimal('estimasi_volume_panen', 12, 2)->nullable();
                $table->string('satuan', 30)->default('kg');
                $table->string('periode_panen', 50)->default('Musiman');
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->unique(['petani_id', 'item_komoditas_id'], 'petani_item_unik');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('potensi_produksi_petani');
    }
};

