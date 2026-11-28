<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'pembelian' => 'id_pembelian',
            'detail_pembelian' => 'id_detail',
        ] as $tableName => $idColumn) {
            if (
                DB::table($tableName)->whereNull($idColumn)->exists()
                || DB::table($tableName)
                    ->select($idColumn)
                    ->groupBy($idColumn)
                    ->havingRaw('COUNT(*) > 1')
                    ->exists()
            ) {
                throw new RuntimeException(
                    "Kolom {$tableName}.{$idColumn} memiliki nilai kosong atau duplikat; primary key tidak diubah."
                );
            }

            $idDefinition = collect(Schema::getColumns($tableName))
                ->firstWhere('name', $idColumn);

            if (! $idDefinition || $idDefinition['type_name'] !== 'bigint') {
                throw new RuntimeException(
                    "Kolom {$tableName}.{$idColumn} tidak memiliki tipe BIGINT yang diharapkan."
                );
            }

            $primaryKey = collect(Schema::getIndexes($tableName))
                ->firstWhere('primary', true);

            if ($primaryKey && $primaryKey['columns'] !== [$idColumn]) {
                throw new RuntimeException(
                    "Tabel {$tableName} memiliki primary key berbeda; migration dibatalkan."
                );
            }

            if (! Schema::hasIndex($tableName, [$idColumn], 'primary')) {
                Schema::table($tableName, function (Blueprint $table) use ($idColumn): void {
                    $table->primary($idColumn);
                });
            }

            if (! $idDefinition['auto_increment']) {
                Schema::table($tableName, function (Blueprint $table) use ($idColumn): void {
                    $table->unsignedBigInteger($idColumn)->autoIncrement()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // The original table definitions already require these primary keys.
    }
};
