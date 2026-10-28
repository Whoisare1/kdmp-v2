<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengiriman_konsinyasi', function (Blueprint $table): void {
            $table->enum('status', [
                'draft', 'menunggu_stok', 'dikirim', 'diterima', 'berjalan', 'selesai', 'ditolak',
            ])->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman_konsinyasi', function (Blueprint $table): void {
            $table->enum('status', [
                'draft', 'dikirim', 'diterima', 'berjalan', 'selesai', 'ditolak',
            ])->default('draft')->change();
        });
    }
};