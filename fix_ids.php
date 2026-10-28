<?php
use Illuminate\Support\Facades\DB;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

\App\Models\Pengguna::where('id', '>', 1)->delete();
\App\Models\Tenant\KoperasiDesa::where('id_koperasi', '>', 1)->delete();
\App\Models\Tenant\Wilayah::where('id', '>', 1)->delete();

DB::statement("ALTER TABLE wilayah AUTO_INCREMENT = 2;");
DB::statement("ALTER TABLE koperasi_desa AUTO_INCREMENT = 2;");
DB::statement("ALTER TABLE pengguna AUTO_INCREMENT = 2;");

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

$lines = file('note.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$desas = [];
$capture = false;
foreach ($lines as $line) {
    $line = trim($line);
    if (strpos(strtolower($line), 'daftar desa desa di kabputane pati') !== false) {
        $capture = true;
        continue;
    }
    if (!$capture) continue;
    
    if (preg_match('/^\d+\.\s+Kecamatan/i', $line)) continue;
    if (preg_match('/^Daftar BPS/i', $line)) continue;
    if (preg_match('/^Catatan:/i', $line)) continue;
    if (preg_match('/^Desa:/i', $line)) continue;
    if (preg_match('/^Kelurahan:/i', $line)) continue;
    if (preg_match('/^\d+\.\s+(.*)/', $line, $matches)) {
        $line = trim($matches[1]);
    }
    
    if (empty($line)) continue;
    $desas[] = $line;
}

$desas = array_unique($desas);
$password = bcrypt('password');
$count = 1;

foreach($desas as $desa) {
    if (strtolower($desa) == 'batursari') {
        continue;
    }

    $wilayah = \App\Models\Tenant\Wilayah::create([
        'nama' => 'Desa ' . $desa,
        'tingkat' => 'desa'
    ]);

    $code = 'KOP-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $desa), 0, 3)) . str_pad($wilayah->id, 4, '0', STR_PAD_LEFT);
    $koperasi = \App\Models\Tenant\KoperasiDesa::create([
        'id_wilayah' => $wilayah->id,
        'kode_koperasi' => $code,
        'nama_koperasi' => 'Koperasi Desa ' . $desa,
        'tahun_buku_awal' => '2026',
        'is_active' => true
    ]);

    $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $desa)) . '@kdmp.test';
    \App\Models\Pengguna::create([
        'id_koperasi' => $koperasi->id_koperasi,
        'nama' => 'Manajer ' . $desa,
        'email' => $email,
        'password' => $password,
        'is_active' => true
    ]);
    
    $count++;
}

echo "IDs reset and re-seeded sequentially! Total data: " . $count . "\n";
