<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengkonsolidasikan komoditas menjadi katalog master data global (1 komoditas untuk seluruh entitas).
     */
    public function up(): void
    {
        $mapping = [
            3 => 1, 25 => 1, 32 => 1, // Beras
            4 => 2,                   // ayam
            14 => 5, 20 => 5, 27 => 5, 34 => 5, // Gula Pasir
            15 => 6, 21 => 6, 28 => 6, 35 => 6, // Minyak Goreng
            16 => 7, 22 => 7, 29 => 7, 36 => 7, // Telur Ayam
            17 => 8, 23 => 8, 30 => 8, 37 => 8, // Daging Sapi
            13 => 9, 19 => 9, 26 => 9, 33 => 9, // Jagung
            18 => 10, 24 => 10, 31 => 10, 38 => 10, // Cabai Merah
        ];

        // 1. Remap kebutuhan_komoditas
        if (Schema::hasTable('kebutuhan_komoditas')) {
            foreach ($mapping as $old => $canonical) {
                $oldRows = DB::table('kebutuhan_komoditas')->where('id_komoditas', $old)->get();
                foreach ($oldRows as $r) {
                    $exists = DB::table('kebutuhan_komoditas')
                        ->where('id_wilayah', $r->id_wilayah)
                        ->where('id_komoditas', $canonical)
                        ->where('tahun', $r->tahun)
                        ->where('bulan', $r->bulan)
                        ->where('kelompok_umur', $r->kelompok_umur)
                        ->exists();
                    if ($exists) {
                        DB::table('kebutuhan_komoditas')->where('id', $r->id)->delete();
                    } else {
                        DB::table('kebutuhan_komoditas')->where('id', $r->id)->update(['id_komoditas' => $canonical]);
                    }
                }
            }
        }

        // 2. Remap standar_kebutuhan_komoditas
        if (Schema::hasTable('standar_kebutuhan_komoditas')) {
            $count9 = DB::table('standar_kebutuhan_komoditas')->where('id_komoditas', 9)->count();
            if ($count9 === 0 && DB::table('standar_kebutuhan_komoditas')->where('id_komoditas', 33)->exists()) {
                DB::table('standar_kebutuhan_komoditas')->where('id_komoditas', 33)->update(['id_komoditas' => 9]);
            }

            $count10 = DB::table('standar_kebutuhan_komoditas')->where('id_komoditas', 10)->count();
            if ($count10 === 0 && DB::table('standar_kebutuhan_komoditas')->where('id_komoditas', 38)->exists()) {
                DB::table('standar_kebutuhan_komoditas')->where('id_komoditas', 38)->update(['id_komoditas' => 10]);
            }

            if (Schema::hasTable('kebutuhan_komoditas')) {
                $canonicalStandards = DB::table('standar_kebutuhan_komoditas')->get();
                foreach ($canonicalStandards as $cs) {
                    DB::table('kebutuhan_komoditas')
                        ->where('id_komoditas', $cs->id_komoditas)
                        ->where('kelompok_umur', $cs->kelompok_umur)
                        ->update(['id_standar' => $cs->id]);
                }
                DB::table('kebutuhan_komoditas')->whereIn('id_standar', function ($q) use ($mapping) {
                    $q->select('id')->from('standar_kebutuhan_komoditas')->whereIn('id_komoditas', array_keys($mapping));
                })->update(['id_standar' => null]);
            }

            DB::table('standar_kebutuhan_komoditas')->whereIn('id_komoditas', array_keys($mapping))->delete();
        }

        // 3. Remap master_barang
        if (Schema::hasTable('master_barang')) {
            foreach ($mapping as $old => $canonical) {
                DB::table('master_barang')->where('id_komoditas', $old)->update(['id_komoditas' => $canonical]);
            }
        }

        // 4. Remap perbandingan_harga
        if (Schema::hasTable('perbandingan_harga')) {
            foreach ($mapping as $old => $canonical) {
                DB::table('perbandingan_harga')->where('id_komoditas', $old)->update(['id_komoditas' => $canonical]);
            }
        }

        // 5. Remap produksi_narasumber
        if (Schema::hasTable('produksi_narasumber')) {
            foreach ($mapping as $old => $canonical) {
                DB::table('produksi_narasumber')->where('id_komoditas', $old)->update(['id_komoditas' => $canonical]);
            }
        }

        // 6. Remap standar_konsumsi
        if (Schema::hasTable('standar_konsumsi')) {
            foreach ($mapping as $old => $canonical) {
                $oldRows = DB::table('standar_konsumsi')->where('id_komoditas', $old)->get();
                foreach ($oldRows as $r) {
                    $exists = DB::table('standar_konsumsi')
                        ->where('id_sesi', $r->id_sesi)
                        ->where('id_komoditas', $canonical)
                        ->where('kategori_gender', $r->kategori_gender)
                        ->where('kategori_umur', $r->kategori_umur)
                        ->exists();
                    if ($exists) {
                        DB::table('standar_konsumsi')->where('id_standar', $r->id_standar)->delete();
                    } else {
                        DB::table('standar_konsumsi')->where('id_standar', $r->id_standar)->update(['id_komoditas' => $canonical]);
                    }
                }
            }
        }

        // 7. Remap ketersediaan_komoditas
        if (Schema::hasTable('ketersediaan_komoditas')) {
            foreach ($mapping as $old => $canonical) {
                $oldRows = DB::table('ketersediaan_komoditas')->where('id_komoditas', $old)->get();
                foreach ($oldRows as $r) {
                    $exists = DB::table('ketersediaan_komoditas')
                        ->where('id_wilayah', $r->id_wilayah)
                        ->where('id_komoditas', $canonical)
                        ->where('tahun', $r->tahun)
                        ->where('bulan', $r->bulan)
                        ->first();
                    if ($exists) {
                        if ((float) $exists->jumlah_produksi <= 0 && (float) $r->jumlah_produksi > 0) {
                            DB::table('ketersediaan_komoditas')->where('id', $exists->id)->update(['jumlah_produksi' => $r->jumlah_produksi]);
                        }
                        DB::table('ketersediaan_komoditas')->where('id', $r->id)->delete();
                    } else {
                        DB::table('ketersediaan_komoditas')->where('id', $r->id)->update(['id_komoditas' => $canonical]);
                    }
                }
            }
        }

        // 8. Remap hasil_kalkulasi
        if (Schema::hasTable('hasil_kalkulasi')) {
            foreach ($mapping as $old => $canonical) {
                $oldRows = DB::table('hasil_kalkulasi')->where('id_komoditas', $old)->get();
                foreach ($oldRows as $r) {
                    $exists = DB::table('hasil_kalkulasi')
                        ->where('id_wilayah', $r->id_wilayah)
                        ->where('id_komoditas', $canonical)
                        ->where('tahun', $r->tahun)
                        ->where('bulan', $r->bulan)
                        ->where('versi', $r->versi)
                        ->exists();
                    if ($exists) {
                        DB::table('hasil_kalkulasi')->where('id_hasil', $r->id_hasil)->delete();
                    } else {
                        DB::table('hasil_kalkulasi')->where('id_hasil', $r->id_hasil)->update(['id_komoditas' => $canonical]);
                    }
                }
            }
        }

        if (Schema::hasTable('pemenuhan_komoditas')) {
            foreach ($mapping as $old => $canonical) {
                DB::table('pemenuhan_komoditas')->where('id_komoditas', $old)->update(['id_komoditas' => $canonical]);
            }
        }

        // 9. Bersihkan duplikat di tabel komoditas & jadikan global (id_entitas = null)
        if (Schema::hasTable('komoditas')) {
            DB::table('komoditas')->whereIn('id', array_keys($mapping))->delete();
            DB::table('komoditas')->update(['id_entitas' => null]);
            DB::table('komoditas')->where('id', 2)->update(['nama' => 'Ayam']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op karena data telah terkonsolidasi
    }
};
