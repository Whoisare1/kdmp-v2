<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Perencanaan\DemografiDesa;
use App\Models\Perencanaan\KetersediaanKomoditas;
use App\Models\Survei\SesiSurvei;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemografiController extends Controller
{
    use ResolvesWilayahAktif;

    public function index(Request $request): View
    {
        $idWilayahAsal = $this->wilayahAktifId();
        $daftarWilayah = Wilayah::where('tingkat', 'desa')->orderBy('nama')->get();
        if (! $daftarWilayah->contains('id', $idWilayahAsal)) {
            $wilayahAktif = Wilayah::find($idWilayahAsal);
            if ($wilayahAktif) {
                $daftarWilayah->prepend($wilayahAktif);
            }
        }

        $idWilayah = $idWilayahAsal;
        if ($request->filled('id_wilayah')) {
            $idWilayahDiminta = (int) $request->query('id_wilayah');
            abort_unless($daftarWilayah->contains('id', $idWilayahDiminta), 404);
            $idWilayah = $idWilayahDiminta;
        }

        $wilayah = Wilayah::find($idWilayah);
        $isKomparasi = ($idWilayah !== $idWilayahAsal);

        $tahun = (int) $request->query('tahun', 2026);

        $records = DemografiDesa::with('sesiSurvei')
            ->where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->get();

        $daftarTahun = DemografiDesa::where('id_wilayah', $idWilayah)
            ->distinct()
            ->pluck('tahun');

        $daftarTahun = $daftarTahun->merge(
            KetersediaanKomoditas::where('id_wilayah', $idWilayah)
                ->distinct()
                ->pluck('tahun')
        )->map(fn ($tahunData) => (int) $tahunData)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if (!in_array($tahun, $daftarTahun, true)) {
            $daftarTahun[] = $tahun;
            rsort($daftarTahun);
        }

        $kelompokUmurList = ['balita', 'anak', 'remaja', 'dewasa', 'lansia'];
        $populasiMap = [];
        $totalPenduduk = 0;

        foreach ($kelompokUmurList as $ku) {
            $rec = $records->firstWhere('kelompok_umur', $ku);
            $jml = $rec ? (int) $rec->jumlah_penduduk : 0;
            $totalPenduduk += $jml;
            $populasiMap[$ku] = [
                'record' => $rec,
                'jumlah' => $jml,
            ];
        }

        $sesiSurveiM1 = SesiSurvei::where('id_wilayah', $idWilayah)
            ->where('tahun', $tahun)
            ->latest('tanggal_survei')
            ->first();

        return view('perencanaan.demografi.index', [
            'wilayah' => $wilayah,
            'daftarWilayah' => $daftarWilayah,
            'idWilayahAsal' => $idWilayahAsal,
            'idWilayah' => $idWilayah,
            'isKomparasi' => $isKomparasi,
            'tahun' => $tahun,
            'daftarTahun' => $daftarTahun,
            'records' => $records,
            'populasiMap' => $populasiMap,
            'totalPenduduk' => $totalPenduduk,
            'sesiSurveiM1' => $sesiSurveiM1,
            'kelompokUmurList' => $kelompokUmurList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'jumlah_penduduk' => ['required', 'array'],
            'jumlah_penduduk.*' => ['required', 'integer', 'min:0'],
        ]);

        $idWilayah = $this->wilayahAktifId();

        foreach (['balita', 'anak', 'remaja', 'dewasa', 'lansia'] as $ku) {
            if (isset($validated['jumlah_penduduk'][$ku])) {
                DemografiDesa::updateOrCreate(
                    [
                        'id_wilayah' => $idWilayah,
                        'tahun' => (int) $validated['tahun'],
                        'kelompok_umur' => $ku,
                    ],
                    [
                        'jumlah_penduduk' => (int) $validated['jumlah_penduduk'][$ku],
                        'created_at' => now(),
                    ]
                );
            }
        }

        return redirect()->route('perencanaan.demografi.index', ['tahun' => $validated['tahun']])
            ->with('status', "Data demografi penduduk tahun {$validated['tahun']} berhasil diperbarui.");
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $idWilayah = $this->wilayahAktifId();
        $record = DemografiDesa::where('id_wilayah', $idWilayah)->findOrFail($id);

        $validated = $request->validate([
            'jumlah_penduduk' => ['required', 'integer', 'min:0'],
        ]);

        $record->update(['jumlah_penduduk' => (int) $validated['jumlah_penduduk']]);

        return back()->with('status', "Populasi kelompok umur " . ucfirst($record->kelompok_umur) . " berhasil diperbarui.");
    }
}
