<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\KategoriKomoditas;
use App\Models\Master\MasterKomoditas;
use App\Models\Survei\ModulSurvei;
use App\Models\Survei\Pertanyaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterPertanyaanController extends Controller
{
    public function index(Request $request): View
    {
        $jenis = $request->query('jenis');
        $search = trim((string) $request->query('q', ''));

        $query = Pertanyaan::with(['kategoriKomoditas', 'masterKomoditas']);

        if ($jenis) {
            $query->where('jenis_pendataan', $jenis);
        }

        if ($search !== '') {
            $query->where('teks_pertanyaan', 'like', "%{$search}%")
                  ->orWhere('kode_pertanyaan', 'like', "%{$search}%");
        }

        $items = $query->orderBy('urutan')->orderBy('id')->paginate(15)->withQueryString();

        return view('master.pertanyaan.index', [
            'title' => 'Master Pertanyaan Survei',
            'items' => $items,
            'jenis' => $jenis,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $kategoriList = KategoriKomoditas::where('is_active', true)->orderBy('nama')->get();
        $masterList = MasterKomoditas::where('is_active', true)->orderBy('nama')->get();

        return view('master.pertanyaan.create', [
            'title' => 'Tambah Pertanyaan Survei',
            'kategoriList' => $kategoriList,
            'masterList' => $masterList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_pendataan' => ['required', 'in:penduduk,komoditas'],
            'id_kategori_komoditas' => ['nullable', 'exists:kategori_komoditas,id'],
            'id_master_komoditas' => ['nullable', 'exists:master_komoditas,id'],
            'kode_pertanyaan' => ['required', 'string', 'max:50', 'unique:pertanyaan,kode_pertanyaan'],
            'teks_pertanyaan' => ['required', 'string'],
            'tipe_jawaban' => ['required', 'in:angka,teks,pilihan,pilihan_ganda'],
            'satuan' => ['nullable', 'string', 'max:20'],
            'urutan' => ['required', 'integer', 'min:1'],
            'wajib_diisi' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $defaultModul = ModulSurvei::firstOrCreate(['kode' => 'SURVEI-DESA'], ['nama' => 'Survei Potensi Desa', 'versi' => '2.0', 'is_active' => true]);
        $validated['id_modul'] = $defaultModul->id;
        $validated['wajib_diisi'] = $request->boolean('wajib_diisi', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        Pertanyaan::create($validated);

        return redirect()->route('master.pertanyaan.index')->with('success', 'Pertanyaan berhasil ditambahkan.');
    }

    public function edit(Pertanyaan $pertanyaan): View
    {
        $kategoriList = KategoriKomoditas::where('is_active', true)->orderBy('nama')->get();
        $masterList = MasterKomoditas::where('is_active', true)->orderBy('nama')->get();

        return view('master.pertanyaan.edit', [
            'title' => 'Edit Pertanyaan Survei',
            'item' => $pertanyaan,
            'kategoriList' => $kategoriList,
            'masterList' => $masterList,
        ]);
    }

    public function update(Request $request, Pertanyaan $pertanyaan): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_pendataan' => ['required', 'in:penduduk,komoditas'],
            'id_kategori_komoditas' => ['nullable', 'exists:kategori_komoditas,id'],
            'id_master_komoditas' => ['nullable', 'exists:master_komoditas,id'],
            'kode_pertanyaan' => ['required', 'string', 'max:50', 'unique:pertanyaan,kode_pertanyaan,' . $pertanyaan->id],
            'teks_pertanyaan' => ['required', 'string'],
            'tipe_jawaban' => ['required', 'in:angka,teks,pilihan,pilihan_ganda'],
            'satuan' => ['nullable', 'string', 'max:20'],
            'urutan' => ['required', 'integer', 'min:1'],
            'wajib_diisi' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['wajib_diisi'] = $request->boolean('wajib_diisi');
        $validated['is_active'] = $request->boolean('is_active');

        $pertanyaan->update($validated);

        return redirect()->route('master.pertanyaan.index')->with('success', 'Pertanyaan berhasil diperbarui.');
    }

    public function destroy(Pertanyaan $pertanyaan): RedirectResponse
    {
        $pertanyaan->delete();
        return redirect()->route('master.pertanyaan.index')->with('success', 'Pertanyaan berhasil dihapus.');
    }
}

