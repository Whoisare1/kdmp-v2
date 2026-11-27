<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\RekomendasiPengadaanDesa;
use App\Models\Pengguna;
use App\Notifications\AppNotification;
use App\Services\Perencanaan\DraftPembelianDariRekomendasiService;
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

        $multiSend = is_array($request->input('id_hasil_surplus'));
        $rules = [
            'id_hasil_defisit' => ['required', 'integer', 'exists:hasil_kalkulasi,id_hasil'],
            'id_hasil_surplus' => $multiSend
                ? ['required', 'array', 'min:1', 'max:3']
                : ['required', 'integer', 'exists:hasil_kalkulasi,id_hasil'],
        ];
        if ($multiSend) {
            $rules['id_hasil_surplus.*'] = ['required', 'integer', 'distinct', 'exists:hasil_kalkulasi,id_hasil'];
        }
        $validated = $request->validate($rules);
        $idWilayah = $this->wilayahAktifId();
        $pengajuId = (int) auth()->id();
        $pasangan = $multiSend
            ? collect($validated['id_hasil_surplus'])
                ->map(fn ($id): array => ['defisit' => (int) $validated['id_hasil_defisit'], 'surplus' => (int) $id])
                ->all()
            : [['defisit' => (int) $validated['id_hasil_defisit'], 'surplus' => (int) $validated['id_hasil_surplus']]];

        $defisit = DB::transaction(function () use ($pasangan, $multiSend, $idWilayah, $pengajuId): HasilKalkulasi {
            $idHasilPasangan = collect($pasangan)
                ->flatMap(fn (array $pair): array => [$pair['defisit'], $pair['surplus']])
                ->unique()
                ->sort()
                ->values();
            $hasilTerkunci = HasilKalkulasi::query()
                ->with(['komoditas', 'wilayah'])
                ->whereIn('id_hasil', $idHasilPasangan)
                ->orderBy('id_hasil')
                ->lockForUpdate()
                ->get()
                ->keyBy('id_hasil');
            $hasilDefisitPertama = null;
            foreach ($pasangan as $pair) {
                $hasilDefisit = $hasilTerkunci->get($pair['defisit']);
                $hasilSurplus = $hasilTerkunci->get($pair['surplus']);
                abort_unless($hasilDefisit && $hasilSurplus, 404, 'Hasil kalkulasi tidak ditemukan.');
                abort_unless(
                    $this->hasilTerbaruDenganStatus($hasilDefisit, 'defisit'),
                    422,
                    'Hasil defisit bukan kalkulasi terbaru. Muat ulang rekomendasi.'
                );
                abort_unless(
                    $multiSend
                        ? (int) $hasilDefisit->id_wilayah === $idWilayah
                        : (int) $hasilDefisit->id_wilayah === $idWilayah || (int) $hasilSurplus->id_wilayah === $idWilayah,
                    403,
                    'Rekomendasi harus melibatkan hasil kalkulasi dari desa aktif Anda.'
                );
                abort_unless(
                    $this->pasanganSurplusCocok($hasilDefisit, $hasilSurplus),
                    422,
                    'Salah satu desa bukan pasangan surplus terbaru pada periode yang sama. Muat ulang rekomendasi.'
                );

                $sudahAda = RekomendasiPengadaanDesa::query()
                    ->where('id_hasil_defisit', $hasilDefisit->id_hasil)
                    ->where('id_hasil_surplus', $hasilSurplus->id_hasil)
                    ->whereIn('status', ['diajukan', 'disetujui'])
                    ->exists();
                abort_if($sudahAda, 409, 'Salah satu pasangan desa dan komoditas ini sudah direkomendasikan atau disetujui.');

                $rekomendasi = RekomendasiPengadaanDesa::create([
                    'id_hasil_defisit' => $hasilDefisit->id_hasil,
                    'id_hasil_surplus' => $hasilSurplus->id_hasil,
                    'id_pengaju' => $pengajuId,
                    'status' => 'diajukan',
                ]);
                $idEntitasSurplus = DB::table('entitas')
                    ->where('id_wilayah', $hasilSurplus->id_wilayah)
                    ->where('is_active', true)
                    ->where('status', 'APPROVED')
                    ->value('id_entitas');
                if ($idEntitasSurplus && (int) $idEntitasSurplus !== (int) auth()->user()->id_entitas) {
                    Pengguna::query()
                        ->where('id_entitas', $idEntitasSurplus)
                        ->whereHas('peran', fn ($query) => $query->where('kode', 'manajer'))
                        ->get()
                        ->each(fn (Pengguna $manajer) => $manajer->notify(new AppNotification(
                            title: 'Permintaan rekomendasi masuk',
                            message: 'Desa '.$hasilDefisit->wilayah?->nama.' meminta komoditas '.$hasilDefisit->komoditas?->nama.'.',
                            url: route('perencanaan.rekomendasi-pengadaan.index'),
                            data: ['type' => 'rekomendasi_pengadaan', 'id_rekomendasi' => $rekomendasi->id_rekomendasi],
                        )));
                }
                $hasilDefisitPertama ??= $hasilDefisit;
            }

            return $hasilDefisitPertama;
        });

        return redirect()->route('perencanaan.rekomendasi-pengadaan.index')
            ->with('status', "Rekomendasi {$defisit->komoditas?->nama} berhasil dikirim ke ".count($pasangan).' desa.');
    }

    public function inbox(Request $request): View
    {
        $this->authorizeAdminGudang();
        $filterStatus = $request->query('status'); // null = semua
        $items = RekomendasiPengadaanDesa::query()
            ->with([
                'hasilDefisit.komoditas',
                'hasilDefisit.wilayah',
                'hasilSurplus.wilayah',
                'pengaju',
                'pemutus',
            ])
            ->when($filterStatus, fn ($q, $s) => $q->where('status', $s))
            ->orderByRaw("CASE WHEN status = 'diajukan' THEN 0 ELSE 1 END")
            ->latest('id_rekomendasi')
            ->paginate(20);

        return view('gudang.rekomendasi-pengadaan.index', [
            'items' => $items,
            'filterStatus' => $filterStatus,
        ]);
    }

    public function setujui(int|string $id): RedirectResponse
    {
        $item = $this->rekomendasiUntukDitinjau($id);
        $this->pastikanBerhakMenyetujui($item);
        abort_unless($item->status === 'diajukan', 409, 'Rekomendasi ini sudah diproses.');
        abort_unless(
            $this->hasilTerbaruDenganStatus($item->hasilDefisit, 'defisit')
                && $this->pasanganSurplusCocok($item->hasilDefisit, $item->hasilSurplus),
            409,
            'Hasil kalkulasi telah berubah. Rekomendasi perlu diperiksa ulang.'
        );

        $idPembelian = null;
        $kodePembelian = null;

        DB::transaction(function () use ($item, &$idPembelian, &$kodePembelian): void {
            HasilKalkulasi::query()
                ->whereKey($item->id_hasil_defisit)
                ->lockForUpdate()
                ->firstOrFail();
            $terpilih = RekomendasiPengadaanDesa::query()
                ->whereKey($item->id_rekomendasi)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($terpilih->status === 'diajukan', 409, 'Rekomendasi ini sudah diproses.');

            $terpilih->update([
                'status' => 'disetujui',
                'diputuskan_oleh' => auth()->id(),
                'diputuskan_pada' => now(),
            ]);

            // Tutup rekomendasi lain untuk defisit yang sama
            RekomendasiPengadaanDesa::query()
                ->where('id_hasil_defisit', $terpilih->id_hasil_defisit)
                ->where('id_rekomendasi', '!=', $terpilih->id_rekomendasi)
                ->where('status', 'diajukan')
                ->update([
                    'status' => 'ditolak',
                    'diputuskan_oleh' => auth()->id(),
                    'diputuskan_pada' => now(),
                    'catatan_penyetuju' => 'Ditutup otomatis karena rekomendasi desa lain telah disetujui lebih dahulu.',
                ]);

            // Buat draft pembelian otomatis
            try {
                $service = app(DraftPembelianDariRekomendasiService::class);
                $idPembelian = $service->buat($terpilih->fresh(['hasilDefisit.komoditas', 'hasilDefisit.wilayah', 'hasilSurplus.wilayah']));
                $kodePembelian = \Illuminate\Support\Facades\DB::table('pembelian')
                    ->where('id_pembelian', $idPembelian)->value('kode_pembelian');
            } catch (\Throwable $e) {
                // Draft gagal dibuat tidak membatalkan persetujuan — catat ke log
                \Illuminate\Support\Facades\Log::warning('Draft pembelian gagal dibuat dari rekomendasi', [
                    'id_rekomendasi' => $item->id_rekomendasi,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        $this->beriNotifikasi($item->id_pengaju, new AppNotification(
            title: 'Rekomendasi disetujui',
            message: 'Desa '.$item->hasilSurplus?->wilayah?->nama.' menyetujui permintaan Anda.'
                .($kodePembelian ? " Draft pembelian {$kodePembelian} telah dibuat." : ' Silakan buat pembelian secara manual.'),
            url: route('perencanaan.rekomendasi-pengadaan.index'),
            data: ['type' => 'rekomendasi_pengadaan', 'id_rekomendasi' => $item->id_rekomendasi],
        ));

        $pesanStatus = 'Rekomendasi disetujui. Alternatif lain ditutup otomatis.';
        if ($kodePembelian) {
            $pesanStatus .= " Draft pembelian <strong>{$kodePembelian}</strong> telah dibuat di modul Pembelian.";
        } else {
            $pesanStatus .= ' Draft pembelian tidak berhasil dibuat, silakan buat manual di modul Pembelian.';
        }

        return back()->with('status', $pesanStatus);
    }

    public function tolak(Request $request, int|string $id): RedirectResponse
    {
        $this->authorizeAdminGudang();
        $validated = $request->validate([
            'catatan_penyetuju' => ['nullable', 'string', 'max:1000'],
        ]);
        $item = $this->rekomendasiUntukDitinjau($id);
        abort_if((int) $item->id_pengaju === (int) auth()->id(), 403, 'Pengaju tidak dapat menolak rekomendasinya sendiri.');
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

    private function rekomendasiUntukDitinjau(int|string $id): RekomendasiPengadaanDesa
    {
        return RekomendasiPengadaanDesa::query()
            ->with(['hasilDefisit.komoditas', 'hasilDefisit.wilayah', 'hasilSurplus.wilayah'])
            ->whereKey($id)
            ->firstOrFail();
    }

    private function pastikanBerhakMenyetujui(RekomendasiPengadaanDesa $item): void
    {
        $user = auth()->user();
        if ($user?->hasPeran('admin_gudang')) {
            abort_if((int) $item->id_pengaju === (int) $user->id, 403, 'Pengaju tidak dapat menyetujui rekomendasinya sendiri.');

            return;
        }

        abort_unless($user?->hasPeran('manajer'), 403, 'Akses hanya untuk desa penerima atau Admin Gudang.');
        $wilayahPenerima = DB::table('entitas')
            ->where('id_entitas', $user->id_entitas)
            ->where('is_active', true)
            ->where('status', 'APPROVED')
            ->value('id_wilayah');
        abort_unless(
            $wilayahPenerima && (int) $wilayahPenerima === (int) $item->hasilSurplus?->id_wilayah,
            403,
            'Hanya Manajer desa surplus penerima yang dapat menyetujui rekomendasi ini.'
        );
        abort_if((int) $item->id_pengaju === (int) $user->id, 403, 'Pengaju tidak dapat menyetujui rekomendasinya sendiri.');
    }

    private function beriNotifikasi(int $idPengguna, AppNotification $notification): void
    {
        Pengguna::query()->whereKey($idPengguna)->first()?->notify($notification);
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
            && DB::table('entitas')
                ->join('wilayah', 'wilayah.id', '=', 'entitas.id_wilayah')
                ->where('entitas.id_wilayah', $surplus->id_wilayah)
                ->where('wilayah.tingkat', 'desa')
                ->where('entitas.is_active', true)
                ->where('entitas.status', 'APPROVED')
                ->exists()
            && (int) $surplus->id_komoditas === (int) $defisit->id_komoditas
            && (int) $surplus->tahun === (int) $defisit->tahun
            && (int) $surplus->bulan === (int) $defisit->bulan
            && $this->hasilTerbaruDenganStatus($surplus, 'surplus');
    }
}
