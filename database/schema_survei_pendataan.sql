-- ==========================================================
-- DDL SKEMA DATABASE FITUR PENDATAAN & SURVEI KDMP
-- Generate Tanggal: 2026-11-27 11:25:47
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- Skema Tabel: entitas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `entitas`;
CREATE TABLE `entitas` (
  `id_entitas` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_entitas` varchar(20) NOT NULL,
  `nama_entitas` varchar(150) NOT NULL,
  `jenis_entitas` enum('BUMDes','Koperasi Desa','Kelompok Tani','Gapoktan','Perorangan','Lainnya') NOT NULL DEFAULT 'Lainnya',
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `badan_hukum_no` varchar(50) DEFAULT NULL,
  `tgl_berdiri` date DEFAULT NULL,
  `tahun_buku_awal` year(4) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_entitas`),
  UNIQUE KEY `entitas_kode_entitas_unique` (`kode_entitas`),
  UNIQUE KEY `entitas_id_wilayah_unique` (`id_wilayah`),
  CONSTRAINT `entitas_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: wilayah
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `wilayah`;
CREATE TABLE `wilayah` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `tingkat` enum('prov','kab','kec','desa','dusun','rw','rt') NOT NULL,
  `nama` varchar(255) NOT NULL,
  `kode_bps` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wilayah_parent_id_foreign` (`parent_id`),
  CONSTRAINT `wilayah_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `wilayah` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: kategori_komoditas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `kategori_komoditas`;
CREATE TABLE `kategori_komoditas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kategori_komoditas_kode_unique` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: master_komoditas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `master_komoditas`;
CREATE TABLE `master_komoditas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_kategori` bigint(20) unsigned NOT NULL,
  `kode` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_komoditas_kode_unique` (`kode`),
  KEY `master_komoditas_id_kategori_foreign` (`id_kategori`),
  CONSTRAINT `master_komoditas_id_kategori_foreign` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_komoditas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: item_komoditas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `item_komoditas`;
CREATE TABLE `item_komoditas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_master_komoditas` bigint(20) unsigned NOT NULL,
  `kode` varchar(30) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `satuan_standar` varchar(30) NOT NULL DEFAULT 'kg',
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_komoditas_kode_unique` (`kode`),
  KEY `item_komoditas_id_master_komoditas_foreign` (`id_master_komoditas`),
  CONSTRAINT `item_komoditas_id_master_komoditas_foreign` FOREIGN KEY (`id_master_komoditas`) REFERENCES `master_komoditas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: petani
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `petani`;
CREATE TABLE `petani` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `nomor_hp` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `rt` varchar(10) DEFAULT NULL,
  `rw` varchar(10) DEFAULT NULL,
  `desa` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `kabupaten` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `petani_id_wilayah_foreign` (`id_wilayah`),
  CONSTRAINT `petani_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: potensi_produksi_petani
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `potensi_produksi_petani`;
CREATE TABLE `potensi_produksi_petani` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `petani_id` bigint(20) unsigned NOT NULL,
  `item_komoditas_id` bigint(20) unsigned NOT NULL,
  `luas_lahan_ha` decimal(10,2) DEFAULT NULL,
  `estimasi_volume_panen` decimal(12,2) DEFAULT NULL,
  `satuan` varchar(30) NOT NULL DEFAULT 'kg',
  `harga_satuan` decimal(15,2) DEFAULT 0.00,
  `total_nilai` decimal(18,2) DEFAULT 0.00,
  `periode_panen` varchar(50) NOT NULL DEFAULT 'Musiman',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `petani_item_unik` (`petani_id`,`item_komoditas_id`),
  KEY `potensi_produksi_petani_item_komoditas_id_foreign` (`item_komoditas_id`),
  CONSTRAINT `potensi_produksi_petani_item_komoditas_id_foreign` FOREIGN KEY (`item_komoditas_id`) REFERENCES `item_komoditas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `potensi_produksi_petani_petani_id_foreign` FOREIGN KEY (`petani_id`) REFERENCES `petani` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: penduduk_desa
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `penduduk_desa`;
CREATE TABLE `penduduk_desa` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `tahun` year(4) NOT NULL,
  `balita` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '0-4 tahun',
  `anak` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '5-9 tahun',
  `remaja` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '10-19 tahun',
  `dewasa` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '20-59 tahun',
  `lansia` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '60+ tahun',
  `total_penduduk` int(10) unsigned NOT NULL DEFAULT 0,
  `sumber_data` varchar(100) NOT NULL DEFAULT 'Input Admin',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `penduduk_desa_tahun_unik` (`id_wilayah`,`tahun`),
  CONSTRAINT `penduduk_desa_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------
-- Skema Tabel: modul_survei
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `modul_survei`;
CREATE TABLE `modul_survei` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(255) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `versi` varchar(255) NOT NULL DEFAULT 'v1',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: pertanyaan
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `pertanyaan`;
CREATE TABLE `pertanyaan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_modul` bigint(20) unsigned NOT NULL,
  `kode_pertanyaan` varchar(255) NOT NULL,
  `teks_pertanyaan` text NOT NULL,
  `tipe_jawaban` enum('angka','teks','pilihan','pilihan_ganda','json','validasi_jumlah') NOT NULL,
  `satuan` varchar(20) DEFAULT NULL,
  `wajib_diisi` tinyint(1) NOT NULL DEFAULT 0,
  `aturan_validasi_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`aturan_validasi_json`)),
  `urutan` smallint(5) unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `jenis_pendataan` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pertanyaan_id_modul_foreign` (`id_modul`),
  CONSTRAINT `pertanyaan_id_modul_foreign` FOREIGN KEY (`id_modul`) REFERENCES `modul_survei` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: sesi_survei
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `sesi_survei`;
CREATE TABLE `sesi_survei` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_petugas` bigint(20) unsigned DEFAULT NULL,
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `tahun` year(4) NOT NULL,
  `bulan` tinyint(3) unsigned DEFAULT NULL,
  `tanggal_survei` date NOT NULL,
  `status` enum('draft','terkirim','disetujui','ditolak') NOT NULL DEFAULT 'draft',
  `catatan` text DEFAULT NULL,
  `id_perangkat` varchar(255) DEFAULT NULL,
  `uuid_sesi_klien` varchar(255) DEFAULT NULL,
  `token_publik` varchar(64) DEFAULT NULL,
  `token_kadaluarsa` datetime DEFAULT NULL,
  `token_rt` varchar(64) DEFAULT NULL,
  `token_produsen` varchar(64) DEFAULT NULL,
  `token_masyarakat` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sesi_survei_token_publik_unique` (`token_publik`),
  KEY `sesi_survei_id_wilayah_foreign` (`id_wilayah`),
  CONSTRAINT `sesi_survei_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: rt_desas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `rt_desas`;
CREATE TABLE `rt_desas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `id_sesi` bigint(20) unsigned DEFAULT NULL,
  `nama_rt` varchar(255) NOT NULL,
  `rw` varchar(50) DEFAULT NULL,
  `dusun` varchar(100) DEFAULT NULL,
  `total_kk_baseline` int(11) NOT NULL DEFAULT 0,
  `total_jiwa_baseline` int(11) NOT NULL DEFAULT 0,
  `nama_ketua_rt` varchar(150) DEFAULT NULL,
  `nomor_hp` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rt_desas_id_wilayah_foreign` (`id_wilayah`),
  KEY `rt_desas_id_sesi_foreign` (`id_sesi`),
  CONSTRAINT `rt_desas_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rt_desas_id_sesi_foreign` FOREIGN KEY (`id_sesi`) REFERENCES `sesi_survei` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: produsen_desas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `produsen_desas`;
CREATE TABLE `produsen_desas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `kategori` enum('Kelompok Tani','Ekraf') NOT NULL,
  `sub_kategori` varchar(100) DEFAULT NULL,
  `nama_anggota` varchar(255) NOT NULL,
  `no_wa` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produsen_desas_id_wilayah_foreign` (`id_wilayah`),
  CONSTRAINT `produsen_desas_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: produksi_produsen
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `produksi_produsen`;
CREATE TABLE `produksi_produsen` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `id_sesi` bigint(20) unsigned DEFAULT NULL,
  `id_produsen` bigint(20) unsigned NOT NULL,
  `komoditas` varchar(150) NOT NULL,
  `volume_produksi` decimal(12,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(30) NOT NULL DEFAULT 'kg',
  `harga_satuan` decimal(15,2) DEFAULT 0.00,
  `frekuensi_panen` varchar(50) DEFAULT 'Per Bulan',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produksi_produsen_id_wilayah_foreign` (`id_wilayah`),
  KEY `produksi_produsen_id_sesi_foreign` (`id_sesi`),
  KEY `produksi_produsen_id_produsen_foreign` (`id_produsen`),
  CONSTRAINT `produksi_produsen_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`) ON DELETE CASCADE,
  CONSTRAINT `produksi_produsen_id_sesi_foreign` FOREIGN KEY (`id_sesi`) REFERENCES `sesi_survei` (`id`) ON DELETE SET NULL,
  CONSTRAINT `produksi_produsen_id_produsen_foreign` FOREIGN KEY (`id_produsen`) REFERENCES `produsen_desas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: masyarakat_desas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `masyarakat_desas`;
CREATE TABLE `masyarakat_desas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_sesi` bigint(20) unsigned NOT NULL,
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `nama_kepala_keluarga` varchar(255) NOT NULL,
  `nomor_hp` varchar(255) DEFAULT NULL,
  `umur` int(11) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `provinsi` varchar(255) DEFAULT NULL,
  `kabupaten` varchar(255) DEFAULT NULL,
  `kecamatan` varchar(255) DEFAULT NULL,
  `desa` varchar(255) DEFAULT NULL,
  `dusun` varchar(255) DEFAULT NULL,
  `rt` varchar(255) DEFAULT NULL,
  `rw` varchar(255) DEFAULT NULL,
  `nama_jalan` varchar(255) DEFAULT NULL,
  `nomor_rumah` varchar(255) DEFAULT NULL,
  `detail_alamat` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `masyarakat_desas_id_sesi_foreign` (`id_sesi`),
  KEY `masyarakat_desas_id_wilayah_foreign` (`id_wilayah`),
  CONSTRAINT `masyarakat_desas_id_sesi_foreign` FOREIGN KEY (`id_sesi`) REFERENCES `sesi_survei` (`id`) ON DELETE CASCADE,
  CONSTRAINT `masyarakat_desas_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: anggota_keluargas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `anggota_keluargas`;
CREATE TABLE `anggota_keluargas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_masyarakat` bigint(20) unsigned NOT NULL,
  `nama_anggota` varchar(255) NOT NULL,
  `hubungan` varchar(100) DEFAULT 'Anggota',
  `umur` int(11) NOT NULL DEFAULT 0,
  `jenis_kelamin` enum('L','P') NOT NULL DEFAULT 'L',
  `pendidikan` varchar(100) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `anggota_keluargas_id_masyarakat_foreign` (`id_masyarakat`),
  CONSTRAINT `anggota_keluargas_id_masyarakat_foreign` FOREIGN KEY (`id_masyarakat`) REFERENCES `masyarakat_desas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: konsumsi_keluargas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `konsumsi_keluargas`;
CREATE TABLE `konsumsi_keluargas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_masyarakat` bigint(20) unsigned NOT NULL,
  `komoditas` varchar(150) NOT NULL,
  `kebutuhan_tahunan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(30) NOT NULL DEFAULT 'kg',
  `sumber_harga` varchar(100) DEFAULT NULL,
  `tipe_harga` varchar(50) DEFAULT NULL,
  `lokasi_pemenuhan` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `konsumsi_keluargas_id_masyarakat_foreign` (`id_masyarakat`),
  CONSTRAINT `konsumsi_keluargas_id_masyarakat_foreign` FOREIGN KEY (`id_masyarakat`) REFERENCES `masyarakat_desas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: demografi_narasumber
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `demografi_narasumber`;
CREATE TABLE `demografi_narasumber` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_sesi` bigint(20) unsigned NOT NULL,
  `nama_narasumber` varchar(150) NOT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `nomor_kontak` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `demografi_narasumber_id_sesi_foreign` (`id_sesi`),
  CONSTRAINT `demografi_narasumber_id_sesi_foreign` FOREIGN KEY (`id_sesi`) REFERENCES `sesi_survei` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: produksi_narasumber
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `produksi_narasumber`;
CREATE TABLE `produksi_narasumber` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_sesi` bigint(20) unsigned NOT NULL,
  `nama_narasumber` varchar(150) NOT NULL,
  `komoditas` varchar(150) NOT NULL,
  `volume` decimal(12,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(30) NOT NULL DEFAULT 'kg',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produksi_narasumber_id_sesi_foreign` (`id_sesi`),
  CONSTRAINT `produksi_narasumber_id_sesi_foreign` FOREIGN KEY (`id_sesi`) REFERENCES `sesi_survei` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: standar_konsumsi
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `standar_konsumsi`;
CREATE TABLE `standar_konsumsi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_sesi` bigint(20) unsigned NOT NULL,
  `komoditas` varchar(150) NOT NULL,
  `standar_kg_per_kapita_tahun` decimal(10,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(30) NOT NULL DEFAULT 'kg/kapita/tahun',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `standar_konsumsi_id_sesi_foreign` (`id_sesi`),
  CONSTRAINT `standar_konsumsi_id_sesi_foreign` FOREIGN KEY (`id_sesi`) REFERENCES `sesi_survei` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: pemenuhan_komoditas
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `pemenuhan_komoditas`;
CREATE TABLE `pemenuhan_komoditas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_sesi` bigint(20) unsigned NOT NULL,
  `komoditas` varchar(150) NOT NULL,
  `persentase_lokal` decimal(5,2) NOT NULL DEFAULT 0.00,
  `persentase_luar` decimal(5,2) NOT NULL DEFAULT 0.00,
  `harga_rata_rata` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pemenuhan_komoditas_id_sesi_foreign` (`id_sesi`),
  CONSTRAINT `pemenuhan_komoditas_id_sesi_foreign` FOREIGN KEY (`id_sesi`) REFERENCES `sesi_survei` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: demografi_desa (Perencanaan)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `demografi_desa`;
CREATE TABLE `demografi_desa` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `id_sesi_survei` bigint(20) unsigned DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `kelompok_umur` enum('balita','anak','remaja','dewasa','lansia') NOT NULL,
  `jumlah_penduduk` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `demografi_desa_id_wilayah_foreign` (`id_wilayah`),
  KEY `demografi_desa_id_sesi_survei_foreign` (`id_sesi_survei`),
  CONSTRAINT `demografi_desa_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`) ON DELETE CASCADE,
  CONSTRAINT `demografi_desa_id_sesi_survei_foreign` FOREIGN KEY (`id_sesi_survei`) REFERENCES `sesi_survei` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: ketersediaan_komoditas (Perencanaan)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `ketersediaan_komoditas`;
CREATE TABLE `ketersediaan_komoditas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_wilayah` bigint(20) unsigned NOT NULL,
  `id_komoditas` bigint(20) unsigned DEFAULT NULL,
  `id_sesi_survei` bigint(20) unsigned DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `bulan` tinyint(3) unsigned DEFAULT NULL,
  `jumlah_produksi` decimal(12,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(30) NOT NULL DEFAULT 'kg',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ketersediaan_komoditas_id_wilayah_foreign` (`id_wilayah`),
  KEY `ketersediaan_komoditas_id_sesi_survei_foreign` (`id_sesi_survei`),
  CONSTRAINT `ketersediaan_komoditas_id_wilayah_foreign` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ketersediaan_komoditas_id_sesi_survei_foreign` FOREIGN KEY (`id_sesi_survei`) REFERENCES `sesi_survei` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Skema Tabel: standar_kebutuhan_komoditas (Perencanaan)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `standar_kebutuhan_komoditas`;
CREATE TABLE `standar_kebutuhan_komoditas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_wilayah` bigint(20) unsigned DEFAULT NULL,
  `id_komoditas` bigint(20) unsigned DEFAULT NULL,
  `kelompok_umur` enum('balita','anak','remaja','dewasa','lansia','semua') NOT NULL DEFAULT 'semua',
  `jumlah_kebutuhan_per_kapita` decimal(10,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(30) NOT NULL DEFAULT 'kg',
  `satuan_periode` varchar(30) NOT NULL DEFAULT 'tahun',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
