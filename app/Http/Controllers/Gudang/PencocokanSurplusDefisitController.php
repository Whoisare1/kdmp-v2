<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Gudang\PermintaanAntarEntitas;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Services\Gudang\StokPerencanaanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PencocokanSurplusDefisitController extends Controller
{
    use ResolvesWilayahAktif;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'status_permintaan' => ['nullable', 'in:semua,terkirim,disetujui,ditolak,dibatalkan'],
        ]);

        $idWilayah = $this->wilayahAktifId();
        $entitasId = (int) (auth()->user()->id_entitas ?? 0);
        $tahun = (int) ($validated['tahun'] ?? now()->year);
        $bulan = isset($validated['bulan']) ? (int) $validated['bulan'] : null;
        $statusPermintaan = $validated['status_permintaan'] ?? 'semua';

        $hasilTerbaru = HasilKalkulasi::query()
            ->with(['komoditas', 'wilayah'])
            ->where('tahun', $tahun)
            ->when($bulan !== null, fn (Builder $query) => $query->where('bulan', $bulan))
            ->where('status', 'disetujui')
            ->whereHas('wilayah', fn (Builder $query) => $query
                ->where('tingkat', 'desa')
                ->whereHas('entitas', fn (Builder $entitas) => $entitas
                    ->where('is_active', true)
                    ->where('status', 'APPROVED')))
            ->whereRaw(
                "hasil_kalkulasi.versi = (SELECT MAX(h2.versi) FROM hasil_kalkulasi h2 WHERE h2.id_wilayah = hasil_kalkulasi.id_wilayah AND h2.id_komoditas = hasil_kalkulasi.id_komoditas AND h2.tahun = hasil_kalkulasi.tahun AND h2.bulan = hasil_kalkulasi.bulan AND h2.status = 'disetujui')"
            )
            ->orderBy('bulan')
            ->orderBy('id_komoditas')
            ->get();

        $idWilayahHasil = $hasilTerbaru->pluck('id_wilayah')->unique()->values();
        $entitasPerWilayah = DB::table('entitas')
            ->whereIn('id_wilayah', $idWilayahHasil)
            ->where('is_active', true)
            ->where('status', 'APPROVED')
            ->pluck('id_entitas', 'id_wilayah');

        $satuanPerHasil = KebutuhanKomoditas::query()
            ->whereIn('id_wilayah', $idWilayahHasil)
            ->where('tahun', $tahun)
            ->when($bulan !== null, fn (Builder $query) => $query->where('bulan', $bulan))
            ->get(['id_wilayah', 'id_komoditas', 'tahun', 'bulan', 'satuan'])
            ->groupBy(fn ($row): string => $this->kunciHasil(
                (int) $row->id_wilayah,
                (int) $row->id_komoditas,
                (int) $row->tahun,
                (int) $row->bulan,
            ))
            ->map(function (Collection $rows): ?string {
                $satuan = $rows->pluck('satuan')->map(fn ($value): string => strtoupper(trim((string) $value)))->unique();

                return $satuan->count() === 1 ? $satuan->first() : null;
            });

        $stokPerencanaan = app(StokPerencanaanService::class);
        $stokTersediaCache = [];
        $stokDapatDijualCache = [];
        $hasilTerbaru->each(function (HasilKalkulasi $hasil) use ($satuanPerHasil, $entitasPerWilayah, $stokPerencanaan, &$stokTersediaCache, &$stokDapatDijualCache): void {
            $kunci = $this->kunciHasil(
                (int) $hasil->id_wilayah,
                (int) $hasil->id_komoditas,
                (int) $hasil->tahun,
                (int) $hasil->bulan,
            );
            $satuan = $satuanPerHasil->get($kunci);
            $namaKomoditas = (string) $hasil->komoditas?->nama;
            $entitasId = (int) $entitasPerWilayah->get($hasil->id_wilayah, 0);
            $komoditasId = (int) $hasil->id_komoditas;
            $kunciStok = $entitasId.':'.$komoditasId.':'.(string) $satuan;
            if ($satuan !== null && ! array_key_exists($kunciStok, $stokTersediaCache)) {
                $stokTersediaCache[$kunciStok] = $stokPerencanaan->tersedia($entitasId, $komoditasId, $satuan);
                $stokDapatDijualCache[$kunciStok] = $stokPerencanaan->tersedia($entitasId, $komoditasId, $satuan, hanyaDapatDijual: true);
            }
            $qtyStok = $satuan !== null ? (float) ($stokTersediaCache[$kunciStok] ?? 0) : 0.0;
            $qtyStokDapatDijual = $satuan !== null ? (float) ($stokDapatDijualCache[$kunciStok] ?? 0) : 0.0;
            $qtyNeraca = abs((float) $hasil->selisih);
            $qtyTindakLanjut = match ($hasil->status_surplus_defisit) {
                'surplus' => min($qtyNeraca, $qtyStokDapatDijual),
                'defisit' => max(0, $qtyNeraca - $qtyStok),
                default => 0.0,
            };

            $hasil->setAttribute('satuan_perencanaan', $satuan);
            $hasil->setAttribute('satuan_cocok', $satuan !== null);
            $hasil->setAttribute('qty_stok_tersedia', $qtyStok);
            $hasil->setAttribute('qty_stok_dapat_dijual', $qtyStokDapatDijual);
            $hasil->setAttribute('qty_tindak_lanjut', $qtyTindakLanjut);
            $hasil->setAttribute('nama_komoditas_cocok', $namaKomoditas);
        });

        $neracaDesaSaya = $hasilTerbaru
            ->where('id_wilayah', $idWilayah)
            ->whereIn('status_surplus_defisit', ['surplus', 'defisit'])
            ->values();

        $pasangan = $neracaDesaSaya->mapWithKeys(function (HasilKalkulasi $hasil) use ($hasilTerbaru): array {
            $statusTujuan = $hasil->status_surplus_defisit === 'surplus' ? 'defisit' : 'surplus';
            $jumlahSaya = (float) $hasil->qty_tindak_lanjut;

            $kandidat = $hasilTerbaru
                ->filter(fn (HasilKalkulasi $calon) => (int) $calon->id_wilayah !== (int) $hasil->id_wilayah
                    && (int) $calon->id_komoditas === (int) $hasil->id_komoditas
                    && (int) $calon->bulan === (int) $hasil->bulan
                    && $calon->status_surplus_defisit === $statusTujuan
                    && $calon->satuan_cocok
                    && $calon->satuan_perencanaan === $hasil->satuan_perencanaan
                    && (float) $calon->qty_tindak_lanjut > 0
                    && $jumlahSaya > 0)
                ->map(fn (HasilKalkulasi $calon) => [
                    'hasil' => $calon,
                    'jumlah_cocok' => min($jumlahSaya, (float) $calon->qty_tindak_lanjut),
                ])
                ->values();

            return [$hasil->id_hasil => $kandidat];
        });

        $idHasilTerlihat = $neracaDesaSaya->pluck('id_hasil')
            ->merge($pasangan->flatMap(fn (Collection $kandidat) => $kandidat->pluck('hasil.id_hasil')))
            ->unique()
            ->values();
        $permintaanAktif = PermintaanAntarEntitas::query()
            ->where('status', '!=', 'dibatalkan')
            ->whereIn('id_hasil_pengaju', $idHasilTerlihat)
            ->whereIn('id_hasil_penerima', $idHasilTerlihat)
            ->get(['id_hasil_pengaju', 'id_hasil_penerima'])
            ->mapWithKeys(fn (PermintaanAntarEntitas $permintaan): array => [
                $this->kunciPasangan((int) $permintaan->id_hasil_pengaju, (int) $permintaan->id_hasil_penerima) => true,
            ]);
        $riwayatPermintaan = PermintaanAntarEntitas::query()
            ->with(['pengaju', 'penerima'])
            ->where(fn (Builder $query) => $query
                ->where('id_entitas_pengaju', $entitasId)
                ->orWhere('id_entitas_penerima', $entitasId))
            ->when($statusPermintaan !== 'semua', fn (Builder $query) => $query->where('status', $statusPermintaan))
            ->latest('created_at')
            ->limit(30)
            ->get();
        $jumlahMenungguRespons = PermintaanAntarEntitas::query()
            ->where('id_entitas_penerima', $entitasId)
            ->where('status', 'terkirim')
            ->count();

        return view('gudang.pencocokan-surplus-defisit', [
            'neracaDesaSaya' => $neracaDesaSaya,
            'pasangan' => $pasangan,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'statusPermintaan' => $statusPermintaan,
            'permintaanAktif' => $permintaanAktif,
            'riwayatPermintaan' => $riwayatPermintaan,
            'jumlahMenungguRespons' => $jumlahMenungguRespons,
            'entitasId' => $entitasId,
        ]);
    }

    private function kunciHasil(int $wilayahId, int $komoditasId, int $tahun, int $bulan): string
    {
        return $wilayahId.':'.$komoditasId.':'.$tahun.':'.$bulan;
    }

    private function kunciPasangan(int $hasilA, int $hasilB): string
    {
        return min($hasilA, $hasilB).':'.max($hasilA, $hasilB);
    }

}
