<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\PenerimaanBarang;
use App\Models\Gudang\PenerimaanBarangDetail;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Master\Pihak;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PenerimaanBarangController extends ModuleCrudController
{
    protected string $model = PenerimaanBarang::class;
    protected string $view = 'gudang.penerimaan';
    protected string $title = 'Penerimaan Barang (GRN)';
    protected string $routeBase = 'gudang.penerimaan';
    protected array $withRelations = ['gudang', 'pihak'];

    public function index(Request $request): View
    {
        $query = PenerimaanBarang::query()->with(['gudang', 'pihak']);

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_penerimaan', 'like', "%{$search}%")
                    ->orWhereHas('gudang', fn ($g) => $g->where('nama_gudang', 'like', "%{$search}%"))
                    ->orWhereHas('pihak', fn ($p) => $p->where('nama', 'like', "%{$search}%"));
            });
        }

        $items = $query->latest('id_penerimaan')->paginate(15)->withQueryString();

        return view('gudang.penerimaan.index', [
            'items' => $items,
            'title' => $this->title,
            'routeBase' => $this->routeBase,
        ]);
    }

    public function create(): View
    {
        $gudang = Gudang::query()->where('is_active', true)->orderBy('nama_gudang')->get();
        $pihak = Pihak::query()->where('is_active', true)->orderBy('nama')->get();
        $barang = Barang::query()->where('is_active', true)->orderBy('nama_barang')->get();

        return view('gudang.penerimaan.create', [
            'gudang' => $gudang,
            'pihak' => $pihak,
            'barang' => $barang,
            'title' => 'Tambah ' . $this->title,
        ]);
    }

    public function show(int|string $id): View
    {
        $item = PenerimaanBarang::query()
            ->with(['gudang', 'pihak', 'detail.barang.satuanDasar'])
            ->findOrFail($id);

        return view('gudang.penerimaan.show', [
            'item' => $item,
            'title' => 'Detail ' . $this->title,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_gudang' => ['required', 'integer', 'exists:gudang,id_gudang'],
            'id_pihak' => ['required', 'integer', 'exists:master_pihak,id_pihak'],
            'kode_penerimaan' => ['required', 'string', 'max:30'],
            'tanggal_terima' => ['required', 'date'],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.harga_satuan' => ['required', 'numeric', 'gte:0'],
        ]);

        $kodePenerimaan = $data['kode_penerimaan'];

        return DB::transaction(function () use ($data, $kodePenerimaan) {
            // Insert header penerimaan
            $header = PenerimaanBarang::query()->create([
                'id_koperasi' => app()->bound('koperasi_aktif') ? app('koperasi_aktif') : null,
                'id_gudang' => $data['id_gudang'],
                'kode_penerimaan' => $kodePenerimaan,
                'id_pembelian' => null,
                'id_pihak' => $data['id_pihak'],
                'tanggal_terima' => $data['tanggal_terima'],
                'status' => 'diposting',
                'catatan' => $data['catatan'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $service = new StokService();

            // Loop setiap item dan insert detail + update stok
            foreach ($data['items'] as $item) {
                $qty = (string) $item['qty'];
                $hargaSatuan = (string) $item['harga_satuan'];
                $subtotal = bcmul($qty, $hargaSatuan, 2);

                // Insert detail
                PenerimaanBarangDetail::query()->create([
                    'id_penerimaan' => $header->id_penerimaan,
                    'id_barang' => $item['id_barang'],
                    'id_satuan_input' => 1, // Default ke satuan dasar untuk sekarang
                    'qty_input' => $qty,
                    'faktor_konversi' => '1',
                    'qty_dasar' => $qty,
                    'qty_layak' => $qty,
                    'qty_tidak_layak' => '0',
                    'harga_satuan_dasar' => $hargaSatuan,
                    'subtotal' => $subtotal,
                    'alasan_tidak_layak' => null,
                    'foto_bukti' => null,
                ]);

                // Update stok untuk item ini
                $service->masuk(
                    koperasiId: (int) ($header->id_koperasi ?? app('koperasi_aktif')),
                    gudangId: (int) $data['id_gudang'],
                    barangId: (int) $item['id_barang'],
                    qty: $qty,
                    hargaSatuan: $hargaSatuan,
                    refTipe: 'PENERIMAAN',
                    refId: (int) $header->id_penerimaan,
                    createdBy: auth()->id(),
                );
            }

            return redirect()->route('gudang.penerimaan.index')->with('success', 'Penerimaan barang berhasil disimpan.');
        });
    }
}
