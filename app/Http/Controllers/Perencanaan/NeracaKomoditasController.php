<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\HasilKalkulasi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NeracaKomoditasController extends ModuleCrudController
{
    use ResolvesWilayahAktif;

    protected string $model = HasilKalkulasi::class;
    protected string $view = 'perencanaan.neraca-komoditas';
    protected string $title = 'Neraca Komoditas (Surplus / Defisit)';
    protected string $routeBase = 'perencanaan.neraca-komoditas';
    protected array $withRelations = ['wilayah', 'komoditas'];

    public function index(Request $request): View
    {
        $idWilayah = $this->wilayahAktifId();

        $tahun = (int) $request->query('tahun', 2026);
        $bulan = $request->filled('bulan') ? (int) $request->query('bulan') : null;
        $idKomoditas = $request->filled('id_komoditas') ? (int) $request->query('id_komoditas') : null;
        $status = $request->query('status_surplus_defisit');

        $query = HasilKalkulasi::with(['wilayah', 'komoditas'])
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun);

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

        // Ringkasan status neraca
        $baseQuery = HasilKalkulasi::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan));

        $totalSurplus = (clone $baseQuery)->where('status_surplus_defisit', 'surplus')->count();
        $totalDefisit = (clone $baseQuery)->where('status_surplus_defisit', 'defisit')->count();
        $totalSeimbang = (clone $baseQuery)->where('status_surplus_defisit', 'seimbang')->count();

        return view('perencanaan.neraca-komoditas.index', [
            'items' => $items,
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'idKomoditas' => $idKomoditas,
            'statusFilter' => $status,
            'daftarKomoditas' => $daftarKomoditas,
            'totalSurplus' => $totalSurplus,
            'totalDefisit' => $totalDefisit,
            'totalSeimbang' => $totalSeimbang,
        ]);
    }
}
