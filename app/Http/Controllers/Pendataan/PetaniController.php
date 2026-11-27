<?php

namespace App\Http\Controllers\Pendataan;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\ItemKomoditas;
use App\Models\Master\KategoriKomoditas;
use App\Models\Master\MasterKomoditas;
use App\Models\Pendataan\Petani;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetaniController extends Controller
{
    use ResolvesEntitasAktif;

    public function index(Request $request): View
    {
        $desaAktif = $this->desaAktif();
        $search = trim((string) $request->query('q', ''));
        $kategoriId = $request->query('kategori_id');
        $masterId = $request->query('master_id');
        $itemId = $request->query('item_id');

        $activeItem = null;
        if ($itemId) {
            $activeItem = ItemKomoditas::with('masterKomoditas.kategori')->find($itemId);
            if ($activeItem) {
                $masterId = $masterId ?: $activeItem->id_master_komoditas;
                $kategoriId = $kategoriId ?: $activeItem->masterKomoditas?->id_kategori;
            }
        } elseif ($masterId && !$kategoriId) {
            $activeMaster = MasterKomoditas::find($masterId);
            if ($activeMaster) {
                $kategoriId = $activeMaster->id_kategori;
            }
        }

        $query = Petani::with(['wilayah.parent.parent', 'items.masterKomoditas.kategori'])->withCount('items');

        if ($desaAktif) {
            $query->where('id_wilayah', $desaAktif->id);
        }

        // Filter berjenjang: Kategori -> Master -> Item
        if ($itemId) {
            $query->whereHas('items', function ($q) use ($itemId) {
                $q->where('item_komoditas.id', $itemId);
            });
        } elseif ($masterId) {
            $query->whereHas('items', function ($q) use ($masterId) {
                $q->where('item_komoditas.id_master_komoditas', $masterId);
            });
        } elseif ($kategoriId) {
            $query->whereHas('items.masterKomoditas', function ($q) use ($kategoriId) {
                $q->where('master_komoditas.id_kategori', $kategoriId);
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nomor_hp', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($qi) use ($search) {
                      $qi->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        $items = $query->orderBy('nama_lengkap')->paginate(15)->withQueryString();

        // Data hierarki untuk cascading dropdown
        $kategoriList = KategoriKomoditas::with([
            'masterKomoditas' => function ($qm) {
                $qm->where('is_active', true)->with([
                    'items' => function ($qi) {
                        $qi->where('is_active', true)->orderBy('nama');
                    }
                ])->orderBy('nama');
            }
        ])->where('is_active', true)->orderBy('nama')->get();

        return view('pendataan.petani.index', [
            'title' => 'Petani Komoditas: ' . ($desaAktif?->nama ?? 'Desa'),
            'items' => $items,
            'desaAktif' => $desaAktif,
            'kategoriList' => $kategoriList,
            'kategoriId' => $kategoriId,
            'masterId' => $masterId,
            'itemId' => $itemId,
            'activeItem' => $activeItem,
            'search' => $search,
        ]);
    }

    public function show(Petani $petani): View
    {
        $petani->load(['wilayah.parent.parent', 'items.masterKomoditas.kategori']);

        return view('pendataan.petani.show', [
            'title' => 'Detail Petani: ' . $petani->nama_lengkap,
            'petani' => $petani,
            'komoditasList' => $petani->items,
        ]);
    }

    public function create(): View
    {
        $desaAktif = $this->desaAktif();
        $itemList = ItemKomoditas::with('masterKomoditas.kategori')->where('is_active', true)->orderBy('nama')->get();

        return view('pendataan.petani.create', [
            'title' => 'Tambah Petani: ' . ($desaAktif?->nama ?? 'Desa'),
            'desaAktif' => $desaAktif,
            'itemList' => $itemList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $desaAktif = $this->desaAktif();

        if (! $desaAktif) {
            return back()->with('error', 'Desa aktif tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'item_komoditas_id' => ['nullable', 'exists:item_komoditas,id'],
            'luas_lahan_ha' => ['nullable', 'numeric', 'min:0'],
            'estimasi_volume_panen' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:30'],
            'periode_panen' => ['nullable', 'string', 'max:50'],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'total_nilai' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $desaAktif) {
            $petani = Petani::create([
                'id_wilayah' => $desaAktif->id,
                'nama_lengkap' => $validated['nama_lengkap'],
                'nomor_hp' => $validated['nomor_hp'],
                'alamat' => $validated['alamat'],
                'rt' => $validated['rt'],
                'rw' => $validated['rw'],
                'desa' => $desaAktif->nama,
                'kecamatan' => $desaAktif->parent?->nama ?? 'Juwana',
                'kabupaten' => $desaAktif->parent?->parent?->nama ?? 'Pati',
            ]);

            if (!empty($validated['item_komoditas_id']) && !empty($validated['estimasi_volume_panen'])) {
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

                $petani->items()->attach($validated['item_komoditas_id'], [
                    'luas_lahan_ha' => $validated['luas_lahan_ha'] ?? 0,
                    'estimasi_volume_panen' => $vol,
                    'satuan' => $validated['satuan'] ?? 'kg',
                    'periode_panen' => $validated['periode_panen'] ?? '3 - 4 bulan',
                    'harga_satuan' => $hargaSatuan,
                    'total_nilai' => $totalNilai,
                ]);
            }
        });

        return redirect()->route('pendataan.petani.index')->with('success', "Data petani di {$desaAktif->nama} berhasil didaftarkan!");
    }

    public function edit(Petani $petani): View
    {
        $desaAktif = $this->desaAktif();
        $petani->load(['wilayah', 'items.masterKomoditas.kategori']);
        $itemList = ItemKomoditas::with('masterKomoditas.kategori')->where('is_active', true)->orderBy('nama')->get();

        return view('pendataan.petani.edit', [
            'title' => 'Edit Petani: ' . $petani->nama_lengkap,
            'petani' => $petani,
            'desaAktif' => $desaAktif,
            'itemList' => $itemList,
        ]);
    }

    public function update(Request $request, Petani $petani): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'tambah_item_komoditas_id' => ['nullable', 'exists:item_komoditas,id'],
            'tambah_estimasi_volume_panen' => ['nullable', 'numeric', 'min:0'],
            'tambah_satuan' => ['nullable', 'string', 'max:30'],
            'tambah_periode_panen' => ['nullable', 'string', 'max:50'],
            'tambah_harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'tambah_total_nilai' => ['nullable', 'numeric', 'min:0'],
            'tambah_keterangan' => ['nullable', 'string'],
        ]);

        $petani->update([
            'nama_lengkap' => $validated['nama_lengkap'],
            'nomor_hp' => $validated['nomor_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'rt' => $validated['rt'] ?? null,
            'rw' => $validated['rw'] ?? null,
        ]);

        if (!empty($validated['tambah_item_komoditas_id']) && !empty($validated['tambah_estimasi_volume_panen'])) {
            $item = ItemKomoditas::with('masterKomoditas.kategori')->find($validated['tambah_item_komoditas_id']);
            $isEkraf = $item && (
                str_contains(strtolower($item->masterKomoditas?->kategori?->kode ?? ''), 'ekraf') ||
                str_contains(strtolower($item->masterKomoditas?->kategori?->nama ?? ''), 'ekraf')
            );
            $satuan = $isEkraf ? 'unit' : 'kg';

            $vol = (float) $validated['tambah_estimasi_volume_panen'];
            $hargaSatuan = isset($validated['tambah_harga_satuan']) ? (float) $validated['tambah_harga_satuan'] : 0;
            $totalNilai = isset($validated['tambah_total_nilai']) ? (float) $validated['tambah_total_nilai'] : 0;

            if ($hargaSatuan > 0 && $totalNilai <= 0) {
                $totalNilai = $vol * $hargaSatuan;
            } elseif ($totalNilai > 0 && $hargaSatuan <= 0 && $vol > 0) {
                $hargaSatuan = $totalNilai / $vol;
            } elseif ($hargaSatuan > 0 && $totalNilai > 0) {
                $totalNilai = $vol * $hargaSatuan;
            }

            \App\Models\Pendataan\PotensiProduksiPetani::updateOrCreate(
                [
                    'petani_id' => $petani->id,
                    'item_komoditas_id' => $validated['tambah_item_komoditas_id'],
                ],
                [
                    'estimasi_volume_panen' => $vol,
                    'satuan' => $satuan,
                    'periode_panen' => $validated['tambah_periode_panen'] ?: '3 - 4 bulan',
                    'harga_satuan' => $hargaSatuan,
                    'total_nilai' => $totalNilai,
                    'keterangan' => $validated['tambah_keterangan'] ?? null,
                ]
            );
        }

        return redirect()->route('pendataan.petani.show', $petani)->with('success', 'Data petani dan komoditas berhasil diperbarui!');
    }

    public function destroy(Petani $petani): RedirectResponse
    {
        $petani->items()->detach();
        $petani->delete();

        return redirect()->route('pendataan.petani.index')->with('success', 'Data petani berhasil dihapus!');
    }
}
