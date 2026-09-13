<?php

namespace App\Http\Controllers\Konsinyasi;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Konsinyasi\PengirimanKonsinyasi;
use App\Models\Konsinyasi\PengirimanKonsinyasiDetail;
use App\Models\Tenant\KoperasiDesa;
use App\Services\KonsinyasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class PengirimanKonsinyasiController extends ModuleCrudController
{
    protected string $model = PengirimanKonsinyasi::class;
    protected string $view = 'konsinyasi.pengiriman';
    protected string $title = 'Pengiriman Konsinyasi';
    protected string $routeBase = 'konsinyasi.pengiriman';
    protected array $withRelations = ['koperasiPemilik', 'koperasiPenerima'];

    public function create(): View
    {
        $gudang = Gudang::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('nama_gudang')
            ->get();

        return view('konsinyasi.pengiriman.create', [
            'koperasi' => KoperasiDesa::query()->where('is_active', true)->orderBy('nama_koperasi')->get(),
            'gudang' => $gudang,
            'gudangData' => $gudang->map(function ($item) {
                return [
                    'id' => $item->id_gudang,
                    'koperasi' => $item->id_koperasi,
                    'nama' => $item->nama_gudang,
                ];
            })->values(),
            'barang' => Barang::query()->with('satuanDasar')->where('is_active', true)->orderBy('nama_barang')->get(),
            'title' => 'Tambah Pengiriman Konsinyasi',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode_kiriman' => ['required', 'string', 'max:30', 'unique:pengiriman_konsinyasi,kode_kiriman'],
            'id_koperasi_pemilik' => ['required', 'integer', 'exists:koperasi_desa,id_koperasi', 'different:id_koperasi_penerima'],
            'id_koperasi_penerima' => ['required', 'integer', 'exists:koperasi_desa,id_koperasi'],
            'id_gudang_asal' => ['required', 'integer', 'exists:gudang,id_gudang'],
            'id_gudang_tujuan' => ['required', 'integer', 'exists:gudang,id_gudang', 'different:id_gudang_asal'],
            'tgl_kirim' => ['required', 'date'],
            'tgl_batas_titip' => ['nullable', 'date', 'after_or_equal:tgl_kirim'],
            'model_imbalan' => ['required', 'in:selisih_harga,komisi_persen'],
            'persen_komisi' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'penanggung_susut' => ['required', 'in:pemilik,penerima'],
            'catatan_pengiriman' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'items.*.qty_dasar' => ['required', 'numeric', 'gt:0'],
            'items.*.harga_titip_satuan' => ['required', 'numeric', 'gte:0'],
            'items.*.harga_jual_saran' => ['nullable', 'numeric', 'gte:0'],
        ], [
            'id_gudang_tujuan.different' => 'Gudang tujuan harus berbeda dari gudang asal.',
            'id_koperasi_pemilik.different' => 'Desa pemilik dan desa penerima harus berbeda.',
        ]);

        $gudang = Gudang::query()
            ->withoutGlobalScopes()
            ->whereIn('id_gudang', [$data['id_gudang_asal'], $data['id_gudang_tujuan']])
            ->get()
            ->keyBy('id_gudang');

        if ((int) $gudang[$data['id_gudang_asal']]->id_koperasi !== (int) $data['id_koperasi_pemilik']) {
            return back()->withInput()->withErrors(['id_gudang_asal' => 'Gudang asal harus milik desa pemilik.']);
        }

        if ((int) $gudang[$data['id_gudang_tujuan']]->id_koperasi !== (int) $data['id_koperasi_penerima']) {
            return back()->withInput()->withErrors(['id_gudang_tujuan' => 'Gudang tujuan harus milik desa penerima.']);
        }

        $idKiriman = DB::transaction(function () use ($data): int {
            $kiriman = PengirimanKonsinyasi::query()->create([
                'kode_kiriman' => $data['kode_kiriman'],
                'id_koperasi_pemilik' => $data['id_koperasi_pemilik'],
                'id_koperasi_penerima' => $data['id_koperasi_penerima'],
                'id_gudang_asal' => $data['id_gudang_asal'],
                'id_gudang_tujuan' => $data['id_gudang_tujuan'],
                'tgl_kirim' => $data['tgl_kirim'],
                'tgl_batas_titip' => $data['tgl_batas_titip'] ?? null,
                'model_imbalan' => $data['model_imbalan'],
                'persen_komisi' => $data['persen_komisi'] ?? 0,
                'penanggung_susut' => $data['penanggung_susut'],
                'status' => 'draft',
                'status_posting' => 'F',
                'catatan_pengiriman' => $data['catatan_pengiriman'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PengirimanKonsinyasiDetail::query()->create([
                    'id_kiriman' => $kiriman->id_kiriman,
                    'id_barang' => $item['id_barang'],
                    'qty_dasar' => $item['qty_dasar'],
                    'harga_titip_satuan' => $item['harga_titip_satuan'],
                    'harga_jual_saran' => $item['harga_jual_saran'] ?? 0,
                    'hpp_pemilik' => 0,
                    'total_nilai_titip' => 0,
                    'total_hpp' => 0,
                ]);
            }

            return (int) $kiriman->id_kiriman;
        });

        return redirect()->route('konsinyasi.pengiriman.show', $idKiriman)
            ->with('success', 'Draft pengiriman konsinyasi berhasil dibuat.');
    }

    public function show(int|string $id): View
    {
        return view('konsinyasi.pengiriman.show', [
            'item' => PengirimanKonsinyasi::query()
                ->with(['koperasiPemilik', 'koperasiPenerima', 'gudangAsal', 'gudangTujuan', 'detail.barang.satuanDasar'])
                ->findOrFail($id),
            'title' => 'Detail Pengiriman Konsinyasi',
        ]);
    }

    public function posting(int|string $id, KonsinyasiService $service): RedirectResponse
    {
        try {
            $berhasil = $service->kirim((int) $id);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('konsinyasi.pengiriman.show', $id)
                ->withErrors(['posting' => $exception->getMessage()]);
        }

        if (! $berhasil) {
            return redirect()
                ->route('konsinyasi.pengiriman.show', $id)
                ->with('warning', 'Pengiriman tercatat, tetapi menunggu stok mencukupi. Stok dan jurnal belum berubah.');
        }

        return redirect()
            ->route('konsinyasi.pengiriman.show', $id)
            ->with('success', 'Pengiriman konsinyasi berhasil diposting.');
    }

    public function postingPage(int|string $id): RedirectResponse
    {
        return redirect()
            ->route('konsinyasi.pengiriman.show', $id)
            ->with('warning', 'Halaman posting hanya diproses melalui tombol Posting Pengiriman.');
    }
}
