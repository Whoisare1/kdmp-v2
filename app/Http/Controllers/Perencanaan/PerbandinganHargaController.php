<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\PerbandinganHarga;
use App\Services\Perencanaan\PerbandinganHargaService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\Perencanaan\PeriodeKalkulasi;

class PerbandinganHargaController extends ModuleCrudController
{
    protected string $model = PerbandinganHarga::class;
    protected string $view = 'perencanaan.perbandingan-harga';
    protected string $title = 'Perbandingan Harga';
    protected string $routeBase = 'perencanaan.perbandingan-harga';
    protected array $withRelations = ['komoditas', 'wilayahSumber'];

    public function index(Request $request): View
    {
        $user = auth()->user();
        $service = app(PerbandinganHargaService::class);
        $selected = $request->integer('id_komoditas');
        $tahun = $request->integer('tahun', now()->year);
        $periode = $request->query('periode', PeriodeKalkulasi::fromMonth(now()->month));
        $comparison = $selected > 0
            ? $service->bandingkan((int) $user->id_entitas, $selected, $tahun, $periode)
            : ['items' => collect(), 'lowest' => null, 'highest' => null, 'difference' => null, 'satuan' => null];

        return view('perencanaan.perbandingan-harga.index', [
            'title' => $this->title,
            'komoditas' => Komoditas::query()->orderBy('nama')->get(),
            'selected' => $selected,
            'tahun' => $tahun,
            'periode' => $periode,
            ...$comparison,
        ]);
    }
}
