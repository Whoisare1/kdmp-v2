<?php

namespace App\Services\Gudang;

use App\Models\Gudang\RekomendasiSurplusDefisit;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Master\Pihak;
use App\Models\Pembelian\Pembelian;
use App\Models\Tenant\Entitas as TenantEntitas;
use App\Services\Pembelian\PurchaseRecommendation;
use App\Services\Pembelian\PurchaseRecommendationProvider;
use App\Services\Pembelian\PurchaseSupplierCandidate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class GudangPurchaseRecommendationProvider implements PurchaseRecommendationProvider
{
    public function pendingForEntity(int $idEntitas): Collection
    {
        if ($idEntitas < 1) {
            throw new InvalidArgumentException('Entitas tujuan rekomendasi harus valid.');
        }

        $rows = RekomendasiSurplusDefisit::query()
            ->where('jenis_rekomendasi', 'pembelian')
            ->where('id_entitas_tujuan', $idEntitas)
            ->where('status', 'menunggu')
            ->where('tgl_berlaku_sampai', '>', now())
            ->orderBy('id_rekomendasi')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $barangs = Barang::with(['satuanDasar', 'konversiSatuan.satuan', 'unitUsaha'])
            ->where('is_active', true)
            ->whereIn('id_barang', $rows->pluck('id_barang')->unique())
            ->get()
            ->keyBy('id_barang');
        $gudangs = Gudang::query()
            ->where('id_entitas', $idEntitas)
            ->where('is_active', true)
            ->whereIn('id_gudang', $rows->pluck('id_gudang')->unique())
            ->get()
            ->keyBy('id_gudang');
        $pihaks = Pihak::query()
            ->where('id_entitas', $idEntitas)
            ->where('jenis_pihak', 'entitas_lain')
            ->where('is_active', true)
            ->whereIn('id_pihak', $rows->pluck('id_pihak_mitra')->unique())
            ->get()
            ->keyBy('id_pihak');
        $entitasAsal = TenantEntitas::query()
            ->whereIn('id_entitas', $rows->pluck('id_entitas_asal')->unique())
            ->get()
            ->keyBy('id_entitas');

        return $rows->map(function (RekomendasiSurplusDefisit $row) use ($barangs, $gudangs, $pihaks, $entitasAsal, $idEntitas): PurchaseRecommendation {
            $barang = $barangs->get($row->id_barang);
            $gudang = $gudangs->get($row->id_gudang);
            $pihak = $pihaks->get($row->id_pihak_mitra);
            $asal = $entitasAsal->get($row->id_entitas_asal);

            if (! $barang || ! $gudang || ! $pihak || ! $asal) {
                throw new RuntimeException("Rekomendasi Gudang {$row->id_rekomendasi} memiliki barang, gudang, pemasok, atau entitas asal yang tidak tersedia.");
            }
            if ((int) $pihak->id_entitas_mitra !== (int) $row->id_entitas_asal) {
                throw new RuntimeException("Pemasok rekomendasi Gudang {$row->id_rekomendasi} tidak cocok dengan entitas asal.");
            }
            if ((int) $barang->id_unit_usaha !== (int) $row->id_unit_usaha || ! $barang->unitUsaha) {
                throw new RuntimeException("Unit usaha barang pada rekomendasi Gudang {$row->id_rekomendasi} tidak cocok.");
            }

            [$idSatuanKg, $faktorKeDasarKg] = $this->kgConversion($barang, (int) $row->id_rekomendasi);
            $quantityKg = round((float) $row->qty_disetujui / $faktorKeDasarKg, 4);
            $pricePerKg = round((float) $row->harga_satuan_kesepakatan * $faktorKeDasarKg, 2);
            $candidate = new PurchaseSupplierCandidate(
                $pihak,
                'desa_lain',
                (string) $asal->nama_entitas,
                $quantityKg,
                'KG',
                $pricePerKg,
            );

            return new PurchaseRecommendation(
                (string) $row->id_rekomendasi,
                $idEntitas,
                "RSD-{$row->id_pengajuan_gudang}-{$row->id_rekomendasi}",
                (int) $row->id_pengajuan_gudang,
                $barang,
                $idSatuanKg,
                $quantityKg,
                $barang->unitUsaha,
                $gudang,
                collect([$candidate]),
            );
        })->values();
    }

    public function markProcessed(int $idEntitas, string $recommendationId, Pembelian $pembelian): void
    {
        $idRekomendasi = filter_var($recommendationId, FILTER_VALIDATE_INT);
        if ($idEntitas < 1 || $idRekomendasi === false || $idRekomendasi < 1) {
            throw new InvalidArgumentException('Entitas dan ID rekomendasi harus valid.');
        }
        if ((int) $pembelian->id_entitas !== $idEntitas || ! $pembelian->getKey()) {
            throw new InvalidArgumentException('Pembelian harus memiliki ID dan milik entitas tujuan rekomendasi.');
        }

        DB::transaction(function () use ($idEntitas, $idRekomendasi, $pembelian): void {
            $row = RekomendasiSurplusDefisit::query()
                ->where('id_rekomendasi', $idRekomendasi)
                ->where('jenis_rekomendasi', 'pembelian')
                ->where('id_entitas_tujuan', $idEntitas)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $row->status === 'diproses'
                && (int) $row->id_transaksi_terbentuk === (int) $pembelian->getKey()
            ) {
                return;
            }

            if ($row->status !== 'menunggu' || now()->greaterThanOrEqualTo($row->tgl_berlaku_sampai)) {
                throw new RuntimeException('Rekomendasi Gudang tidak lagi menunggu transaksi atau sudah kedaluwarsa.');
            }

            $row->update([
                'status' => 'diproses',
                'id_transaksi_terbentuk' => (int) $pembelian->getKey(),
            ]);
        });
    }

    /**
     * @return array{int, float}
     */
    private function kgConversion(Barang $barang, int $idRekomendasi): array
    {
        if (strtoupper($barang->satuanDasar?->kode_satuan ?? '') === 'KG') {
            return [(int) $barang->id_satuan_dasar, 1.0];
        }

        $conversion = $barang->konversiSatuan->first(
            fn ($item) => strtoupper($item->satuan?->kode_satuan ?? '') === 'KG'
                && (float) $item->faktor_ke_dasar > 0,
        );

        if (! $conversion) {
            throw new RuntimeException("Barang pada rekomendasi Gudang {$idRekomendasi} tidak memiliki konversi KG.");
        }

        return [(int) $conversion->id_satuan, (float) $conversion->faktor_ke_dasar];
    }
}
