<?php

namespace App\Http\Controllers\Pendataan;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\ItemKomoditas;
use App\Models\Master\KategoriKomoditas;
use App\Models\Pendataan\Petani;
use App\Models\Pendataan\PotensiProduksiPetani;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PotensiKomoditasController extends Controller
{
    use ResolvesEntitasAktif;

    public function index(Request $request): View
    {
        $desaAktif = $this->desaAktif();
        $kategoriId = $request->query('kategori_id');
        $itemId = $request->query('item_id');
        $search = trim((string) $request->query('q', ''));

        $query = PotensiProduksiPetani::with(['petani.wilayah.parent.parent', 'item.masterKomoditas.kategori']);

        if ($desaAktif) {
            $query->whereHas('petani', function ($q) use ($desaAktif) {
                $q->where('id_wilayah', $desaAktif->id);
            });
        }

        if ($itemId) {
            $query->where('item_komoditas_id', $itemId);
        } elseif ($kategoriId) {
            $query->whereHas('item.masterKomoditas', function ($q) use ($kategoriId) {
                $q->where('id_kategori', $kategoriId);
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('petani', function ($qp) use ($search) {
                    $qp->where('nama_lengkap', 'like', "%{$search}%");
                })->orWhereHas('item', function ($qi) use ($search) {
                    $qi->where('nama', 'like', "%{$search}%");
                });
            });
        }

        $items = $query->latest()->paginate(15)->withQueryString();

        $kategoriList = KategoriKomoditas::where('is_active', true)->orderBy('nama')->get();
        $itemList = ItemKomoditas::where('is_active', true)->orderBy('nama')->get();

        return view('pendataan.potensi.index', [
            'title' => 'Potensi Komoditas: ' . ($desaAktif?->nama ?? 'Desa'),
            'items' => $items,
            'desaAktif' => $desaAktif,
            'kategoriList' => $kategoriList,
            'itemList' => $itemList,
            'kategoriId' => $kategoriId,
            'itemId' => $itemId,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $desaAktif = $this->desaAktif();
        $petaniList = $desaAktif ? Petani::where('id_wilayah', $desaAktif->id)->orderBy('nama_lengkap')->get() : collect();
        $itemList = ItemKomoditas::with('masterKomoditas.kategori')->where('is_active', true)->orderBy('nama')->get();

        return view('pendataan.potensi.create', [
            'title' => 'Tambah Potensi Komoditas Petani: ' . ($desaAktif?->nama ?? 'Desa'),
            'desaAktif' => $desaAktif,
            'petaniList' => $petaniList,
            'itemList' => $itemList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'petani_id' => ['required', 'exists:petani,id'],
            'item_komoditas_id' => ['required', 'exists:item_komoditas,id'],
            'estimasi_volume_panen' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:30'],
            'periode_panen' => ['required', 'string', 'max:50'],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'total_nilai' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $vol = (float) $validated['estimasi_volume_panen'];
        $hargaSatuan = isset($validated['harga_satuan']) ? (float) $validated['harga_satuan'] : 0;
        $totalNilai = isset($validated['total_nilai']) ? (float) $validated['total_nilai'] : 0;

        if ($hargaSatuan > 0 && $totalNilai <= 0) {
            $totalNilai = $vol * $hargaSatuan;
        } elseif ($totalNilai > 0 && $hargaSatuan <= 0 && $vol > 0) {
            $hargaSatuan = $totalNilai / $vol;
        } elseif ($hargaSatuan > 0 && $totalNilai > 0) {
            $totalNilai = $vol * $hargaSatuan;
        }

        $validated['harga_satuan'] = $hargaSatuan;
        $validated['total_nilai'] = $totalNilai;

        // Standardisasi satuan otomatis
        $item = ItemKomoditas::with('masterKomoditas.kategori')->find($validated['item_komoditas_id']);
        $isEkraf = $item && (
            str_contains(strtolower($item->masterKomoditas?->kategori?->kode ?? ''), 'ekraf') ||
            str_contains(strtolower($item->masterKomoditas?->kategori?->nama ?? ''), 'ekraf')
        );
        $validated['satuan'] = $isEkraf ? 'unit' : 'kg';

        PotensiProduksiPetani::updateOrCreate(
            [
                'petani_id' => $validated['petani_id'],
                'item_komoditas_id' => $validated['item_komoditas_id'],
            ],
            $validated
        );

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Data komoditas petani berhasil disimpan!');
        }

        return redirect()->route('pendataan.petani.index')->with('success', 'Data potensi komoditas petani berhasil dicatat!');
    }

    public function edit(PotensiProduksiPetani $potensi, Request $request): View
    {
        $desaAktif = $this->desaAktif();
        $potensi->load(['petani.wilayah', 'item.masterKomoditas.kategori']);
        $petaniList = $desaAktif ? Petani::where('id_wilayah', $desaAktif->id)->orderBy('nama_lengkap')->get() : Petani::orderBy('nama_lengkap')->get();
        $itemList = ItemKomoditas::with('masterKomoditas.kategori')->where('is_active', true)->orderBy('nama')->get();
        $redirectTo = $request->query('redirect_to', '');

        return view('pendataan.potensi.edit', [
            'title' => 'Edit Komoditas: ' . ($potensi->item?->nama ?? 'Komoditas') . ' - ' . ($potensi->petani?->nama_lengkap ?? 'Petani'),
            'potensi' => $potensi,
            'desaAktif' => $desaAktif,
            'petaniList' => $petaniList,
            'itemList' => $itemList,
            'redirectTo' => $redirectTo,
        ]);
    }

    public function update(Request $request, PotensiProduksiPetani $potensi): RedirectResponse
    {
        $validated = $request->validate([
            'petani_id' => ['required', 'exists:petani,id'],
            'item_komoditas_id' => ['required', 'exists:item_komoditas,id'],
            'estimasi_volume_panen' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:30'],
            'periode_panen' => ['required', 'string', 'max:50'],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'total_nilai' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $vol = (float) $validated['estimasi_volume_panen'];
        $hargaSatuan = isset($validated['harga_satuan']) ? (float) $validated['harga_satuan'] : 0;
        $totalNilai = isset($validated['total_nilai']) ? (float) $validated['total_nilai'] : 0;

        if ($hargaSatuan > 0 && $totalNilai <= 0) {
            $totalNilai = $vol * $hargaSatuan;
        } elseif ($totalNilai > 0 && $hargaSatuan <= 0 && $vol > 0) {
            $hargaSatuan = $totalNilai / $vol;
        } elseif ($hargaSatuan > 0 && $totalNilai > 0) {
            $totalNilai = $vol * $hargaSatuan;
        }

        $validated['harga_satuan'] = $hargaSatuan;
        $validated['total_nilai'] = $totalNilai;

        $item = ItemKomoditas::with('masterKomoditas.kategori')->find($validated['item_komoditas_id']);
        $isEkraf = $item && (
            str_contains(strtolower($item->masterKomoditas?->kategori?->kode ?? ''), 'ekraf') ||
            str_contains(strtolower($item->masterKomoditas?->kategori?->nama ?? ''), 'ekraf')
        );
        $validated['satuan'] = $isEkraf ? 'unit' : 'kg';

        $potensi->update($validated);

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Data komoditas petani berhasil diperbarui!');
        }

        return redirect()->route('pendataan.petani.index')->with('success', 'Data potensi komoditas petani berhasil diperbarui!');
    }

    public function destroy(PotensiProduksiPetani $potensi): RedirectResponse
    {
        $potensi->delete();

        return back()->with('success', 'Data komoditas produksi berhasil dihapus!');
    }
}
