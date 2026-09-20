<?php
$w = \App\Models\Tenant\Wilayah::first();
if($w) {
    $w->nama = 'Desa Batursari';
    $w->save();
}

$k = \App\Models\Tenant\KoperasiDesa::first();
if($k) {
    $k->nama_koperasi = 'Koperasi Desa Merah Putih Batursari';
    $k->save();
}

$u = \App\Models\Pengguna::first();
if($u) {
    $u->nama = 'Manajer Desa Batursari';
    $u->save();
}
echo "OK\n";
