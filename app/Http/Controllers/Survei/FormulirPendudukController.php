<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Pendataan\PendudukDesa;
use App\Models\Survei\Pertanyaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormulirPendudukController extends Controller
{
    use ResolvesEntitasAktif;

    public function create(): View
    {
        $desaAktif = $this->desaAktif();
        $pertanyaanList = Pertanyaan::where('jenis_pendataan', 'penduduk')->where('is_active', true)->orderBy('urutan')->get();

        // Riwayat data penduduk desa ini
        $riwayatData = $desaAktif ? PendudukDesa::where('id_wilayah', $desaAktif->id)->orderBy('tahun', 'desc')->get() : collect();

        return view('survei.formulir.penduduk', [
            'title' => 'Formulir Pendataan Penduduk: ' . ($desaAktif?->nama ?? 'Desa'),
            'desaAktif' => $desaAktif,
            'pertanyaanList' => $pertanyaanList,
            'riwayatData' => $riwayatData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $desaAktif = $this->desaAktif();

        if (! $desaAktif) {
            return back()->with('error', 'Desa aktif tidak ditemukan.');
        }

        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'balita' => ['required', 'integer', 'min:0'],
            'anak' => ['required', 'integer', 'min:0'],
            'remaja' => ['required', 'integer', 'min:0'],
            'dewasa' => ['required', 'integer', 'min:0'],
            'lansia' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $validated['id_wilayah'] = $desaAktif->id;
        $validated['total_penduduk'] = (int)$validated['balita'] + (int)$validated['anak'] + (int)$validated['remaja'] + (int)$validated['dewasa'] + (int)$validated['lansia'];
        $validated['sumber_data'] = 'Survei Penduduk Desa ' . $desaAktif->nama;

        PendudukDesa::updateOrCreate(
            ['id_wilayah' => $desaAktif->id, 'tahun' => $validated['tahun']],
            $validated
        );

        return redirect()->route('survei.formulir.penduduk')->with('success', "Data demografi penduduk {$desaAktif->nama} tahun {$validated['tahun']} berhasil disimpan!");
    }
}
