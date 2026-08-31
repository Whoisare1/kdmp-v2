<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * KERANGKA SAJA. Neraca, Laba Rugi, Neraca Saldo, Arus Kas dibangun saat
 * pendalaman modul Finance — lihat Bagian 16 dokumen pembelajaran.
 */
class LaporanController extends Controller
{
    public function neraca(): View
    {
        return view('akuntansi.laporan.placeholder', ['judul' => 'Neraca']);
    }

    public function labaRugi(): View
    {
        return view('akuntansi.laporan.placeholder', ['judul' => 'Laba Rugi']);
    }

    public function neracaSaldo(): View
    {
        return view('akuntansi.laporan.placeholder', ['judul' => 'Neraca Saldo']);
    }

    public function arusKas(): View
    {
        return view('akuntansi.laporan.placeholder', ['judul' => 'Arus Kas']);
    }
}
