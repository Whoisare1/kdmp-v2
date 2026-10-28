<?php
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
    
    // Ignore descriptive lines
    if (preg_match('/^\d+\.\s+Kecamatan/i', $line)) continue;
    if (preg_match('/^Daftar BPS/i', $line)) continue;
    if (preg_match('/^Catatan:/i', $line)) continue;
    if (preg_match('/^Desa:/i', $line)) continue;
    if (preg_match('/^Kelurahan:/i', $line)) continue;
    if (preg_match('/^\d+\.\s+(.*)/', $line, $matches)) {
        // Handle "25. Pati Wetan"
        $line = trim($matches[1]);
    }
    
    if (empty($line)) continue;
    
    $desas[] = $line;
}

$desas = array_unique($desas);
$password = bcrypt('password');
$count = 0;

foreach($desas as $desa) {
    // 1. Wilayah
    $wilayah = \App\Models\Tenant\Wilayah::updateOrCreate(
        ['nama' => 'Desa ' . $desa],
        ['tingkat' => 'desa']
    );

    // 2. Koperasi Desa
    // unique code
    $code = 'KOP-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $desa), 0, 3)) . str_pad($wilayah->id, 4, '0', STR_PAD_LEFT);
    $koperasi = \App\Models\Tenant\KoperasiDesa::updateOrCreate(
        ['id_wilayah' => $wilayah->id],
        [
            'kode_koperasi' => $code,
            'nama_koperasi' => 'Koperasi Desa ' . $desa,
            'tahun_buku_awal' => '2026',
            'is_active' => true
        ]
    );

    // 3. Pengguna
    $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $desa)) . '@kdmp.test';
    $user = \App\Models\Pengguna::updateOrCreate(
        ['email' => $email],
        [
            'id_koperasi' => $koperasi->id_koperasi,
            'nama' => 'Manajer ' . $desa,
            'password' => $password,
            'is_active' => true
        ]
    );
    $count++;
}
echo "Seeded " . $count . " desas successfully!\n";
