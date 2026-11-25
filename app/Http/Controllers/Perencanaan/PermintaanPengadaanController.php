<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Master\UnitUsaha;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Rules\TenantOwnershipRule;
use App\Services\Perencanaan\PerbandinganHargaService;
use App\Support\Perencanaan\PeriodeKalkulasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermintaanPengadaanController extends ModuleCrudController
{
    protected string $model = PermintaanPengadaan::class;
    protected string $view = 'perencanaan.permintaan-pengadaan';
    protected string $title = 'Permintaan Pengadaan (PR)';
    protected string $routeBase = 'perencanaan.permintaan-pengadaan';
    protected array $withRelations = ['pihak'];

    public function fromHasil(HasilKalkulasi $hasil): RedirectResponse
    {
        $this->assertHasilBisaDiproses($hasil);

        return redirect()->route('perencanaan.permintaan-pengadaan.create', [
            'id_hasil' => $hasil->id_hasil,
        ]);
    }

    public function index(Request $request): View
    {
        $user = auth()->user();
        $idWilayah = $user?->entitas?->id_wilayah;
        $hargaService = app(PerbandinganHargaService::class);
        $items = PermintaanPengadaan::query()
            ->with(['pihak', 'unitUsaha', 'gudang', 'detail.barang'])
            ->latest('id_permintaan')
            ->paginate(15)
            ->withQueryString();
        $calonPerencanaan = HasilKalkulasi::query()
            ->with(['komoditas', 'wilayah'])
            ->where('status_surplus_defisit', 'defisit')
            ->when($idWilayah, fn ($query) => $query->where('id_wilayah', $idWilayah))
            ->latest('id_hasil')
            ->get()
            ->map(function (HasilKalkulasi $hasil) use ($hargaService, $user): array {
                $periode = PeriodeKalkulasi::fromMonth((int) $hasil->bulan);
                $comparison = $hargaService->bandingkan(
                    (int) $user->id_entitas,
                    (int) $hasil->id_komoditas,
                    (int) $hasil->tahun,
                    $periode,
                );

                return [
                    'hasil' => $hasil,
                    'sektor' => in_array($hasil->komoditas?->kategori, ['pertanian', 'perkebunan', 'peternakan'], true)
                        ? $hasil->komoditas->kategori
                        : 'lainnya',
                    'periode' => $periode,
                    'calonSupplier' => $hargaService->calonSupplier(
                        (int) $hasil->id_komoditas,
                        (int) $hasil->tahun,
                        $periode,
                    ),
                    'hargaTerendah' => $comparison['lowest'],
                ];
            });
        $calonPerencanaanSektor = $calonPerencanaan
            ->groupBy('sektor')
            ->sortKeys()
            ->map(function ($items) {
                return $items
                    ->groupBy(fn (array $rencana): int => (int) $rencana['hasil']->id_komoditas)
                    ->map(fn ($komoditasItems) => $komoditasItems->sortBy([
                        ['periode', 'asc'],
                        ['hasil.bulan', 'asc'],
                    ])->values())
                    ->sortBy(fn ($komoditasItems) => $komoditasItems->first()['hasil']->komoditas?->nama ?? '')
                    ->values();
            });

        return view('perencanaan.permintaan-pengadaan.index', [
            'title' => $this->title,
            'items' => $items,
            'calonPerencanaanSektor' => $calonPerencanaanSektor,
        ]);
    }

    public function show(int|string $id): View
    {
        $item = PermintaanPengadaan::query()
            ->with(['pihak', 'unitUsaha', 'gudang', 'detail.barang', 'detail'])
            ->findOrFail($id);

        return view('perencanaan.permintaan-pengadaan.show', [
            'title' => 'Detail '.$this->title,
            'item' => $item,
        ]);
    }

    /**
     * Setujui draft Permintaan Pengadaan (PR).
     */
    public function setujui(int|string $id): RedirectResponse
    {
        $user = auth()->user();
        $item = PermintaanPengadaan::findOrFail($id);

        if ($user && $user->id_entitas !== null && (int) $item->id_entitas !== (int) $user->id_entitas) {
            abort(403, 'Akses ditolak: Permintaan pengadaan milik koperasi lain.');
        }

        $item->update([
            'status' => 'disetujui',
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', "Permintaan Pengadaan {$item->kode_permintaan} berhasil disetujui.");
    }

    /**
     * Tolak Permintaan Pengadaan.
     */
    public function tolak(Request $request, int|string $id): RedirectResponse
    {
        $user = auth()->user();
        $item = PermintaanPengadaan::findOrFail($id);

        if ($user && $user->id_entitas !== null && (int) $item->id_entitas !== (int) $user->id_entitas) {
            abort(403, 'Akses ditolak: Permintaan pengadaan milik koperasi lain.');
        }

        $alasan = $request->input('catatan') ?? $request->input('alasan');

        $item->update([
            'status' => 'ditolak',
            'catatan' => $alasan ? ($item->catatan ? $item->catatan . ' | Ditolak: ' . $alasan : 'Ditolak: ' . $alasan) : ($item->catatan ?: 'Ditolak oleh pengelola.'),
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', "Permintaan Pengadaan {$item->kode_permintaan} berhasil ditolak.");
    }

    /**
     * Kembalikan status Permintaan Pengadaan ke draft.
     */
    public function kembalikanKeDraft(int|string $id): RedirectResponse
    {
        $user = auth()->user();
        $item = PermintaanPengadaan::findOrFail($id);

        if ($user && $user->id_entitas !== null && (int) $item->id_entitas !== (int) $user->id_entitas) {
            abort(403, 'Akses ditolak: Permintaan pengadaan milik koperasi lain.');
        }

        $item->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return back()->with('status', "Status Permintaan Pengadaan {$item->kode_permintaan} dikembalikan ke Draft.");
    }

    public function create(): View
    {
        $hargaService = app(PerbandinganHargaService::class);
        $idHasil = request()->query('id_hasil');
        $hasil = $idHasil ? HasilKalkulasi::with(['komoditas', 'wilayah'])->find($idHasil) : null;

        if ($idHasil !== null && ! $hasil) {
            abort(404, 'Hasil kalkulasi tidak ditemukan.');
        }

        if ($hasil) {
            $this->assertHasilBisaDiproses($hasil);
        }

        $user = auth()->user();
        $entitasId = $user?->id_entitas 
            ?? session('entitas_aktif_pilihan') 
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');

        $barang = $hasil
            ? Barang::query()
                ->with('satuanDasar')
                ->where('id_komoditas', $hasil->id_komoditas)
                ->where('is_active', true)
                ->where(function ($q) use ($entitasId) {
                    if ($entitasId) {
                        $q->where('id_entitas', $entitasId)->orWhereNull('id_entitas');
                    }
                })
                ->orderByRaw("CASE WHEN id_entitas = ? THEN 0 ELSE 1 END", [$entitasId ?? 0])
                ->orderByDesc('id_barang')
                ->first()
            : null;

        $gudangs = $entitasId
            ? Gudang::query()->where('id_entitas', $entitasId)->where('is_active', true)->orderBy('nama_gudang')->get()
            : Gudang::query()->where('is_active', true)->orderBy('nama_gudang')->get();
        $satuanBarang = $barang?->satuanDasar?->kode_satuan;
        $hargaComparison = $hasil
            ? $hargaService->bandingkan(
                (int) $entitasId,
                (int) $hasil->id_komoditas,
                (int) $hasil->tahun,
                PeriodeKalkulasi::fromMonth((int) $hasil->bulan),
                $satuanBarang,
            )
            : ['items' => collect(), 'lowest' => null, 'highest' => null, 'difference' => null, 'satuan' => null];

        return view('perencanaan.permintaan-pengadaan.create', [
            'title' => 'PR - Pengadaan Defisit',
            'routeBase' => $this->routeBase,
            'hasil' => $hasil,
            'periodeLabel' => $hasil
                ? PeriodeKalkulasi::detail(PeriodeKalkulasi::fromMonth((int) $hasil->bulan))['label'].' '.$hasil->tahun
                : null,
            'barang' => $barang,
            'jumlahDiminta' => $hasil ? max(0, abs((float) $hasil->selisih)) : 0,
            'hargaPerkiraan' => $hargaComparison['lowest'],
            'satuanHarga' => $hargaComparison['satuan'],
            'tanggalPengajuan' => now()->toDateString(),
            'gudangs' => $gudangs,
            'calonSupplier' => $hasil
                ? $hargaService->calonSupplier((int) $hasil->id_komoditas, (int) $hasil->tahun, PeriodeKalkulasi::fromMonth((int) $hasil->bulan))
                : collect(),
            'perbandinganHarga' => $hargaComparison['items']->keyBy('id_wilayah_sumber'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hargaService = app(PerbandinganHargaService::class);
        $validated = $request->validate([
            'id_hasil' => ['required', 'integer', 'exists:hasil_kalkulasi,id_hasil'],
            'id_barang' => ['required', 'integer', 'exists:master_barang,id_barang'],
            'id_gudang' => ['required', 'integer', new TenantOwnershipRule('gudang', 'id_gudang')],
            'tgl_pengajuan' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ]);
        $hasil = HasilKalkulasi::with('komoditas')->findOrFail((int) $validated['id_hasil']);
        $this->assertHasilBisaDiproses($hasil);
        $user = auth()->user();
        $entitasId = $user?->id_entitas 
            ?? session('entitas_aktif_pilihan') 
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? \App\Models\Tenant\Entitas::value('id_entitas');

        $barang = Barang::query()->whereKey($validated['id_barang'])
            ->with('satuanDasar')
            ->where('id_komoditas', $hasil->id_komoditas)->where('is_active', true)->firstOrFail();
        $gudang = Gudang::query()->whereKey($validated['id_gudang'])
            ->where(function ($q) use ($entitasId) {
                if ($entitasId) {
                    $q->where('id_entitas', $entitasId);
                }
            })->where('is_active', true)->firstOrFail();

        $unitUsahaId = $hasil->id_unit_usaha_rekomendasi
            ?? $barang->id_unit_usaha
            ?? UnitUsaha::where('kode_unit_usaha', 'SEMBAKO')->value('id_unit_usaha')
            ?? UnitUsaha::value('id_unit_usaha');

        if (! $unitUsahaId || ! UnitUsaha::query()->whereKey($unitUsahaId)->exists()) {
            return back()->withInput()->withErrors(['id_hasil' => 'Unit usaha rekomendasi tidak ditemukan.']);
        }

        if (! $hasil->id_unit_usaha_rekomendasi) {
            $hasil->update([
                'id_unit_usaha_rekomendasi' => $unitUsahaId,
                'alasan_rekomendasi' => 'Rekomendasi pengadaan untuk pemenuhan defisit komoditas.',
            ]);
        }

        $jumlah = abs((float) $hasil->selisih);
        if ($jumlah <= 0) {
            return back()->withInput()->withErrors(['id_hasil' => 'Jumlah kebutuhan defisit tidak valid.']);
        }
        $comparison = $hargaService->bandingkan(
            (int) $entitasId,
            (int) $hasil->id_komoditas,
            (int) $hasil->tahun,
            PeriodeKalkulasi::fromMonth((int) $hasil->bulan),
            $barang->satuanDasar?->kode_satuan,
        );
        $harga = $comparison['lowest'] ?? 0;
        $total = $jumlah * $harga;

        $isSetujui = $request->input('aksi') === 'setujui';

        DB::transaction(function () use ($validated, $entitasId, $hasil, $barang, $gudang, $jumlah, $harga, $total, $unitUsahaId, $isSetujui): void {
            $pr = PermintaanPengadaan::create([
                'id_entitas' => $entitasId,
                'kode_permintaan' => PermintaanPengadaan::generateKode((int) $entitasId),
                'tgl_pengajuan' => $validated['tgl_pengajuan'],
                'id_unit_usaha' => $unitUsahaId,
                'id_gudang' => $gudang->id_gudang,
                'total_nilai' => $total,
                'status' => $isSetujui ? 'disetujui' : 'draft',
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => auth()->id(),
                'approved_by' => $isSetujui ? auth()->id() : null,
                'approved_at' => $isSetujui ? now() : null,
            ]);
            $pr->detail()->create([
                'id_barang' => $barang->id_barang,
                'id_hasil' => $hasil->id_hasil,
                'jumlah_diminta' => $jumlah,
                'harga_perkiraan' => $harga,
                'subtotal' => $total,
            ]);

        });

        $statusMsg = $isSetujui
            ? 'Permintaan Pengadaan berhasil dibuat dan langsung disetujui.'
            : 'Draft PR berhasil disimpan.';

        return redirect()->route($this->routeBase . '.index')->with('status', $statusMsg);
    }

    private function assertHasilBisaDiproses(HasilKalkulasi $hasil): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, 'Silakan login terlebih dahulu.');
        }

        // Jika bukan Super Admin, validasi apakah hasil kalkulasi milik wilayahnya
        if ($user->id_entitas !== null) {
            $idWilayahUser = $user->entitas?->id_wilayah;

            if ((int) $hasil->id_wilayah !== (int) $idWilayahUser) {
                abort(403, 'Hasil kalkulasi tidak milik koperasi aktif Anda.');
            }
        }

        if ($hasil->status_surplus_defisit !== 'defisit') {
            abort(422, 'Hanya komoditas dengan status defisit yang dapat dibuatkan PR.');
        }
    }

}
