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
            $requiredColumns = [
                'id_antrean', 'id_entitas', 'jenis_transaksi', 'ref_tipe', 'ref_id',
                'no_dokumen', 'id_unit_usaha', 'tanggal', 'nilai', 'payload',
                'status', 'id_jurnal', 'no_jurnal', 'pesan_error', 'created_at', 'updated_at',
            ];
            $missingColumns = array_diff($requiredColumns, Schema::getColumnListing('antrean_jurnal_gudang'));
            if ($missingColumns !== []) {
                throw new RuntimeException(
                    'Tabel antrean_jurnal_gudang sudah ada tetapi belum lengkap: '
                    .implode(', ', $missingColumns).'. Periksa struktur database sebelum melanjutkan.'
                );
            }

            Schema::table('antrean_jurnal_gudang', function (Blueprint $table): void {
                if (! Schema::hasIndex('antrean_jurnal_gudang', 'antrean_idempoten')) {
                    $table->unique(
                        ['id_entitas', 'ref_tipe', 'ref_id', 'jenis_transaksi', 'id_unit_usaha'],
                        'antrean_idempoten',
                    );
                }
                if (! Schema::hasIndex('antrean_jurnal_gudang', 'antrean_status_entitas')) {
                    $table->index(['status', 'id_entitas'], 'antrean_status_entitas');
                }
            });

            return;
        }

        Schema::create('antrean_jurnal_gudang', function (Blueprint $table): void {
            $table->id('id_antrean');
            $table->foreignId('id_entitas')->constrained('entitas', 'id_entitas')->restrictOnDelete();
            $table->enum('jenis_transaksi', ['PENERIMAAN', 'KERUSAKAN', 'OPNAME_KURANG', 'OPNAME_LEBIH']);
            $table->string('ref_tipe', 30);
            $table->unsignedBigInteger('ref_id');
            $table->string('no_dokumen', 30);
            // master_unit_usaha.id_unit_usaha has no index in the current
            // database, so MySQL cannot accept it as a foreign-key target.
            // Keep the reference value without changing that shared table.
            $table->unsignedBigInteger('id_unit_usaha');
            $table->date('tanggal');
            $table->decimal('nilai', 18, 2);
            $table->json('payload');

            // Kolom berikut diisi dan dikelola oleh pemroses milik Keuangan.
            $table->string('status', 20)->default('MENUNGGU');
            $table->unsignedBigInteger('id_jurnal')->nullable();
            $table->string('no_jurnal', 30)->nullable();
            $table->text('pesan_error')->nullable();
            $table->timestamps();

            $table->unique(
                ['id_entitas', 'ref_tipe', 'ref_id', 'jenis_transaksi', 'id_unit_usaha'],
                'antrean_idempoten',
            );
            $table->index(['status', 'id_entitas'], 'antrean_status_entitas');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('antrean_jurnal_gudang') && DB::table('antrean_jurnal_gudang')->exists()) {
            throw new RuntimeException('Antrean jurnal Gudang masih memiliki data; tangani data sebelum rollback.');
        }

        Schema::dropIfExists('antrean_jurnal_gudang');
    }
};
