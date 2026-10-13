<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Akuntansi\Coa;
use App\Models\Master\AsetTetap;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AsetTetapController extends Controller
{
    public function index(Request $request): View
    {
        $idEntitas = app('entitas_aktif');
        $q = $request->query('q', '');

        $query = AsetTetap::query()
            ->when($idEntitas, fn($query) => $query->where('id_entitas', $idEntitas))
            ->when($q, fn($query) => $query->where('nama_aset', 'like', "%{$q}%")
                                           ->orWhere('kode_aset', 'like', "%{$q}%"));

        $aset = $query->orderBy('tgl_perolehan', 'desc')->paginate(15)->withQueryString();

        return view('akuntansi.aset-tetap.index', compact('aset', 'q', 'idEntitas'));
    }

    public function create(): View
    {
        // Ambil COA terkait aset tetap (12xx), Akumulasi Penyusutan (12x9 atau sejenis), dan Biaya Penyusutan (5xxx)
        $idEntitas = app('entitas_aktif');
        
        $akunAset = Coa::where('kode_anak', 'like', '12%')->get();
        $akunAkum = Coa::where('kode_anak', 'like', '12%')->get(); // Biasanya 12xx untuk akumulasi
        $akunBiaya = Coa::where('kode_anak', 'like', '5%')->get();

        return view('akuntansi.aset-tetap.create', compact('akunAset', 'akunAkum', 'akunBiaya'));
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = app('entitas_aktif');
        
        $validated = $request->validate([
            'kode_aset'       => 'required|string|max:50',
            'nama_aset'       => 'required|string|max:255',
            'kategori'        => 'required|string|max:100',
            'tgl_perolehan'   => 'required|date',
            'nilai_perolehan' => 'required|numeric|min:0',
            'nilai_residu'    => 'required|numeric|min:0',
            'umur_bulan'      => 'required|integer|min:1',
            'kode_akun_aset'  => 'required|string|max:20',
            'kode_akun_akum'  => 'required|string|max:20',
            'kode_akun_biaya' => 'required|string|max:20',
        ]);

        $validated['id_entitas'] = $idEntitas;
        $validated['akum_penyusutan'] = 0;
        $validated['status'] = 'AKTIF';

        AsetTetap::create($validated);

        return redirect()->route('akuntansi.aset-tetap.index')
                         ->with('success', 'Aset Tetap berhasil ditambahkan.');
    }

    public function edit(AsetTetap $aset_tetap): View
    {
        $idEntitas = app('entitas_aktif');
        
        $akunAset = Coa::where('kode_anak', 'like', '12%')->get();
        $akunAkum = Coa::where('kode_anak', 'like', '12%')->get(); 
        $akunBiaya = Coa::where('kode_anak', 'like', '5%')->get();

        return view('akuntansi.aset-tetap.edit', [
            'aset' => $aset_tetap,
            'akunAset' => $akunAset,
            'akunAkum' => $akunAkum,
            'akunBiaya' => $akunBiaya
        ]);
    }

    public function update(Request $request, AsetTetap $aset_tetap): RedirectResponse
    {
        $validated = $request->validate([
            'nama_aset'       => 'required|string|max:255',
            'kategori'        => 'required|string|max:100',
            'nilai_residu'    => 'required|numeric|min:0',
            'umur_bulan'      => 'required|integer|min:1',
            'kode_akun_aset'  => 'required|string|max:20',
            'kode_akun_akum'  => 'required|string|max:20',
            'kode_akun_biaya' => 'required|string|max:20',
            'status'          => 'required|in:AKTIF,DIJUAL,RUSAK',
        ]);

        // Nilai perolehan & tgl perolehan sengaja tidak bisa diubah karena berdampak ke histori penyusutan
        $aset_tetap->update($validated);

        return redirect()->route('akuntansi.aset-tetap.index')
                         ->with('success', 'Data Aset Tetap berhasil diperbarui.');
    }
}
