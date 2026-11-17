<?php

namespace App\Http\Controllers\Konsinyasi;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Baca dari view SQL v_rekonsiliasi_konsinyasi (dibuat di migrasi jurnal) —
 * hanya menampilkan baris di mana piutang pemilik != hutang penerima.
 * Idealnya daftar ini SELALU kosong. Lihat validasi tutup bulan #8.
 */
class RekonsiliasiController extends Controller
{
    public function index(): View
    {
        $entitasId = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);
        abort_if(! $entitasId, 403, 'Pilih entitas aktif untuk melihat rekonsiliasi konsinyasi.');

        $selisih = DB::table('v_rekonsiliasi_konsinyasi')
            ->where(function ($query) use ($entitasId): void {
                $query->where('id_entitas_pemilik', $entitasId)
                    ->orWhere('id_entitas_penerima', $entitasId);
            })
            ->get();

        return view('konsinyasi.rekonsiliasi', ['selisih' => $selisih]);
    }
}
