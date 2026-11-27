<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\ItemKomoditas;
use App\Models\Pendataan\PendudukDesa;
use App\Models\Pendataan\PotensiProduksiPetani;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanAnalisisPotensiController extends Controller
{
    use ResolvesEntitasAktif;

    public function index(Request $request): View
    {
        $desaAktif = $this->desaAktif();
        $tahun = $request->query('tahun', date('Y'));

        // 1. Matriks Potensi Komoditas per Desa
        $potensiPerDesa = PotensiProduksiPetani::select(
            'petani.id_wilayah',
            'petani.desa',
            'item_komoditas.nama as nama_komoditas',
            'item_komoditas.id as item_id',
            DB::raw('SUM(potensi_produksi_petani.estimasi_volume_panen) as total_volume'),
            'potensi_produksi_petani.satuan',
            DB::raw('COUNT(DISTINCT petani.id) as jumlah_petani')
        )
        ->join('petani', 'potensi_produksi_petani.petani_id', '=', 'petani.id')
        ->join('item_komoditas', 'potensi_produksi_petani.item_komoditas_id', '=', 'item_komoditas.id')
        ->groupBy('petani.id_wilayah', 'petani.desa', 'item_komoditas.id', 'item_komoditas.nama', 'potensi_produksi_petani.satuan')
        ->get();

        // 2. Data Demografi Penduduk per Desa
        $pendudukPerDesa = PendudukDesa::where('tahun', $tahun)
            ->with('wilayah')
            ->get()
            ->keyBy('id_wilayah');

        // 3. Mengelompokkan komoditas unggulan tiap desa
        $komoditasUnggulan = [];
        $komoditasDesaAktif = [];

        foreach ($potensiPerDesa as $row) {
            $desaNama = $row->desa ?: ('Desa ID ' . $row->id_wilayah);
            $itemData = [
                'komoditas' => $row->nama_komoditas,
                'volume' => $row->total_volume,
                'satuan' => $row->satuan,
                'petani' => $row->jumlah_petani,
            ];

            $komoditasUnggulan[$desaNama][] = $itemData;

            if ($desaAktif && ($row->id_wilayah == $desaAktif->id || strtolower((string)$row->desa) === strtolower((string)$desaAktif->nama))) {
                $komoditasDesaAktif[] = $itemData;
            }
        }

        return view('laporan.analisis.index', [
            'title' => 'Analisis Potensi Desa & Peluang Barter Koperasi',
            'desaAktif' => $desaAktif,
            'komoditasDesaAktif' => $komoditasDesaAktif,
            'komoditasUnggulan' => $komoditasUnggulan,
            'pendudukPerDesa' => $pendudukPerDesa,
            'tahun' => $tahun,
        ]);
    }
}
