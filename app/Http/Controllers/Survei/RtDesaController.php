<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use App\Models\Survei\RtDesa;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\Request;

class RtDesaController extends Controller
{
    public function index(Request $request)
    {
        $query = RtDesa::with(['wilayah', 'sesi']);
        
        $user = auth()->user();
        if ($user->id_koperasi && $user->koperasi) {
            $query->where('id_wilayah', $user->koperasi->id_wilayah);
            $sesis = \App\Models\Survei\SesiSurvei::where('id_wilayah', $user->koperasi->id_wilayah)->orderBy('id', 'desc')->get();
        } else {
            $sesis = \App\Models\Survei\SesiSurvei::orderBy('id', 'desc')->get();
        }
        
        $selectedSesi = null;
        if ($request->has('id_sesi') && $request->id_sesi != '') {
            $query->where('id_sesi', $request->id_sesi);
            $selectedSesi = $sesis->where('id', $request->id_sesi)->first();
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_rt', 'like', "%{$search}%")
                  ->orWhere('dusun', 'like', "%{$search}%")
                  ->orWhere('nama_ketua_rt', 'like', "%{$search}%");
            });
        }

        $totalKk = (clone $query)->sum('total_kk_baseline');
        $totalJiwa = (clone $query)->sum('total_jiwa_baseline');

        $rts = $query->latest()->paginate(10);
        return view('survei.rt.index', compact('rts', 'sesis', 'selectedSesi', 'totalKk', 'totalJiwa'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->id_koperasi && $user->koperasi) {
            $wilayahs = \App\Models\Tenant\Wilayah::where('id', $user->koperasi->id_wilayah)->get();
            $sesis = \App\Models\Survei\SesiSurvei::where('id_wilayah', $user->koperasi->id_wilayah)->orderBy('id', 'desc')->get();
        } else {
            $wilayahs = \App\Models\Tenant\Wilayah::where('tingkat', 'Desa')->get();
            $sesis = \App\Models\Survei\SesiSurvei::orderBy('id', 'desc')->get();
        }
        return view('survei.rt.create', compact('wilayahs', 'sesis'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_wilayah' => 'required|exists:wilayah,id',
            'id_sesi' => 'required|exists:sesi_survei,id',
            'dusun' => 'nullable|string|max:255',
            'rw' => 'nullable|string|max:5',
            'nama_rt' => [
                'required', 
                'string', 
                'max:255',
                \Illuminate\Validation\Rule::unique('rt_desas')->where('id_wilayah', $request->id_wilayah)->where('dusun', $request->dusun)->where('id_sesi', $request->id_sesi)
            ],
            'nama_ketua_rt' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'total_kk_baseline' => 'nullable|integer|min:0',
            'total_jiwa_baseline' => 'nullable|integer|min:0',
        ], [
            'nama_rt.unique' => 'Nama RT ini sudah ada. Jika ini RT yang berbeda, harap isi nama Dusun/Dukuh untuk membedakannya.'
        ]);

        // Auto format RT/RW (e.g., "rt 5" -> "RT 05", "5" -> "05")
        $formatNumber = function($val, $prefix) {
            if (!$val) return null;
            $clean = preg_replace('/^' . $prefix . '\s*/i', '', trim($val));
            if (preg_match('/^\d$/', $clean)) {
                $clean = '0' . $clean;
            }
            return strtoupper($prefix) . ' ' . $clean;
        };

        $validated['nama_rt'] = $formatNumber($validated['nama_rt'], 'RT');
        $validated['rw'] = $formatNumber($validated['rw'], 'RW');

        $rt = RtDesa::create($validated);

        // Auto-update status sesi 1 menjadi sedang diisi jika belum
        if ($rt->sesi && in_array($rt->sesi->status_sesi1, ['belum_diisi', null])) {
            $rt->sesi->update(['status_sesi1' => 'sedang_diisi']);
        }

        return redirect()->route('survei.rt.index')->with('success', 'Data RT berhasil ditambahkan.');
    }

    public function edit(RtDesa $rt)
    {
        $user = auth()->user();
        if ($user->id_koperasi && $user->koperasi) {
            $wilayahs = \App\Models\Tenant\Wilayah::where('id', $user->koperasi->id_wilayah)->get();
            $sesis = \App\Models\Survei\SesiSurvei::where('id_wilayah', $user->koperasi->id_wilayah)->orderBy('id', 'desc')->get();
        } else {
            $wilayahs = \App\Models\Tenant\Wilayah::where('tingkat', 'Desa')->get();
            $sesis = \App\Models\Survei\SesiSurvei::orderBy('id', 'desc')->get();
        }
        return view('survei.rt.edit', compact('rt', 'wilayahs', 'sesis'));
    }

    public function update(Request $request, RtDesa $rt)
    {
        $validated = $request->validate([
            'id_wilayah' => 'required|exists:wilayah,id',
            'id_sesi' => 'required|exists:sesi_survei,id',
            'dusun' => 'nullable|string|max:255',
            'rw' => 'nullable|string|max:5',
            'nama_rt' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('rt_desas')->where('id_wilayah', $request->id_wilayah)->where('dusun', $request->dusun)->where('id_sesi', $request->id_sesi)->ignore($rt->id)
            ],
            'nama_ketua_rt' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'total_kk_baseline' => 'nullable|integer|min:0',
            'total_jiwa_baseline' => 'nullable|integer|min:0',
        ], [
            'nama_rt.unique' => 'Nama RT ini sudah ada. Jika ini RT yang berbeda, harap isi nama Dusun/Dukuh untuk membedakannya.'
        ]);

        // Auto format RT/RW
        $formatNumber = function($val, $prefix) {
            if (!$val) return null;
            $clean = preg_replace('/^' . $prefix . '\s*/i', '', trim($val));
            if (preg_match('/^\d$/', $clean)) {
                $clean = '0' . $clean;
            }
            return strtoupper($prefix) . ' ' . $clean;
        };

        $validated['nama_rt'] = $formatNumber($validated['nama_rt'], 'RT');
        $validated['rw'] = $formatNumber($validated['rw'], 'RW');

        $rt->update($validated);

        return redirect()->route('survei.rt.index')->with('success', 'Data RT berhasil diperbarui.');
    }

    public function destroy(RtDesa $rt)
    {
        $rt->delete();
        return redirect()->route('survei.rt.index')->with('success', 'Data RT berhasil dihapus.');
    }
}
