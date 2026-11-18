<?php

namespace App\Http\Controllers;

use App\Models\Master\Komoditas;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Tenant\Entitas;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        try {
            $daftarEntitas = Entitas::withoutGlobalScopes()
                ->with('wilayah')
                ->where('is_active', true)
                ->orderBy('nama_entitas')
                ->get();
        } catch (\Throwable) {
            $daftarEntitas = collect();
        }

        try {
            $daftarKomoditas = Komoditas::withoutGlobalScopes()
                ->where('is_active', true)
                ->orderBy('nama')
                ->get();
        } catch (\Throwable) {
            $daftarKomoditas = collect();
        }

        try {
            $periodeTerbaru = HasilKalkulasi::query()
                ->select('tahun', 'bulan')
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->first();

            $tahun = (int) ($periodeTerbaru->tahun ?? date('Y'));
            $bulan = (int) ($periodeTerbaru->bulan ?? 1);

            $hasilKalkulasi = HasilKalkulasi::with(['wilayah', 'komoditas'])
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->orderBy('id_wilayah')
                ->get();
        } catch (\Throwable) {
            $tahun = (int) date('Y');
            $bulan = 1;
            $hasilKalkulasi = collect();
        }

        return view('landing', compact(
            'daftarEntitas',
            'daftarKomoditas',
            'hasilKalkulasi',
            'tahun',
            'bulan'
        ));
    }
}
