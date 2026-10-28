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
        Schema::table('rt_desas', function (Blueprint $table) {
            $table->string('rw', 5)->nullable()->after('nama_rt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rt_desas', function (Blueprint $table) {
            $table->dropColumn('rw');
        });
    }
};
