<?php

namespace App\Http\Controllers\Pendataan;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Pendataan\PendudukDesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendudukDesaController extends Controller
{
    use ResolvesEntitasAktif;

    public function index(Request $request): View
    {
        $desaAktif = $this->desaAktif();
        $tahun = $request->query('tahun');

        $query = PendudukDesa::with('wilayah.parent.parent');

        if ($desaAktif) {
            $query->where('id_wilayah', $desaAktif->id);
        }

        if ($tahun) {
            $query->where('tahun', $tahun);
        }

        $items = $query->orderBy('tahun', 'desc')->paginate(15)->withQueryString();

        return view('pendataan.penduduk.index', [
            'title' => 'Data Penduduk: ' . ($desaAktif?->nama ?? 'Desa'),
            'items' => $items,
            'desaAktif' => $desaAktif,
            'tahun' => $tahun,
        ]);
    }

    public function create(): View
    {
        $desaAktif = $this->desaAktif();

        return view('pendataan.penduduk.create', [
            'title' => 'Input Penduduk: ' . ($desaAktif?->nama ?? 'Desa'),
            'desaAktif' => $desaAktif,
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
            'sumber_data' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $validated['id_wilayah'] = $desaAktif->id;
        $validated['total_penduduk'] = (int)$validated['balita'] + (int)$validated['anak'] + (int)$validated['remaja'] + (int)$validated['dewasa'] + (int)$validated['lansia'];
        $validated['sumber_data'] = $validated['sumber_data'] ?? ('Input Admin ' . $desaAktif->nama);

        PendudukDesa::updateOrCreate(
            ['id_wilayah' => $desaAktif->id, 'tahun' => $validated['tahun']],
            $validated
        );

        return redirect()->route('pendataan.penduduk.index')->with('success', "Data penduduk {$desaAktif->nama} tahun {$validated['tahun']} berhasil disimpan!");
    }

    public function edit(PendudukDesa $penduduk): View
    {
        $desaAktif = $this->desaAktif();

        return view('pendataan.penduduk.edit', [
            'title' => 'Edit Data Penduduk Tahun ' . $penduduk->tahun,
            'penduduk' => $penduduk,
            'desaAktif' => $desaAktif,
        ]);
    }

    public function update(Request $request, PendudukDesa $penduduk): RedirectResponse
    {
        $validated = $request->validate([
            'balita' => ['required', 'integer', 'min:0'],
            'anak' => ['required', 'integer', 'min:0'],
            'remaja' => ['required', 'integer', 'min:0'],
            'dewasa' => ['required', 'integer', 'min:0'],
            'lansia' => ['required', 'integer', 'min:0'],
            'sumber_data' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $validated['total_penduduk'] = (int)$validated['balita'] + (int)$validated['anak'] + (int)$validated['remaja'] + (int)$validated['dewasa'] + (int)$validated['lansia'];

        $penduduk->update($validated);

        return redirect()->route('pendataan.penduduk.index')->with('success', "Data penduduk tahun {$penduduk->tahun} berhasil diperbarui!");
    }

    public function destroy(PendudukDesa $penduduk): RedirectResponse
    {
        $penduduk->delete();

        return redirect()->route('pendataan.penduduk.index')->with('success', 'Data penduduk berhasil dihapus!');
    }
}
