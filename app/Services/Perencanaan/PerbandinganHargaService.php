<?php

namespace App\Services\Perencanaan;

use App\Models\Perencanaan\PerbandinganHarga;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Survei\PemenuhanKomoditas;
use Illuminate\Support\Collection;
use App\Support\Perencanaan\PeriodeKalkulasi;
use Carbon\Carbon;

class PerbandinganHargaService
{
    /**
     * @return Collection<int, KetersediaanKomoditas>
     */
    public function calonSupplier(int $idKomoditas, int $tahun, string|int $periode): Collection
    {
        $bulan = is_int($periode) ? [$periode] : PeriodeKalkulasi::detail($periode)['bulan'];
        return KetersediaanKomoditas::query()
            ->with(['wilayah', 'komoditas'])
            ->where('id_komoditas', $idKomoditas)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $bulan)
            ->where('jumlah_produksi', '>', 0)
            ->orderByDesc('jumlah_produksi')
            ->get();
    }

    /**
     * Build a comparison from the latest survey prices in the requested period.
     *
     * @return array{items: Collection, lowest: ?float, highest: ?float, difference: ?float, satuan: ?string}
     */
    public function bandingkan(
        int $idEntitas,
        int $idKomoditas,
        int $tahun,
        string|int $periode,
    ): array {
        $legacyMonthly = is_int($periode);
        $bulanLegacy = $legacyMonthly ? $periode : null;
        $periode = $legacyMonthly ? PeriodeKalkulasi::fromMonth($periode) : $periode;
        $detail = PeriodeKalkulasi::detail($periode);
        if ($legacyMonthly) {
            $detail['bulan_awal'] = $bulanLegacy;
            $detail['bulan_akhir'] = $bulanLegacy;
        }
        $prices = PemenuhanKomoditas::query()
            ->select('pemenuhan_komoditas.*')
            ->with(['komoditas', 'sesi.wilayah'])
            ->where('id_komoditas', $idKomoditas)
            ->whereBetween('tanggal_survei', [
                Carbon::createFromDate($tahun, $detail['bulan_awal'], 1)->startOfMonth()->toDateString(),
                Carbon::createFromDate($tahun, $detail['bulan_akhir'], 1)->endOfMonth()->toDateString(),
            ])
            ->where('harga', '>', 0)
            ->orderBy('harga')
            ->get();

        $satuan = $prices
            ->groupBy(fn (PemenuhanKomoditas $price): string => mb_strtolower(trim((string) $price->satuan_harga)))
            ->sortByDesc(fn (Collection $rows): int => $rows->count())
            ->keys()
            ->first();
        $prices = $satuan === null
            ? $prices
            : $prices->filter(fn (PemenuhanKomoditas $price): bool => mb_strtolower(trim((string) $price->satuan_harga)) === $satuan)->values();

        $items = $prices
            ->groupBy(fn (PemenuhanKomoditas $price): int => (int) $price->sesi->id_wilayah)
            ->map(function (Collection $rows) use ($idEntitas, $detail, $tahun): PerbandinganHarga {
                /** @var PemenuhanKomoditas $first */
                $first = $rows->first();
                $average = (float) $rows->avg(fn (PemenuhanKomoditas $row): float => (float) $row->harga);

                $comparison = new PerbandinganHarga([
                    'id_entitas' => $idEntitas,
                    'id_komoditas' => $first->id_komoditas,
                    'id_wilayah_sumber' => $first->sesi->id_wilayah,
                    'bulan' => $detail['bulan_awal'],
                    'tahun' => $tahun,
                    'harga_ditawarkan' => $average,
                    'jumlah_tersedia' => 0,
                    'harga_efektif' => $average,
                ]);
                $comparison->setRelation('wilayahSumber', $first->sesi->wilayah);

                return $comparison;
            })
            ->sortBy('harga_efektif')
            ->values();

        $items->each(function (PerbandinganHarga $item, int $index): void {
            $item->rank_harga = $index + 1;
        });

        $values = $items->pluck('harga_efektif')->map(fn ($value): float => (float) $value);

        return [
            'items' => $items,
            'lowest' => $values->isEmpty() ? null : $values->min(),
            'highest' => $values->isEmpty() ? null : $values->max(),
            'difference' => $values->isEmpty() ? null : $values->max() - $values->min(),
            'satuan' => $prices->first()?->satuan_harga,
        ];
    }
}
