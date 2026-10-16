<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('penerimaan_barang', ['id_penerimaan'], 'primary')) {
            Schema::table('penerimaan_barang', function (Blueprint $table): void {
                $table->primary('id_penerimaan');
            });
        }

        $receiptId = collect(Schema::getColumns('penerimaan_barang'))
            ->firstWhere('name', 'id_penerimaan');

        if (! $receiptId['auto_increment']) {
            Schema::table('penerimaan_barang', function (Blueprint $table): void {
                $table->unsignedBigInteger('id_penerimaan')->autoIncrement()->change();
            });
        }

        if (! Schema::hasIndex('migrations', ['id'], 'primary')) {
            Schema::table('migrations', function (Blueprint $table): void {
                $table->primary('id');
            });
        }

        $migrationId = collect(Schema::getColumns('migrations'))
            ->firstWhere('name', 'id');

        if (! $migrationId['auto_increment']) {
            Schema::table('migrations', function (Blueprint $table): void {
                $table->unsignedInteger('id')->autoIncrement()->change();
            });
        }
    }

    public function down(): void
    {
        // The original table definitions already require these primary keys.
    }
};
