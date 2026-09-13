<?php

namespace Survei\Controllers;

use App\Http\Controllers\Controller;
use Survei\Models\SesiSurvei;
use Survei\Models\NarasumberSesi;
use Survei\Models\ProduksiNarasumber;
use App\Models\Master\Komoditas;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controller untuk pengisian Sesi 2 (Potensi Produksi Desa) via link token publik.
 */
class PublicSesi2Controller extends Controller
{
    private const KATEGORI_NARASUMBER = [
        'Petani',
        'Peternak',
        'Nelayan',
        'Pembudidaya',
        'Pelaku Usaha',
        'Kelompok Tani',
        'Kepala Dusun',
        'Perangkat Desa',
        'Pengepul / Agregator',
        'Lainnya',
    ];

    public function show(string $token): View
    {
        $sesi = SesiSurvei::where('token_publik', $token)
            ->with([
                'wilayah.parent.parent',
                'petugas',
                'narasumber',
                'produksi.komoditas',
                'produksi.narasumber',
            ])->firstOrFail();

        $komoditasList = Komoditas::where('is_active', true)->orderBy('kategori')->orderBy('nama')->get();
        $rekap = $this->hitungRekapProduksiDesa($sesi);

        return view('survei.public.sesi2.index', [
            'sesi'               => $sesi,
            'token'              => $token,
            'narasumbers'        => $sesi->narasumber,
            'produksis'          => $sesi->produksi,
            'komoditasList'      => $komoditasList,
            'rekap'              => $rekap,
            'kategoriNarasumber' => self::KATEGORI_NARASUMBER,
            'kategoriKomoditas'  => ProduksiNarasumber::KATEGORI_KOMODITAS_LIST,
            'satuanList'         => ProduksiNarasumber::SATUAN_LIST,
            'bulanPanenList'     => ProduksiNarasumber::BULAN_PANEN_LIST,
            'polaPanenList'      => ProduksiNarasumber::POLA_PANEN_LIST,
            'kendalaList'        => ProduksiNarasumber::KENDALA_LIST,
            'periodeList'        => ProduksiNarasumber::PERIODE_LIST,
            'sumberDataList'     => ProduksiNarasumber::SUMBER_DATA_LIST,
            'labelStatusSesi2'   => SesiSurvei::LABEL_STATUS_SESI2,
        ]);
    }

    public function storeNarasumber(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_publik', $token)->firstOrFail();

        $validated = $request->validate([
            'nama_narasumber' => 'required|string|max:100',
            'kategori'        => 'required|string|max:50',
            'nomor_kontak'    => 'nullable|string|max:30',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        $validated['id_sesi'] = $sesi->id;
        $validated['status']  = 'draft';

        NarasumberSesi::create($validated);

        if ($sesi->status_sesi2 === 'belum_diisi') {
            $sesi->update(['status_sesi2' => 'sedang_diisi']);
        }

        return back()->with('success', 'Narasumber berhasil ditambahkan.');
    }

    private function resolveKomoditasInput(Request $request): void
    {
        $idKom = $request->input('id_komoditas');
        if (is_string($idKom) && str_starts_with($idKom, 'new:')) {
            $namaBaru = trim(substr($idKom, 4));
            if ($namaBaru !== '') {
                $katBaru = $request->input('pub_filter_kat') ?: ($request->input('filter_kategori_komoditas') ?: 'Pertanian');
                $kom = Komoditas::firstOrCreate(
                    ['nama' => $namaBaru],
                    ['kategori' => $katBaru, 'is_active' => true, 'satuan_standar' => 'kg']
                );
                $request->merge(['id_komoditas' => $kom->id]);
            }
        }
    }

    public function storeProduksi(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_publik', $token)->firstOrFail();
        $this->resolveKomoditasInput($request);

        $validated = $request->validate([
            'id_narasumber'             => 'required|exists:narasumber_sesi,id_narasumber',
            'id_komoditas'              => 'required|exists:komoditas,id',
            'jumlah_produksi'           => 'required|numeric|min:0',
            'satuan'                    => 'required|string|max:30',
            'periode'                   => 'required|string|max:50',
            'pola_panen'                => 'nullable|string|max:50',
            'bulan_panen_active'        => 'nullable|array',
            'bulan_panen_qty'           => 'nullable|array',
            'jumlah_dijual'             => 'nullable|numeric|min:0',
            'jumlah_dikonsumsi_sendiri' => 'nullable|numeric|min:0',
            'kendala_json'              => 'nullable|array',
            'kendala_produksi'          => 'nullable|string|max:1000',
            'sumber_data'               => 'required|string|max:100',
            'tanggal_data'              => 'nullable|date',
            'keterangan_sumber'         => 'nullable|string|max:500',
        ]);

        $validated['jumlah_dijual'] = $validated['jumlah_dijual'] ?? 0;
        $validated['jumlah_dikonsumsi_sendiri'] = $validated['jumlah_dikonsumsi_sendiri'] ?? 0;

        $activeMonths = $request->input('bulan_panen_active', []);
        $qtyMonths    = $request->input('bulan_panen_qty', []);
        $cleanQtyMap  = [];
        foreach ($activeMonths as $mCode) {
            $cleanQtyMap[$mCode] = isset($qtyMonths[$mCode]) ? (float)$qtyMonths[$mCode] : 0;
        }

        $validated['bulan_panen_json'] = [
            'pola_panen'  => $validated['pola_panen'] ?? 'Musiman',
            'months'      => array_values($activeMonths),
            'monthly_qty' => $cleanQtyMap,
        ];

        unset($validated['pola_panen'], $validated['bulan_panen_active'], $validated['bulan_panen_qty']);

        $validated['id_sesi'] = $sesi->id;
        $validated['jumlah_produksi_kg'] = ProduksiNarasumber::konversiKeKg($validated['jumlah_produksi'], $validated['satuan']);

        ProduksiNarasumber::create($validated);

        if ($sesi->status_sesi2 === 'belum_diisi') {
            $sesi->update(['status_sesi2' => 'sedang_diisi']);
        }

        return back()->with('success', 'Data produksi komoditas berhasil ditambahkan.');
    }

    public function updateProduksi(Request $request, string $token, ProduksiNarasumber $produksi): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_publik', $token)->firstOrFail();
        $this->resolveKomoditasInput($request);

        $validated = $request->validate([
            'id_narasumber'             => 'required|exists:narasumber_sesi,id_narasumber',
            'id_komoditas'              => 'required|exists:komoditas,id',
            'jumlah_produksi'           => 'required|numeric|min:0',
            'satuan'                    => 'required|string|max:30',
            'periode'                   => 'required|string|max:50',
            'pola_panen'                => 'nullable|string|max:50',
            'bulan_panen_active'        => 'nullable|array',
            'bulan_panen_qty'           => 'nullable|array',
            'jumlah_dijual'             => 'nullable|numeric|min:0',
            'jumlah_dikonsumsi_sendiri' => 'nullable|numeric|min:0',
            'kendala_json'              => 'nullable|array',
            'kendala_produksi'          => 'nullable|string|max:1000',
            'sumber_data'               => 'required|string|max:100',
            'tanggal_data'              => 'nullable|date',
            'keterangan_sumber'         => 'nullable|string|max:500',
        ]);

        $validated['jumlah_dijual'] = $validated['jumlah_dijual'] ?? 0;
        $validated['jumlah_dikonsumsi_sendiri'] = $validated['jumlah_dikonsumsi_sendiri'] ?? 0;

        $activeMonths = $request->input('bulan_panen_active', []);
        $qtyMonths    = $request->input('bulan_panen_qty', []);
        $cleanQtyMap  = [];
        foreach ($activeMonths as $mCode) {
            $cleanQtyMap[$mCode] = isset($qtyMonths[$mCode]) ? (float)$qtyMonths[$mCode] : 0;
        }

        $validated['bulan_panen_json'] = [
            'pola_panen'  => $validated['pola_panen'] ?? 'Musiman',
            'months'      => array_values($activeMonths),
            'monthly_qty' => $cleanQtyMap,
        ];

        unset($validated['pola_panen'], $validated['bulan_panen_active'], $validated['bulan_panen_qty']);

        $validated['jumlah_produksi_kg'] = ProduksiNarasumber::konversiKeKg($validated['jumlah_produksi'], $validated['satuan']);

        $produksi->update($validated);

        return back()->with('success', 'Data produksi komoditas berhasil diperbarui.');
    }

    public function destroyProduksi(string $token, ProduksiNarasumber $produksi): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_publik', $token)->firstOrFail();
        $produksi->delete();

        return back()->with('success', 'Data produksi komoditas berhasil dihapus.');
    }

    public function simpanDraft(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_publik', $token)->firstOrFail();
        $sesi->update(['status_sesi2' => 'sedang_diisi']);

        return back()->with('success', 'Draft Sesi 2 berhasil disimpan.');
    }

    public function selesaikan(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_publik', $token)->firstOrFail();

        if ($sesi->produksi->isEmpty()) {
            return back()->with('error', 'Sesi 2 tidak dapat diselesaikan karena belum ada data produksi komoditas.');
        }

        $sesi->update([
            'status_sesi2'     => 'selesai',
            'selesai_sesi2_at' => now(),
        ]);

        return back()->with('success', 'Sesi 2 — Potensi Produksi Desa telah berhasil diselesaikan!');
    }

    private function hitungRekapProduksiDesa(SesiSurvei $sesi): array
    {
        $rekap = [];

        foreach ($sesi->produksi as $p) {
            $komoditasNama = $p->komoditas?->nama ?? 'Lainnya';
            $kategori = $p->komoditas?->kategori ?? 'Umum';

            $prodKg = $p->jumlah_produksi_kg ?: ProduksiNarasumber::konversiKeKg($p->jumlah_produksi, $p->satuan);
            $dijualKg = ProduksiNarasumber::konversiKeKg($p->jumlah_dijual, $p->satuan);
            $konsumsiKg = ProduksiNarasumber::konversiKeKg($p->jumlah_dikonsumsi_sendiri, $p->satuan);

            $key = $komoditasNama . '___' . $kategori;

            if (!isset($rekap[$key])) {
                $rekap[$key] = [
                    'nama_komoditas'    => $komoditasNama,
                    'kategori'          => $kategori,
                    'total_produksi_kg' => 0,
                    'total_dijual_kg'   => 0,
                    'total_konsumsi_kg' => 0,
                    'total_sisa_kg'     => 0,
                    'bulan_panen_all'   => [],
                    'kendala_all'       => [],
                    'periode_list'      => [],
                    'narasumber_ids'    => [],
                ];
            }

            $rekap[$key]['total_produksi_kg'] += $prodKg;
            $rekap[$key]['total_dijual_kg']   += $dijualKg;
            $rekap[$key]['total_konsumsi_kg'] += $konsumsiKg;
            $rekap[$key]['narasumber_ids'][]   = $p->id_narasumber;
            $rekap[$key]['periode_list'][]     = $p->periode;

            // Extract bulan panen list
            $bJson = $p->bulan_panen_json;
            $monthsList = [];
            if (is_array($bJson)) {
                if (isset($bJson['months']) && is_array($bJson['months'])) {
                    $monthsList = $bJson['months'];
                } else {
                    $monthsList = array_filter($bJson, fn($v) => is_string($v));
                }
            }

            if (!empty($monthsList)) {
                $rekap[$key]['bulan_panen_all'] = array_unique(array_merge($rekap[$key]['bulan_panen_all'], $monthsList));
            }

            if (is_array($p->kendala_json)) {
                $rekap[$key]['kendala_all'] = array_unique(array_merge($rekap[$key]['kendala_all'], $p->kendala_json));
            }
        }

        foreach ($rekap as &$item) {
            $sisa = $item['total_produksi_kg'] - ($item['total_dijual_kg'] + $item['total_konsumsi_kg']);
            $item['total_sisa_kg'] = max(0, $sisa);
            $item['jumlah_narasumber'] = count(array_unique($item['narasumber_ids']));
            $uniquePeriodes = array_unique($item['periode_list']);
            $item['periode_str'] = implode(', ', $uniquePeriodes);
            $item['has_period_warning'] = count($uniquePeriodes) > 1;
            sort($item['bulan_panen_all']);
        }

        return array_values($rekap);
    }
}
