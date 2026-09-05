<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class GudangDashboardController extends Controller
{
    public function index(): View
    {
        return view('gudang.index');
    }
}
