<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\PenerimaanBarang;
use App\Models\Gudang\PenerimaanBarangDetail;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Master\KonversiSatuan;
use App\Models\Master\Pihak;
use App\Models\Master\Satuan;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $barang = Barang::query()
            ->with(['satuanDasar', 'konversiSatuan.satuan'])
            ->where('is_active', true)
            ->orderBy('nama_barang')
            ->get();
        $satuan = Satuan::query()
            ->where('is_active', true)
            ->orderBy('kode_satuan')
            ->get(['id', 'kode_satuan']);

        return view('gudang.penerimaan.create', [
            'gudang' => $gudang,
            'pihak' => $pihak,
            'barangData' => $barang->map(fn (Barang $item) => [
                'id' => $item->id_barang,
                'kode' => $item->kode_barang,
                'nama' => $item->nama_barang,
                'satuanDasarId' => $item->id_satuan_dasar,
                'satuanDasar' => $item->satuanDasar?->kode_satuan ?? '-',
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
            'title' => 'Tambah '.$this->title,
        ]);
    }

    public function show(int|string $id): View
    {
        $item = PenerimaanBarang::query()
            ->with(['gudang', 'pihak', 'detail.barang.satuanDasar', 'detail.satuanInput'])
            ->findOrFail($id);

        return view('gudang.penerimaan.show', [
            'item' => $item,
            'title' => 'Detail '.$this->title,
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
            'items.*.id_satuan_input' => ['required', 'integer', 'exists:satuan,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.qty_layak' => ['required', 'numeric', 'regex:/^\d{1,14}(?:\.\d{1,4})?$/', 'gte:0'],
            'items.*.alasan_tidak_layak' => ['nullable', 'string'],
            'items.*.harga_satuan' => ['required', 'numeric', 'gte:0'],
            'items.*.faktor_konversi' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,6})?$/', 'gt:0'],
            'items.*.gunakan_faktor_aktual' => ['sometimes', 'boolean'],
            'items.*.simpan_konversi' => ['sometimes', 'boolean'],
        ]);

        $kodePenerimaan = $data['kode_penerimaan'];

        return DB::transaction(function () use ($data, $kodePenerimaan) {
            // Insert header penerimaan
            $header = PenerimaanBarang::query()->create([
                'id_entitas' => app()->bound('entitas_aktif') ? app('entitas_aktif') : null,
                'id_gudang' => $data['id_gudang'],
                'kode_penerimaan' => $kodePenerimaan,
                'id_pembelian' => null,
                'id_pihak' => $data['id_pihak'],
                'tanggal_terima' => $data['tanggal_terima'],
                'status' => 'diposting',
                'catatan' => $data['catatan'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $service = new StokService;

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
                $qtyLayak = (string) $item['qty_layak'];
                $qtyTidakLayak = bcsub($qtyDasar, $qtyLayak, 4);
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

                $alasanTidakLayak = trim((string) ($item['alasan_tidak_layak'] ?? ''));

                if (bccomp($qtyTidakLayak, '0', 4) > 0 && $alasanTidakLayak === '') {
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
                PenerimaanBarangDetail::query()->create([
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

                if (bccomp($qtyLayak, '0', 4) > 0) {
                    $service->masuk(
                        entitasId: (int) ($header->id_entitas ?? app('entitas_aktif')),
                        gudangId: (int) $data['id_gudang'],
                        barangId: (int) $barang->id_barang,
                        qty: $qtyLayak,
                        hargaSatuan: $hargaSatuanDasar,
                        refTipe: 'PENERIMAAN',
                        refId: (int) $header->id_penerimaan,
                        createdBy: auth()->id(),
                    );
                }
            }

            return redirect()->route('gudang.penerimaan.index')->with('success', 'Penerimaan barang berhasil disimpan.');
        });
    }
}
