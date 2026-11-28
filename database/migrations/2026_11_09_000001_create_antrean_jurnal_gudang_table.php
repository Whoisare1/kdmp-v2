<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('antrean_jurnal_gudang')) {
            $columnsToAdd = array_values(array_filter(
                ['kode_error', 'jumlah_percobaan', 'diproses_pada', 'created_by'],
                fn (string $column): bool => ! Schema::hasColumn('antrean_jurnal_gudang', $column),
            ));

            if ($columnsToAdd !== []) {
                Schema::table('antrean_jurnal_gudang', function (Blueprint $t) use ($columnsToAdd): void {
                    if (in_array('kode_error', $columnsToAdd, true)) {
                        $t->string('kode_error', 50)->nullable();
                    }
                    if (in_array('jumlah_percobaan', $columnsToAdd, true)) {
                        $t->unsignedSmallInteger('jumlah_percobaan')->default(0);
                    }
                    if (in_array('diproses_pada', $columnsToAdd, true)) {
                        $t->timestamp('diproses_pada')->nullable();
                    }
                    if (in_array('created_by', $columnsToAdd, true)) {
                        $t->unsignedBigInteger('created_by')->nullable();
                    }
                });
            }

            return;
        }

        Schema::create('antrean_jurnal_gudang', function (Blueprint $t) {
            $t->id('id_antrean');
            $t->foreignId('id_entitas')->constrained('entitas', 'id_entitas');
            $t->enum('jenis_transaksi', ['PENERIMAAN', 'KERUSAKAN', 'OPNAME_KURANG', 'OPNAME_LEBIH']);
            $t->string('ref_tipe', 30)->comment('PENERIMAAN | KERUSAKAN | OPNAME');
            $t->unsignedBigInteger('ref_id');
            $t->string('no_dokumen', 30);
            $t->foreignId('id_unit_usaha')->constrained('master_unit_usaha', 'id_unit_usaha');
            $t->date('tanggal');
            $t->decimal('nilai', 18, 2)->default(0)->comment('Nilai total HPP, selalu positif');
            $t->json('payload')->nullable();

            // Status pemrosesan oleh Keuangan / Akuntansi
            $t->enum('status', ['MENUNGGU', 'DIPROSES', 'DIPOSTING', 'GAGAL', 'DIBALIK', 'DIBATALKAN'])
                ->default('MENUNGGU');
            $t->unsignedBigInteger('id_jurnal')->nullable();
            $t->string('no_jurnal', 30)->nullable();
            $t->string('kode_error', 50)->nullable();
            $t->text('pesan_error')->nullable();
            $t->unsignedSmallInteger('jumlah_percobaan')->default(0);
            $t->timestamp('diproses_pada')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            // Unique constraint agar tidak dobel antre
            $t->unique(['id_entitas', 'ref_tipe', 'ref_id', 'jenis_transaksi', 'id_unit_usaha'], 'antrean_idempoten');
            $t->index(['status', 'id_entitas']);
        });
    }

    public function down(): void
    {
        if (
            Schema::hasTable('antrean_jurnal_gudang')
            && DB::table('antrean_jurnal_gudang')->exists()
        ) {
            return;
        }

        Schema::dropIfExists('antrean_jurnal_gudang');
    }
};

