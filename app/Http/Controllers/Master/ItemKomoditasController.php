<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ItemKomoditas;
use App\Models\Master\MasterKomoditas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemKomoditasController extends Controller
{
    public function index(Request $request): View
    {
        $masterId = $request->query('master_id');
        $statusPetani = $request->query('status_petani');
        $search = trim((string) $request->query('q', ''));

        $query = ItemKomoditas::with([
            'masterKomoditas.kategori',
            'petani.wilayah',
        ])->withCount('petani');

        if ($masterId) {
            $query->where('id_master_komoditas', $masterId);
        }

        if ($statusPetani === 'ada') {
            $query->has('petani');
        } elseif ($statusPetani === 'belum') {
            $query->doesntHave('petani');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('nama')->paginate(15)->withQueryString();
        $masterList = MasterKomoditas::with('kategori')->where('is_active', true)->orderBy('nama')->get();

        return view('master.item_komoditas.index', [
            'title' => 'Item / Subjenis Komoditas',
            'items' => $items,
            'masterList' => $masterList,
            'masterId' => $masterId,
            'statusPetani' => $statusPetani,
            'search' => $search,
        ]);
    }

    public function show(ItemKomoditas $itemKomoditas): RedirectResponse
    {
        $itemKomoditas->load('masterKomoditas');

        return redirect()->route('pendataan.petani.index', [
            'kategori_id' => $itemKomoditas->masterKomoditas?->id_kategori,
            'master_id' => $itemKomoditas->id_master_komoditas,
            'item_id' => $itemKomoditas->id,
        ]);
    }

    public function create(): View
    {
        $masterList = MasterKomoditas::with('kategori')->where('is_active', true)->orderBy('nama')->get();
        $petaniList = \App\Models\Pendataan\Petani::orderBy('nama_lengkap')->get();

        return view('master.item_komoditas.create', [
            'title' => 'Tambah Item Komoditas',
            'masterList' => $masterList,
            'petaniList' => $petaniList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_master_komoditas' => ['required', 'exists:master_komoditas,id'],
            'kode' => ['required', 'string', 'max:30', 'unique:item_komoditas,kode'],
            'nama' => ['required', 'string', 'max:150'],
            'satuan_standar' => ['required', 'string', 'max:30'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Standarisasi satuan: Ekraf = 'unit', selain Ekraf = 'kg'
        $master = MasterKomoditas::with('kategori')->find($validated['id_master_komoditas']);
        $isEkraf = $master && (
            str_contains(strtolower($master->kategori?->kode ?? ''), 'ekraf') ||
            str_contains(strtolower($master->kategori?->nama ?? ''), 'ekraf')
        );
        $validated['satuan_standar'] = $isEkraf ? 'unit' : 'kg';

        $itemKomoditas = ItemKomoditas::create($validated);

        // Opsi hubungkan petani produsen awal
        $petaniId = $request->input('tambah_petani_id');
        $namaPetaniBaru = trim((string) $request->input('tambah_nama_petani', ''));

        if (!$petaniId && $namaPetaniBaru !== '') {
            $desa = \App\Models\Tenant\Wilayah::where('tingkat', 'desa')->first();
            $newPetani = \App\Models\Pendataan\Petani::create([
                'id_wilayah' => $desa?->id,
                'nama_lengkap' => $namaPetaniBaru,
                'nomor_hp' => $request->input('tambah_nomor_hp'),
                'dusun' => $request->input('tambah_dusun'),
                'rt' => $request->input('tambah_rt'),
                'rw' => $request->input('tambah_rw'),
                'desa' => $desa?->nama,
                'kecamatan' => $desa?->parent?->nama,
                'kabupaten' => $desa?->parent?->parent?->nama,
            ]);
            $petaniId = $newPetani->id;
        }

        if ($petaniId) {
            $volume = (float) $request->input('tambah_estimasi_volume_panen', 0);
            $hargaSatuan = (float) $request->input('tambah_harga_satuan', 0);
            $totalNilai = (float) $request->input('tambah_total_nilai', 0);

            if ($hargaSatuan > 0 && $volume > 0 && $totalNilai <= 0) {
                $totalNilai = round($volume * $hargaSatuan);
            }
            if ($totalNilai > 0 && $volume > 0 && $hargaSatuan <= 0) {
                $hargaSatuan = round($totalNilai / $volume);
            }

            $itemKomoditas->petani()->syncWithoutDetaching([
                $petaniId => [
                    'estimasi_volume_panen' => $volume,
                    'satuan' => $itemKomoditas->satuan_standar,
                    'harga_satuan' => $hargaSatuan,
                    'total_nilai' => $totalNilai,
                    'periode_panen' => $request->input('tambah_periode_panen'),
                    'keterangan' => 'Dihubungkan saat pendaftaran komoditas baru',
                ]
            ]);
        }

        return redirect()->route('master.item-komoditas.index')->with('success', 'Item komoditas berhasil ditambahkan.');
    }

    public function edit(ItemKomoditas $itemKomoditas): View
    {
        $itemKomoditas->load(['masterKomoditas.kategori', 'petani.wilayah']);
        $masterList = MasterKomoditas::with('kategori')->where('is_active', true)->orderBy('nama')->get();
        $petaniList = \App\Models\Pendataan\Petani::orderBy('nama_lengkap')->get();

        return view('master.item_komoditas.edit', [
            'title' => 'Edit Item Komoditas: ' . $itemKomoditas->nama,
            'item' => $itemKomoditas,
            'masterList' => $masterList,
            'petaniList' => $petaniList,
        ]);
    }

    public function update(Request $request, ItemKomoditas $itemKomoditas): RedirectResponse
    {
        $validated = $request->validate([
            'id_master_komoditas' => ['required', 'exists:master_komoditas,id'],
            'kode' => ['required', 'string', 'max:30', 'unique:item_komoditas,kode,' . $itemKomoditas->id],
            'nama' => ['required', 'string', 'max:150'],
            'satuan_standar' => ['required', 'string', 'max:30'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Standarisasi satuan: Ekraf = 'unit', selain Ekraf = 'kg'
        $master = MasterKomoditas::with('kategori')->find($validated['id_master_komoditas']);
        $isEkraf = $master && (
            str_contains(strtolower($master->kategori?->kode ?? ''), 'ekraf') ||
            str_contains(strtolower($master->kategori?->nama ?? ''), 'ekraf')
        );
        $validated['satuan_standar'] = $isEkraf ? 'unit' : 'kg';

        $itemKomoditas->update($validated);

        // Cek jika ada penambahan petani baru atau penghubungan petani terdaftar
        $petaniId = $request->input('tambah_petani_id');
        $namaPetaniBaru = trim((string) $request->input('tambah_nama_petani', ''));

        if (!$petaniId && $namaPetaniBaru !== '') {
            $desa = \App\Models\Tenant\Wilayah::where('tingkat', 'desa')->first();
            $newPetani = \App\Models\Pendataan\Petani::create([
                'id_wilayah' => $desa?->id,
                'nama_lengkap' => $namaPetaniBaru,
                'nomor_hp' => $request->input('tambah_nomor_hp'),
                'dusun' => $request->input('tambah_dusun'),
                'rt' => $request->input('tambah_rt'),
                'rw' => $request->input('tambah_rw'),
                'desa' => $desa?->nama,
                'kecamatan' => $desa?->parent?->nama,
                'kabupaten' => $desa?->parent?->parent?->nama,
            ]);
            $petaniId = $newPetani->id;
        }

        if ($petaniId) {
            $volume = (float) $request->input('tambah_estimasi_volume_panen', 0);
            $hargaSatuan = (float) $request->input('tambah_harga_satuan', 0);
            $totalNilai = (float) $request->input('tambah_total_nilai', 0);

            if ($hargaSatuan > 0 && $volume > 0 && $totalNilai <= 0) {
                $totalNilai = round($volume * $hargaSatuan);
            }
            if ($totalNilai > 0 && $volume > 0 && $hargaSatuan <= 0) {
                $hargaSatuan = round($totalNilai / $volume);
            }

            $itemKomoditas->petani()->syncWithoutDetaching([
                $petaniId => [
                    'estimasi_volume_panen' => $volume,
                    'satuan' => $itemKomoditas->satuan_standar,
                    'harga_satuan' => $hargaSatuan,
                    'total_nilai' => $totalNilai,
                    'periode_panen' => $request->input('tambah_periode_panen'),
                    'keterangan' => 'Dihubungkan melalui form edit komoditas',
                ]
            ]);
        }

        return redirect()->route('master.item-komoditas.index')->with('success', 'Item komoditas dan data petani terkait berhasil diperbarui.');
    }

    public function destroy(ItemKomoditas $itemKomoditas): RedirectResponse
    {
        if ($itemKomoditas->petani()->count() > 0) {
            return back()->with('error', 'Item komoditas tidak dapat dihapus karena sudah terhubung dengan data petani/produksi.');
        }

        $itemKomoditas->delete();
        return redirect()->route('master.item-komoditas.index')->with('success', 'Item komoditas berhasil dihapus.');
    }
}

