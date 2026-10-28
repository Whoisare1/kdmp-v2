<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengisi container binding 'entitas_aktif' yang dibaca EntitasScope.
 *
 * Pengguna desa (id_entitas terisi) selalu terkunci ke desanya sendiri.
 * Pengguna pusat (id_entitas NULL) memilih tenant lewat session; kalau
 * belum memilih, akan bind null sehingga EntitasScope tidak memfilter apa pun
 * (mode konsolidasi, bisa melihat semua data).
 */
class SetEntitasAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if ($pengguna !== null) {
            $id = $pengguna->id_entitas ?? $request->session()->get('entitas_aktif_pilihan');
            
            // Selalu bind 'entitas_aktif' (walaupun null) untuk mencegah BindingResolutionException
            app()->bind('entitas_aktif', fn() => $id);
        }

        return $next($request);
    }
}
