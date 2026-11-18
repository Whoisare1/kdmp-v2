<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Models\Gudang\AntreanJurnalGudang;
use App\Models\Gudang\PenerimaanBarang;
use App\Models\Gudang\PenerimaanBarangDetail;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Master\KonversiSatuan;
use App\Models\Master\Pihak;
use App\Models\Master\Satuan;
use App\Models\Pembelian\Pembelian;
use App\Rules\TenantOwnershipRule;
use App\Services\StokService;
use App\Services\StokBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenerimaanBarangController extends ModuleCrudController
{
    use ResolvesEntitasAktif;

    public function __construct(private StokBatchService $stokBatch) {}

    protected string $model = PenerimaanBarang::class;

    protected string $view = 'gudang.penerimaan';

    protected string $title = 'Penerimaan Barang (GRN)';

    protected string $routeBase = 'gudang.penerimaan';

    protected array $withRelations = ['gudang', 'pihak'];

    public function index(Request $request): View
    {
        $entitasId = $this->entitasAktifId();
        $query = PenerimaanBarang::query()
            ->when($entitasId, fn ($q) => $q->where('id_entitas', $entitasId))
            ->with(['gudang', 'pihak']);

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
        $entitasId = $this->entitasAktifId();
        $batchTrackingAvailable = Schema::hasColumn('barang_per_koperasi', 'lacak_batch');
        $pelacakanBarang = $batchTrackingAvailable && $entitasId !== null
            ? DB::table('barang_per_koperasi')
                ->where('id_entitas', $entitasId)
                ->pluck('lacak_batch', 'id_barang')
            : collect();

        $gudang = Gudang::query()
            ->when($entitasId, fn ($q) => $q->where('id_entitas', $entitasId))
            ->where('is_active', true)
            ->orderBy('nama_gudang')
            ->get();
        $pihak = Pihak::query()
            ->when($entitasId, fn ($q) => $q->where(fn ($sub) => $sub->where('id_entitas', $entitasId)->orWhereNull('id_entitas')))
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();
        $barang = Barang::query()
            ->with(['satuanDasar', 'konversiSatuan.satuan'])
            ->forEntitas($entitasId)
            ->where('is_active', true)
            ->orderBy('nama_barang')
            ->get();
        $satuan = Satuan::query()
            ->when($entitasId, fn ($q) => $q->forEntitas($entitasId))
            ->where('is_active', true)
            ->orderBy('kode_satuan')
            ->get(['id', 'kode_satuan']);

        $pembelianList = Pembelian::query()
            ->when($entitasId, fn ($q) => $q->where('id_entitas', $entitasId))
            ->with(['pihak', 'gudang', 'detail'])
            ->where('status', 'disetujui')
            ->orderByDesc('id_pembelian')
            ->get();

        return view('gudang.penerimaan.create', [
            'gudang' => $gudang,
            'pihak' => $pihak,
            'pembelianData' => $pembelianList->map(fn (Pembelian $pb) => [
                'id' => $pb->id_pembelian,
                'kode' => $pb->kode_pembelian,
                'tanggal' => $pb->tanggal_transaksi?->format('Y-m-d'),
                'idGudang' => $pb->id_gudang,
                'idPihak' => $pb->id_pihak,
                'pihakNama' => $pb->pihak?->nama ?? '-',
                'total' => (float) $pb->total_pembelian,
                'items' => $pb->detail->map(fn ($detail) => [
                    'id_barang' => $detail->id_barang,
                    'id_satuan_input' => $detail->id_satuan_input,
                    'qty' => (float) $detail->qty_input,
                    'harga_satuan' => (float) $detail->harga_satuan_input,
                ])->values(),
            ])->values(),
            'barangData' => $barang->map(fn (Barang $item) => [
                'id' => $item->id_barang,
                'kode' => $item->kode_barang,
                'nama' => $item->nama_barang,
                'satuanDasarId' => $item->id_satuan_dasar,
                'satuanDasar' => $item->satuanDasar?->kode_satuan ?? '-',
                'lacakBatch' => (bool) ($pelacakanBarang[$item->id_barang] ?? false),
                'konversi' => $item->konversiSatuan
                    ->filter(fn (KonversiSatuan $konversi) => $konversi->satuan?->is_active)
                    ->map(fn (KonversiSatuan $konversi) => [
                        'idSatuan' => $konversi->id_satuan,
                        'faktor' => (string) $konversi->faktor_ke_dasar,
                        'defaultBeli' => (bool) $konversi->is_default_beli,
                    ])
                    ->values(),
            ])->values(),
            'satuanData' => $satuan->map(fn (Satuan $item) => [
                'id' => $item->id,
                'kode' => $item->kode_satuan,
            ])->values(),
            'batchTrackingAvailable' => $batchTrackingAvailable,
            'title' => 'Tambah '.$this->title,
        ]);
    }

    public function show(int|string $id): View
    {
        $relations = ['gudang', 'pihak', 'pembelian', 'detail.barang.satuanDasar', 'detail.satuanInput'];
        if (Schema::hasTable('penerimaan_batch_detail')) {
            $relations[] = 'detail.batchDetails.batch';
        }
        if (Schema::hasTable('antrean_jurnal_gudang')) {
            $relations[] = 'antreanJurnal.jurnal';
        }
        $item = PenerimaanBarang::query()->with($relations)->findOrFail($id);

        return view('gudang.penerimaan.show', [
            'item' => $item,
            'title' => 'Detail '.$this->title,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sumber_penerimaan' => ['nullable', 'in:pembelian,non_pembelian,saldo_awal'],
            'id_pembelian' => [
                'nullable',
                'integer',
                'required_if:sumber_penerimaan,pembelian',
                new TenantOwnershipRule('pembelian', 'id_pembelian'),
            ],
            'id_gudang' => ['required', 'integer', new TenantOwnershipRule('gudang', 'id_gudang')],
            'id_pihak' => ['required', 'integer', 'exists:master_pihak,id_pihak'],
            'kode_penerimaan' => ['required', 'string', 'max:30'],
            'tanggal_terima' => ['required', 'date'],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'items.*.id_satuan_input' => ['required', 'integer', 'exists:satuan,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.qty_layak' => ['required', 'numeric', 'regex:/^\d{1,14}(?:\.\d{1,4})?$/', 'gte:0'],
            'items.*.alasan_tidak_layak' => ['nullable', 'string'],
            'items.*.harga_satuan' => ['required', 'numeric', 'gte:0'],
            'items.*.faktor_konversi' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,6})?$/', 'gt:0'],
            'items.*.gunakan_faktor_aktual' => ['sometimes', 'boolean'],
            'items.*.simpan_konversi' => ['sometimes', 'boolean'],
            'items.*.batches' => ['sometimes', 'array', 'min:1'],
            'items.*.batches.*.kode_batch' => ['nullable', 'string', 'max:100'],
            'items.*.batches.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'items.*.batches.*.qty_dasar' => ['required_with:items.*.batches', 'numeric', 'regex:/^\d{1,14}(?:\.\d{1,4})?$/', 'gt:0'],
            'items.*.batches.*.qty_layak' => ['required_with:items.*.batches', 'numeric', 'regex:/^\d{1,14}(?:\.\d{1,4})?$/', 'gte:0'],
            'items.*.batches.*.alasan_tidak_layak' => ['nullable', 'string'],
        ]);

        $entitasId = $this->entitasAktifId();
        $kodePenerimaan = $data['kode_penerimaan'];
        $sumberPenerimaan = $data['sumber_penerimaan']
            ?? (! empty($data['id_pembelian']) ? 'pembelian' : 'non_pembelian');
        $idPembelian = $sumberPenerimaan === 'pembelian' && ! empty($data['id_pembelian'])
            ? (int) $data['id_pembelian']
            : null;

        return DB::transaction(function () use ($data, $kodePenerimaan, $sumberPenerimaan, $idPembelian, $entitasId) {
            $pembelian = null;
            if ($idPembelian !== null) {
                $pembelian = Pembelian::query()->lockForUpdate()->find($idPembelian);
                if (! $pembelian || ! in_array($pembelian->status, ['disetujui', 'diterima'], true)) {
                    throw ValidationException::withMessages([
                        'id_pembelian' => 'Pembelian tidak ditemukan atau belum berstatus disetujui.',
                    ]);
                }
                if (! $pembelian->id_jurnal) {
                    throw ValidationException::withMessages([
                        'id_pembelian' => "Pembelian #{$pembelian->kode_pembelian} belum memiliki jurnal akuntansi.",
                    ]);
                }
            }

            // Insert header penerimaan
            $header = PenerimaanBarang::query()->create([
                'id_entitas' => $entitasId,
                'id_gudang' => $data['id_gudang'],
                'kode_penerimaan' => $kodePenerimaan,
                'id_pembelian' => $idPembelian,
                'id_pihak' => $data['id_pihak'],
                'tanggal_terima' => $data['tanggal_terima'],
                'status' => 'diposting',
                'catatan' => $data['catatan'] ?? null,
                'created_by' => auth()->id(),
            ]);

            if ($pembelian && $pembelian->status === 'disetujui') {
                $pembelian->update(['status' => 'diterima']);
            }

            $service = app(StokService::class);
            $antreanGroup = [];

            // Loop setiap item dan insert detail + update stok
            foreach ($data['items'] as $index => $item) {
                $barang = Barang::query()->findOrFail($item['id_barang']);
                $idSatuanInput = (int) $item['id_satuan_input'];
                $isSatuanDasar = $idSatuanInput === (int) $barang->id_satuan_dasar;
                $gunakanFaktorAktual = (bool) ($item['gunakan_faktor_aktual'] ?? false);
                $simpanKonversi = (bool) ($item['simpan_konversi'] ?? false);
                $satuanAktif = Satuan::query()
                    ->whereKey($idSatuanInput)
                    ->where('is_active', true)
                    ->exists();

                if (! $satuanAktif) {
                    throw ValidationException::withMessages([
                        "items.{$index}.id_satuan_input" => 'Satuan yang dipilih tidak aktif.',
                    ]);
                }

                $konversi = $isSatuanDasar
                    ? null
                    : KonversiSatuan::query()
                        ->where('id_barang', $barang->id_barang)
                        ->where('id_satuan', $idSatuanInput)
                        ->first();

                if (! $isSatuanDasar && ! $konversi && ! $simpanKonversi) {
                    throw ValidationException::withMessages([
                        "items.{$index}.id_satuan_input" => 'Satuan ini belum dikonversi untuk barang tersebut. Simpan konversinya sebagai standar terlebih dahulu.',
                    ]);
                }

                $faktorKonversi = $isSatuanDasar
                    ? '1'
                    : ($konversi && ! $gunakanFaktorAktual
                        ? (string) $konversi->faktor_ke_dasar
                        : (string) $item['faktor_konversi']);
                $qtyInput = (string) $item['qty'];
                $hargaSatuanInput = (string) $item['harga_satuan'];
                $qtyDasar = bcmul($qtyInput, $faktorKonversi, 4);
                $lacakBatch = $this->stokBatch->pelacakanAktif(
                    (int) ($header->id_entitas ?? app('entitas_aktif')),
                    (int) $barang->id_barang,
                );
                $batchInputs = $item['batches'] ?? [];

                if ($lacakBatch && $batchInputs === []) {
                    throw ValidationException::withMessages([
                        "items.{$index}.batches" => 'Barang ini wajib dicatat per batch.',
                    ]);
                }

                if ($lacakBatch) {
                    $qtyBatchDasarTotal = '0';
                    $qtyBatchDasar = '0';
                    $qtyLayak = '0';
                    $qtyTidakLayak = '0';

                    foreach ($batchInputs as $batchIndex => $batch) {
                        $qtyBatchDasar = (string) $batch['qty_dasar'];
                        $qtyBatchLayak = (string) $batch['qty_layak'];
                        $qtyBatchTidakLayak = bcsub($qtyBatchDasar, $qtyBatchLayak, 4);
                        if (bccomp($qtyBatchTidakLayak, '0', 4) < 0) {
                            throw ValidationException::withMessages([
                                "items.{$index}.batches.{$batchIndex}.qty_layak" => 'Jumlah layak batch tidak boleh melebihi jumlah batch diterima.',
                            ]);
                        }
                        if (
                            bccomp($qtyBatchTidakLayak, '0', 4) > 0
                            && trim((string) ($batch['alasan_tidak_layak'] ?? '')) === ''
                        ) {
                            throw ValidationException::withMessages([
                                "items.{$index}.batches.{$batchIndex}.alasan_tidak_layak" => 'Alasan wajib diisi untuk batch yang tidak layak.',
                            ]);
                        }
                        if (
                            bccomp($qtyBatchLayak, '0', 4) > 0
                            && ! empty($batch['tanggal_kedaluwarsa'])
                            && $batch['tanggal_kedaluwarsa'] < now()->toDateString()
                        ) {
                            throw ValidationException::withMessages([
                                "items.{$index}.batches.{$batchIndex}.tanggal_kedaluwarsa" => 'Batch yang sudah kedaluwarsa tidak dapat menambah stok layak.',
                            ]);
                        }

                        $qtyBatchDasarTotal = bcadd($qtyBatchDasarTotal, $qtyBatchDasar, 4);
                        $qtyLayak = bcadd($qtyLayak, $qtyBatchLayak, 4);
                        $qtyTidakLayak = bcadd($qtyTidakLayak, $qtyBatchTidakLayak, 4);
                        $batchInputs[$batchIndex]['qty_tidak_layak'] = $qtyBatchTidakLayak;
                    }

                    if (bccomp($qtyBatchDasarTotal, $qtyDasar, 4) !== 0) {
                        throw ValidationException::withMessages([
                            "items.{$index}.batches" => 'Total jumlah per batch harus sama dengan jumlah diterima dalam satuan dasar.',
                        ]);
                    }
                    $alasanTidakLayak = 'Sortir tercatat per batch.';
                } else {
                    $qtyLayak = (string) $item['qty_layak'];
                    $qtyTidakLayak = bcsub($qtyDasar, $qtyLayak, 4);
                    $alasanTidakLayak = trim((string) ($item['alasan_tidak_layak'] ?? ''));
                }
                $hargaSatuanDasar = bcdiv($hargaSatuanInput, $faktorKonversi, 4);
                $subtotal = bcmul($qtyInput, $hargaSatuanInput, 2);

                if (bccomp($qtyDasar, '0', 4) <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.faktor_konversi" => 'Hasil konversi harus lebih besar dari nol.',
                    ]);
                }

                if (bccomp($qtyTidakLayak, '0', 4) < 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.qty_layak" => 'Jumlah layak tidak boleh melebihi jumlah yang diterima.',
                    ]);
                }

                if (! $lacakBatch && bccomp($qtyTidakLayak, '0', 4) > 0 && $alasanTidakLayak === '') {
                    throw ValidationException::withMessages([
                        "items.{$index}.alasan_tidak_layak" => 'Alasan wajib diisi jika ada barang tidak layak.',
                    ]);
                }

                if ($simpanKonversi && ! $isSatuanDasar && (! $konversi || $gunakanFaktorAktual)) {
                    KonversiSatuan::query()->updateOrCreate(
                        [
                            'id_barang' => $barang->id_barang,
                            'id_satuan' => $idSatuanInput,
                        ],
                        ['faktor_ke_dasar' => $faktorKonversi],
                    );
                }

                // Insert detail
                $detail = PenerimaanBarangDetail::query()->create([
                    'id_penerimaan' => $header->id_penerimaan,
                    'id_barang' => $barang->id_barang,
                    'id_satuan_input' => $idSatuanInput,
                    'qty_input' => $qtyInput,
                    'faktor_konversi' => $faktorKonversi,
                    'qty_dasar' => $qtyDasar,
                    'qty_layak' => $qtyLayak,
                    'qty_tidak_layak' => $qtyTidakLayak,
                    'harga_satuan_dasar' => $hargaSatuanDasar,
                    'subtotal' => $subtotal,
                    'alasan_tidak_layak' => $alasanTidakLayak !== '' ? $alasanTidakLayak : null,
                    'foto_bukti' => null,
                ]);

                $kartuStok = null;
                if (bccomp($qtyLayak, '0', 4) > 0) {
                    $kartuStok = $service->masuk(
                        entitasId: (int) ($header->id_entitas ?? $entitasId),
                        gudangId: (int) $data['id_gudang'],
                        barangId: (int) $barang->id_barang,
                        qty: $qtyLayak,
                        hargaSatuan: $hargaSatuanDasar,
                        refTipe: 'PENERIMAAN',
                        refId: (int) $header->id_penerimaan,
                        createdBy: auth()->id(),
                    );
                }

                if ($lacakBatch) {
                    $this->stokBatch->catatPenerimaan(
                        detailId: (int) $detail->id_detail,
                        gudangId: (int) $data['id_gudang'],
                        barangId: (int) $barang->id_barang,
                        batches: $batchInputs,
                        kartuStok: $kartuStok,
                    );
                }

                // Kumpulkan antrean jika ada barang layak
                if (bccomp($qtyLayak, '0', 4) > 0 && $barang->id_unit_usaha) {
                    $unitId = (int) $barang->id_unit_usaha;
                    $nilaiItem = (float) bcmul($qtyLayak, $hargaSatuanDasar, 2);
                    if (! isset($antreanGroup[$unitId])) {
                        $antreanGroup[$unitId] = [
                            'unit_id' => $unitId,
                            'nilai' => 0.0,
                            'items' => [],
                        ];
                    }
                    $antreanGroup[$unitId]['nilai'] += $nilaiItem;
                    $antreanGroup[$unitId]['items'][] = [
                        'id_barang' => (int) $barang->id_barang,
                        'qty' => (float) $qtyLayak,
                        'hpp_satuan' => (float) $hargaSatuanDasar,
                        'nilai' => $nilaiItem,
                        'id_kartu' => $kartuStok?->id_kartu,
                    ];
                }
            }

            // Insert ke antrean jurnal per unit usaha
            foreach ($antreanGroup as $g) {
                if ($g['nilai'] > 0) {
                    AntreanJurnalGudang::create([
                        'id_entitas' => (int) ($header->id_entitas ?? $entitasId),
                        'jenis_transaksi' => 'PENERIMAAN',
                        'ref_tipe' => 'PENERIMAAN',
                        'ref_id' => (int) $header->id_penerimaan,
                        'no_dokumen' => $header->kode_penerimaan,
                        'id_unit_usaha' => $g['unit_id'],
                        'tanggal' => $data['tanggal_terima'],
                        'nilai' => $g['nilai'],
                        'payload' => [
                            'sumber_penerimaan' => $sumberPenerimaan,
                            'id_gudang' => (int) $data['id_gudang'],
                            'id_pembelian' => $header->id_pembelian,
                            'id_pihak' => (int) $data['id_pihak'],
                            'items' => $g['items'],
                        ],
                        'status' => 'MENUNGGU',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            return redirect()->route('gudang.penerimaan.index')->with('success', 'Penerimaan barang berhasil disimpan.');
        });
    }
}
