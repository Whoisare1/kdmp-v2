<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Pembelian\ReturPembelian;
use App\Models\Pembelian\Pembelian;
use App\Services\PembelianService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReturPembelianController extends ModuleCrudController
{
    protected string $model = ReturPembelian::class;
    protected string $view = 'pembelian.retur';
    protected string $title = 'Retur Pembelian';
    protected string $routeBase = 'pembelian.retur';
    protected array $withRelations = ['pembelian'];

    public function index(Request $request): View
    {
        return view('pembelian.retur.dashboard', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'purchases' => $this->purchasesWithReturnQuantities(),
            'returns' => ReturPembelian::with(['pembelian.pihak', 'detail.barang.satuanDasar'])
                ->latest('id_retur')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $purchases = $this->purchasesWithReturnQuantities()
            ->filter(fn (Pembelian $purchase) => $purchase->returItems->contains(fn ($line) => $line->qty_sisa_retur > 0))
            ->values();

        return view('pembelian.retur.create', [
            'title' => 'Ajukan Retur Pembelian',
            'routeBase' => $this->routeBase,
            'purchases' => $purchases,
            'pembelian' => $purchases->firstWhere('id_pembelian', request()->integer('id_pembelian')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_pembelian' => 'required|exists:pembelian,id_pembelian',
            'jenis_penyelesaian' => 'required|in:uang,potong_hutang,ganti_barang',
            'alasan' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:master_barang,id_barang',
            'items.*.qty_dasar' => 'nullable|numeric|min:0',
            'items.*.hpp_rata2' => 'nullable|numeric|min:0',
        ]);

        try {
            $pembelian = Pembelian::whereIn('status', ['disetujui', 'diterima', 'selesai'])
                ->findOrFail($validated['id_pembelian']);
            $items = collect($validated['items'])
                ->filter(fn ($item) => (float) ($item['qty_dasar'] ?? 0) > 0)
                ->values()
                ->all();

            if ($items === []) {
                throw new \InvalidArgumentException('Isi jumlah minimal satu barang yang akan diretur.');
            }

            PembelianService::createRetur(
                $pembelian,
                $items,
                $validated['jenis_penyelesaian'],
                $validated['alasan'] ?? '',
            );

            return redirect()->route('pembelian.retur.index')
                ->with('success', 'Retur pembelian berhasil diajukan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function approve(ReturPembelian $retur): RedirectResponse
    {
        try {
            PembelianService::approveRetur($retur);

            return back()->with('success', 'Retur pembelian berhasil diproses.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function purchasesWithReturnQuantities()
    {
        $purchases = Pembelian::whereIn('status', ['disetujui', 'diterima', 'selesai'])
            ->with(['pihak', 'detail.barang.satuanDasar', 'returs.detail'])
            ->latest('id_pembelian')
            ->get();
        return $purchases->each(function (Pembelian $pembelian) {
                $requestedByBarang = $pembelian->returs
                    ->where('status', 'diajukan')
                    ->flatMap(fn ($retur) => $retur->detail)
                    ->groupBy('id_barang')
                    ->map(fn ($details) => (float) $details->sum('qty_dasar'));
                $returnedByBarang = $pembelian->returs
                    ->whereIn('status', ['disetujui', 'selesai'])
                    ->flatMap(fn ($retur) => $retur->detail)
                    ->groupBy('id_barang')
                    ->map(fn ($details) => (float) $details->sum('qty_dasar'));
                $reservedByBarang = $pembelian->returs
                    ->flatMap(fn ($retur) => $retur->detail)
                    ->groupBy('id_barang')
                    ->map(fn ($details) => (float) $details->sum('qty_dasar'));

                $returnItems = $pembelian->detail->groupBy('id_barang')->map(function ($lines) use ($requestedByBarang, $returnedByBarang, $reservedByBarang) {
                    $detail = $lines->first();
                    $qtyDibeli = (float) $lines->sum('qty_dasar');
                    $requested = (float) $requestedByBarang->get($detail->id_barang, 0);
                    $returned = (float) $returnedByBarang->get($detail->id_barang, 0);
                    $reserved = (float) $reservedByBarang->get($detail->id_barang, 0);
                    $nilaiBeli = $lines->sum(function ($line) {
                        $faktor = (float) $line->faktor_konversi;
                        $hargaDasar = $faktor > 0
                            ? (float) $line->harga_satuan_input / $faktor
                            : (float) $line->harga_satuan_input;

                        return (float) $line->qty_dasar * $hargaDasar;
                    });

                    $detail->setAttribute('qty_dasar', $qtyDibeli);
                    $detail->setAttribute('qty_diajukan', $requested);
                    $detail->setAttribute('qty_diretur', $returned);
                    $detail->setAttribute('qty_sisa_retur', max(0, $qtyDibeli - $reserved));
                    $detail->setAttribute(
                        'hpp_retur_dasar',
                        $qtyDibeli > 0 ? $nilaiBeli / $qtyDibeli : 0,
                    );
                    return $detail;
                })->values();

                $pembelian->setRelation('returItems', $returnItems);
            });
    }
}
