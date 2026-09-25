<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Services\Finance\TutupTahunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TutupTahunController — mengelola finalisasi tutup tahun buku (Hard-Close).
 *
 * Alur pengguna:
 *   1. GET  /akuntansi/tutup-tahun  → lihat status pra-kondisi + preview laba/rugi
 *   2. POST /akuntansi/tutup-tahun  → eksekusi finalisasi (permanen, tidak bisa dibatalkan)
 *
 * Catatan Keamanan:
 *   Sebaiknya tambahkan middleware gate/policy untuk membatasi akses hanya ke
 *   peran "Manajer Pusat" atau "Ketua Finance". Implementasi middleware peran
 *   berada di luar scope Step 9 ini.
 */
class TutupTahunController extends Controller
{
    public function __construct(private TutupTahunService $service) {}

    /**
     * GET — Tampilkan status pra-kondisi + ringkasan laba/rugi tahun ini.
     */
    public function index(Request $request): View
    {
        // Default: tahun lalu (yang paling mungkin akan difinalisasi)
        $tahun = (int) $request->query('tahun', now()->subYear()->year);

        $idEntitas = app('entitas_aktif');

        if ($idEntitas === null) {
            return view('akuntansi.tutup-tahun', [
                'tahun' => $tahun,
                'praKondisi' => [],
                'semuaLulus' => false,
                'ringkasan' => ['total_pendapatan' => 0, 'total_hpp' => 0, 'total_biaya' => 0, 'net_non_operasional' => 0, 'laba_bersih' => 0],
                'message' => 'Harap pilih Entitas terlebih dahulu di pengaturan atas untuk melakukan Tutup Tahun.'
            ]);
        }

        $praKondisi   = $this->service->cekPraKondisi($tahun);
        $semuaLulus   = collect($praKondisi)->every(fn($v) => $v['lulus']);
        $ringkasan    = $this->service->ringkasanLabaRugi($tahun);

        return view('akuntansi.tutup-tahun', compact(
            'tahun', 'praKondisi', 'semuaLulus', 'ringkasan'
        ));
    }

    /**
     * POST — Eksekusi finalisasi tutup tahun. Tindakan ini PERMANEN.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'tahun'       => ['required', 'integer', 'min:2000', 'max:2099'],
            'konfirmasi'  => ['required', 'accepted'],   // checkbox konfirmasi wajib dicentang
        ]);

        $tahun = (int) $request->input('tahun');

        $idEntitas = app('entitas_aktif');
        if ($idEntitas === null) {
            return back()->with('error', 'Harap pilih Entitas terlebih dahulu untuk melakukan eksekusi Tutup Tahun.');
        }

        try {
            $this->service->tutupTahun($tahun);

            return redirect()
                ->route('akuntansi.tutup-tahun.index', ['tahun' => $tahun])
                ->with('success', "✅ Tahun buku {$tahun} berhasil difinalisasi. Jurnal Penutup otomatis telah dibuat.");

        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
