<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Master\Komoditas;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EtalasePublikController extends Controller
{
    /**
     * Halaman publik & lintas-entitas: menampilkan informasi terbuka seperti
     * katalog komoditas, profil entitas koperasi desa, serta potensi surplus/defisit
     * antar-desa untuk barter/konsinyasi.
     *
     * Data sensitif internal (harga negosiasi, rincian PO/POS, stok fisik gudang,
     * buku kas, piutang/hutang, dan link token survei warga) TIDAK dipublikasikan di sini.
     */
    public function komoditas(Request $request): \Illuminate\Http\RedirectResponse|\Illuminate\View\View
    {
        // Jika pengguna sudah login, arahkan langsung ke tab katalog di Dasbor internal
        if (auth()->check()) {
            return redirect()->route('dashboard', ['tab' => 'katalog']);
        }

        $kategoriFilter = $request->query('kategori');
        $wilayahFilter = $request->query('wilayah');
        $cari = trim((string) $request->query('q', ''));

        $daftarEntitas = Entitas::withoutGlobalScopes()
            ->with('wilayah')
            ->where('is_active', true)
            ->orderBy('nama_entitas')
            ->get();

        $daftarWilayah = Wilayah::orderBy('nama')->get();

        $komoditasQuery = Komoditas::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('kategori')
            ->orderBy('nama');

        if ($kategoriFilter) {
            $komoditasQuery->where('kategori', $kategoriFilter);
        }
        if ($cari !== '') {
            $komoditasQuery->where('nama', 'like', "%{$cari}%");
        }

        $daftarKomoditas = $komoditasQuery->get();

        $kategoriTersedia = Komoditas::withoutGlobalScopes()
            ->where('is_active', true)
            ->select('kategori')
            ->distinct()
            ->pluck('kategori')
            ->filter()
            ->values();

        $periodeTerbaru = HasilKalkulasi::query()
            ->select('tahun', 'bulan')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->first();

        $tahunAktif = (int) ($request->query('tahun') ?: ($periodeTerbaru->tahun ?? date('Y')));
        $bulanAktif = (int) ($request->query('bulan') ?: ($periodeTerbaru->bulan ?? 1));

        $neracaQuery = HasilKalkulasi::with(['wilayah', 'komoditas'])
            ->where('tahun', $tahunAktif)
            ->where('bulan', $bulanAktif);

        if ($wilayahFilter) {
            $neracaQuery->where('id_wilayah', (int) $wilayahFilter);
        }
        if ($cari !== '') {
            $neracaQuery->whereHas('komoditas', fn ($q) => $q->where('nama', 'like', "%{$cari}%"));
        }
        if ($kategoriFilter) {
            $neracaQuery->whereHas('komoditas', fn ($q) => $q->where('kategori', $kategoriFilter));
        }

        $neracaLintasDesa = $neracaQuery
            ->orderBy('id_wilayah')
            ->orderByDesc('selisih')
            ->get();

        return view('publik.komoditas', compact(
            'daftarEntitas',
            'daftarWilayah',
            'daftarKomoditas',
            'kategoriTersedia',
            'neracaLintasDesa',
            'tahunAktif',
            'bulanAktif',
            'kategoriFilter',
            'wilayahFilter',
            'cari'
        ));
    }
}
