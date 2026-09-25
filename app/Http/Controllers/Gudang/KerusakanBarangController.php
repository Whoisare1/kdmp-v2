<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\KerusakanBarang;
use App\Models\Gudang\Stok;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KerusakanBarangController extends ModuleCrudController
{
    protected string $model = KerusakanBarang::class;
    protected string $view = 'gudang.kerusakan';
    protected string $title = 'Kerusakan / Susut Barang';
    protected string $routeBase = 'gudang.kerusakan';
    protected array $withRelations = ['barang.satuanDasar'];

    public function index(Request $request): View
    {
        $query = KerusakanBarang::query()->with(['barang', 'gudang']);

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('jenis_kejadian', 'like', "%{$search}%")
                    ->orWhereHas('barang', fn ($b) => $b->where('nama_barang', 'like', "%{$search}%"));
            });
        }

        $items = $query->latest('id_kerusakan')->paginate(15)->withQueryString();

        return view('gudang.kerusakan.index', [
            'items' => $items,
            'title' => $this->title,
        ]);
    }

    public function create(): View
    {
        $gudang = Gudang::query()->where('is_active', true)->orderBy('nama_gudang')->get();
        $barang = Barang::query()->where('is_active', true)->orderBy('nama_barang')->get();

        return view('gudang.kerusakan.create', [
            'gudang' => $gudang,
            'barang' => $barang,
            'title' => 'Tambah ' . $this->title,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_gudang' => ['required', 'integer', 'exists:gudang,id_gudang'],
            'id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'tanggal' => ['required', 'date'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'jenis_kejadian' => ['required', 'in:rusak,susut,hilang,kadaluarsa'],
            'keterangan' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data) {
            $stok = Stok::query()
                ->where('id_gudang', $data['id_gudang'])
                ->where('id_barang', $data['id_barang'])
                ->first();

            if (! $stok || bccomp((string) $stok->qty_on_hand, (string) $data['qty'], 4) < 0) {
                throw new \RuntimeException('Stok tidak mencukupi untuk kerusakan barang.');
            }

            $hpp = (string) $stok->hpp_rata2;
            $nilaiKerugian = bcmul((string) $data['qty'], $hpp, 2);

            $item = KerusakanBarang::query()->create([
                'id_koperasi' => app()->bound('koperasi_aktif') ? app('koperasi_aktif') : null,
                'id_gudang' => $data['id_gudang'],
                'id_barang' => $data['id_barang'],
                'tanggal' => $data['tanggal'],
                'qty' => (string) $data['qty'],
                'hpp_rata2' => $hpp,
                'nilai_kerugian' => $nilaiKerugian,
                'jenis_kejadian' => $data['jenis_kejadian'],
                    'keterangan' => $data['keterangan'] ?? null,
                'foto_bukti' => null,
                'status' => 'diposting',
                'approved_by' => auth()->id(),
            ]);

            $service = new StokService();
            $service->keluar(
                koperasiId: (int) ($item->id_koperasi ?? app('koperasi_aktif')),
                gudangId: (int) $data['id_gudang'],
                barangId: (int) $data['id_barang'],
                qty: (string) $data['qty'],
                refTipe: 'KERUSAKAN',
                refId: (int) $item->id_kerusakan,
                createdBy: auth()->id(),
            );

            return redirect()->route('gudang.kerusakan.index')->with('success', 'Data kerusakan barang berhasil disimpan.');
        });
    }

    public function show(int|string $id): View
    {
        $item = KerusakanBarang::query()
            ->with(['gudang', 'barang.satuanDasar'])
            ->findOrFail($id);

        return view('gudang.kerusakan.show', [
            'item' => $item,
            'title' => 'Detail ' . $this->title,
        ]);
    }
}
