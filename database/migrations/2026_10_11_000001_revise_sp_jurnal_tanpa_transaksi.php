<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Revisi sp_post_jurnal & sp_jurnal_pembalik: SP TIDAK LAGI mengelola transaksi.
 *
 * MASALAH VERSI LAMA:
 *   SP menjalankan START TRANSACTION / COMMIT / ROLLBACK sendiri. Di MySQL,
 *   START TRANSACTION di tengah transaksi yang aktif akan meng-COMMIT transaksi
 *   tersebut secara diam-diam (implicit commit). Akibatnya:
 *     - Data yang ditulis Laravel sebelum CALL (kas_transaksi, pelunasan, stok, dll)
 *       sudah permanen sebelum jurnal dibuat. Jika SP gagal / koneksi putus,
 *       data transaksi tersimpan TANPA jurnal.
 *     - DB::rollBack() / DB::commit() Laravel sesudahnya tidak bermakna lagi.
 *     - lockForUpdate() saat generate nomor jurnal tidak mengunci apa pun.
 *
 * VERSI BARU:
 *   - Tidak ada START TRANSACTION / COMMIT / ROLLBACK di dalam SP.
 *   - Error SQL apa pun diteruskan otomatis ke pemanggil (Laravel).
 *   - Transaksi dipegang SATU pihak: Laravel (JurnalService membungkus setiap
 *     CALL dengan DB::transaction, yang otomatis "ikut" transaksi pemanggil
 *     bila sudah ada — via SAVEPOINT).
 *
 * Bonus perbaikan sp_jurnal_pembalik:
 *   - Jurnal asal dikunci (SELECT ... FOR UPDATE) agar dua user tidak bisa
 *     membalik jurnal yang sama secara bersamaan.
 *   - Baris buku_besar_periode baru kini mengambil saldo_awal dari saldo_akhir
 *     bulan sebelumnya (versi lama selalu 0, sehingga saldo akhir salah).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->buatSpPostJurnal();
        $this->buatSpJurnalPembalik();
    }

    public function down(): void
    {
        // Sengaja no-op: kembali ke versi lama berarti mengembalikan bug implicit
        // commit. Jika benar-benar perlu, jalankan ulang isi migration
        // 2026_01_02_000000_create_sp_post_jurnal & 2026_09_30_003312_create_sp_jurnal_pembalik.
    }

    private function buatSpPostJurnal(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_post_jurnal');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_post_jurnal(
    IN p_id_entitas  BIGINT UNSIGNED,
    IN p_user_id     BIGINT UNSIGNED,
    IN p_json        JSON
)
BEGIN
    -- =========================================================
    -- KONTRAK: SP ini WAJIB dipanggil di dalam transaksi milik
    -- pemanggil (JurnalService sudah menjamin ini). SP tidak
    -- melakukan COMMIT/ROLLBACK; jika ada error, MySQL melempar
    -- error ke Laravel dan Laravel yang me-ROLLBACK semuanya.
    -- =========================================================
    DECLARE v_id_jurnal     BIGINT UNSIGNED DEFAULT 0;
    DECLARE v_n_baris       INT DEFAULT 0;
    DECLARE v_i             INT DEFAULT 0;

    DECLARE v_kode_anak     VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_debet         DECIMAL(18,2) DEFAULT 0;
    DECLARE v_kredit        DECIMAL(18,2) DEFAULT 0;
    DECLARE v_id_pihak      BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_ket_baris     VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;

    DECLARE v_total_debet   DECIMAL(18,2) DEFAULT 0;
    DECLARE v_total_kredit  DECIMAL(18,2) DEFAULT 0;

    DECLARE v_saldo_awal_d  DECIMAL(18,2) DEFAULT 0;
    DECLARE v_saldo_awal_k  DECIMAL(18,2) DEFAULT 0;

    DECLARE v_tahun         SMALLINT UNSIGNED;
    DECLARE v_bulan         TINYINT  UNSIGNED;

    SET v_tahun = JSON_EXTRACT(p_json, '$.periode_tahun');
    SET v_bulan = JSON_EXTRACT(p_json, '$.periode_bulan');

    -- LANGKAH 1: hitung total debet & kredit
    SET v_n_baris = JSON_LENGTH(JSON_EXTRACT(p_json, '$.baris'));
    SET v_i       = 0;

    WHILE v_i < v_n_baris DO
        SET v_total_debet = v_total_debet +
            IFNULL(JSON_EXTRACT(p_json, CONCAT('$.baris[', v_i, '].debet')), 0);
        SET v_total_kredit = v_total_kredit +
            IFNULL(JSON_EXTRACT(p_json, CONCAT('$.baris[', v_i, '].kredit')), 0);
        SET v_i = v_i + 1;
    END WHILE;

    -- Pengaman lapis database: jurnal harus balance
    IF ABS(v_total_debet - v_total_kredit) > 0.001 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'sp_post_jurnal: total debet dan kredit tidak balance.';
    END IF;

    -- LANGKAH 2: INSERT jurnal_header (langsung POSTED)
    INSERT INTO jurnal_header (
        id_entitas, no_jurnal, nomor_nota, tanggal_jurnal,
        periode_tahun, periode_bulan, kode_transaksi, jenis_jurnal,
        source_type, source_id, keterangan,
        total_debet, total_kredit, status,
        created_by, posted_by, posted_at, created_at
    ) VALUES (
        p_id_entitas,
        JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.no_jurnal')),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.nomor_nota')),     'null'),
        JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.tanggal_jurnal')),
        v_tahun,
        v_bulan,
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.kode_transaksi')), 'null'),
        JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.jenis_jurnal')),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.source_type')),    'null'),
        NULLIF(JSON_EXTRACT(p_json, '$.source_id'),                     'null'),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.keterangan')),     'null'),
        v_total_debet,
        v_total_kredit,
        'POSTED',
        p_user_id,
        p_user_id,
        NOW(),
        NOW()
    );

    SET v_id_jurnal = LAST_INSERT_ID();

    -- LANGKAH 3 + 4: INSERT jurnal_detail + UPSERT buku_besar_periode
    SET v_i = 0;

    WHILE v_i < v_n_baris DO
        SET v_kode_anak = JSON_UNQUOTE(JSON_EXTRACT(p_json, CONCAT('$.baris[', v_i, '].kode_anak')));
        SET v_debet     = IFNULL(JSON_EXTRACT(p_json, CONCAT('$.baris[', v_i, '].debet')), 0);
        SET v_kredit    = IFNULL(JSON_EXTRACT(p_json, CONCAT('$.baris[', v_i, '].kredit')), 0);
        SET v_id_pihak  = NULLIF(JSON_EXTRACT(p_json, CONCAT('$.baris[', v_i, '].id_pihak')), 'null');
        SET v_ket_baris = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_json, CONCAT('$.baris[', v_i, '].keterangan'))), 'null');

        INSERT INTO jurnal_detail (
            id_jurnal, urutan, kode_anak, debet, kredit, id_pihak, keterangan
        ) VALUES (
            v_id_jurnal, v_i + 1, v_kode_anak, v_debet, v_kredit, v_id_pihak, v_ket_baris
        );

        -- saldo_awal = saldo_akhir bulan sebelumnya (0 jika akun baru)
        SET v_saldo_awal_d = 0;
        SET v_saldo_awal_k = 0;

        SELECT IFNULL(saldo_akhir_debet, 0), IFNULL(saldo_akhir_kredit, 0)
        INTO   v_saldo_awal_d, v_saldo_awal_k
        FROM   buku_besar_periode
        WHERE  id_entitas    = p_id_entitas
          AND  periode_tahun = IF(v_bulan = 1, v_tahun - 1, v_tahun)
          AND  periode_bulan = IF(v_bulan = 1, 12, v_bulan - 1)
          AND  kode_anak     = v_kode_anak
        LIMIT 1;

        INSERT INTO buku_besar_periode (
            id_entitas, periode_tahun, periode_bulan, kode_anak,
            saldo_awal_debet, saldo_awal_kredit,
            mutasi_debet, mutasi_kredit,
            saldo_akhir_debet, saldo_akhir_kredit,
            dihitung_pada
        ) VALUES (
            p_id_entitas, v_tahun, v_bulan, v_kode_anak,
            v_saldo_awal_d, v_saldo_awal_k,
            v_debet, v_kredit,
            v_saldo_awal_d + v_debet, v_saldo_awal_k + v_kredit,
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            saldo_akhir_debet  = saldo_awal_debet  + mutasi_debet  + v_debet,
            saldo_akhir_kredit = saldo_awal_kredit + mutasi_kredit + v_kredit,
            mutasi_debet       = mutasi_debet  + v_debet,
            mutasi_kredit      = mutasi_kredit + v_kredit,
            dihitung_pada      = NOW();

        SET v_i = v_i + 1;
    END WHILE;

    -- RETURN id_jurnal ke JurnalService
    SELECT v_id_jurnal AS id_jurnal;
END
SQL);
    }

    private function buatSpJurnalPembalik(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_jurnal_pembalik');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_jurnal_pembalik(
    IN p_id_jurnal BIGINT UNSIGNED,
    IN p_user_id   BIGINT UNSIGNED,
    IN p_alasan    VARCHAR(255)
)
BEGIN
    -- KONTRAK: dipanggil di dalam transaksi milik pemanggil (JurnalService::balik).
    DECLARE v_id_entitas      BIGINT UNSIGNED;
    DECLARE v_no_jurnal_lama  VARCHAR(50);
    DECLARE v_kode_transaksi  VARCHAR(50);
    DECLARE v_total_debet     DECIMAL(18,2);
    DECLARE v_total_kredit    DECIMAL(18,2);
    DECLARE v_status          VARCHAR(20) DEFAULT NULL;
    DECLARE v_sudah_dibalik   INT DEFAULT 0;

    DECLARE v_new_id_jurnal   BIGINT UNSIGNED;
    DECLARE v_new_no_jurnal   VARCHAR(50);
    DECLARE v_tahun           SMALLINT UNSIGNED;
    DECLARE v_bulan           TINYINT UNSIGNED;

    -- Validasi 1: jurnal asal ada & POSTED. FOR UPDATE mengunci baris ini
    -- sampai transaksi selesai -> pembalikan ganda bersamaan tidak mungkin.
    SELECT id_entitas, no_jurnal, kode_transaksi, total_debet, total_kredit, status
    INTO   v_id_entitas, v_no_jurnal_lama, v_kode_transaksi, v_total_debet, v_total_kredit, v_status
    FROM   jurnal_header
    WHERE  id_jurnal = p_id_jurnal
    FOR UPDATE;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal tidak ditemukan.';
    END IF;

    IF v_status <> 'POSTED' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya jurnal dengan status POSTED yang dapat dibalik.';
    END IF;

    -- Validasi 2: belum pernah dibalik
    SELECT COUNT(*) INTO v_sudah_dibalik
    FROM   jurnal_header
    WHERE  id_jurnal_asal = p_id_jurnal;

    IF v_sudah_dibalik > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal ini sudah pernah dibalik.';
    END IF;

    SET v_tahun = YEAR(CURRENT_DATE());
    SET v_bulan = MONTH(CURRENT_DATE());
    SET v_new_no_jurnal = CONCAT('REV-', v_no_jurnal_lama);

    -- 1. Header pembalik
    INSERT INTO jurnal_header (
        id_entitas, no_jurnal, tanggal_jurnal, periode_tahun, periode_bulan,
        kode_transaksi, jenis_jurnal, id_jurnal_asal, keterangan,
        total_debet, total_kredit, status, created_by, posted_by, posted_at, created_at
    ) VALUES (
        v_id_entitas, v_new_no_jurnal, CURRENT_DATE(), v_tahun, v_bulan,
        v_kode_transaksi, 'PEMBALIK', p_id_jurnal,
        CONCAT('Koreksi untuk Transaksi ID ', p_id_jurnal, ': ', p_alasan),
        v_total_kredit, v_total_debet, 'POSTED', p_user_id, p_user_id, NOW(), NOW()
    );

    SET v_new_id_jurnal = LAST_INSERT_ID();

    -- 2. Detail dibalik (Debet <-> Kredit)
    INSERT INTO jurnal_detail (id_jurnal, urutan, kode_anak, debet, kredit, id_pihak, keterangan)
    SELECT v_new_id_jurnal, urutan, kode_anak, kredit, debet, id_pihak, keterangan
    FROM   jurnal_detail
    WHERE  id_jurnal = p_id_jurnal;

    -- 3. UPSERT buku besar bulan berjalan.
    --    Baris baru: saldo_awal diambil dari saldo_akhir bulan sebelumnya.
    INSERT INTO buku_besar_periode (
        id_entitas, periode_tahun, periode_bulan, kode_anak,
        saldo_awal_debet, saldo_awal_kredit,
        mutasi_debet, mutasi_kredit,
        saldo_akhir_debet, saldo_akhir_kredit, dihitung_pada
    )
    SELECT
        v_id_entitas, v_tahun, v_bulan, jd.kode_anak,
        IFNULL(prev.saldo_akhir_debet, 0),
        IFNULL(prev.saldo_akhir_kredit, 0),
        jd.kredit,
        jd.debet,
        IFNULL(prev.saldo_akhir_debet, 0)  + jd.kredit,
        IFNULL(prev.saldo_akhir_kredit, 0) + jd.debet,
        NOW()
    FROM jurnal_detail jd
    LEFT JOIN buku_besar_periode prev
           ON prev.id_entitas    = v_id_entitas
          AND prev.periode_tahun = IF(v_bulan = 1, v_tahun - 1, v_tahun)
          AND prev.periode_bulan = IF(v_bulan = 1, 12, v_bulan - 1)
          AND prev.kode_anak     = jd.kode_anak
    WHERE jd.id_jurnal = p_id_jurnal
    ON DUPLICATE KEY UPDATE
        mutasi_debet       = mutasi_debet       + VALUES(mutasi_debet),
        mutasi_kredit      = mutasi_kredit      + VALUES(mutasi_kredit),
        saldo_akhir_debet  = saldo_akhir_debet  + VALUES(mutasi_debet),
        saldo_akhir_kredit = saldo_akhir_kredit + VALUES(mutasi_kredit),
        dihitung_pada      = NOW();

    SELECT v_new_id_jurnal AS id_jurnal;
END
SQL);
    }
};
