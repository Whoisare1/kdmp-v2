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
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);

        $filters = $request->validate([
            'archive' => ['sometimes', 'in:1'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:9999'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
        ]);
        $purchases = $this->purchasesWithReturnQuantities();
        $pendingReturnRequests = $this->pendingReturnRequests($purchases);
        $query = ReturPembelian::when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))
            ->with([
                'pembelian.pihak',
                'pembelian.detail',
                'detail.barang.satuanDasar',
                'detail.barang.konversiSatuan.satuan',
            ]);

        foreach (['year' => 'tgl_retur', 'month' => 'tgl_retur', 'day' => 'tgl_retur'] as $filter => $column) {
            if (isset($filters[$filter])) {
                match ($filter) {
                    'year' => $query->whereYear($column, $filters[$filter]),
                    'month' => $query->whereMonth($column, $filters[$filter]),
                    'day' => $query->whereDay($column, $filters[$filter]),
                };
            }
        }

        $archiveActive = isset($filters['archive'])
            || isset($filters['year'])
            || isset($filters['month'])
            || isset($filters['day']);
        $yearlySummary = isset($filters['year']) && !isset($filters['month']) && !isset($filters['day'])
            ? (clone $query)->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(total_nilai), 0) as total_nilai')->first()
            : null;
        $returns = $query
            ->orderByDesc('tgl_retur')
            ->orderByDesc('id_retur')
            ->when($archiveActive, fn ($builder) => $builder->paginate(15)->withQueryString(), fn ($builder) => $builder->limit(5)->get());

        $returnCollection = $archiveActive ? $returns->getCollection() : $returns;
        $returnCollection->each(function (ReturPembelian $retur) {
            $retur->detail->each(function ($line) {
                $factor = $this->kilogramFactor($line->barang);
                $line->setAttribute('qty_kg', $factor ? (float) $line->qty_dasar / $factor : null);
            });
        });

        return view('pembelian.retur.dashboard', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'purchases' => $purchases,
            'pendingReturnRequests' => $pendingReturnRequests,
            'returns' => $returns,
            'archiveActive' => $archiveActive,
            'archiveFilters' => $filters,
            'yearlySummary' => $yearlySummary,
        ]);
    }

    public function create(): View
    {
        $purchases = $this->purchasesWithReturnQuantities()
            ->filter(fn (Pembelian $purchase) => $purchase->returItems->contains(fn ($line) => $line->qty_sisa_retur > 0))
            ->values();

        $pendingReturnRequests = $this->pendingReturnRequests($purchases);
        $selectedReturnRequest = null;
        $selectedPurchase = null;
        $selectedLine = null;

        if (request()->filled('permintaan_retur')) {
            $selectedReturnRequest = $pendingReturnRequests
                ->firstWhere('id_permintaan_retur', request('permintaan_retur'));
            if ($selectedReturnRequest) {
                $selectedPurchase = $selectedReturnRequest->pembelian;
                $selectedLine = $selectedReturnRequest->line;
            }
        } elseif (request()->filled('id_pembelian') && request()->filled('id_barang')) {
            $selectedPurchase = $purchases->firstWhere('id_pembelian', (int) request('id_pembelian'));
            if ($selectedPurchase) {
                $selectedLine = $selectedPurchase->returItems->firstWhere('id_barang', (int) request('id_barang'));
            }
        }

        if (! $selectedPurchase && $purchases->isNotEmpty()) {
            if ($pendingReturnRequests->isNotEmpty()) {
                $selectedReturnRequest = $pendingReturnRequests->first();
                $selectedPurchase = $selectedReturnRequest->pembelian;
                $selectedLine = $selectedReturnRequest->line;
            } else {
                $selectedPurchase = $purchases->first();
                $selectedLine = $selectedPurchase->returItems->first(fn ($l) => $l->qty_sisa_retur > 0);
            }
        }

        return view('pembelian.retur.create', [
            'title' => 'Ajukan Retur Pembelian',
            'routeBase' => $this->routeBase,
            'purchases' => $purchases,
            'pendingReturnRequests' => $pendingReturnRequests,
            'selectedReturnRequest' => $selectedReturnRequest,
            'selectedPurchase' => $selectedPurchase,
            'selectedLine' => $selectedLine,
            'pembelian' => $selectedPurchase,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_pembelian' => 'required|integer|exists:pembelian,id_pembelian',
            'id_barang' => 'required|integer|exists:master_barang,id_barang',
            'id_penerimaan' => 'nullable|integer|exists:penerimaan_barang,id_penerimaan',
            'qty_retur' => 'required|numeric|min:0.001',
            'jenis_penyelesaian' => 'required|in:uang,potong_hutang,ganti_barang',
            'alasan' => 'required|string|max:500',
        ]);

        try {
            $pembelian = Pembelian::whereIn('status', ['disetujui', 'diterima', 'selesai'])
                ->findOrFail($validated['id_pembelian']);

            $retur = PembelianService::createRetur(
                $pembelian,
                [[
                    'id_barang' => (int) $validated['id_barang'],
                    'qty_dasar' => (float) $validated['qty_retur'],
                ]],
                $validated['jenis_penyelesaian'],
                $validated['alasan'],
                $validated['id_penerimaan'] ?? null,
            );

            return redirect()->route('pembelian.retur.index')
                ->with('success', "Pengajuan retur {$retur->kode_retur} berhasil dibuat.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Permintaan retur riil dari penerimaan gudang yang mencatat barang rusak / tidak layak.
     *
     * @param  \Illuminate\Support\Collection<int, Pembelian>  $purchases
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function pendingReturnRequests($purchases)
    {
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);

        $unfitRows = \Illuminate\Support\Facades\DB::table('penerimaan_barang_detail as pbd')
            ->join('penerimaan_barang as pb', 'pb.id_penerimaan', '=', 'pbd.id_penerimaan')
            ->join('master_barang as b', 'b.id_barang', '=', 'pbd.id_barang')
            ->join('pembelian as p', 'p.id_pembelian', '=', 'pb.id_pembelian')
            ->when($idEntitas, fn ($q) => $q->where('pb.id_entitas', $idEntitas))
            ->where('pbd.qty_tidak_layak', '>', 0)
            ->whereNotNull('pb.id_pembelian')
            ->select([
                'pb.id_penerimaan',
                'pb.kode_penerimaan',
                'pb.tanggal_terima',
                'pb.id_pembelian',
                'p.kode_pembelian',
                'pbd.id_detail',
                'pbd.id_barang',
                'pbd.qty_tidak_layak',
                'pbd.alasan_tidak_layak',
                'b.nama_barang',
            ])
            ->get();

        if ($unfitRows->isEmpty()) {
            return collect();
        }

        $alreadyReturned = \Illuminate\Support\Facades\DB::table('retur_pembelian as rp')
            ->join('retur_pembelian_detail as rpd', 'rpd.id_retur', '=', 'rp.id_retur')
            ->whereNotNull('rp.id_penerimaan')
            ->select('rp.id_penerimaan', 'rpd.id_barang', \Illuminate\Support\Facades\DB::raw('SUM(rpd.qty_dasar) as total_diretur'))
            ->groupBy('rp.id_penerimaan', 'rpd.id_barang')
            ->get()
            ->keyBy(fn ($r) => "{$r->id_penerimaan}-{$r->id_barang}");

        return $unfitRows->map(function ($row) use ($purchases, $alreadyReturned) {
            $pembelian = $purchases->firstWhere('id_pembelian', (int) $row->id_pembelian);
            if (! $pembelian) {
                return null;
            }

            $line = $pembelian->returItems->firstWhere('id_barang', (int) $row->id_barang);
            if (! $line || $line->qty_sisa_retur <= 0) {
                return null;
            }

            $returnedKey = "{$row->id_penerimaan}-{$row->id_barang}";
            $sudahDiretur = (float) ($alreadyReturned->get($returnedKey)->total_diretur ?? 0);
            $sisaKebutuhanRetur = max(0, (float) $row->qty_tidak_layak - $sudahDiretur);

            if ($sisaKebutuhanRetur <= 0) {
                return null;
            }

            $qtyBisaDiretur = min($sisaKebutuhanRetur, (float) $line->qty_sisa_retur);

            return (object) [
                'id_permintaan_retur' => "penerimaan-{$row->id_penerimaan}-{$row->id_barang}",
                'id_penerimaan' => (int) $row->id_penerimaan,
                'kode_permintaan' => $row->kode_penerimaan,
                'pembelian' => $pembelian,
                'barang' => $line->barang,
                'line' => $line,
                'id_barang' => (int) $row->id_barang,
                'qty_diminta' => $qtyBisaDiretur,
                'alasan' => $row->alasan_tidak_layak ?: 'Barang rusak / tidak layak saat penerimaan di gudang.',
            ];
        })->filter()->values();
    }

    public function ajukanAkuntansi(ReturPembelian $retur): RedirectResponse
    {
        try {
            $processed = PembelianService::ajukanReturAkuntansi($retur, auth()->id());

            $message = $processed->id_jurnal
                ? "Jurnal retur {$processed->kode_retur} berhasil diposting."
                : ($processed->jenis_penyelesaian === 'ganti_barang'
                    ? "Retur {$processed->kode_retur} selesai diproses; ganti barang tidak membuat jurnal keuangan."
                    : "Retur {$processed->kode_retur} selesai diproses, tetapi jurnal belum terbentuk.");

            return redirect()->route('pembelian.retur.show', $processed)
                ->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(int|string $id): View
    {
        $retur = ReturPembelian::with([
            'pembelian.pihak',
            'pembelian.unitUsaha',
            'pembelian.gudang',
            'detail.barang.satuanDasar',
        ])->findOrFail($id);

        return view('pembelian.retur.show', [
            'title' => "Retur {$retur->kode_retur}",
            'routeBase' => $this->routeBase,
            'item' => $retur,
        ]);
    }

    public function invoice(ReturPembelian $retur): View
    {
        abort_unless(in_array($retur->status, ['disetujui', 'selesai'], true), 404);

        $retur->load([
            'pembelian.pihak',
            'pembelian.unitUsaha',
            'pembelian.gudang',
            'pembelian.detail',
            'detail.barang.satuanDasar',
            'detail.barang.konversiSatuan.satuan',
        ]);

        $retur->detail->each(function ($line) {
            $barang = $line->barang;
            $factor = $this->kilogramFactor($barang);

            $line->setAttribute('qty_kg', $factor ? (float) $line->qty_dasar / $factor : null);
            $line->setAttribute('hpp_per_kg', $factor ? (float) $line->hpp_rata2 * $factor : null);
        });

        return view('pembelian.retur.invoice', [
            'item' => $retur,
        ]);
    }

    private function purchasesWithReturnQuantities()
    {
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);

        $purchases = Pembelian::whereIn('status', ['disetujui', 'diterima', 'selesai'])
            ->when($idEntitas, fn ($q) => $q->where('id_entitas', $idEntitas))
            ->with(['pihak', 'detail.barang.satuanDasar', 'detail.barang.konversiSatuan.satuan', 'returs.detail'])
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

                    $factor = $this->kilogramFactor($detail->barang);

                    if (! $factor) {
                        return null;
                    }

                    $qtyDibeliKg = $qtyDibeli / $factor;
                    $detail->setAttribute('qty_dasar', $qtyDibeliKg);
                    $detail->setAttribute('qty_diajukan', $requested / $factor);
                    $detail->setAttribute('qty_diretur', $returned / $factor);
                    $detail->setAttribute('qty_sisa_retur', max(0, $qtyDibeli - $reserved) / $factor);
                    $detail->setAttribute(
                        'hpp_retur_dasar',
                        $qtyDibeliKg > 0 ? $nilaiBeli / $qtyDibeliKg : 0,
                    );
                    return $detail;
                })->filter()->values();

                $pembelian->setRelation('returItems', $returnItems);
            });
    }

    private function kilogramFactor($barang): ?float
    {
        if (! $barang) {
            return null;
        }

        if (strtoupper($barang->satuanDasar?->kode_satuan ?? '') === 'KG') {
            return 1.0;
        }

        $factor = $barang->konversiSatuan
            ->first(fn ($conversion) => strtoupper($conversion->satuan?->kode_satuan ?? '') === 'KG')
            ?->faktor_ke_dasar;

        return $factor !== null && (float) $factor > 0 ? (float) $factor : null;
    }
}
