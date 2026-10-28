<?php

namespace Database\Seeders;

use App\Models\Akuntansi\MasterTransaksi;
use App\Models\Akuntansi\MasterDetailTransaksi;
use Illuminate\Database\Seeder;

class TransaksiPembelianSeeder extends Seeder
{
    public function run(): void
    {
        // JPO: Jurnal Pembelian dari PO
        // Persediaan Unit (D) / Kas/Bank atau Hutang Dagang (K)
        $this->createTransaksi('JPO', 'Jurnal Pembelian (PO)', 'Pembelian', [
            [
                'urutan' => 1,
                'kode_anak' => null,
                'akun_dinamis' => 'PERSEDIAAN_UNIT',
                'posisi' => 'D',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => false,
            ],
            [
                'urutan' => 2,
                'kode_anak' => null,
                'akun_dinamis' => 'KAS_BANK',
                'posisi' => 'K',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => true, // Jika transfer/tunai
            ],
            [
                'urutan' => 3,
                'kode_anak' => '2111', // Hutang Dagang
                'akun_dinamis' => null,
                'posisi' => 'K',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => true, // Jika kredit
            ],
        ]);

        // GRN: Goods Receipt Note (posting ketika barang diterima)
        // Sama dengan JPO, tapi trigger saat GRN
        $this->createTransaksi('GRN', 'Penerimaan Barang (GRN)', 'Pembelian', [
            [
                'urutan' => 1,
                'kode_anak' => null,
                'akun_dinamis' => 'PERSEDIAAN_UNIT',
                'posisi' => 'D',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => false,
            ],
            [
                'urutan' => 2,
                'kode_anak' => null,
                'akun_dinamis' => 'KAS_BANK',
                'posisi' => 'K',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => true,
            ],
            [
                'urutan' => 3,
                'kode_anak' => '2111',
                'akun_dinamis' => null,
                'posisi' => 'K',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => true,
            ],
        ]);

        // BPT: Nota Pembelian Petani (quick purchase)
        // Sama struktur dengan JPO/GRN
        $this->createTransaksi('BPT', 'Nota Pembelian Petani', 'Pembelian', [
            [
                'urutan' => 1,
                'kode_anak' => null,
                'akun_dinamis' => 'PERSEDIAAN_UNIT',
                'posisi' => 'D',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => false,
            ],
            [
                'urutan' => 2,
                'kode_anak' => null,
                'akun_dinamis' => 'KAS_BANK',
                'posisi' => 'K',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => false, // BPT selalu tunai
            ],
        ]);

        // RBU: Retur Balik Uang
        // Kas (D) / Persediaan (K)
        $this->createTransaksi('RBU', 'Retur Pembelian - Balik Uang', 'Pembelian', [
            [
                'urutan' => 1,
                'kode_anak' => null,
                'akun_dinamis' => 'KAS_BANK',
                'posisi' => 'D',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => false,
            ],
            [
                'urutan' => 2,
                'kode_anak' => null,
                'akun_dinamis' => 'PERSEDIAAN_UNIT',
                'posisi' => 'K',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => false,
            ],
        ]);

        // RBH: Retur Balik Hutang (potong hutang)
        // Hutang Dagang (D) / Persediaan (K)
        $this->createTransaksi('RBH', 'Retur Pembelian - Potong Hutang', 'Pembelian', [
            [
                'urutan' => 1,
                'kode_anak' => '2111',
                'akun_dinamis' => null,
                'posisi' => 'D',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => false,
            ],
            [
                'urutan' => 2,
                'kode_anak' => null,
                'akun_dinamis' => 'PERSEDIAAN_UNIT',
                'posisi' => 'K',
                'sumber_variabel' => 'total_nilai',
                'is_optional' => false,
            ],
        ]);

        // RGB: Retur Ganti Barang (no jurnal, hanya swap kartu stok)
        // Ini akan di-skip di JournalService karena is_optional semua
        // Tapi disediakan untuk audit trail
        $this->createTransaksi('RGB', 'Retur Pembelian - Ganti Barang', 'Pembelian', [
            // No entries - hanya untuk dokumentasi
        ]);
    }

    private function createTransaksi(
        string $kodeTransaksi,
        string $namaTransaksi,
        string $modul,
        array $details,
    ): void {
        MasterTransaksi::updateOrCreate(
            ['kode_transaksi' => $kodeTransaksi],
            [
                'nama_transaksi' => $namaTransaksi,
                'modul' => $modul,
                'is_active' => true,
            ],
        );

        // Hapus detail lama
        MasterDetailTransaksi::where('kode_transaksi', $kodeTransaksi)->delete();

        // Insert detail baru
        foreach ($details as $detail) {
            MasterDetailTransaksi::create([
                'kode_transaksi' => $kodeTransaksi,
                'urutan' => $detail['urutan'],
                'kode_anak' => $detail['kode_anak'],
                'akun_dinamis' => $detail['akun_dinamis'],
                'posisi' => $detail['posisi'],
                'sumber_variabel' => $detail['sumber_variabel'],
                'is_optional' => $detail['is_optional'] ?? false,
            ]);
        }
    }
}
