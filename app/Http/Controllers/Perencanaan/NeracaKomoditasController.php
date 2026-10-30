<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Models\Perencanaan\KetersediaanKomoditas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\Perencanaan\PeriodeKalkulasi;

class NeracaKomoditasController extends ModuleCrudController
{
    use ResolvesWilayahAktif;

    protected string $model = HasilKalkulasi::class;
    protected string $view = 'perencanaan.neraca-komoditas';
    protected string $title = 'Surplus Defisit';
    protected string $routeBase = 'perencanaan.neraca-komoditas';
    protected array $withRelations = ['wilayah', 'komoditas'];

    public function index(Request $request): View
    {
        $idWilayah = $this->wilayahAktifId();

        $tahun = (int) $request->query('tahun', 2026);
        $bulanQuery = $request->query('bulan');
        $periode = $request->query('periode');
        $bulan = $periode ? PeriodeKalkulasi::detail($periode)['bulan_awal'] : ($bulanQuery ? (int) $bulanQuery : null);
        $idKomoditas = $request->filled('id_komoditas') ? (int) $request->query('id_komoditas') : null;
        $status = $request->query('status_surplus_defisit');

        $query = HasilKalkulasi::with(['wilayah', 'komoditas'])
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun);

        $this->onlyLatestVersion($query);

        if ($bulan !== null) {
            $query->where('bulan', $bulan);
        }

        if ($idKomoditas !== null) {
            $query->where('id_komoditas', $idKomoditas);
        }

        if ($status !== null && in_array($status, ['surplus', 'defisit', 'seimbang'], true)) {
            $query->where('status_surplus_defisit', $status);
        }

        if ($search = $request->query('q')) {
            $query->whereHas('komoditas', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->orderBy('id_komoditas')
            ->paginate($this->perPage)
            ->withQueryString();

        $daftarKomoditas = Komoditas::where('is_active', true)->orderBy('nama')->get();

        // Satuan komoditas
        $satuanMap = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->pluck('satuan', 'id_komoditas')
            ->toArray();

        // Ringkasan status neraca
        $baseQuery = HasilKalkulasi::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan));

        $this->onlyLatestVersion($baseQuery);

        $totalSurplus = (clone $baseQuery)->where('status_surplus_defisit', 'surplus')->count();
        $totalDefisit = (clone $baseQuery)->where('status_surplus_defisit', 'defisit')->count();
        $totalSeimbang = (clone $baseQuery)->where('status_surplus_defisit', 'seimbang')->count();

        // Kelengkapan data standar kebutuhan per komoditas
        $kelengkapanStandarRaw = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->selectRaw('id_komoditas, COUNT(CASE WHEN id_standar IS NOT NULL AND per_kapita_harian > 0 THEN 1 END) as valid_count, COUNT(*) as total_groups')
            ->groupBy('id_komoditas')
            ->get();

        $kelengkapanStandarMap = [];
        $totalKomoditasKurangStandar = 0;
        foreach ($kelengkapanStandarRaw as $row) {
            $kelengkapanStandarMap[$row->id_komoditas] = [
                'valid' => (int) $row->valid_count,
                'total' => (int) $row->total_groups,
                'is_lengkap' => ((int) $row->valid_count > 0 && (int) $row->valid_count >= (int) $row->total_groups),
            ];
            if ((int) $row->valid_count < (int) $row->total_groups) {
                $totalKomoditasKurangStandar++;
            }
        }

        return view('perencanaan.neraca-komoditas.index', [
            'items' => $items,
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'idKomoditas' => $idKomoditas,
            'statusFilter' => $status,
            'daftarKomoditas' => $daftarKomoditas,
            'satuanMap' => $satuanMap,
            'totalSurplus' => $totalSurplus,
            'totalDefisit' => $totalDefisit,
            'totalSeimbang' => $totalSeimbang,
            'kelengkapanStandarMap' => $kelengkapanStandarMap,
            'totalKomoditasKurangStandar' => $totalKomoditasKurangStandar,
        ]);
    }

    private function onlyLatestVersion(Builder $query): Builder
    {
        return $query->whereRaw(
            'hasil_kalkulasi.versi = (SELECT MAX(h2.versi) FROM hasil_kalkulasi h2 WHERE h2.id_wilayah = hasil_kalkulasi.id_wilayah AND h2.id_komoditas = hasil_kalkulasi.id_komoditas AND h2.tahun = hasil_kalkulasi.tahun AND h2.bulan = hasil_kalkulasi.bulan)'
        );
    }

    public function show(int|string $id): View
    {
        $idWilayah = $this->wilayahAktifId();
        $hasil = HasilKalkulasi::with(['wilayah', 'komoditas', 'unitUsahaRekomendasi'])
            ->where('id_wilayah', $idWilayah)
            ->findOrFail($id);

        $rincianKebutuhan = KebutuhanKomoditas::with('standar')
            ->where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $hasil->id_komoditas)
            ->where('tahun', $hasil->tahun)
            ->where('bulan', $hasil->bulan)
            ->orderBy('kelompok_umur')
            ->get();

        $ketersediaan = KetersediaanKomoditas::where('id_wilayah', $idWilayah)
            ->where('id_komoditas', $hasil->id_komoditas)
            ->where('tahun', $hasil->tahun)
            ->where('bulan', $hasil->bulan)
            ->first();

        $satuan = $rincianKebutuhan->first()?->satuan ?? ($ketersediaan?->satuan ?? 'KG');

        $totalKelompok = $rincianKebutuhan->count();
        $kelompokValidStandar = $rincianKebutuhan->filter(fn ($rk) => $rk->id_standar !== null && (float) $rk->per_kapita_harian > 0)->count();
        $standarLengkap = ($totalKelompok > 0 && $kelompokValidStandar >= $totalKelompok);

        return view('perencanaan.neraca-komoditas.show', [
            'hasil' => $hasil,
            'rincianKebutuhan' => $rincianKebutuhan,
            'ketersediaan' => $ketersediaan,
            'satuan' => $satuan,
            'standarLengkap' => $standarLengkap,
            'kelompokValidStandar' => $kelompokValidStandar,
            'totalKelompok' => $totalKelompok,
            'title' => 'Detail Neraca: ' . ($hasil->komoditas?->nama ?? 'Komoditas'),
            'routeBase' => $this->routeBase,
        ]);
    }
}
