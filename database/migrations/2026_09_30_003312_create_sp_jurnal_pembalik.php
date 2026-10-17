<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_jurnal_pembalik');

        DB::unprepared("
CREATE PROCEDURE sp_jurnal_pembalik(
    IN p_id_jurnal BIGINT UNSIGNED,
    IN p_user_id BIGINT UNSIGNED,
    IN p_alasan VARCHAR(255)
)
BEGIN
    DECLARE v_id_entitas BIGINT UNSIGNED;
    DECLARE v_no_jurnal_lama VARCHAR(50);
    DECLARE v_kode_transaksi VARCHAR(50);
    DECLARE v_total_debet DECIMAL(18,2);
    DECLARE v_total_kredit DECIMAL(18,2);
    DECLARE v_status VARCHAR(20);
    DECLARE v_sudah_dibalik INT;
    
    DECLARE v_new_id_jurnal BIGINT UNSIGNED;
    DECLARE v_new_no_jurnal VARCHAR(50);
    DECLARE v_tahun SMALLINT UNSIGNED;
    DECLARE v_bulan TINYINT UNSIGNED;

    -- Exit handler
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Validasi 1: Jurnal asal ada dan POSTED
    SELECT id_entitas, no_jurnal, kode_transaksi, total_debet, total_kredit, status 
    INTO v_id_entitas, v_no_jurnal_lama, v_kode_transaksi, v_total_debet, v_total_kredit, v_status
    FROM jurnal_header 
    WHERE id_jurnal = p_id_jurnal;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal tidak ditemukan.';
    END IF;

    IF v_status != 'POSTED' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya jurnal dengan status POSTED yang dapat dibalik.';
    END IF;

    -- Validasi 2: Belum pernah dibalik
    SELECT COUNT(*) INTO v_sudah_dibalik 
    FROM jurnal_header 
    WHERE id_jurnal_asal = p_id_jurnal;

    IF v_sudah_dibalik > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal ini sudah pernah dibalik.';
    END IF;

    START TRANSACTION;

    -- Tetapkan periode hari ini
    SET v_tahun = YEAR(NOW());
    SET v_bulan = MONTH(NOW());
    
    -- Generate Nomor Jurnal Pembalik (Sederhana: REV-lama)
    SET v_new_no_jurnal = CONCAT('REV-', v_no_jurnal_lama);

    -- 1. INSERT HEADER PEMBALIK
    INSERT INTO jurnal_header (
        id_entitas, no_jurnal, tanggal_jurnal, periode_tahun, periode_bulan,
        kode_transaksi, jenis_jurnal, id_jurnal_asal, keterangan,
        total_debet, total_kredit, status, created_by, posted_by, posted_at, created_at
    ) VALUES (
        v_id_entitas, v_new_no_jurnal, CURRENT_DATE(), v_tahun, v_bulan,
        v_kode_transaksi, 'PEMBALIK', p_id_jurnal, CONCAT('Koreksi untuk Transaksi ID ', p_id_jurnal, ': ', p_alasan),
        v_total_kredit, v_total_debet, 'POSTED', p_user_id, p_user_id, NOW(), NOW()
    );

    SET v_new_id_jurnal = LAST_INSERT_ID();

    -- 2. COPY & BALIK DETAILS (Debet jadi Kredit, Kredit jadi Debet)
    INSERT INTO jurnal_detail (
        id_jurnal, urutan, kode_anak, debet, kredit, id_pihak, keterangan
    )
    SELECT 
        v_new_id_jurnal, urutan, kode_anak, kredit, debet, id_pihak, keterangan
    FROM jurnal_detail
    WHERE id_jurnal = p_id_jurnal;

    -- 3. UPSERT BUKU BESAR PERIODE (Secara Massal menggunakan INSERT ... SELECT ... ON DUPLICATE KEY UPDATE)
    INSERT INTO buku_besar_periode (
        id_entitas, periode_tahun, periode_bulan, kode_anak,
        saldo_awal_debet, saldo_awal_kredit, 
        mutasi_debet, mutasi_kredit,
        saldo_akhir_debet, saldo_akhir_kredit, dihitung_pada
    )
    SELECT 
        v_id_entitas, v_tahun, v_bulan, jd.kode_anak,
        0, 0, -- Saldo awal diabaikan dulu, akan bergantung pada UPDATE jika sudah ada, atau jika baru, biarkan 0 (butuh logic lebih rumit untuk saldo awal jika ini adalah akun baru di bulan ini, tapi kita set 0 untuk aman)
        jd.kredit, jd.debet, -- Ingat: kita men-select dari jurnal lama, jadi mutasi pembalik = kredit dari lama adalah debet baru
        jd.kredit, jd.debet, NOW()
    FROM jurnal_detail jd
    WHERE jd.id_jurnal = p_id_jurnal
    ON DUPLICATE KEY UPDATE
        mutasi_debet = mutasi_debet + VALUES(mutasi_debet),
        mutasi_kredit = mutasi_kredit + VALUES(mutasi_kredit),
        saldo_akhir_debet = saldo_akhir_debet + VALUES(mutasi_debet),
        saldo_akhir_kredit = saldo_akhir_kredit + VALUES(mutasi_kredit),
        dihitung_pada = NOW();

    -- Return ID Jurnal Pembalik
    SELECT v_new_id_jurnal AS id_jurnal;

    COMMIT;
END
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_jurnal_pembalik');
    }
};
