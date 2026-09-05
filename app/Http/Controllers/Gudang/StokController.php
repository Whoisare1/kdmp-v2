<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\Stok;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Laporan posisi stok on-hand + HPP moving average saat ini — read-only. */
class StokController extends ModuleCrudController
{
    protected string $model = Stok::class;
    protected string $view = 'gudang.stok';
    protected string $title = 'Posisi Stok';
    protected string $routeBase = 'gudang.stok';
    protected array $withRelations = ['gudang', 'barang'];

    public function index(Request $request): View
    {
        $query = Stok::query()->with(['gudang', 'barang.satuanDasar']);

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('barang', fn ($b) => $b->where('nama_barang', 'like', "%{$search}%"))
                    ->orWhereHas('gudang', fn ($g) => $g->where('nama_gudang', 'like', "%{$search}%"));
            });
        }

        // Stok punya composite primary key, jadi tidak bisa sort by ID standar
        $items = $query->orderBy('id_gudang')->orderBy('id_barang')->paginate(15)->withQueryString();

        return view('gudang.stok.index', [
            'items' => $items,
            'title' => $this->title,
        ]);
    }
}
