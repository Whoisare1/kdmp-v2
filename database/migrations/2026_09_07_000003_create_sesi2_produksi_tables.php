<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alter sesi_survei: tambah kolom khusus Sesi 2
        Schema::table('sesi_survei', function (Blueprint $t) {
            if (!Schema::hasColumn('sesi_survei', 'status_sesi2')) {
                $t->enum('status_sesi2', ['belum_diisi', 'sedang_diisi', 'selesai', 'terverifikasi'])
                  ->default('belum_diisi')
                  ->after('status_sesi1');
            }
            if (!Schema::hasColumn('sesi_survei', 'selesai_sesi2_at')) {
                $t->timestamp('selesai_sesi2_at')
                  ->nullable()
                  ->after('status_sesi2');
            }
            if (!Schema::hasColumn('sesi_survei', 'diselesaikan_sesi2_oleh')) {
                $t->unsignedBigInteger('diselesaikan_sesi2_oleh')
                  ->nullable()
                  ->after('selesai_sesi2_at');
            }
        });

        // Ubah kolom kategori pada narasumber_sesi menjadi VARCHAR(100) agar mendukung semua kategori narasumber Sesi 2
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE narasumber_sesi MODIFY COLUMN kategori VARCHAR(100) NOT NULL DEFAULT 'Lainnya'");
        } catch (\Throwable $e) {
            // Ignore if driver doesn't support raw ALTER
        }

        // Drop & create tabel produksi_narasumber untuk penyesuaian bidang baru Sesi 2
        Schema::dropIfExists('produksi_narasumber');

        Schema::create('produksi_narasumber', function (Blueprint $t) {
            $t->id('id_produksi_ns');
            $t->foreignId('id_sesi')->constrained('sesi_survei')->cascadeOnDelete();
            $t->foreignId('id_narasumber')
              ->constrained('narasumber_sesi', 'id_narasumber')
              ->cascadeOnDelete();
            $t->foreignId('id_komoditas')->constrained('komoditas');
            $t->decimal('jumlah_produksi', 14, 4)->default(0);
            $t->string('satuan', 30)->default('kg'); // satuan input (misal ton, kuintal, kg)
            $t->decimal('jumlah_produksi_kg', 14, 4)->default(0); // konversi ke satuan dasar kg
            $t->string('periode', 50)->default('Bulanan');
            $t->json('bulan_panen_json')->nullable(); // array bulan panen (1..12 atau 'Jan'..'Des')
            $t->decimal('jumlah_dijual', 14, 4)->default(0);
            $t->decimal('jumlah_dikonsumsi_sendiri', 14, 4)->default(0);
            $t->json('kendala_json')->nullable(); // array pilihan kendala (Hama, Bencana, Pupuk, Air, Cuaca, Pakan, dll)
            $t->text('kendala_produksi')->nullable(); // catatan detail kendala
            $t->string('sumber_data', 100)->default('Wawancara Narasumber');
            $t->date('tanggal_data')->nullable();
            $t->text('keterangan_sumber')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi_narasumber');

        Schema::table('sesi_survei', function (Blueprint $t) {
            if (Schema::hasColumn('sesi_survei', 'status_sesi2')) {
                $t->dropColumn([
                    'status_sesi2',
                    'selesai_sesi2_at',
                    'diselesaikan_sesi2_oleh',
                ]);
            }
        });
    }
};
