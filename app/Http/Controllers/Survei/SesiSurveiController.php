<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Survei\SesiSurvei;
use App\Services\Survei\SurveySyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(int|string $id): View
    {
        $sesi = SesiSurvei::with(['wilayah', 'petugas', 'jawaban.modul', 'jawaban.pertanyaan'])->findOrFail($id);

        return view('survei.sesi.show', [
            'sesi' => $sesi,
            'title' => 'Detail Sesi Survei #' . $sesi->id,
            'routeBase' => $this->routeBase,
        ]);
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
