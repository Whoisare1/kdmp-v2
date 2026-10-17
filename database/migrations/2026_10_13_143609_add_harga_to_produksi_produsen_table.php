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
        Schema::table('produksi_produsen', function (Blueprint $table) {
            $table->decimal('harga', 15, 2)->nullable()->after('satuan');
            $table->string('tipe_harga', 20)->default('Satuan')->after('harga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produksi_produsen', function (Blueprint $table) {
            $table->dropColumn(['harga', 'tipe_harga']);
        });
    }
};
