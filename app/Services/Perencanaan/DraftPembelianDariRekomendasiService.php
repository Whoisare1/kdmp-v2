<?php

namespace App\Services\Perencanaan;

use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\RekomendasiPengadaanDesa;
use Illuminate\Support\Facades\DB;

/**
 * Membuat draft pembelian di tabel `pembelian` + `detail_pembelian`
 * saat rekomendasi pengadaan antar desa disetujui.
 *
 * Draft ini berstatus "draft" — tidak mengubah stok, tidak memposting jurnal.
 * Proses lanjutan (approve, GRN, posting) tetap dilakukan manual oleh modul Pembelian.
 */
class DraftPembelianDariRekomendasiService
{
    /**
     * Buat draft pembelian untuk desa DEFISIT (pembeli) yang membeli dari desa SURPLUS (penjual).
     *
     * Desa defisit = pengaju rekomendasi
     * Desa surplus = yang menyetujui
     *
     * @return int id_pembelian yang baru dibuat
     */
    public function buat(RekomendasiPengadaanDesa $rekomendasi): int
    {
        $hasilDefisit = $rekomendasi->hasilDefisit()->with(['komoditas', 'wilayah'])->first();
        $hasilSurplus = $rekomendasi->hasilSurplus()->with(['wilayah'])->first();

        abort_unless($hasilDefisit && $hasilSurplus, 500, 'Data hasil kalkulasi rekomendasi tidak lengkap.');

        // Entitas pembeli = entitas desa defisit
        $idEntitasPembeli = (int) DB::table('entitas')
            ->where('id_wilayah', $hasilDefisit->id_wilayah)
            ->where('is_active', true)
            ->where('status', 'APPROVED')
            ->value('id_entitas');

        // Entitas penjual = entitas desa surplus
        $idEntitasPenjual = (int) DB::table('entitas')
            ->where('id_wilayah', $hasilSurplus->id_wilayah)
            ->where('is_active', true)
            ->where('status', 'APPROVED')
            ->value('id_entitas');

        abort_unless($idEntitasPembeli && $idEntitasPenjual, 500, 'Entitas pembeli atau penjual tidak ditemukan.');

        // Cari pihak "entitas_lain" yang merepresentasikan desa surplus di sisi desa defisit
        $pihak = DB::table('master_pihak')
            ->where('id_entitas', $idEntitasPembeli)
            ->where('jenis_pihak', 'entitas_lain')
            ->where('id_entitas_mitra', $idEntitasPenjual)
            ->first();

        // Jika belum ada, buat otomatis
        if (! $pihak) {
            $namaDesa = $hasilSurplus->wilayah?->nama ?? "Desa id:{$idEntitasPenjual}";
            $idPihak = DB::table('master_pihak')->insertGetId([
                'id_entitas'      => $idEntitasPembeli,
                'jenis_pihak'     => 'entitas_lain',
                'id_entitas_mitra'=> $idEntitasPenjual,
                'nama'            => $namaDesa,
                'is_anggota'      => 0,
                'is_active'       => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        } else {
            $idPihak = $pihak->id_pihak;
        }

        // Gudang utama desa pembeli
        $idGudang = (int) DB::table('gudang')
            ->where('id_entitas', $idEntitasPembeli)
            ->value('id_gudang');

        abort_unless($idGudang, 500, 'Gudang pembeli tidak ditemukan.');

        // Unit usaha (Sembako = id:1 default)
        $idUnitUsaha = 1;

        // Barang yang sesuai komoditas di sisi entitas pembeli
        $idKomoditas = (int) $hasilDefisit->id_komoditas;
        $barang = DB::table('master_barang')
            ->where('id_komoditas', $idKomoditas)
            ->where('id_entitas', $idEntitasPembeli)
            ->where('is_active', 1)
            ->first();

        // Qty = abs(selisih defisit)
        $qty = abs((float) $hasilDefisit->selisih);
        // Satuan KG dari entitas pembeli
        $idSatuan = (int) DB::table('satuan')
            ->where('id_entitas', $idEntitasPembeli)
            ->where('kode_satuan', 'KG')
            ->value('id');

        // Harga estimasi dari barang_per_koperasi (harga jual standar penjual sebagai acuan)
        $hargaEstimasi = 0;
        if ($barang) {
            $bpk = DB::table('barang_per_koperasi')
                ->where('id_entitas', $idEntitasPembeli)
                ->where('id_barang', $barang->id_barang)
                ->first();
            $hargaEstimasi = $bpk ? (float) $bpk->harga_jual_standar : 0;
        }
        $subtotal = $qty * $hargaEstimasi;

        // Generate kode pembelian: PBL-YYYYMM-XXXX
        $prefix = 'PBL-' . now()->format('Ym') . '-';
        $last = DB::table('pembelian')
            ->where('id_entitas', $idEntitasPembeli)
            ->where('kode_pembelian', 'like', $prefix . '%')
            ->orderByDesc('kode_pembelian')
            ->value('kode_pembelian');
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }
        $kodePembelian = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        return DB::transaction(function () use (
            $idEntitasPembeli, $kodePembelian, $idPihak, $idUnitUsaha, $idGudang,
            $barang, $idSatuan, $qty, $hargaEstimasi, $subtotal,
            $rekomendasi, $hasilDefisit
        ): int {
            $idPembelian = DB::table('pembelian')->insertGetId([
                'id_entitas'         => $idEntitasPembeli,
                'kode_pembelian'     => $kodePembelian,
                'id_permintaan'      => null,
                'sumber_pembelian'   => 'rekomendasi_desa',
                'id_pihak'           => $idPihak,
                'id_unit_usaha'      => $idUnitUsaha,
                'id_gudang'          => $idGudang,
                'tanggal_transaksi'  => now()->toDateString(),
                'jenis_pembayaran'   => 'transfer',
                'id_kas_bank'        => null,
                'tgl_jatuh_tempo'    => now()->addDays(14)->toDateString(),
                'total_pembelian'    => $subtotal,
                'diskon'             => 0,
                'status'             => 'draft',
                'status_posting'     => 'F',
                'id_jurnal'          => null,
                'created_by'         => auth()->id(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // Detail pembelian — hanya jika barang ditemukan
            if ($barang && $idSatuan) {
                DB::table('detail_pembelian')->insert([
                    'id_pembelian'       => $idPembelian,
                    'id_barang'          => $barang->id_barang,
                    'id_satuan_input'    => $idSatuan,
                    'qty_input'          => $qty,
                    'faktor_konversi'    => 1.0,
                    'qty_dasar'          => $qty,
                    'harga_satuan_input' => $hargaEstimasi,
                    'subtotal'           => $subtotal,
                ]);
            }

            // Simpan id_pembelian ke rekomendasi (tambah kolom nanti, cukup catatan dulu)
            // Kita update catatan_penyetuju agar traceable
            DB::table('rekomendasi_pengadaan_desa')
                ->where('id_rekomendasi', $rekomendasi->id_rekomendasi)
                ->update(['catatan_penyetuju' =>
                    'Draft pembelian dibuat: '.$kodePembelian.
                    ' | Komoditas: '.($hasilDefisit->komoditas?->nama ?? 'unknown').
                    ' | Qty: '.$qty.' KG']);

            return $idPembelian;
        });
    }
}
