<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Revisi sp_tutup_tahun (menggantikan versi 2026_01_03_000000).
 *
 * Perubahan:
 *   - Tahun tanpa transaksi nominal (pendapatan = biaya = 0): tidak membuat jurnal
 *     apa pun, langsung mengembalikan ringkasan. Entitas baru tetap bisa dikunci.
 *   - Laba bersih = 0: jurnal alokasi hasil usaha (JTP-xxxx-002) dilewati.
 *   - Keterangan jurnal: "Alokasi Hasil Usaha" (netral; bukan hanya SHU koperasi).
 *   - Logika laba/rugi dan perhitungan lain tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_tutup_tahun');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_tutup_tahun(
    IN p_id_entitas  BIGINT UNSIGNED,
    IN p_tahun        SMALLINT UNSIGNED,
    IN p_user_id      BIGINT UNSIGNED
)
main: BEGIN
    DECLARE v_kode_anak     VARCHAR(10) COLLATE utf8mb4_unicode_ci;
    DECLARE v_saldo_bersih  DECIMAL(18,2);
    DECLARE v_done          INT DEFAULT 0;

    DECLARE v_id_jurnal_penutup   BIGINT UNSIGNED DEFAULT 0;
    DECLARE v_id_jurnal_shu       BIGINT UNSIGNED DEFAULT 0;

    DECLARE v_urutan        INT DEFAULT 1;

    DECLARE v_total_pendapatan    DECIMAL(18,2) DEFAULT 0;
    DECLARE v_total_biaya         DECIMAL(18,2) DEFAULT 0;
    DECLARE v_laba_bersih         DECIMAL(18,2) DEFAULT 0;

    DECLARE v_persentase    DECIMAL(5,2);
    DECLARE v_kode_akun_shu VARCHAR(10) COLLATE utf8mb4_unicode_ci;
    DECLARE v_nilai_shu     DECIMAL(18,2);
    DECLARE v_total_shu_distribusi DECIMAL(18,2) DEFAULT 0;

    DECLARE v_tanggal_tutup DATE;

    DECLARE cur_pendapatan CURSOR FOR
        SELECT
            bbp.kode_anak,
            (SUM(bbp.saldo_akhir_kredit) - SUM(bbp.saldo_akhir_debet)) AS saldo_bersih
        FROM buku_besar_periode bbp
        JOIN master_coa c ON c.kode_anak = bbp.kode_anak
        WHERE bbp.id_entitas = p_id_entitas
          AND bbp.periode_tahun = p_tahun
          AND c.kelompok IN ('Pendapatan', 'Non-Operasional')
          AND c.posisi_normal = 'K'
          AND c.is_transaction = 'T'
        GROUP BY bbp.kode_anak
        HAVING saldo_bersih > 0.009;

    DECLARE cur_biaya CURSOR FOR
        SELECT
            bbp.kode_anak,
            (SUM(bbp.saldo_akhir_debet) - SUM(bbp.saldo_akhir_kredit)) AS saldo_bersih
        FROM buku_besar_periode bbp
        JOIN master_coa c ON c.kode_anak = bbp.kode_anak
        WHERE bbp.id_entitas = p_id_entitas
          AND bbp.periode_tahun = p_tahun
          AND c.kelompok IN ('Biaya', 'HPP')
          AND c.posisi_normal = 'D'
          AND c.is_transaction = 'T'
        GROUP BY bbp.kode_anak
        HAVING saldo_bersih > 0.009;

    DECLARE cur_shu CURSOR FOR
        SELECT persentase, kode_akun
        FROM config_shu
        WHERE id_entitas = p_id_entitas
          AND tahun = p_tahun
        ORDER BY id_config;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    SET v_tanggal_tutup = MAKEDATE(p_tahun, 365);
    IF DAY(v_tanggal_tutup) = 30 AND MONTH(v_tanggal_tutup) = 12 THEN
        SET v_tanggal_tutup = DATE_ADD(v_tanggal_tutup, INTERVAL 1 DAY);
    END IF;

    -- LANGKAH 1: total Pendapatan dan Biaya
    SELECT
        COALESCE(SUM(CASE WHEN c.kelompok IN ('Pendapatan','Non-Operasional') AND c.posisi_normal='K'
                          THEN bbp.saldo_akhir_kredit - bbp.saldo_akhir_debet ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN c.kelompok IN ('Biaya','HPP') AND c.posisi_normal='D'
                          THEN bbp.saldo_akhir_debet - bbp.saldo_akhir_kredit ELSE 0 END), 0)
    INTO v_total_pendapatan, v_total_biaya
    FROM buku_besar_periode bbp
    JOIN master_coa c ON c.kode_anak = bbp.kode_anak
    WHERE bbp.id_entitas = p_id_entitas
      AND bbp.periode_tahun = p_tahun
      AND c.kelompok IN ('Pendapatan','Non-Operasional','Biaya','HPP')
      AND c.is_transaction = 'T';

    SET v_laba_bersih = v_total_pendapatan - v_total_biaya;

    -- Tidak ada akun nominal bersaldo: tidak ada yang perlu ditutup.
    IF v_total_pendapatan = 0 AND v_total_biaya = 0 THEN
        SELECT
            0                  AS id_jurnal_penutup,
            0                  AS id_jurnal_shu,
            v_total_pendapatan AS total_pendapatan,
            v_total_biaya      AS total_biaya,
            v_laba_bersih      AS laba_bersih;
        LEAVE main;
    END IF;

    -- LANGKAH 2: header Jurnal Penutup
    INSERT INTO jurnal_header (
        id_entitas, no_jurnal, nomor_nota, tanggal_jurnal,
        periode_tahun, periode_bulan, kode_transaksi, jenis_jurnal,
        source_type, source_id, keterangan,
        total_debet, total_kredit, status,
        created_by, posted_by, posted_at, created_at
    ) VALUES (
        p_id_entitas,
        CONCAT('JTP-', p_tahun, '-001'),
        NULL,
        v_tanggal_tutup,
        p_tahun,
        12,
        NULL,
        'PENUTUP',
        NULL, NULL,
        CONCAT('Jurnal Penutup Tahun Buku ', p_tahun,
               ' | Pendapatan: ', FORMAT(v_total_pendapatan, 0),
               ' | Biaya: ', FORMAT(v_total_biaya, 0),
               ' | Laba Bersih: ', FORMAT(v_laba_bersih, 0)),
        v_total_pendapatan + v_total_biaya,
        v_total_pendapatan + v_total_biaya,
        'POSTED',
        p_user_id, p_user_id, NOW(), NOW()
    );

    SET v_id_jurnal_penutup = LAST_INSERT_ID();
    SET v_urutan = 1;

    -- LANGKAH 3A: akun Pendapatan -> Debet
    SET v_done = 0;
    OPEN cur_pendapatan;

    loop_pendapatan: LOOP
        FETCH cur_pendapatan INTO v_kode_anak, v_saldo_bersih;
        IF v_done THEN LEAVE loop_pendapatan; END IF;

        INSERT INTO jurnal_detail (id_jurnal, urutan, kode_anak, debet, kredit)
        VALUES (v_id_jurnal_penutup, v_urutan, v_kode_anak, v_saldo_bersih, 0);

        INSERT INTO buku_besar_periode (
            id_entitas, periode_tahun, periode_bulan, kode_anak,
            saldo_awal_debet, saldo_awal_kredit,
            mutasi_debet, mutasi_kredit,
            saldo_akhir_debet, saldo_akhir_kredit,
            dihitung_pada
        )
        SELECT
            p_id_entitas, p_tahun, 12, v_kode_anak,
            bbp.saldo_awal_debet, bbp.saldo_awal_kredit,
            0, 0,
            bbp.saldo_akhir_debet, bbp.saldo_akhir_kredit,
            NOW()
        FROM buku_besar_periode bbp
        WHERE bbp.id_entitas = p_id_entitas AND bbp.periode_tahun = p_tahun AND bbp.periode_bulan = 12 AND bbp.kode_anak = v_kode_anak
        ON DUPLICATE KEY UPDATE
            mutasi_debet       = buku_besar_periode.mutasi_debet + v_saldo_bersih,
            saldo_akhir_debet  = buku_besar_periode.saldo_awal_debet + buku_besar_periode.mutasi_debet + v_saldo_bersih,
            dihitung_pada      = NOW();

        SET v_urutan = v_urutan + 1;
    END LOOP;

    CLOSE cur_pendapatan;

    IF v_total_pendapatan > 0 THEN
        INSERT INTO jurnal_detail (id_jurnal, urutan, kode_anak, debet, kredit)
        VALUES (v_id_jurnal_penutup, v_urutan, '811', 0, v_total_pendapatan);
        SET v_urutan = v_urutan + 1;
    END IF;

    -- LANGKAH 3B: akun Biaya+HPP -> Kredit
    SET v_done = 0;
    OPEN cur_biaya;

    loop_biaya: LOOP
        FETCH cur_biaya INTO v_kode_anak, v_saldo_bersih;
        IF v_done THEN LEAVE loop_biaya; END IF;

        INSERT INTO jurnal_detail (id_jurnal, urutan, kode_anak, debet, kredit)
        VALUES (v_id_jurnal_penutup, v_urutan, v_kode_anak, 0, v_saldo_bersih);

        INSERT INTO buku_besar_periode (
            id_entitas, periode_tahun, periode_bulan, kode_anak,
            saldo_awal_debet, saldo_awal_kredit,
            mutasi_debet, mutasi_kredit,
            saldo_akhir_debet, saldo_akhir_kredit,
            dihitung_pada
        )
        SELECT
            p_id_entitas, p_tahun, 12, v_kode_anak,
            bbp.saldo_awal_debet, bbp.saldo_awal_kredit,
            0, 0,
            bbp.saldo_akhir_debet, bbp.saldo_akhir_kredit,
            NOW()
        FROM buku_besar_periode bbp
        WHERE bbp.id_entitas = p_id_entitas AND bbp.periode_tahun = p_tahun AND bbp.periode_bulan = 12 AND bbp.kode_anak = v_kode_anak
        ON DUPLICATE KEY UPDATE
            mutasi_kredit      = buku_besar_periode.mutasi_kredit + v_saldo_bersih,
            saldo_akhir_kredit = buku_besar_periode.saldo_awal_kredit + buku_besar_periode.mutasi_kredit + v_saldo_bersih,
            dihitung_pada      = NOW();

        SET v_urutan = v_urutan + 1;
    END LOOP;

    CLOSE cur_biaya;

    IF v_total_biaya > 0 THEN
        INSERT INTO jurnal_detail (id_jurnal, urutan, kode_anak, debet, kredit)
        VALUES (v_id_jurnal_penutup, v_urutan, '811', v_total_biaya, 0);
        SET v_urutan = v_urutan + 1;
    END IF;

    -- LANGKAH 4: alokasi hasil usaha ke ekuitas (dilewati jika laba bersih = 0)
    IF v_laba_bersih <> 0 THEN
        INSERT INTO jurnal_header (
            id_entitas, no_jurnal, nomor_nota, tanggal_jurnal,
            periode_tahun, periode_bulan, kode_transaksi, jenis_jurnal,
            source_type, source_id, keterangan,
            total_debet, total_kredit, status,
            created_by, posted_by, posted_at, created_at
        ) VALUES (
            p_id_entitas,
            CONCAT('JTP-', p_tahun, '-002'),
            NULL,
            v_tanggal_tutup,
            p_tahun,
            12,
            NULL,
            'PENUTUP',
            NULL, NULL,
            CONCAT('Alokasi Hasil Usaha Tahun Buku ', p_tahun,
                   ' | Laba/Rugi Bersih: ', FORMAT(v_laba_bersih, 0)),
            ABS(v_laba_bersih),
            ABS(v_laba_bersih),
            'POSTED',
            p_user_id, p_user_id, NOW(), NOW()
        );

        SET v_id_jurnal_shu = LAST_INSERT_ID();
        SET v_urutan = 1;
        SET v_done = 0;

        OPEN cur_shu;

        loop_shu: LOOP
            FETCH cur_shu INTO v_persentase, v_kode_akun_shu;
            IF v_done THEN LEAVE loop_shu; END IF;

            SET v_nilai_shu = ROUND(ABS(v_laba_bersih) * v_persentase / 100, 2);

            IF v_nilai_shu > 0 THEN
                SET v_total_shu_distribusi = v_total_shu_distribusi + v_nilai_shu;

                IF v_laba_bersih >= 0 THEN
                    -- Laba: [D] 811, [K] akun ekuitas
                    INSERT INTO jurnal_detail (id_jurnal, urutan, kode_anak, debet, kredit)
                    VALUES
                        (v_id_jurnal_shu, v_urutan,     '811',           v_nilai_shu, 0),
                        (v_id_jurnal_shu, v_urutan + 1, v_kode_akun_shu, 0, v_nilai_shu);
                ELSE
                    -- Rugi: [K] 811, [D] akun ekuitas (mengurangi ekuitas)
                    INSERT INTO jurnal_detail (id_jurnal, urutan, kode_anak, debet, kredit)
                    VALUES
                        (v_id_jurnal_shu, v_urutan,     '811',           0, v_nilai_shu),
                        (v_id_jurnal_shu, v_urutan + 1, v_kode_akun_shu, v_nilai_shu, 0);
                END IF;

                SET v_urutan = v_urutan + 2;
            END IF;
        END LOOP;

        CLOSE cur_shu;
    END IF;

    SELECT
        v_id_jurnal_penutup AS id_jurnal_penutup,
        v_id_jurnal_shu     AS id_jurnal_shu,
        v_total_pendapatan  AS total_pendapatan,
        v_total_biaya       AS total_biaya,
        v_laba_bersih       AS laba_bersih;

END
SQL);
    }

    public function down(): void
    {
        // Sengaja no-op: menghapus SP akan merusak Tutup Tahun.
        // Untuk kembali ke versi lama, jalankan ulang isi 2026_01_03_000000_create_sp_tutup_tahun.
    }
};
