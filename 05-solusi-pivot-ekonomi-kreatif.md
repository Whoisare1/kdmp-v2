# Solusi Perubahan Arsitektur & Modul — Pivot KDMP → Ekonomi Kreatif Pati

> Dokumen ini adalah kelanjutan teknis dari `PROMPT-KONTEKS-PIVOT.md`.
> Isinya: solusi konkret per modul untuk menjawab masalah bahwa **KDMP-Sembako
> punya model bisnis yang tidak otomatis berlaku untuk BUMDes, kelompok tani,
> dan kelompok pengrajin**, meski infrastruktur teknis (multi-tenant, service
> layer) tetap dipakai ulang.
>
> Status tag di tiap bagian:
> **[SUDAH DIPUTUSKAN]** — logika sudah pasti dan siap diimplementasi.
> **[PERLU KEPUTUSAN TIM]** — desain diusulkan di sini, tapi butuh konfirmasi
> sebelum implementasi karena ada ruang interpretasi.

---

## 0. Prinsip Dasar Perubahan

Kesalahan yang harus dihindari sejak awal: **jangan anggap semua `jenis_unit`
punya kebutuhan model bisnis yang sama.** Rombak KDMP jadi platform ekonomi
desa itu bukan sekadar rename label UI — ada tiga titik di mana model
matematis dan model hukum yang lama **memang khusus untuk koperasi sembako**
dan tidak berlaku untuk jenis unit lain:

| Titik | Masalah | Modul terdampak |
|---|---|---|
| 1 | Model kebutuhan itu model **konsumsi pangan per kapita** | M2, M3 |
| 2 | COA dan pendapatan diasumsikan **satu bagan akun untuk semua tenant** | M0, M6, M10 |
| 3 | Distribusi laba (SHU) itu **kewajiban hukum koperasi**, bukan generik | M8 |

Solusi di bawah menjawab ketiganya. Modul lain (M1, M4, M5, M7, M9) hanya
butuh penyesuaian ringan atau tidak berubah sama sekali — dijelaskan di
bagian masing-masing kenapa.

**Konsep kunci baru: `jenis_unit` bukan sekadar label, tapi *dispatcher*.**
Field ini menentukan strategi/alur mana yang dijalankan di M2, M3, M6, dan
M8. Ini bukan satu field ekstra kosmetik — ini adalah field yang paling
menentukan seluruh pivot.

```
enum jenis_unit {
    koperasi            // alur lama, tidak berubah
    bumdes              // alur laba-ke-PADes
    kelompok_tani        // alur produksi + pangan (masih pakai M2/M3 pangan)
    kelompok_pengrajin   // alur produksi + non-pangan (skip M2/M3 pangan)
    umkm_lain            // fallback: skip M2/M3, skip SHU
}
```

---

## 1. M0 — Master & Periode

**Masalah:** `master_coa` didokumentasikan sebagai "global, dipakai semua
desa". Itu tidak bisa dipertahankan karena BUMDes butuh akun ke arah PADes,
sementara koperasi butuh 5 akun SHU (331-334, 2116) yang tidak relevan untuk
jenis unit lain.

**Solusi [SUDAH DIPUTUSKAN struktur, PERLU KEPUTUSAN TIM isi akunnya]:**

1. `master_coa` tetap satu tabel, tapi tambah kolom `berlaku_untuk_jenis_unit`
   (nullable array/JSON, atau tabel pivot `coa_jenis_unit` kalau butuh
   many-to-many). Akun 331-334 dan 2116 ditandai `['koperasi']` saja.
2. Saat provisioning tenant baru, sistem hanya menampilkan/mengaktifkan akun
   yang cocok dengan `jenis_unit` tenant tersebut. Akun yang tidak relevan
   tetap ada di tabel master (supaya laporan konsolidasi pusat tetap bisa
   agregasi lintas tenant), tapi disembunyikan dari transaksi harian tenant
   yang tidak memakainya.
3. Tambah field `jenis_unit` di `koperasi_desa` (enum di atas).

**Perubahan lain di M0:** tidak ada. `periode_akuntansi`, `master_kas_bank`,
`gudang`, `master_barang`, `master_pihak` semuanya generik, berlaku untuk
semua jenis unit tanpa perubahan.

**User/aktor:** tidak berubah — admin pusat kelola master global, pengurus
unit kelola master lokal.

---

## 2. M1 — Survei

**Masalah:** ringan. Tiga jenis data yang dikumpulkan (demografi, potensi
produksi, harga pasar) — dua di antaranya (demografi, harga pasar) generik.
Yang perlu disesuaikan hanya **potensi produksi**.

**Solusi [PERLU KEPUTUSAN TIM soal skema field]:**

`ketersediaan_komoditas` perlu dipecah maknanya tergantung kategori produk
di `master_barang`:

- Kategori **pangan**: field tetap seperti sekarang — per bulan panen, dalam
  satuan berat/volume.
- Kategori **non-pangan (kerajinan/UMKM)**: field yang dikumpulkan berubah
  makna jadi **kapasitas produksi** — jumlah unit yang bisa diproduksi per
  bulan, bukan "panen". Struktur tabel bisa dipertahankan (kolom `jumlah`,
  `bulan`, `komoditas_id`), tapi label form dan cara STT bertanya ke pengurus
  desa berbeda tergantung kategori produk yang mereka isi.

Tidak perlu tabel baru — cukup kondisional di form berdasarkan kategori
produk yang dipilih.

**User/aktor:** tidak berubah — pengurus desa isi via STT/manual, admin
verifikasi.

---

## 3. M2 — Kalkulasi Kebutuhan

**Masalah paling serius kedua.** Formula:

```
Kebutuhan(produk, desa, bulan) =
    SUM(penduduk x per_kapita_harian x hari) x faktor_musiman
```

adalah model konsumsi pangan pokok. Tidak ada makna matematis "kebutuhan
kerajinan per kapita per hari" — dipaksakan, hasilnya nol atau angka yang
tidak berarti, dan M3 di bawahnya akan mewarisi kerusakan itu.

**Solusi [SUDAH DIPUTUSKAN — modul ini jadi kondisional per kategori]:**

```
JIKA kategori_produk == 'pangan':
    -> jalankan alur M2 yang sudah ada, tidak berubah
    -> hasil disimpan ke kebutuhan_komoditas seperti sekarang

JIKA kategori_produk == 'non_pangan' (kerajinan/UMKM):
    -> SKIP M2 sepenuhnya
    -> M3 dapat input dari sumber lain (lihat solusi M3 di bawah)
```

Field `kebutuhan_komoditas` untuk produk non-pangan **sengaja dikosongkan**,
bukan diisi angka default nol yang terlihat seperti hasil kalkulasi valid.
UI harus jelas menampilkan "Modul kalkulasi kebutuhan tidak berlaku untuk
kategori ini" — supaya tidak disalahartikan sebagai data yang hilang atau
bug.

**User/aktor:** tidak berubah untuk kategori pangan. Untuk kategori
non-pangan, tidak ada aktor yang menjalankan M2 sama sekali — langsung ke
M3.

---

## 4. M3 — Perencanaan Pengadaan

**Masalah:** rumus `Kebutuhan Pengadaan Bersih = Kebutuhan(M2) - Stok -
OnOrder + SafetyStock` adalah model **restock retail** (beli barang jadi
untuk dijual ulang berdasarkan prediksi konsumsi). Kerajinan/UMKM itu
**supply-driven** — dibatasi kapasitas produksi pengrajin dan
permintaan/pesanan pasar, bukan konsumsi warga desa sendiri.

**Solusi [PERLU KEPUTUSAN TIM — ini modul baru, bukan modifikasi kecil]:**

Dua jalur paralel di M3, dipilih berdasarkan kategori produk:

```
Jalur A — Pangan (alur lama, tidak berubah):
    PR = Kebutuhan(M2) - Stok(M4) - OnOrder(M5) + SafetyStock

Jalur B — Non-pangan / produksi (BARU):
    Rencana Produksi = Kapasitas Produksi (dari M1, hasil survei
                        kelompok pengrajin)
                        - Stok jadi yang belum terjual (M4)
                        + Estimasi permintaan/pesanan (input manual
                        pengurus, atau dari data penjualan M6 bulan lalu)

    Output: bukan "permintaan_pengadaan" (PR beli barang), tapi dokumen
    baru "rencana_produksi" — isinya target produksi per pengrajin/bulan,
    bukan daftar barang yang mau dibeli.
```

Dokumen `rencana_produksi` ini **tidak masuk ke M5 (Pembelian)** seperti PR
pangan — dia jadi acuan informal untuk pengurus kelompok memantau target,
tanpa memicu alur approval/jurnal apa pun (sama seperti M1-M3 pangan yang
juga tanpa jurnal).

**Keputusan yang perlu tim ambil:** apakah `rencana_produksi` ini benar-benar
dibutuhkan untuk fase awal pivot, atau untuk kelompok pengrajin cukup
langsung skip M2+M3 dan mulai dari M5 (beli bahan baku) → produksi manual di
luar sistem → M6 (jual barang jadi). Yang kedua lebih murah dibangun dan
mungkin cukup untuk versi pertama; jalur A di atas bisa menyusul kalau
memang dibutuhkan.

**User/aktor:** pengurus unit — sama untuk yang kelola jalur A; untuk jalur
B (kalau dibangun), pengurus kelompok yang input target/kapasitas produksi.

---

## 5. M4 — Gudang (Moving Average HPP)

**Masalah:** tidak ada. HPP rata-rata bergerak berlaku untuk barang fisik
apa pun yang disimpan dan punya qty — sembako atau kerajinan sama saja
secara akuntansi persediaan.

**Solusi [SUDAH DIPUTUSKAN — tidak ada perubahan logika].** `StokService`,
rumus HPP, kartu stok, stock opname — semua dipakai ulang apa adanya.

**User/aktor:** petugas gudang, tidak berubah.

---

## 6. M5 — Pembelian

**Masalah ringan:** jalur cepat "petani datang, ditimbang, langsung bayar"
adalah model pembelian barang jadi dari produsen primer. Untuk kelompok
pengrajin, pembelian yang lebih relevan sering kali **bahan baku** (kain,
bambu, cat), bukan barang jadi dari petani.

**Solusi [PERLU KEPUTUSAN TIM soal penamaan, logika tidak berubah]:**

Alur PR→Pembelian→GRN dan jalur cepat dipertahankan apa adanya secara
teknis — `master_barang` sudah cukup generik untuk menampung "bahan baku"
sebagai item. Yang perlu diubah hanya:

- Label "Nota Pembelian Petani" digeneralisasi jadi "Nota Pembelian Cepat"
  (tunai, tanpa PO), supaya tidak terikat konteks petani/sembako.
- Kode transaksi `BPT` (Beli dari Petani Tunai) tetap dipakai apa adanya
  sebagai kode teknis — tidak perlu rename di database, cukup label UI-nya.

**User/aktor:** kasir/petugas lapangan, tidak berubah.

---

## 7. M6 — Penjualan

**Masalah:** model M6 adalah POS retail — toko fisik jual ke warga, dengan
akun pendapatan dipisah per status anggota dan unit usaha (411-414, khusus
sembako/apotek). Kelompok pengrajin sering jual B2B/grosir/luar desa, bukan
eceran ke warga desa sendiri.

**Solusi [PERLU KEPUTUSAN TIM — akun pendapatan perlu diperluas]:**

1. Tabel akun pendapatan (411-414) diperluas jadi dinamis per `unit_usaha`
   dan `jenis_unit`, bukan hardcode 4 baris. Struktur `master_detail_transaksi`
   yang sudah ada (menerjemahkan `PENDAPATAN_UNIT` jadi akun konkret) sudah
   mendukung ini — tinggal ditambah baris konfigurasi untuk unit usaha baru
   (mis. "Kerajinan — Anggota", "Kerajinan — Non-Anggota", atau kalau
   kelompok pengrajin tidak punya konsep "anggota" sama sekali, cukup satu
   akun pendapatan tanpa split).
2. M6 tetap dipakai untuk unit yang memang jual eceran ke warga (toko
   sembako, kios). Untuk kelompok pengrajin yang jual B2B/grosir, transaksi
   tetap lewat M6 tapi dengan `is_pembeli_anggota` selalu `false` dan tanpa
   split akun anggota/non-anggota — cukup satu jalur penjualan generik.

**Belum diputuskan / di luar cakupan dokumen ini:** kalau kelompok pengrajin
butuh integrasi marketplace eksternal (bukan penjualan langsung dicatat di
sistem), itu kebutuhan terpisah yang tidak dibahas di sini.

**User/aktor:** kasir unit — sama untuk yang punya jalur jual langsung.

---

## 8. M7 — Konsinyasi Antar Desa

**Masalah:** tidak ada. Titip-jual dan barter antar desa berlaku untuk
barang apa pun, sembako atau kerajinan — M7 tidak peduli asal-usul angka
kebutuhan, dia cuma memindahkan barang dan jurnal antar dua tenant.

**Solusi [SUDAH DIPUTUSKAN — tidak ada perubahan logika].**
`KonsinyasiService`, alur KTK/KJL/KAP/KST/KTR/KRT — dipakai ulang apa
adanya. Marketplace pencocokan surplus-defisit (`permintaan_barter`,
`penawaran_barter`) juga langsung berlaku untuk komoditas desa apa pun tanpa
modifikasi.

**User/aktor:** pengurus desa pengirim/penerima, tidak berubah.

---

## 9. M8 — Akuntansi

**Masalah paling serius.** SHU (Sisa Hasil Usaha) dan distribusinya ke
Cadangan Umum (331), Dana Pendidikan (332), Dana Sosial (333), Dana
Pengurus (334), Dana Pembagian SHU (2116) adalah **kewajiban hukum koperasi**
(UU 25/1992), bukan model akuntansi generik. BUMDes tunduk ke Permendagri
(laba ke PADes, bukan SHU anggota). Kelompok tani/pengrajin/UMKM pada
umumnya tidak punya kewajiban distribusi laba formal semacam ini sama
sekali.

**Solusi [SUDAH DIPUTUSKAN struktur — pisahkan Jurnal & Buku Besar dari
Distribusi Laba]:**

**Lapis 1 — Jurnal & Buku Besar (universal, tidak berubah untuk semua
`jenis_unit`):**
Pencatatan double-entry, neraca, laba rugi, 8 validasi tutup bulan.
`JurnalService`, semua kode transaksi harian (JTW, KTK, dll) — tetap jalan
apa adanya. Ini murni akuntansi dasar, bukan spesifik koperasi.

**Lapis 2 — Tutup Tahun (bercabang per `jenis_unit`, via strategy
pattern):**

```php
// app/Services/Finance/TutupTahunService.php
public function posting(int $koperasiId, int $tahun): void
{
    $jenisUnit = KoperasiDesa::find($koperasiId)->jenis_unit;

    $strategy = match ($jenisUnit) {
        'koperasi' => new TutupTahunKoperasiStrategy(),   // SHU lengkap, tidak berubah
        'bumdes'   => new TutupTahunBumdesStrategy(),     // Laba -> Laba Ditahan/PADes
        default    => new TutupTahunUmumStrategy(),        // Laba -> Laba Ditahan saja
    };

    $strategy->tutup($koperasiId, $tahun);
}
```

Ketiga strategy sama-sama pakai jurnal penutup Pendapatan/Biaya → Ikhtisar
(811) yang sudah ada — yang beda cuma baris terakhir:

| Strategy | Ikhtisar (811) dikreditkan ke |
|---|---|
| Koperasi | 5 akun SHU (331, 332, 333, 334, 2116) sesuai `config_shu` — tidak berubah |
| BUMDes | 1 akun: Laba Ditahan / PADes |
| Kelompok tani, pengrajin, UMKM lain | 1 akun: Laba Ditahan |

`config_shu` untuk BUMDes/kelompok dikosongkan (null) — strategy non-koperasi
**skip langkah pembagian SHU sepenuhnya**, tidak mencoba membaca config yang
tidak ada.

`is_anggota` **tidak dihapus** dari skema — untuk BUMDes/kelompok, flag ini
tetap boleh dipakai untuk pembedaan harga member vs non-member di M6 kalau
relevan bagi mereka, hanya saja dia tidak lagi memicu hak atas SHU.

**Field baru yang dibutuhkan:** `strategi_tutup_tahun` (diturunkan otomatis
dari `jenis_unit`, tidak perlu kolom terpisah — cukup fungsi mapping di
service).

**User/aktor:** admin unit yang eksekusi tutup tahun — tidak berubah secara
prosedur, hanya hasil jurnalnya yang berbeda tergantung `jenis_unit`.

---

## 10. M9 — Piutang, Hutang, Kas

**Masalah:** tidak ada. Piutang/hutang/kas adalah konsep akuntansi generik,
berlaku untuk semua jenis unit yang punya transaksi kredit atau kas masuk
keluar.

**Solusi [SUDAH DIPUTUSKAN — tidak ada perubahan logika].**

---

## 11. M10 — Pelaporan

**Masalah:** tidak ada langsung, tapi hasilnya bergantung penuh pada M0
(COA). Kalau M0 sudah diperbaiki (COA per-jenis_unit), M10 otomatis ikut
menyesuaikan lewat filter `master_coa.kelompok` yang sudah ada — tidak
perlu logika baru di M10 sendiri.

**Solusi [SUDAH DIPUTUSKAN — tergantung penyelesaian M0, tidak ada
perubahan mandiri di M10].**

---

## 12. Ringkasan Prioritas Implementasi

| Prioritas | Modul | Alasan |
|---|---|---|
| 1 (blocker) | M0 — COA per jenis_unit | Semua modul turunan (M6, M8, M10) bergantung pada ini |
| 2 (blocker) | M8 — Strategy pattern tutup tahun | Risiko hukum/akuntansi kalau salah, harus benar dari awal |
| 3 | M2/M3 — Jalur kondisional pangan vs non-pangan | Kompleks, tapi bisa disederhanakan dulu (lihat catatan M3) untuk versi awal |
| 4 | M6 — Akun pendapatan dinamis | Menyusul setelah M0 selesai |
| 5 | M1, M5 — Penyesuaian label/field ringan | Bisa paralel, risiko rendah |
| — | M4, M7, M9 | Tidak perlu perubahan |

---

## 13. Pertanyaan Terbuka untuk Tim

Sebelum mulai coding, ini yang masih butuh keputusan eksplisit — jangan
diasumsikan sambil jalan:

1. Untuk kelompok pengrajin, apakah M2/M3 versi non-pangan (jalur B di
   bagian 4) benar-benar dibangun di fase ini, atau kelompok pengrajin cukup
   skip M2/M3 sepenuhnya dulu untuk MVP?
2. Apakah BUMDes butuh field tambahan di luar `koperasi_desa` yang sudah ada
   (mis. relasi ke pemerintah desa untuk pelaporan PADes), atau cukup
   `jenis_unit = bumdes` saja?
3. Siapa yang isi `config_shu` kosong untuk non-koperasi — dibiarkan null
   permanen, atau ada UI khusus yang menjelaskan ke pengurus BUMDes/kelompok
   kenapa field itu tidak ada buat mereka?
