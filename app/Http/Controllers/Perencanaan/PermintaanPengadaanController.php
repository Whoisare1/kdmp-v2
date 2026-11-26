<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Master\Komoditas;
use App\Models\Master\UnitUsaha;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Perencanaan\RekomendasiPengadaanDesa;
use App\Rules\TenantOwnershipRule;
use App\Services\Perencanaan\PerbandinganHargaService;
use App\Support\Perencanaan\PeriodeKalkulasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PermintaanPengadaanController extends ModuleCrudController
{
    use ResolvesWilayahAktif;

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
        $idWilayah = $this->wilayahAktifId();

        $hasilWilayahSendiri = HasilKalkulasi::query()
            ->with(['komoditas', 'wilayah'])
            ->whereIn('status_surplus_defisit', ['defisit', 'surplus'])
            ->where('id_wilayah', $idWilayah)
            ->whereRaw(
                'hasil_kalkulasi.versi = (SELECT MAX(h2.versi) FROM hasil_kalkulasi h2 WHERE h2.id_wilayah = hasil_kalkulasi.id_wilayah AND h2.id_komoditas = hasil_kalkulasi.id_komoditas AND h2.tahun = hasil_kalkulasi.tahun AND h2.bulan = hasil_kalkulasi.bulan)'
            )
            ->latest('id_hasil')
            ->get();
        $pasanganAntarDesa = collect();

        if ($hasilWilayahSendiri->isNotEmpty()) {
            $pasanganAntarDesa = HasilKalkulasi::query()
                ->with(['wilayah', 'komoditas'])
                ->where('id_wilayah', '!=', $idWilayah)
                ->whereIn('status_surplus_defisit', ['defisit', 'surplus'])
                ->whereIn('id_komoditas', $hasilWilayahSendiri->pluck('id_komoditas')->unique())
                ->whereIn('tahun', $hasilWilayahSendiri->pluck('tahun')->unique())
                ->whereIn('bulan', $hasilWilayahSendiri->pluck('bulan')->unique())
                ->whereHas('wilayah', fn ($query) => $query
                    ->where('tingkat', 'desa')
                    ->whereHas('entitas', fn ($entitas) => $entitas
                        ->where('is_active', true)
                        ->where('status', 'APPROVED')))
                ->whereRaw(
                    'hasil_kalkulasi.versi = (SELECT MAX(h2.versi) FROM hasil_kalkulasi h2 WHERE h2.id_wilayah = hasil_kalkulasi.id_wilayah AND h2.id_komoditas = hasil_kalkulasi.id_komoditas AND h2.tahun = hasil_kalkulasi.tahun AND h2.bulan = hasil_kalkulasi.bulan)'
                )
                ->get()
                ->groupBy(fn (HasilKalkulasi $hasil): string => implode('|', [
                    $hasil->status_surplus_defisit,
                    $hasil->id_komoditas,
                    $hasil->tahun,
                    $hasil->bulan,
                ]));
        }

        $calonPerencanaan = $hasilWilayahSendiri->map(function (HasilKalkulasi $hasil) use ($pasanganAntarDesa): array {
            $periode = PeriodeKalkulasi::fromMonth((int) $hasil->bulan);
            $statusPasangan = $hasil->status_surplus_defisit === 'defisit' ? 'surplus' : 'defisit';
            $key = implode('|', [$statusPasangan, $hasil->id_komoditas, $hasil->tahun, $hasil->bulan]);

            return [
                'hasil' => $hasil,
                'sektor' => strtolower(trim((string) ($hasil->komoditas?->kategori ?: 'lainnya'))),
                'periode' => $periode,
                'statusPasangan' => $statusPasangan,
                'calonSupplier' => $pasanganAntarDesa->get($key, collect())->sortByDesc(
                    fn (HasilKalkulasi $calon): float => abs((float) $calon->selisih)
                )->values(),
            ];
        });
        $rekomendasiTerkini = collect();
        if ($calonPerencanaan->isNotEmpty()) {
            $rekomendasiTerkini = RekomendasiPengadaanDesa::query()
                ->where(function ($query) use ($calonPerencanaan): void {
                    $idHasilSendiri = $calonPerencanaan->pluck('hasil')->map->id_hasil;
                    $query->whereIn('id_hasil_defisit', $idHasilSendiri)
                        ->orWhereIn('id_hasil_surplus', $idHasilSendiri);
                })
                ->latest('id_rekomendasi')
                ->get()
                ->unique(fn (RekomendasiPengadaanDesa $item): string => $item->id_hasil_defisit.'|'.$item->id_hasil_surplus)
                ->keyBy(fn (RekomendasiPengadaanDesa $item): string => $item->id_hasil_defisit.'|'.$item->id_hasil_surplus);
        }
        $kategoriSektor = Komoditas::query()
            ->where('is_active', true)
            ->whereNotNull('kategori')
            ->pluck('kategori')
            ->map(fn (string $sektor): string => strtolower(trim($sektor)))
            ->merge(['pertanian', 'perkebunan', 'peternakan', 'perikanan'])
            ->filter()
            ->unique()
            ->values();
        $calonPerencanaanSektor = $kategoriSektor
            ->mapWithKeys(fn (string $sektor): array => [$sektor => collect()])
            ->merge($calonPerencanaan
            ->groupBy('sektor')
            ->sortKeys())
            ->sortKeys()
            ->map(function ($items) {
                return $items
                    ->groupBy(fn (array $rencana): string => mb_strtolower(trim((string) ($rencana['hasil']->komoditas?->nama ?: 'Komoditas tidak ditemukan'))))
                    ->map(fn ($komoditasItems) => $komoditasItems->sortBy([
                        ['periode', 'asc'],
                        ['hasil.bulan', 'asc'],
                        ['hasil.status_surplus_defisit', 'asc'],
                    ])->values())
                    ->sortBy(fn ($komoditasItems) => $komoditasItems->first()['hasil']->komoditas?->nama ?? '')
                    ->values();
            });

        return view('perencanaan.permintaan-pengadaan.index', [
            'title' => 'Rekomendasi Pengadaan Desa',
            'calonPerencanaanSektor' => $calonPerencanaanSektor,
            'rekomendasiTerkini' => $rekomendasiTerkini,
            'wilayahAktif' => $hasilWilayahSendiri->first()?->wilayah,
        ]);
    }

    public function show(int|string $id): View
    {
        $user = auth()->user();
        $item = PermintaanPengadaan::query()
            ->with(['pihak', 'unitUsaha', 'gudang', 'detail.barang', 'detail'])
            ->findOrFail($id);

        if ($user && $user->id_entitas !== null && (int) $item->id_entitas !== (int) $user->id_entitas) {
            abort(403, 'Akses ditolak: Permintaan pengadaan milik koperasi lain.');
        }

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

        $entitasId = $this->entitasAktifId();

        $barang = $hasil
            ? Barang::query()
                ->with('satuanDasar')
                ->where('id_komoditas', $hasil->id_komoditas)
                ->where('is_active', true)
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('master_barang', 'id_entitas'), function ($query) use ($entitasId) {
                    $query->where(function ($q) use ($entitasId) {
                        $q->where('id_entitas', $entitasId)->orWhereNull('id_entitas');
                    })->orderByRaw("CASE WHEN id_entitas = ? THEN 0 ELSE 1 END", [$entitasId]);
                })
                ->orderByDesc('id_barang')
                ->first()
            : null;

        $gudangs = Gudang::query()->where('id_entitas', $entitasId)->where('is_active', true)->orderBy('nama_gudang')->get();
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
        $entitasId = $this->entitasAktifId();

        $barangQuery = Barang::query()->whereKey($validated['id_barang'])
            ->with('satuanDasar')
            ->where('id_komoditas', $hasil->id_komoditas)
            ->where('is_active', true);
        if (Schema::hasColumn('master_barang', 'id_entitas')) {
            $barangQuery->where(fn ($query) => $query
                ->where('id_entitas', $entitasId)
                ->orWhereNull('id_entitas'));
        }
        $barang = $barangQuery->firstOrFail();
        $gudang = Gudang::query()->whereKey($validated['id_gudang'])
            ->where('id_entitas', $entitasId)
            ->where('is_active', true)->firstOrFail();

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

        if ((int) $hasil->id_wilayah !== $this->wilayahAktifId()) {
            abort(403, 'Hasil kalkulasi tidak milik desa aktif Anda.');
        }

        if ($hasil->status_surplus_defisit !== 'defisit') {
            abort(422, 'Hanya komoditas dengan status defisit yang dapat dibuatkan PR.');
        }
    }

}
