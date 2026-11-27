<?php

namespace App\Services;

use App\Models\Gudang\KartuStok;
use App\Models\Gudang\Stok;
use App\Models\Master\Barang;
use App\Models\Pengguna;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StokService
{
    public function __construct(private StokBatchService $stokBatch) {}

    public function masuk(
        int $entitasId,
        int $gudangId,
        int $barangId,
        string $qty,
        string $hargaSatuan,
        string $refTipe,
        int $refId,
        ?int $createdBy = null,
        ?string $tanggal = null,
    ): KartuStok {
        $this->pastikanPositif($qty, 'qty');
        $this->pastikanTidakNegatif($hargaSatuan, 'harga satuan');

        return DB::transaction(function () use (
            $entitasId,
            $gudangId,
            $barangId,
            $qty,
            $hargaSatuan,
            $refTipe,
            $refId,
            $createdBy,
            $tanggal,
        ): KartuStok {
            $qtyTersediaSebelum = self::qtyTersediaEntitasBarang($entitasId, $barangId);
            $stok = Stok::query()
                ->where('id_gudang', $gudangId)
                ->where('id_barang', $barangId)
                ->lockForUpdate()
                ->first();

            if (! $stok) {
                $stok = new Stok([
                    'id_gudang' => $gudangId,
                    'id_barang' => $barangId,
                    'qty_on_hand' => '0',
                    'qty_reserved' => '0',
                    'hpp_rata2' => '0',
                    'nilai_persediaan' => '0',
                ]);
            }

            $nilaiMasuk = bcmul($qty, $hargaSatuan, 2);
            $qtyBaru = bcadd((string) $stok->qty_on_hand, $qty, 4);
            $nilaiBaru = bcadd((string) $stok->nilai_persediaan, $nilaiMasuk, 2);
            $hppBaru = bcdiv($nilaiBaru, $qtyBaru, 4);

            Stok::query()->updateOrInsert(
                [
                    'id_gudang' => $gudangId,
                    'id_barang' => $barangId,
                ],
                [
                    'qty_on_hand' => $qtyBaru,
                    'qty_reserved' => $stok->qty_reserved ?? '0',
                    'hpp_rata2' => $hppBaru,
                    'nilai_persediaan' => $nilaiBaru,
                    'updated_at' => now(),
                ],
            );
            self::notifikasiJikaMencapaiMinimum($entitasId, $barangId, $qtyTersediaSebelum);

            return KartuStok::create([
                'id_entitas' => $entitasId,
                'id_gudang' => $gudangId,
                'id_barang' => $barangId,
                'tanggal' => $tanggal ?? now()->toDateString(),
                'jenis_mutasi' => 'IN',
                'ref_tipe' => $refTipe,
                'ref_id' => $refId,
                'qty_masuk' => $qty,
                'qty_keluar' => '0',
                'harga_satuan' => $hargaSatuan,
                'nilai_mutasi' => $nilaiMasuk,
                'saldo_qty' => $qtyBaru,
                'saldo_nilai' => $nilaiBaru,
                'hpp_rata2_setelah' => $hppBaru,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function keluar(
        int $entitasId,
        int $gudangId,
        int $barangId,
        string $qty,
        string $refTipe,
        int $refId,
        ?int $createdBy = null,
        ?string $tanggal = null,
        ?int $idBatch = null,
        bool $izinkanBatchKedaluwarsa = false,
    ): KartuStok {
        $this->pastikanPositif($qty, 'qty');

        return DB::transaction(function () use (
            $entitasId,
            $gudangId,
            $barangId,
            $qty,
            $refTipe,
            $refId,
            $createdBy,
            $tanggal,
            $idBatch,
            $izinkanBatchKedaluwarsa,
        ): KartuStok {
            $qtyTersediaSebelum = self::qtyTersediaEntitasBarang($entitasId, $barangId);
            $stok = Stok::query()
                ->where('id_gudang', $gudangId)
                ->where('id_barang', $barangId)
                ->lockForUpdate()
                ->first();

            $qtyTersedia = $stok
                ? bcsub((string) $stok->qty_on_hand, (string) $stok->qty_reserved, 4)
                : '0';
            if (! $stok || bccomp($qtyTersedia, $qty, 4) < 0) {
                $tersedia = $stok && bccomp($qtyTersedia, '0', 4) > 0 ? $qtyTersedia : '0';
                throw new RuntimeException("Stok tidak mencukupi. Tersedia {$tersedia}, diminta {$qty}.");
            }

            $alokasiBatch = null;
            if ($this->stokBatch->pelacakanAktif($entitasId, $barangId)) {
                $alokasiBatch = $this->stokBatch->alokasiKeluar(
                    gudangId: $gudangId,
                    barangId: $barangId,
                    qty: $qty,
                    idBatch: $idBatch,
                    izinkanBatchKedaluwarsa: $izinkanBatchKedaluwarsa,
                );
            }

            $nilaiKeluar = bcmul($qty, (string) $stok->hpp_rata2, 2);
            $qtyBaru = bcsub((string) $stok->qty_on_hand, $qty, 4);
            $nilaiBaru = bcsub((string) $stok->nilai_persediaan, $nilaiKeluar, 2);
            $hppTetap = (string) $stok->hpp_rata2;

            Stok::query()
                ->where('id_gudang', $gudangId)
                ->where('id_barang', $barangId)
                ->update([
                    'qty_on_hand' => $qtyBaru,
                    'nilai_persediaan' => $nilaiBaru,
                    'updated_at' => now(),
                ]);
            self::notifikasiJikaMencapaiMinimum($entitasId, $barangId, $qtyTersediaSebelum);

            $kartuStok = KartuStok::create([
                'id_entitas' => $entitasId,
                'id_gudang' => $gudangId,
                'id_barang' => $barangId,
                'tanggal' => $tanggal ?? now()->toDateString(),
                'jenis_mutasi' => 'OUT',
                'ref_tipe' => $refTipe,
                'ref_id' => $refId,
                'qty_masuk' => '0',
                'qty_keluar' => $qty,
                'harga_satuan' => $hppTetap,
                'nilai_mutasi' => $nilaiKeluar,
                'saldo_qty' => $qtyBaru,
                'saldo_nilai' => $nilaiBaru,
                'hpp_rata2_setelah' => $hppTetap,
                'created_by' => $createdBy,
            ]);

            if ($alokasiBatch !== null) {
                $this->stokBatch->catatPengeluaran($kartuStok, $alokasiBatch);
            }

            return $kartuStok;
        });
    }

    public function hppSaatIni(int $gudangId, int $barangId): string
    {
        return (string) (DB::table('stok')
            ->where('id_gudang', $gudangId)
            ->where('id_barang', $barangId)
            ->value('hpp_rata2') ?? '0');
    }

    public function tersedia(int $gudangId, int $barangId): string
    {
        return (string) (DB::table('stok')
            ->where('id_gudang', $gudangId)
            ->where('id_barang', $barangId)
            ->value('qty_on_hand') ?? '0');
    }

    public function sesuaikanOpname(
        int $entitasId,
        int $gudangId,
        int $barangId,
        string $qtyFisik,
        int $opnameId,
        ?int $createdBy = null,
    ): array {
        return DB::transaction(function () use (
            $entitasId,
            $gudangId,
            $barangId,
            $qtyFisik,
            $opnameId,
            $createdBy,
        ): array {
            $qtyTersediaSebelum = self::qtyTersediaEntitasBarang($entitasId, $barangId);
            $stok = Stok::query()
                ->where('id_gudang', $gudangId)
                ->where('id_barang', $barangId)
                ->lockForUpdate()
                ->first();

            $qtySistem = (string) ($stok?->qty_on_hand ?? '0');
            $hpp = (string) ($stok?->hpp_rata2 ?? '0');
            $qtyReserved = (string) ($stok?->qty_reserved ?? '0');
            if (bccomp($qtyFisik, $qtyReserved, 4) < 0) {
                throw new RuntimeException(
                    "Jumlah fisik {$qtyFisik} tidak boleh lebih kecil dari stok yang sudah dipesan {$qtyReserved}. Lepaskan pesanan terkait sebelum menyetujui opname."
                );
            }
            $selisih = bcsub($qtyFisik, $qtySistem, 4);
            $nilaiSelisih = bcmul($selisih, $hpp, 2);

            if (bccomp($selisih, '0', 4) === 0) {
                return [
                    'qty_sistem' => $qtySistem,
                    'selisih' => $selisih,
                    'hpp_rata2' => $hpp,
                    'nilai_selisih' => $nilaiSelisih,
                    'id_kartu' => null,
                ];
            }

            $nilaiPersediaanBaru = bcmul($qtyFisik, $hpp, 2);
            $jenisMutasi = bccomp($selisih, '0', 4) > 0 ? 'ADJ_IN' : 'ADJ_OUT';
            $qtyMutasi = bccomp($selisih, '0', 4) > 0
                ? $selisih
                : bcsub('0', $selisih, 4);

            Stok::query()->updateOrInsert(
                [
                    'id_gudang' => $gudangId,
                    'id_barang' => $barangId,
                ],
                [
                    'qty_on_hand' => $qtyFisik,
                    'qty_reserved' => $stok?->qty_reserved ?? '0',
                    'hpp_rata2' => $hpp,
                    'nilai_persediaan' => $nilaiPersediaanBaru,
                    'updated_at' => now(),
                ],
            );
            self::notifikasiJikaMencapaiMinimum($entitasId, $barangId, $qtyTersediaSebelum);

            $kartuStok = KartuStok::create([
                'id_entitas' => $entitasId,
                'id_gudang' => $gudangId,
                'id_barang' => $barangId,
                'tanggal' => now()->toDateString(),
                'jenis_mutasi' => $jenisMutasi,
                'ref_tipe' => 'OPNAME',
                'ref_id' => $opnameId,
                'qty_masuk' => $jenisMutasi === 'ADJ_IN' ? $qtyMutasi : '0',
                'qty_keluar' => $jenisMutasi === 'ADJ_OUT' ? $qtyMutasi : '0',
                'harga_satuan' => $hpp,
                'nilai_mutasi' => bcmul($qtyMutasi, $hpp, 2),
                'saldo_qty' => $qtyFisik,
                'saldo_nilai' => $nilaiPersediaanBaru,
                'hpp_rata2_setelah' => $hpp,
                'created_by' => $createdBy,
            ]);

            return [
                'qty_sistem' => $qtySistem,
                'selisih' => $selisih,
                'hpp_rata2' => $hpp,
                'nilai_selisih' => $nilaiSelisih,
                'id_kartu' => (int) $kartuStok->id_kartu,
            ];
        });
    }

    private function pastikanPositif(string $value, string $field): void
    {
        if (bccomp($value, '0', 4) <= 0) {
            throw new InvalidArgumentException("{$field} harus lebih besar dari nol.");
        }
    }

    private function pastikanTidakNegatif(string $value, string $field): void
    {
        if (bccomp($value, '0', 4) < 0) {
            throw new InvalidArgumentException("{$field} tidak boleh negatif.");
        }
    }

    // --- Ditambahkan dari branch feature/pembelian-penjualan ---

    /**
     * Update stok saat penerimaan barang (GRN).
     * Calculate moving average HPP.
     *
     * @param int $kooperasiId
     * @param int $idGudang
     * @param int $idBarang
     * @param float $qtyMasuk Qty dasar (satuan terkecil)
     * @param float $hppSatuan HPP per satuan
     * @param int $idReferencePenerimaan
     * @param string $jenisMutasi 'PENERIMAAN', 'RETUR_PENJUALAN', etc.
     * @return Stok
     * @throws \Exception
     */
    public static function masukBarang(
        int $entitasId,
        int $idGudang,
        int $idBarang,
        float $qtyMasuk,
        float $hppSatuan,
        int $idReferencePenerimaan,
        string $jenisMutasi,
    ): Stok {
        if ($qtyMasuk <= 0) {
            throw new \Exception('Qty masuk harus lebih besar dari 0.');
        }

        DB::beginTransaction();
        try {
            $qtyTersediaSebelum = self::qtyTersediaEntitasBarang($entitasId, $idBarang);
            $where = ['id_gudang' => $idGudang, 'id_barang' => $idBarang];

            $stok = DB::table('stok')->where($where)->first();
            if (!$stok) {
                DB::table('stok')->insert([
                    'id_gudang' => $idGudang,
                    'id_barang' => $idBarang,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'hpp_rata2' => 0,
                    'nilai_persediaan' => 0,
                ]);
                $stok = DB::table('stok')->where($where)->first();
            }

            // 2. Calculate moving average
            $qtyLama = (float) ($stok->qty_on_hand ?? 0);
            $hppLama = (float) ($stok->hpp_rata2 ?? 0);
            $nilaiLama = $qtyLama * $hppLama;
            $nilaiMasuk = $qtyMasuk * $hppSatuan;
            $qtyBaru = $qtyLama + $qtyMasuk;

            $hppBaru = $qtyBaru > 0 ? ($nilaiLama + $nilaiMasuk) / $qtyBaru : 0;

            // 3. Update stok
            DB::table('stok')->where($where)->update([
                'qty_on_hand' => $qtyBaru,
                'hpp_rata2' => $hppBaru,
                'nilai_persediaan' => $qtyBaru * $hppBaru,
            ]);
            self::notifikasiJikaMencapaiMinimum($entitasId, $idBarang, $qtyTersediaSebelum);

            $stok = Stok::where($where)->firstOrFail();

            // 4. Create kartu stok (detail mutasi)
            $kartuStok = KartuStok::create([
                'id_entitas' => $entitasId,
                'id_gudang' => $idGudang,
                'id_barang' => $idBarang,
                'tanggal' => now()->toDateString(),
                'jenis_mutasi' => 'IN',
                'ref_tipe' => $jenisMutasi,
                'ref_id' => $idReferencePenerimaan,
                'qty_masuk' => $qtyMasuk,
                'qty_keluar' => 0,
                'harga_satuan' => $hppSatuan,
                'nilai_mutasi' => $nilaiMasuk,
                'saldo_qty' => $qtyBaru,
                'saldo_nilai' => $qtyBaru * $hppBaru,
                'hpp_rata2_setelah' => $hppBaru,
            ]);

            $stokBatch = app(StokBatchService::class);
            if ($stokBatch->pelacakanAktif($entitasId, $idBarang)) {
                $stokBatch->catatPenerimaanTanpaRincian(
                    gudangId: $idGudang,
                    barangId: $idBarang,
                    qty: number_format($qtyMasuk, 4, '.', ''),
                    idKartu: (int) $kartuStok->id_kartu,
                    refId: $idReferencePenerimaan,
                );
            }

            DB::commit();

            return $stok;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update stok saat pengeluaran barang (penjualan, dll).
     * Gunakan HPP rata2 saat itu, JANGAN update HPP.
     *
     * @param int $kooperasiId
     * @param int $idGudang
     * @param int $idBarang
     * @param float $qtyKeluar
     * @param int $idReferencePenjualan
     * @return Stok
     * @throws \Exception
     */
    public static function keluarBarang(
        int $entitasId,
        int $idGudang,
        int $idBarang,
        float $qtyKeluar,
        int $idReferencePenjualan,
    ): Stok {
        if ($qtyKeluar <= 0) {
            throw new \Exception('Qty keluar harus lebih besar dari 0.');
        }

        DB::beginTransaction();
        try {
            $qtyTersediaSebelum = self::qtyTersediaEntitasBarang($entitasId, $idBarang);
            // 1. Ambil stok
            $where = ['id_gudang' => $idGudang, 'id_barang' => $idBarang];
            $stok = DB::table('stok')->where($where)->lockForUpdate()->firstOrFail();

            if ($stok->qty_on_hand < $qtyKeluar) {
                throw new \Exception(
                    "Stok tidak cukup. On hand: {$stok->qty_on_hand}, permintaan: {$qtyKeluar}"
                );
            }

            $stokBatch = app(StokBatchService::class);
            $alokasiBatch = null;
            if ($stokBatch->pelacakanAktif($entitasId, $idBarang)) {
                $alokasiBatch = $stokBatch->alokasiKeluar(
                    gudangId: $idGudang,
                    barangId: $idBarang,
                    qty: number_format($qtyKeluar, 4, '.', ''),
                );
            }

            // 2. Gunakan HPP saat ini (JANGAN update HPP)
            $hppPakai = (float) ($stok->hpp_rata2 ?? 0);
            $nilaiKeluar = $qtyKeluar * $hppPakai;

            // 3. Update qty, TIDAK update HPP
            $qtyBaru = $stok->qty_on_hand - $qtyKeluar;
            $nilaiPersediaanBaru = $qtyBaru * $hppPakai;

            DB::table('stok')->where($where)->update([
                'qty_on_hand' => $qtyBaru,
                'nilai_persediaan' => $nilaiPersediaanBaru,
            ]);
            self::notifikasiJikaMencapaiMinimum($entitasId, $idBarang, $qtyTersediaSebelum);

            // 4. Create kartu stok
            $kartuStok = KartuStok::create([
                'id_entitas' => $entitasId,
                'id_gudang' => $idGudang,
                'id_barang' => $idBarang,
                'tanggal' => now()->toDateString(),
                'jenis_mutasi' => 'OUT',
                'ref_tipe' => 'PENJUALAN',
                'ref_id' => $idReferencePenjualan,
                'qty_masuk' => 0,
                'qty_keluar' => $qtyKeluar,
                'harga_satuan' => $hppPakai,
                'nilai_mutasi' => $nilaiKeluar,
                'saldo_qty' => $qtyBaru,
                'saldo_nilai' => $nilaiPersediaanBaru,
                'hpp_rata2_setelah' => $hppPakai,
            ]);

            if ($alokasiBatch !== null) {
                $stokBatch->catatPengeluaran($kartuStok, $alokasiBatch);
            }

            DB::commit();

            return $stok;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * Reverse stock mutation (untuk pembatalan/koreksi).
     * Bayar perhatian: reversal hanya pada kartu stok, BUKAN update HPP.
     *
     * @param int $idKartuStok
     * @return void
     * @throws \Exception
     */
    public static function reverseMutasi(int $idKartuStok): void
    {
        DB::beginTransaction();
        try {
            $kartu = KartuStok::findOrFail($idKartuStok);
            $stokBatch = app(StokBatchService::class);
            if ($stokBatch->pelacakanAktif((int) $kartu->id_entitas, (int) $kartu->id_barang)) {
                throw new RuntimeException(
                    'Pembatalan mutasi barang dengan pelacakan batch aktif belum didukung. '
                    .'Gunakan transaksi koreksi yang mencatat batch.',
                );
            }

            $where = ['id_gudang' => $kartu->id_gudang, 'id_barang' => $kartu->id_barang];
            $qtyTersediaSebelum = self::qtyTersediaEntitasBarang((int) $kartu->id_entitas, (int) $kartu->id_barang);
            $stok = Stok::query()->where($where)->lockForUpdate()->firstOrFail();

            // Reverse: stok sekarang - qty_masuk_di_kartu + qty_keluar_di_kartu
            $qtyBaru = bcadd(
                bcsub((string) $stok->qty_on_hand, (string) $kartu->qty_masuk, 4),
                (string) $kartu->qty_keluar,
                4
            );

            $hppBaru = (string) $stok->hpp_rata2;
            $nilaiPersediaanBaru = bcmul($qtyBaru, $hppBaru, 2);

            Stok::query()->where($where)->update([
                'qty_on_hand' => $qtyBaru,
                'nilai_persediaan' => $nilaiPersediaanBaru,
                'updated_at' => now(),
            ]);
            self::notifikasiJikaMencapaiMinimum((int) $kartu->id_entitas, (int) $kartu->id_barang, $qtyTersediaSebelum);

            $kartu->update(['is_reversed' => true]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate stok cocok antara sistem dan fisik (stock opname).
     *
     * @param int $idGudang
     * @param array $stokFisik
     * @return array
     */
    public static function validateStockOpname(int $idGudang, array $stokFisik): array
    {
        $selisih = [];

        foreach ($stokFisik as $item) {
            $stok = Stok::where([
                'id_gudang' => $idGudang,
                'id_barang' => $item['id_barang'],
            ])->first();

            $qtySystem = $stok?->qty_on_hand ?? '0';
            $qtyFisik = (string) ($item['qty_fisik'] ?? '0');
            $diff = bcsub($qtyFisik, $qtySystem, 4);

            if (bccomp($diff, '0', 4) !== 0) {
                $selisih[] = [
                    'id_barang' => $item['id_barang'],
                    'qty_system' => $qtySystem,
                    'qty_fisik' => $qtyFisik,
                    'selisih' => $diff,
                    'jenis_selisih' => bccomp($diff, '0', 4) > 0 ? 'LEBIH' : 'KURANG',
                ];
            }
        }

        return $selisih;
    }

    /**
     * Post stock opname adjustments ke kartu stok dan jurnal.
     *
     * @param int $idGudang
     * @param array $adjustments
     * @return void
     */
    public static function postStockOpname(int $idGudang, array $adjustments): void
    {
        DB::beginTransaction();
        try {
            $entitasId = (int) DB::table('gudang')
                ->where('id_gudang', $idGudang)
                ->value('id_entitas');
            $stokBatch = app(StokBatchService::class);

            foreach ($adjustments as $adj) {
                $qtyTersediaSebelum = self::qtyTersediaEntitasBarang($entitasId, (int) $adj['id_barang']);
                if ($stokBatch->pelacakanAktif($entitasId, (int) $adj['id_barang'])) {
                    throw new RuntimeException(
                        'Opname melalui jalur lama tidak dapat digunakan untuk barang dengan pelacakan batch aktif. '
                        .'Gunakan alur opname gudang yang mencatat penyesuaian batch.',
                    );
                }

                $where = ['id_gudang' => $idGudang, 'id_barang' => $adj['id_barang']];
                $stok = Stok::query()->where($where)->lockForUpdate()->firstOrFail();

                $qtyBaru = (string) $adj['qty_fisik'];
                $hppPakai = (string) $stok->hpp_rata2;
                $nilaiPersediaanBaru = bcmul($qtyBaru, $hppPakai, 2);

                Stok::query()->where($where)->update([
                    'qty_on_hand' => $qtyBaru,
                    'nilai_persediaan' => $nilaiPersediaanBaru,
                    'updated_at' => now(),
                ]);
                self::notifikasiJikaMencapaiMinimum($entitasId, (int) $adj['id_barang'], $qtyTersediaSebelum);

                $diff = (string) $adj['selisih'];
                $absDiff = ltrim($diff, '-'); 
                $nilaiMutasi = bcmul($absDiff, $hppPakai, 2);

                KartuStok::create([
                    'id_entitas' => app('entitas_aktif') ?: 1,
                    'id_gudang' => $idGudang,
                    'id_barang' => $adj['id_barang'],
                    'tanggal' => now()->toDateString(),
                    'jenis_mutasi' => $adj['jenis_selisih'] === 'LEBIH' ? 'ADJ_IN' : 'ADJ_OUT',
                    'ref_tipe' => 'OPNAME',
                    'ref_id' => 0,
                    'qty_masuk' => $adj['jenis_selisih'] === 'LEBIH' ? $absDiff : '0',
                    'qty_keluar' => $adj['jenis_selisih'] === 'KURANG' ? $absDiff : '0',
                    'harga_satuan' => $hppPakai,
                    'nilai_mutasi' => $nilaiMutasi,
                    'saldo_qty' => $qtyBaru,
                    'saldo_nilai' => $nilaiPersediaanBaru,
                    'hpp_rata2_setelah' => $hppPakai,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        private static function qtyTersediaEntitasBarang(int $entitasId, int $barangId): string
        {
            $total = '0';
            $saldo = DB::table('stok')
                ->join('gudang', 'gudang.id_gudang', '=', 'stok.id_gudang')
                ->where('gudang.id_entitas', $entitasId)
                ->where('stok.id_barang', $barangId)
                ->get(['stok.qty_on_hand', 'stok.qty_reserved']);

            foreach ($saldo as $stok) {
                $tersedia = bcsub((string) $stok->qty_on_hand, (string) $stok->qty_reserved, 4);
                if (bccomp($tersedia, '0', 4) < 0) {
                    $tersedia = '0';
                }
                $total = bcadd($total, $tersedia, 4);
            }

            return $total;
        }

        private static function notifikasiJikaMencapaiMinimum(int $entitasId, int $barangId, string $qtySebelum): void
        {
            $stokMinimum = DB::table('barang_per_koperasi')
                ->where('id_entitas', $entitasId)
                ->where('id_barang', $barangId)
                ->value('stok_minimum');
            if ($stokMinimum === null || bccomp((string) $stokMinimum, '0', 4) <= 0) {
                return;
            }

            $qtySesudah = self::qtyTersediaEntitasBarang($entitasId, $barangId);
            if (
                bccomp($qtySebelum, (string) $stokMinimum, 4) <= 0
                || bccomp($qtySesudah, (string) $stokMinimum, 4) > 0
            ) {
                return;
            }

            $barang = Barang::query()->with('satuanDasar')->find($barangId);
            $jumlah = rtrim(rtrim(number_format((float) $qtySesudah, 4, ',', '.'), '0'), ',');
            $minimum = rtrim(rtrim(number_format((float) $stokMinimum, 4, ',', '.'), '0'), ',');
            $satuan = $barang?->satuanDasar?->kode_satuan;
            $teksSatuan = $satuan ? ' '.$satuan : '';
            $notifikasi = new AppNotification(
                title: 'Stok mencapai batas minimum',
                message: ($barang?->nama_barang ?? 'Barang').' tersisa '.$jumlah.$teksSatuan.' (batas minimum '.$minimum.$teksSatuan.').',
                url: route('master.barang.stok-minimum.index'),
                data: ['type' => 'stok_minimum', 'id_entitas' => $entitasId, 'id_barang' => $barangId],
            );

            Pengguna::query()
                ->where('id_entitas', $entitasId)
                ->whereHas('peran', fn ($query) => $query->where('kode', 'manajer'))
                ->get()
                ->each(fn (Pengguna $manajer) => $manajer->notify($notifikasi));
        }
    }
}
