<?php

namespace App\Http\Controllers\Konsinyasi;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Konsinyasi\SetoranKonsinyasi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetoranKonsinyasiController extends ModuleCrudController
{
    use ResolvesEntitasAktif;

    protected string $model = SetoranKonsinyasi::class;
    protected string $view = 'konsinyasi.setoran';
    protected string $title = 'Setoran Hasil Konsinyasi';
    protected string $routeBase = 'konsinyasi.setoran';
    protected array $withRelations = ['koperasiPenyetor', 'koperasiPenerimaDana'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $items = $this->querySetoranEntitasAktif()
            ->when($filters['bulan'] ?? null, fn ($query, $bulan) => $query->whereMonth('tanggal', $bulan))
            ->when($filters['tahun'] ?? null, fn ($query, $tahun) => $query->whereYear('tanggal', $tahun))
            ->latest('id_setoran')
            ->paginate(15)
            ->withQueryString();

        return view('konsinyasi.setoran.index', [
            'items' => $items,
            'filters' => $filters,
            'title' => $this->title,
        ]);
    }

    public function show(int|string $id): View
    {
        $item = $this->querySetoranEntitasAktif()->findOrFail($id);

        return view('modules.placeholder', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'mode' => 'show',
            'item' => $item,
        ]);
    }

    public function edit(int|string $id): View
    {
        $item = $this->querySetoranEntitasAktif()->findOrFail($id);

        return view('modules.placeholder', [
            'title' => 'Ubah '.$this->title,
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'item' => $item,
        ]);
    }

    private function querySetoranEntitasAktif()
    {
        $entitasId = $this->requireEntitasAktifId();

        return SetoranKonsinyasi::query()
            ->with(['koperasiPenyetor', 'koperasiPenerimaDana'])
            ->where(function ($query) use ($entitasId): void {
                $query->where('id_entitas_penyetor', $entitasId)
                    ->orWhere('id_entitas_penerima_dana', $entitasId);
            });
    }
}
