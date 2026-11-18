<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengisi container binding 'entitas_aktif' yang dibaca EntitasScope.
 *
 * Pengguna desa (id_entitas terisi) selalu terkunci ke desanya sendiri.
 * Pengguna pusat/super admin memilih tenant lewat session; default otomatis
 * diarahkan ke entitas approved pertama agar seluruh modul langsung dapat diakses.
 */
class SetEntitasAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if ($pengguna !== null) {
            $id = $pengguna->id_entitas;

            if ($id === null) {
                if ($request->session()->has('entitas_aktif_pilihan')) {
                    $id = $request->session()->get('entitas_aktif_pilihan');
                } elseif (! $request->session()->get('entitas_global_mode')) {
                    // Default pertama kali login: pilihkan entitas approved pertama
                    $id = \App\Models\Tenant\Entitas::where('status', 'APPROVED')->value('id_entitas')
                        ?? \App\Models\Tenant\Entitas::value('id_entitas');
                    if ($id) {
                        $request->session()->put('entitas_aktif_pilihan', $id);
                    }
                }
            }
            
            // Selalu bind 'entitas_aktif' (walaupun null) untuk mencegah BindingResolutionException
            app()->bind('entitas_aktif', fn() => $id);
        }

        return $next($request);
    }
}
