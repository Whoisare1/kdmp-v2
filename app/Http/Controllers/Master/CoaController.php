<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Akuntansi\Coa;

class CoaController extends ModuleCrudController
{
    protected string $model = Coa::class;
    protected string $view = 'master.coa';
    protected string $title = 'Chart of Accounts';
    protected string $routeBase = 'master.coa';
    protected int $perPage = 30;

    public function index(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        $query = Coa::query();

        if ($search = $request->query('q')) {
            $query->where('kode_anak', 'like', "%{$search}%")
                  ->orWhere('nama_rekening', 'like', "%{$search}%");
        }

        $items = $query->orderBy('kode_anak')->get();

        return view('master.coa.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
        ]);
    }
}
