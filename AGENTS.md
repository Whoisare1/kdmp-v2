
# AGENTS.md — Panduan Kerja Proyek Ekraf

## 1. Identitas Proyek

- Proyek ini adalah sistem Ekonomi Kreatif (Ekraf) berbasis Laravel.
- Framework: Laravel 12.
- Database: MariaDB/MySQL melalui XAMPP.
- Folder proyek lokal: `D:\SKRIPSI GITHUB\kdmp-v2`.
- Repository masih menggunakan nama lama KDMP, tetapi fokus sistem saat ini adalah Ekraf.
- Modul yang menjadi tanggung jawab utama pengembang adalah Gudang dan Konsinyasi.

## 2. Aturan Utama Sebelum Coding

1. Baca dan pahami kode yang sudah ada sebelum mengubahnya.
2. Jangan langsung membuat, menghapus, atau mengganti banyak file.
3. Jangan melakukan refactor yang tidak diminta.
4. Pertahankan pola penulisan dan arsitektur proyek yang sudah ada.
5. Periksa relasi antarmodul sebelum mengubah proses bisnis.
6. Jangan mengarang nama tabel, kolom, model, service, atau route. Periksa kode dan database terlebih dahulu.
7. Gunakan bahasa Indonesia yang mudah dipahami saat menjelaskan hasil pekerjaan.
8. Jika perubahan berpotensi memengaruhi modul lain, jelaskan terlebih dahulu dan minta persetujuan.
9. Jangan menganggap fitur sudah selesai sebelum memeriksa implementasinya.

## 3. Hak Akses dan Entitas

- Sistem bekerja berdasarkan entitas yang sedang aktif.
- Perhatikan penggunaan `id_entitas` dan mekanisme `SetEntitasAktif` jika masih digunakan dalam kode.
- Jangan menganggap pengguna memiliki akses superadmin.
- Data setiap entitas harus tetap terisolasi sesuai hak aksesnya.
- Jangan mengubah mekanisme autentikasi dan pembatasan akses tanpa persetujuan.
- Rencana penggunaan akun masing-masing desa setelah serah terima bukan berarti fitur tersebut sudah diterapkan saat ini.

## 4. Alur Bisnis Surplus dan Defisit

- Data surplus dan defisit berasal dari proses Hitung Kalkulasi dan Perencanaan Pengadaan.
- Modul Gudang menerima dan menggunakan data tersebut.
- Gudang mencocokkan barang yang surplus pada suatu entitas dengan barang yang defisit pada entitas lain.
- Contoh: Desa A mengalami surplus beras, sedangkan Desa C mengalami defisit beras.
- Hasil pencocokan menjadi informasi atau bahan tindak lanjut bagi modul Penjualan dan Pembelian.
- Informasi tersebut membantu menentukan apakah kebutuhan dipenuhi dari warga, desa sendiri, atau desa lain.
- Menu surplus dan defisit merupakan bagian dari Gudang, bukan Konsinyasi.
- Jangan menghitung ulang atau mengubah sumber data surplus dan defisit tanpa memahami modul asalnya.
- Jangan otomatis membuat transaksi penjualan atau pembelian tanpa persetujuan dan aturan bisnis yang jelas.

## 5. Ruang Lingkup Modul Gudang

Gudang bertanggung jawab terhadap pengelolaan persediaan barang, meliputi:

1. Data barang dan satuan barang.
2. Penerimaan barang.
3. Pengelolaan stok.
4. Posisi stok setiap entitas.
5. Kartu stok dan riwayat mutasi.
6. Stok opname.
7. Pencatatan barang rusak atau kerusakan barang.
8. Penyortiran barang.
9. Retur barang sesuai alur dan tanggung jawab Gudang.
10. Informasi rata-rata atau nilai persediaan.
11. Peringatan stok minimum.
12. Informasi surplus dan defisit.
13. Pencocokan surplus dan defisit antarentitas.
14. Penyampaian informasi kebutuhan kepada Penjualan dan Pembelian.

Perhatikan satuan barang, misalnya:
- Kg.
- Pcs.
- Karung (contoh: 100 kg).
- Satuan eceran.

Jangan menganggap semua barang memiliki satuan atau konversi yang sama.

## 6. Ruang Lingkup Modul Konsinyasi

Konsinyasi digunakan untuk mengelola barang titipan atau titip-jual antarentitas.

Ruang lingkupnya meliputi:
1. Pencatatan barang konsinyasi.
2. Pembuatan nota konsinyasi.
3. Pengiriman barang konsinyasi.
4. Pencatatan jumlah barang yang dititipkan.
5. Pengelolaan stok konsinyasi.
6. Riwayat mutasi barang konsinyasi.
7. Pemantauan status pengiriman dan barang titipan.
8. Retur barang konsinyasi sesuai proses bisnis.

Ketentuan:
- Konsinyasi berbeda dari stok milik sendiri.
- Jangan mencampur stok gudang biasa dengan stok barang titipan.
- Surplus dan defisit bukan bagian dari menu Konsinyasi.
- Jangan mengubah alur konsinyasi tanpa memahami dampaknya terhadap stok dan transaksi.

## 7. Batas Tanggung Jawab Keuangan

- Posting jurnal merupakan tanggung jawab modul Keuangan.
- Gudang dan Konsinyasi tidak boleh memanggil `JurnalService` secara langsung untuk melakukan posting jurnal.
- Jangan membuat jurnal otomatis dari proses Gudang atau Konsinyasi.
- Jika diperlukan integrasi dengan Keuangan, koordinasikan melalui mekanisme yang memang sudah disediakan sistem.
- Jangan mengubah proses akuntansi tanpa persetujuan.

## 8. Aturan Database dan Migrasi

Sebelum mengubah database:
1. Periksa struktur tabel dan relasinya.
2. Periksa migration yang sudah ada.
3. Periksa data yang mungkin terdampak.
4. Jelaskan tujuan perubahan dan risikonya.
5. Minta persetujuan sebelum perubahan yang berisiko.

Ketentuan:
- Perbaikan struktur database harus menggunakan file migration Laravel.
- Jangan langsung menjalankan SQL manual melalui phpMyAdmin untuk memperbaiki struktur.
- Jangan menggunakan `migrate:fresh`.
- Jangan menggunakan `migrate:refresh`.
- Jangan menggunakan `db:wipe`.
- Jangan melakukan DROP, TRUNCATE, atau penghapusan data tanpa persetujuan eksplisit.
- Jangan mengubah primary key, foreign key, atau auto increment tanpa pemeriksaan terlebih dahulu.

## 9. Aturan Git

Sebelum perubahan:
- Periksa branch aktif.
- Jalankan `git status`.
- Periksa perubahan yang belum disimpan.
- Jangan menimpa pekerjaan yang sudah ada.

Tanpa persetujuan eksplisit, jangan:
- Commit.
- Push.
- Merge.
- Rebase.
- Reset.
- Stash.
- Menghapus file yang berubah.
- Menjalankan `git reset --hard`.
- Menjalankan `git clean -fd`.

Jangan menganggap perubahan dari branch lain sudah aman digabungkan sebelum diperiksa.

## 10. Cara Bekerja dengan Pengembang

Pengembang masih dalam tahap belajar Laravel dan pengembangan sistem.

Karena itu:
1. Gunakan penjelasan Bahasa Indonesia yang sederhana.
2. Jelaskan fungsi kode sebelum memberikan perubahan yang besar.
3. Sebutkan file yang akan diubah.
4. Jelaskan alasan perubahan.
5. Hindari istilah teknis tanpa penjelasan.
6. Jangan memberikan banyak langkah sekaligus jika prosesnya berisiko.
7. Berikan perintah terminal secara bertahap.
8. Tunggu hasil pemeriksaan sebelum melanjutkan ke langkah berikutnya.
9. Jangan mengubah kode di luar kebutuhan yang diminta.
10. Jika ada beberapa solusi, jelaskan perbedaannya dan rekomendasikan yang paling aman.

## 11. Pemeriksaan Setelah Coding

Setelah melakukan perubahan:
- Periksa kembali file yang diubah.
- Jalankan pemeriksaan sintaks atau tes yang relevan.
- Periksa apakah alur bisnis masih sesuai.
- Periksa pembatasan berdasarkan entitas.
- Periksa kemungkinan dampak terhadap modul lain.
- Laporkan file yang berubah.
- Laporkan perintah dan tes yang sudah dijalankan.
- Jelaskan hasil yang berhasil dan yang belum berhasil.
- Jangan mengklaim berhasil jika belum diuji.

## 12. Prinsip Paling Penting

Utamakan keamanan data, ketepatan alur bisnis, konsistensi antarentitas, dan kestabilan sistem.

Jangan membuat perubahan besar hanya agar kode terlihat lebih rapi. Pertahankan sistem yang sudah berjalan dan lakukan perubahan sekecil mungkin sesuai kebutuhan.
