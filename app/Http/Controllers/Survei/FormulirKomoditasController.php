<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Master\ItemKomoditas;
use App\Models\Master\KategoriKomoditas;
use App\Models\Pendataan\Petani;
use App\Models\Pendataan\PotensiProduksiPetani;
use App\Models\Survei\Pertanyaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FormulirKomoditasController extends Controller
{
    use ResolvesEntitasAktif;

    public function create(): View
    {
        $desaAktif = $this->desaAktif();
        $kategoriList = KategoriKomoditas::with('masterKomoditas.items')->where('is_active', true)->orderBy('nama')->get();
        $pertanyaanList = Pertanyaan::where('jenis_pendataan', 'komoditas')->where('is_active', true)->orderBy('urutan')->get();

        // Daftar petani desa ini untuk opsi autocomplete jika sudah ada
        $petaniExisting = $desaAktif ? Petani::where('id_wilayah', $desaAktif->id)->orderBy('nama_lengkap')->get() : collect();

        return view('survei.formulir.komoditas', [
            'title' => 'Formulir Potensi Komoditas Petani: ' . ($desaAktif?->nama ?? 'Desa'),
            'desaAktif' => $desaAktif,
            'kategoriList' => $kategoriList,
            'pertanyaanList' => $pertanyaanList,
            'petaniExisting' => $petaniExisting,
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
            'item_komoditas_id' => ['required', 'exists:item_komoditas,id'],
            'luas_lahan_ha' => ['nullable', 'numeric', 'min:0'],
            'estimasi_volume_panen' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:30'],
            'periode_panen' => ['required', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $desaAktif) {
            // Petani otomatis terkunci ke desa aktif
            $petani = Petani::firstOrCreate(
                [
                    'nama_lengkap' => $validated['nama_lengkap'],
                    'id_wilayah' => $desaAktif->id,
                ],
                [
                    'nomor_hp' => $validated['nomor_hp'],
                    'alamat' => $validated['alamat'],
                    'rt' => $validated['rt'],
                    'rw' => $validated['rw'],
                    'desa' => $desaAktif->nama,
                    'kecamatan' => $desaAktif->parent?->nama ?? 'Juwana',
                    'kabupaten' => $desaAktif->parent?->parent?->nama ?? 'Pati',
                ]
            );

            // Simpan / update potensi produksi petani untuk komoditas ini
            PotensiProduksiPetani::updateOrCreate(
                [
                    'petani_id' => $petani->id,
                    'item_komoditas_id' => $validated['item_komoditas_id'],
                ],
                [
                    'luas_lahan_ha' => $validated['luas_lahan_ha'] ?? 0,
                    'estimasi_volume_panen' => $validated['estimasi_volume_panen'],
                    'satuan' => $validated['satuan'],
                    'periode_panen' => $validated['periode_panen'],
                    'keterangan' => $validated['keterangan'],
                ]
            );
        });

        return redirect()->route('survei.formulir.komoditas')->with('success', "Data potensi komoditas petani di {$desaAktif->nama} berhasil disimpan!");
    }
}
