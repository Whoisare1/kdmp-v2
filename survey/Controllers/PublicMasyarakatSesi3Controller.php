<?php

namespace Survei\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Master\Komoditas;
use App\Models\Survei\KonsumsiKeluarga;
use App\Models\Survei\MasyarakatDesa;
use App\Models\Survei\ProduksiProdusen;
use Survei\Models\SesiSurvei;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PublicMasyarakatSesi3Controller extends Controller
{
    /**
     * Tampilkan form pengisian Sesi 3 (Kebutuhan Konsumsi Keluarga).
     */
    public function show(string $token, int $id_masyarakat): View
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();
        $masyarakat = MasyarakatDesa::where('id', $id_masyarakat)
            ->where('id_sesi', $sesi->id)
            ->firstOrFail();

        // Ambil komoditas unik dari tabel ProduksiProdusen (Master Data Produsen) untuk sesi ini
        $produsenKomoditas = ProduksiProdusen::where('id_sesi', $sesi->id)
            ->select('nama_komoditas as nama', 'satuan')
            ->distinct()
            ->orderBy('nama')
            ->get();

        // Daftar komoditas pokok pangan keluarga standar untuk akurasi rekomendasi suara
        $defaultKomoditas = collect([
            (object)['nama' => 'Beras', 'satuan' => 'kg'],
            (object)['nama' => 'Minyak Goreng', 'satuan' => 'liter'],
            (object)['nama' => 'Gula Pasir', 'satuan' => 'kg'],
            (object)['nama' => 'Telur Ayam', 'satuan' => 'kg'],
            (object)['nama' => 'Daging Ayam', 'satuan' => 'kg'],
            (object)['nama' => 'Daging Sapi', 'satuan' => 'kg'],
            (object)['nama' => 'Bawang Merah', 'satuan' => 'kg'],
            (object)['nama' => 'Bawang Putih', 'satuan' => 'kg'],
            (object)['nama' => 'Cabai Rawit', 'satuan' => 'kg'],
            (object)['nama' => 'Cabai Merah', 'satuan' => 'kg'],
            (object)['nama' => 'Garam', 'satuan' => 'bungkus'],
            (object)['nama' => 'Tahu', 'satuan' => 'potong'],
            (object)['nama' => 'Tempe', 'satuan' => 'papan'],
            (object)['nama' => 'Ikan Segar', 'satuan' => 'kg'],
            (object)['nama' => 'Sayur Mayur', 'satuan' => 'ikat'],
            (object)['nama' => 'Sayur Bayam', 'satuan' => 'ikat'],
            (object)['nama' => 'Sayur Kangkung', 'satuan' => 'ikat'],
            (object)['nama' => 'Wortel', 'satuan' => 'kg'],
            (object)['nama' => 'Kentang', 'satuan' => 'kg'],
            (object)['nama' => 'Tepung Terigu', 'satuan' => 'kg'],
            (object)['nama' => 'Gas LPG 3kg', 'satuan' => 'tabung'],
            (object)['nama' => 'Susu', 'satuan' => 'kaleng'],
            (object)['nama' => 'Kopi / Teh', 'satuan' => 'bungkus'],
        ]);

        $komoditasList = $produsenKomoditas->concat($defaultKomoditas)->unique('nama')->values();
        
        // Ambil data konsumsi yang sudah tersimpan (jika ada)
        $konsumsiTersimpan = KonsumsiKeluarga::where('id_masyarakat', $masyarakat->id)
            ->get(); // Tidak di keyBy karena tidak ada ID, nanti di-handle dari array langsung di Alpine

        return view('survei.public.masyarakat_sesi3', [
            'token' => $token,
            'sesi' => $sesi,
            'masyarakat' => $masyarakat,
            'komoditasList' => $komoditasList,
            'konsumsiTersimpan' => $konsumsiTersimpan,
        ]);
    }

    /**
     * Simpan data Sesi 3.
     */
    public function store(Request $request, string $token, int $id_masyarakat): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();
        $masyarakat = MasyarakatDesa::where('id', $id_masyarakat)
            ->where('id_sesi', $sesi->id)
            ->firstOrFail();

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.nama_komoditas' => 'required|string|max:255',
            'items.*.jumlah' => 'required|numeric|min:0',
            'items.*.satuan' => 'nullable|string',
            'items.*.sumber_pemenuhan' => 'nullable|string|max:100',
            'items.*.lokasi_pemenuhan' => 'nullable|string|max:255',
            'items.*.harga' => 'nullable|numeric|min:0',
            'items.*.tipe_harga' => 'nullable|in:Satuan,Total',
        ]);

        DB::transaction(function () use ($validated, $masyarakat) {
            // Hapus yang lama untuk replace
            KonsumsiKeluarga::where('id_masyarakat', $masyarakat->id)->delete();

            foreach ($validated['items'] as $data) {
                if (!empty($data['jumlah']) && $data['jumlah'] > 0) {
                    KonsumsiKeluarga::create([
                        'id_masyarakat' => $masyarakat->id,
                        'nama_komoditas' => $data['nama_komoditas'],
                        'jumlah' => $data['jumlah'],
                        'satuan' => $data['satuan'] ?? 'kg',
                        'sumber_pemenuhan' => $data['sumber_pemenuhan'] ?? null,
                        'lokasi_pemenuhan' => $data['lokasi_pemenuhan'] ?? null,
                        'harga' => $data['harga'] ?? null,
                        'tipe_harga' => $data['tipe_harga'] ?? 'Total',
                    ]);
                }
            }
        });

        // Kuesioner Selesai, arahkan ke halaman Review
        return redirect()->route('survei.public.masyarakat.review.show', [
            'token' => $token,
            'id_masyarakat' => $masyarakat->id,
        ]);
    }
}
