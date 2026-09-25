<?php

namespace Survei\Controllers;

use App\Http\Controllers\Concerns\ModuleCrudController;
use Survei\Models\Pertanyaan;

class PertanyaanController extends ModuleCrudController
{
    protected string $model = Pertanyaan::class;
    protected string $view = 'survei.pertanyaan';
    protected string $title = 'Pertanyaan Survei';
    protected string $routeBase = 'survei.pertanyaan';
    protected array $withRelations = ['modul'];
}
