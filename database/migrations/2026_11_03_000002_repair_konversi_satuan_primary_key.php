<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'konversi_satuan';

        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'id_konversi')) {
            throw new RuntimeException('Tabel atau kolom konversi_satuan.id_konversi tidak ditemukan.');
        }

        $idColumn = collect(Schema::getColumns($tableName))->firstWhere('name', 'id_konversi');

        if (
            ! $idColumn
            || ! str_contains(strtolower($idColumn['type'] ?? ''), 'bigint')
            || ! str_contains(strtolower($idColumn['type'] ?? ''), 'unsigned')
            || ($idColumn['nullable'] ?? true)
        ) {
            throw new RuntimeException(
                'Struktur konversi_satuan.id_konversi bukan BIGINT UNSIGNED NOT NULL; perbaikan dibatalkan.'
            );
        }

        $invalidRows = DB::table($tableName)->whereNull('id_konversi')->count();
        $duplicateIds = DB::table($tableName)
            ->select('id_konversi')
            ->groupBy('id_konversi')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($invalidRows > 0 || $duplicateIds) {
            throw new RuntimeException(
                'Terdapat ID konversi kosong atau duplikat; perbaikan dibatalkan.'
            );
        }

        $indexes = Schema::getIndexes($tableName);
        $primaryIndex = collect($indexes)->first(
            fn (array $index) => ($index['primary'] ?? false) === true
        );

        if ($primaryIndex && ($primaryIndex['columns'] ?? []) !== ['id_konversi']) {
            throw new RuntimeException(
                'Primary key konversi_satuan sudah memakai kolom lain; perbaikan dibatalkan.'
            );
        }

        if (! $primaryIndex) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->primary('id_konversi');
            });
        }

        if (! ($idColumn['auto_increment'] ?? false)) {
            DB::statement(
                'ALTER TABLE `konversi_satuan` MODIFY `id_konversi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        }
    }

    public function down(): void
    {
        // Sengaja tidak menghapus primary key atau AUTO_INCREMENT yang dipulihkan.
    }
};
