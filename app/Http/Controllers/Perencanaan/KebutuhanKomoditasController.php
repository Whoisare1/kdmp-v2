<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Services\Perencanaan\KalkulasiKebutuhanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class KebutuhanKomoditasController extends ModuleCrudController
{
    use ResolvesWilayahAktif;

    protected string $model = KebutuhanKomoditas::class;
    protected string $view = 'perencanaan.kebutuhan-komoditas';
    protected string $title = 'Kebutuhan Komoditas (Hasil Kalkulasi M2)';
    protected string $routeBase = 'perencanaan.kebutuhan-komoditas';
    protected array $withRelations = ['wilayah', 'komoditas', 'standar'];

    public function index(Request $request): View
    {
        $idWilayah = $this->wilayahAktifId();

        $tahun = (int) $request->query('tahun', 2026);
        $bulan = $request->filled('bulan') ? (int) $request->query('bulan') : null;
        $idKomoditas = $request->filled('id_komoditas') ? (int) $request->query('id_komoditas') : null;

        $query = KebutuhanKomoditas::with(['wilayah', 'komoditas', 'standar'])
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun);

        if ($bulan !== null) {
            $query->where('bulan', $bulan);
        }

        if ($idKomoditas !== null) {
            $query->where('id_komoditas', $idKomoditas);
        }

        if ($search = $request->query('q')) {
            $query->whereHas('komoditas', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('id_komoditas')
            ->orderBy('bulan')
            ->orderBy('kelompok_umur')
            ->paginate($this->perPage)
            ->withQueryString();

        // Data pendukung untuk filter dan kalkulasi
        $daftarKomoditas = Komoditas::where('is_active', true)->orderBy('nama')->get();
        $totalPendudukTahunIni = DemografiDesa::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->sum('jumlah_penduduk');

        $adaDataDemografi = $totalPendudukTahunIni > 0;

        $ringkasanKebutuhan = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->when($idKomoditas, fn ($q) => $q->where('id_komoditas', $idKomoditas))
            ->sum('kebutuhan_bulanan');

        $totalKomoditasTerhitung = KebutuhanKomoditas::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->distinct('id_komoditas')
            ->count('id_komoditas');

        return view('perencanaan.kebutuhan-komoditas.index', [
            'items' => $items,
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'idKomoditas' => $idKomoditas,
            'daftarKomoditas' => $daftarKomoditas,
            'adaDataDemografi' => $adaDataDemografi,
            'totalPenduduk' => $totalPendudukTahunIni,
            'ringkasanKebutuhan' => $ringkasanKebutuhan,
            'totalKomoditasTerhitung' => $totalKomoditasTerhitung,
        ]);
    }

    /**
     * Menjalankan kalkulasi kebutuhan bulanan komoditas via service.
     */
    public function kalkulasi(Request $request, KalkulasiKebutuhanService $service): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'id_komoditas' => ['nullable', 'exists:komoditas,id'],
        ]);

        try {
            $idWilayah = $this->wilayahAktifId();
            $hasil = $service->hitungKebutuhan(
                $idWilayah,
                (int) $validated['tahun'],
                (int) $validated['bulan'],
                !empty($validated['id_komoditas']) ? (int) $validated['id_komoditas'] : null
            );

            $namaBulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ][$validated['bulan']] ?? $validated['bulan'];

            return redirect()->route('perencanaan.kebutuhan-komoditas.index', [
                'tahun' => $validated['tahun'],
                'bulan' => $validated['bulan'],
                'id_komoditas' => $validated['id_komoditas'] ?? null,
            ])->with('status', "Kalkulasi kebutuhan berhasil dijalankan untuk {$namaBulan} {$validated['tahun']}. Terhitung {$hasil['total_baris_kebutuhan']} baris kebutuhan dan diperbarui {$hasil['total_hasil_kalkulasi']} neraca komoditas.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
