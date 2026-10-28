<?php

namespace App\Http\Middleware;

use App\Services\Finance\ProfilPenutupan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi fitur pembagian SHU hanya untuk entitas Koperasi.
 * Entitas lain (BUMDes, kelompok tani, UMKM) dialokasikan otomatis saat Tutup Tahun.
 */
class HanyaKoperasi
{
    public function handle(Request $request, Closure $next): Response
    {
        $idEntitas = app()->bound('entitas_aktif') ? app('entitas_aktif') : null;

        if ($idEntitas && !ProfilPenutupan::untuk((int) $idEntitas)->isDistribusi()) {
            abort(403, 'Konfigurasi SHU hanya untuk Koperasi. Entitas ini dialokasikan otomatis saat Tutup Tahun.');
        }

        return $next($request);
    }
}
