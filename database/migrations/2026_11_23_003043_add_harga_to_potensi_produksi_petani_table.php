<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('potensi_produksi_petani', function (Blueprint $table) {
            $table->decimal('harga_satuan', 15, 2)->nullable()->default(0)->after('satuan');
            $table->decimal('total_nilai', 18, 2)->nullable()->default(0)->after('harga_satuan');
        });
    }

    public function down(): void
    {
        Schema::table('potensi_produksi_petani', function (Blueprint $table) {
            $table->dropColumn(['harga_satuan', 'total_nilai']);
        });
    }
};
