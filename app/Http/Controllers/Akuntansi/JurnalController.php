<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Akuntansi\JurnalHeader;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only: satu-satunya cara menulis jurnal adalah via JurnalService.
 * Controller ini hanya menampilkan daftar & detail jurnal yang sudah POSTED.
 */
class JurnalController extends Controller
{
    public function index(Request $request): View
    {
        $idEntitas = app('entitas_aktif');
        $tahun     = (int) $request->query('tahun', date('Y'));
        $bulan     = $request->query('bulan');   // nullable — jika kosong tampilkan semua bulan
        $q         = $request->query('q', '');
        $jenis     = $request->query('jenis', '');

        $query = JurnalHeader::query()
            ->when($idEntitas, fn($query) => $query->where('id_entitas', $idEntitas))
            ->where('periode_tahun', $tahun)
            ->when($bulan, fn($query) => $query->where('periode_bulan', (int) $bulan))
            ->when($jenis, fn($query) => $query->where('jenis_jurnal', $jenis))
            ->when($q, fn($query) => $query->where(function ($q2) use ($q) {
                $q2->where('no_jurnal', 'like', "%{$q}%")
                   ->orWhere('keterangan', 'like', "%{$q}%")
                   ->orWhere('kode_transaksi', 'like', "%{$q}%");
            }))
            ->orderByDesc('tanggal_jurnal')
            ->orderByDesc('id_jurnal');

        $jurnal = $query->paginate(20)->withQueryString();

        // Summary untuk header
        $totalPosted   = (clone $query)->where('status', 'POSTED')->count();
        $totalDebet    = (clone $query)->where('status', 'POSTED')->sum('total_debet');

        return view('akuntansi.jurnal.index', compact(
            'jurnal', 'tahun', 'bulan', 'q', 'jenis',
            'idEntitas', 'totalPosted', 'totalDebet'
        ));
    }

    public function show(int|string $id): View
    {
        $jurnal = JurnalHeader::with(['detail.akun'])->findOrFail($id);

        // Jika ada jurnal asal (pembalik), load juga
        $jurnalAsal = $jurnal->id_jurnal_asal
            ? JurnalHeader::find($jurnal->id_jurnal_asal)
            : null;

        // Jurnal pembalik yang mengacu ke jurnal ini
        $jurnalPembalik = JurnalHeader::where('id_jurnal_asal', $id)->first();

        return view('akuntansi.jurnal.show', compact('jurnal', 'jurnalAsal', 'jurnalPembalik'));
    }

    public function balik(Request $request, int|string $id, \App\Services\Finance\JurnalService $jurnalService)
    {
        $request->validate([
            'alasan' => 'required|string|min:5|max:255'
        ]);

        try {
            $pembalik = $jurnalService->balik((int) $id, $request->input('alasan'));
            return redirect()->route('akuntansi.jurnal.show', $pembalik->id_jurnal)
                             ->with('success', 'Jurnal berhasil dibalik via Stored Procedure! Anda sedang melihat Jurnal Pembalik.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membalik jurnal: ' . $e->getMessage());
        }
    }
}

