<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Survei\SesiSurvei;
use App\Services\Survei\SurveySyncService;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SesiSurveiController extends ModuleCrudController
{
    protected string $model = SesiSurvei::class;
    protected string $view = 'survei.sesi';
    protected string $title = 'Sesi Survei';
    protected string $routeBase = 'survei.sesi';
    protected array $withRelations = ['wilayah', 'petugas', 'jawaban'];

    public function index(Request $request): View
    {
        $query = SesiSurvei::with(['wilayah', 'petugas', 'jawaban']);

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('catatan', 'like', "%{$search}%")
                  ->orWhereHas('wilayah', fn ($qw) => $qw->where('nama', 'like', "%{$search}%"))
                  ->orWhereHas('petugas', fn ($qp) => $qp->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $items = $query->latest('id')->paginate($this->perPage)->withQueryString();

        return view('survei.sesi.index', [
            'items' => $items,
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'columns' => ['id', 'id_wilayah', 'tahun', 'tanggal_survei', 'status'],
        ]);
    }

    public function create(): View
    {
        $wilayahs = Wilayah::all();
        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('survei.sesi.create', [
            'title' => 'Buat Sesi Survei Baru',
            'wilayahs' => $wilayahs,
            'bulans' => $bulans,
            'routeBase' => $this->routeBase
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_wilayah' => 'required|exists:wilayah,id',
            'tahun' => 'required|integer|min:2000|max:2100',
            'bulan' => 'required|integer|min:1|max:12',
            'tanggal_survei' => 'required|date',
        ]);

        $validated['status'] = 'DRAFT';
        $validated['token_rt'] = Str::random(32);
        $validated['token_masyarakat'] = Str::random(32);
        $validated['token_produsen'] = Str::random(32);
        $validated['id_petugas'] = auth()->id();

        $sesi = SesiSurvei::create($validated);

        return redirect()->route('survei.sesi.show', $sesi->id)
            ->with('success', 'Sesi survei berhasil dibuat. Silakan bagikan tautan ke petugas lapangan.');
    }

    public function show(int|string $id): View
    {
        $item = SesiSurvei::with(['wilayah', 'petugas', 'jawaban.modul', 'jawaban.pertanyaan'])->findOrFail($id);

        // Auto-update status sesi 1 jika data RT sudah ada
        $rtCount = \App\Models\Survei\RtDesa::where('id_sesi', $item->id)->count();
        if ($rtCount > 0 && in_array($item->status_sesi1, ['belum_diisi', null])) {
            $item->status_sesi1 = 'sedang_diisi';
            $item->save();
        }

        return view('survei.sesi.show', [
            'sesi' => $item,
            'item' => $item,
            'title' => 'Detail Sesi Survei #' . $item->id,
            'routeBase' => $this->routeBase,
        ]);
    }

    public function edit(int|string $id): View
    {
        $item = SesiSurvei::findOrFail($id);

        $wilayahs = Wilayah::all();
        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('survei.sesi.edit', [
            'title' => 'Ubah Sesi Survei',
            'item' => $item,
            'wilayahs' => $wilayahs,
            'bulans' => $bulans,
            'routeBase' => $this->routeBase
        ]);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $sesi = SesiSurvei::findOrFail($id);

        $validated = $request->validate([
            'id_wilayah' => 'required|exists:wilayah,id',
            'tahun' => 'required|integer|min:2000|max:2100',
            'bulan' => 'required|integer|min:1|max:12',
            'tanggal_survei' => 'required|date',
        ]);

        $sesi->update($validated);

        return redirect()->route('survei.sesi.show', $sesi->id)
            ->with('success', 'Sesi survei berhasil diperbarui.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $sesi = SesiSurvei::findOrFail($id);
        $sesi->delete();

        return back()->with('success', 'Sesi survei berhasil dihapus.');
    }

    /**
     * Memicu sinkronisasi eksplisit data survei ke modul Perencanaan (M2/M3).
     */
    public function sinkron(Request $request, int|string $id, SurveySyncService $syncService): RedirectResponse
    {
        try {
            $sesi = SesiSurvei::findOrFail($id);
            $hasil = $syncService->syncSesi($sesi);

            $msg = "Sinkronisasi berhasil! ";
            $msg .= "{$hasil['demografi_updated']} baris demografi, ";
            $msg .= "{$hasil['ketersediaan_updated']} ketersediaan komoditas, ";
            $msg .= "{$hasil['standar_updated']} standar kebutuhan diperbarui.";

            if (!empty($hasil['data_gaps'])) {
                $gapMsg = implode('; ', array_column($hasil['data_gaps'], 'keterangan'));
                return back()->with('status', $msg)->with('warning', "Catatan Data Gap: " . $gapMsg);
            }

            return back()->with('status', $msg);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal melakukan sinkronisasi: ' . $e->getMessage());
        }
    }
}
