<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\RekomendasiPengadaanDesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RekomendasiPengadaanDesaController extends Controller
{
    use ResolvesWilayahAktif;

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPeran('manajer'), 403, 'Pengajuan rekomendasi hanya dapat dilakukan Admin Desa.');

        $validated = $request->validate([
            'id_hasil_defisit' => ['required', 'integer', 'exists:hasil_kalkulasi,id_hasil'],
            'id_hasil_surplus' => ['required', 'integer', 'exists:hasil_kalkulasi,id_hasil'],
        ]);
        $idWilayah = $this->wilayahAktifId();
        $pengajuId = (int) auth()->id();

        $hasilDefisit = DB::transaction(function () use ($validated, $idWilayah, $pengajuId): HasilKalkulasi {
            $defisit = HasilKalkulasi::query()
                ->whereKey($validated['id_hasil_defisit'])
                ->where('id_wilayah', $idWilayah)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($this->hasilTerbaruDenganStatus($defisit, 'defisit'), 422, 'Hasil defisit sudah berubah. Muat ulang rekomendasi.');

            $surplus = HasilKalkulasi::query()->findOrFail($validated['id_hasil_surplus']);
            abort_unless($this->pasanganSurplusCocok($defisit, $surplus), 422, 'Desa sumber bukan surplus terbaru untuk komoditas dan periode ini.');

            $sudahAda = RekomendasiPengadaanDesa::query()
                ->where('id_hasil_defisit', $defisit->id_hasil)
                ->whereIn('status', ['diajukan', 'disetujui'])
                ->exists();
            abort_if($sudahAda, 409, 'Rekomendasi untuk kebutuhan ini sudah diajukan atau disetujui.');

            RekomendasiPengadaanDesa::create([
                'id_hasil_defisit' => $defisit->id_hasil,
                'id_hasil_surplus' => $surplus->id_hasil,
                'id_pengaju' => $pengajuId,
                'status' => 'diajukan',
            ]);

            return $defisit;
        });

        return redirect()->route('perencanaan.permintaan-pengadaan.index')
            ->with('status', "Rekomendasi untuk {$hasilDefisit->komoditas?->nama} berhasil diajukan ke Admin Gudang.");
    }

    public function inbox(): View
    {
        $this->authorizeAdminGudang();
        $idWilayah = $this->wilayahAktifId();
        $items = RekomendasiPengadaanDesa::query()
            ->with([
                'hasilDefisit.komoditas',
                'hasilDefisit.wilayah',
                'hasilSurplus.wilayah',
                'pengaju',
                'pemutus',
            ])
            ->whereHas('hasilDefisit', fn ($query) => $query->where('id_wilayah', $idWilayah))
            ->orderByRaw("CASE WHEN status = 'diajukan' THEN 0 ELSE 1 END")
            ->latest('id_rekomendasi')
            ->paginate(20);

        return view('gudang.rekomendasi-pengadaan.index', [
            'items' => $items,
        ]);
    }

    public function setujui(int|string $id): RedirectResponse
    {
        $this->authorizeAdminGudang();
        $item = $this->rekomendasiDiWilayahAktif($id);
        $this->pastikanBukanPengaju($item);
        abort_unless($item->status === 'diajukan', 409, 'Rekomendasi ini sudah diproses.');
        abort_unless(
            $this->hasilTerbaruDenganStatus($item->hasilDefisit, 'defisit')
                && $this->pasanganSurplusCocok($item->hasilDefisit, $item->hasilSurplus),
            409,
            'Hasil kalkulasi telah berubah. Rekomendasi perlu diperiksa ulang.'
        );

        $item->update([
            'status' => 'disetujui',
            'diputuskan_oleh' => auth()->id(),
            'diputuskan_pada' => now(),
            'catatan_penyetuju' => null,
        ]);

        return back()->with('status', 'Rekomendasi disetujui. Tidak ada PR atau transaksi yang dibuat.');
    }

    public function tolak(Request $request, int|string $id): RedirectResponse
    {
        $this->authorizeAdminGudang();
        $validated = $request->validate([
            'catatan_penyetuju' => ['nullable', 'string', 'max:1000'],
        ]);
        $item = $this->rekomendasiDiWilayahAktif($id);
        $this->pastikanBukanPengaju($item);
        abort_unless($item->status === 'diajukan', 409, 'Rekomendasi ini sudah diproses.');

        $item->update([
            'status' => 'ditolak',
            'diputuskan_oleh' => auth()->id(),
            'diputuskan_pada' => now(),
            'catatan_penyetuju' => $validated['catatan_penyetuju'] ?? null,
        ]);

        return back()->with('status', 'Rekomendasi ditolak. Tidak ada PR atau transaksi yang dibuat.');
    }

    private function authorizeAdminGudang(): void
    {
        abort_unless(auth()->user()?->hasPeran('admin_gudang'), 403, 'Akses hanya untuk Admin Gudang.');
    }

    private function rekomendasiDiWilayahAktif(int|string $id): RekomendasiPengadaanDesa
    {
        return RekomendasiPengadaanDesa::query()
            ->with(['hasilDefisit.komoditas', 'hasilSurplus.wilayah'])
            ->whereKey($id)
            ->whereHas('hasilDefisit', fn ($query) => $query->where('id_wilayah', $this->wilayahAktifId()))
            ->firstOrFail();
    }

    private function pastikanBukanPengaju(RekomendasiPengadaanDesa $item): void
    {
        abort_if((int) $item->id_pengaju === (int) auth()->id(), 403, 'Pengaju tidak dapat menyetujui atau menolak rekomendasinya sendiri.');
    }

    private function hasilTerbaruDenganStatus(HasilKalkulasi $hasil, string $status): bool
    {
        $versiTerbaru = HasilKalkulasi::query()
            ->where('id_wilayah', $hasil->id_wilayah)
            ->where('id_komoditas', $hasil->id_komoditas)
            ->where('tahun', $hasil->tahun)
            ->where('bulan', $hasil->bulan)
            ->max('versi');

        return (int) $hasil->versi === (int) $versiTerbaru
            && $hasil->status_surplus_defisit === $status;
    }

    private function pasanganSurplusCocok(HasilKalkulasi $defisit, HasilKalkulasi $surplus): bool
    {
        return (int) $surplus->id_wilayah !== (int) $defisit->id_wilayah
            && (int) $surplus->id_komoditas === (int) $defisit->id_komoditas
            && (int) $surplus->tahun === (int) $defisit->tahun
            && (int) $surplus->bulan === (int) $defisit->bulan
            && $this->hasilTerbaruDenganStatus($surplus, 'surplus');
    }
}
