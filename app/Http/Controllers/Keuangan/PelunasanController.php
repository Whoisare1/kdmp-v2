<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Keuangan\Pelunasan;

/**
 * KERANGKA SAJA. Logika alokasi multi-piutang + posting jurnal TPI/BHU
 * (lihat PelunasanService di dokumen pembelajaran) dibangun saat pendalaman
 * modul Finance — termasuk perbaikan filter sumber_tipe supaya piutang
 * konsinyasi tidak bisa salah kredit akun (lihat Temuan #17.1).
 */
class PelunasanController extends ModuleCrudController
{
    protected string $model = Pelunasan::class;
    protected string $view = 'keuangan.pelunasan';
    protected string $title = 'Pelunasan Piutang / Pembayaran Hutang';
    protected string $routeBase = 'keuangan.pelunasan';
    protected array $withRelations = ['pihak', 'kasBank'];
}
