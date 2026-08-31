<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * KERANGKA SAJA. Delapan validasi tutup bulan (TutupBukuService) dibangun
 * saat pendalaman modul Finance — lihat Bagian 15 dokumen pembelajaran.
 */
class TutupBulanController extends Controller
{
    public function index(): View
    {
        return view('akuntansi.tutup-bulan');
    }
}
