<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Gudang\KartuStok;
use App\Models\Master\Barang;
use App\Models\Tenant\Entitas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Laporan mutasi kartu stok (audit trail persediaan perpetual moving average). */
class KartuStokController extends ModuleCrudController
{
    protected string $model = KartuStok::class;
    protected string $view = 'gudang.kartu-stok';
    protected string $title = 'Kartu Stok Persediaan';
    protected string $routeBase = 'gudang.kartu-stok';
    protected array $withRelations = ['barang.satuanDasar', 'petugas'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'id_barang' => ['nullable', 'integer', 'exists:master_barang,id_barang'],
            'jenis_mutasi' => ['nullable', 'in:IN,OUT,ADJ_IN,ADJ_OUT'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
        ]);

        $tanggalMulai = $filters['tanggal_mulai'] ?? null;
        $tanggalSelesai = $filters['tanggal_selesai'] ?? null;

        // Jika user memilih filter Bulan & Tahun
        if (! empty($filters['bulan']) && ! empty($filters['tahun'])) {
            $startDate = Carbon::createFromDate((int) $filters['tahun'], (int) $filters['bulan'], 1)->startOfMonth();
            $endDate = (clone $startDate)->endOfMonth();
            $tanggalMulai = $startDate->toDateString();
            $tanggalSelesai = $endDate->toDateString();
        } elseif (! empty($filters['tahun']) && empty($filters['bulan'])) {
            $startDate = Carbon::createFromDate((int) $filters['tahun'], 1, 1)->startOfYear();
            $endDate = (clone $startDate)->endOfYear();
            $tanggalMulai = $startDate->toDateString();
            $tanggalSelesai = $endDate->toDateString();
        }

        if (! empty($tanggalMulai) && ! empty($tanggalSelesai) && $tanggalMulai > $tanggalSelesai) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            ]);
        }

        $query = KartuStok::query()
            ->with(['barang.satuanDasar', 'petugas'])
            ->when(
                $filters['id_barang'] ?? null,
                fn ($query, $idBarang) => $query->where('id_barang', $idBarang),
            )
            ->when(
                $filters['jenis_mutasi'] ?? null,
                fn ($query, $jenisMutasi) => $query->where('jenis_mutasi', $jenisMutasi),
            )
            ->when(
                $tanggalMulai,
                fn ($query, $tglMulai) => $query->whereDate('tanggal', '>=', $tglMulai),
            )
            ->when(
                $tanggalSelesai,
                fn ($query, $tglSelesai) => $query->whereDate('tanggal', '<=', $tglSelesai),
            );

        // Jika sedang mencetak (request print=1), tampilkan seluruh data tanpa pagination
        if ($request->query('print') === '1') {
            $items = $query->orderBy('tanggal')->orderBy('id_kartu')->get();
        } else {
            $items = $query->orderByDesc('tanggal')->orderByDesc('id_kartu')->paginate(25)->withQueryString();
        }

        $barangList = Barang::query()->where('is_active', true)->orderBy('nama_barang')->get();
        $selectedBarang = ! empty($filters['id_barang']) ? Barang::query()->with('satuanDasar')->find($filters['id_barang']) : null;
        $entitasAktifId = auth()->user()->id_entitas ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null);
        $entitas = $entitasAktifId ? Entitas::query()->find($entitasAktifId) : null;

        return view('gudang.kartu-stok.index', [
            'items' => $items,
            'barangList' => $barangList,
            'selectedBarang' => $selectedBarang,
            'entitas' => $entitas,
            'filters' => $filters,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'title' => $this->title,
        ]);
    }

    public function show(int|string $id): View
    {
        $item = KartuStok::query()
            ->with(['barang.satuanDasar', 'petugas', 'gudang'])
            ->findOrFail($id);

        return view('gudang.kartu-stok.show', [
            'item' => $item,
            'title' => 'Rincian Mutasi Kartu Stok #' . $item->id_kartu,
        ]);
    }
}
