<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\ItemKomoditas;
use App\Models\Master\KategoriKomoditas;
use App\Models\Master\MasterKomoditas;
use App\Models\Pendataan\PotensiProduksiPetani;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanPotensiController extends Controller
{
    use ResolvesEntitasAktif;

    public function index(Request $request): View
    {
        $desaAktif = $this->desaAktif();
        $kategoriId = $request->query('kategori_id');
        $masterId = $request->query('master_id');
        $itemId = $request->query('item_id');

        $query = PotensiProduksiPetani::with(['petani.wilayah.parent.parent', 'item.masterKomoditas.kategori']);

        if ($desaAktif) {
            $query->whereHas('petani', fn($q) => $q->where('id_wilayah', $desaAktif->id));
        }

        if ($itemId) {
            $query->where('item_komoditas_id', $itemId);
        } elseif ($masterId) {
            $query->whereHas('item', fn($q) => $q->where('id_master_komoditas', $masterId));
        } elseif ($kategoriId) {
            $query->whereHas('item.masterKomoditas', fn($q) => $q->where('id_kategori', $kategoriId));
        }

        $items = $query->get();

        $kategoriList = KategoriKomoditas::where('is_active', true)->orderBy('nama')->get();
        $masterList = MasterKomoditas::where('is_active', true)->orderBy('nama')->get();
        $itemList = ItemKomoditas::where('is_active', true)->orderBy('nama')->get();

        return view('laporan.potensi.index', [
            'title' => 'Laporan Potensi Komoditas: ' . ($desaAktif?->nama ?? 'Desa'),
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
