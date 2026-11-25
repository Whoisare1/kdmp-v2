<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Tenant\Entitas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoPeranService
{
    /**
     * Definisi 7 peran utama berdasarkan urutan alur bisnis skripsi (Hulu ke Hilir).
     */
    public static function katalogPeran(): array
    {
        return [
            'surveyor' => [
                'kode' => 'surveyor',
                'urutan' => 1,
                'tahap' => 'Tahap 1 — Hulu: Survei & Pemetaan',
                'nama' => 'Surveyor Lapangan',
                'ikon' => '📋',
                'warna_badge' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                'warna_header' => 'from-indigo-600 to-indigo-800',
                'warna_kartu' => 'border-indigo-200 hover:border-indigo-500',
                'ringkasan' => 'Bertugas mengumpulkan data primer di desa: mendata baseline KK per RT, kelompok produsen tani/ekraf, menyebarkan link survei publik ke warga, serta memvalidasi hasil survei sebelum disinkronkan ke Manajer.',
                'data_dikelola' => [
                    'Sesi Survei & Token Link Publik (RT, Produsen, Masyarakat)',
                    'Data Baseline RT & Demografi Warga',
                    'Data Kelompok Produsen Desa (Tani, Ternak, Ekraf)',
                    'Validasi Kelengkapan Survei (Baseline vs Realisasi)',
                ],
                'data_dibatasi' => [
                    'Stok fisik & opname gudang',
                    'Order pembelian (PO) & kasir penjualan (POS)',
                    'Buku kas, piutang/hutang, dan laporan akuntansi',
                ],
                'grup_sidebar' => ['Survei & Pemetaan'],
                'alur_kerja' => [
                    [
                        'langkah' => 1,
                        'judul' => 'Buat & Kelola Sesi Survei',
                        'deskripsi' => 'Buka periode survei baru dan terbitkan link/token publik untuk RT, Produsen, dan Warga.',
                        'route' => 'survei.sesi.index',
                        'ikon' => '🗓️',
                    ],
                    [
                        'langkah' => 2,
                        'judul' => 'Data Baseline RT & Produsen',
                        'deskripsi' => 'Input data kuota KK tiap RT serta daftar kelompok produsen komoditas lokal desa.',
                        'route' => 'survei.rt.index',
                        'route_sekunder' => 'survei.produsen.index',
                        'label_sekunder' => 'Produsen Desa',
                        'ikon' => '🌾',
                    ],
                    [
                        'langkah' => 3,
                        'judul' => 'Validasi & Sinkronisasi Survei',
                        'deskripsi' => 'Verifikasi isian survei warga & produsen, lalu sinkronkan ke modul Kalkulasi Perencanaan.',
                        'route' => 'survei.validasi.index',
                        'ikon' => '✅',
                    ],
                    [
                        'langkah' => 4,
                        'judul' => 'Dashboard Monitoring Survei',
                        'deskripsi' => 'Pantau persentase keterisian survei lapangan dan bank pertanyaan.',
                        'route' => 'survei.dashboard.index',
                        'route_sekunder' => 'survei.pertanyaan.index',
                        'label_sekunder' => 'Bank Pertanyaan',
                        'ikon' => '📊',
                    ],
                ],
            ],

            'manajer' => [
                'kode' => 'manajer',
                'urutan' => 2,
                'tahap' => 'Tahap 2 — Analisis & Perencanaan Desa',
                'nama' => 'Manajer / Perencana Koperasi',
                'ikon' => '🧭',
                'warna_badge' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                'warna_header' => 'from-cyan-600 to-teal-800',
                'warna_kartu' => 'border-cyan-200 hover:border-cyan-500',
                'ringkasan' => 'Pimpinan operasional koperasi desa. Menganalisis ketahanan pangan dari hasil survei (kebutuhan konsumsi vs potensi produksi lokal), memetakan komoditas Surplus/Defisit, menerbitkan Permintaan Pengadaan (PR), serta mengelola staf dan master data entitas.',
                'data_dikelola' => [
                    'Parameter Demografi, Potensi Produksi & Standar Kebutuhan',
                    'Kalkulasi Kebutuhan Komoditas & Neraca Surplus-Defisit',
                    'Perbandingan Harga Antar-Desa & Permintaan Pengadaan (PR)',
                    'Master Data Koperasi & Manajemen Staf Desa',
                ],
                'data_dibatasi' => [
                    'Terisolasi dari data internal koperasi desa lain (Multi-tenant Isolation)',
                    'Persetujuan legalitas pendaftaran entitas baru (khusus Super Admin Pusat)',
                ],
                'grup_sidebar' => ['Kalkulasi & Perencanaan', 'Master Data'],
                'alur_kerja' => [
                    [
                        'langkah' => 1,
                        'judul' => 'Parameter Demografi & Standar',
                        'deskripsi' => 'Tinjau jumlah penduduk, potensi produksi desa, dan standar konsumsi per kapita.',
                        'route' => 'perencanaan.demografi.index',
                        'route_sekunder' => 'perencanaan.standar-kebutuhan.index',
                        'label_sekunder' => 'Standar Kebutuhan',
                        'ikon' => '👥',
                    ],
                    [
                        'langkah' => 2,
                        'judul' => 'Kalkulasi Kebutuhan Komoditas',
                        'deskripsi' => 'Jalankan perhitungan otomatis total kebutuhan konsumsi warga per bulan.',
                        'route' => 'perencanaan.kebutuhan-komoditas.index',
                        'ikon' => '🧮',
                    ],
                    [
                        'langkah' => 3,
                        'judul' => 'Neraca Komoditas (Surplus / Defisit)',
                        'deskripsi' => 'Analisis selisih produksi lokal vs kebutuhan desa untuk menentukan status Surplus/Defisit.',
                        'route' => 'perencanaan.neraca-komoditas.index',
                        'route_sekunder' => 'perencanaan.laporan.index',
                        'label_sekunder' => 'Laporan Kebutuhan',
                        'ikon' => '⚖️',
                    ],
                    [
                        'langkah' => 4,
                        'judul' => 'Perbandingan Harga & Pengadaan',
                        'deskripsi' => 'Bandingkan harga penawaran desa mitra dan ajukan Permintaan Pengadaan (PR) ke Gudang.',
                        'route' => 'perencanaan.rekomendasi-pengadaan.index',
                        'route_sekunder' => 'perencanaan.perbandingan-harga.index',
                        'label_sekunder' => 'Perbandingan Harga',
                        'ikon' => '📑',
                    ],
                ],
            ],

            'admin_gudang' => [
                'kode' => 'admin_gudang',
                'urutan' => 3,
                'tahap' => 'Tahap 3 — Logistik, Gudang & Konsinyasi',
                'nama' => 'Admin Gudang & Logistik',
                'ikon' => '🏭',
                'warna_badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                'warna_header' => 'from-amber-500 to-orange-700',
                'warna_kartu' => 'border-amber-200 hover:border-amber-500',
                'ringkasan' => 'Penanggung jawab stok fisik komoditas di gudang koperasi. Mengelola penerimaan barang masuk, memantau batch & tanggal kedaluwarsa, melakukan stock opname, mencatat kerusakan barang, serta mengatur pengiriman konsinyasi (titipan/barter) ke desa mitra.',
                'data_dikelola' => [
                    'Posisi Stok Fisik Real, Batch & Tanggal Kedaluwarsa',
                    'Penerimaan Barang (GRN) & Pencocokan Surplus-Defisit',
                    'Stock Opname, Pencatatan Barang Rusak & Kartu Stok',
                    'Pengiriman, Retur & Rekonsiliasi Konsinyasi Antar-Desa',
                ],
                'data_dibatasi' => [
                    'Transaksi Kasir Penjualan (POS)',
                    'Buku Kas/Bank, Pelunasan & Penutupan Periode Akuntansi',
                ],
                'grup_sidebar' => ['Manajemen Gudang', 'Konsinyasi & Titipan'],
                'alur_kerja' => [
                    [
                        'langkah' => 1,
                        'judul' => 'Pencocokan Surplus-Defisit & Penerimaan',
                        'deskripsi' => 'Tindak lanjuti hasil neraca komoditas dan catat Penerimaan Barang masuk ke gudang.',
                        'route' => 'gudang.pencocokan-surplus-defisit.index',
                        'route_sekunder' => 'gudang.penerimaan.index',
                        'label_sekunder' => 'Penerimaan Barang',
                        'ikon' => '📥',
                    ],
                    [
                        'langkah' => 2,
                        'judul' => 'Posisi Stok, Batch & Kartu Stok',
                        'deskripsi' => 'Pantau saldo stok real-time per gudang, masa kedaluwarsa batch (FEFO/FIFO), dan mutasi Kartu Stok.',
                        'route' => 'gudang.stok.index',
                        'route_sekunder' => 'gudang.batch.index',
                        'label_sekunder' => 'Batch & Kedaluwarsa',
                        'ikon' => '📦',
                    ],
                    [
                        'langkah' => 3,
                        'judul' => 'Stock Opname & Barang Rusak',
                        'deskripsi' => 'Lakukan penyesuaian fisik berkala (Opname) dan catat kerusakan/susut komoditas.',
                        'route' => 'gudang.opname.index',
                        'route_sekunder' => 'gudang.kerusakan.index',
                        'label_sekunder' => 'Kerusakan Barang',
                        'ikon' => '🔍',
                    ],
                    [
                        'langkah' => 4,
                        'judul' => 'Konsinyasi / Barter Antar-Desa',
                        'deskripsi' => 'Kirim komoditas surplus ke koperasi desa lain dan pantau stok serta rekonsiliasi titipan.',
                        'route' => 'konsinyasi.pengiriman.index',
                        'route_sekunder' => 'konsinyasi.rekonsiliasi.index',
                        'label_sekunder' => 'Rekonsiliasi',
                        'ikon' => '🚚',
                    ],
                ],
            ],

            'pembelian' => [
                'kode' => 'pembelian',
                'urutan' => 4,
                'tahap' => 'Tahap 4A — Pengadaan & Pembelian',
                'nama' => 'Staf Pembelian (Purchasing)',
                'ikon' => '🛒',
                'warna_badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'warna_header' => 'from-emerald-600 to-green-800',
                'warna_kartu' => 'border-emerald-200 hover:border-emerald-500',
                'ringkasan' => 'Mengeksekusi pengadaan komoditas defisit dari petani lokal, pemasok, maupun koperasi desa mitra. Mengelola siklus Purchase Order (PO), persetujuan pembelian, penerimaan barang (GRN), hingga retur pembelian.',
                'data_dikelola' => [
                    'Order Pembelian (Tunai, Transfer, Kredit, Petani, Konsinyasi)',
                    'Persetujuan PO & Penerimaan Barang (GRN)',
                    'Faktur / Invoice Pembelian & Retur Pembelian',
                ],
                'data_dibatasi' => [
                    'Transaksi Kasir Penjualan (POS)',
                    'Survei Lapangan & Kalkulasi Perencanaan',
                    'Jurnal Umum, Tutup Buku & Laporan Keuangan Akuntansi',
                ],
                'grup_sidebar' => ['Pembelian & Penjualan'],
                'alur_kerja' => [
                    [
                        'langkah' => 1,
                        'judul' => 'Buat Order Pembelian Baru',
                        'deskripsi' => 'Input transaksi pengadaan komoditas dari pemasok atau petani (tunai/kredit).',
                        'route' => 'pembelian.pembelian.create',
                        'ikon' => '📝',
                    ],
                    [
                        'langkah' => 2,
                        'judul' => 'Daftar Pembelian, Approve & GRN',
                        'deskripsi' => 'Setujui PO, proses penerimaan barang ke gudang (GRN), dan cetak invoice pembelian.',
                        'route' => 'pembelian.pembelian.index',
                        'ikon' => '🛒',
                    ],
                    [
                        'langkah' => 3,
                        'judul' => 'Retur Pembelian ke Pemasok',
                        'deskripsi' => 'Proses pengembalian barang yang tidak sesuai spesifikasi (diganti uang / potong hutang).',
                        'route' => 'pembelian.retur.index',
                        'ikon' => '↩️',
                    ],
                ],
            ],

            'kasir' => [
                'kode' => 'kasir',
                'urutan' => 5,
                'tahap' => 'Tahap 4B — Penjualan & Layanan Warga',
                'nama' => 'Kasir / Penjualan (POS)',
                'ikon' => '🧾',
                'warna_badge' => 'bg-rose-100 text-rose-800 border-rose-200',
                'warna_header' => 'from-rose-600 to-pink-800',
                'warna_kartu' => 'border-rose-200 hover:border-rose-500',
                'ringkasan' => 'Melayani penjualan komoditas koperasi kepada warga anggota maupun non-anggota (secara tunai, transfer, atau kredit anggota). Setiap transaksi penjualan otomatis memotong stok gudang dan memicu jurnal pendapatan serta HPP.',
                'data_dikelola' => [
                    'Transaksi Penjualan Warga (Tunai, Transfer, Kredit Anggota)',
                    'Cetak Nota / Invoice Penjualan',
                    'Retur Penjualan dari Pembeli',
                ],
                'data_dibatasi' => [
                    'Order Pembelian (PO) & Manajemen Gudang Internal',
                    'Survei Lapangan & Kalkulasi Neraca Komoditas',
                    'Jurnal Umum, Tutup Buku & Laporan Keuangan Akuntansi',
                ],
                'grup_sidebar' => ['Pembelian & Penjualan'],
                'alur_kerja' => [
                    [
                        'langkah' => 1,
                        'judul' => 'Input Transaksi Kasir (POS)',
                        'deskripsi' => 'Buka kasir penjualan untuk melayani pembelian komoditas oleh warga anggota/non-anggota.',
                        'route' => 'penjualan.penjualan.create',
                        'ikon' => '💳',
                    ],
                    [
                        'langkah' => 2,
                        'judul' => 'Daftar Penjualan & Cetak Nota',
                        'deskripsi' => 'Pantau histori transaksi penjualan harian dan cetak struk/faktur penjualan.',
                        'route' => 'penjualan.penjualan.index',
                        'ikon' => '🧾',
                    ],
                    [
                        'langkah' => 3,
                        'judul' => 'Retur Penjualan Warga',
                        'deskripsi' => 'Catat pengembalian barang dari pelanggan beserta penyesuaian kas/piutangnya.',
                        'route' => 'penjualan.retur.index',
                        'ikon' => '🔄',
                    ],
                ],
            ],

            'akuntan' => [
                'kode' => 'akuntan',
                'urutan' => 6,
                'tahap' => 'Tahap 5 — Hilir: Keuangan & Akuntansi',
                'nama' => 'Akuntan & Bendahara Koperasi',
                'ikon' => '📒',
                'warna_badge' => 'bg-violet-100 text-violet-800 border-violet-200',
                'warna_header' => 'from-violet-600 to-purple-900',
                'warna_kartu' => 'border-violet-200 hover:border-violet-500',
                'ringkasan' => 'Mengelola pembukuan keuangan dan siklus akuntansi koperasi: memantau piutang warga & hutang suplier, memproses pelunasan, mencatat mutasi kas/bank, memverifikasi jurnal otomatis, melakukan tutup bulan/tahun (termasuk SHU), dan menyajikan laporan keuangan.',
                'data_dikelola' => [
                    'Piutang Warga, Hutang Pemasok, Pelunasan & Mutasi Kas/Bank',
                    'Saldo Awal, Jurnal Umum (Otomatis & Penyesuaian), Aset Tetap',
                    'Tutup Bulan, Tutup Tahun & Konfigurasi Pembagian SHU',
                    'Laporan Keuangan: Buku Besar, Neraca Saldo, Laba Rugi, Neraca & Arus Kas',
                ],
                'data_dibatasi' => [
                    'Entri Survei Lapangan & Validasi RT',
                    'Operasional fisik penerimaan/pengiriman gudang',
                ],
                'grup_sidebar' => ['Buku Keuangan', 'Akuntansi & Laporan'],
                'alur_kerja' => [
                    [
                        'langkah' => 1,
                        'judul' => 'Kas, Piutang, Hutang & Pelunasan',
                        'deskripsi' => 'Kelola arus kas masuk/keluar, pantau tagihan terbuka, dan catat transaksi pelunasan.',
                        'route' => 'keuangan.kas-transaksi.index',
                        'route_sekunder' => 'keuangan.pelunasan.index',
                        'label_sekunder' => 'Pelunasan Tagihan',
                        'ikon' => '💰',
                    ],
                    [
                        'langkah' => 2,
                        'judul' => 'Saldo Awal, Aset Tetap & Jurnal Umum',
                        'deskripsi' => 'Atur neraca pembuka, daftar penyusutan aset tetap, dan periksa Jurnal Umum otomatis.',
                        'route' => 'akuntansi.jurnal.index',
                        'route_sekunder' => 'akuntansi.saldo-awal.index',
                        'label_sekunder' => 'Saldo Awal',
                        'ikon' => '📒',
                    ],
                    [
                        'langkah' => 3,
                        'judul' => 'Tutup Bulan & Tutup Tahun (SHU)',
                        'deskripsi' => 'Kunci periode bulanan (termasuk penyusutan otomatis) dan jalankan tutup buku tahunan.',
                        'route' => 'akuntansi.tutup-bulan.index',
                        'route_sekunder' => 'akuntansi.tutup-tahun.index',
                        'label_sekunder' => 'Tutup Tahun',
                        'ikon' => '🔒',
                    ],
                    [
                        'langkah' => 4,
                        'judul' => 'Laporan Keuangan Koperasi',
                        'deskripsi' => 'Cetak & ekspor Neraca Saldo, Buku Besar, Laporan Laba Rugi, Neraca Posisi, dan Arus Kas.',
                        'route' => 'akuntansi.laporan.laba-rugi',
                        'route_sekunder' => 'akuntansi.laporan.neraca',
                        'label_sekunder' => 'Laporan Neraca',
                        'ikon' => '📈',
                    ],
                ],
            ],

            'super_admin' => [
                'kode' => 'super_admin',
                'urutan' => 7,
                'tahap' => 'Pengawasan Pusat (Multi-Entitas)',
                'nama' => 'Super Admin Pusat (Dinas / Pengawas)',
                'ikon' => '🏛️',
                'warna_badge' => 'bg-slate-200 text-slate-900 border-slate-300',
                'warna_header' => 'from-slate-700 to-slate-900',
                'warna_kartu' => 'border-slate-300 hover:border-slate-600',
                'ringkasan' => 'Otoritas pengawas tingkat pusat/kabupaten yang memverifikasi pendaftaran koperasi desa baru, menginisialisasi master data standar nasional, serta dapat beralih antar-entitas desa untuk memonitor seluruh ekosistem.',
                'data_dikelola' => [
                    'Persetujuan (Approval) Pendaftaran Entitas Koperasi Desa Baru',
                    'Direktori Seluruh Entitas Koperasi Lintas Desa',
                    'Standarisasi Master Komoditas, Satuan Ukur & Bagan Akun (COA)',
                ],
                'data_dibatasi' => [
                    'Akses penuh lintas entitas (Global Supervisor)',
                ],
                'grup_sidebar' => ['Master Data', 'Survei & Pemetaan', 'Kalkulasi & Perencanaan', 'Manajemen Gudang', 'Pembelian & Penjualan', 'Konsinyasi & Titipan', 'Buku Keuangan', 'Akuntansi & Laporan'],
                'alur_kerja' => [
                    [
                        'langkah' => 1,
                        'judul' => 'Verifikasi & Persetujuan Entitas Baru',
                        'deskripsi' => 'Setujui pendaftaran koperasi desa baru dan inisialisasi gudang, kas, serta periode otomatis.',
                        'route' => 'admin.dashboard',
                        'ikon' => '🛡️',
                    ],
                    [
                        'langkah' => 2,
                        'judul' => 'Kelola Direktori Entitas Lintas Desa',
                        'deskripsi' => 'Pantau seluruh koperasi desa terdaftar dan status operasionalnya.',
                        'route' => 'master.entitas.index',
                        'ikon' => '🏢',
                    ],
                    [
                        'langkah' => 3,
                        'judul' => 'Master Komoditas & Bagan Akun (COA)',
                        'deskripsi' => 'Standarisasi katalog komoditas, satuan ukur, dan struktur akun akuntansi.',
                        'route' => 'master.komoditas.index',
                        'route_sekunder' => 'master.coa.index',
                        'label_sekunder' => 'Master COA',
                        'ikon' => '📜',
                    ],
                    [
                        'langkah' => 4,
                        'judul' => 'Katalog & Neraca Pangan Lintas Desa',
                        'deskripsi' => 'Lihat katalog komoditas desa dan potensi surplus/defisit antar-desa di dasbor utama.',
                        'route' => 'dashboard',
                        'route_params' => ['tab' => 'neraca'],
                        'ikon' => '🌾',
                    ],
                ],
            ],
        ];
    }

    /**
     * Definisi Alur Bisnis End-to-End (9 Tahap) sesuai panduan operasional sistem:
     * Mulai dari Inisialisasi Master Data & Saldo Awal hingga Tutup Bulan / Tutup Tahun & Laporan Keuangan.
     */
    public static function alurEndToEnd(): array
    {
        return [
            1 => [
                'tahap' => 1,
                'judul' => 'Inisialisasi Master Data & Saldo Awal (Modal Koperasi)',
                'judul_singkat' => 'Master Data & Saldo Awal',
                'ikon' => '🏗️',
                'peran_kode' => 'akuntan',
                'peran_nama' => 'Akuntan / Manajer / Super Admin',
                'tujuan' => 'Menyiapkan data dasar entitas (Gudang, Kas & Bank, Barang & Konversi Satuan, Pihak, COA, Periode 1–13) dan mengisi Saldo Awal Modal Kas sebelum koperasi mulai beroperasi.',
                'efek_jurnal_aturan' => [
                    'Jurnal Saldo Awal (JOP): (D) Kas Utama / Kas Bank — (K) Modal Awal / Ekuitas Desa (contoh: Rp 50.000.000).',
                    'Periode Akuntansi: Bulan 1–12 operasional dan Bulan 13 penyesuaian berstatus OPEN (ditentukan dari tanggal_jurnal).',
                    'Master Barang, Satuan Dasar & Pihak (flag is_anggota menentukan hak SHU dan akun pendapatan).',
                ],
                'aksi' => [
                    [
                        'step' => '1A',
                        'judul' => 'Verifikasi Master Data (Barang, Pihak, Gudang, Kas & Bank, Periode)',
                        'deskripsi' => 'Pastikan master barang, satuan konversi, gudang, akun kas/bank, pihak mitra, dan periode buku berstatus OPEN.',
                        'route' => 'master.kas-bank.index',
                        'route_sekunder' => 'master.barang.index',
                        'label_sekunder' => 'Master Barang',
                        'peran_akses' => 'manajer',
                    ],
                    [
                        'step' => '1B',
                        'judul' => 'Input Saldo Awal Modal Kas & Neraca Pembuka',
                        'deskripsi' => 'Buka Akuntansi ➔ Saldo Awal. Isi Kas Utama (D) dan Ekuitas / Modal Awal Desa (K), lalu Simpan.',
                        'route' => 'akuntansi.saldo-awal.index',
                        'route_sekunder' => 'master.periode.index',
                        'label_sekunder' => 'Cek Periode',
                        'peran_akses' => 'akuntan',
                    ],
                    [
                        'step' => '1C',
                        'judul' => 'Cek Saldo Kas & Transaksi Keuangan',
                        'deskripsi' => 'Pastikan modal kas awal sudah tampil di daftar Kas & Bank / Buku Kas.',
                        'route' => 'keuangan.kas-transaksi.index',
                        'peran_akses' => 'akuntan',
                    ],
                ],
            ],
            2 => [
                'tahap' => 2,
                'judul' => 'Pengumpulan Data Survei Konsumsi & Produksi Desa',
                'judul_singkat' => 'Survei Desa',
                'ikon' => '📋',
                'peran_kode' => 'surveyor',
                'peran_nama' => 'Surveyor Lapangan',
                'tujuan' => 'Mengumpulkan data primer desa: Demografi KK per RT, Potensi Produksi petani/ekraf (per bulan panen), serta konsumsi warga via input manual atau suara (STT).',
                'efek_jurnal_aturan' => [
                    'Alur status sesi: Buat sesi + token URL publik ➔ Pengisian (draft ➔ terkirim) ➔ Verifikasi Surveyor (disetujui).',
                    'Hanya data survei berstatus disetujui yang otomatis masuk ke kalkulasi modul Perencanaan & Kebutuhan.',
                    '3 data dipisah: Demografi, Potensi Produksi (per bulan panen), dan Harga Pasar. (Tidak ada jurnal akuntansi di tahap survei).',
                ],
                'aksi' => [
                    [
                        'step' => '2A',
                        'judul' => 'Buat Sesi Survei & Terbitkan Token Publik',
                        'deskripsi' => 'Buka sesi survei periode berjalan dan bagikan tautan survei untuk RT, Produsen, dan Warga.',
                        'route' => 'survei.sesi.index',
                        'route_sekunder' => 'survei.dashboard.index',
                        'label_sekunder' => 'Dashboard Survei',
                        'peran_akses' => 'surveyor',
                    ],
                    [
                        'step' => '2B',
                        'judul' => 'Input Data Baseline RT & Produsen Lokal',
                        'deskripsi' => 'Catat data KK tiap RT serta daftar petani/produsen desa beserta estimasi hasil panen bulanannya.',
                        'route' => 'survei.rt.index',
                        'route_sekunder' => 'survei.produsen.index',
                        'label_sekunder' => 'Produsen Desa',
                        'peran_akses' => 'surveyor',
                    ],
                    [
                        'step' => '2C',
                        'judul' => 'Validasi & Sinkronisasi Hasil Survei',
                        'deskripsi' => 'Verifikasi isian responden warga & produsen lalu setujui agar tersinkronisasi ke tahap Perencanaan.',
                        'route' => 'survei.validasi.index',
                        'peran_akses' => 'surveyor',
                    ],
                ],
            ],
            3 => [
                'tahap' => 3,
                'judul' => 'Kalkulasi Kebutuhan, Neraca Komoditas & Rencana Pengadaan',
                'judul_singkat' => 'Neraca & Pengadaan',
                'ikon' => '⚖️',
                'peran_kode' => 'manajer',
                'peran_nama' => 'Manajer / Perencana Koperasi',
                'tujuan' => 'Menghitung snapshot kebutuhan konsumsi warga, memetakan status SURPLUS / DEFISIT di Neraca Komoditas, membandingkan harga pemasok, dan menerbitkan Permintaan Pengadaan (PR).',
                'efek_jurnal_aturan' => [
                    'Rumus Kebutuhan: SUM(penduduk × per_kapita_harian × hari_bulan) × faktor_musiman (disimpan sebagai snapshot!).',
                    'Rumus Neraca: Produksi (Survei) - Kebutuhan ➔ SURPLUS atau DEFISIT (kebutuhan > produksi).',
                    'Pengadaan Bersih = Kebutuhan - Stok On-Hand (Gudang) - On-Order (Pembelian) + Safety Stock. State PR: draft ➔ diajukan ➔ disetujui ➔ jadi_pembelian. (Belum ada jurnal).',
                ],
                'aksi' => [
                    [
                        'step' => '3A',
                        'judul' => 'Cek Demografi, Produksi & Jalankan Kalkulasi Kebutuhan',
                        'deskripsi' => 'Review parameter penduduk & standar konsumsi lalu hitung snapshot kebutuhan komoditas bulanan.',
                        'route' => 'perencanaan.kebutuhan-komoditas.index',
                        'route_sekunder' => 'perencanaan.demografi.index',
                        'label_sekunder' => 'Data Demografi',
                        'peran_akses' => 'manajer',
                    ],
                    [
                        'step' => '3B',
                        'judul' => 'Analisis Neraca Komoditas (Surplus / Defisit)',
                        'deskripsi' => 'Lihat komoditas mana yang Surplus (bisa dikonsinyasi) atau Defisit (perlu pengadaan).',
                        'route' => 'perencanaan.neraca-komoditas.index',
                        'route_sekunder' => 'perencanaan.laporan.index',
                        'label_sekunder' => 'Laporan Kebutuhan',
                        'peran_akses' => 'manajer',
                    ],
                    [
                        'step' => '3C',
                        'judul' => 'Perbandingan Harga & Terbitkan Permintaan Pengadaan (PR)',
                        'deskripsi' => 'Bandingkan harga pemasok/desa mitra dan buat serta setujui PR untuk komoditas defisit.',
                        'route' => 'perencanaan.rekomendasi-pengadaan.index',
                        'route_sekunder' => 'perencanaan.perbandingan-harga.index',
                        'label_sekunder' => 'Perbandingan Harga',
                        'peran_akses' => 'manajer',
                    ],
                ],
            ],
            4 => [
                'tahap' => 4,
                'judul' => 'Pengadaan & Pembelian Barang (PO / Beli Petani)',
                'judul_singkat' => 'Pembelian (PO)',
                'ikon' => '🛒',
                'peran_kode' => 'pembelian',
                'peran_nama' => 'Staf Pembelian (Purchasing)',
                'tujuan' => 'Membeli barang fisik dari supplier/mitra berdasarkan PR (atau Jalur Cepat Pembelian Langsung dari Petani BPT) menggunakan Kas, Bank, atau Kredit.',
                'efek_jurnal_aturan' => [
                    'Saat Pembelian disetujui (Approve): Terbentuk otomatis Jurnal: (D) Persediaan — (K) Kas Utama / Bank / Hutang Dagang.',
                    'Jalur Cepat Petani (BPT): Tanpa PR/PO — timbang, terima, langsung bayar tunai: (D) Persediaan / (K) Kas.',
                    'Retur Pembelian: Diganti uang (RBU: Kas D/Persediaan K), Potong hutang (RBH: Hutang D/Persediaan K), atau Ganti barang.',
                ],
                'aksi' => [
                    [
                        'step' => '4A',
                        'judul' => 'Buat Transaksi Pembelian / Beli Petani Baru',
                        'deskripsi' => 'Pilih pemasok/petani, item barang, qty, harga beli, dan metode bayar (Tunai / Transfer / Kredit).',
                        'route' => 'pembelian.pembelian.create',
                        'peran_akses' => 'pembelian',
                    ],
                    [
                        'step' => '4B',
                        'judul' => 'Setujui (Approve) Order Pembelian & Cetak Invoice',
                        'deskripsi' => 'Klik Setujui pada draf pembelian agar jurnal persediaan & kas/hutang terbentuk otomatis.',
                        'route' => 'pembelian.pembelian.index',
                        'route_sekunder' => 'pembelian.retur.index',
                        'label_sekunder' => 'Retur Pembelian',
                        'peran_akses' => 'pembelian',
                    ],
                ],
            ],
            5 => [
                'tahap' => 5,
                'judul' => 'Penerimaan Fisik Gudang (GRN) & HPP Moving Average',
                'judul_singkat' => 'Penerimaan & Stok',
                'ikon' => '📥',
                'peran_kode' => 'admin_gudang',
                'peran_nama' => 'Admin Gudang & Logistik',
                'tujuan' => 'Mencatat kedatangan fisik truk barang ke rak gudang (GRN), menyortir barang layak/tidak layak, memperbarui HPP Rata-Rata Bergerak, dan mencatat mutasi IN di Kartu Stok.',
                'efek_jurnal_aturan' => [
                    'Rumus Moving Average: HPP_baru = (nilai_persediaan_lama + nilai_masuk) / (qty_lama + qty_masuk).',
                    'Verifikasi Anti-Dobel Jurnal: Bila GRN terhubung ke Pembelian, sistem TIDAK membuat jurnal persediaan ganda, melainkan menautkan ID Jurnal Pembelian ke Kartu Stok.',
                    'Larangan Keras: Mutasi mundur tanggal (backdate) dilarang agar saldo nilai dan HPP kartu stok tetap akurat.',
                ],
                'aksi' => [
                    [
                        'step' => '5A',
                        'judul' => 'Input Penerimaan Barang (GRN) di Gudang',
                        'deskripsi' => 'Pilih gudang tujuan, tautkan ID Pembelian dari Tahap 4, masukkan qty layak masuk & simpan.',
                        'route' => 'gudang.penerimaan.index',
                        'peran_akses' => 'admin_gudang',
                    ],
                    [
                        'step' => '5B',
                        'judul' => 'Cek Posisi Stok, Batch Kedaluwarsa & Mutasi Kartu Stok (IN)',
                        'deskripsi' => 'Pastikan qty_on_hand bertambah, HPP rata-rata terhitung, dan tercatat mutasi IN di Kartu Stok.',
                        'route' => 'gudang.stok.index',
                        'route_sekunder' => 'gudang.kartu-stok.index',
                        'label_sekunder' => 'Kartu Stok',
                        'peran_akses' => 'admin_gudang',
                    ],
                ],
            ],
            6 => [
                'tahap' => 6,
                'judul' => 'Penjualan Warga (POS) & Konsinyasi Antar-Desa',
                'judul_singkat' => 'POS & Konsinyasi',
                'ikon' => '🧾',
                'peran_kode' => 'kasir',
                'peran_nama' => 'Kasir (POS) & Admin Gudang (Konsinyasi)',
                'tujuan' => 'Menjual komoditas kepada warga anggota maupun non-anggota melalui kasir (POS) untuk meraih pendapatan, serta menyalurkan surplus ke desa mitra via Konsinyasi.',
                'efek_jurnal_aturan' => [
                    'Penjualan Warga (JTW): (D) Kas/Bank / (K) Pendapatan (411/413 Anggota atau 412/414 Non-Anggota) + (D) HPP / (K) Persediaan. Retur pakai akun kontra 421.',
                    'Konsinyasi Antar-Desa: Desa A kirim KTK (Persediaan Konsinyasi D / Persediaan K) ➔ Desa B terima (tanpa jurnal) ➔ Jual KJL (Kas D/Hutang Konsinyasi K) + KAP otomatis di Desa A ➔ Setor KST/KTR.',
                ],
                'aksi' => [
                    [
                        'step' => '6A',
                        'judul' => 'Input Transaksi Kasir Penjualan Warga (POS)',
                        'deskripsi' => 'Scan/pilih barang, cek is_pembeli_anggota (hak SHU), pilih metode bayar tunai/transfer, lalu simpan.',
                        'route' => 'penjualan.penjualan.create',
                        'route_sekunder' => 'penjualan.penjualan.index',
                        'label_sekunder' => 'Daftar Penjualan',
                        'peran_akses' => 'kasir',
                    ],
                    [
                        'step' => '6B',
                        'judul' => 'Pencocokan Surplus-Defisit & Konsinyasi Antar-Desa',
                        'deskripsi' => 'Cocokkan desa mitra defisit lalu kirim barang konsinyasi (KTK) atau kelola setoran titip jual.',
                        'route' => 'konsinyasi.pengiriman.index',
                        'route_sekunder' => 'gudang.pencocokan-surplus-defisit.index',
                        'label_sekunder' => 'Pencocokan Surplus-Defisit',
                        'peran_akses' => 'admin_gudang',
                    ],
                ],
            ],
            7 => [
                'tahap' => 7,
                'judul' => 'Pencatatan Kerusakan / Susut & Stok Opname Fisik',
                'judul_singkat' => 'Susut & Opname',
                'ikon' => '🔍',
                'peran_kode' => 'admin_gudang',
                'peran_nama' => 'Admin Gudang & Manajer',
                'tujuan' => 'Mencatat penyusutan/kerusakan barang non-penjualan serta melaksanakan stok opname fisik berkala guna menyinkronkan saldo sistem dengan fisik rak gudang.',
                'efek_jurnal_aturan' => [
                    'Kerusakan (KRG): Stok fisik berkurang otomatis dan masuk antrean outbox: (D) 641 Biaya Kerusakan & Susut / (K) Persediaan.',
                    'Stok Opname: Buat sesi ➔ freeze stok ➔ input hasil hitung fisik ➔ Approval Manajer: Kurang (OPK: 641 D / Persediaan K), Lebih (OPL: Persediaan D / 712 Selisih Lebih K).',
                ],
                'aksi' => [
                    [
                        'step' => '7A',
                        'judul' => 'Catat Kerusakan / Susut Barang (KRG)',
                        'deskripsi' => 'Laporkan qty barang yang rusak/susut dimakan hama agar stok & beban kerugian tercatat di outbox.',
                        'route' => 'gudang.kerusakan.index',
                        'peran_akses' => 'admin_gudang',
                    ],
                    [
                        'step' => '7B',
                        'judul' => 'Laksanakan Sesi Stok Opname Fisik (OPK / OPL)',
                        'deskripsi' => 'Bandingkan stok seharusnya di sistem vs hitungan rak fisik (misal selisih kurang 2 kg) lalu setujui.',
                        'route' => 'gudang.opname.index',
                        'peran_akses' => 'admin_gudang',
                    ],
                ],
            ],
            8 => [
                'tahap' => 8,
                'judul' => 'Keuangan, Pelunasan & Outbox Jurnal Otomatis Gudang',
                'judul_singkat' => 'Keuangan & Outbox',
                'ikon' => '⚡',
                'peran_kode' => 'akuntan',
                'peran_nama' => 'Akuntan & Bendahara',
                'tujuan' => 'Memproses antrean outbox gudang menjadi Jurnal Umum resmi (tanpa modul gudang memanggil JurnalService langsung), mengelola pelunasan piutang/hutang, dan rekonsiliasi buku pembantu.',
                'efek_jurnal_aturan' => [
                    'Outbox Gudang: Proses antrean_jurnal_gudang (KRG, OPK, OPL, PNB) via tombol Proses Outbox atau `php artisan jurnal:proses-gudang` ➔ otomatis mengisi ID Jurnal di Kartu Stok.',
                    'Buku Pembantu = Buku Besar: SUM(piutang.sisa)=1132+1133+1135 | SUM(hutang.sisa)=2111+2117 | SUM(kas_bank)=1111+1112x.',
                    'Jurnal Pembalik: Jurnal POSTED haram diedit/dihapus! Koreksi wajib memakai JurnalService::balik() (+ balik kartu stok bila berdampak stok).',
                ],
                'aksi' => [
                    [
                        'step' => '8A',
                        'judul' => 'Verifikasi Jurnal Umum, Outbox & Jurnal Pembalik',
                        'deskripsi' => 'Pastikan jurnal PNB/KRG/OPK sudah terposting, Debet = Kredit seimbang, dan ID Jurnal tertaut di Kartu Stok.',
                        'route' => 'akuntansi.jurnal.index',
                        'route_sekunder' => 'gudang.kartu-stok.index',
                        'label_sekunder' => 'Cek Kartu Stok',
                        'peran_akses' => 'akuntan',
                    ],
                    [
                        'step' => '8B',
                        'judul' => 'Kelola Piutang, Hutang, Pelunasan & Simpanan Anggota',
                        'deskripsi' => 'Lakukan penagihan piutang (TPI), pembayaran hutang supplier (BHU), offset (OFS), atau simpanan (SPK/SWJ).',
                        'route' => 'keuangan.pelunasan.index',
                        'route_sekunder' => 'keuangan.piutang.index',
                        'label_sekunder' => 'Daftar Piutang',
                        'peran_akses' => 'akuntan',
                    ],
                ],
            ],
            9 => [
                'tahap' => 9,
                'judul' => 'Tutup Bulan (8 Validasi), Tutup Tahun (SHU) & Laporan Keuangan',
                'judul_singkat' => 'Tutup Buku & Laporan',
                'ikon' => '🔒',
                'peran_kode' => 'akuntan',
                'peran_nama' => 'Akuntan & Bendahara',
                'tujuan' => 'Mengunci pembukuan bulanan (setelah lulus 8 validasi integritas), mencetak/ekspor laporan keuangan (PDF & CSV), serta menjalankan Tutup Tahun & Pembagian SHU.',
                'efek_jurnal_aturan' => [
                    '8 Validasi Wajib Tutup Bulan: 1) Tanpa draft; 2) Semua jurnal POSTED; 3) D=K; 4) Stok BB=Gudang; 5) Piutang BB=Sub-ledger; 6) Hutang BB=Sub-ledger; 7) Konsinyasi BB=Stok; 8) Piutang Konsinyasi A = Hutang Konsinyasi B.',
                    'Tutup Tahun: Bulan 1–13 CLOSED ➔ Jurnal Penutup akun 4,5,6,7 ke Ikhtisar (811) ➔ SHU (341) ➔ Pembagian SHU sesuai config_shu (331,332,333,334,2116) ➔ Tahun LOCKED.',
                ],
                'aksi' => [
                    [
                        'step' => '9A',
                        'judul' => 'Tutup Bulan (8 Validasi + Penyusutan Aset Tetap)',
                        'deskripsi' => 'Jalankan validasi integritas 8 lapis dan kunci buku bulan berjalan ke tabel buku_besar_periode.',
                        'route' => 'akuntansi.tutup-bulan.index',
                        'route_sekunder' => 'akuntansi.aset-tetap.index',
                        'label_sekunder' => 'Aset Tetap',
                        'peran_akses' => 'akuntan',
                    ],
                    [
                        'step' => '9B',
                        'judul' => 'Ekspor & Cetak Laporan Keuangan (Laba Rugi, Neraca, Arus Kas)',
                        'deskripsi' => 'Pastikan Neraca BALANCE (Aktiva = Passiva) dan unduh laporan format CSV/PDF untuk lampiran.',
                        'route' => 'akuntansi.laporan.neraca',
                        'route_sekunder' => 'akuntansi.laporan.laba-rugi',
                        'label_sekunder' => 'Laba Rugi',
                        'peran_akses' => 'akuntan',
                    ],
                    [
                        'step' => '9C',
                        'judul' => 'Konfigurasi SHU & Tutup Buku Akhir Tahun (Bulan 1–13)',
                        'deskripsi' => 'Tutup buku tahunan untuk membentuk jurnal penutup, pembagian SHU anggota, dan carry-forward neraca.',
                        'route' => 'akuntansi.tutup-tahun.index',
                        'route_sekunder' => 'akuntansi.config-shu.index',
                        'label_sekunder' => 'Config SHU',
                        'peran_akses' => 'akuntan',
                    ],
                ],
            ],
        ];
    }

    /**
     * Menentukan kode peran aktif dari pengguna & session saat ini.
     */
    public static function kodePeranAktif(?Pengguna $user): string
    {
        if (! $user) {
            return 'manajer';
        }

        $sessionPeran = session('demo_peran_aktif');
        $katalog = self::katalogPeran();

        if ($sessionPeran && isset($katalog[$sessionPeran])) {
            if ($sessionPeran === 'super_admin' && $user->isSuperAdmin()) {
                return 'super_admin';
            }
            if ($user->hasPeran($sessionPeran) || $user->isSuperAdmin() || $user->hasPeran('manajer')) {
                return $sessionPeran;
            }
        }

        if ($user->isSuperAdmin()) {
            return 'super_admin';
        }

        foreach (['surveyor', 'admin_gudang', 'pembelian', 'kasir', 'akuntan', 'manajer'] as $kode) {
            if ($user->hasPeran($kode)) {
                return $kode;
            }
        }

        return 'manajer';
    }

    /**
     * Mencari atau membuat otomatis akun pengguna demo untuk peran & entitas yang dipilih.
     */
    public static function resolveOrCreateDemoUser(string $kodePeran, ?int $idEntitas = null): Pengguna
    {
        $peranModel = Peran::firstOrCreate(
            ['kode' => $kodePeran],
            ['nama' => ucwords(str_replace('_', ' ', $kodePeran))]
        );

        if ($kodePeran === 'super_admin') {
            $superAdmin = Pengguna::whereNull('id_entitas')
                ->whereHas('peran', fn ($q) => $q->where('kode', 'super_admin'))
                ->first();

            if (! $superAdmin) {
                $superAdmin = Pengguna::updateOrCreate(
                    ['email' => 'superadmin@ngobar.id'],
                    [
                        'id_entitas' => null,
                        'nama' => 'Super Administrator',
                        'password' => Hash::make('password'),
                        'is_active' => true,
                    ]
                );
                DB::table('pengguna_peran')->updateOrInsert([
                    'id_pengguna' => $superAdmin->id,
                    'id_peran' => $peranModel->id_peran,
                ]);
            }

            return $superAdmin;
        }

        $entitas = null;
        if ($idEntitas) {
            $entitas = Entitas::find($idEntitas);
        }
        if (! $entitas) {
            $entitas = Entitas::where('status', 'APPROVED')->orderBy('id_entitas')->first()
                ?? Entitas::orderBy('id_entitas')->first();
        }

        $targetEntitasId = $entitas?->id_entitas;

        // Cari pengguna di entitas tersebut yang memiliki peran spesifik ini
        $existingUser = Pengguna::where('id_entitas', $targetEntitasId)
            ->where('is_active', true)
            ->whereHas('peran', fn ($q) => $q->where('kode', $kodePeran))
            ->get()
            ->first(function (Pengguna $u) use ($kodePeran) {
                // Utamakan akun yang memang dedikasi untuk peran tersebut (bukan akun multi-peran jika ada)
                return $u->peran->count() === 1 && $u->peran->first()->kode === $kodePeran;
            });

        if (! $existingUser) {
            $existingUser = Pengguna::where('id_entitas', $targetEntitasId)
                ->where('is_active', true)
                ->whereHas('peran', fn ($q) => $q->where('kode', $kodePeran))
                ->first();
        }

        if ($existingUser) {
            return $existingUser;
        }

        // Buat akun demo untuk peran ini secara otomatis jika belum ada di database
        $suffixDesa = $entitas
            ? strtolower(str_replace(' ', '', $entitas->kode_entitas))
            : 'desa';
        $namaWilayah = $entitas?->wilayah?->nama ?? $entitas?->nama_entitas ?? 'Desa';
        $labelPeran = match ($kodePeran) {
            'manajer' => 'Manajer',
            'surveyor' => 'Surveyor',
            'admin_gudang' => 'Admin Gudang',
            'pembelian' => 'Staf Pembelian',
            'kasir' => 'Kasir',
            'akuntan' => 'Akuntan',
            default => ucwords(str_replace('_', ' ', $kodePeran)),
        };
        $emailPrefix = $kodePeran === 'admin_gudang' ? 'gudang' : $kodePeran;
        $email = "{$emailPrefix}@{$suffixDesa}.test";

        $newUser = Pengguna::updateOrCreate(
            ['email' => $email],
            [
                'id_entitas' => $targetEntitasId,
                'nama' => "{$labelPeran} {$namaWilayah}",
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        DB::table('pengguna_peran')->updateOrInsert([
            'id_pengguna' => $newUser->id,
            'id_peran' => $peranModel->id_peran,
        ]);

        return $newUser->fresh(['peran', 'entitas']);
    }
}
