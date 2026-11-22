<?php

namespace App\Http\Controllers\Konsinyasi;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Konsinyasi\KartuKonsinyasi;
use App\Models\Konsinyasi\StokKonsinyasi;
use App\Models\Konsinyasi\SusutKonsinyasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SusutKonsinyasiController extends Controller
{
    use ResolvesEntitasAktif;

    public function index(Request $request): View
    {
        $entitasId = $this->requireEntitasAktifId();
        $filters = $request->validate([
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $items = SusutKonsinyasi::query()
            ->where(function ($query) use ($entitasId): void {
                $query->where('id_entitas_pemilik', $entitasId)
                    ->orWhere('id_entitas_pencatat', $entitasId);
            })
            ->with([
                'stokKonsinyasi.barang.satuanDasar',
                'stokKonsinyasi.kiriman.koperasiPemilik',
                'stokKonsinyasi.kiriman.pihakPemilik',
                'stokKonsinyasi.kiriman.koperasiPenerima',
                'entitasPencatat',
                'pengguna',
            ])
            ->when($filters['bulan'] ?? null, fn ($query, $bulan) => $query->whereMonth('tanggal', $bulan))
            ->when($filters['tahun'] ?? null, fn ($query, $tahun) => $query->whereYear('tanggal', $tahun))
            ->latest('id_susut_konsinyasi')
            ->paginate(15)
            ->withQueryString();

        return view('konsinyasi.susut.index', [
            'items' => $items,
            'filters' => $filters,
            'title' => 'Riwayat Susut Konsinyasi',
        ]);
    }

    public function create(Request $request): View
    {
        $entitasId = $this->requireEntitasAktifId();
        $items = $this->stokYangDapatDicatat($entitasId)
            ->with([
                'barang.satuanDasar',
                'kiriman.koperasiPemilik',
                'kiriman.pihakPemilik',
            ])
            ->orderBy('id_stok_konsinyasi')
            ->get();

        return view('konsinyasi.susut.create', [
            'items' => $items,
            'selectedId' => (int) $request->query('stok', 0),
            'title' => 'Catat Susut Barang Konsinyasi',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_stok_konsinyasi' => ['required', 'integer', 'exists:stok_konsinyasi,id_stok_konsinyasi'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kejadian' => ['required', 'in:rusak,susut_alami,hilang,kadaluarsa'],
            'qty' => ['required', 'numeric', 'regex:/^\\d{1,14}(?:\\.\\d{1,4})?$/', 'gt:0'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        $entitasId = $this->requireEntitasAktifId();

        $susut = DB::transaction(function () use ($validated, $entitasId): SusutKonsinyasi {
            $stok = StokKonsinyasi::query()
                ->with('kiriman')
                ->whereKey($validated['id_stok_konsinyasi'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((int) $stok->id_entitas_penerima === $entitasId, 403);
            if ($stok->kiriman?->status_posting !== 'T') {
                throw ValidationException::withMessages([
                    'id_stok_konsinyasi' => 'Susut hanya dapat dicatat untuk barang titipan yang sudah diposting.',
                ]);
            }
            if (bccomp((string) $stok->qty_sisa, (string) $validated['qty'], 4) < 0) {
                throw ValidationException::withMessages([
                    'qty' => 'Jumlah susut melebihi sisa stok konsinyasi.',
                ]);
            }

            $qtySusut = bcadd((string) $stok->qty_susut, (string) $validated['qty'], 4);
            $qtySisa = bcsub((string) $stok->qty_sisa, (string) $validated['qty'], 4);
            $hpp = (string) $stok->hpp_pemilik;
            $nilaiSusut = bcmul((string) $validated['qty'], $hpp, 2);
            $susut = SusutKonsinyasi::query()->create([
                'kode_susut' => 'SKN-'.Str::ulid(),
                'id_stok_konsinyasi' => $stok->id_stok_konsinyasi,
                'id_entitas_pemilik' => $stok->id_entitas_pemilik,
                'id_entitas_pencatat' => $entitasId,
                'id_pengguna' => auth()->id(),
                'tanggal' => $validated['tanggal'],
                'jenis_kejadian' => $validated['jenis_kejadian'],
                'qty' => $validated['qty'],
                'hpp_pemilik' => $hpp,
                'nilai_susut' => $nilaiSusut,
                'penanggung_susut' => $stok->kiriman->penanggung_susut,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $stok->update([
                'qty_susut' => $qtySusut,
                'qty_sisa' => $qtySisa,
                'status' => bccomp($qtySisa, '0', 4) === 0 ? 'habis' : 'aktif',
            ]);

            KartuKonsinyasi::query()->create([
                'id_stok_konsinyasi' => $stok->id_stok_konsinyasi,
                'tanggal' => $validated['tanggal'],
                'jenis_mutasi' => 'SUSUT',
                'ref_tipe' => 'SUSUT_KONSINYASI',
                'ref_id' => $susut->id_susut_konsinyasi,
                'qty' => $validated['qty'],
                'harga_titip_satuan' => $stok->harga_titip_satuan,
                'harga_jual_satuan' => $stok->harga_jual_satuan,
                'saldo_qty' => $qtySisa,
                'created_at' => now(),
            ]);

            return $susut;
        });

        return redirect()->route('konsinyasi.susut.index')
            ->with('success', "Susut {$susut->kode_susut} berhasil dicatat. Konsinyasi hanya memperbarui stok dan kartu mutasi; jurnal tidak diposting dari modul ini.");
    }

    private function stokYangDapatDicatat(int $entitasId)
    {
        return StokKonsinyasi::query()
            ->where('id_entitas_penerima', $entitasId)
            ->where('status', 'aktif')
            ->where('qty_sisa', '>', 0)
            ->whereHas('kiriman', fn ($query) => $query->where('status_posting', 'T'));
    }
}
