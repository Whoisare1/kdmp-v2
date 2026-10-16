
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $detailId = collect(Schema::getColumns('penerimaan_barang_detail'))
            ->firstWhere('name', 'id_detail');

        if ($detailId === null) {
            throw new RuntimeException(
                'Kolom id_detail tidak ditemukan pada tabel penerimaan_barang_detail.'
            );
        }

        if (! Schema::hasIndex('penerimaan_barang_detail', ['id_detail'], 'primary')) {
            Schema::table('penerimaan_barang_detail', function (Blueprint $table): void {
                $table->primary('id_detail');
            });
        }

        if (! $detailId['auto_increment']) {
            Schema::table('penerimaan_barang_detail', function (Blueprint $table): void {
                $table->unsignedBigInteger('id_detail')->autoIncrement()->change();
            });
        }
    }

    public function down(): void
    {
        // Primary key tetap dipertahankan sesuai kebutuhan struktur tabel.
    }
};
