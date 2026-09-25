<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user login dan tidak terikat entitas (id_entitas null), 
        // atau cek role super_admin secara spesifik
        if (!auth()->check() || auth()->user()->id_entitas !== null) {
            abort(403, 'Akses ditolak. Halaman ini hanya untuk Super Administrator.');
        }

        return $next($request);
    }
}
