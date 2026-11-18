<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Models\Gudang\KartuStok;
use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Laporan mutasi stok, append-only — hanya index (read-only). */
class KartuStokController extends ModuleCrudController
{
    use ResolvesEntitasAktif;

    protected string $model = KartuStok::class;
    protected string $view = 'gudang.kartu-stok';
    protected string $title = 'Kartu Stok';
    protected string $routeBase = 'gudang.kartu-stok';
    protected array $withRelations = ['barang'];

    public function index(Request $request): View
    {
        $entitasId = $this->entitasAktifId();

        $filters = $request->validate([
            'id_gudang' => ['nullable', 'integer', 'exists:gudang,id_gudang'],
            'id_barang' => ['nullable', 'integer', 'exists:master_barang,id_barang'],
            'jenis_mutasi' => ['nullable', 'in:IN,OUT,ADJ_IN,ADJ_OUT'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
        ]);

        if (
            ! empty($filters['tanggal_mulai'])
            && ! empty($filters['tanggal_selesai'])
            && $filters['tanggal_mulai'] > $filters['tanggal_selesai']
        ) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            ]);
        }

        $query = KartuStok::query()->with(['barang.satuanDasar', 'gudang'])
            ->when($entitasId, fn ($q) => $q->where('id_entitas', $entitasId))
            ->when(
                $filters['id_gudang'] ?? null,
                fn ($query, $idGudang) => $query->where('id_gudang', $idGudang),
            )
            ->when(
                $filters['id_barang'] ?? null,
                fn ($query, $idBarang) => $query->where('id_barang', $idBarang),
            )
            ->when(
                $filters['jenis_mutasi'] ?? null,
                fn ($query, $jenisMutasi) => $query->where('jenis_mutasi', $jenisMutasi),
            )
            ->when(
                $filters['tanggal_mulai'] ?? null,
                fn ($query, $tanggalMulai) => $query->whereDate('tanggal', '>=', $tanggalMulai),
            )
            ->when(
                $filters['tanggal_selesai'] ?? null,
                fn ($query, $tanggalSelesai) => $query->whereDate('tanggal', '<=', $tanggalSelesai),
            );

        $items = $query->orderByDesc('tanggal')->orderByDesc('id_kartu')->paginate(20)->withQueryString();

        return view('gudang.kartu-stok.index', [
            'items' => $items,
            'gudang' => Gudang::query()
                ->when($entitasId, fn ($q) => $q->where('id_entitas', $entitasId))
                ->where('is_active', true)
                ->orderBy('nama_gudang')
                ->get(),
            'barang' => Barang::query()
                ->forEntitas($entitasId)
                ->where('is_active', true)
                ->orderBy('nama_barang')
                ->get(),
            'title' => $this->title,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $entitasId = $this->entitasAktifId();

        $filters = $request->validate([
            'id_gudang' => ['nullable', 'integer', 'exists:gudang,id_gudang'],
            'id_barang' => ['nullable', 'integer', 'exists:master_barang,id_barang'],
            'jenis_mutasi' => ['nullable', 'in:IN,OUT,ADJ_IN,ADJ_OUT'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
        ]);

        $query = KartuStok::query()->with(['barang.satuanDasar', 'gudang'])
            ->when($entitasId, fn ($q) => $q->where('id_entitas', $entitasId))
            ->when($filters['id_gudang'] ?? null, fn ($q, $v) => $q->where('id_gudang', $v))
            ->when($filters['id_barang'] ?? null, fn ($q, $v) => $q->where('id_barang', $v))
            ->when($filters['jenis_mutasi'] ?? null, fn ($q, $v) => $q->where('jenis_mutasi', $v))
            ->when($filters['tanggal_mulai'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($filters['tanggal_selesai'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v));

        $items = $query->orderBy('tanggal')->orderBy('id_kartu')->get();
        $filename = "Kartu_Stok_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['KARTU STOK PERSEDIAAN GUDANG']);
            fputcsv($handle, ['Tanggal Unduh: ' . date('d/m/Y H:i')]);
            fputcsv($handle, []);
            fputcsv($handle, [
                'ID Kartu', 'Tanggal', 'Gudang', 'Kode Barang', 'Nama Barang', 'Satuan',
                'Jenis Mutasi', 'Ref Tipe', 'Ref ID', 'Qty Masuk', 'Qty Keluar',
                'Harga Satuan / HPP (Rp)', 'Nilai Mutasi (Rp)', 'Saldo Qty', 'Saldo Nilai (Rp)', 'ID Jurnal'
            ]);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->id_kartu,
                    $item->tanggal ? $item->tanggal->format('Y-m-d') : '',
                    $item->gudang?->nama_gudang ?? '-',
                    $item->barang?->kode_barang ?? '-',
                    $item->barang?->nama_barang ?? '-',
                    $item->barang?->satuanDasar?->nama_satuan ?? '',
                    $item->jenis_mutasi,
                    $item->ref_tipe,
                    $item->ref_id,
                    number_format((float) $item->qty_masuk, 4, '.', ''),
                    number_format((float) $item->qty_keluar, 4, '.', ''),
                    number_format((float) $item->harga_satuan, 2, '.', ''),
                    number_format((float) $item->nilai_mutasi, 2, '.', ''),
                    number_format((float) $item->saldo_qty, 4, '.', ''),
                    number_format((float) $item->saldo_nilai, 2, '.', ''),
                    $item->id_jurnal ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
