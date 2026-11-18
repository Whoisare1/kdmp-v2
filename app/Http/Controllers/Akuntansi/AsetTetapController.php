<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Akuntansi\Coa;
use App\Models\Master\AsetTetap;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AsetTetapController extends Controller
{
    public const KATEGORI_LIST = [
        'peralatan' => 'Peralatan & Elektronik (1214)',
        'kendaraan' => 'Kendaraan (1213)',
        'gedung'    => 'Gedung & Bangunan (1212)',
        'tanah'     => 'Tanah (1211 - Tidak Disusutkan)',
    ];

    public function index(Request $request): View
    {
        $idEntitas = app('entitas_aktif');
        $q = $request->query('q', '');

        $query = AsetTetap::query()
            ->when($idEntitas, fn($query) => $query->where('id_entitas', $idEntitas))
            ->when($q, fn($query) => $query->where(fn($sub) => $sub
                ->where('nama_aset', 'like', "%{$q}%")
                ->orWhere('kode_aset', 'like', "%{$q}%")));

        $aset = $query->orderBy('tgl_perolehan', 'desc')->paginate(15)->withQueryString();

        return view('akuntansi.aset-tetap.index', compact('aset', 'q', 'idEntitas'));
    }

    private function coaPilihan(): array
    {
        $coaAktif = fn (string $prefix) => Coa::where('is_transaction', 'T')
            ->where('is_active', 1)
            ->where('kode_anak', 'like', $prefix);

        $akunAset = $coaAktif('121%')->orderBy('kode_anak')->get();
        $akunAkum = $coaAktif('122%')->orderBy('kode_anak')->get();
        $akunBiaya = $coaAktif('6%')
            ->orderByRaw("CASE WHEN kode_anak LIKE '63%' THEN 0 ELSE 1 END")
            ->orderBy('kode_anak')
            ->get();

        $kategoriList = self::KATEGORI_LIST;

        return compact('akunAset', 'akunAkum', 'akunBiaya', 'kategoriList');
    }

    private function validatePayload(
        Request $request,
        bool $isCreate,
        ?int $idEntitas,
        bool $bisaUbahPerolehan = false,
    ): array {
        $isTanah = strtolower((string) $request->input('kategori')) === 'tanah';

        $rules = [
            'nama_aset'       => 'required|string|max:150',
            'kategori'        => 'required|in:tanah,gedung,kendaraan,peralatan',
            'nilai_residu'    => 'required|numeric|min:0',
            'umur_bulan'      => $isTanah ? 'nullable|integer|min:0' : 'required|integer|min:1',
            'kode_akun_aset'  => 'required|string|max:10',
            'kode_akun_akum'  => $isTanah ? 'nullable|string|max:10' : 'required|string|max:10',
            'kode_akun_biaya' => $isTanah ? 'nullable|string|max:10' : 'required|string|max:10',
        ];

        if ($isCreate) {
            $uniqueRule = \Illuminate\Validation\Rule::unique('aset_tetap', 'kode_aset');
            if ($idEntitas !== null) {
                $uniqueRule->where(fn ($q) => $q->where('id_entitas', $idEntitas));
            }
            $rules['kode_aset'] = ['required', 'string', 'max:30', $uniqueRule];
            $rules['tgl_perolehan'] = 'required|date';
            $rules['nilai_perolehan'] = 'required|numeric|min:0';
        } else {
            $rules['status'] = 'required|in:aktif,habis_susut,dilepas';
            if ($bisaUbahPerolehan) {
                $rules['tgl_perolehan'] = 'sometimes|required|date';
                $rules['nilai_perolehan'] = 'sometimes|required|numeric|min:0';
            }
        }

        $validated = $request->validate($rules);

        if ($validated['kategori'] === 'tanah') {
            $validated['umur_bulan'] = 0;
            $validated['kode_akun_akum'] = null;
            $validated['kode_akun_biaya'] = null;
        }

        return $validated;
    }

    public function create(): View
    {
        return view('akuntansi.aset-tetap.create', $this->coaPilihan());
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = app('entitas_aktif');
        if (! $idEntitas) {
            return back()->withInput()->with('error', 'Harap pilih Entitas terlebih dahulu sebelum menambah Aset Tetap.');
        }

        $validated = $this->validatePayload($request, true, (int) $idEntitas);

        $validated['id_entitas'] = $idEntitas;
        $validated['akum_penyusutan'] = 0;
        $validated['status'] = 'aktif';

        AsetTetap::create($validated);

        return redirect()->route('akuntansi.aset-tetap.index')
                         ->with('success', 'Aset Tetap berhasil ditambahkan.');
    }

    public function edit(AsetTetap $aset_tetap): View
    {
        return view('akuntansi.aset-tetap.edit', array_merge(
            [
                'aset' => $aset_tetap,
                'terkunciPerolehan' => $aset_tetap->sudahDisusutkan(),
            ],
            $this->coaPilihan()
        ));
    }

    public function update(Request $request, AsetTetap $aset_tetap): RedirectResponse
    {
        $idEntitas = app('entitas_aktif');
        $bisaUbahPerolehan = ! $aset_tetap->sudahDisusutkan();
        $validated = $this->validatePayload($request, false, $idEntitas ? (int) $idEntitas : null, $bisaUbahPerolehan);

        // Tanggal dan nilai perolehan hanya bisa diubah selama aset belum memiliki histori penyusutan
        $aset_tetap->update($validated);

        return redirect()->route('akuntansi.aset-tetap.index')
                         ->with('success', 'Data Aset Tetap berhasil diperbarui.');
    }
}
