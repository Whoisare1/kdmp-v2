<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $financeMigration = require __DIR__.'/2026_10_11_000001_revise_sp_jurnal_tanpa_transaksi.php';
        $financeMigration->up();
    }

    public function down(): void
    {
        // Keep the installed Finance procedures; reverting would remove posting support.
    }
};
