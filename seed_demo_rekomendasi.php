<?php
/**
 * Seed Demo: Rekomendasi Pengadaan Antar Desa
 *
 * Skenario:
 *   Mekar Jaya  (wilayah 1, entitas 1) → SURPLUS Ikan Nila & Telur Ayam
 *   Sukamaju    (wilayah 2, entitas 2) → DEFISIT  Ikan Nila & Telur Ayam
 *
 * Langkah:
 *  1.  Approve entitas 1 & 2
 *  2.  Buat user admin_gudang di kedua entitas
 *  3.  Pastikan barang Ikan Nila & Telur Ayam ada di master_barang kedua entitas
 *  4.  Daftarkan ke barang_per_koperasi (is_dijual = 1) + konversi satuan KG
 *  5.  Isi stok gudang Mekar Jaya (gudang id:1)
 *  6.  Tambah kebutuhan_komoditas agar satuan = KG terdeteksi
 *  7.  Tambah hasil_kalkulasi Nov & Des 2026 jika belum ada
 *  8.  Reset rekomendasi & permintaan lama → uji coba bersih
 */

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$now = now()->toDateTimeString();

// ─── 1. Approve entitas ───────────────────────────────────────────────────────
DB::table('entitas')->whereIn('id_entitas', [1, 2])->update(['status' => 'APPROVED']);
echo "[1] Entitas 1 & 2 → APPROVED\n";

// ─── 2. User admin_gudang ─────────────────────────────────────────────────────
$akun = [
    ['email' => 'admingudang@kdmp-a.test', 'nama' => 'Admin Gudang Mekar Jaya', 'entitas' => 1],
    ['email' => 'admingudang@kdmp-b.test', 'nama' => 'Admin Gudang Sukamaju',   'entitas' => 2],
];
foreach ($akun as $a) {
    if (! DB::table('pengguna')->where('email', $a['email'])->exists()) {
        $uid = DB::table('pengguna')->insertGetId([
            'id_entitas' => $a['entitas'], 'nama' => $a['nama'],
            'email' => $a['email'], 'password' => Hash::make('password'),
            'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('pengguna_peran')->insert(['id_pengguna' => $uid, 'id_peran' => 2]); // id:2 = admin_gudang
        echo "[2] User {$a['email']} dibuat (id:{$uid})\n";
    } else {
        echo "[2] User {$a['email']} sudah ada\n";
    }
}

// ─── 3. Master barang per entitas ─────────────────────────────────────────────
// id_unit_usaha = 1 (Sembako) — wajib NOT NULL
// id_satuan_dasar : entitas 1 pakai id:1 (KG), entitas 2 pakai id:5 (KG)
$masterBarang = [
    // [komoditas, entitas, satuan_dasar_id, kode, nama]
    [11, 1, 1, 'IKN-NL-A1', 'Ikan Nila Lokal'],
    [25, 1, 1, 'TLR-AY-A1', 'Telur Ayam Lokal'],
    [11, 2, 5, 'IKN-NL-B2', 'Ikan Nila Lokal'],
    [25, 2, 5, 'TLR-AY-B2', 'Telur Ayam Lokal'],
];
$barangIdMap = []; // key: "komoditas:entitas" => id_barang
foreach ($masterBarang as [$kom, $ent, $sat, $kode, $nama]) {
    $existing = DB::table('master_barang')
        ->where('id_komoditas', $kom)->where('id_entitas', $ent)->first();
    if (! $existing) {
        $bid = DB::table('master_barang')->insertGetId([
            'id_entitas' => $ent, 'kode_barang' => $kode,
            'id_komoditas' => $kom, 'id_unit_usaha' => 1,
            'nama_barang' => $nama, 'id_satuan_dasar' => $sat,
            'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        echo "[3] Barang '{$nama}' entitas {$ent} dibuat (id:{$bid})\n";
    } else {
        $bid = $existing->id_barang;
        echo "[3] Barang '{$nama}' entitas {$ent} sudah ada (id:{$bid})\n";
    }
    $barangIdMap["{$kom}:{$ent}"] = $bid;
}

// ─── 4. barang_per_koperasi & konversi satuan ─────────────────────────────────
// satuan KG: entitas 1 → id_satuan:1 ; entitas 2 → id_satuan:5
$satuanKgPer = [1 => 1, 2 => 5];
foreach ($barangIdMap as $key => $bid) {
    [,$ent] = explode(':', $key);
    $ent = (int) $ent;
    $satKg = $satuanKgPer[$ent];

    // barang_per_koperasi
    if (! DB::table('barang_per_koperasi')->where('id_entitas', $ent)->where('id_barang', $bid)->exists()) {
        DB::table('barang_per_koperasi')->insert([
            'id_entitas' => $ent, 'id_barang' => $bid,
            'stok_minimum' => 20, 'stok_maksimum' => 2000,
            'harga_jual_standar' => 20000, 'is_dijual' => 1, 'lacak_batch' => 0,
        ]);
        echo "[4] barang_per_koperasi: barang {$bid} → entitas {$ent}\n";
    }

    // konversi satuan
    if (! DB::table('konversi_satuan')->where('id_barang', $bid)->where('id_satuan', $satKg)->exists()) {
        DB::table('konversi_satuan')->insert([
            'id_barang' => $bid, 'id_satuan' => $satKg,
            'faktor_ke_dasar' => 1.0, 'is_default_beli' => 1, 'is_default_jual' => 1,
        ]);
        echo "[4] Konversi KG (satuan id:{$satKg}) untuk barang {$bid}\n";
    }
}

// ─── 5. Stok gudang Mekar Jaya (gudang id:1, entitas 1) ──────────────────────
$stokInsert = [
    [$barangIdMap['11:1'], 1000.0, 18000],  // Ikan Nila 1000 KG
    [$barangIdMap['25:1'],  600.0, 25000],  // Telur Ayam 600 KG
];
foreach ($stokInsert as [$bid, $qty, $hpp]) {
    $existing = DB::table('stok')->where('id_gudang', 1)->where('id_barang', $bid)->first();
    if (! $existing) {
        DB::table('stok')->insert([
            'id_gudang' => 1, 'id_barang' => $bid,
            'qty_on_hand' => $qty, 'qty_reserved' => 0.0,
            'hpp_rata2' => $hpp, 'nilai_persediaan' => $qty * $hpp,
            'updated_at' => $now,
        ]);
        echo "[5] Stok barang {$bid}: {$qty} KG @ gudang 1\n";
    } else {
        DB::table('stok')->where('id_gudang', 1)->where('id_barang', $bid)
            ->update(['qty_on_hand' => $qty, 'qty_reserved' => 0.0,
                      'nilai_persediaan' => $qty * $hpp, 'updated_at' => $now]);
        echo "[5] Update stok barang {$bid} → {$qty} KG\n";
    }
}

// ─── 6. kebutuhan_komoditas (satuan = KG) ────────────────────────────────────
$added = 0;
foreach ([11, 25] as $kom) {
    foreach ([1, 2] as $wil) {
        foreach ([7, 8, 9, 10, 11, 12] as $bln) {
            if (! DB::table('kebutuhan_komoditas')
                ->where('id_wilayah', $wil)->where('id_komoditas', $kom)
                ->where('tahun', 2026)->where('bulan', $bln)->exists()) {
                DB::table('kebutuhan_komoditas')->insert([
                    'id_wilayah' => $wil, 'id_komoditas' => $kom,
                    'tahun' => 2026, 'bulan' => $bln,
                    'kelompok_umur' => 'dewasa', 'jumlah_penduduk' => 1000,
                    'per_kapita_harian' => '0.100000', 'faktor_musiman' => '1.0000',
                    'kebutuhan_bulanan' => '300.0000', 'satuan' => 'KG',
                    'id_standar' => null, 'created_at' => $now,
                ]);
                $added++;
            }
        }
    }
}
echo "[6] kebutuhan_komoditas: {$added} baris ditambahkan\n";

// ─── 7. Hasil kalkulasi Nov & Des 2026 ───────────────────────────────────────
// Mekar Jaya surplus, Sukamaju defisit — jika belum ada tambahkan
$kalkulasi = [
    // [wilayah, komoditas, tahun, bulan, kebutuhan, ketersediaan, selisih, status_sd]
    [1, 11, 2026, 11,  200.0,  1000.0,   800.0, 'surplus'],
    [1, 25, 2026, 11,  100.0,   600.0,   500.0, 'surplus'],
    [1, 11, 2026, 12,  200.0,  1000.0,   800.0, 'surplus'],
    [1, 25, 2026, 12,  100.0,   600.0,   500.0, 'surplus'],
    [2, 11, 2026, 11,  600.0,   300.0,  -300.0, 'defisit'],
    [2, 25, 2026, 11,  400.0,   100.0,  -300.0, 'defisit'],
    [2, 11, 2026, 12,  600.0,   300.0,  -300.0, 'defisit'],
    [2, 25, 2026, 12,  400.0,   100.0,  -300.0, 'defisit'],
];
$addedHasil = 0;
foreach ($kalkulasi as [$wil, $kom, $thn, $bln, $butuh, $tersedia, $selisih, $sd]) {
    $exists = DB::table('hasil_kalkulasi')
        ->where('id_wilayah', $wil)->where('id_komoditas', $kom)
        ->where('tahun', $thn)->where('bulan', $bln)
        ->where('status', 'disetujui')->exists();
    if (! $exists) {
        DB::table('hasil_kalkulasi')->insert([
            'id_wilayah'              => $wil,
            'id_komoditas'            => $kom,
            'tahun'                   => $thn,
            'bulan'                   => $bln,
            'total_kebutuhan'         => $butuh,
            'total_ketersediaan'      => $tersedia,
            'selisih'                 => $selisih,
            'status_surplus_defisit'  => $sd,
            'persentase_kecukupan'    => round($tersedia / $butuh * 100, 2),
            'id_unit_usaha_rekomendasi' => null,
            'alasan_rekomendasi'      => null,
            'prioritas'               => null,
            'versi'                   => 1,
            'status'                  => 'disetujui',
            'created_at'              => $now,
        ]);
        $addedHasil++;
    }
}
echo "[7] hasil_kalkulasi: {$addedHasil} baris ditambahkan\n";

// ─── 8. Reset data uji coba lama ─────────────────────────────────────────────
$del1 = DB::table('rekomendasi_pengadaan_desa')->delete();
$del2 = DB::table('permintaan_antar_entitas_gudang')->delete();
echo "[8] Rekomendasi lama dihapus: {$del1} | Permintaan antar entitas: {$del2}\n";

// ─── Summary ─────────────────────────────────────────────────────────────────
echo "\n════════════════════════════════════════════════\n";
echo "  SEED SELESAI — data siap uji coba\n";
echo "════════════════════════════════════════════════\n\n";
echo "Akun login:\n";
echo "  Manajer Sukamaju (DEFISIT)  : manajer@kdmp-b.test      / password\n";
echo "  Manajer Mekar Jaya (SURPLUS): manajer@kdmp-a.test      / password\n";
echo "  Admin Gudang Mekar Jaya     : admingudang@kdmp-a.test  / password\n";
echo "  Admin Gudang Sukamaju       : admingudang@kdmp-b.test  / password\n";
echo "  Super Admin                 : superadmin@ngobar.id     / (password lama)\n\n";
echo "Alur uji coba:\n";
echo "  1. Login → Manajer Sukamaju (desa DEFISIT)\n";
echo "  2. Perencanaan → Rekomendasi Pengadaan\n";
echo "  3. Pilih komoditas Ikan Nila atau Telur Ayam\n";
echo "     → Centang hingga 3 desa surplus → Kirim\n";
echo "  4. Login → Manajer/Admin Gudang Mekar Jaya\n";
echo "  5. Cek notifikasi ATAU Gudang → Rekomendasi Pengadaan\n";
echo "  6. Klik 'Setujui' → rekomendasi lain ditutup otomatis\n";
echo "     → Draft pembelian dibuat di modul Pembelian\n";
echo "════════════════════════════════════════════════\n";
