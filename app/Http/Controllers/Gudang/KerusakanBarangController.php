<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\AntreanJurnalGudang;
use App\Models\Gudang\KerusakanBarang;
use App\Models\Gudang\Stok;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Rules\TenantOwnershipRule;
use App\Services\StokBatchService;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class KerusakanBarangController extends ModuleCrudController
{
    public function __construct(private StokBatchService $stokBatch) {}

    protected string $model = KerusakanBarang::class;
    protected string $view = 'gudang.kerusakan';
    protected string $title = 'Kerusakan / Susut Barang';
    protected string $routeBase = 'gudang.kerusakan';
    protected array $withRelations = ['barang.satuanDasar'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $relations = ['barang', 'gudang', 'batch'];
        $query = KerusakanBarang::query()->with($relations);

        if ($search = $filters['q'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('jenis_kejadian', 'like', "%{$search}%")
                    ->orWhereHas('barang', fn ($b) => $b->where('nama_barang', 'like', "%{$search}%"));
            });
        }
        $query->when($filters['bulan'] ?? null, fn ($q, $bulan) => $q->whereMonth('tanggal', $bulan));
        $query->when($filters['tahun'] ?? null, fn ($q, $tahun) => $q->whereYear('tanggal', $tahun));

        $items = $query->latest('id_kerusakan')->paginate(15)->withQueryString();

        return view('gudang.kerusakan.index', [
            'items' => $items,
            'title' => $this->title,
            'filters' => $filters,
        ]);
    }

    public function show(int|string $id): View
    {
        $relations = ['barang.satuanDasar', 'gudang', 'jurnal.detail', 'batch', 'antreanJurnal'];

        return view('gudang.kerusakan.show', [
            'item' => KerusakanBarang::query()->with($relations)->findOrFail($id),
            'title' => 'Detail ' . $this->title,
        ]);
    }

    public function create(): View
    {
        $gudang = Gudang::query()->where('is_active', true)->orderBy('nama_gudang')->get();
        $barang = Barang::query()->where('is_active', true)->orderBy('nama_barang')->get();
        $batchTrackingAvailable = true;
        $entitasId = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);
        $pelacakanBarang = $entitasId !== null
            ? DB::table('barang_per_koperasi')
                ->where('id_entitas', $entitasId)
                ->pluck('lacak_batch', 'id_barang')
                ->map(fn ($value): bool => (bool) $value)
            : collect();
        $batchData = DB::table('stok_batch')
            ->whereIn('id_gudang', $gudang->pluck('id_gudang'))
            ->where('qty_on_hand', '>', 0)
            ->get([
                'id_batch',
                'id_gudang',
                'id_barang',
                'kode_batch',
                'tanggal_kedaluwarsa',
                'qty_on_hand',
            ]);

        return view('gudang.kerusakan.create', [
            'gudang' => $gudang,
            'barang' => $barang,
            'batchData' => $batchData,
            'pelacakanBarang' => $pelacakanBarang,
            'batchTrackingAvailable' => $batchTrackingAvailable,
            'title' => 'Tambah ' . $this->title,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'id_gudang' => ['required', 'integer', new TenantOwnershipRule('gudang', 'id_gudang')],
            'id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'tanggal' => ['required', 'date'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'jenis_kejadian' => ['required', 'in:rusak,susut,hilang,kadaluarsa'],
            'keterangan' => ['nullable', 'string'],
            'id_batch' => ['nullable', 'integer', 'exists:stok_batch,id_batch'],
        ];
        $data = $request->validate($rules);

        return DB::transaction(function () use ($data) {
            $entitasId = (int) (auth()->user()->id_entitas
                ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : 0));
            abort_if($entitasId <= 0, 403, 'Pilih entitas aktif sebelum mencatat kerusakan barang.');
            $lacakBatch = $this->stokBatch->pelacakanAktif($entitasId, (int) $data['id_barang']);
            if ($lacakBatch && empty($data['id_batch'])) {
                throw ValidationException::withMessages([
                    'id_batch' => 'Pilih batch yang akan dicatat untuk barang ini.',
                ]);
            }

            if ($lacakBatch) {
                $batch = DB::table('stok_batch')
                    ->where('id_batch', $data['id_batch'])
                    ->where('id_gudang', $data['id_gudang'])
                    ->where('id_barang', $data['id_barang'])
                    ->first();
                if (! $batch) {
                    throw ValidationException::withMessages([
                        'id_batch' => 'Batch tidak sesuai dengan gudang dan barang yang dipilih.',
                    ]);
                }

                $batchKedaluwarsa = $batch->tanggal_kedaluwarsa !== null
                    && $batch->tanggal_kedaluwarsa < now()->toDateString();
                if (
                    ($data['jenis_kejadian'] === 'kadaluarsa') !== $batchKedaluwarsa
                    && ($data['jenis_kejadian'] === 'kadaluarsa' || $batchKedaluwarsa)
                ) {
                    throw ValidationException::withMessages([
                        'id_batch' => $data['jenis_kejadian'] === 'kadaluarsa'
                            ? 'Batch belum kedaluwarsa. Catat sebagai kerusakan biasa atau pilih batch yang sudah kedaluwarsa.'
                            : 'Batch kedaluwarsa hanya dapat dikeluarkan melalui pencatatan kejadian kedaluwarsa.',
                    ]);
                }
            }

            $stok = Stok::query()
                ->where('id_gudang', $data['id_gudang'])
                ->where('id_barang', $data['id_barang'])
                ->lockForUpdate()
                ->first();

            $qtyTersedia = $stok
                ? bcsub((string) $stok->qty_on_hand, (string) $stok->qty_reserved, 4)
                : '0';
            if (! $stok || bccomp($qtyTersedia, (string) $data['qty'], 4) < 0) {
                throw ValidationException::withMessages([
                    'qty' => 'Stok tersedia setelah dikurangi jumlah yang dipesan tidak mencukupi untuk kerusakan barang.',
                ]);
            }

            $hpp = (string) $stok->hpp_rata2;
            $nilaiKerugian = bcmul((string) $data['qty'], $hpp, 2);

            $dataKerusakan = [
                'id_entitas' => app()->bound('entitas_aktif') ? app('entitas_aktif') : $entitasId,
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
                'id_batch' => $lacakBatch ? (int) $data['id_batch'] : null,
            ];
            $barang = Barang::query()->findOrFail((int) $data['id_barang']);
            if (! $barang->id_unit_usaha) {
                throw ValidationException::withMessages([
                    'id_barang' => 'Barang ini belum terhubung ke unit usaha, sehingga belum dapat dikirim ke antrean Keuangan.',
                ]);
            }

            $item = KerusakanBarang::query()->create($dataKerusakan);

            $service = app(StokService::class);
            try {
                $kartuStok = $service->keluar(
                    entitasId: (int) ($item->id_entitas ?? $entitasId),
                    gudangId: (int) $data['id_gudang'],
                    barangId: (int) $data['id_barang'],
                    qty: (string) $data['qty'],
                    refTipe: 'KERUSAKAN',
                    refId: (int) $item->id_kerusakan,
                    createdBy: auth()->id(),
                    idBatch: $lacakBatch ? (int) $data['id_batch'] : null,
                    izinkanBatchKedaluwarsa: $lacakBatch && $data['jenis_kejadian'] === 'kadaluarsa',
                );

            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([
                    $lacakBatch ? 'id_batch' : 'qty' => $exception->getMessage(),
                ]);
            }

            AntreanJurnalGudang::query()->create([
                'id_entitas' => (int) ($item->id_entitas ?? $entitasId),
                'jenis_transaksi' => 'KERUSAKAN',
                'ref_tipe' => 'KERUSAKAN',
                'ref_id' => (int) $item->id_kerusakan,
                'no_dokumen' => 'KRG-' . $item->id_kerusakan,
                'id_unit_usaha' => (int) $barang->id_unit_usaha,
                'tanggal' => $item->tanggal,
                'nilai' => $kartuStok->nilai_mutasi,
                'payload' => [
                    'id_gudang' => (int) $item->id_gudang,
                    'jenis_kejadian' => $item->jenis_kejadian,
                    'items' => [[
                        'id_barang' => (int) $kartuStok->id_barang,
                        'qty' => (string) $kartuStok->qty_keluar,
                        'hpp_satuan' => (string) $kartuStok->harga_satuan,
                        'nilai' => (string) $kartuStok->nilai_mutasi,
                        'id_kartu' => (int) $kartuStok->id_kartu,
                    ]],
                    'id_pembelian' => null,
                    'keterangan' => $item->keterangan,
                ],
            ]);

            return redirect()->route('gudang.kerusakan.index')->with('success', 'Data kerusakan, mutasi stok, dan antrean jurnal berhasil dicatat. Jurnal diproses oleh modul Keuangan.');
        });
    }
}
