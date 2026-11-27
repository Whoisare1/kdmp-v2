<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Pendataan\PendudukDesa;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanDemografiController extends Controller
{
    use ResolvesEntitasAktif;

    public function index(Request $request): View
    {
        $desaAktif = $this->desaAktif();
        $desaId = $request->query('desa_id', $desaAktif?->id);
        $tahun = $request->query('tahun');

        $query = PendudukDesa::with('wilayah.parent.parent');

        if ($tahun) {
            $query->where('tahun', $tahun);
        }

        if ($desaId) {
            $query->where('id_wilayah', $desaId);
        }

        $items = $query->orderBy('tahun', 'desc')->get();

        // Rekapitulasi Total
        $totalBalita = $items->sum('balita');
        $totalAnak = $items->sum('anak');
        $totalRemaja = $items->sum('remaja');
        $totalDewasa = $items->sum('dewasa');
        $totalLansia = $items->sum('lansia');
        $totalJiwa = $items->sum('total_penduduk');

        return view('laporan.demografi.index', [
            'title' => 'Laporan Demografi Penduduk: ' . ($desaAktif?->nama ?? 'Desa'),
            'items' => $items,
            'desaAktif' => $desaAktif,
            'desaId' => $desaId,
            'tahun' => $tahun,
            'rekap' => compact('totalBalita', 'totalAnak', 'totalRemaja', 'totalDewasa', 'totalLansia', 'totalJiwa'),
        ]);
    }
}
