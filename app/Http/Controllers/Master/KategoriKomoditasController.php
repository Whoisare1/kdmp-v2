<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\KategoriKomoditas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategoriKomoditasController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $query = KategoriKomoditas::withCount(['masterKomoditas']);

        if ($search !== '') {
            $query->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
        }

        $items = $query->orderBy('nama')->paginate(15)->withQueryString();

        return view('master.kategori_komoditas.index', [
            'title' => 'Kategori Komoditas',
            'items' => $items,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('master.kategori_komoditas.create', [
            'title' => 'Tambah Kategori Komoditas',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:kategori_komoditas,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        KategoriKomoditas::create($validated);

        return redirect()->route('master.kategori-komoditas.index')->with('success', 'Kategori komoditas berhasil ditambahkan.');
    }

    public function edit(KategoriKomoditas $kategoriKomoditas): View
    {
        return view('master.kategori_komoditas.edit', [
            'title' => 'Edit Kategori Komoditas',
            'item' => $kategoriKomoditas,
        ]);
    }

    public function update(Request $request, KategoriKomoditas $kategoriKomoditas): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:kategori_komoditas,kode,' . $kategoriKomoditas->id],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $kategoriKomoditas->update($validated);

        return redirect()->route('master.kategori-komoditas.index')->with('success', 'Kategori komoditas berhasil diperbarui.');
    }

    public function destroy(KategoriKomoditas $kategoriKomoditas): RedirectResponse
    {
        if ($kategoriKomoditas->masterKomoditas()->count() > 0) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki Master Komoditas terkait.');
        }

        $kategoriKomoditas->delete();
        return redirect()->route('master.kategori-komoditas.index')->with('success', 'Kategori berhasil dihapus.');
    }
}

