<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_antar_entitas_gudang', function (Blueprint $table): void {
            $table->dropUnique('perm_gudang_hasil_pair_unique');
        });
    }

    public function down(): void
    {
        $duplikat = DB::table('permintaan_antar_entitas_gudang')
            ->select('id_hasil_pengaju', 'id_hasil_penerima')
            ->groupBy('id_hasil_pengaju', 'id_hasil_penerima')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplikat) {
            throw new RuntimeException(
                'Pasangan hasil kalkulasi memiliki beberapa riwayat permintaan; rollback constraint dibatalkan agar data tidak hilang.'
            );
        }

        Schema::table('permintaan_antar_entitas_gudang', function (Blueprint $table): void {
            $table->unique(
                ['id_hasil_pengaju', 'id_hasil_penerima'],
                'perm_gudang_hasil_pair_unique',
            );
        });
    }
};
