<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel pengaman transaksi:
 *
 * 1. penomoran_jurnal
 *    Counter nomor jurnal per entitas per prefix (mis: JRN-2026-10-).
 *    Menggantikan pola "COUNT(*) + 1 ... FOR UPDATE" yang:
 *      - bisa menghasilkan nomor kembar jika dua posting berjalan bersamaan, dan
 *      - rawan DEADLOCK (gap lock dua transaksi saling menunggu saat INSERT).
 *    Pola baru: INSERT ... ON DUPLICATE KEY UPDATE nomor = LAST_INSERT_ID(nomor + 1)
 *    -> satu statement atomik, mengunci SATU baris counter saja. Karena ikut
 *    transaksi, jika posting gagal counter ikut di-rollback (tidak ada nomor loncat).
 *
 * 2. idempotency_keys
 *    Mencatat token unik setiap submit form (dibuat browser saat form dibuka).
 *    Token disimpan DALAM transaksi yang sama dengan data bisnis, sehingga:
 *      - submit ulang (klik dobel / internet putus lalu kirim ulang) dengan token
 *        yang sama dikenali dan TIDAK diproses dua kali;
 *      - jika proses gagal, token ikut di-rollback sehingga user boleh kirim ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penomoran_jurnal', function (Blueprint $t) {
            $t->unsignedBigInteger('id_entitas');
            $t->string('prefix', 20);
            $t->unsignedInteger('nomor_terakhir')->default(0);
            $t->primary(['id_entitas', 'prefix']);
        });

        Schema::create('idempotency_keys', function (Blueprint $t) {
            $t->id();
            $t->string('kunci', 64)->unique();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('method', 10);
            $t->string('path', 255);
            $t->text('redirect_to')->nullable();
            $t->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('penomoran_jurnal');
    }
};
