<?php
$desas = ['Batursari', 'Jimbaran', 'Penambuhan', 'Bermi', 'Plukaran'];
$password = bcrypt('password');

foreach($desas as $index => $desa_name) {
    // 1. Wilayah / Desa
    $wilayah = \App\Models\Tenant\Wilayah::updateOrCreate(
        ['nama' => 'Desa ' . $desa_name],
        ['tingkat' => 'desa']
    );

    // 2. Koperasi Desa (tanpa "Merah Putih")
    $koperasi = \App\Models\Tenant\KoperasiDesa::updateOrCreate(
        ['id_wilayah' => $wilayah->id],
        [
            'kode_koperasi' => 'KOP-' . strtoupper(substr($desa_name, 0, 3)),
            'nama_koperasi' => 'Koperasi Desa ' . $desa_name,
            'tahun_buku_awal' => '2026',
            'is_active' => true
        ]
    );

    // 3. Akun Pengguna / Manajer
    $user = \App\Models\Pengguna::updateOrCreate(
        ['nama' => 'Manajer ' . $desa_name],
        [
            'id_koperasi' => $koperasi->id_koperasi,
            'email' => strtolower($desa_name) . '@kdmp.test',
            'password' => $password,
            'is_active' => true
        ]
    );
}

echo "Desa seeding completed!\n";
