<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** KERANGKA SAJA. Jurnal penutup + pembagian SHU dibangun saat pendalaman modul Finance. */
class TutupTahunController extends Controller
{
    public function index(): View
    {
        return view('akuntansi.tutup-tahun');
    }
}
