<?php

namespace App\Http\Controllers\Konsinyasi;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Master\Pihak;
use App\Models\Konsinyasi\PengirimanKonsinyasi;
use App\Models\Konsinyasi\PengirimanKonsinyasiDetail;
use App\Models\Tenant\Entitas;
use App\Services\KonsinyasiService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class PengirimanKonsinyasiController extends ModuleCrudController
{
    protected string $model = PengirimanKonsinyasi::class;
    protected string $view = 'konsinyasi.pengiriman';
    protected string $title = 'Pengiriman Konsinyasi';
    protected string $routeBase = 'konsinyasi.pengiriman';
    protected array $withRelations = ['koperasiPemilik', 'pihakPemilik', 'koperasiPenerima'];

    protected function canEdit(Model $item): bool
    {
        return $item->status_posting !== 'T';
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'perhatian' => ['nullable', 'in:semua,perlu'],
        ]);
        $query = PengirimanKonsinyasi::query()
            ->terlibat()
            ->with(['koperasiPemilik', 'pihakPemilik', 'koperasiPenerima'])
            ->withSum('stokKonsinyasi as qty_sisa_total', 'qty_sisa');

        if ($search = trim((string) ($filters['q'] ?? ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('kode_kiriman', 'like', "%{$search}%")
                    ->orWhereHas('koperasiPemilik', fn ($pemilik) => $pemilik->where('nama_entitas', 'like', "%{$search}%"))
                    ->orWhereHas('pihakPemilik', fn ($pemilik) => $pemilik->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('koperasiPenerima', fn ($penerima) => $penerima->where('nama_entitas', 'like', "%{$search}%"));
            });
        }
        $query->when($filters['bulan'] ?? null, fn ($builder, $bulan) => $builder->whereMonth('tgl_kirim', $bulan));
        $query->when($filters['tahun'] ?? null, fn ($builder, $tahun) => $builder->whereYear('tgl_kirim', $tahun));
        if (($filters['perhatian'] ?? 'semua') === 'perlu') {
            $query->whereNotNull('tgl_batas_titip')
                ->whereDate('tgl_batas_titip', '<=', now()->addDays(30)->toDateString())
                ->where('status_posting', 'T')
                ->whereHas('stokKonsinyasi', fn ($stok) => $stok->where('qty_sisa', '>', 0));
        }

        return view('konsinyasi.pengiriman.index', [
            'items' => $query->latest('id_kiriman')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'title' => $this->title,
        ]);
    }

    public function create(): View
    {
        return view('konsinyasi.pengiriman.create', $this->formOptions() + [
            'title' => 'Tambah Pengiriman Konsinyasi',
        ]);
    }

    public function edit(int|string $id): View
    {
        $item = PengirimanKonsinyasi::query()
            ->with('detail')
            ->findOrFail($id);

        abort_if($item->status_posting === 'T', 403, 'Pengiriman yang sudah diposting tidak dapat diubah.');

        return view('konsinyasi.pengiriman.create', $this->formOptions() + [
            'item' => $item,
            'title' => 'Ubah Pengiriman Konsinyasi',
        ]);
    }

    private function formOptions(): array
    {
        $entitas = Entitas::query()->where('is_active', true)->orderBy('nama_entitas')->get();
        $gudang = Gudang::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('nama_gudang')
            ->get();
        $pihak = Pihak::query()
            ->where('is_active', true)
            ->whereIn('jenis_pihak', ['warga', 'petani'])
            ->orderBy('nama')
            ->get();

        return [
            'entitas' => $entitas,
            'gudang' => $gudang,
            'pihak' => $pihak,
            'gudangData' => $gudang->map(function ($item) {
                return [
                    'id' => $item->id_gudang,
                    'koperasi' => $item->id_entitas,
                    'nama' => $item->nama_gudang,
                ];
            })->values(),
            'barang' => Barang::query()->with('satuanDasar')->where('is_active', true)->orderBy('nama_barang')->get(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $this->validateOwnership($data);

        $idKiriman = DB::transaction(function () use ($data): int {
            $kiriman = PengirimanKonsinyasi::query()->create([
                'kode_kiriman' => $data['kode_kiriman'],
                'jenis_pemilik' => $data['jenis_pemilik'],
                'id_entitas_pemilik' => $data['id_entitas_pemilik'] ?? null,
                'id_pihak_pemilik' => $data['id_pihak_pemilik'] ?? null,
                'id_entitas_penerima' => $data['id_entitas_penerima'],
                'id_gudang_asal' => $data['id_gudang_asal'] ?? null,
                'alamat_asal' => $data['alamat_asal'] ?? null,
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

            $this->saveDetails($kiriman, $data['items']);

            return (int) $kiriman->id_kiriman;
        });

        return redirect()->route('konsinyasi.pengiriman.show', $idKiriman)
            ->with('success', 'Draft pengiriman konsinyasi berhasil dibuat.');
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $data = $this->validateData($request, (int) $id);
        $this->validateOwnership($data);

        $updated = DB::transaction(function () use ($data, $id): bool {
            $kiriman = PengirimanKonsinyasi::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($kiriman->status_posting === 'T') {
                return false;
            }

            $kiriman->update([
                'kode_kiriman' => $data['kode_kiriman'],
                'jenis_pemilik' => $data['jenis_pemilik'],
                'id_entitas_pemilik' => $data['id_entitas_pemilik'] ?? null,
                'id_pihak_pemilik' => $data['id_pihak_pemilik'] ?? null,
                'id_entitas_penerima' => $data['id_entitas_penerima'],
                'id_gudang_asal' => $data['id_gudang_asal'] ?? null,
                'alamat_asal' => $data['alamat_asal'] ?? null,
                'id_gudang_tujuan' => $data['id_gudang_tujuan'],
                'tgl_kirim' => $data['tgl_kirim'],
                'tgl_batas_titip' => $data['tgl_batas_titip'] ?? null,
                'model_imbalan' => $data['model_imbalan'],
                'persen_komisi' => $data['persen_komisi'] ?? 0,
                'penanggung_susut' => $data['penanggung_susut'],
                'status' => 'draft',
                'catatan_pengiriman' => $data['catatan_pengiriman'] ?? null,
            ]);

            $kiriman->detail()->delete();
            $this->saveDetails($kiriman, $data['items']);

            return true;
        });

        if (! $updated) {
            return redirect()->route('konsinyasi.pengiriman.show', $id)
                ->withErrors(['edit' => 'Pengiriman yang sudah diposting tidak dapat diubah.']);
        }

        return redirect()->route('konsinyasi.pengiriman.show', $id)
            ->with('success', 'Draft pengiriman konsinyasi berhasil diperbarui.');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        $kodeKirimanRule = Rule::unique('pengiriman_konsinyasi', 'kode_kiriman');
        if ($id !== null) {
            $kodeKirimanRule->ignore($id, 'id_kiriman');
        }

        return $request->validate([
            'jenis_pemilik' => ['required', 'in:entitas,pihak'],
            'kode_kiriman' => ['required', 'string', 'max:30', $kodeKirimanRule],
            'id_entitas_pemilik' => ['exclude_unless:jenis_pemilik,entitas', 'required', 'integer', 'exists:entitas,id_entitas', 'different:id_entitas_penerima'],
            'id_pihak_pemilik' => ['exclude_unless:jenis_pemilik,pihak', 'required', 'integer', 'exists:master_pihak,id_pihak'],
            'id_entitas_penerima' => ['required', 'integer', 'exists:entitas,id_entitas'],
            'id_gudang_asal' => ['exclude_unless:jenis_pemilik,entitas', 'required', 'integer', 'exists:gudang,id_gudang'],
            'alamat_asal' => ['nullable', 'string', 'max:2000'],
            'id_gudang_tujuan' => ['required', 'integer', 'exists:gudang,id_gudang'],
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
            'id_entitas_pemilik.different' => 'Desa pemilik dan desa penerima harus berbeda.',
        ]);
    }

    private function validateOwnership(array $data): void
    {
        $gudangTujuan = Gudang::query()
            ->withoutGlobalScopes()
            ->where('id_gudang', $data['id_gudang_tujuan'])
            ->first();

        if (! $gudangTujuan || (int) $gudangTujuan->id_entitas !== (int) $data['id_entitas_penerima']) {
            throw ValidationException::withMessages([
                'id_gudang_tujuan' => 'Gudang tujuan harus milik desa penerima.',
            ]);
        }

        if ($data['jenis_pemilik'] === 'pihak') {
            $pihakValid = Pihak::query()
                ->withoutGlobalScopes()
                ->where('id_pihak', $data['id_pihak_pemilik'])
                ->where('id_entitas', $data['id_entitas_penerima'])
                ->where('is_active', true)
                ->whereIn('jenis_pihak', ['warga', 'petani'])
                ->exists();

            if (! $pihakValid) {
                throw ValidationException::withMessages([
                    'id_pihak_pemilik' => 'Penitip harus berupa warga atau petani aktif yang terdaftar pada desa penerima.',
                ]);
            }

            return;
        }

        $gudangAsal = Gudang::query()
            ->withoutGlobalScopes()
            ->where('id_gudang', $data['id_gudang_asal'])
            ->first();

        if (! $gudangAsal || (int) $gudangAsal->id_entitas !== (int) $data['id_entitas_pemilik']) {
            throw ValidationException::withMessages([
                'id_gudang_asal' => 'Gudang asal harus milik desa pemilik.',
            ]);
        }

        if ((int) $data['id_entitas_pemilik'] === (int) $data['id_entitas_penerima']) {
            throw ValidationException::withMessages([
                'id_entitas_pemilik' => 'Desa pemilik dan desa penerima harus berbeda.',
            ]);
        }
    }

    private function saveDetails(PengirimanKonsinyasi $kiriman, array $items): void
    {
        foreach ($items as $item) {
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
    }

    public function show(int|string $id): View
    {
        $entitasId = $this->entitasAktifId();
        $item = PengirimanKonsinyasi::query()
            ->where(function ($query) use ($entitasId): void {
                $query->where('id_entitas_pemilik', $entitasId)
                    ->orWhere('id_entitas_penerima', $entitasId);
            })
            ->with(['koperasiPemilik', 'pihakPemilik', 'koperasiPenerima', 'gudangAsal', 'gudangTujuan', 'detail.barang.satuanDasar'])
            ->findOrFail($id);

        return view('konsinyasi.pengiriman.show', [
            'item' => $item,
            'bolehPosting' => $this->bolehPosting($item, $entitasId),
            'bolehKonfirmasiTerima' => $item->jenis_pemilik === 'entitas'
                && (int) $item->id_entitas_penerima === $entitasId
                && $item->status_posting === 'T'
                && $item->status === 'dikirim',
            'title' => 'Detail Pengiriman Konsinyasi',
        ]);
    }

    public function posting(int|string $id, KonsinyasiService $service): RedirectResponse
    {
        $entitasId = $this->entitasAktifId();
        $kiriman = PengirimanKonsinyasi::query()->findOrFail($id);
        abort_unless($this->bolehPosting($kiriman, $entitasId), 403);

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

    public function terima(Request $request, int|string $id): RedirectResponse
    {
        $data = $request->validate([
            'catatan_penerimaan' => ['nullable', 'string', 'max:2000'],
        ]);
        $entitasId = $this->entitasAktifId();

        $diterima = DB::transaction(function () use ($id, $entitasId, $data): bool {
            $kiriman = PengirimanKonsinyasi::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $kiriman->jenis_pemilik === 'entitas'
                    && (int) $kiriman->id_entitas_penerima === $entitasId,
                403,
            );

            if ($kiriman->status === 'diterima') {
                return false;
            }
            if ($kiriman->status_posting !== 'T' || $kiriman->status !== 'dikirim') {
                throw ValidationException::withMessages([
                    'penerimaan' => 'Hanya pengiriman yang sudah diposting dan berstatus dikirim yang dapat dikonfirmasi.',
                ]);
            }

            $kiriman->update([
                'status' => 'diterima',
                'tgl_terima' => now()->toDateString(),
                'catatan_penerimaan' => $data['catatan_penerimaan'] ?? null,
            ]);

            return true;
        });

        return redirect()->route('konsinyasi.pengiriman.show', $id)
            ->with($diterima ? 'success' : 'warning', $diterima
                ? 'Penerimaan barang berhasil dikonfirmasi.'
                : 'Pengiriman ini sudah pernah dikonfirmasi diterima.');
    }

    private function entitasAktifId(): int
    {
        $entitasId = (int) (auth()->user()?->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : 0));
        abort_if($entitasId <= 0, 403, 'Pilih entitas aktif untuk mengakses pengiriman konsinyasi.');

        return $entitasId;
    }

    private function bolehPosting(PengirimanKonsinyasi $kiriman, int $entitasId): bool
    {
        return $kiriman->jenis_pemilik === 'entitas'
            ? (int) $kiriman->id_entitas_pemilik === $entitasId
            : (int) $kiriman->id_entitas_penerima === $entitasId;
    }

    public function postingPage(int|string $id): RedirectResponse
    {
        return redirect()
            ->route('konsinyasi.pengiriman.show', $id)
            ->with('warning', 'Halaman posting hanya diproses melalui tombol Posting Pengiriman.');
    }
}
