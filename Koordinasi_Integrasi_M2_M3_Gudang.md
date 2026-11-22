# Koordinasi Integrasi M2, M3, dan Gudang

## 1. Tujuan

Dokumen ini merangkum rancangan alur integrasi hasil surplus/defisit
dari M2 dengan Gudang, serta hubungan proses tersebut dengan Perencanaan
Pengadaan (M3) dan modul Pembelian.

Pembagian tanggung jawab yang disepakati dalam rancangan:

-   **M2 --- Kalkulasi Kebutuhan:** menghitung kebutuhan dan neraca
    komoditas desa.
-   **Gudang:** membaca hasil kalkulasi yang telah disahkan, mencocokkan
    surplus/defisit antarentitas dengan stok aktual, lalu menampilkan
    saran tindak lanjut.
-   **M3 --- Perencanaan:** menyusun perencanaan pengadaan dan Draf
    Permintaan Pengadaan (PR).
-   **Modul Pembelian:** memilih supplier dan menjalankan proses
    pembelian.

> Catatan: Poin-poin di bawah merupakan rancangan yang perlu
> dikonfirmasi bersama tim terkait. Dokumen ini tidak menyatakan bahwa
> seluruh mekanisme persetujuan atau integrasi sudah tersedia di
> aplikasi.

## 2. Persetujuan Hasil Kalkulasi

### Usulan kewenangan

Usulan awal adalah **Manajer Desa** berwenang memeriksa dan menyetujui
hasil kalkulasi M2, sedangkan Operator menjalankan proses kalkulasi.

Alur yang diusulkan:

1.  Operator menjalankan kalkulasi kebutuhan di M2.
2.  Sistem menyimpan hasil kalkulasi baru dengan status `draft`.
3.  Manajer Desa memeriksa hasil kebutuhan dan neraca komoditas.
4.  Manajer Desa menyetujui hasil kalkulasi.
5.  Hasil berstatus `disetujui` dapat dibaca oleh Gudang.

**Hal yang perlu dikonfirmasi:** saat ini belum ditemukan aksi atau
route persetujuan pada pemeriksaan sebelumnya. Karena itu, role yang
berwenang, halaman persetujuan, dan mekanisme implementasinya perlu
dipastikan bersama tim M2 sebelum dilakukan perubahan kode.

## 3. Versi Resmi untuk Gudang

Usulan mekanisme versi:

-   Hasil kalkulasi terakhir yang berstatus `disetujui` menjadi
    referensi resmi yang dibaca Gudang.
-   Hasil kalkulasi ulang disimpan sebagai versi baru dengan status
    `draft`.
-   Versi `draft` tidak menggantikan versi resmi yang sedang digunakan
    Gudang.
-   Setelah versi baru disetujui, versi tersebut menjadi referensi resmi
    terbaru.
-   Riwayat versi sebelumnya sebaiknya tetap dapat ditelusuri sesuai
    kemampuan struktur aplikasi.

Dengan mekanisme ini, Gudang tidak membaca hasil kalkulasi yang belum
disahkan.

## 4. Konsistensi Komoditas dan Satuan

Pencocokan data antara hasil kalkulasi dan stok Gudang perlu menggunakan
identitas komoditas serta satuan yang konsisten.

Ketentuan yang diusulkan:

1.  Gunakan ID dari master komoditas sebagai identitas utama, bukan
    hanya mencocokkan nama teks.
2.  Gunakan satuan standar yang disepakati untuk setiap komoditas.
3.  Jika terdapat perbedaan satuan, lakukan konversi hanya jika aturan
    konversinya tersedia dan disepakati.
4.  Jangan membandingkan jumlah dengan satuan yang berbeda tanpa
    konversi yang valid.
5.  Pastikan data desa/entitas, komoditas, periode, satuan, dan nilai
    surplus/defisit tersedia pada data integrasi.

Contoh format informasi yang perlu disepakati:

  -----------------------------------------------------------------------
  Elemen                              Keterangan
  ----------------------------------- -----------------------------------
  ID komoditas                        Referensi ke master komoditas

  Nama komoditas                      Nama yang ditampilkan

  ID desa/entitas                     Identitas desa atau entitas sumber

  Periode kalkulasi                   Periode hasil kalkulasi

  Kebutuhan                           Jumlah kebutuhan komoditas

  Pasokan yang diperhitungkan         Nilai pasokan sesuai definisi M2

  Neraca                              Nilai surplus atau defisit

  Satuan                              Satuan standar komoditas

  Status kalkulasi                    Harus `disetujui` untuk dibaca
                                      sebagai hasil resmi
  -----------------------------------------------------------------------

Struktur di atas merupakan usulan kontrak data; perlu dicocokkan dengan
struktur database dan model yang benar-benar digunakan proyek.

## 5. Integrasi Hasil Surplus/Defisit dengan Gudang

Alur yang diusulkan:

``` text
M2 — KALKULASI KEBUTUHAN
          |
          v
HASIL KALKULASI (DRAFT)
          |
          v
PEMERIKSAAN DAN PERSETUJUAN
          |
          v
HASIL KALKULASI (DISETUJUI)
          |
          v
GUDANG MEMBACA HASIL RESMI
          |
          v
PENCOCOKAN SURPLUS/DEFISIT ANTAR-ENTITAS
          |
          v
PENCOCOKAN DENGAN STOK AKTUAL
          |
          v
SARAN TINDAK LANJUT
```

Tanggung jawab Gudang adalah membaca hasil yang telah disahkan,
mencocokkannya dengan stok aktual, dan menampilkan saran tindak lanjut.
Gudang tidak menghitung ulang kebutuhan atau mengubah hasil kalkulasi
M2.

## 6. Perbedaan Integrasi Gudang dan Draf PR

Kedua proses ini harus dibedakan.

### A. Integrasi hasil kalkulasi ke Gudang

Tujuan: menyediakan hasil surplus/defisit M2 yang telah disetujui agar
Gudang dapat mencocokkannya dengan data antarentitas dan stok aktual.

``` text
M2 — HASIL KALKULASI DISETUJUI
          |
          v
GUDANG MEMBACA HASIL
          |
          v
PENCOCOKAN ANTAR-ENTITAS
          |
          v
PENCOCOKAN STOK AKTUAL
          |
          v
SARAN TINDAK LANJUT
```

### B. Penyusunan Draf PR di M3

Tujuan: menyusun rencana pengadaan berdasarkan kebutuhan yang perlu
dipenuhi, kemudian meneruskannya ke alur modul Pembelian sesuai proses
dan persetujuan yang berlaku.

``` text
M2 — INFORMASI KEBUTUHAN
          |
          v
M3 — PERENCANAAN PENGADAAN
          |
          v
PENYUSUNAN DRAF PR
          |
          v
PENGAJUAN / PERSETUJUAN PR
          |
          v
MODUL PEMBELIAN
          |
          v
PEMILIHAN SUPPLIER DAN PROSES PEMBELIAN
```

Dengan demikian, tombol **Ajukan ke Gudang** tidak seharusnya hanya
membuat Draf PR. Tombol atau aksi integrasi Gudang harus menjalankan
proses penyediaan hasil kalkulasi yang telah disetujui untuk dibaca
Gudang. Draf PR tetap menjadi bagian dari alur M3 menuju Pembelian.

## 7. Hal yang Perlu Dikonfirmasi Bersama Tim

Sebelum implementasi, sepakati hal-hal berikut:

1.  Role yang berwenang menyetujui hasil kalkulasi M2.
2.  Halaman, tombol, dan route yang digunakan untuk persetujuan.
3.  Mekanisme penentuan versi resmi yang dibaca Gudang.
4.  ID master komoditas dan satuan standar yang digunakan bersama.
5.  Elemen data dan format integrasi antara M2 dan Gudang.
6.  Apakah aksi pengiriman ke Gudang dilakukan otomatis setelah
    persetujuan atau melalui tombol khusus.
7.  Status dan mekanisme tindak lanjut yang dikembalikan Gudang, jika
    ada.

## 8. Batasan Perubahan Modul

-   M2 tetap bertanggung jawab atas perhitungan kebutuhan dan neraca
    komoditas.
-   Gudang bertanggung jawab atas pencocokan dengan stok aktual dan
    saran tindak lanjut.
-   M3 bertanggung jawab atas perencanaan pengadaan dan penyusunan PR.
-   Modul Pembelian bertanggung jawab atas pemilihan supplier dan
    transaksi pembelian.
-   Perubahan internal Gudang, M2, Unit Usaha, atau Pembelian harus
    dikoordinasikan dengan pemilik modul masing-masing.
-   Jangan mengubah struktur database atau menjalankan operasi
    destruktif tanpa audit dan persetujuan terlebih dahulu.

## 9. Kesimpulan

Hasil kalkulasi M2 yang berstatus `disetujui` diusulkan menjadi sumber
data resmi bagi Gudang. Gudang membaca hasil tersebut, mencocokkan
surplus/defisit antarentitas dengan stok aktual, dan menampilkan saran
tindak lanjut tanpa menghitung ulang kebutuhan.

Di sisi lain, M3 menggunakan informasi kebutuhan untuk menyusun
perencanaan pengadaan dan Draf PR yang diteruskan ke modul Pembelian.
Integrasi hasil kalkulasi ke Gudang dan pembuatan Draf PR merupakan dua
proses berbeda dan tidak boleh disamakan.

1. Persetujuan hasil kalkulasi M2 dilakukan oleh Manajer Desa setelah memeriksa hasil perhitungan. Operator hanya menjalankan kalkulasi.

2. Tombol dan proses persetujuan ditempatkan di modul M2 (Kalkulasi Kebutuhan), karena pengesahan merupakan bagian dari proses kalkulasi, bukan tanggung jawab M3.

3. Hasil kalkulasi yang sudah disetujui otomatis tersedia untuk dibaca Gudang. Tidak diperlukan tombol pengiriman khusus, selama integrasi menggunakan sumber data bersama yang sudah disepakati.

4. Jika terdapat kalkulasi baru, hasil lama yang sudah disetujui tetap menjadi referensi Gudang sampai hasil baru disetujui.

5. Gudang hanya membaca hasil resmi tersebut, mencocokkan surplus/defisit antarentitas dengan stok aktual, dan memberikan saran tindak lanjut. Gudang tidak menghitung ulang kebutuhan.
