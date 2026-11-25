<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->timestamp('diajukan_jurnal_pada')->nullable();
            $table->unsignedBigInteger('diajukan_jurnal_oleh')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropColumn(['diajukan_jurnal_pada', 'diajukan_jurnal_oleh']);
        });
    }
};
