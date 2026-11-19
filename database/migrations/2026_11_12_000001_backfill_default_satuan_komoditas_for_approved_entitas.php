<?php

use App\Http\Controllers\Master\EntitasController;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('entitas')) {
            return;
        }

        $entitasIds = DB::table('entitas')
            ->where('status', 'APPROVED')
            ->pluck('id_entitas');

        foreach ($entitasIds as $idEntitas) {
            EntitasController::provisionDefaultSatuanDanKomoditas((int) $idEntitas);
        }
    }

    public function down(): void
    {
        // Sengaja no-op agar data referensi yang dipakai atau disesuaikan entitas tidak terhapus.
    }
};

