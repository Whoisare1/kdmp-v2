<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\KategoriKomoditas;
use App\Models\Master\MasterKomoditas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterKomoditasController extends Controller
{
    public function index(Request $request): View
    {
        $kategoriId = $request->query('kategori_id');
        $search = trim((string) $request->query('q', ''));

        $query = MasterKomoditas::with('kategori')->withCount('items');

        if ($kategoriId) {
            $query->where('id_kategori', $kategoriId);
        }

        if ($search !== '') {
            $query->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
        }

        $items = $query->orderBy('nama')->paginate(15)->withQueryString();
        $kategoriList = KategoriKomoditas::where('is_active', true)->orderBy('nama')->get();

        return view('master.master_komoditas.index', [
            'title' => 'Master Komoditas',
            'items' => $items,
            'kategoriList' => $kategoriList,
            'kategoriId' => $kategoriId,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $kategoriList = KategoriKomoditas::where('is_active', true)->orderBy('nama')->get();

        return view('master.master_komoditas.create', [
            'title' => 'Tambah Master Komoditas',
            'kategoriList' => $kategoriList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_kategori' => ['required', 'exists:kategori_komoditas,id'],
            'kode' => ['required', 'string', 'max:30', 'unique:master_komoditas,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        MasterKomoditas::create($validated);

        return redirect()->route('master.master-komoditas.index')->with('success', 'Master komoditas berhasil ditambahkan.');
    }

    public function edit(MasterKomoditas $masterKomoditas): View
    {
        $kategoriList = KategoriKomoditas::where('is_active', true)->orderBy('nama')->get();

        return view('master.master_komoditas.edit', [
            'title' => 'Edit Master Komoditas',
            'item' => $masterKomoditas,
            'kategoriList' => $kategoriList,
        ]);
    }

    public function update(Request $request, MasterKomoditas $masterKomoditas): RedirectResponse
    {
        $validated = $request->validate([
            'id_kategori' => ['required', 'exists:kategori_komoditas,id'],
            'kode' => ['required', 'string', 'max:30', 'unique:master_komoditas,kode,' . $masterKomoditas->id],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $masterKomoditas->update($validated);

        return redirect()->route('master.master-komoditas.index')->with('success', 'Master komoditas berhasil diperbarui.');
    }

    public function destroy(MasterKomoditas $masterKomoditas): RedirectResponse
    {
        if ($masterKomoditas->items()->count() > 0) {
            return back()->with('error', 'Master komoditas tidak dapat dihapus karena masih memiliki Item/Subjenis komoditas terkait.');
        }

        $masterKomoditas->delete();
        return redirect()->route('master.master-komoditas.index')->with('success', 'Master komoditas berhasil dihapus.');
    }
}

