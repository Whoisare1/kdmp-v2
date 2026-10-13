<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Piutang;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Buku pembantu piutang — dibuat OTOMATIS oleh service transaksi, bukan CRUD manual. Read-only di sini. */
class PiutangController extends Controller
{
    public function index(Request $request): View
    {
        $idEntitas = app('entitas_aktif');

        $query = Piutang::with(['pihak'])
            ->when($idEntitas, fn($q) => $q->where('id_entitas', $idEntitas));

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($q = $request->query('q')) {
            $query->whereHas('pihak', fn ($p) => $p->where('nama', 'like', "%{$q}%"));
        }

        $items = $query->orderBy('tgl_jatuh_tempo')->paginate(15)->withQueryString();

        return view('keuangan.piutang.index', compact('items'));
    }

    public function show(Piutang $piutang): View
    {
        $idEntitas = app('entitas_aktif');

        if ($idEntitas) {
            abort_if($piutang->id_entitas != $idEntitas, 403);
        }

        $piutang->load('pihak');

        return view('keuangan.piutang.show', ['item' => $piutang]);
    }
}
