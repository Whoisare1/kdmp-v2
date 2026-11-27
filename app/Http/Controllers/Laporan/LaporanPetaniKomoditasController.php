<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\ItemKomoditas;
use App\Models\Master\KategoriKomoditas;
use App\Models\Master\MasterKomoditas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanPetaniKomoditasController extends Controller
{
    use ResolvesEntitasAktif;

    public function index(Request $request): View
    {
        $desaAktif = $this->desaAktif();
        $kategoriId = $request->query('kategori_id');
        $masterId = $request->query('master_id');
        $itemId = $request->query('item_id');

        $query = ItemKomoditas::with(['masterKomoditas.kategori', 'petani' => function ($q) use ($desaAktif) {
            if ($desaAktif) {
                $q->where('id_wilayah', $desaAktif->id);
            }
            $q->with('wilayah.parent.parent');
        }]);

        if ($itemId) {
            $query->where('id', $itemId);
        } elseif ($masterId) {
            $query->where('id_master_komoditas', $masterId);
        } elseif ($kategoriId) {
            $query->whereHas('masterKomoditas', fn($q) => $q->where('id_kategori', $kategoriId));
        }

        // Hanya tampilkan item yang memiliki petani terdaftar di desa ini
        $items = $query->get()->filter(fn($item) => $item->petani->count() > 0);

        $kategoriList = KategoriKomoditas::where('is_active', true)->orderBy('nama')->get();
        $masterList = MasterKomoditas::where('is_active', true)->orderBy('nama')->get();
        $itemList = ItemKomoditas::where('is_active', true)->orderBy('nama')->get();

        return view('laporan.petani_komoditas.index', [
            'title' => 'Laporan Petani per Komoditas: ' . ($desaAktif?->nama ?? 'Desa'),
            'items' => $items,
            'desaAktif' => $desaAktif,
            'kategoriList' => $kategoriList,
            'masterList' => $masterList,
            'itemList' => $itemList,
            'kategoriId' => $kategoriId,
            'masterId' => $masterId,
            'itemId' => $itemId,
        ]);
    }
}
