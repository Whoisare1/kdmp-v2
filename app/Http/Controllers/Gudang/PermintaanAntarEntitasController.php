<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Gudang\PermintaanAntarEntitas;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Services\Gudang\StokPerencanaanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PermintaanAntarEntitasController extends Controller
{
    use ResolvesWilayahAktif;

    public function __construct(private StokPerencanaanService $stokPerencanaan) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_hasil_pengaju' => ['required', 'integer', 'exists:hasil_kalkulasi,id_hasil'],
            'id_hasil_penerima' => ['required', 'integer', 'different:id_hasil_pengaju', 'exists:hasil_kalkulasi,id_hasil'],
        ]);

        $entitasId = (int) (auth()->user()->id_entitas ?? 0);
        $wilayahId = $this->wilayahAktifId();

        DB::transaction(function () use ($validated, $entitasId, $wilayahId): PermintaanAntarEntitas {
                $hasilTerkunci = HasilKalkulasi::query()
                    ->with(['wilayah', 'komoditas'])
                    ->whereIn('id_hasil', [
                        (int) $validated['id_hasil_pengaju'],
                        (int) $validated['id_hasil_penerima'],
                    ])
                    ->orderBy('id_hasil')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id_hasil');
                $hasilPengaju = $hasilTerkunci->get((int) $validated['id_hasil_pengaju']);
                $hasilPenerima = $hasilTerkunci->get((int) $validated['id_hasil_penerima']);

                abort_unless($hasilPengaju && $hasilPenerima, 404, 'Hasil kalkulasi tidak ditemukan.');

                if ((int) $hasilPengaju->id_wilayah !== $wilayahId) {
                    throw ValidationException::withMessages([
                        'id_hasil_pengaju' => 'Hasil kalkulasi pengaju bukan milik entitas aktif.',
                    ]);
                }

                if (! $this->hasilTerbaruDisetujui($hasilPengaju) || ! $this->hasilTerbaruDisetujui($hasilPenerima)) {
                    throw ValidationException::withMessages([
                        'id_hasil_penerima' => 'Permintaan hanya dapat dibuat dari hasil kalkulasi terbaru yang disetujui.',
                    ]);
                }

                $entitasPenerima = DB::table('entitas')
                    ->where('id_wilayah', $hasilPenerima->id_wilayah)
                    ->where('is_active', true)
                    ->where('status', 'APPROVED')
                    ->first();

                if (! $entitasPenerima || (int) $entitasPenerima->id_entitas === $entitasId) {
                    throw ValidationException::withMessages([
                        'id_hasil_penerima' => 'Entitas tujuan tidak aktif atau sama dengan entitas pengaju.',
                    ]);
                }

                $statusPengaju = (string) $hasilPengaju->status_surplus_defisit;
                $statusPenerima = (string) $hasilPenerima->status_surplus_defisit;

                if (
                    ! in_array($statusPengaju, ['surplus', 'defisit'], true)
                    || $statusPenerima === $statusPengaju
                    || ! in_array($statusPenerima, ['surplus', 'defisit'], true)
                    || (int) $hasilPengaju->id_komoditas !== (int) $hasilPenerima->id_komoditas
                    || (int) $hasilPengaju->tahun !== (int) $hasilPenerima->tahun
                    || (int) $hasilPengaju->bulan !== (int) $hasilPenerima->bulan
                ) {
                    throw ValidationException::withMessages([
                        'id_hasil_penerima' => 'Hasil kalkulasi tujuan tidak cocok dengan komoditas dan periode pengaju.',
                    ]);
                }

                $satuanPengaju = $this->satuanHasil($hasilPengaju);
                $satuanPenerima = $this->satuanHasil($hasilPenerima);
                if ($satuanPengaju === null || $satuanPengaju !== $satuanPenerima) {
                    throw ValidationException::withMessages([
                        'id_hasil_penerima' => 'Satuan kedua hasil kalkulasi belum cocok untuk dibandingkan.',
                    ]);
                }

                $pengajuSebagaiPenjual = $statusPengaju === 'surplus';
                $hasilPenjual = $pengajuSebagaiPenjual ? $hasilPengaju : $hasilPenerima;
                $hasilPembeli = $pengajuSebagaiPenjual ? $hasilPenerima : $hasilPengaju;
                $idEntitasPenjual = $pengajuSebagaiPenjual
                    ? $entitasId
                    : (int) DB::table('entitas')->where('id_wilayah', $hasilPenerima->id_wilayah)->value('id_entitas');
                $idEntitasPembeli = $pengajuSebagaiPenjual
                    ? (int) $entitasPenerima->id_entitas
                    : $entitasId;
                $komoditasId = (int) $hasilPengaju->id_komoditas;

                $qtyPenjual = min(
                    abs((float) $hasilPenjual->selisih),
                    $this->stokPerencanaan->tersedia($idEntitasPenjual, $komoditasId, $satuanPengaju, hanyaDapatDijual: true),
                );
                $qtyPembeli = max(
                    0,
                    abs((float) $hasilPembeli->selisih)
                        - $this->stokPerencanaan->tersedia($idEntitasPembeli, $komoditasId, $satuanPengaju),
                );
                $qtyRekomendasi = min($qtyPenjual, $qtyPembeli);

                if ($qtyRekomendasi <= 0) {
                    throw ValidationException::withMessages([
                        'id_hasil_penerima' => 'Stok tersedia atau kebutuhan belum mendukung permintaan ini.',
                    ]);
                }

                $sudahAda = PermintaanAntarEntitas::query()
                    ->where('status', '!=', 'dibatalkan')
                    ->where(fn ($query) => $query
                        ->where(fn ($pair) => $pair
                            ->where('id_hasil_pengaju', $hasilPengaju->id_hasil)
                            ->where('id_hasil_penerima', $hasilPenerima->id_hasil))
                        ->orWhere(fn ($pair) => $pair
                            ->where('id_hasil_pengaju', $hasilPenerima->id_hasil)
                            ->where('id_hasil_penerima', $hasilPengaju->id_hasil)))
                    ->exists();

                if ($sudahAda) {
                    throw ValidationException::withMessages([
                        'id_hasil_penerima' => 'Permintaan untuk pasangan desa dan hasil kalkulasi ini sudah pernah dikirim.',
                    ]);
                }

            return PermintaanAntarEntitas::query()->create([
                    'id_entitas_pengaju' => $entitasId,
                    'id_entitas_penerima' => (int) $entitasPenerima->id_entitas,
                    'id_hasil_pengaju' => $hasilPengaju->id_hasil,
                    'id_hasil_penerima' => $hasilPenerima->id_hasil,
                    'jenis_permintaan' => $pengajuSebagaiPenjual ? 'tawaran_jual' : 'permintaan_beli',
                    'nama_komoditas' => (string) $hasilPengaju->komoditas?->nama,
                    'tahun' => $hasilPengaju->tahun,
                    'bulan' => $hasilPengaju->bulan,
                    'qty_rekomendasi' => number_format($qtyRekomendasi, 4, '.', ''),
                    'satuan' => $satuanPengaju,
                    'status' => 'terkirim',
                    'created_by' => auth()->id(),
            ]);
        });

        return redirect()->back()->with('success', 'Permintaan Gudang berhasil dikirim sebagai informasi tindak lanjut. Tidak ada stok atau transaksi yang diubah.');
    }

    public function respond(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:disetujui,ditolak'],
        ]);

        $entitasId = (int) (auth()->user()->id_entitas ?? 0);
        $permintaan = DB::transaction(function () use ($id, $entitasId, $validated): PermintaanAntarEntitas {
            $permintaan = PermintaanAntarEntitas::query()
                ->where('id_permintaan_gudang', $id)
                ->where('id_entitas_penerima', $entitasId)
                ->lockForUpdate()
                ->first();

            if (! $permintaan) {
                throw new NotFoundHttpException('Permintaan tidak ditemukan untuk entitas aktif.');
            }

            if ($permintaan->status !== 'terkirim') {
                throw ValidationException::withMessages([
                    'status' => 'Permintaan ini sudah ditindaklanjuti.',
                ]);
            }

            $permintaan->update(['status' => $validated['status']]);

            return $permintaan;
        });

        $pesan = $permintaan->status === 'disetujui' ? 'Permintaan disetujui' : 'Permintaan ditolak';

        return redirect()->back()->with('success', $pesan.'. Status diperbarui tanpa mengubah stok atau membuat transaksi.');
    }

    private function hasilTerbaruDisetujui(HasilKalkulasi $hasil): bool
    {
        if ($hasil->status !== 'disetujui') {
            return false;
        }

        $versiTerbaru = HasilKalkulasi::query()
            ->where('id_wilayah', $hasil->id_wilayah)
            ->where('id_komoditas', $hasil->id_komoditas)
            ->where('tahun', $hasil->tahun)
            ->where('bulan', $hasil->bulan)
            ->where('status', 'disetujui')
            ->max('versi');

        return (int) $hasil->versi === (int) $versiTerbaru;
    }

    private function satuanHasil(HasilKalkulasi $hasil): ?string
    {
        $satuan = KebutuhanKomoditas::query()
            ->where('id_wilayah', $hasil->id_wilayah)
            ->where('id_komoditas', $hasil->id_komoditas)
            ->where('tahun', $hasil->tahun)
            ->where('bulan', $hasil->bulan)
            ->pluck('satuan')
            ->map(fn ($value): string => strtoupper(trim((string) $value)))->unique();

        return $satuan->count() === 1 ? $satuan->first() : null;
    }

}
