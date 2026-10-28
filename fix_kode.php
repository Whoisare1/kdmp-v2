<?php

$lines = file('note.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$desas = [];
$current_kecamatan = '';
$capture = false;

foreach ($lines as $line) {
    $line = trim($line);
    if (strpos(strtolower($line), 'daftar desa desa di kabputane pati') !== false) {
        $capture = true;
        continue;
    }
    if (!$capture) continue;
    
    if (preg_match('/^\d+\.\s+Kecamatan\s+([a-zA-Z0-9\s]+)\s+—/i', $line, $m)) {
        $current_kecamatan = trim($m[1]);
        continue;
    }
    
    if (preg_match('/^Daftar BPS/i', $line)) continue;
    if (preg_match('/^Catatan:/i', $line)) continue;
    if (preg_match('/^Desa:/i', $line)) continue;
    if (preg_match('/^Kelurahan:/i', $line)) continue;
    if (preg_match('/^\d+\.\s+(.*)/', $line, $matches)) {
        $line = trim($matches[1]);
    }
    
    if (empty($line) || empty($current_kecamatan)) continue;
    
    $desas[] = [
        'desa' => $line,
        'kecamatan' => $current_kecamatan
    ];
}

$count = 0;
foreach($desas as $d) {
    $nama = 'Koperasi Desa ' . $d['desa'];
    
    $kecStr = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $d['kecamatan']), 0, 3));
    $desStr = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $d['desa']), 0, 3));
    
    $kop = \App\Models\Tenant\KoperasiDesa::where('nama_koperasi', $nama)->first();
    if ($kop) {
        // limit to fit in small varchar, e.g. "KOP-BAT-BAT"
        $code = "KOP-{$kecStr}-{$desStr}";
        
        // append id to avoid duplicates if 3 letters collide
        $code .= "-" . $kop->id_koperasi;
        
        // If still too long, limit it to 15 chars max
        if (strlen($code) > 15) {
            $code = substr($code, 0, 15);
        }

        $kop->kode_koperasi = $code;
        $kop->save();
        $count++;
    }
}

echo "Updated kode_koperasi for {$count} koperasi.\n";
