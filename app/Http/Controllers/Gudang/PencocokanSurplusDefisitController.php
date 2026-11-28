<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Gudang\PermintaanAntarEntitas;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PencocokanSurplusDefisitController extends Controller
{
    use ResolvesWilayahAktif;

    public function index(): View
    {
        $idWilayah = $this->wilayahAktifId();

        $user = auth()->user();
        $entitasId = (int) ($user?->id_entitas
            ?? session('entitas_aktif_pilihan')
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : 0));

        if ($entitasId <= 0 && $idWilayah) {
            $entitasId = (int) (DB::table('entitas')->where('id_wilayah', $idWilayah)->value('id_entitas') ?? 1);
        }

        $permintaanMasuk = PermintaanAntarEntitas::query()
            ->with(['pengaju.wilayah', 'penerima.wilayah'])
            ->where('id_entitas_penerima', $entitasId)
            ->latest()
            ->get();

        $permintaanKeluar = PermintaanAntarEntitas::query()
            ->with(['pengaju.wilayah', 'penerima.wilayah'])
            ->where('id_entitas_pengaju', $entitasId)
            ->latest()
            ->get();

        $jumlahMenungguRespons = $permintaanMasuk
            ->where('status', 'terkirim')
            ->whereNull('tgl_persetujuan_penerima')
            ->count();

        return view('gudang.pencocokan-surplus-defisit', [
            'permintaanMasuk' => $permintaanMasuk,
            'permintaanKeluar' => $permintaanKeluar,
            'jumlahMenungguRespons' => $jumlahMenungguRespons,
            'entitasId' => $entitasId,
        ]);
    }
}
