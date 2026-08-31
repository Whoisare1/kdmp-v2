<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Keuangan\KasTransaksi;

class KasTransaksiController extends ModuleCrudController
{
    protected string $model = KasTransaksi::class;
    protected string $view = 'keuangan.kas-transaksi';
    protected string $title = 'Kas Masuk / Keluar / Mutasi';
    protected string $routeBase = 'keuangan.kas-transaksi';
    protected array $withRelations = ['kasBank'];
}
