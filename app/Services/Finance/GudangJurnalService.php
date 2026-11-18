<?php

namespace App\Services\Finance;

use App\Exceptions\Finance\AkunTidakDitemukanException;
use App\Exceptions\Finance\JurnalTidakBalanceException;
use App\Exceptions\Finance\PeriodeTutupException;
use App\Models\Akuntansi\JurnalHeader;
use App\Models\Gudang\AntreanJurnalGudang;
use App\Models\Gudang\KartuStok;
use App\Models\Master\UnitUsaha;
use App\Models\Pembelian\Pembelian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GudangJurnalService
{
    public function __construct(
        protected JurnalService $jurnalService
    ) {}

    /**
     * Memproses satu item antrean jurnal gudang.
     */
    public function prosesItem(AntreanJurnalGudang $antrean): bool
    {
        // Kunci baris agar tidak diproses ganda oleh dua worker bersamaan
        return DB::transaction(function () use ($antrean) {
            $item = AntreanJurnalGudang::lockForUpdate()->find($antrean->id_antrean);

            if (! $item || in_array($item->status, ['DIPOSTING', 'DIBATALKAN'])) {
                return false;
            }

            $item->update([
                'status' => 'DIPROSES',
                'diproses_pada' => now(),
                'jumlah_percobaan' => $item->jumlah_percobaan + 1,
            ]);

            try {
                $payload = $item->payload ?? [];
                $idPembelian = $payload['id_pembelian'] ?? null;
                $sumberPenerimaan = $payload['sumber_penerimaan'] ?? ($idPembelian ? 'pembelian' : 'non_pembelian');

                // 1. Kasus Penerimaan yang terhubung dengan Pembelian atau Saldo Awal (tidak membuat jurnal PNB dobel)
                if ($item->jenis_transaksi === 'PENERIMAAN' && $sumberPenerimaan === 'pembelian') {
                    return $this->prosesPenerimaanDariPembelian($item, $idPembelian ? (int) $idPembelian : null, $payload['items'] ?? []);
                }

                if ($item->jenis_transaksi === 'PENERIMAAN' && $sumberPenerimaan === 'saldo_awal') {
                    return $this->prosesPenerimaanSaldoAwal($item, $payload['items'] ?? []);
                }

                // 2. Tentukan kode transaksi template
                $kodeTransaksi = match ($item->jenis_transaksi) {
                    'KERUSAKAN' => 'KRG',
                    'OPNAME_KURANG' => 'OPK',
                    'OPNAME_LEBIH' => 'OPL',
                    'PENERIMAAN' => 'PNB',
                    default => throw new \InvalidArgumentException("Jenis transaksi {$item->jenis_transaksi} tidak dikenal."),
                };

                // Ambil kode unit usaha
                $unitUsaha = UnitUsaha::withoutGlobalScopes()->find($item->id_unit_usaha);
                if (! $unitUsaha) {
                    throw new AkunTidakDitemukanException('UNIT_USAHA', "Unit usaha #{$item->id_unit_usaha} tidak ditemukan.");
                }

                $sumberVariabel = match ($kodeTransaksi) {
                    'KRG' => 'nilai_kerugian',
                    'OPK', 'OPL' => 'nilai_selisih',
                    'PNB' => 'nilai_penerimaan',
                };

                $nilaiTotal = (float) $item->nilai;

                $postingPayload = [
                    'tanggal_jurnal' => $item->tanggal->toDateString(),
                    'nomor_nota' => $item->no_dokumen,
                    'kode_unit' => $unitUsaha->kode_unit_usaha,
                    $sumberVariabel => $nilaiTotal,
                ];

                $keterangan = "{$item->jenis_transaksi} Gudang: {$item->no_dokumen} ({$unitUsaha->nama_unit_usaha})";

                $jurnal = $this->jurnalService->posting(
                    kodeTransaksi: $kodeTransaksi,
                    payload: $postingPayload,
                    sourceType: AntreanJurnalGudang::class,
                    sourceId: (int) $item->id_antrean,
                    keterangan: $keterangan,
                    entitasId: (int) $item->id_entitas,
                );

                $item->update([
                    'status' => 'DIPOSTING',
                    'id_jurnal' => $jurnal->id_jurnal,
                    'no_jurnal' => $jurnal->no_jurnal,
                    'kode_error' => null,
                    'pesan_error' => null,
                ]);

                // Update id_jurnal ke kartu_stok
                $this->tautkanKartuStok($payload['items'] ?? [], $jurnal->id_jurnal);

                return true;

            } catch (PeriodeTutupException $e) {
                $item->update([
                    'status' => 'GAGAL',
                    'kode_error' => 'PERIODE_TUTUP',
                    'pesan_error' => $e->getMessage(),
                ]);
            } catch (AkunTidakDitemukanException $e) {
                $item->update([
                    'status' => 'GAGAL',
                    'kode_error' => 'AKUN_TIDAK_DITEMUKAN',
                    'pesan_error' => $e->getMessage(),
                ]);
            } catch (JurnalTidakBalanceException $e) {
                $item->update([
                    'status' => 'GAGAL',
                    'kode_error' => 'TIDAK_BALANCE',
                    'pesan_error' => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                Log::error("Gagal memproses antrean jurnal gudang #{$item->id_antrean}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
                $item->update([
                    'status' => 'GAGAL',
                    'kode_error' => 'SISTEM',
                    'pesan_error' => $e->getMessage(),
                ]);
            }

            return false;
        });
    }

    /**
     * Memproses batch antrean berstatus MENUNGGU atau GAGAL
     */
    public function prosesAntrean(int $limit = 50, ?int $entitasId = null): int
    {
        $query = AntreanJurnalGudang::query()
            ->whereIn('status', ['MENUNGGU', 'GAGAL'])
            ->where('jumlah_percobaan', '<', 5)
            ->when($entitasId !== null, fn ($q) => $q->where('id_entitas', $entitasId))
            ->orderBy('id_antrean');

        $items = $query->limit($limit)->get();
        $sukses = 0;

        foreach ($items as $item) {
            if ($this->prosesItem($item)) {
                $sukses++;
            }
        }

        return $sukses;
    }

    protected function prosesPenerimaanDariPembelian(AntreanJurnalGudang $item, ?int $idPembelian, array $stockItems): bool
    {
        if (! $idPembelian) {
            return $this->tandaiGagal($item, 'PEMBELIAN_TIDAK_DITEMUKAN', 'Referensi pembelian (id_pembelian) tidak ditemukan pada antrean penerimaan.');
        }

        $pembelian = Pembelian::withoutGlobalScopes()->with('jurnal')->find($idPembelian);
        if (! $pembelian) {
            return $this->tandaiGagal($item, 'PEMBELIAN_TIDAK_DITEMUKAN', "Pembelian #{$idPembelian} tidak ditemukan.");
        }

        if (! $pembelian->id_jurnal) {
            return $this->tandaiGagal($item, 'JURNAL_BELI_BELUM_ADA', "Pembelian #{$pembelian->kode_pembelian} belum memiliki jurnal akuntansi. Setujui pembelian terlebih dahulu.");
        }

        $noJurnal = $pembelian->jurnal?->no_jurnal ?? ('JRN-REF-' . $pembelian->id_jurnal);

        return $this->tautkanKeJurnal(
            $item,
            (int) $pembelian->id_jurnal,
            $noJurnal,
            'Ditautkan ke Jurnal Pembelian #' . $pembelian->kode_pembelian,
            $stockItems,
        );
    }

    protected function prosesPenerimaanSaldoAwal(AntreanJurnalGudang $item, array $stockItems): bool
    {
        $jurnalSaldoAwal = JurnalHeader::withoutGlobalScopes()
            ->where('id_entitas', $item->id_entitas)
            ->where('jenis_jurnal', 'SALDO_AWAL')
            ->where('status', 'POSTED')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('jurnal_header as rev')
                    ->whereColumn('rev.id_jurnal_asal', 'jurnal_header.id_jurnal');
            })
            ->orderByRaw('CASE WHEN periode_tahun = ? THEN 0 ELSE 1 END', [$item->tanggal->year])
            ->latest('id_jurnal')
            ->first();

        if (! $jurnalSaldoAwal) {
            return $this->tandaiGagal(
                $item,
                'SALDO_AWAL_BELUM_ADA',
                'Jurnal Saldo Awal (JOP) belum dibuat di menu Akuntansi -> Saldo Awal.'
            );
        }

        return $this->tautkanKeJurnal(
            $item,
            (int) $jurnalSaldoAwal->id_jurnal,
            $jurnalSaldoAwal->no_jurnal,
            'Ditautkan ke Jurnal Saldo Awal ' . $jurnalSaldoAwal->no_jurnal,
            $stockItems,
        );
    }

    protected function tautkanKeJurnal(AntreanJurnalGudang $item, int $idJurnal, string $noJurnal, string $pesan, array $stockItems): bool
    {
        $item->update([
            'status' => 'DIPOSTING',
            'id_jurnal' => $idJurnal,
            'no_jurnal' => $noJurnal,
            'kode_error' => null,
            'pesan_error' => $pesan,
        ]);

        $this->tautkanKartuStok($stockItems, $idJurnal);

        return true;
    }

    protected function tandaiGagal(AntreanJurnalGudang $item, string $kodeError, string $pesanError): bool
    {
        $item->update([
            'status' => 'GAGAL',
            'kode_error' => $kodeError,
            'pesan_error' => $pesanError,
        ]);

        return false;
    }

    /**
     * Menautkan id_jurnal kembali ke kartu_stok berdasarkan id_kartu di payload item.
     */
    protected function tautkanKartuStok(array $items, int $idJurnal): void
    {
        $idKartuList = array_filter(array_column($items, 'id_kartu'));
        if (! empty($idKartuList)) {
            KartuStok::withoutGlobalScopes()
                ->whereIn('id_kartu', $idKartuList)
                ->whereNull('id_jurnal')
                ->update(['id_jurnal' => $idJurnal]);
        }
    }
}

