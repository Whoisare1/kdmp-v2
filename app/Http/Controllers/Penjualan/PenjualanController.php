<?php

namespace App\Http\Controllers\Penjualan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Barang;
use App\Models\Master\Pihak;
use App\Models\Master\UnitUsaha;
use App\Models\Penjualan\Penjualan;
use App\Services\PenjualanService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PenjualanController extends ModuleCrudController
{
    protected string $model = Penjualan::class;
    protected string $view = 'penjualan.index';
    protected string $title = 'Penjualan';
    protected string $routeBase = 'penjualan.penjualan';
    protected array $withRelations = ['pihak', 'unitUsaha', 'gudang'];

    public function index(Request $request): View
    {
        $query = Penjualan::with(['pihak', 'unitUsaha', 'gudang']);

        if ($search = $request->query('q')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('kode_penjualan', 'like', "%{$search}%")
                    ->orWhereHas('pihak', fn ($pihak) => $pihak->where('nama', 'like', "%{$search}%"));
            });
        }

        return view('penjualan.index', [
            'title' => 'Kasir / POS',
            'routeBase' => $this->routeBase,
            'items' => $query->latest('id_penjualan')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');

        abort_unless($idEntitas, 403, 'Entitas aktif tidak ditemukan.');

        $products = Barang::query()
            ->join('barang_per_koperasi', function ($join) use ($idEntitas) {
                $join->on('barang_per_koperasi.id_barang', '=', 'master_barang.id_barang')
                    ->where('barang_per_koperasi.id_entitas', $idEntitas)
                    ->where('barang_per_koperasi.is_dijual', true);
            })
            ->where('master_barang.is_active', true)
            ->select('master_barang.*', 'barang_per_koperasi.harga_jual_standar')
            ->with(['satuanDasar', 'unitUsaha'])
            ->orderBy('master_barang.nama_barang')
            ->get();
        $products->each(fn ($product) => $product->setAttribute(
            'stok_demo',
            PenjualanService::availableDemoStock((int) $idEntitas, (int) $product->id_barang),
        ));

        return view('penjualan.pos', [
            'title' => 'Kasir / POS',
            'routeBase' => $this->routeBase,
            'products' => $products,
            'customers' => Pihak::where('jenis_pihak', 'warga')->where('is_active', true)->orderBy('nama')->get(),
            'units' => UnitUsaha::whereIn('id_unit_usaha', $products->pluck('id_unit_usaha')->unique())
                ->orderBy('nama_unit_usaha')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');

        abort_unless($idEntitas, 403, 'Entitas aktif tidak ditemukan.');

        $validated = $request->validate([
            'id_pihak' => ['required', 'integer', 'exists:master_pihak,id_pihak'],
            'id_unit_usaha' => ['required', 'integer', 'exists:master_unit_usaha,id_unit_usaha'],
            'metode_bayar' => ['required', 'in:tunai,transfer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer', 'distinct', 'exists:master_barang,id_barang'],
            'items.*.qty_dasar' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $sale = PenjualanService::createSale(
                (int) $idEntitas,
                (int) $validated['id_pihak'],
                (int) $validated['id_unit_usaha'],
                $validated['metode_bayar'],
                $validated['items'],
            );

            return redirect()->route("{$this->routeBase}.show", $sale)
                ->with('success', "Penjualan {$sale->kode_penjualan} berhasil dicatat.");
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function show(int|string $id): View
    {
        $sale = Penjualan::with(['pihak', 'unitUsaha', 'detail.barang.satuanDasar', 'returs.detail.barang.satuanDasar'])
            ->findOrFail($id);

        $returnedByBarang = $sale->returs
            ->flatMap(fn ($retur) => $retur->detail)
            ->groupBy('id_barang')
            ->map(fn ($details) => (float) $details->sum('qty_dasar'));

        $hasReturnableItems = $sale->status === 'selesai' && $sale->detail->contains(function ($line) use ($returnedByBarang) {
            $sold = (float) $line->qty_dasar;
            $returned = (float) $returnedByBarang->get($line->id_barang, 0);
            return ($sold - $returned) > 0.0001;
        });

        return view('penjualan.show', [
            'title' => "Penjualan {$sale->kode_penjualan}",
            'routeBase' => $this->routeBase,
            'item' => $sale,
            'hasReturnableItems' => $hasReturnableItems,
        ]);
    }
}
