<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Tenant\Entitas;

class EntitasController extends ModuleCrudController
{
    protected string $model = Entitas::class;
    protected string $view = 'master.koperasi';
    protected string $title = 'Koperasi Desa';
    protected string $routeBase = 'master.koperasi';
}

