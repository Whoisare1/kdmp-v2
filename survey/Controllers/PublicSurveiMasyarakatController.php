<?php

namespace Survei\Controllers;

use App\Http\Controllers\Controller;
use Survei\Models\SesiSurvei;
use App\Models\Survei\MasyarakatDesa;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\Survei\AnggotaKeluarga;
use Illuminate\Support\Facades\DB;

class PublicSurveiMasyarakatController extends Controller
{
    /**
     * Tampilkan form 3 langkah (Data Diri, Alamat, Lokasi).
     */
    public function show(Request $request, string $token): View
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)
            ->with(['wilayah.parent.parent', 'petugas'])
            ->firstOrFail();

        $masyarakat = null;
        if ($request->has('id_masyarakat')) {
            $masyarakat = MasyarakatDesa::where('id', $request->query('id_masyarakat'))
                ->where('id_sesi', $sesi->id)
                ->first();
        }

        // Validasi ketersediaan sesi
        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, "Tautan survei ini sudah kedaluwarsa. Survei ini ditutup karena hanya berlaku untuk periode bulan " . $sesi->bulan . " tahun " . $sesi->tahun . ".");
        }

        // Ambil struktur wilayah Pati
        $wilayahTree = \App\Models\Tenant\Wilayah::where('tingkat', 'kab')
            ->with(['anak.anak']) // Load kecamatan dan desa
            ->get();

        return view('survei.public.masyarakat', [
            'sesi'  => $sesi,
            'token' => $token,
            'wilayahTree' => $wilayahTree,
            'masyarakat' => $masyarakat,
        ]);
    }

    /**
     * Simpan data dari form 3 langkah, lalu kembalikan URL redirect (via AJAX).
     */
    public function store(Request $request, string $token)
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();

        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            return response()->json(['success' => false, 'message' => 'Sesi kedaluwarsa'], 403);
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nomor_hp' => 'nullable|string|max:20',
            'umur' => 'required|integer|min:0|max:150',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'provinsi' => 'nullable|string|max:100',
            'kabupaten' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'desa' => 'nullable|string|max:100',
            'dusun' => 'nullable|string|max:100',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'nama_jalan' => 'nullable|string|max:255',
            'nomor_rumah' => 'nullable|string|max:50',
            'detail_alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $dataToSave = [
            'id_sesi' => $sesi->id,
            'id_wilayah' => $sesi->id_wilayah,
            'nama_kepala_keluarga' => $validated['nama'],
            'nomor_hp' => $validated['nomor_hp'],
            'umur' => $validated['umur'],
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'provinsi' => $validated['provinsi'],
            'kabupaten' => $validated['kabupaten'],
            'kecamatan' => $validated['kecamatan'],
            'desa' => $validated['desa'],
            'dusun' => $validated['dusun'],
            'rt' => $validated['rt'],
            'rw' => $validated['rw'],
            'nama_jalan' => $validated['nama_jalan'],
            'nomor_rumah' => $validated['nomor_rumah'],
            'detail_alamat' => $validated['detail_alamat'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ];

        if ($request->has('id_masyarakat')) {
            $masyarakat = MasyarakatDesa::where('id', $request->input('id_masyarakat'))
                ->where('id_sesi', $sesi->id)
                ->firstOrFail();
            $masyarakat->update($dataToSave);
        } else {
            $masyarakat = MasyarakatDesa::create($dataToSave);
        }

        $redirectUrl = route('survei.public.masyarakat.anggota.show', [
            'token' => $token,
            'id_masyarakat' => $masyarakat->id
        ]);

        if ($request->query('redirect') === 'review') {
            $redirectUrl = route('survei.public.masyarakat.review.show', [
                'token' => $token,
                'id_masyarakat' => $masyarakat->id
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data lokasi berhasil dicatat',
            'redirect_url' => $redirectUrl
        ]);
    }

    /**
     * Tampilkan form pengisian Anggota Keluarga.
     */
    public function showAnggota(string $token, int $id_masyarakat): View
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();
        $masyarakat = MasyarakatDesa::with('anggota')
            ->where('id', $id_masyarakat)
            ->where('id_sesi', $sesi->id)
            ->firstOrFail();

        return view('survei.public.masyarakat_anggota', [
            'sesi' => $sesi,
            'token' => $token,
            'masyarakat' => $masyarakat,
        ]);
    }

    /**
     * Simpan data daftar anggota keluarga.
     */
    public function storeAnggota(Request $request, string $token, int $id_masyarakat)
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();
        $masyarakat = MasyarakatDesa::where('id', $id_masyarakat)
            ->where('id_sesi', $sesi->id)
            ->firstOrFail();

        $validated = $request->validate([
            'anggota' => 'required|array|min:1',
            'anggota.*.nama' => 'required|string|max:255',
            'anggota.*.umur' => 'required|integer|min:0|max:150',
            'anggota.*.jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
        ]);

        DB::transaction(function () use ($validated, $masyarakat) {
            // Hapus anggota sebelumnya jika responden mengulang proses
            AnggotaKeluarga::where('id_masyarakat', $masyarakat->id)->delete();

            foreach ($validated['anggota'] as $agt) {
                AnggotaKeluarga::create([
                    'id_masyarakat' => $masyarakat->id,
                    'nama' => $agt['nama'],
                    'umur' => $agt['umur'],
                    'jenis_kelamin' => $agt['jenis_kelamin'],
                ]);
            }
        });

        // Cek redirect
        if ($request->query('redirect') === 'review') {
            return redirect()->route('survei.public.masyarakat.review.show', [
                'token' => $token,
                'id_masyarakat' => $masyarakat->id
            ])->with('success', 'Data Anggota Keluarga berhasil diperbarui.');
        }

        // Redirect ke Sesi 3 (Kebutuhan Konsumsi Keluarga) default
        return redirect()->route('survei.public.masyarakat.sesi3.show', [
            'token' => $token,
            'id_masyarakat' => $masyarakat->id
        ])->with('success', 'Data Anggota Keluarga berhasil disimpan. Silakan masukkan data konsumsi keluarga.');
    }
}
