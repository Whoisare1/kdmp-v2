<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\Stok;
use App\Services\StokMinimumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Laporan posisi stok on-hand + HPP moving average saat ini — read-only. */
class StokController extends ModuleCrudController
{
    public function __construct(private StokMinimumService $stokMinimum) {}

    protected string $model = Stok::class;
    protected string $view = 'gudang.stok';
    protected string $title = 'Posisi Stok';
    protected string $routeBase = 'gudang.stok';
    protected array $withRelations = ['gudang', 'barang'];

    public function index(Request $request): View
    {
        $entitasId = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);
        $entitasId = $entitasId !== null ? (int) $entitasId : null;

        $saldoPerBarang = $this->stokMinimum->tersediaPerBarang($entitasId);
        $filterStatus = $request->query('status');

        if ($filterStatus === 'menipis') {
            $ringkasanStok = DB::table('stok')
                ->join('gudang as gudang_ringkasan', 'gudang_ringkasan.id_gudang', '=', 'stok.id_gudang')
                ->select('gudang_ringkasan.id_entitas', 'stok.id_barang')
                ->selectRaw('SUM(stok.qty_on_hand) as qty_on_hand')
                ->selectRaw('SUM(stok.qty_reserved) as qty_reserved')
                ->selectRaw('SUM(stok.nilai_persediaan) as nilai_persediaan')
                ->selectRaw('CASE WHEN SUM(stok.qty_on_hand) > 0 THEN SUM(stok.nilai_persediaan) / SUM(stok.qty_on_hand) ELSE 0 END as hpp_rata2')
                ->groupBy('gudang_ringkasan.id_entitas', 'stok.id_barang');

            $query = Stok::query()
                ->from('barang_per_koperasi as pengaturan')
                ->join('entitas as entitas_stok', 'entitas_stok.id_entitas', '=', 'pengaturan.id_entitas')
                ->join('master_barang as barang_minimum', 'barang_minimum.id_barang', '=', 'pengaturan.id_barang')
                ->leftJoinSub($ringkasanStok, 'ringkasan_stok', function ($join) {
                    $join->on('ringkasan_stok.id_entitas', '=', 'pengaturan.id_entitas')
                        ->on('ringkasan_stok.id_barang', '=', 'pengaturan.id_barang');
                })
                ->leftJoinSub($saldoPerBarang, 'saldo_per_barang', function ($join) {
                    $join->on('saldo_per_barang.id_entitas', '=', 'pengaturan.id_entitas')
                        ->on('saldo_per_barang.id_barang', '=', 'pengaturan.id_barang');
                })
                ->where('pengaturan.stok_minimum', '>', 0)
                ->whereRaw('COALESCE(saldo_per_barang.qty_tersedia, 0) <= pengaturan.stok_minimum')
                ->selectRaw('
                    NULL as id_gudang,
                    pengaturan.id_barang as id_barang,
                    COALESCE(ringkasan_stok.qty_on_hand, 0) as qty_on_hand,
                    COALESCE(ringkasan_stok.qty_reserved, 0) as qty_reserved,
                    COALESCE(ringkasan_stok.hpp_rata2, 0) as hpp_rata2,
                    COALESCE(ringkasan_stok.nilai_persediaan, 0) as nilai_persediaan,
                    pengaturan.stok_minimum,
                    COALESCE(saldo_per_barang.qty_tersedia, 0) as qty_tersedia_koperasi,
                    entitas_stok.nama_entitas as nama_entitas
                ')
                ->with(['gudang', 'barang.satuanDasar']);
        } else {
            $query = Stok::query()
                ->leftJoin('gudang as gudang_stok', 'gudang_stok.id_gudang', '=', 'stok.id_gudang')
                ->leftJoin('barang_per_koperasi', function ($join) {
                    $join->on('barang_per_koperasi.id_barang', '=', 'stok.id_barang')
                        ->on('barang_per_koperasi.id_entitas', '=', 'gudang_stok.id_entitas');
                })
                ->leftJoinSub($saldoPerBarang, 'saldo_per_barang', function ($join) {
                    $join->on('saldo_per_barang.id_entitas', '=', 'gudang_stok.id_entitas')
                        ->on('saldo_per_barang.id_barang', '=', 'stok.id_barang');
                })
                ->select(
                    'stok.*',
                    'barang_per_koperasi.stok_minimum',
                    'saldo_per_barang.qty_tersedia as qty_tersedia_koperasi',
                )
                ->with(['gudang', 'barang.satuanDasar']);
        }

        if ($entitasId !== null) {
            $query->where($filterStatus === 'menipis' ? 'pengaturan.id_entitas' : 'gudang_stok.id_entitas', $entitasId);
        }

        if ($search = $request->query('q')) {
            if ($filterStatus === 'menipis') {
                $query->where(function ($q) use ($search) {
                    $q->where('barang_minimum.nama_barang', 'like', "%{$search}%")
                        ->orWhere('entitas_stok.nama_entitas', 'like', "%{$search}%");
                });
            } else {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('barang', fn ($b) => $b->where('nama_barang', 'like', "%{$search}%"))
                        ->orWhereHas('gudang', fn ($g) => $g->where('nama_gudang', 'like', "%{$search}%"));
                });
            }
        }

        // Stok punya composite primary key, jadi tidak bisa sort by ID standar
        $items = $query->orderBy($filterStatus === 'menipis' ? 'pengaturan.id_entitas' : 'gudang_stok.id_gudang')
            ->orderBy($filterStatus === 'menipis' ? 'pengaturan.id_barang' : 'stok.id_barang')
            ->paginate(15)
            ->withQueryString();

        return view('gudang.stok.index', [
            'items' => $items,
            'title' => $this->title,
            'filterStatus' => $filterStatus,
        ]);
    }
}
