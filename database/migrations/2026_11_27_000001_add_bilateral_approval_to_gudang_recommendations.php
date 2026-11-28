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
            $table->unsignedBigInteger('id_persetujuan_pengaju')->nullable();
            $table->dateTime('tgl_persetujuan_pengaju')->nullable();
            $table->unsignedBigInteger('id_persetujuan_penerima')->nullable();
            $table->dateTime('tgl_persetujuan_penerima')->nullable();
            $table->dateTime('disetujui_penuh_pada')->nullable();
            $table->decimal('qty_dialokasikan', 18, 4)->nullable();
        });

        Schema::table('rekomendasi_transaksi', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_gudang')->nullable();
            $table->unsignedBigInteger('id_transaksi_terbentuk')->nullable();
            $table->dropUnique('uq_rekomendasi_pengajuan_jenis_barang');
            $table->unique(
                ['id_pengajuan_gudang', 'jenis_rekomendasi', 'id_barang', 'id_gudang'],
                'uq_rekomendasi_pengajuan_barang_gudang',
            );
        });
    }

    public function down(): void
    {
        if (
            DB::table('permintaan_antar_entitas_gudang')
                ->where(function ($query): void {
                    $query->whereNotNull('tgl_persetujuan_pengaju')
                        ->orWhereNotNull('tgl_persetujuan_penerima')
                        ->orWhereNotNull('disetujui_penuh_pada')
                        ->orWhereNotNull('qty_dialokasikan');
                })
                ->exists()
            || DB::table('rekomendasi_transaksi')
                ->whereNotNull('id_gudang')
                ->orWhereNotNull('id_transaksi_terbentuk')
                ->exists()
        ) {
            throw new RuntimeException(
                'Persetujuan atau rekomendasi Gudang sudah menggunakan kolom baru; rollback dibatalkan agar data tidak hilang.'
            );
        }

        Schema::table('rekomendasi_transaksi', function (Blueprint $table): void {
            $table->dropUnique('uq_rekomendasi_pengajuan_barang_gudang');
            $table->unique(
                ['id_pengajuan_gudang', 'jenis_rekomendasi', 'id_barang'],
                'uq_rekomendasi_pengajuan_jenis_barang',
            );
            $table->dropColumn(['id_gudang', 'id_transaksi_terbentuk']);
        });

        Schema::table('permintaan_antar_entitas_gudang', function (Blueprint $table): void {
            $table->dropColumn([
                'id_persetujuan_pengaju',
                'tgl_persetujuan_pengaju',
                'id_persetujuan_penerima',
                'tgl_persetujuan_penerima',
                'disetujui_penuh_pada',
                'qty_dialokasikan',
            ]);
        });
    }
};
