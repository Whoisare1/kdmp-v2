<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Hutang;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Buku pembantu hutang — dibuat OTOMATIS oleh service transaksi. Read-only di sini. */
class HutangController extends Controller
{
    public function index(Request $request): View
    {
        $idEntitas = app('entitas_aktif');

        $query = Hutang::with(['pihak'])
            ->when($idEntitas, fn($q) => $q->where('id_entitas', $idEntitas));

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($q = $request->query('q')) {
            $query->whereHas('pihak', fn ($p) => $p->where('nama', 'like', "%{$q}%"));
        }

        $items = $query->orderBy('tgl_jatuh_tempo')->paginate(15)->withQueryString();

        return view('keuangan.hutang.index', compact('items'));
    }

    public function show(Hutang $hutang): View
    {
        $idEntitas = app('entitas_aktif');

        if ($idEntitas) {
            abort_if($hutang->id_entitas != $idEntitas, 403);
        }

        $hutang->load('pihak');

        return view('keuangan.hutang.show', ['item' => $hutang]);
    }
}
