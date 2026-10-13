<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sesi_survei', function (Blueprint $table) {
            $columnsToDrop = [
                'status_sesi1',
                'status_sesi2',
                'status_sesi3',
                'status_sesi4',
                'selesai_sesi1_at',
                'diselesaikan_sesi1_oleh',
                'selesai_sesi2_at',
                'diselesaikan_sesi2_oleh',
                'selesai_sesi3_at',
                'diselesaikan_sesi3_oleh',
                'selesai_sesi4_at',
                'diselesaikan_sesi4_oleh',
                'jumlah_kk_verifikasi',
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('sesi_survei', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sesi_survei', function (Blueprint $table) {
            $table->string('status_sesi1')->nullable();
            $table->string('status_sesi2')->nullable();
            $table->string('status_sesi3')->nullable();
            $table->string('status_sesi4')->nullable();
        });
    }
};
