<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Services\StokBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class BatchController extends Controller
{
    public function klasifikasikanStokAwal(Request $request, int $idBatch, StokBatchService $stokBatch): RedirectResponse
    {
        $pengguna = $request->user();
        abort_unless(
            $pengguna && ($pengguna->hasPeran('admin_gudang') || $pengguna->hasPeran('manajer')),
            403,
            'Hanya Admin Gudang atau Manajer yang dapat melengkapi batch stok awal.',
        );

        $entitasId = $pengguna->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);
        abort_unless($entitasId, 403, 'Entitas aktif tidak ditemukan.');

        $data = $request->validate([
            'batches' => ['required', 'array', 'min:1'],
            'batches.*.kode_batch' => ['nullable', 'string', 'max:100'],
            'batches.*.tanggal_kedaluwarsa' => ['required', 'date'],
            'batches.*.qty' => ['required', 'numeric', 'regex:/^\\d{1,14}(?:\\.\\d{1,4})?$/', 'gt:0'],
        ]);

        try {
            $batchBaru = $stokBatch->klasifikasikanStokAwal(
                entitasId: (int) $entitasId,
                barangId: (int) DB::table('stok_batch')->where('id_batch', $idBatch)->value('id_barang'),
                batchAwalId: $idBatch,
                allocations: $data['batches'],
                createdBy: $pengguna->getAuthIdentifier(),
                ipAddress: $request->ip(),
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['batch' => $exception->getMessage()]);
        }

        return redirect()->route('gudang.batch.index')
            ->with('status', count($batchBaru).' batch stok awal berhasil dicatat tanpa mengubah jumlah stok gudang.');
    }

    public function index(Request $request): View
    {
        $entitasId = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);
        $status = $request->query('status');
        $query = DB::table('stok_batch')
            ->join('gudang', 'gudang.id_gudang', '=', 'stok_batch.id_gudang')
            ->join('master_barang', 'master_barang.id_barang', '=', 'stok_batch.id_barang')
            ->leftJoin('satuan', 'satuan.id', '=', 'master_barang.id_satuan_dasar')
            ->join('barang_per_koperasi as pengaturan', function ($join): void {
                $join->on('pengaturan.id_entitas', '=', 'gudang.id_entitas')
                    ->on('pengaturan.id_barang', '=', 'stok_batch.id_barang')
                    ->where('pengaturan.lacak_batch', true);
            })
            ->where('stok_batch.qty_on_hand', '>', 0)
            ->when(
                $entitasId !== null,
                fn ($query) => $query->where('gudang.id_entitas', $entitasId),
            )
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('master_barang.nama_barang', 'like', "%{$search}%")
                        ->orWhere('master_barang.kode_barang', 'like', "%{$search}%")
                        ->orWhere('stok_batch.kode_batch', 'like', "%{$search}%")
                        ->orWhere('gudang.nama_gudang', 'like', "%{$search}%");
                });
            });

        if ($status === 'segera') {
            $query->whereBetween('stok_batch.tanggal_kedaluwarsa', [
                now()->toDateString(),
                now()->addDays(30)->toDateString(),
            ]);
        } elseif ($status === 'kedaluwarsa') {
            $query->where('stok_batch.tanggal_kedaluwarsa', '<', now()->toDateString());
        } elseif ($status === 'tanpa-tanggal') {
            $query->whereNull('stok_batch.tanggal_kedaluwarsa');
        } else {
            $status = '';
        }

        $items = $query
            ->select([
                'stok_batch.id_batch',
                'stok_batch.kode_batch',
                'stok_batch.tanggal_kedaluwarsa',
                'stok_batch.qty_on_hand',
                'stok_batch.is_stok_awal',
                'gudang.nama_gudang',
                'master_barang.kode_barang',
                'master_barang.nama_barang',
                'satuan.kode_satuan',
            ])
            ->orderByRaw('stok_batch.tanggal_kedaluwarsa IS NULL')
            ->orderBy('stok_batch.tanggal_kedaluwarsa')
            ->orderBy('master_barang.nama_barang')
            ->paginate(20)
            ->withQueryString();

        return view('gudang.batch.index', [
            'items' => $items,
            'filterStatus' => $status,
            'title' => 'Batch dan Kedaluwarsa',
        ]);
    }
}
