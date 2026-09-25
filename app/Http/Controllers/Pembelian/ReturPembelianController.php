<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Pembelian\ReturPembelian;
use App\Models\Pembelian\Pembelian;
use App\Services\PembelianService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ReturPembelianController extends ModuleCrudController
{
    protected string $model = ReturPembelian::class;
    protected string $view = 'pembelian.retur';
    protected string $title = 'Retur Pembelian';
    protected string $routeBase = 'pembelian.retur';
    protected array $withRelations = ['pembelian'];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_pembelian' => 'required|exists:pembelian,id_pembelian',
            'jenis_penyelesaian' => 'required|in:uang,potong_hutang,ganti_barang',
            'alasan' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:master_barang,id_barang',
            'items.*.qty_dasar' => 'required|numeric|min:0.01',
            'items.*.hpp_rata2' => 'nullable|numeric|min:0',
        ]);

        try {
            $pembelian = Pembelian::findOrFail($validated['id_pembelian']);
            PembelianService::createRetur(
                $pembelian,
                $validated['items'],
                $validated['jenis_penyelesaian'],
                $validated['alasan'] ?? '',
            );

            return redirect()->route('pembelian.show-retur', $pembelian)
                ->with('success', 'Retur pembelian berhasil diajukan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, ReturPembelian $retur): RedirectResponse
    {
        try {
            PembelianService::approveRetur($retur, $request->integer('id_kas_bank') ?: null);

            return back()->with('success', 'Retur pembelian berhasil diproses.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
