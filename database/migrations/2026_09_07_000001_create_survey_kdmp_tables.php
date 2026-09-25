<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── MASTER WILAYAH ───────────────────────────────────────────────────

        Schema::create('kecamatan', function (Blueprint $t) {
            $t->id('id_kecamatan');
            $t->string('kode_kecamatan', 10)->unique();
            $t->string('nama_kecamatan', 100);
            $t->timestamps();
        });

        Schema::create('desa', function (Blueprint $t) {
            $t->id('id_desa');
            $t->foreignId('id_kecamatan')->constrained('kecamatan', 'id_kecamatan');
            $t->string('kode_desa', 10)->unique();
            $t->string('nama_desa', 100);
            $t->year('tahun_survey')->nullable();
            $t->timestamps();
        });

        // ─── MASTER KOMODITAS (ALTER EXISTING) ────────────────────────────────

        Schema::table('komoditas', function (Blueprint $t) {
            $t->string('kode_komoditas', 20)->unique()->nullable()->after('id');
            $t->string('satuan_standar', 30)->nullable()->after('nama');
        });

        Schema::create('standar_konsumsi', function (Blueprint $t) {
            $t->id('id_standar');
            $t->foreignId('id_komoditas')->constrained('komoditas');
            $t->enum('kategori_gender', ['L', 'P']);
            $t->enum('kategori_umur', ['Balita', 'Anak', 'Remaja', 'Dewasa', 'Lansia']);
            $t->decimal('nilai_konsumsi', 12, 4);
            $t->string('satuan', 30);
            $t->enum('periode', ['harian', 'mingguan', 'bulanan', 'tahunan'])->default('bulanan');
            $t->decimal('nilai_per_tahun_standar', 14, 4)->nullable()
              ->comment('Konversi ke tahunan untuk kalkulasi kebutuhan M2');
            $t->timestamps();

            // Satu standar unik per kombinasi komoditas × gender × umur
            $t->unique(['id_komoditas', 'kategori_gender', 'kategori_umur'], 'standar_unik');
        });

        // ─── HEADER SURVEI ────────────────────────────────────────────────────

        Schema::create('survei', function (Blueprint $t) {
            $t->id('id_survei');
            $t->string('kode_survei', 30)->unique();
            $t->date('tanggal_input');
            $t->string('nama_petugas', 100);
            $t->foreignId('id_desa')->constrained('desa', 'id_desa');
            $t->string('dusun_rw_rt', 100)->nullable();
            $t->enum('status', ['draft', 'terkirim', 'disetujui', 'ditolak'])->default('draft');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('responden', function (Blueprint $t) {
            $t->id('id_responden');
            $t->foreignId('id_survei')->constrained('survei', 'id_survei');
            $t->string('nama_responden', 100);
            $t->enum('kategori_responden', [
                'Kepala RT',
                'Warga',
                'Petani',
                'Pedagang',
                'Nelayan',
                'Peternak',
                'Lainnya',
            ]);
            $t->string('nomor_kontak', 20)->nullable();
            $t->string('koordinat_gps', 50)->nullable()
              ->comment('Format: lat,lng — diambil otomatis dari perangkat');
        });

        // ─── DETAIL SURVEI ────────────────────────────────────────────────────

        // Jumlah penduduk/anggota rumah tangga per kombinasi gender × kelompok umur
        Schema::create('penduduk_kelompok', function (Blueprint $t) {
            $t->id('id_penduduk_kelompok');
            $t->foreignId('id_survei')->constrained('survei', 'id_survei');
            $t->foreignId('id_desa')->constrained('desa', 'id_desa');
            $t->enum('kategori_gender', ['L', 'P']);
            $t->enum('kategori_umur', ['Balita', 'Anak', 'Remaja', 'Dewasa', 'Lansia']);
            $t->unsignedInteger('jumlah_orang')->default(0);
            $t->timestamp('created_at')->useCurrent();

            // Satu baris per kombinasi survei × desa × gender × umur
            $t->unique(
                ['id_survei', 'id_desa', 'kategori_gender', 'kategori_umur'],
                'penduduk_unik'
            );
        });

        Schema::create('konsumsi_survei', function (Blueprint $t) {
            $t->id('id_konsumsi');
            $t->foreignId('id_survei')->constrained('survei', 'id_survei');
            $t->foreignId('id_desa')->constrained('desa', 'id_desa');
            $t->foreignId('id_komoditas')->constrained('komoditas');
            $t->decimal('jumlah_konsumsi', 14, 4);
            $t->string('satuan', 30);
            $t->enum('periode', ['harian', 'mingguan', 'bulanan', 'tahunan'])->default('bulanan');
            $t->enum('sumber_pemenuhan', [
                'Beli',
                'Produksi Sendiri',
                'Barter',
                'Bantuan',
                'Campuran',
            ])->nullable();
            $t->decimal('harga_beli', 14, 2)->nullable()
              ->comment('Diisi hanya jika sumber = Beli atau Campuran');
            $t->string('tempat_membeli', 100)->nullable();
            $t->enum('ketersediaan_barang', [
                'Mudah',
                'Kadang Sulit',
                'Sering Sulit',
                'Tidak Tersedia',
            ])->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('produksi_survei', function (Blueprint $t) {
            $t->id('id_produksi');
            $t->foreignId('id_survei')->constrained('survei', 'id_survei');
            $t->foreignId('id_desa')->constrained('desa', 'id_desa');
            $t->foreignId('id_komoditas')->constrained('komoditas');
            $t->decimal('jumlah_produksi', 14, 4);
            $t->string('satuan', 30);
            $t->enum('periode', ['harian', 'mingguan', 'bulanan', 'musiman', 'tahunan'])->default('bulanan');
            $t->decimal('jumlah_dijual', 14, 4)->default(0);
            $t->decimal('jumlah_dikonsumsi_sendiri', 14, 4)->default(0);
            $t->text('kendala_produksi')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach ([
            'produksi_survei',
            'konsumsi_survei',
            'penduduk_kelompok',
            'responden',
            'survei',
            'standar_konsumsi',
            'desa',
            'kecamatan',
        ] as $tabel) {
            Schema::dropIfExists($tabel);
        }

        Schema::table('komoditas', function (Blueprint $t) {
            $t->dropColumn(['kode_komoditas', 'satuan_standar']);
        });
    }
};
