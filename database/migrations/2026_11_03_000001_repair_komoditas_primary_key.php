<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'komoditas';

        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'id')) {
            throw new RuntimeException('Tabel atau kolom komoditas.id tidak ditemukan.');
        }

        $idColumn = collect(Schema::getColumns($tableName))->firstWhere('name', 'id');

        if (
            ! $idColumn
            || ! str_contains(strtolower($idColumn['type'] ?? ''), 'bigint')
            || ! str_contains(strtolower($idColumn['type'] ?? ''), 'unsigned')
            || ($idColumn['nullable'] ?? true)
        ) {
            throw new RuntimeException(
                'Struktur komoditas.id bukan BIGINT UNSIGNED NOT NULL; perbaikan dibatalkan.'
            );
        }

        $invalidRows = DB::table($tableName)->whereNull('id')->count();
        $duplicateIds = DB::table($tableName)
            ->select('id')
            ->groupBy('id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($invalidRows > 0 || $duplicateIds) {
            throw new RuntimeException(
                'Terdapat ID komoditas kosong atau duplikat; perbaikan dibatalkan.'
            );
        }

        $indexes = Schema::getIndexes($tableName);
        $primaryIndex = collect($indexes)->first(
            fn (array $index) => ($index['primary'] ?? false) === true
        );

        if ($primaryIndex && ($primaryIndex['columns'] ?? []) !== ['id']) {
            throw new RuntimeException(
                'Primary key komoditas sudah memakai kolom lain; perbaikan dibatalkan.'
            );
        }

        if (! $primaryIndex) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->primary('id');
            });
        }

        if (! ($idColumn['auto_increment'] ?? false)) {
            DB::statement(
                'ALTER TABLE `komoditas` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        }
    }

    public function down(): void
    {
        // Sengaja tidak menghapus primary key atau AUTO_INCREMENT yang dipulihkan.
    }
};
