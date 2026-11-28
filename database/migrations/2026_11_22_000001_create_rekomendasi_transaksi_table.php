<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'rekomendasi_transaksi';

        Schema::create($tableName, function (Blueprint $table): void {
                $table->id('id_rekomendasi');
                $table->unsignedBigInteger('id_pengajuan_gudang');
                $table->string('jenis_rekomendasi', 20);
                $table->unsignedBigInteger('id_entitas_asal');
                $table->unsignedBigInteger('id_entitas_tujuan');
                $table->unsignedBigInteger('id_pihak_mitra');
                $table->unsignedBigInteger('id_barang');
                $table->string('nama_barang', 150);
                $table->unsignedBigInteger('id_satuan');
                $table->decimal('qty_disetujui', 18, 4);
                $table->decimal('harga_satuan_kesepakatan', 18, 2);
                $table->unsignedBigInteger('id_unit_usaha');
                $table->dateTime('tgl_berlaku_sampai');
                $table->string('status', 20)->default('menunggu');
                $table->timestamps();

                $table->unique(
                    ['id_pengajuan_gudang', 'jenis_rekomendasi', 'id_barang'],
                    'uq_rekomendasi_pengajuan_jenis_barang',
                );
                $table->index(['jenis_rekomendasi', 'id_entitas_asal', 'status'], 'idx_rekomendasi_penjualan');
                $table->index(['jenis_rekomendasi', 'id_entitas_tujuan', 'status'], 'idx_rekomendasi_pembelian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_transaksi');
    }
};
