<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\KartuStok;
use App\Models\Master\Barang;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Laporan mutasi stok, append-only — hanya index (read-only). */
class KartuStokController extends ModuleCrudController
{
    protected string $model = KartuStok::class;
    protected string $view = 'gudang.kartu-stok';
    protected string $title = 'Kartu Stok';
    protected string $routeBase = 'gudang.kartu-stok';
    protected array $withRelations = ['barang'];

    public function index(Request $request): View
    {
        $query = KartuStok::query()->with(['barang.satuanDasar']);

        if ($barang = $request->query('id_barang')) {
            $query->where('id_barang', $barang);
        }

        if ($jenis = $request->query('jenis_mutasi')) {
            $query->where('jenis_mutasi', $jenis);
        }

        $items = $query->orderByDesc('tanggal')->orderByDesc('id_kartu')->paginate(20)->withQueryString();

        return view('gudang.kartu-stok.index', [
            'items' => $items,
            'barang' => Barang::query()->where('is_active', true)->orderBy('nama_barang')->get(),
            'title' => $this->title,
        ]);
    }
}
