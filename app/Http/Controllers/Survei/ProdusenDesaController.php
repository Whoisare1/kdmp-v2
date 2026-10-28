<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use App\Models\Survei\ProdusenDesa;
use App\Models\Survei\ProduksiProdusen;
use App\Models\Tenant\Wilayah;
use Survei\Models\SesiSurvei;
use Illuminate\Http\Request;

class ProdusenDesaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $idWilayah = $user->id_koperasi && $user->koperasi ? $user->koperasi->id_wilayah : null;

        // Daftar semua sesi yang tersedia (untuk dropdown pilih periode)
        $sesiQuery = SesiSurvei::with('wilayah')->orderBy('tahun', 'desc')->orderBy('bulan', 'desc');
        if ($idWilayah) {
            $sesiQuery->where('id_wilayah', $idWilayah);
        }
        $daftarSesi = $sesiQuery->get();

        // Sesi yang dipilih user (via ?sesi_id=)
        $sesiDipilih = null;
        $produksiPeriode = collect();

        if ($request->filled('sesi_id')) {
            $q = SesiSurvei::where('id', $request->sesi_id);
            if ($idWilayah) $q->where('id_wilayah', $idWilayah);
            $sesiDipilih = $q->first();

            if ($sesiDipilih) {
                $produksiPeriode = ProduksiProdusen::where('id_sesi', $sesiDipilih->id)
                    ->orderBy('kategori')
                    ->orderBy('nama_responden')
                    ->get();
            }
        }

        // Daftar master produsen
        $query = ProdusenDesa::with('wilayah');
        if ($idWilayah) $query->where('id_wilayah', $idWilayah);
        if ($request->filled('search')) {
            $query->where('nama_anggota', 'like', '%' . $request->search . '%');
        }
        $produsens = $query->paginate(15)->withQueryString();

        return view('survei.produsen.index', compact('produsens', 'daftarSesi', 'sesiDipilih', 'produksiPeriode'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->id_koperasi && $user->koperasi) {
            $wilayahs = Wilayah::where('id', $user->koperasi->id_wilayah)->get();
        } else {
            $wilayahs = Wilayah::where('tingkat', 'Desa')->get();
        }
        return view('survei.produsen.create', compact('wilayahs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_wilayah'  => 'required|exists:wilayah,id',
            'kategori'    => 'required|in:Kelompok Tani,Ekraf',
            'nama_anggota'=> 'required|string|max:255',
            'no_wa'       => 'nullable|string|max:20',
        ]);

        ProdusenDesa::create($request->only(['id_wilayah', 'kategori', 'nama_anggota', 'no_wa']));

        return redirect()->route('survei.produsen.index')->with('success', 'Data Produsen berhasil ditambahkan.');
    }

    public function edit(ProdusenDesa $produsen)
    {
        $user = auth()->user();
        if ($user->id_koperasi && $user->koperasi) {
            $wilayahs = Wilayah::where('id', $user->koperasi->id_wilayah)->get();
        } else {
            $wilayahs = Wilayah::where('tingkat', 'Desa')->get();
        }
        return view('survei.produsen.edit', compact('produsen', 'wilayahs'));
    }

    public function update(Request $request, ProdusenDesa $produsen)
    {
        $request->validate([
            'id_wilayah'  => 'required|exists:wilayah,id',
            'kategori'    => 'required|in:Kelompok Tani,Ekraf',
            'nama_anggota'=> 'required|string|max:255',
            'no_wa'       => 'nullable|string|max:20',
        ]);

        $produsen->update($request->only(['id_wilayah', 'kategori', 'nama_anggota', 'no_wa']));

        return redirect()->route('survei.produsen.index')->with('success', 'Data Produsen berhasil diperbarui.');
    }

    public function destroy(ProdusenDesa $produsen)
    {
        $produsen->delete();
        return redirect()->route('survei.produsen.index')->with('success', 'Data Produsen berhasil dihapus.');
    }
}
