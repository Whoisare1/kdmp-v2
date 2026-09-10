<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\OpnameDetail;
use App\Models\Gudang\OpnameHeader;
use App\Models\Gudang\Stok;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OpnameController extends ModuleCrudController
{
    protected string $model = OpnameHeader::class;
    protected string $view = 'gudang.opname';
    protected string $title = 'Stock Opname';
    protected string $routeBase = 'gudang.opname';

    public function index(Request $request): View
    {
        $query = OpnameHeader::query()->with('detail');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_opname', 'like', "%{$search}%")
                    ->orWhereHas('detail', fn ($d) => $d->whereHas('barang', fn ($b) => $b->where('nama_barang', 'like', "%{$search}%")));
            });
        }

        $items = $query->latest('id_opname')->paginate(15)->withQueryString();

        return view('gudang.opname.index', [
            'items' => $items,
            'title' => $this->title,
        ]);
    }

    public function create(): View
    {
        $gudang = Gudang::query()->where('is_active', true)->orderBy('nama_gudang')->get();
        $barang = Barang::query()->where('is_active', true)->orderBy('nama_barang')->get();

        return view('gudang.opname.create', [
            'gudang' => $gudang,
            'barang' => $barang,
            'title' => 'Tambah ' . $this->title,
        ]);
    }

    public function show(int|string $id): View
    {
        $item = OpnameHeader::query()
            ->with(['gudang', 'detail.barang.satuanDasar'])
            ->findOrFail($id);

        return view('gudang.opname.show', [
            'item' => $item,
            'title' => 'Detail ' . $this->title,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_gudang' => ['required', 'integer', 'exists:gudang,id_gudang'],
            'kode_opname' => ['required', 'string', 'max:30'],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'items.*.qty_fisik' => ['required', 'numeric', 'gte:0'],
            'items.*.keterangan' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data) {
            $header = OpnameHeader::query()->create([
                'id_koperasi' => app()->bound('koperasi_aktif') ? app('koperasi_aktif') : null,
                'id_gudang' => $data['id_gudang'],
                'kode_opname' => $data['kode_opname'],
                'tanggal' => $data['tanggal'],
                'status' => 'dihitung',
                'approved_by' => null,
            ]);

            foreach ($data['items'] as $item) {
                $barangId = (int) $item['id_barang'];
                $qtySistem = Stok::query()
                    ->where('id_gudang', $data['id_gudang'])
                    ->where('id_barang', $barangId)
                    ->value('qty_on_hand') ?? '0';

                $qtyFisik = (string) $item['qty_fisik'];
                $selisih = bcsub($qtyFisik, (string) $qtySistem, 4);
                $hpp = Stok::query()
                    ->where('id_gudang', $data['id_gudang'])
                    ->where('id_barang', $barangId)
                    ->value('hpp_rata2') ?? '0';
                $nilaiSelisih = bcmul($selisih, $hpp, 2);

                OpnameDetail::query()->create([
                    'id_opname' => $header->id_opname,
                    'id_barang' => $barangId,
                    'qty_sistem' => $qtySistem,
                    'qty_fisik' => $qtyFisik,
                    'selisih' => $selisih,
                    'hpp_rata2' => $hpp,
                    'nilai_selisih' => $nilaiSelisih,
                    'keterangan' => $item['keterangan'] ?? null,
                ]);

                if ($selisih !== '0') {
                    Stok::query()
                        ->where('id_gudang', $data['id_gudang'])
                        ->where('id_barang', $barangId)
                        ->update([
                            'qty_on_hand' => bcadd((string) ($qtySistem), $selisih, 4),
                            'updated_at' => now(),
                        ]);
                }
            }

            return redirect()->route('gudang.opname.index')->with('success', 'Data opname berhasil disimpan.');
        });
    }
}
