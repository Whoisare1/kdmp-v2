<?php

$text = file_get_contents(__DIR__ . '/note.txt');

preg_match('/1\. Kecamatan Batangan.*?Wirun/s', $text, $matches);
if (empty($matches)) {
    die("Data tidak ditemukan di note.txt\n");
}

$lines = explode("\n", trim($matches[0]));
$kecamatan = '';
$data = [];

foreach($lines as $line) {
    $line = trim($line);
    // Lewati baris kosong atau catatan
    if(empty($line) || str_starts_with($line, 'Daftar BPS') || str_starts_with($line, 'Catatan:') || str_starts_with($line, 'Desa:') || str_starts_with($line, 'Kelurahan:')) continue;
    
    // Deteksi kecamatan
    if(preg_match('/^[0-9]+\.\s+Kecamatan\s+([A-Za-z\s]+)\s+—/', $line, $m)) {
        $kecamatan = trim($m[1]);
        $data[$kecamatan] = [];
    } else if (preg_match('/^[0-9]+\.\s+(.*)$/', $line, $m)) {
        // Kelurahan dengan nomor (contoh: 25. Pati Wetan)
        $data[$kecamatan][] = trim($m[1]);
    } else if ($kecamatan !== '') {
        // Desa biasa
        $data[$kecamatan][] = $line;
    }
}

file_put_contents(__DIR__ . '/scratch_wilayah.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Selesai mem-parsing wilayah!\n";
