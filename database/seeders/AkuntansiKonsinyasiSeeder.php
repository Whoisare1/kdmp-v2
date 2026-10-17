<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * AkuntansiKonsinyasiSeeder - Flow lengkap konsinyasi antar desa dengan jurnal
 *
 * Skenario (model_imbalan = selisih_harga):
 *
 *  KDMP-A = Pemilik titipan (Mekar Jaya)
 *  KDMP-B = Penerima titipan (Sukamaju)
 *
 *  1. KTK  - KDMP-A kirim 50 kg Beras ke KDMP-B
 *            Jurnal di KDMP-A: Persediaan Konsinyasi 1126 (D) / Persediaan Sembako 1123 (K)
 *
 *  2. KJL  - KDMP-B jual 30 kg ke warga @ Rp 13.500
 *            Jurnal di KDMP-B: Kas 1111 (D) / Hutang Konsinyasi 2117 (K) + Imbalan 417 (K)
 *
 *  3. KAP  - KDMP-A akui penjualan titipan
 *            Jurnal di KDMP-A: Piutang Konsinyasi 1135 (D) / Pendapatan Konsinyasi 416 (K)
 *                              HPP Konsinyasi 514 (D) / Persediaan Konsinyasi 1126 (K)
 *
 *  4. KST  - KDMP-B setor hasil penjualan ke KDMP-A
 *            Jurnal di KDMP-B: Hutang Konsinyasi 2117 (D) / Kas 1111 (K)
 *
 *  5. KTR  - KDMP-A terima setoran dari KDMP-B
 *            Jurnal di KDMP-A: Kas 1111 (D) / Piutang Konsinyasi 1135 (K)
 *
 *  6. KRT  - KDMP-B retur sisa 20 kg ke KDMP-A
 *            Jurnal di KDMP-A: Persediaan Sembako 1123 (D) / Persediaan Konsinyasi 1126 (K)
 *
 * CARA JALANKAN:
 *   php artisan db:seed --class=AkuntansiKonsinyasiSeeder
 */
class AkuntansiKonsinyasiSeeder extends Seeder
{
    // Data KDMP-A (Pemilik)
    private int $idEntitasA;
    private int $idUserA;
    private int $idKasA;
    private int $idGudangA;
    private int $idPihakBdiA; // entitas_lain KDMP-B di buku KDMP-A

    // Data KDMP-B (Penerima)
    private int $idEntitasB;
    private int $idUserB;
    private int $idKasB;
    private int $idGudangB;
    private int $idPihakAdiB; // entitas_lain KDMP-A di buku KDMP-B

    // Shared
    private int $idBarang;
    private int $idKiriman;
    private int $idStokKonsinyasi;

    public function run(): void
    {
        // === Resolusi data ===
        $entitasA = DB::table('entitas')->where('kode_entitas', 'KDMP-A')->first();
        $entitasB = DB::table('entitas')->where('kode_entitas', 'KDMP-B')->first();

        if (! $entitasA || ! $entitasB) {
            $this->command->error('Entitas KDMP-A atau KDMP-B tidak ditemukan.');
            return;
        }

        $this->idEntitasA = $entitasA->id_entitas;
        $this->idEntitasB = $entitasB->id_entitas;

        $this->idUserA = DB::table('pengguna')->where('id_entitas', $this->idEntitasA)->value('id') ?? 1;
        $this->idUserB = DB::table('pengguna')->where('id_entitas', $this->idEntitasB)->value('id') ?? 1;

        $this->idKasA = DB::table('master_kas_bank')
            ->where('id_entitas', $this->idEntitasA)->where('jenis', 'kas')->value('id_kas_bank');
        $this->idKasB = DB::table('master_kas_bank')
            ->where('id_entitas', $this->idEntitasB)->where('jenis', 'kas')->value('id_kas_bank');

        $this->idGudangA = DB::table('gudang')->where('id_entitas', $this->idEntitasA)->value('id_gudang');
        $this->idGudangB = DB::table('gudang')->where('id_entitas', $this->idEntitasB)->value('id_gudang');

        // Pihak "entitas_lain" ? dari DataAwalSeeder
        $this->idPihakBdiA = DB::table('master_pihak')
            ->where('id_entitas', $this->idEntitasA)
            ->where('id_entitas_mitra', $this->idEntitasB)
            ->value('id_pihak');

        $this->idPihakAdiB = DB::table('master_pihak')
            ->where('id_entitas', $this->idEntitasB)
            ->where('id_entitas_mitra', $this->idEntitasA)
            ->value('id_pihak');

        // Barang: BRG-BERAS-DEMO (dari KonsinyasiDemoSeeder) atau fallback
        $this->idBarang = DB::table('master_barang')->where('kode_barang', 'BRG-BERAS-DEMO')->value('id_barang')
            ?? DB::table('master_barang')->value('id_barang');

        if (! $this->idBarang) {
            $this->command->error('Barang tidak ditemukan. Jalankan KonsinyasiDemoSeeder dulu.');
            return;
        }

        // Pastikan KDMP-A punya stok cukup untuk dikirim
        $saldoA = DB::table('kartu_stok')
            ->where('id_entitas', $this->idEntitasA)
            ->where('id_gudang', $this->idGudangA)
            ->where('id_barang', $this->idBarang)
            ->orderByDesc('id_kartu')
            ->value('saldo_qty') ?? 0;

        $this->command->info("KDMP-A saldo stok beras: {$saldoA} kg");

        if ($saldoA < 50) {
            $this->command->warn("Stok KDMP-A kurang dari 50 kg. Tambahkan stok awal dulu.");
            $this->command->warn("Jalankan: php artisan db:seed --class=AkuntansiDemoSeeder");
            return;
        }

        $this->command->info("Membuat data dummy konsinyasi KDMP-A -> KDMP-B...");

        // === Cleanup data lama ===
        $this->command->line('  [0] Membersihkan data konsinyasi demo lama...');
        $this->bersihkanDataKonsinyasi();

        // === Skenario 1: KTK - Kirim Titipan ===
        $this->command->line('  [1] KTK - KDMP-A kirim 50 kg ke KDMP-B...');
        $this->kirimTitipan();

        // === Skenario 2: KJL - Jual di KDMP-B ===
        $this->command->line('  [2] KJL - KDMP-B jual 30 kg ke warga @ Rp 13.500...');
        $this->jualTitipan();

        // === Skenario 3: KAP - Pemilik akui penjualan ===
        $this->command->line('  [3] KAP - KDMP-A akui penjualan titipan...');
        $this->akuiPenjualan();

        // === Skenario 4: KST - KDMP-B setor hasil ===
        $this->command->line('  [4] KST - KDMP-B setor hasil ke KDMP-A...');
        $this->setorHasil();

        // === Skenario 5: KTR - KDMP-A terima setoran ===
        $this->command->line('  [5] KTR - KDMP-A terima setoran dari KDMP-B...');
        $this->terimaSetoran();

        // === Skenario 6: KRT - Retur sisa ke KDMP-A ===
        $this->command->line('  [6] KRT - KDMP-B retur 20 kg sisa ke KDMP-A...');
        $this->returTitipan();

        // === Ringkasan ===
        $jA = DB::table('jurnal_header')->where('id_entitas', $this->idEntitasA)->count();
        $jB = DB::table('jurnal_header')->where('id_entitas', $this->idEntitasB)->count();

        $this->command->newLine();
        $this->command->info("Selesai! Jurnal KDMP-A: {$jA} | Jurnal KDMP-B: {$jB}");
        $this->command->table(
            ['Pihak', 'Kode', 'Skenario', 'No. Jurnal', 'Nilai (Rp)'],
            [
                ['KDMP-A (Pemilik)',  'KTK', 'Kirim 50 kg Beras',         'JRN-2026-02-K001', '400.000 (HPP)'],
                ['KDMP-B (Penerima)', 'KJL', 'Jual 30 kg @ Rp 13.500',   'JRN-2026-02-K002', '405.000'],
                ['KDMP-A (Pemilik)',  'KAP', 'Akui penjualan titipan',    'JRN-2026-02-K003', 'Hak: 360.000'],
                ['KDMP-B (Penerima)', 'KST', 'Setor hasil ke KDMP-A',     'JRN-2026-02-K004', '360.000'],
                ['KDMP-A (Pemilik)',  'KTR', 'Terima setoran dari KDMP-B','JRN-2026-02-K005', '360.000'],
                ['KDMP-A (Pemilik)',  'KRT', 'Retur 20 kg sisa titipan',  'JRN-2026-02-K006', '160.000 (HPP)'],
            ]
        );
        $this->command->newLine();
        $this->command->line('Catatan model imbalan: SELISIH HARGA (harga_titip=12.000, jual=13.500, imbalan=1.500/kg)');
    }

    // ================================================================
    // SKENARIO 1: KTK - KDMP-A kirim 50 kg ke KDMP-B
    // ================================================================
    private function kirimTitipan(): void
    {
        $qtyKirim    = 50;
        $hppPemilik  = 8000;   // dari KonsinyasiDemoSeeder (saldo awal)
        $hargaTitip  = 12000;  // hak pemilik per kg
        $hargaJualSaran = 13500;
        $totalHpp    = $qtyKirim * $hppPemilik;   // 400.000
        $totalNilaiTitip = $qtyKirim * $hargaTitip; // 600.000

        // Buat dokumen pengiriman
        $this->idKiriman = DB::table('pengiriman_konsinyasi')->insertGetId([
            'kode_kiriman'         => 'KNS-2026-02-0001',
            'id_entitas_pemilik'   => $this->idEntitasA,
            'id_entitas_penerima'  => $this->idEntitasB,
            'id_gudang_asal'       => $this->idGudangA,
            'id_gudang_tujuan'     => $this->idGudangB,
            'tgl_kirim'            => '2026-02-20',
            'tgl_batas_titip'      => '2026-03-31',
            'model_imbalan'        => 'selisih_harga',
            'persen_komisi'        => 0,
            'penanggung_susut'     => 'pemilik',
            'total_nilai_titip'    => $totalNilaiTitip,
            'total_hpp_pemilik'    => $totalHpp,
            'status'               => 'berjalan',
            'status_posting'       => 'F',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('pengiriman_konsinyasi_detail')->insert([
            'id_kiriman'          => $this->idKiriman,
            'id_barang'           => $this->idBarang,
            'qty_dasar'           => $qtyKirim,
            'harga_titip_satuan'  => $hargaTitip,
            'harga_jual_saran'    => $hargaJualSaran,
            'hpp_pemilik'         => $hppPemilik,
            'total_nilai_titip'   => $totalNilaiTitip,
            'total_hpp'           => $totalHpp,
        ]);

        // Buat stok_konsinyasi di KDMP-B
        $this->idStokKonsinyasi = DB::table('stok_konsinyasi')->insertGetId([
            'id_kiriman'          => $this->idKiriman,
            'id_entitas_pemilik'  => $this->idEntitasA,
            'id_entitas_penerima' => $this->idEntitasB,
            'id_gudang_penerima'  => $this->idGudangB,
            'id_barang'           => $this->idBarang,
            'qty_titip'           => $qtyKirim,
            'qty_terjual'         => 0,
            'qty_retur'           => 0,
            'qty_susut'           => 0,
            'qty_sisa'            => $qtyKirim,
            'harga_titip_satuan'  => $hargaTitip,
            'harga_jual_satuan'   => $hargaJualSaran,
            'hpp_pemilik'         => $hppPemilik,
            'status'              => 'aktif',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Kartu konsinyasi: mutasi TITIP
        DB::table('kartu_konsinyasi')->insert([
            'id_stok_konsinyasi'  => $this->idStokKonsinyasi,
            'tanggal'             => '2026-02-20',
            'jenis_mutasi'        => 'TITIP',
            'ref_tipe'            => 'PENGIRIMAN',
            'ref_id'              => $this->idKiriman,
            'qty'                 => $qtyKirim,
            'harga_titip_satuan'  => $hargaTitip,
            'harga_jual_satuan'   => $hargaJualSaran,
            'saldo_qty'           => $qtyKirim,
            'created_at'          => now(),
        ]);

        // Kurangi stok KDMP-A
        $this->kurangiStokLedger($this->idEntitasA, $this->idGudangA, $this->idBarang, $qtyKirim, $hppPemilik, 'KIRIM_KONSINYASI', $this->idKiriman);

        // Jurnal KTK di KDMP-A: 1126 (D) / 1123 (K)
        // Model selisih_harga: reklasifikasi HPP pemilik (bukan nilai jual)
        $jKtkId = $this->buatJurnal(
            entitasId:    $this->idEntitasA,
            userId:       $this->idUserA,
            noJurnal:     'JRN-2026-02-K001',
            tanggal:      '2026-02-20',
            tahun:        2026, bulan: 2,
            kodeTransaksi:'KTK',
            jenisJurnal:  'OTOMATIS',
            keterangan:   'Kirim 50 kg Beras titipan ke KDMP-B (Sukamaju)',
            sourceType:   'Kons.PengirimanKonsinyasi',
            sourceId:     $this->idKiriman,
            baris: [
                ['kode_anak' => '1126', 'debet' => $totalHpp, 'kredit' => 0,         'id_pihak' => $this->idPihakBdiA],
                ['kode_anak' => '1123', 'debet' => 0,         'kredit' => $totalHpp, 'id_pihak' => $this->idPihakBdiA],
            ]
        );

        DB::table('pengiriman_konsinyasi')->where('id_kiriman', $this->idKiriman)
            ->update(['status_posting' => 'T', 'id_jurnal_kirim' => $jKtkId]);
    }

    // ================================================================
    // SKENARIO 2: KJL - KDMP-B jual 30 kg ke warga @ Rp 13.500
    //   Model selisih_harga: imbalan penerima = (13.500 - 12.000) * 30 = 45.000
    //   Hak pemilik = 12.000 * 30 = 360.000
    // ================================================================
    private function jualTitipan(): void
    {
        $qtyJual       = 30;
        $hargaJual     = 13500;
        $hargaTitip    = 12000;
        $nilaiImbalan  = ($hargaJual - $hargaTitip) * $qtyJual;  // 45.000
        $nilaiHakPemilik = $hargaTitip * $qtyJual;                // 360.000
        $totalBayar    = $hargaJual * $qtyJual;                   // 405.000

        // Update stok_konsinyasi di KDMP-B
        DB::table('stok_konsinyasi')->where('id_stok_konsinyasi', $this->idStokKonsinyasi)->update([
            'qty_terjual' => $qtyJual,
            'qty_sisa'    => 50 - $qtyJual,  // 20
            'updated_at'  => now(),
        ]);

        // Kartu konsinyasi: mutasi JUAL
        $idKartuJual = DB::table('kartu_konsinyasi')->insertGetId([
            'id_stok_konsinyasi'  => $this->idStokKonsinyasi,
            'tanggal'             => '2026-02-25',
            'jenis_mutasi'        => 'JUAL',
            'ref_tipe'            => 'PENJUALAN_KONSINYASI',
            'ref_id'              => $this->idStokKonsinyasi,
            'qty'                 => $qtyJual,
            'harga_titip_satuan'  => $hargaTitip,
            'harga_jual_satuan'   => $hargaJual,
            'saldo_qty'           => 20,
            'created_at'          => now(),
        ]);

        // Jurnal KJL di KDMP-B: Kas (D) / 2117 Hutang Konsinyasi (K) / 417 Imbalan (K)
        $jKjlId = $this->buatJurnal(
            entitasId:    $this->idEntitasB,
            userId:       $this->idUserB,
            noJurnal:     'JRN-2026-02-K002',
            tanggal:      '2026-02-25',
            tahun:        2026, bulan: 2,
            kodeTransaksi:'KJL',
            jenisJurnal:  'OTOMATIS',
            keterangan:   'Jual 30 kg Beras titipan KDMP-A ke warga @ Rp 13.500',
            sourceType:   'App\Models\Konsinyasi\KartuKonsinyasi',
            sourceId:     $idKartuJual,
            baris: [
                ['kode_anak' => '1111', 'debet' => $totalBayar,    'kredit' => 0,              'id_pihak' => null],
                ['kode_anak' => '2117', 'debet' => 0,              'kredit' => $nilaiHakPemilik,'id_pihak' => $this->idPihakAdiB],
                ['kode_anak' => '417',  'debet' => 0,              'kredit' => $nilaiImbalan,   'id_pihak' => null],
            ]
        );

        DB::table('kartu_konsinyasi')->where('id_kartu_kons', $idKartuJual)
            ->update(['id_jurnal_penerima' => $jKjlId]);

        // Catat hutang konsinyasi di buku KDMP-B (Buku Pembantu Hutang)
        DB::table('hutang')->insert([
            'id_entitas'      => $this->idEntitasB,
            'id_pihak'        => $this->idPihakAdiB,
            'sumber_tipe'     => 'KONSINYASI',
            'sumber_id'       => $this->idKiriman,
            'kode_akun'       => '2117',
            'tanggal'         => '2026-02-25',
            'tgl_jatuh_tempo' => '2026-03-10',
            'nilai_awal'      => $nilaiHakPemilik,
            'nilai_terbayar'  => 0,
            'status'          => 'belum_lunas',
            'created_at'      => now(),
        ]);
    }

    // ================================================================
    // SKENARIO 3: KAP - KDMP-A akui penjualan titipan
    //   Piutang Konsinyasi (D) / Pendapatan Konsinyasi 416 (K)
    //   HPP Konsinyasi 514 (D) / Persediaan Konsinyasi 1126 (K)
    // ================================================================
    private function akuiPenjualan(): void
    {
        $qtyJual       = 30;
        $hargaTitip    = 12000;
        $hppPemilik    = 8000;
        $nilaiHakPemilik = $hargaTitip * $qtyJual;  // 360.000
        $totalHpp      = $hppPemilik * $qtyJual;     // 240.000

        $jKapId = $this->buatJurnal(
            entitasId:    $this->idEntitasA,
            userId:       $this->idUserA,
            noJurnal:     'JRN-2026-02-K003',
            tanggal:      '2026-02-25',
            tahun:        2026, bulan: 2,
            kodeTransaksi:'KAP',
            jenisJurnal:  'OTOMATIS',
            keterangan:   'Akui penjualan 30 kg titipan di KDMP-B ? Hak: Rp 360.000',
            sourceType:   'App\Models\Konsinyasi\StokKonsinyasi',
            sourceId:     $this->idStokKonsinyasi,
            baris: [
                // Piutang ke KDMP-B
                ['kode_anak' => '1135', 'debet' => $nilaiHakPemilik, 'kredit' => 0,               'id_pihak' => $this->idPihakBdiA],
                ['kode_anak' => '416',  'debet' => 0,                'kredit' => $nilaiHakPemilik, 'id_pihak' => $this->idPihakBdiA],
                // HPP konsinyasi
                ['kode_anak' => '514',  'debet' => $totalHpp, 'kredit' => 0],
                ['kode_anak' => '1126', 'debet' => 0,         'kredit' => $totalHpp],
            ]
        );

        // Catat piutang konsinyasi di buku KDMP-A
        DB::table('piutang')->insert([
            'id_entitas'      => $this->idEntitasA,
            'id_pihak'        => $this->idPihakBdiA,
            'sumber_tipe'     => 'KONSINYASI',
            'sumber_id'       => $this->idKiriman,
            'kode_akun'       => '1135',
            'tanggal'         => '2026-02-25',
            'tgl_jatuh_tempo' => '2026-03-10',
            'nilai_awal'      => $nilaiHakPemilik,
            'nilai_terbayar'  => 0,
            'status'          => 'belum_lunas',
            'created_at'      => now(),
        ]);
    }

    // ================================================================
    // SKENARIO 4: KST - KDMP-B setor hasil Rp 360.000 ke KDMP-A
    //   Hutang Konsinyasi 2117 (D) / Kas 1111 (K)
    // ================================================================
    private function setorHasil(): void
    {
        $nilaiSetor = 30 * 12000; // 360.000

        $idKasBankPenerima = DB::table('master_kas_bank')
            ->where('id_entitas', $this->idEntitasA)->where('jenis', 'kas')->value('id_kas_bank');

        $idSetoran = DB::table('setoran_konsinyasi')->insertGetId([
            'kode_setoran'           => 'SET-2026-02-0001',
            'id_entitas_penyetor'    => $this->idEntitasB,
            'id_entitas_penerima_dana' => $this->idEntitasA,
            'tanggal'                => '2026-02-28',
            'total_nilai'            => $nilaiSetor,
            'id_kas_bank_penyetor'   => $this->idKasB,
            'id_kas_bank_penerima'   => $idKasBankPenerima,
            'status_posting'         => 'F',
            'catatan'                => 'Setor hasil penjualan 30 kg beras titipan KDMP-A',
            'created_at'             => now(),
        ]);

        // Jurnal KST di KDMP-B: 2117 (D) / Kas (K)
        $jKstId = $this->buatJurnal(
            entitasId:    $this->idEntitasB,
            userId:       $this->idUserB,
            noJurnal:     'JRN-2026-02-K004',
            tanggal:      '2026-02-28',
            tahun:        2026, bulan: 2,
            kodeTransaksi:'KST',
            jenisJurnal:  'OTOMATIS',
            keterangan:   'Setor hasil penjualan titipan KDMP-A ? Rp 360.000',
            sourceType:   'App\Models\Konsinyasi\SetoranKonsinyasi',
            sourceId:     $idSetoran,
            baris: [
                ['kode_anak' => '2117', 'debet' => $nilaiSetor, 'kredit' => 0,           'id_pihak' => $this->idPihakAdiB],
                ['kode_anak' => '1111', 'debet' => 0,           'kredit' => $nilaiSetor, 'id_pihak' => $this->idPihakAdiB],
            ]
        );

        DB::table('setoran_konsinyasi')->where('id_setoran', $idSetoran)
            ->update(['status_posting' => 'T', 'id_jurnal_penyetor' => $jKstId]);

        // Lunasi hutang di buku pembantu KDMP-B
        DB::table('hutang')
            ->where('id_entitas', $this->idEntitasB)
            ->where('sumber_tipe', 'KONSINYASI')
            ->where('sumber_id', $this->idKiriman)
            ->update(['nilai_terbayar' => $nilaiSetor, 'status' => 'lunas']);
    }

    // ================================================================
    // SKENARIO 5: KTR - KDMP-A terima setoran Rp 360.000
    //   Kas (D) / Piutang Konsinyasi 1135 (K)
    // ================================================================
    private function terimaSetoran(): void
    {
        $nilaiSetor = 30 * 12000; // 360.000

        $idSetoran = DB::table('setoran_konsinyasi')
            ->where('kode_setoran', 'SET-2026-02-0001')->value('id_setoran');

        $jKtrId = $this->buatJurnal(
            entitasId:    $this->idEntitasA,
            userId:       $this->idUserA,
            noJurnal:     'JRN-2026-02-K005',
            tanggal:      '2026-02-28',
            tahun:        2026, bulan: 2,
            kodeTransaksi:'KTR',
            jenisJurnal:  'OTOMATIS',
            keterangan:   'Terima setoran titipan dari KDMP-B ? Rp 360.000',
            sourceType:   'App\Models\Konsinyasi\SetoranKonsinyasi',
            sourceId:     $idSetoran,
            baris: [
                ['kode_anak' => '1111', 'debet' => $nilaiSetor, 'kredit' => 0,           'id_pihak' => $this->idPihakBdiA],
                ['kode_anak' => '1135', 'debet' => 0,           'kredit' => $nilaiSetor, 'id_pihak' => $this->idPihakBdiA],
            ]
        );

        DB::table('setoran_konsinyasi')->where('id_setoran', $idSetoran)
            ->update(['id_jurnal_penerima' => $jKtrId]);

        // Lunasi piutang
        DB::table('piutang')
            ->where('id_entitas', $this->idEntitasA)
            ->where('sumber_tipe', 'KONSINYASI')
            ->where('sumber_id', $this->idKiriman)
            ->update(['nilai_terbayar' => $nilaiSetor, 'status' => 'lunas']);
    }

    // ================================================================
    // SKENARIO 6: KRT - KDMP-B retur 20 kg sisa ke KDMP-A
    //   Di buku KDMP-A: Persediaan Sembako 1123 (D) / Persediaan Konsinyasi 1126 (K)
    // ================================================================
    private function returTitipan(): void
    {
        $qtyRetur   = 20;
        $hppPemilik = 8000;
        $totalHpp   = $qtyRetur * $hppPemilik; // 160.000

        // Update stok_konsinyasi
        DB::table('stok_konsinyasi')->where('id_stok_konsinyasi', $this->idStokKonsinyasi)->update([
            'qty_retur' => $qtyRetur,
            'qty_sisa'  => 0,
            'status'    => 'dikembalikan',
            'updated_at'=> now(),
        ]);

        // Kartu konsinyasi: mutasi RETUR
        DB::table('kartu_konsinyasi')->insert([
            'id_stok_konsinyasi' => $this->idStokKonsinyasi,
            'tanggal'            => '2026-02-28',
            'jenis_mutasi'       => 'RETUR',
            'ref_tipe'           => 'RETUR_KONSINYASI',
            'ref_id'             => $this->idKiriman,
            'qty'                => $qtyRetur,
            'harga_titip_satuan' => 12000,
            'harga_jual_satuan'  => 0,
            'saldo_qty'          => 0,
            'created_at'         => now(),
        ]);

        // Pulihkan stok KDMP-A (barang retur masuk lagi)
        $this->masukStokLedger($this->idEntitasA, $this->idGudangA, $this->idBarang, $qtyRetur, $hppPemilik, 'RETUR_KONSINYASI', $this->idKiriman);

        // Jurnal KRT di KDMP-A: 1123 (D) / 1126 (K)
        $jKrtId = $this->buatJurnal(
            entitasId:    $this->idEntitasA,
            userId:       $this->idUserA,
            noJurnal:     'JRN-2026-02-K006',
            tanggal:      '2026-02-28',
            tahun:        2026, bulan: 2,
            kodeTransaksi:'KRT',
            jenisJurnal:  'OTOMATIS',
            keterangan:   'Retur 20 kg sisa titipan dari KDMP-B',
            sourceType:   'Kons.ReturKonsinyasi',
            sourceId:     $this->idKiriman,
            baris: [
                ['kode_anak' => '1123', 'debet' => $totalHpp, 'kredit' => 0,        'id_pihak' => $this->idPihakBdiA],
                ['kode_anak' => '1126', 'debet' => 0,         'kredit' => $totalHpp,'id_pihak' => $this->idPihakBdiA],
            ]
        );

        // Selesaikan pengiriman
        DB::table('pengiriman_konsinyasi')->where('id_kiriman', $this->idKiriman)
            ->update(['status' => 'selesai']);
    }

    // ================================================================
    // CLEANUP
    // ================================================================
    private function bersihkanDataKonsinyasi(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_jurnal_detail_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_jurnal_detail_no_update');

        // Jurnal konsinyasi (no_jurnal mengandung '-K0')
        $jurnalKons = DB::table('jurnal_header')
            ->whereIn('id_entitas', [$this->idEntitasA, $this->idEntitasB])
            ->where('no_jurnal', 'like', '%-K0%')
            ->pluck('id_jurnal');
        if ($jurnalKons->isNotEmpty()) {
            DB::table('jurnal_detail')->whereIn('id_jurnal', $jurnalKons)->delete();
        }
        DB::table('buku_besar_periode')
            ->whereIn('id_entitas', [$this->idEntitasA, $this->idEntitasB])
            ->delete();
        DB::table('jurnal_header')
            ->whereIn('id_entitas', [$this->idEntitasA, $this->idEntitasB])
            ->where('no_jurnal', 'like', '%-K0%')->delete();

        // Konsinyasi data
        $kirimanIds = DB::table('pengiriman_konsinyasi')
            ->where('id_entitas_pemilik', $this->idEntitasA)->pluck('id_kiriman');
        if ($kirimanIds->isNotEmpty()) {
            $stokIds = DB::table('stok_konsinyasi')->whereIn('id_kiriman', $kirimanIds)->pluck('id_stok_konsinyasi');
            if ($stokIds->isNotEmpty()) {
                DB::table('kartu_konsinyasi')->whereIn('id_stok_konsinyasi', $stokIds)->delete();
            }
            DB::table('stok_konsinyasi')->whereIn('id_kiriman', $kirimanIds)->delete();
            DB::table('pengiriman_konsinyasi_detail')->whereIn('id_kiriman', $kirimanIds)->delete();
            DB::table('setoran_konsinyasi')->where('id_entitas_penyetor', $this->idEntitasB)->delete();
        }
        DB::table('pengiriman_konsinyasi')->where('id_entitas_pemilik', $this->idEntitasA)->delete();
        DB::table('piutang')->where('id_entitas', $this->idEntitasA)->where('sumber_tipe', 'KONSINYASI')->delete();
        DB::table('hutang')->where('id_entitas', $this->idEntitasB)->where('sumber_tipe', 'KONSINYASI')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Recreate trigger
        DB::unprepared("
        CREATE TRIGGER trg_jurnal_detail_no_update
        BEFORE UPDATE ON jurnal_detail FOR EACH ROW
        BEGIN
            DECLARE v_status VARCHAR(10);
            SELECT status INTO v_status FROM jurnal_header WHERE id_jurnal = OLD.id_jurnal;
            IF v_status <> 'DRAFT' THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail jurnal POSTED tidak boleh diubah. Gunakan jurnal pembalik.';
            END IF;
        END");

        DB::unprepared("
        CREATE TRIGGER trg_jurnal_detail_no_delete
        BEFORE DELETE ON jurnal_detail FOR EACH ROW
        BEGIN
            DECLARE v_status VARCHAR(10);
            SELECT status INTO v_status FROM jurnal_header WHERE id_jurnal = OLD.id_jurnal;
            IF v_status <> 'DRAFT' THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail jurnal POSTED tidak boleh dihapus.';
            END IF;
        END");
    }

    // ================================================================
    // HELPERS
    // ================================================================
    private function buatJurnal(
        int    $entitasId,
        int    $userId,
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
        $hasil = DB::select('CALL sp_post_jurnal(?, ?, ?)', [$entitasId, $userId, $payload]);
        return $hasil[0]->id_jurnal;
    }

    private function kurangiStokLedger(int $eId, int $gId, int $bId, float $qty, float $hpp, string $refTipe, int $refId): void
    {
        $last = DB::table('kartu_stok')
            ->where('id_entitas', $eId)->where('id_gudang', $gId)->where('id_barang', $bId)
            ->orderByDesc('id_kartu')->first();

        $saldoQty   = $last ? (float) $last->saldo_qty   : 0;
        $saldoNilai = $last ? (float) $last->saldo_nilai  : 0;
        $nilaiKeluar = $qty * $hpp;

        DB::table('kartu_stok')->insert([
            'id_entitas' => $eId, 'id_gudang' => $gId, 'id_barang' => $bId,
            'tanggal' => now()->toDateString(), 'jenis_mutasi' => 'OUT',
            'ref_tipe' => $refTipe, 'ref_id' => $refId,
            'qty_masuk' => 0, 'qty_keluar' => $qty,
            'harga_satuan' => $hpp, 'nilai_mutasi' => $nilaiKeluar,
            'saldo_qty'   => max(0, $saldoQty - $qty),
            'saldo_nilai' => max(0, $saldoNilai - $nilaiKeluar),
            'hpp_rata2_setelah' => $last ? (float) $last->hpp_rata2_setelah : $hpp,
            'created_by' => $this->idUserA, 'created_at' => now(),
        ]);
    }

    private function masukStokLedger(int $eId, int $gId, int $bId, float $qty, float $hpp, string $refTipe, int $refId): void
    {
        $last = DB::table('kartu_stok')
            ->where('id_entitas', $eId)->where('id_gudang', $gId)->where('id_barang', $bId)
            ->orderByDesc('id_kartu')->first();

        $saldoQty   = $last ? (float) $last->saldo_qty   : 0;
        $saldoNilai = $last ? (float) $last->saldo_nilai  : 0;
        $nilaiMasuk  = $qty * $hpp;
        $qtyBaru    = $saldoQty + $qty;
        $nilaiBaru  = $saldoNilai + $nilaiMasuk;
        $hppBaru    = $qtyBaru > 0 ? $nilaiBaru / $qtyBaru : $hpp;

        DB::table('kartu_stok')->insert([
            'id_entitas' => $eId, 'id_gudang' => $gId, 'id_barang' => $bId,
            'tanggal' => now()->toDateString(), 'jenis_mutasi' => 'IN',
            'ref_tipe' => $refTipe, 'ref_id' => $refId,
            'qty_masuk' => $qty, 'qty_keluar' => 0,
            'harga_satuan' => $hpp, 'nilai_mutasi' => $nilaiMasuk,
            'saldo_qty' => $qtyBaru, 'saldo_nilai' => $nilaiBaru,
            'hpp_rata2_setelah' => $hppBaru,
            'created_by' => $this->idUserA, 'created_at' => now(),
        ]);
    }
}
