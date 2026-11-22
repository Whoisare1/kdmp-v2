<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PeranMiddleware
{
    /**
     * Handle an incoming request based on roles.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$peranDiizinkan
     */
    public function handle(Request $request, Closure $next, string ...$peranDiizinkan): Response
    {
        $pengguna = $request->user();

        if (! $pengguna) {
            abort(401, 'Silakan masuk terlebih dahulu.');
        }

        // 1. Super Administrator (Tingkat Pusat) selalu diizinkan
        if ($pengguna->isSuperAdmin()) {
            return $next($request);
        }

        // 2. Manajer adalah Administrator Desa/Koperasi -> selalu diizinkan untuk semua modul desanya
        if ($pengguna->hasPeran('manajer')) {
            return $next($request);
        }

        // 3. Periksa apakah pengguna memiliki salah satu peran yang diizinkan untuk route ini
        if ($pengguna->hasAnyPeran($peranDiizinkan)) {
            return $next($request);
        }

        $namaPeran = implode(', ', array_map(fn($p) => ucwords(str_replace('_', ' ', $p)), $peranDiizinkan));
        abort(403, "Akses ditolak. Halaman ini hanya untuk staf {$namaPeran} atau Manajer Koperasi.");
    }
}

