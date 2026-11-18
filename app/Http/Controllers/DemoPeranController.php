<?php

namespace App\Http\Controllers;

use App\Services\DemoPeranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoPeranController extends Controller
{
    /**
     * Beralih peran demo sekaligus login ke akun staf sesuai peran & entitas yang dipilih.
     */
    public function switchPeran(Request $request): RedirectResponse
    {
        $katalog = DemoPeranService::katalogPeran();
        $validated = $request->validate([
            'peran' => ['required', 'string', 'in:' . implode(',', array_keys($katalog))],
            'id_entitas' => ['nullable', 'integer', 'exists:entitas,id_entitas'],
            'mode' => ['nullable', 'string', 'in:login,kartu'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $kodePeran = $validated['peran'];
        $infoPeran = $katalog[$kodePeran];
        $mode = $validated['mode'] ?? 'login';

        $currentUser = $request->user();
        $idEntitas = $validated['id_entitas']
            ?? ($currentUser?->id_entitas ?? $request->session()->get('entitas_aktif_pilihan'));

        if ($mode === 'kartu' && $currentUser) {
            $request->session()->put('demo_peran_aktif', $kodePeran);
            if ($idEntitas && $currentUser->isSuperAdmin()) {
                $request->session()->put('entitas_aktif_pilihan', (int) $idEntitas);
            }

            return redirect()
                ->route('dashboard', ['peran' => $kodePeran])
                ->with('info', "Tampilan dasbor & sidebar disinkronkan ke alur peran: {$infoPeran['nama']}.");
        }

        $targetUser = DemoPeranService::resolveOrCreateDemoUser(
            $kodePeran,
            $idEntitas ? (int) $idEntitas : null
        );

        Auth::guard('web')->login($targetUser);
        $request->session()->put('demo_peran_aktif', $kodePeran);

        if ($kodePeran === 'super_admin') {
            if (! empty($validated['id_entitas'])) {
                $request->session()->put('entitas_aktif_pilihan', (int) $validated['id_entitas']);
            }
        } else {
            $request->session()->forget('entitas_aktif_pilihan');
        }

        $namaEntitas = $targetUser->entitas?->nama_entitas ?? 'Pusat / Lintas Desa';

        return redirect()
            ->to($validated['redirect_to'] ?? route('dashboard', ['peran' => $kodePeran]))
            ->with('status', "Mode Demo Aktif: Login sebagai {$targetUser->nama} ({$infoPeran['nama']} — {$namaEntitas}). Alur dasbor & menu sidebar telah disinkronkan.");
    }
}

