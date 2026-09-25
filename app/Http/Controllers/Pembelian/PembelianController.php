<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Pembelian\Pembelian;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Master\Barang;
use App\Models\Master\Pihak;
use App\Models\Master\Satuan;
use App\Models\Master\UnitUsaha;
use App\Models\Master\KasBank;
use App\Services\PembelianService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PembelianController extends ModuleCrudController
{
    protected string $model = Pembelian::class;
    protected string $view = 'pembelian.index';
    protected string $title = 'Pembelian';
    protected string $routeBase = 'pembelian.pembelian';
    protected array $withRelations = ['pihak', 'unitUsaha', 'gudang', 'detail.barang.satuanDasar', 'detail.satuanInput'];

    public function index(Request $request): View
    {
        $query = Pembelian::with(['pihak', 'unitUsaha', 'gudang']);

        if ($search = $request->query('q')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('kode_pembelian', 'like', "%{$search}%")
                    ->orWhereHas('pihak', fn ($pihak) => $pihak->where('nama', 'like', "%{$search}%"));
            });
        }

        return view('pembelian.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $query->latest('id_pembelian')->paginate(15)->withQueryString(),
        ]);
    }

    /**
     * Tampilkan form create pembelian: pilih PR atau buat quick purchase
     */
    public function create(): View
    {
        $prs = PermintaanPengadaan::where('status', 'disetujui')->get();
        $pihaks = Pihak::all();
        $kasbanks = KasBank::all();
        $barangs = Barang::where('is_active', true)->orderBy('nama_barang')->get();
        $satuans = Satuan::where('is_active', true)->orderBy('kode_satuan')->get();

        return view('pembelian.create', [
            'title' => 'Tambah Pembelian',
            'routeBase' => $this->routeBase,
            'prs' => $prs,
            'pihaks' => $pihaks,
            'kasbanks' => $kasbanks,
            'barangs' => $barangs,
            'satuans' => $satuans,
        ]);
    }

    /**
     * Store: Create from PR atau Quick Purchase
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $koperasiId = auth()->user()->id_koperasi ?? app('koperasi_aktif');

            $validated = $request->validate([
                'source' => 'required|in:from_pr,quick_purchase',
                'id_permintaan' => [
                    'exclude_unless:source,from_pr',
                    'required',
                    Rule::exists('permintaan_pengadaan', 'id_permintaan')
                        ->where('id_koperasi', $koperasiId)
                        ->where('status', 'disetujui'),
                ],
                'id_pihak' => 'required|exists:master_pihak,id_pihak',
                'jenis_pembayaran' => 'required|in:tunai,transfer,kredit',
                'id_kas_bank' => [
                    'required_if:jenis_pembayaran,tunai,transfer',
                    'exists:master_kas_bank,id_kas_bank',
                    function ($attribute, $value, $fail) use ($koperasiId) {
                        if (! KasBank::where('id_kas_bank', $value)->where('id_koperasi', $koperasiId)->exists()) {
                            $fail('Kas/Bank tidak tersedia untuk koperasi aktif.');
                        }
                    },
                ],
                'tgl_jatuh_tempo' => [
                    'nullable',
                    'date_format:Y-m-d',
                    Rule::requiredIf($request->input('jenis_pembayaran') === 'kredit'),
                ],
                'id_unit_usaha' => ['exclude_unless:source,quick_purchase', 'required', 'exists:master_unit_usaha,id_unit_usaha'],
                'id_gudang' => ['exclude_unless:source,quick_purchase', 'required', 'exists:gudang,id_gudang'],
                'items' => ['exclude_unless:source,quick_purchase', 'required', 'array', 'min:1'],
                'items.*.id_barang' => ['required', 'exists:master_barang,id_barang'],
                'items.*.id_satuan' => ['required', 'exists:satuan,id'],
                'items.*.qty_dasar' => ['required', 'numeric', 'min:0.01'],
                'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            ]);

            if ($validated['source'] === 'from_pr') {
                $pr = PermintaanPengadaan::findOrFail($validated['id_permintaan']);
                $pembelian = PembelianService::createFromPR(
                    $pr,
                    $validated['id_pihak'],
                    $validated['jenis_pembayaran'],
                    $validated['id_kas_bank'] ?? null,
                    $validated['tgl_jatuh_tempo'] ?? null,
                );
            } else {
                $items = collect($validated['items'] ?? [])
                    ->map(function ($item) {
                        return [
                            'id_barang' => (int) $item['id_barang'],
                            'id_satuan' => (int) $item['id_satuan'],
                            'qty_dasar' => (float) $item['qty_dasar'],
                            'harga_satuan' => (float) $item['harga_satuan'],
                        ];
                    })
                    ->values()
                    ->all();

                $pembelian = PembelianService::createQuickPurchase(
                    $koperasiId,
                    (int) $validated['id_pihak'],
                    (int) $validated['id_unit_usaha'],
                    (int) $validated['id_gudang'],
                    $items,
                    $validated['jenis_pembayaran'],
                    $validated['id_kas_bank'] ?? null,
                );

                PembelianService::approvePembelian($pembelian);
                PembelianService::createGRN(
                    $pembelian->fresh('detail'),
                    $pembelian->detail->map(fn ($detail) => [
                        'id_detail' => $detail->id_detail,
                        'qty_layak' => $detail->qty_dasar,
                        'qty_tidak_layak' => 0,
                        'harga_satuan' => $detail->faktor_konversi > 0
                            ? $detail->harga_satuan_input / $detail->faktor_konversi
                            : $detail->harga_satuan_input,
                    ])->all(),
                );
            }

            return redirect()->route("{$this->routeBase}.index")
                ->with('success', "Pembelian {$pembelian->kode_pembelian} berhasil ditambahkan.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Tampilkan detail pembelian
     */
    public function show(int|string $id): View
    {
        $pembelian = Pembelian::with($this->withRelations)->findOrFail($id);
        $pihaks = Pihak::all();
        $kasbanks = KasBank::all();

        return view('pembelian.show', [
            'title' => "Pembelian {$pembelian->kode_pembelian}",
            'routeBase' => $this->routeBase,
            'item' => $pembelian,
            'pihaks' => $pihaks,
            'kasbanks' => $kasbanks,
        ]);
    }

    /**
     * Approve pembelian
     */
    public function approve(Pembelian $pembelian): RedirectResponse
    {
        try {
            PembelianService::approvePembelian($pembelian);

            return back()->with('success', "Pembelian disetujui.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Pembelian $pembelian): RedirectResponse
    {
        try {
            PembelianService::cancelPurchase($pembelian);

            return redirect()->route("{$this->routeBase}.index")
                ->with('success', "Pembelian {$pembelian->kode_pembelian} dibatalkan.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tampilkan form GRN (penerimaan barang)
     */
    public function createGrn(Pembelian $pembelian): View
    {
        if ($pembelian->status !== 'disetujui') {
            abort(403, 'Pembelian harus disetujui terlebih dahulu.');
        }

        return view('pembelian.grn', [
            'title' => "GRN untuk {$pembelian->kode_pembelian}",
            'routeBase' => $this->routeBase,
            'pembelian' => $pembelian->load('detail.barang'),
        ]);
    }

    /**
     * Store GRN
     */
    public function storeGrn(Request $request, Pembelian $pembelian): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.id_detail' => 'required|integer',
                'items.*.qty_layak' => 'required|numeric|min:0',
                'items.*.qty_tidak_layak' => 'nullable|numeric|min:0',
                'items.*.harga_satuan' => 'required|numeric|min:0',
            ]);

            if (count($validated['items']) !== count(array_unique(array_column($validated['items'], 'id_detail')))) {
                throw new \Exception('Detail pembelian tidak boleh dikirim lebih dari satu kali.');
            }

            $penerimaan = PembelianService::createGRN($pembelian, $validated['items']);

            return redirect()->route("{$this->routeBase}.show", $pembelian->id_pembelian)
                ->with('success', "GRN {$penerimaan->kode_penerimaan} dibuat dan diposting.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Tampilkan retur pembelian
     */
    public function showRetur(Pembelian $pembelian): View
    {
        $returs = $pembelian->returs()->with('detail')->get();

        return view('pembelian.retur.index', [
            'title' => "Retur untuk {$pembelian->kode_pembelian}",
            'routeBase' => $this->routeBase,
            'pembelian' => $pembelian,
            'returs' => $returs,
        ]);
    }
}
