<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * AkuntansiDemoSeeder - Data dummy end-to-end untuk KDMP-A
 *
 * Skenario yang dicakup (bulan Januari-Maret 2026):
 *
 *  A. Jurnal Pembuka (JOP)          - Saldo awal kas, modal, persediaan
 *  B. Simpanan Anggota (SPK + SWJ)  - Setor simpanan pokok & wajib
 *  C. Pembelian Tunai (BTU)         - Beli Beras Medium, bayar kas
 *  D. Pembelian Kredit (BKR)        - Beli Beras, timbulkan hutang dagang
 *  E. Penjualan Tunai Warga (JTW)   - Jual ke anggota, kas masuk
 *  F. Penjualan Transfer (JTFW)     - Jual ke non-anggota, Bank A
 *  G. Bayar Hutang (BHU)            - Lunasi hutang dagang dari D
 *  H. Biaya Operasional (KSK)       - Listrik + telepon, via kas manual
 *  I. Kas Masuk Lain (KSM)          - Sewa tempat, pendapatan lain-lain
 *
 * CATATAN TEKNIS:
 *   - Seeder ini bypass JurnalService (karena Auth::id() tidak tersedia saat
 *     seeding). Jurnal ditulis LANGSUNG via CALL sp_post_jurnal dengan
 *     id_user dari tabel pengguna KDMP-A.
 *   - Semua nilai sudah balance (Debit = Kredit) sebelum dikirim ke SP.
 *
 * CARA JALANKAN (setelah db:seed utama sudah jalan):
 *   php artisan db:seed --class=AkuntansiDemoSeeder
 */
class AkuntansiDemoSeeder extends Seeder
{
    private int  $idEntitas;
    private int  $idUser;
    private ?int $idKasUtama;
    private ?int $idBankA;
    private ?int $idSuplier;
    private ?int $idWarga;
    private ?int $idBarasBeras;
    private ?int $idGudang;
    private ?int $idUnitSembako;

    public function run(): void
    {
        // Resolusi ID entitas KDMP-A
        $this->idEntitas = DB::table('entitas')
            ->where('kode_entitas', 'KDMP-A')
            ->value('id_entitas');

        if (! $this->idEntitas) {
            $this->command->error('Entitas KDMP-A tidak ditemukan. Jalankan DataAwalSeeder dulu.');
            return;
        }

        // Resolusi data pendukung
        $this->idUser = DB::table('pengguna')
            ->where('id_entitas', $this->idEntitas)
            ->value('id') ?? 1;

        $this->idKasUtama = DB::table('master_kas_bank')
            ->where('id_entitas', $this->idEntitas)
            ->where('jenis', 'kas')
            ->value('id_kas_bank');

        $this->idBankA = DB::table('master_kas_bank')
            ->where('id_entitas', $this->idEntitas)
            ->where('jenis', 'bank')
            ->value('id_kas_bank');

        $this->idSuplier = DB::table('master_pihak')
            ->where('id_entitas', $this->idEntitas)
            ->where('jenis_pihak', 'supplier')
            ->value('id_pihak');

        $this->idWarga = DB::table('master_pihak')
            ->where('id_entitas', $this->idEntitas)
            ->where('jenis_pihak', 'warga')
            ->value('id_pihak');

        $this->idUnitSembako = DB::table('master_unit_usaha')
            ->where('kode_unit_usaha', 'SEMBAKO')
            ->value('id_unit_usaha');

        $this->idGudang = DB::table('gudang')
            ->where('id_entitas', $this->idEntitas)
            ->value('id_gudang');

        // Coba BRG-BERAS-MEDIUM dulu, fallback ke barang pertama di unit SEMBAKO
        $this->idBarasBeras = DB::table('master_barang')
            ->where('kode_barang', 'BRG-BERAS-MEDIUM')
            ->value('id_barang')
            ?? DB::table('master_barang')
                ->where('id_unit_usaha', $this->idUnitSembako)
                ->value('id_barang')
            ?? DB::table('master_barang')->value('id_barang');

        $satuanKgId = DB::table('satuan')->where('kode_satuan', 'KG')->value('id');

        $this->command->info("Membuat data dummy akuntansi untuk KDMP-A (id_entitas={$this->idEntitas}, id_user={$this->idUser})...");

        // ================================================================
        // CLEANUP: hapus data lama agar seeder bisa dijalankan ulang
        // ================================================================
        $this->command->line('  [0] Membersihkan data demo lama...');
        $this->bersihkanDataLama();

        // ================================================================
        // A. JURNAL PEMBUKA - Saldo Awal per 1 Januari 2026
        // ================================================================
        $this->command->line('  [A] Jurnal Pembuka (JOP)...');

        $this->buatJurnalManual(
            noJurnal:       'JRN-2026-01-0001',
            tanggal:        '2026-01-01',
            tahun:          2026,
            bulan:          1,
            kodeTransaksi:  'JOP',
            jenisJurnal:    'SALDO_AWAL',
            keterangan:     'Jurnal Pembuka - Saldo Awal Koperasi Desa Mekar Jaya per 1 Jan 2026',
            sourceType:     'AkuntansiDemoSeeder',
            sourceId:       1001,
            baris: [
                ['kode_anak' => '1111',  'debet' => 5000000,  'kredit' => 0],
                ['kode_anak' => '11121', 'debet' => 10000000, 'kredit' => 0],
                ['kode_anak' => '1123',  'debet' => 800000,   'kredit' => 0],
                ['kode_anak' => '322',   'debet' => 0,        'kredit' => 15800000],
            ]
        );

        // ================================================================
        // B. SIMPANAN ANGGOTA - Setor simpanan pokok & wajib (5 Jan)
        // ================================================================
        $this->command->line('  [B] Simpanan Anggota (SPK + SWJ)...');

        $idSimpananPokok = DB::table('simpanan_anggota')->insertGetId([
            'id_entitas'     => $this->idEntitas,
            'id_pihak'       => $this->idWarga,
            'jenis'          => 'pokok',
            'tanggal'        => '2026-01-05',
            'arah'           => 'setor',
            'nilai'          => 500000,
            'id_kas_bank'    => $this->idKasUtama,
            'status_posting' => 'F',
            'created_at'     => now(),
        ]);

        $idSimpananWajib = DB::table('simpanan_anggota')->insertGetId([
            'id_entitas'     => $this->idEntitas,
            'id_pihak'       => $this->idWarga,
            'jenis'          => 'wajib',
            'tanggal'        => '2026-01-05',
            'arah'           => 'setor',
            'nilai'          => 200000,
            'id_kas_bank'    => $this->idKasUtama,
            'status_posting' => 'F',
            'created_at'     => now(),
        ]);

        $jSpkId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-01-0002',
            tanggal:        '2026-01-05',
            tahun:          2026,
            bulan:          1,
            kodeTransaksi:  'SPK',
            jenisJurnal:    'OTOMATIS',
            keterangan:     'Setor Simpanan Pokok - Warga Contoh',
            sourceType:     'App\Models\Keuangan\SimpananAnggota',
            sourceId:       $idSimpananPokok,
            baris: [
                ['kode_anak' => '1111', 'debet' => 500000, 'kredit' => 0, 'id_pihak' => $this->idWarga],
                ['kode_anak' => '311',  'debet' => 0, 'kredit' => 500000, 'id_pihak' => $this->idWarga],
            ]
        );
        DB::table('simpanan_anggota')->where('id_simpanan', $idSimpananPokok)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jSpkId]);

        $jSwjId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-01-0003',
            tanggal:        '2026-01-05',
            tahun:          2026,
            bulan:          1,
            kodeTransaksi:  'SWJ',
            jenisJurnal:    'OTOMATIS',
            keterangan:     'Setor Simpanan Wajib - Warga Contoh',
            sourceType:     'App\Models\Keuangan\SimpananAnggota',
            sourceId:       $idSimpananWajib,
            baris: [
                ['kode_anak' => '1111', 'debet' => 200000, 'kredit' => 0, 'id_pihak' => $this->idWarga],
                ['kode_anak' => '312',  'debet' => 0, 'kredit' => 200000, 'id_pihak' => $this->idWarga],
            ]
        );
        DB::table('simpanan_anggota')->where('id_simpanan', $idSimpananWajib)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jSwjId]);

        // ================================================================
        // C. PEMBELIAN TUNAI (BTU) - 10 Januari 2026
        //    200 kg Beras Medium @ Rp 12.000 = Rp 2.400.000
        // ================================================================
        $this->command->line('  [C] Pembelian Tunai Beras (BTU)...');

        $totalBtu = 200 * 12000;
        $idPembelianBtu = DB::table('pembelian')->insertGetId([
            'id_entitas'        => $this->idEntitas,
            'kode_pembelian'    => 'PBL-2026-01-0001',
            'id_pihak'          => $this->idSuplier,
            'id_unit_usaha'     => $this->idUnitSembako,
            'id_gudang'         => $this->idGudang,
            'tanggal_transaksi' => '2026-01-10',
            'jenis_pembayaran'  => 'tunai',
            'id_kas_bank'       => $this->idKasUtama,
            'total_pembelian'   => $totalBtu,
            'status'            => 'selesai',
            'status_posting'    => 'F',
            'created_by'        => $this->idUser,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        DB::table('detail_pembelian')->insert([
            'id_pembelian'       => $idPembelianBtu,
            'id_barang'          => $this->idBarasBeras,
            'id_satuan_input'    => $satuanKgId,
            'qty_input'          => 200,
            'faktor_konversi'    => 1,
            'qty_dasar'          => 200,
            'harga_satuan_input' => 12000,
            'subtotal'           => $totalBtu,
        ]);
        $this->upsertStok($this->idGudang, $this->idBarasBeras, 200, 12000);

        $jBtuId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-01-0004',
            tanggal:        '2026-01-10',
            tahun:          2026,
            bulan:          1,
            kodeTransaksi:  'BTU',
            jenisJurnal:    'OTOMATIS',
            keterangan:     'Beli Tunai 200 kg Beras Medium @ Rp 12.000 - Sumber Rejeki',
            sourceType:     'App\Models\Pembelian\Pembelian',
            sourceId:       $idPembelianBtu,
            baris: [
                ['kode_anak' => '1123', 'debet' => $totalBtu, 'kredit' => 0, 'id_pihak' => $this->idSuplier],
                ['kode_anak' => '1111', 'debet' => 0, 'kredit' => $totalBtu, 'id_pihak' => $this->idSuplier],
            ]
        );
        DB::table('pembelian')->where('id_pembelian', $idPembelianBtu)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jBtuId]);

        // ================================================================
        // D. PEMBELIAN KREDIT (BKR) - 15 Januari 2026
        //    100 kg Beras @ Rp 12.000 = Rp 1.200.000, jatuh tempo 14 Feb
        // ================================================================
        $this->command->line('  [D] Pembelian Kredit Beras (BKR)...');

        $totalBkr = 100 * 12000;
        $idPembelianBkr = DB::table('pembelian')->insertGetId([
            'id_entitas'        => $this->idEntitas,
            'kode_pembelian'    => 'PBL-2026-01-0002',
            'id_pihak'          => $this->idSuplier,
            'id_unit_usaha'     => $this->idUnitSembako,
            'id_gudang'         => $this->idGudang,
            'tanggal_transaksi' => '2026-01-15',
            'jenis_pembayaran'  => 'kredit',
            'id_kas_bank'       => null,
            'tgl_jatuh_tempo'   => '2026-02-14',
            'total_pembelian'   => $totalBkr,
            'status'            => 'selesai',
            'status_posting'    => 'F',
            'created_by'        => $this->idUser,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        DB::table('detail_pembelian')->insert([
            'id_pembelian'       => $idPembelianBkr,
            'id_barang'          => $this->idBarasBeras,
            'id_satuan_input'    => $satuanKgId,
            'qty_input'          => 100,
            'faktor_konversi'    => 1,
            'qty_dasar'          => 100,
            'harga_satuan_input' => 12000,
            'subtotal'           => $totalBkr,
        ]);
        $this->upsertStok($this->idGudang, $this->idBarasBeras, 100, 12000);

        $idHutang = DB::table('hutang')->insertGetId([
            'id_entitas'      => $this->idEntitas,
            'id_pihak'        => $this->idSuplier,
            'sumber_tipe'     => 'PEMBELIAN',
            'sumber_id'       => $idPembelianBkr,
            'kode_akun'       => '2111',
            'tanggal'         => '2026-01-15',
            'tgl_jatuh_tempo' => '2026-02-14',
            'nilai_awal'      => $totalBkr,
            'nilai_terbayar'  => 0,
            'status'          => 'belum_lunas',
            'created_at'      => now(),
        ]);

        $jBkrId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-01-0005',
            tanggal:        '2026-01-15',
            tahun:          2026,
            bulan:          1,
            kodeTransaksi:  'BKR',
            jenisJurnal:    'OTOMATIS',
            keterangan:     'Beli Kredit 100 kg Beras Medium @ Rp 12.000 - Jatuh tempo 14 Feb',
            sourceType:     'App\Models\Pembelian\Pembelian',
            sourceId:       $idPembelianBkr,
            baris: [
                ['kode_anak' => '1123', 'debet' => $totalBkr, 'kredit' => 0, 'id_pihak' => $this->idSuplier],
                ['kode_anak' => '2111', 'debet' => 0, 'kredit' => $totalBkr, 'id_pihak' => $this->idSuplier],
            ]
        );
        DB::table('pembelian')->where('id_pembelian', $idPembelianBkr)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jBkrId]);

        // ================================================================
        // E. PENJUALAN TUNAI WARGA/ANGGOTA (JTW) - 20 Januari 2026
        //    50 kg @ Rp 14.000 = Rp 700.000, HPP = Rp 600.000
        // ================================================================
        $this->command->line('  [E] Penjualan Tunai ke Anggota (JTW)...');

        $totalBayarJtw = 50 * 14000;
        $totalHppJtw   = 50 * 12000;

        $idPenjualanJtw = DB::table('penjualan')->insertGetId([
            'id_entitas'         => $this->idEntitas,
            'kode_penjualan'     => 'JUL-2026-01-0001',
            'id_pihak'           => $this->idWarga,
            'id_unit_usaha'      => $this->idUnitSembako,
            'id_gudang'          => $this->idGudang,
            'tanggal_transaksi'  => '2026-01-20',
            'is_pembeli_anggota' => true,
            'id_kas_bank'        => $this->idKasUtama,
            'metode_bayar'       => 'tunai',
            'total_bruto'        => $totalBayarJtw,
            'diskon'             => 0,
            'total_bayar'        => $totalBayarJtw,
            'total_hpp'          => $totalHppJtw,
            'status'             => 'selesai',
            'status_posting'     => 'F',
            'created_by'         => $this->idUser,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        DB::table('detail_penjualan')->insert([
            'id_penjualan'     => $idPenjualanJtw,
            'id_barang'        => $this->idBarasBeras,
            'id_satuan_input'  => $satuanKgId,
            'qty_input'        => 50,
            'faktor_konversi'  => 1,
            'qty_dasar'        => 50,
            'harga_satuan'     => 14000,
            'subtotal'         => $totalBayarJtw,
            'hpp_satuan_dasar' => 12000,
            'total_hpp'        => $totalHppJtw,
        ]);
        $this->kurangiStok($this->idGudang, $this->idBarasBeras, 50);

        $jJtwId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-01-0006',
            tanggal:        '2026-01-20',
            tahun:          2026,
            bulan:          1,
            kodeTransaksi:  'JTW',
            jenisJurnal:    'OTOMATIS',
            keterangan:     'Jual Tunai 50 kg Beras ke Warga Contoh (anggota)',
            sourceType:     'App\Models\Penjualan\Penjualan',
            sourceId:       $idPenjualanJtw,
            baris: [
                ['kode_anak' => '1111', 'debet' => $totalBayarJtw, 'kredit' => 0,              'id_pihak' => $this->idWarga],
                ['kode_anak' => '411',  'debet' => 0,              'kredit' => $totalBayarJtw, 'id_pihak' => $this->idWarga],
                ['kode_anak' => '511',  'debet' => $totalHppJtw,   'kredit' => 0],
                ['kode_anak' => '1123', 'debet' => 0,              'kredit' => $totalHppJtw],
            ]
        );
        DB::table('penjualan')->where('id_penjualan', $idPenjualanJtw)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jJtwId]);

        // ================================================================
        // F. PENJUALAN TRANSFER NON-ANGGOTA (JTFW) - 25 Januari 2026
        //    30 kg @ Rp 14.500 = Rp 435.000, HPP = Rp 360.000
        // ================================================================
        $this->command->line('  [F] Penjualan Transfer ke Non-Anggota (JTFW)...');

        $totalBayarJtfw = 30 * 14500;
        $totalHppJtfw   = 30 * 12000;

        $idPenjualanJtfw = DB::table('penjualan')->insertGetId([
            'id_entitas'         => $this->idEntitas,
            'kode_penjualan'     => 'JUL-2026-01-0002',
            'id_pihak'           => $this->idWarga,
            'id_unit_usaha'      => $this->idUnitSembako,
            'id_gudang'          => $this->idGudang,
            'tanggal_transaksi'  => '2026-01-25',
            'is_pembeli_anggota' => false,
            'id_kas_bank'        => $this->idBankA,
            'metode_bayar'       => 'transfer',
            'total_bruto'        => $totalBayarJtfw,
            'diskon'             => 0,
            'total_bayar'        => $totalBayarJtfw,
            'total_hpp'          => $totalHppJtfw,
            'status'             => 'selesai',
            'status_posting'     => 'F',
            'created_by'         => $this->idUser,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        DB::table('detail_penjualan')->insert([
            'id_penjualan'     => $idPenjualanJtfw,
            'id_barang'        => $this->idBarasBeras,
            'id_satuan_input'  => $satuanKgId,
            'qty_input'        => 30,
            'faktor_konversi'  => 1,
            'qty_dasar'        => 30,
            'harga_satuan'     => 14500,
            'subtotal'         => $totalBayarJtfw,
            'hpp_satuan_dasar' => 12000,
            'total_hpp'        => $totalHppJtfw,
        ]);
        $this->kurangiStok($this->idGudang, $this->idBarasBeras, 30);

        $jJtfwId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-01-0007',
            tanggal:        '2026-01-25',
            tahun:          2026,
            bulan:          1,
            kodeTransaksi:  'JTFW',
            jenisJurnal:    'OTOMATIS',
            keterangan:     'Jual Transfer 30 kg Beras ke Non-Anggota via Bank A',
            sourceType:     'App\Models\Penjualan\Penjualan',
            sourceId:       $idPenjualanJtfw,
            baris: [
                ['kode_anak' => '11121', 'debet' => $totalBayarJtfw, 'kredit' => 0],
                ['kode_anak' => '412',   'debet' => 0, 'kredit' => $totalBayarJtfw],
                ['kode_anak' => '511',   'debet' => $totalHppJtfw, 'kredit' => 0],
                ['kode_anak' => '1123',  'debet' => 0, 'kredit' => $totalHppJtfw],
            ]
        );
        DB::table('penjualan')->where('id_penjualan', $idPenjualanJtfw)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jJtfwId]);

        // ================================================================
        // G. BAYAR HUTANG (BHU) - 10 Februari 2026
        //    Lunasi hutang BKR Rp 1.200.000
        // ================================================================
        $this->command->line('  [G] Bayar Hutang Dagang (BHU)...');

        $idPelunasan = DB::table('pelunasan')->insertGetId([
            'id_entitas'     => $this->idEntitas,
            'kode_pelunasan' => 'PLN-2026-02-0001',
            'jenis'          => 'bayar_hutang',
            'id_pihak'       => $this->idSuplier,
            'tanggal'        => '2026-02-10',
            'id_kas_bank'    => $this->idKasUtama,
            'total_nilai'    => $totalBkr,
            'status_posting' => 'F',
            'catatan'        => 'Pelunasan hutang pembelian kredit Jan 2026',
            'created_by'     => $this->idUser,
            'created_at'     => now(),
        ]);
        DB::table('pelunasan_detail')->insert([
            'id_pelunasan' => $idPelunasan,
            'id_hutang'    => $idHutang,
            'nilai_bayar'  => $totalBkr,
        ]);

        $jBhuId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-02-0001',
            tanggal:        '2026-02-10',
            tahun:          2026,
            bulan:          2,
            kodeTransaksi:  'BHU',
            jenisJurnal:    'OTOMATIS',
            keterangan:     'Bayar hutang dagang ke Sumber Rejeki - Rp 1.200.000',
            sourceType:     'App\Models\Keuangan\Pelunasan',
            sourceId:       $idPelunasan,
            baris: [
                ['kode_anak' => '2111', 'debet' => $totalBkr, 'kredit' => 0, 'id_pihak' => $this->idSuplier],
                ['kode_anak' => '1111', 'debet' => 0, 'kredit' => $totalBkr, 'id_pihak' => $this->idSuplier],
            ]
        );
        DB::table('pelunasan')->where('id_pelunasan', $idPelunasan)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jBhuId]);
        DB::table('hutang')->where('id_hutang', $idHutang)
            ->update(['nilai_terbayar' => $totalBkr, 'status' => 'lunas']);

        // ================================================================
        // H. BIAYA OPERASIONAL (KSK) - Listrik & Telepon, 15 Feb 2026
        // ================================================================
        $this->command->line('  [H] Biaya Listrik & Telepon (KSK)...');

        // Listrik Rp 350.000
        $idTrxListrik = DB::table('kas_transaksi')->insertGetId([
            'id_entitas'      => $this->idEntitas,
            'kode_trx'        => 'KSK-2026-02-0001',
            'tanggal'         => '2026-02-15',
            'jenis'           => 'keluar',
            'id_kas_bank'     => $this->idKasUtama,
            'kode_akun_lawan' => '623',
            'nilai'           => 350000,
            'keterangan'      => 'Bayar tagihan listrik Februari 2026',
            'status_posting'  => 'F',
            'created_by'      => $this->idUser,
            'created_at'      => now(),
        ]);
        $jListrikId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-02-0002',
            tanggal:        '2026-02-15',
            tahun:          2026,
            bulan:          2,
            kodeTransaksi:  'KSK',
            jenisJurnal:    'MANUAL',
            keterangan:     'Biaya Listrik Februari 2026',
            sourceType:     'App\Models\Keuangan\KasTransaksi',
            sourceId:       $idTrxListrik,
            baris: [
                ['kode_anak' => '623',  'debet' => 350000, 'kredit' => 0],
                ['kode_anak' => '1111', 'debet' => 0, 'kredit' => 350000],
            ]
        );
        DB::table('kas_transaksi')->where('id_kas_trx', $idTrxListrik)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jListrikId]);

        // Telepon & Internet Rp 200.000
        $idTrxTlp = DB::table('kas_transaksi')->insertGetId([
            'id_entitas'      => $this->idEntitas,
            'kode_trx'        => 'KSK-2026-02-0002',
            'tanggal'         => '2026-02-15',
            'jenis'           => 'keluar',
            'id_kas_bank'     => $this->idKasUtama,
            'kode_akun_lawan' => '622',
            'nilai'           => 200000,
            'keterangan'      => 'Bayar tagihan telepon & internet Februari 2026',
            'status_posting'  => 'F',
            'created_by'      => $this->idUser,
            'created_at'      => now(),
        ]);
        $jTlpId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-02-0003',
            tanggal:        '2026-02-15',
            tahun:          2026,
            bulan:          2,
            kodeTransaksi:  'KSK',
            jenisJurnal:    'MANUAL',
            keterangan:     'Biaya Telepon & Internet Februari 2026',
            sourceType:     'App\Models\Keuangan\KasTransaksi',
            sourceId:       $idTrxTlp,
            baris: [
                ['kode_anak' => '622',  'debet' => 200000, 'kredit' => 0],
                ['kode_anak' => '1111', 'debet' => 0, 'kredit' => 200000],
            ]
        );
        DB::table('kas_transaksi')->where('id_kas_trx', $idTrxTlp)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jTlpId]);

        // ================================================================
        // I. KAS MASUK LAIN (KSM) - Sewa Tempat, 1 Maret 2026
        // ================================================================
        $this->command->line('  [I] Pendapatan Sewa Tempat (KSM)...');

        $idTrxSewa = DB::table('kas_transaksi')->insertGetId([
            'id_entitas'      => $this->idEntitas,
            'kode_trx'        => 'KSM-2026-03-0001',
            'tanggal'         => '2026-03-01',
            'jenis'           => 'masuk',
            'id_kas_bank'     => $this->idKasUtama,
            'kode_akun_lawan' => '711',
            'nilai'           => 500000,
            'keterangan'      => 'Penerimaan sewa tempat lapak - Maret 2026',
            'status_posting'  => 'F',
            'created_by'      => $this->idUser,
            'created_at'      => now(),
        ]);
        $jSewaId = $this->buatJurnalManual(
            noJurnal:       'JRN-2026-03-0001',
            tanggal:        '2026-03-01',
            tahun:          2026,
            bulan:          3,
            kodeTransaksi:  'KSM',
            jenisJurnal:    'MANUAL',
            keterangan:     'Pendapatan Lain-lain - Sewa tempat lapak Maret 2026',
            sourceType:     'App\Models\Keuangan\KasTransaksi',
            sourceId:       $idTrxSewa,
            baris: [
                ['kode_anak' => '1111', 'debet' => 500000, 'kredit' => 0],
                ['kode_anak' => '711',  'debet' => 0, 'kredit' => 500000],
            ]
        );
        DB::table('kas_transaksi')->where('id_kas_trx', $idTrxSewa)
            ->update(['status_posting' => 'T', 'id_jurnal' => $jSewaId]);

        // ================================================================
        // RINGKASAN
        // ================================================================
        $jumlahJurnal = DB::table('jurnal_header')
            ->where('id_entitas', $this->idEntitas)
            ->count();

        $this->command->newLine();
        $this->command->info("Selesai! Total jurnal di KDMP-A: {$jumlahJurnal} entri");
        $this->command->table(
            ['Skenario', 'No. Jurnal', 'Nilai (Rp)'],
            [
                ['A. Jurnal Pembuka (JOP)',     'JRN-2026-01-0001', '18.000.000'],
                ['B. Simpanan Pokok (SPK)',     'JRN-2026-01-0002', '500.000'],
                ['B. Simpanan Wajib (SWJ)',     'JRN-2026-01-0003', '200.000'],
                ['C. Beli Tunai Beras (BTU)',   'JRN-2026-01-0004', '2.400.000'],
                ['D. Beli Kredit Beras (BKR)', 'JRN-2026-01-0005', '1.200.000'],
                ['E. Jual Tunai Anggota (JTW)', 'JRN-2026-01-0006', '700.000 (HPP 600.000)'],
                ['F. Jual Transfer Non (JTFW)', 'JRN-2026-01-0007', '435.000 (HPP 360.000)'],
                ['G. Bayar Hutang (BHU)',       'JRN-2026-02-0001', '1.200.000'],
                ['H. Biaya Listrik (KSK)',      'JRN-2026-02-0002', '350.000'],
                ['H. Biaya Telepon (KSK)',      'JRN-2026-02-0003', '200.000'],
                ['I. Sewa Tempat (KSM)',        'JRN-2026-03-0001', '500.000'],
            ]
        );
        $this->command->newLine();
        $this->command->line('Login: manajer@kdmp-a.test | password: password');
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Hapus semua data demo sebelumnya untuk KDMP-A agar seeder bisa
     * dijalankan ulang tanpa error duplikat.
     * Urutan penghapusan mengikuti FK constraints.
     */
    private function bersihkanDataLama(): void
    {
        $e = $this->idEntitas;

        // Nonaktifkan FK check sementara untuk kemudahan cleanup
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop trigger proteksi jurnal sementara (trigger mencegah hapus POSTED)
        DB::unprepared('DROP TRIGGER IF EXISTS trg_jurnal_detail_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_jurnal_detail_no_update');

        // Jurnal detail & header
        $jurnalIds = DB::table('jurnal_header')->where('id_entitas', $e)->pluck('id_jurnal');
        if ($jurnalIds->isNotEmpty()) {
            DB::table('jurnal_detail')->whereIn('id_jurnal', $jurnalIds)->delete();
        }
        DB::table('buku_besar_periode')->where('id_entitas', $e)->delete();
        DB::table('jurnal_header')->where('id_entitas', $e)->delete();

        // Operasional
        DB::table('pelunasan_detail')
            ->whereIn('id_pelunasan', DB::table('pelunasan')->where('id_entitas', $e)->pluck('id_pelunasan'))
            ->delete();
        DB::table('pelunasan')->where('id_entitas', $e)->delete();
        DB::table('hutang')->where('id_entitas', $e)->delete();
        DB::table('piutang')->where('id_entitas', $e)->delete();
        DB::table('simpanan_anggota')->where('id_entitas', $e)->delete();
        DB::table('kas_transaksi')->where('id_entitas', $e)->delete();

        // Penjualan & pembelian
        DB::table('detail_penjualan')
            ->whereIn('id_penjualan', DB::table('penjualan')->where('id_entitas', $e)->pluck('id_penjualan'))
            ->delete();
        DB::table('penjualan')->where('id_entitas', $e)->delete();
        DB::table('detail_pembelian')
            ->whereIn('id_pembelian', DB::table('pembelian')->where('id_entitas', $e)->pluck('id_pembelian'))
            ->delete();
        DB::table('pembelian')->where('id_entitas', $e)->delete();

        // Stok (hanya untuk gudang KDMP-A)
        $gudangIds = DB::table('gudang')->where('id_entitas', $e)->pluck('id_gudang');
        if ($gudangIds->isNotEmpty()) {
            DB::table('kartu_stok')->whereIn('id_gudang', $gudangIds)->delete();
        }

        // Pulihkan trigger dan FK check
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Recreate trigger proteksi jurnal
        DB::unprepared("
        CREATE TRIGGER trg_jurnal_detail_no_update
        BEFORE UPDATE ON jurnal_detail
        FOR EACH ROW
        BEGIN
            DECLARE v_status VARCHAR(10);
            SELECT status INTO v_status FROM jurnal_header WHERE id_jurnal = OLD.id_jurnal;
            IF v_status <> 'DRAFT' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Detail jurnal POSTED tidak boleh diubah. Gunakan jurnal pembalik.';
            END IF;
        END
        ");

        DB::unprepared("
        CREATE TRIGGER trg_jurnal_detail_no_delete
        BEFORE DELETE ON jurnal_detail
        FOR EACH ROW
        BEGIN
            DECLARE v_status VARCHAR(10);
            SELECT status INTO v_status FROM jurnal_header WHERE id_jurnal = OLD.id_jurnal;
            IF v_status <> 'DRAFT' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Detail jurnal POSTED tidak boleh dihapus.';
            END IF;
        END
        ");
    }

    /**
     * Panggil sp_post_jurnal dan kembalikan id_jurnal baru.
     * Balance D=K divalidasi sebelum dikirim ke SP.
     */
    private function buatJurnalManual(
        string $noJurnal,
        string $tanggal,
        int    $tahun,
        int    $bulan,
        string $kodeTransaksi,
        string $jenisJurnal,
        string $keterangan,
        string $sourceType,
        int    $sourceId,
        array  $baris,
    ): int {
        $totalD = array_sum(array_column($baris, 'debet'));
        $totalK = array_sum(array_column($baris, 'kredit'));
        if (abs($totalD - $totalK) > 0.01) {
            throw new \LogicException("Jurnal {$noJurnal} tidak balance: D={$totalD} K={$totalK}");
        }

        $barisJson = array_map(function (array $b, int $idx): array {
            return [
                'urutan'     => $idx + 1,
                'kode_anak'  => $b['kode_anak'],
                'debet'      => (float) ($b['debet'] ?? 0),
                'kredit'     => (float) ($b['kredit'] ?? 0),
                'id_pihak'   => $b['id_pihak'] ?? null,
                'keterangan' => $b['keterangan'] ?? null,
            ];
        }, $baris, array_keys($baris));

        $payload = json_encode([
            'no_jurnal'      => $noJurnal,
            'nomor_nota'     => null,
            'tanggal_jurnal' => $tanggal,
            'periode_tahun'  => $tahun,
            'periode_bulan'  => $bulan,
            'kode_transaksi' => $kodeTransaksi,
            'jenis_jurnal'   => $jenisJurnal,
            'source_type'    => $sourceType,
            'source_id'      => $sourceId,
            'keterangan'     => $keterangan,
            'baris'          => $barisJson,
        ], JSON_UNESCAPED_UNICODE);

        DB::statement("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

        $hasil = DB::select('CALL sp_post_jurnal(?, ?, ?)', [
            $this->idEntitas,
            $this->idUser,
            $payload,
        ]);

        return $hasil[0]->id_jurnal;
    }

    /**
     * Catat mutasi masuk di kartu_stok (ledger).
     * Saldo dihitung dari saldo baris terakhir + qty baru.
     */
    private function upsertStok(int $idGudang, int $idBarang, float $qty, float $hargaBeli): void
    {
        // Ambil saldo terakhir
        $last = DB::table('kartu_stok')
            ->where('id_entitas', $this->idEntitas)
            ->where('id_gudang', $idGudang)
            ->where('id_barang', $idBarang)
            ->orderByDesc('id_kartu')
            ->first();

        $saldoQtyLama   = $last ? (float) $last->saldo_qty   : 0;
        $saldoNilaiLama = $last ? (float) $last->saldo_nilai  : 0;
        $hppLama        = $last ? (float) $last->hpp_rata2_setelah : 0;

        $qtyBaru   = $saldoQtyLama + $qty;
        $nilaiMutasi = $qty * $hargaBeli;
        $saldoNilaiBaru = $saldoNilaiLama + $nilaiMutasi;
        $hppBaru = $qtyBaru > 0 ? ($saldoNilaiBaru / $qtyBaru) : $hargaBeli;

        DB::table('kartu_stok')->insert([
            'id_entitas'       => $this->idEntitas,
            'id_gudang'        => $idGudang,
            'id_barang'        => $idBarang,
            'tanggal'          => now()->toDateString(),
            'jenis_mutasi'     => 'IN',
            'ref_tipe'         => 'PENERIMAAN',
            'ref_id'           => null,
            'qty_masuk'        => $qty,
            'qty_keluar'       => 0,
            'harga_satuan'     => $hargaBeli,
            'nilai_mutasi'     => $nilaiMutasi,
            'saldo_qty'        => $qtyBaru,
            'saldo_nilai'      => $saldoNilaiBaru,
            'hpp_rata2_setelah'=> $hppBaru,
            'created_by'       => $this->idUser,
            'created_at'       => now(),
        ]);
    }

    /**
     * Catat mutasi keluar di kartu_stok (ledger).
     */
    private function kurangiStok(int $idGudang, int $idBarang, float $qty): void
    {
        $last = DB::table('kartu_stok')
            ->where('id_entitas', $this->idEntitas)
            ->where('id_gudang', $idGudang)
            ->where('id_barang', $idBarang)
            ->orderByDesc('id_kartu')
            ->first();

        $saldoQtyLama   = $last ? (float) $last->saldo_qty   : 0;
        $saldoNilaiLama = $last ? (float) $last->saldo_nilai  : 0;
        $hppLama        = $last ? (float) $last->hpp_rata2_setelah : 0;

        $qtyBaru    = $saldoQtyLama - $qty;
        $nilaiKeluar = $qty * $hppLama;
        $saldoNilaiBaru = max(0, $saldoNilaiLama - $nilaiKeluar);

        DB::table('kartu_stok')->insert([
            'id_entitas'       => $this->idEntitas,
            'id_gudang'        => $idGudang,
            'id_barang'        => $idBarang,
            'tanggal'          => now()->toDateString(),
            'jenis_mutasi'     => 'OUT',
            'ref_tipe'         => 'PENJUALAN',
            'ref_id'           => null,
            'qty_masuk'        => 0,
            'qty_keluar'       => $qty,
            'harga_satuan'     => $hppLama,
            'nilai_mutasi'     => $nilaiKeluar,
            'saldo_qty'        => $qtyBaru,
            'saldo_nilai'      => $saldoNilaiBaru,
            'hpp_rata2_setelah'=> $hppLama,
            'created_by'       => $this->idUser,
            'created_at'       => now(),
        ]);
    }
}
