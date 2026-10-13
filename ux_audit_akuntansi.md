# 📋 UX Audit — Modul Akuntansi KDMP-v2

> Dibuat: 2026-09-29 · Dibaca dari semua file view + controller
> **PIVOT WARNING:** User bukan hanya koperasi — BUMDes, kelompok tani, UMKM, dll.

---

## Peta Halaman (Sidebar Order)

```
Akuntansi & Laporan
  1. Saldo Awal          → /akuntansi/saldo-awal
  2. Jurnal              → /akuntansi/jurnal
  3. Aset Tetap          → /akuntansi/aset-tetap
  4. Tutup Bulan         → /akuntansi/tutup-bulan
  5. Tutup Tahun         → /akuntansi/tutup-tahun
  6. Neraca Saldo        → /akuntansi/laporan/neraca-saldo
  7. Buku Besar          → /akuntansi/laporan/buku-besar
  8. Neraca              → /akuntansi/laporan/neraca
  9. Laba Rugi           → /akuntansi/laporan/laba-rugi
 10. Arus Kas            → /akuntansi/laporan/arus-kas
```

---

## Detail Per Halaman

### 1. 🆕 Saldo Awal — `/akuntansi/saldo-awal`

**Status:** ✅ Baru dibuat, sudah di-redesign (dua kolom)

| Aspek | Kondisi |
|---|---|
| UX Desain | Dua kolom: Biru (Debet/Aset) vs Amber (Kredit/Modal) |
| Input D/K | ✅ Otomatis dari `posisi_normal` COA — user tidak perlu pilih |
| Live balance | ✅ Hitung selisih real-time |
| Panduan | ✅ Ada kotak penjelasan konsep atas |
| Filter tahun | ✅ Ada |
| Tampilkan tersimpan | ✅ Tabel saldo tersimpan setelah form |
| Idempoten | ✅ Jika ada JOP lama, dibalik otomatis |
| Warna tombol | ✅ Sudah pakai `bg-merah-500` |

**Masalah yang masih ada:**
- [ ] Baris template Blade untuk JS `optDebet`/`optKredit` mungkin menghasilkan baris panjang — perlu test apakah rendering JS-nya benar (template literal multiline di Blade bisa bermasalah)
- [ ] Seeder JOP harus dijalankan dulu (`php artisan db:seed --class=TransaksiTemplateSeeder`)

---

### 2. 📒 Jurnal — `/akuntansi/jurnal`

**Status:** ⚠️ Tidak ada view khusus — hanya resource route `only(['index','show'])`

| Aspek | Kondisi |
|---|---|
| View file | ❌ **Tidak ditemukan** `views/akuntansi/jurnal/*.blade.php` |
| Route | `akuntansi.jurnal.index` dan `akuntansi.jurnal.show` terdaftar |
| Sidebar | ✅ Ada link ke `akuntansi.jurnal.index` |

**Masalah:**
- [ ] ❌ KRITIS: Halaman jurnal kemungkinan akan `404` atau error karena view tidak ada
- [ ] Perlu dibuat view `resources/views/akuntansi/jurnal/index.blade.php`
- [ ] Jurnal harus bisa dilihat per periode, bisa drill-down ke detail per jurnal (show)

---

### 3. 🏢 Aset Tetap — `/akuntansi/aset-tetap`

**Status:** ⚠️ Ada sebagai route resource tapi belum diaudit viewnya

| Aspek | Kondisi |
|---|---|
| Route | Resource CRUD terdaftar |
| View | Belum dicek |
| COA | Perlu akun Aset Tetap dan Akumulasi Penyusutan di COA |

---

### 4. 🔒 Tutup Bulan — `/akuntansi/tutup-bulan`

**Status:** ⚠️ Sebagian bug CSS, logika sudah bagus

| Aspek | Kondisi |
|---|---|
| UX Desain | Form pilih tahun+bulan → tabel 8 validasi → tombol tutup |
| Tombol Cek Validasi | ❌ **`bg-primary-600`** → invisible! Harus diganti `bg-merah-500` |
| Tombol Tutup Bulan | ✅ Pakai `bg-red-600` (Tailwind default, bukan design system → mungkin OK) |
| Validasi | ✅ 8 item, badge Lulus/Gagal |
| Guard | ✅ Tombol tutup hanya muncul jika semua Lulus |
| Notifikasi | ⚠️ Pakai `border-emerald-300`/`border-red-300` — bukan design system (sawah/merah) |
| Wording | ⚠️ "Koperasi" tidak disebut tapi tidak ada konteks entitas di header |

**Yang perlu diperbaiki:**
- [ ] `bg-primary-600` → `bg-merah-500` pada tombol "Cek Validasi"
- [ ] `focus:ring-primary-400` → `focus:ring-merah-400` di input/select
- [ ] Alert color: `emerald` → `sawah`, `red` → `merah`

---

### 5. 🔐 Tutup Tahun — `/akuntansi/tutup-tahun`

**Status:** ⚠️ Sama — bug CSS, tapi logika paling kompleks dan bagus

| Aspek | Kondisi |
|---|---|
| UX Desain | Grid 5 kolom: kiri (pra-kondisi + preview L/R) \| kanan (form finalisasi) |
| Tombol Cek Status | ❌ **`bg-primary-600`** → invisible! |
| Pra-kondisi | ✅ Checklist yang jelas, badge Siap/Belum |
| Preview Laba/Rugi | ✅ Total pendapatan, biaya, laba bersih |
| Guard | ✅ Checkbox konfirmasi wajib dicentang sebelum bisa submit |
| Tombol Finalisasi | ✅ `bg-red-600`, disabled by default |
| ⚠️ PIVOT | ❌ Hardcode "Modal/SHU" di deskripsi — seharusnya tergantung `jenis_unit` |
| Wording | ❌ "Laba/Rugi dipindahkan ke **Modal/SHU**" → SHU hanya untuk koperasi |

**Yang perlu diperbaiki:**
- [ ] `bg-primary-600` → `bg-merah-500` pada "Cek Status"
- [ ] Ganti wording "Modal/SHU" → "Modal / Laba Ditahan" (atau dinamis dari `jenis_unit`)
- [ ] Alert color: `emerald` → `sawah`

---

### 6. 📊 Neraca Saldo — `/akuntansi/laporan/neraca-saldo`

**Status:** ✅ Paling lengkap dan konsisten dari sisi CSS

| Aspek | Kondisi |
|---|---|
| UX Desain | Filter (tahun+bulan) → tabel flat semua akun |
| Warna tombol | ✅ `bg-merah-500` |
| Balance check | ✅ Alert warning jika tidak balance |
| Link silang | ✅ Quick link ke Buku Besar, Laba/Rugi, Neraca |
| Empty state | ✅ Pesan yang jelas |
| Footer total | ✅ Ada |

**Kekurangan minor:**
- [ ] Tidak ada kolom "Saldo Awal" bulan → user tidak tahu saldo pembuka

---

### 7. 📖 Buku Besar — `/akuntansi/laporan/buku-besar`

**Status:** ✅ Fungsional, baik

| Aspek | Kondisi |
|---|---|
| UX Desain | Filter (tahun+bulan+**pilih akun**) → tabel mutasi kronologis |
| Warna tombol | ✅ `bg-merah-500` |
| Saldo berjalan | ✅ Ada kolom running balance |
| Baris saldo awal | ✅ Baris pertama = saldo dari bulan lalu |
| Link silang | ✅ Quick link ke Neraca Saldo, Laba/Rugi |
| Empty state | ✅ Dua kondisi: belum pilih akun, atau akun kosong |

**Kekurangan:**
- [ ] Tidak bisa pilih **range** bulan — hanya satu bulan
- [ ] Tidak ada navigasi bulan cepat (prev/next)

---

### 8. ⚖️ Neraca — `/akuntansi/laporan/neraca`

**Status:** ✅ Fungsional, layout dua kolom bagus

| Aspek | Kondisi |
|---|---|
| UX Desain | Dua kolom: Aktiva (kiri) \| Kewajiban + Modal (kanan) |
| Balance badge | ✅ Total passiva berubah warna: hijau (balance) / merah (tidak) |
| Alert tidak balance | ✅ Ada pesan penjelasan |
| Wording | ⚠️ "Pastikan Laba/Rugi tahun berjalan sudah ditutup ke akun **Modal**" — ok generik |
| Filter | ✅ Tahun + bulan |

**Kekurangan:**
- [ ] Aktiva tidak dikelompokkan (Lancar vs Tidak Lancar) — flat list
- [ ] Modal tidak menyertakan Laba Berjalan tahun ini (perlu tutup tahun dulu)

---

### 9. 📈 Laba Rugi — `/akuntansi/laporan/laba-rugi`

**Status:** ✅ Fungsional, struktur laporan benar

| Aspek | Kondisi |
|---|---|
| UX Desain | Laporan vertikal single-column, max-w-2xl |
| Seksi | Pendapatan → HPP → Laba Kotor → Biaya Operasional → Laba Operasi → Non-Op → Laba Bersih |
| Filter | ✅ Tahun + Bulan Dari + s.d. Bulan (range) |
| Warna laba | ✅ Hijau jika positif, merah jika negatif |
| ⚠️ PIVOT | ❌ "Koperasi mengalami kerugian" hardcode di baris 147 |

**Yang perlu diperbaiki:**
- [ ] Ganti "Koperasi mengalami kerugian" → "Entitas mengalami kerugian pada periode ini"

---

### 10. 💧 Arus Kas — `/akuntansi/laporan/arus-kas`

**Status:** ❓ Ada di sidebar tapi view-nya kemungkinan `placeholder.blade.php`

| Aspek | Kondisi |
|---|---|
| Route | `akuntansi.laporan.arus-kas` terdaftar |
| View | Kemungkinan menampilkan placeholder |
| Implementasi | Belum ada — perlu dibuat dari nol |

---

## Ringkasan Bug/Masalah (Prioritas)

### 🔴 KRITIS — Harus Diperbaiki
| # | Halaman | Masalah |
|---|---|---|
| 1 | Jurnal | View tidak ada → 404 |
| 2 | Tutup Bulan | Tombol "Cek Validasi" invisible (`bg-primary-600`) |
| 3 | Tutup Tahun | Tombol "Cek Status" invisible (`bg-primary-600`) |

### 🟡 PENTING — Perlu Diperbaiki
| # | Halaman | Masalah |
|---|---|---|
| 4 | Laba Rugi | Hardcode "Koperasi" (pivot violation) |
| 5 | Tutup Tahun | Hardcode "SHU" (pivot violation) |
| 6 | Saldo Awal | Perlu test JS template literal Blade |
| 7 | Saldo Awal | Seeder JOP harus dijalankan |

### 🔵 MINOR — Boleh Ditunda
| # | Halaman | Masalah |
|---|---|---|
| 8 | Tutup Bulan/Tahun | Alert pakai warna `emerald`/`red` bukan `sawah`/`merah` |
| 9 | Buku Besar | Tidak bisa range bulan |
| 10 | Neraca | Aktiva tidak dikelompokkan Lancar/Tidak Lancar |
| 11 | Arus Kas | Belum diimplementasi sama sekali |

---

## Alur Penggunaan Normal (Happy Path)

```
SETUP AWAL (sekali saja per tahun buku):
  1. Saldo Awal → input saldo pembuka

OPERASIONAL (setiap hari/transaksi):
  2. Transaksi (Pembelian/Penjualan/Kas) → jurnal otomatis via SP
  3. Jurnal → lihat/verifikasi

AKHIR BULAN:
  4. Tutup Bulan → cek 8 validasi → kunci periode

AKHIR TAHUN:
  5. Laba Rugi → cek P&L setahun
  6. Tutup Tahun → finalisasi, buat jurnal penutup otomatis
  7. Neraca → verifikasi akhir

LAPORAN (kapan saja):
  → Neraca Saldo (untuk audit)
  → Buku Besar (untuk trace per akun)
  → Neraca (posisi keuangan)
  → Laba Rugi (kinerja)
```
