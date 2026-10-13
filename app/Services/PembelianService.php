<?php

namespace App\Services;

use App\Models\Pembelian\Pembelian;
use App\Models\Pembelian\DetailPembelian;
use App\Models\Pembelian\ReturPembelian;
use App\Models\Pembelian\ReturPembelianDetail;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Master\Barang;
use Illuminate\Support\Facades\DB;

class PembelianService
{
    /**
     * Create Purchase Order (PO) from approved PR.
     *
     * @param PermintaanPengadaan $pr Approved PR
     * @param int $idPihak Supplier ID
     * @param string $jenisPembayaran 'tunai', 'transfer', or 'kredit'
     * @param string|null $tglJatuhTempo For kredit
     * @return Pembelian
     * @throws \Exception
     */
    public static function createFromPR(
        PermintaanPengadaan $pr,
        int $idPihak,
        string $jenisPembayaran,
        ?string $tglJatuhTempo = null,
    ): Pembelian {
        if ($pr->status !== 'disetujui') {
            throw new \Exception('PR harus berstatus "disetujui" sebelum dibuat PO.');
        }

        if (!$pr->id_unit_usaha || !$pr->id_gudang) {
            throw new \Exception('PR harus memiliki unit usaha dan gudang tujuan.');
        }

        if ($jenisPembayaran === 'kredit' && !$tglJatuhTempo) {
            throw new \Exception('Jatuh tempo harus diisi untuk pembayaran kredit.');
        }

        DB::beginTransaction();

        try {
            $kodePembelian = self::generatePurchaseCode(
                $pr->id_entitas,
                $pr->tgl_pengajuan->toDateString(),
            );

            $totalPembelian = 0;

            foreach ($pr->detail as $item) {
                $totalPembelian += $item->subtotal ?? 0;
            }

            $pembelian = Pembelian::create([
                'id_entitas' => $pr->id_entitas,
                'kode_pembelian' => $kodePembelian,
                'id_permintaan' => $pr->id_permintaan,
                'id_pihak' => $idPihak,
                'id_unit_usaha' => $pr->id_unit_usaha,
                'id_gudang' => $pr->id_gudang,
                'tanggal_transaksi' => now()->toDateString(),
                'jenis_pembayaran' => $jenisPembayaran,
                'id_kas_bank' => null,
                'tgl_jatuh_tempo' => $tglJatuhTempo,
                'total_pembelian' => $totalPembelian,
                'status' => 'draft',
                'status_posting' => 'F',
                'created_by' => auth()->id(),
            ]);

            foreach ($pr->detail as $prDetail) {
                $faktorKonversi = self::conversionFactor(
                    (int) $prDetail->id_barang,
                    (int) $prDetail->barang->id_satuan_dasar,
                );

                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $prDetail->id_barang,
                    'id_satuan_input' => $prDetail->barang->id_satuan_dasar,
                    'qty_input' => $prDetail->jumlah_diminta,
                    'faktor_konversi' => $faktorKonversi,
                    'qty_dasar' => $prDetail->jumlah_diminta * $faktorKonversi,
                    'harga_satuan_input' => $prDetail->harga_perkiraan ?? 0,
                    'subtotal' => $prDetail->subtotal ?? 0,
                ]);
            }

            DB::commit();

            return $pembelian;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create quick purchase.
     *
    * Payment terms are recorded locally without Finance posting.
     *
     * @param int $idEntitas
     * @param int $idPihak Farmer ID
     * @param int $idUnitUsaha
     * @param int $idGudang
     * @param array $items
    * @param string $jenisPembayaran 'tunai', 'transfer', or 'kredit'
    * @param string|null $tglJatuhTempo
     * @return Pembelian
     * @throws \Exception
     */
    public static function createQuickPurchase(
        int $idEntitas,
        int $idPihak,
        int $idUnitUsaha,
        int $idGudang,
        array $items,
        string $jenisPembayaran,
        ?string $tglJatuhTempo = null,
    ): Pembelian {
        if (!in_array($jenisPembayaran, ['tunai', 'transfer', 'kredit'], true)) {
            throw new \Exception('Jenis pembayaran pembelian tidak valid.');
        }

        if ($jenisPembayaran === 'kredit' && !$tglJatuhTempo) {
            throw new \Exception('Tanggal jatuh tempo wajib diisi untuk pembayaran kredit.');
        }

        if (empty($items)) {
            throw new \Exception('Minimal satu item harus ditambahkan.');
        }

        DB::beginTransaction();

        try {
            $kodePembelian = self::generatePurchaseCode(
                $idEntitas,
                now()->toDateString(),
            );

            $totalPembelian = 0;

            foreach ($items as $item) {
                $totalPembelian +=
                    ($item['qty_dasar'] ?? 0)
                    * ($item['harga_satuan'] ?? 0);
            }

            $pembelian = Pembelian::create([
                'id_entitas' => $idEntitas,
                'kode_pembelian' => $kodePembelian,
                'id_permintaan' => null,
                'id_pihak' => $idPihak,
                'id_unit_usaha' => $idUnitUsaha,
                'id_gudang' => $idGudang,
                'tanggal_transaksi' => now()->toDateString(),
                'jenis_pembayaran' => $jenisPembayaran,
                'id_kas_bank' => null,
                'tgl_jatuh_tempo' => $tglJatuhTempo,
                'total_pembelian' => $totalPembelian,
                'status' => 'draft',
                'status_posting' => 'F',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $faktorKonversi = self::conversionFactor(
                    (int) $item['id_barang'],
                    (int) $item['id_satuan'],
                );

                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $item['id_barang'],
                    'id_satuan_input' => $item['id_satuan'],
                    'qty_input' => $item['qty_dasar'],
                    'faktor_konversi' => $faktorKonversi,
                    'qty_dasar' => $item['qty_dasar'] * $faktorKonversi,
                    'harga_satuan_input' => $item['harga_satuan'],
                    'subtotal' =>
                        $item['qty_dasar'] * $item['harga_satuan'],
                ]);
            }

            DB::commit();

            return $pembelian;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** Approve a purchase locally without posting to other modules. */
    public static function approvePembelian(Pembelian $pembelian): Pembelian
    {
        if ($pembelian->status !== 'draft') {
            throw new \Exception('Hanya pembelian draft yang bisa disetujui.');
        }

        try {
            $pembelian->update([
                'status' => 'disetujui',
                'status_posting' => 'F',
            ]);
            return $pembelian->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function cancelPurchase(Pembelian $pembelian): Pembelian
    {
        if (!in_array($pembelian->status, ['draft', 'disetujui'], true)) {
            throw new \Exception('Hanya pembelian draft atau disetujui yang bisa dibatalkan.');
        }

        if ($pembelian->returs()->exists()) {
            throw new \Exception('Pembelian yang sudah memiliki catatan retur tidak bisa dibatalkan.');
        }

        DB::beginTransaction();

        try {
            $pembelian->update(['status' => 'dibatalkan']);

            DB::commit();

            return $pembelian->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private static function conversionFactor(int $idBarang, int $idSatuan): float
    {
        $factor = DB::table('konversi_satuan')
            ->where('id_barang', $idBarang)
            ->where('id_satuan', $idSatuan)
            ->value('faktor_ke_dasar');

        if ($factor === null || (float) $factor <= 0) {
            $idSatuanDasar = DB::table('master_barang')
                ->where('id_barang', $idBarang)
                ->value('id_satuan_dasar');

            if ((int) $idSatuanDasar === $idSatuan) {
                return 1.0;
            }

            throw new \Exception('Konversi satuan untuk barang dan satuan yang dipilih tidak ditemukan. Pilih satuan yang tersedia untuk barang tersebut.');
        }

        return (float) $factor;
    }

    /**
     * Create purchase return.
     *
     * @param Pembelian $pembelian
     * @param array $items
     * @param string $jenisPenyelesaian
     * @param string $alasan
     * @return ReturPembelian
     * @throws \Exception
     */
    public static function createRetur(
        Pembelian $pembelian,
        array $items,
        string $jenisPenyelesaian,
        string $alasan,
    ): ReturPembelian {
        if (!in_array($pembelian->status, [
            'disetujui',
            'diterima',
            'selesai',
        ], true)) {
            throw new \Exception(
                'Hanya pembelian yang sudah disetujui atau diterima bisa di-retur.'
            );
        }

        if (empty($items)) {
            throw new \Exception('Minimal satu item harus di-retur.');
        }

        if (!in_array($jenisPenyelesaian, [
            'uang',
            'potong_hutang',
            'ganti_barang',
        ], true)) {
            throw new \Exception('Jenis penyelesaian tidak valid.');
        }

        DB::beginTransaction();

        try {
            $pembelian = Pembelian::with(['detail', 'returs.detail'])
                ->whereKey($pembelian->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $returnedByBarang = $pembelian->returs
                ->flatMap(fn ($retur) => $retur->detail)
                ->groupBy('id_barang')
                ->map(fn ($details) => (float) $details->sum('qty_dasar'));
            $requestedByBarang = collect($items)
                ->groupBy('id_barang')
                ->map(fn ($rows) => (float) $rows->sum('qty_dasar'));

            foreach ($requestedByBarang as $idBarang => $qtyDiminta) {
                $qtyDibeli = (float) $pembelian->detail
                    ->where('id_barang', (int) $idBarang)
                    ->sum('qty_dasar');
                $qtySudahDiretur = (float) $returnedByBarang->get($idBarang, 0);

                if ($qtyDibeli <= 0) {
                    throw new \Exception("Barang {$idBarang} tidak ditemukan pada pembelian.");
                }

                if ($qtySudahDiretur + $qtyDiminta > $qtyDibeli + 0.0000001) {
                    throw new \Exception(
                        "Jumlah retur barang {$idBarang} melebihi sisa kuantitas pembelian."
                    );
                }
            }

            $kodeRetur = self::generateReturnCode(
                $pembelian->id_entitas,
                now()->toDateString(),
            );

            $totalNilai = 0;
            $itemsWithHpp = [];

            foreach ($items as $item) {
                $detailPembelian = $pembelian->detail
                    ->where('id_barang', (int) $item['id_barang']);

                if ($detailPembelian->isEmpty()) {
                    throw new \Exception(
                        "Barang {$item['id_barang']} tidak ditemukan pada pembelian."
                    );
                }

                $qtyRetur = (float) $item['qty_dasar'];
                $qtyDibeli = (float) $detailPembelian->sum('qty_dasar');
                $nilaiBeli = $detailPembelian->sum(function ($detail) {
                    $faktorKonversi = (float) $detail->faktor_konversi;
                    $hargaSatuanDasar = $faktorKonversi > 0
                        ? (float) $detail->harga_satuan_input / $faktorKonversi
                        : (float) $detail->harga_satuan_input;

                    return (float) $detail->qty_dasar * $hargaSatuanDasar;
                });
                $hargaBeliDasar = $qtyDibeli > 0 ? $nilaiBeli / $qtyDibeli : 0;

                $item['hpp_rata2'] = $hargaBeliDasar;
                $itemsWithHpp[] = $item;
                $totalNilai +=
                    $qtyRetur * $hargaBeliDasar;
            }

            $retur = ReturPembelian::create([
                'id_entitas' => $pembelian->id_entitas,
                'kode_retur' => $kodeRetur,
                'id_pembelian' => $pembelian->id_pembelian,
                'tgl_retur' => now()->toDateString(),
                'jenis_penyelesaian' => $jenisPenyelesaian,
                'total_nilai' => $totalNilai,
                'alasan' => $alasan,
                'status' => 'diajukan',
                'status_posting' => 'F',
            ]);

            foreach ($itemsWithHpp as $item) {
                ReturPembelianDetail::create([
                    'id_retur' => $retur->id_retur,
                    'id_barang' => $item['id_barang'],
                    'qty_dasar' => $item['qty_dasar'],
                    'hpp_rata2' => $item['hpp_rata2'],
                    'nilai' =>
                        $item['qty_dasar'] * $item['hpp_rata2'],
                ]);
            }

            DB::commit();

            return $retur;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** Mark a return complete within the purchase module only. */
    public static function approveRetur(ReturPembelian $retur): ReturPembelian
    {
        if ($retur->status !== 'diajukan') {
            throw new \Exception('Hanya retur yang diajukan yang dapat diproses.');
        }

        $retur->update([
            'status' => 'selesai',
            'status_posting' => 'F',
            'id_jurnal' => null,
        ]);

        return $retur->fresh();
    }

    private static function generatePurchaseCode(
        int $idEntitas,
        string $tanggal
    ): string {
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $last = Pembelian::where('id_entitas', $idEntitas)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('id_pembelian')
            ->first();

        $seq = 1;

        if (
            $last
            && preg_match(
                '/(\d+)$/',
                $last->kode_pembelian,
                $matches
            )
        ) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf(
            'PO/%04d%02d/%04d',
            $tahun,
            $bulan,
            $seq
        );
    }

    private static function generateReturnCode(
        int $idEntitas,
        string $tanggal
    ): string {
        $tahun = date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $last = ReturPembelian::where('id_entitas', $idEntitas)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('id_retur')
            ->first();

        $seq = 1;

        if (
            $last
            && preg_match(
                '/(\d+)$/',
                $last->kode_retur,
                $matches
            )
        ) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf(
            'RET/%04d%02d/%04d',
            $tahun,
            $bulan,
            $seq
        );
    }
}


