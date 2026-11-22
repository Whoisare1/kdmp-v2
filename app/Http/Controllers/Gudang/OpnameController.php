<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\AntreanJurnalGudang;
use App\Models\Gudang\KartuStok;
use App\Models\Gudang\OpnameDetail;
use App\Models\Gudang\OpnameHeader;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Rules\TenantOwnershipRule;
use App\Services\StokBatchService;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class OpnameController extends ModuleCrudController
{
    protected string $model = OpnameHeader::class;
    protected string $view = 'gudang.opname';
    protected string $title = 'Stock Opname';
    protected string $routeBase = 'gudang.opname';

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $query = OpnameHeader::query()->with(['gudang', 'approver', 'detail']);

        if ($search = $filters['q'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_opname', 'like', "%{$search}%")
                    ->orWhereHas('detail', fn ($d) => $d->whereHas('barang', fn ($b) => $b->where('nama_barang', 'like', "%{$search}%")));
            });
        }
        $query->when($filters['bulan'] ?? null, fn ($q, $bulan) => $q->whereMonth('tanggal', $bulan));
        $query->when($filters['tahun'] ?? null, fn ($q, $tahun) => $q->whereYear('tanggal', $tahun));

        $items = $query->latest('id_opname')->paginate(15)->withQueryString();

        return view('gudang.opname.index', [
            'items' => $items,
            'title' => $this->title,
            'filters' => $filters,
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
            ->with(['gudang', 'approver', 'detail.barang.satuanDasar', 'antreanJurnal.unitUsaha'])
            ->findOrFail($id);

        return view('gudang.opname.show', [
            'item' => $item,
            'title' => 'Detail ' . $this->title,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_gudang' => ['required', 'integer', new TenantOwnershipRule('gudang', 'id_gudang')],
            'kode_opname' => ['required', 'string', 'max:30'],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer', 'distinct', 'exists:master_barang,id_barang'],
            'items.*.qty_fisik' => ['required', 'numeric', 'regex:/^\d{1,14}(?:\.\d{1,4})?$/', 'gte:0'],
            'items.*.keterangan' => ['nullable', 'string'],
        ]);

        $entitasId = (int) (auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : 0));
        abort_if($entitasId <= 0, 403, 'Pilih entitas aktif sebelum membuat stok opname.');

        $header = DB::transaction(function () use ($data, $entitasId) {
            $header = OpnameHeader::query()->create([
                'id_entitas' => $entitasId,
                'id_gudang' => $data['id_gudang'],
                'kode_opname' => $data['kode_opname'],
                'tanggal' => $data['tanggal'],
                'status' => 'draft',
                'approved_by' => null,
            ]);

            foreach ($data['items'] as $item) {
                $barangId = (int) $item['id_barang'];
                $qtyFisik = (string) $item['qty_fisik'];

                // Ambil stok sistem dan HPP saat ini untuk menghitung selisih sementara
                $stok = DB::table('stok')
                    ->where('id_gudang', $data['id_gudang'])
                    ->where('id_barang', $barangId)
                    ->first();

                $qtySistem = (string) ($stok?->qty_on_hand ?? '0');
                $hpp = (string) ($stok?->hpp_rata2 ?? '0');
                $selisih = bcsub($qtyFisik, $qtySistem, 4);
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
            }

            return $header;
        });

        return redirect()->route('gudang.opname.show', $header->id_opname)
            ->with('success', "Draft opname {$header->kode_opname} berhasil disimpan. Silakan tinjau selisih stok sebelum disetujui.");
    }

    public function approve(int|string $id): RedirectResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $header = OpnameHeader::query()
                    ->with('detail.barang')
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($header->status === 'diposting') {
                    return redirect()->route('gudang.opname.show', $id)
                        ->with('warning', 'Opname ini sudah disetujui dan diposting sebelumnya.');
                }

                $stokService = app(StokService::class);
                $stokBatchService = app(StokBatchService::class);
                $entitasId = (int) ($header->id_entitas ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : auth()->user()->id_entitas));
                $antreanPerUnitDanArah = [];

                foreach ($header->detail as $detail) {
                    $barangId = (int) $detail->id_barang;
                    $qtyFisik = (string) $detail->qty_fisik;

                    $hasilOpname = $stokService->sesuaikanOpname(
                        entitasId: $entitasId,
                        gudangId: (int) $header->id_gudang,
                        barangId: $barangId,
                        qtyFisik: $qtyFisik,
                        opnameId: (int) $header->id_opname,
                        createdBy: auth()->id(),
                    );

                    $detail->update([
                        'qty_sistem' => $hasilOpname['qty_sistem'],
                        'selisih' => $hasilOpname['selisih'],
                        'hpp_rata2' => $hasilOpname['hpp_rata2'],
                        'nilai_selisih' => $hasilOpname['nilai_selisih'],
                    ]);

                    $stokBatchService->catatPenyesuaianOpname(
                        detailId: (int) $detail->id_detail,
                        gudangId: (int) $header->id_gudang,
                        barangId: $barangId,
                        qtySistem: $hasilOpname['qty_sistem'],
                        qtyFisik: $qtyFisik,
                        idKartu: $hasilOpname['id_kartu'],
                    );

                    if ($hasilOpname['id_kartu'] === null) {
                        continue;
                    }

                    $idUnitUsaha = (int) ($detail->barang?->id_unit_usaha ?? 0);
                    if ($idUnitUsaha <= 0) {
                        throw new RuntimeException(
                            "Barang {$detail->barang?->nama_barang} belum terhubung ke unit usaha. Opname dibatalkan dan stok tidak diubah."
                        );
                    }

                    $kartuStok = KartuStok::query()
                        ->where('id_entitas', $entitasId)
                        ->findOrFail((int) $hasilOpname['id_kartu']);
                    $jenisTransaksi = bccomp((string) $hasilOpname['selisih'], '0', 4) < 0
                        ? 'OPNAME_KURANG'
                        : 'OPNAME_LEBIH';
                    $key = $idUnitUsaha . ':' . $jenisTransaksi;

                    if (! isset($antreanPerUnitDanArah[$key])) {
                        $antreanPerUnitDanArah[$key] = [
                            'id_unit_usaha' => $idUnitUsaha,
                            'jenis_transaksi' => $jenisTransaksi,
                            'nilai' => '0.00',
                            'items' => [],
                        ];
                    }

                    $qtyMutasi = $jenisTransaksi === 'OPNAME_KURANG'
                        ? (string) $kartuStok->qty_keluar
                        : (string) $kartuStok->qty_masuk;
                    $antreanPerUnitDanArah[$key]['nilai'] = bcadd(
                        $antreanPerUnitDanArah[$key]['nilai'],
                        (string) $kartuStok->nilai_mutasi,
                        2,
                    );
                    $antreanPerUnitDanArah[$key]['items'][] = [
                        'id_barang' => (int) $kartuStok->id_barang,
                        'qty' => $qtyMutasi,
                        'hpp_satuan' => (string) $kartuStok->harga_satuan,
                        'nilai' => (string) $kartuStok->nilai_mutasi,
                        'id_kartu' => (int) $kartuStok->id_kartu,
                    ];
                }

                foreach ($antreanPerUnitDanArah as $antrean) {
                    AntreanJurnalGudang::query()->create([
                        'id_entitas' => $entitasId,
                        'jenis_transaksi' => $antrean['jenis_transaksi'],
                        'ref_tipe' => 'OPNAME',
                        'ref_id' => (int) $header->id_opname,
                        'no_dokumen' => $header->kode_opname,
                        'id_unit_usaha' => $antrean['id_unit_usaha'],
                        'tanggal' => $header->tanggal,
                        'nilai' => $antrean['nilai'],
                        'payload' => [
                            'id_gudang' => (int) $header->id_gudang,
                            'items' => $antrean['items'],
                        ],
                        'status' => 'MENUNGGU',
                        'created_by' => auth()->id(),
                    ]);
                }

                $header->update([
                    'status' => 'diposting',
                    'approved_by' => auth()->id(),
                ]);

                return redirect()->route('gudang.opname.show', $header->id_opname)
                    ->with('success', "Opname {$header->kode_opname} berhasil disetujui, stok disesuaikan, dan antrean jurnal dibuat.");
            });
        } catch (RuntimeException $exception) {
            return redirect()->route('gudang.opname.show', $id)
                ->with('error', $exception->getMessage());
        }
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $header = OpnameHeader::findOrFail($id);

        if ($header->status === 'diposting') {
            return redirect()->route('gudang.opname.show', $id)
                ->with('error', 'Opname yang sudah diposting tidak dapat dihapus.');
        }

        DB::transaction(function () use ($header) {
            $header->detail()->delete();
            $header->delete();
        });

        return redirect()->route('gudang.opname.index')
            ->with('success', 'Draft opname berhasil dihapus.');
    }
}
