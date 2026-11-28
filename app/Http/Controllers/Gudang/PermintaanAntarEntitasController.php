<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Concerns\ResolvesWilayahAktif;
use App\Http\Controllers\Controller;
use App\Models\Gudang\PermintaanAntarEntitas;
use App\Models\Gudang\RekomendasiSurplusDefisit;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\KebutuhanKomoditas;
use App\Services\Gudang\StokPerencanaanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
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

                if (! $this->hasilResmiTerbaru($hasilPengaju) || ! $this->hasilResmiTerbaru($hasilPenerima)) {
                    throw ValidationException::withMessages([
                        'id_hasil_penerima' => 'Permintaan hanya dapat dibuat dari hasil kalkulasi terbaru.',
                    ]);
                }

                $entitasPenerima = DB::table('entitas')
                    ->where('id_wilayah', $hasilPenerima->id_wilayah)
                    ->where('is_active', true)
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

                if ($satuanPengaju === null || $satuanPenerima === null || $satuanPengaju !== $satuanPenerima) {
                    throw ValidationException::withMessages([
                        'id_hasil_penerima' => 'Satuan perencanaan kedua desa belum tersedia atau belum sama; jumlah belum dapat diajukan.',
                    ]);
                }

                $pengajuSebagaiPenjual = $statusPengaju === 'surplus';
                $hasilPenjual = $pengajuSebagaiPenjual ? $hasilPengaju : $hasilPenerima;
                $hasilPembeli = $pengajuSebagaiPenjual ? $hasilPenerima : $hasilPengaju;
                $komoditasId = (int) $hasilPengaju->id_komoditas;

                $idEntitasPenjual = $pengajuSebagaiPenjual
                    ? $entitasId
                    : (int) $entitasPenerima->id_entitas;
                $idEntitasPembeli = $pengajuSebagaiPenjual
                    ? (int) $entitasPenerima->id_entitas
                    : $entitasId;

                $stokPenjual = $this->stokPerencanaan->tersedia(
                    $idEntitasPenjual,
                    $komoditasId,
                    $satuanPengaju,
                );
                $stokDapatDijual = $this->stokPerencanaan->tersedia(
                    $idEntitasPenjual,
                    $komoditasId,
                    $satuanPengaju,
                    hanyaDapatDijual: true,
                );
                $stokPembeli = $this->stokPerencanaan->tersedia(
                    $idEntitasPembeli,
                    $komoditasId,
                    $satuanPengaju,
                );

                $qtyPenjual = $this->stokPerencanaan->jumlahTindakLanjut(
                    'surplus',
                    (float) $hasilPenjual->selisih,
                    $stokPenjual,
                    $stokDapatDijual,
                );
                $qtyPembeli = $this->stokPerencanaan->jumlahTindakLanjut(
                    'defisit',
                    (float) $hasilPembeli->selisih,
                    $stokPembeli,
                    0,
                );
                $qtyRekomendasi = min($qtyPenjual, $qtyPembeli);

                if ($qtyRekomendasi <= 0) {
                    throw ValidationException::withMessages([
                        'id_hasil_penerima' => 'Tidak ada jumlah yang dapat diajukan setelah dicocokkan dengan stok Gudang kedua entitas.',
                    ]);
                }

                $sudahAda = PermintaanAntarEntitas::query()
                    ->whereIn('status', ['terkirim', 'disetujui', 'dilempar'])
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
                ->where(fn ($query) => $query
                    ->where('id_entitas_pengaju', $entitasId)
                    ->orWhere('id_entitas_penerima', $entitasId))
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

            if ($validated['status'] === 'ditolak') {
                $permintaan->update(['status' => 'ditolak']);

                return $permintaan;
            }

            $sisi = (int) $permintaan->id_entitas_pengaju === $entitasId
                ? 'pengaju'
                : 'penerima';
            $kolomWaktu = 'tgl_persetujuan_'.$sisi;
            $kolomPengguna = 'id_persetujuan_'.$sisi;

            if ($permintaan->{$kolomWaktu} !== null) {
                throw ValidationException::withMessages([
                    'status' => 'Entitas aktif sudah memberikan persetujuan untuk permintaan ini.',
                ]);
            }

            $permintaan->setAttribute($kolomWaktu, now());
            $permintaan->setAttribute($kolomPengguna, auth()->id());

            $sisiLain = $sisi === 'pengaju' ? 'penerima' : 'pengaju';
            if ($permintaan->{'tgl_persetujuan_'.$sisiLain} !== null) {
                $hasilPengaju = HasilKalkulasi::query()->find($permintaan->id_hasil_pengaju);
                $hasilPenerima = HasilKalkulasi::query()->find($permintaan->id_hasil_penerima);

                if (
                    ! $hasilPengaju
                    || ! $hasilPenerima
                    || ! $this->hasilResmiTerbaru($hasilPengaju)
                    || ! $this->hasilResmiTerbaru($hasilPenerima)
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Hasil kalkulasi resmi telah berubah. Buat pengajuan baru berdasarkan hasil resmi terbaru.',
                    ]);
                }

                $permintaan->disetujui_penuh_pada = now();
                $this->buatRekomendasiTransaksi($permintaan, $hasilPengaju, $hasilPenerima);
                $permintaan->status = 'disetujui';
            }

            $permintaan->save();

            return $permintaan;
        });

        $pesan = match ($permintaan->status) {
            'disetujui' => 'Persetujuan kedua entitas lengkap. Stok surplus telah direservasi selama 48 jam; rekomendasi siap dilempar.',
            'ditolak' => 'Permintaan ditolak.',
            default => 'Persetujuan entitas aktif tercatat; menunggu persetujuan entitas lainnya.',
        };

        return redirect()->back()->with('success', $pesan);
    }

    public function dispatch(Request $request, int $id): RedirectResponse
    {
        $entitasId = (int) (auth()->user()->id_entitas ?? 0);
        $sudahDilempar = DB::transaction(function () use ($id, $entitasId): bool {
            $permintaan = PermintaanAntarEntitas::query()
                ->where('id_permintaan_gudang', $id)
                ->where(fn ($query) => $query
                    ->where('id_entitas_pengaju', $entitasId)
                    ->orWhere('id_entitas_penerima', $entitasId))
                ->lockForUpdate()
                ->first();

            if (! $permintaan) {
                throw new NotFoundHttpException('Permintaan tidak ditemukan untuk entitas aktif.');
            }

            if ($permintaan->status === 'dilempar') {
                return true;
            }

            if (
                $permintaan->tgl_persetujuan_pengaju === null
                || $permintaan->tgl_persetujuan_penerima === null
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Persetujuan lengkap dari kedua entitas belum tercatat.',
                ]);
            }

            if (! in_array($permintaan->status, ['terkirim', 'disetujui'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status permintaan saat ini tidak dapat dilempar.',
                ]);
            }

            $rekomendasi = RekomendasiSurplusDefisit::query()
                ->where('id_pengajuan_gudang', $permintaan->id_permintaan_gudang)
                ->where('status', 'siap_dilempar')
                ->where('tgl_berlaku_sampai', '>', now());
            $jenisTersedia = (clone $rekomendasi)->distinct()->pluck('jenis_rekomendasi');

            if (! $jenisTersedia->contains('penjualan') || ! $jenisTersedia->contains('pembelian')) {
                throw ValidationException::withMessages([
                    'status' => 'Rekomendasi sudah kedaluwarsa atau belum lengkap. Periksa stok dan ajukan pencocokan baru.',
                ]);
            }

            $rekomendasi->update(['status' => 'menunggu']);
            $permintaan->update(['status' => 'dilempar']);

            return false;
        });

        $pesan = $sudahDilempar
            ? 'Rekomendasi sudah pernah dilempar; tidak ada transaksi yang dibuat.'
            : 'Rekomendasi berhasil dilempar ke daftar Penjualan dan Pembelian. Transaksi tetap dipilih dan disimpan oleh masing-masing modul.';

        return redirect()->back()->with('success', $pesan);
    }

    private function hasilResmiTerbaru(HasilKalkulasi $hasil): bool
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

    /** Siapkan rekomendasi bilateral dan reservasi stok sumber sebelum keduanya dapat dilempar. */
    private function buatRekomendasiTransaksi(
        PermintaanAntarEntitas $permintaan,
        HasilKalkulasi $hasilPengaju,
        HasilKalkulasi $hasilPenerima,
    ): void {
        $pengajuMenjual = $permintaan->jenis_permintaan === 'tawaran_jual';
        $idPenjual = $pengajuMenjual
            ? (int) $permintaan->id_entitas_pengaju
            : (int) $permintaan->id_entitas_penerima;
        $idPembeli = $pengajuMenjual
            ? (int) $permintaan->id_entitas_penerima
            : (int) $permintaan->id_entitas_pengaju;
        $hasilPenjual = $pengajuMenjual ? $hasilPengaju : $hasilPenerima;
        $satuan = $this->satuanHasil($hasilPenjual);
        $idSatuan = $satuan === null
            ? null
            : DB::table('satuan')
                ->whereRaw('UPPER(TRIM(kode_satuan)) = ?', [$satuan])
                ->value('id');

        if (! $idSatuan) {
            throw ValidationException::withMessages([
                'status' => 'Satuan perencanaan belum terhubung ke master satuan; rekomendasi belum dapat diteruskan.',
            ]);
        }

        $pihakPenjual = $this->idPihakEntitasMitra($idPenjual, $idPembeli);
        $pihakPembeli = $this->idPihakEntitasMitra($idPembeli, $idPenjual);
        if (! $pihakPenjual || ! $pihakPembeli) {
            throw ValidationException::withMessages([
                'status' => 'Data rekanan antardesa belum lengkap di kedua entitas; rekomendasi belum dapat diteruskan.',
            ]);
        }

        $idGudangPenjual = DB::table('gudang')
            ->where('id_entitas', $idPenjual)
            ->where('is_active', true)
            ->orderBy('id_gudang')
            ->value('id_gudang');
        $idGudangPembeli = DB::table('gudang')
            ->where('id_entitas', $idPembeli)
            ->where('is_active', true)
            ->orderBy('id_gudang')
            ->value('id_gudang');

        if (! $idGudangPenjual || ! $idGudangPembeli) {
            throw ValidationException::withMessages([
                'status' => 'Kedua entitas harus memiliki gudang aktif sebelum stok dapat direservasi.',
            ]);
        }

        $batchTersedia = DB::table('stok_batch')
            ->select('id_gudang', 'id_barang')
            ->selectRaw('SUM(qty_on_hand) as qty_batch_belum_kedaluwarsa')
            ->where('qty_on_hand', '>', 0)
            ->where(fn ($query) => $query->whereNull('tanggal_kedaluwarsa')
                ->orWhereDate('tanggal_kedaluwarsa', '>=', now()->toDateString()))
            ->groupBy('id_gudang', 'id_barang');

        $faktor = 'CASE WHEN barang.id_satuan_dasar = ? THEN 1 ELSE NULLIF(konversi.faktor_ke_dasar, 0) END';
        $qtyLayak = 'CASE WHEN gudang.id_gudang IS NULL THEN 0 '
            .'WHEN COALESCE(pengaturan_penjual.lacak_batch, 0) = 1 '
            .'THEN COALESCE(batch.qty_batch_belum_kedaluwarsa, 0) '
            .'ELSE COALESCE(stok.qty_on_hand, 0) END';

        $barangTersedia = DB::table('master_barang as barang')
            ->join('barang_per_koperasi as pengaturan_penjual', function ($join) use ($idPenjual): void {
                $join->on('pengaturan_penjual.id_barang', '=', 'barang.id_barang')
                    ->where('pengaturan_penjual.id_entitas', $idPenjual)
                    ->where('pengaturan_penjual.is_dijual', true)
                    ->where('pengaturan_penjual.harga_jual_standar', '>', 0);
            })
            ->join('barang_per_koperasi as pengaturan_pembeli', function ($join) use ($idPembeli): void {
                $join->on('pengaturan_pembeli.id_barang', '=', 'barang.id_barang')
                    ->where('pengaturan_pembeli.id_entitas', $idPembeli);
            })
            ->join('satuan as satuan_dasar', 'satuan_dasar.id', '=', 'barang.id_satuan_dasar')
            ->leftJoin('konversi_satuan as konversi', function ($join) use ($idSatuan): void {
                $join->on('konversi.id_barang', '=', 'barang.id_barang')
                    ->where('konversi.id_satuan', $idSatuan);
            })
            ->leftJoin('stok', 'stok.id_barang', '=', 'barang.id_barang')
            ->join('gudang', function ($join) use ($idPenjual): void {
                $join->on('gudang.id_gudang', '=', 'stok.id_gudang')
                    ->where('gudang.id_entitas', $idPenjual)
                    ->where('gudang.is_active', true);
            })
            ->leftJoinSub($batchTersedia, 'batch', function ($join): void {
                $join->on('batch.id_gudang', '=', 'stok.id_gudang')
                    ->on('batch.id_barang', '=', 'stok.id_barang');
            })
            ->where('barang.is_active', true)
            ->where('barang.id_komoditas', $hasilPenjual->id_komoditas)
            ->where('gudang.is_active', true)
            ->where(fn ($query) => $query
                ->where('barang.id_satuan_dasar', $idSatuan)
                ->orWhere(fn ($converted) => $converted
                    ->whereNotNull('konversi.id_konversi')
                    ->where('konversi.faktor_ke_dasar', '>', 0)))
            ->groupBy(
                'barang.id_barang', 'barang.nama_barang', 'barang.id_unit_usaha',
                'barang.id_satuan_dasar', 'pengaturan_penjual.harga_jual_standar',
                'pengaturan_penjual.lacak_batch', 'konversi.faktor_ke_dasar', 'gudang.id_gudang',
            )
            ->select([
                'gudang.id_gudang', 'barang.id_barang', 'barang.nama_barang', 'barang.id_unit_usaha',
                'barang.id_satuan_dasar', 'pengaturan_penjual.harga_jual_standar',
                'pengaturan_penjual.lacak_batch',
            ])
            ->selectRaw($faktor.' as faktor_ke_dasar', [$idSatuan])
            ->selectRaw(
                'SUM(GREATEST(('.$qtyLayak.') - COALESCE(stok.qty_reserved, 0), 0) / '
                .$faktor.') as qty_tersedia_satuan_perencanaan',
                [$idSatuan],
            )
            ->orderBy('barang.id_barang')
            ->orderBy('gudang.id_gudang')
            ->get();

        $sisa = (float) $permintaan->qty_rekomendasi;
        $jumlahDialokasikan = 0.0;
        $alokasiPerBarang = [];
        $berlakuSampai = Carbon::parse($permintaan->disetujui_penuh_pada)->addHours(48);

        if (RekomendasiSurplusDefisit::query()
            ->where('id_pengajuan_gudang', $permintaan->id_permintaan_gudang)
            ->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Rekomendasi untuk permintaan ini sudah tersedia; periksa statusnya sebelum melanjutkan.',
            ]);
        }

        foreach ($barangTersedia as $barang) {
            if ($sisa <= 0.00005) {
                break;
            }

            $stok = DB::table('stok')
                ->where('id_gudang', $barang->id_gudang)
                ->where('id_barang', $barang->id_barang)
                ->lockForUpdate()
                ->first();

            if (! $stok) {
                continue;
            }

            $qtyLayak = (float) $stok->qty_on_hand;
            if ((bool) $barang->lacak_batch) {
                $qtyLayak = (float) DB::table('stok_batch')
                    ->where('id_gudang', $barang->id_gudang)
                    ->where('id_barang', $barang->id_barang)
                    ->where('qty_on_hand', '>', 0)
                    ->where(fn ($query) => $query->whereNull('tanggal_kedaluwarsa')
                        ->orWhereDate('tanggal_kedaluwarsa', '>=', now()->toDateString()))
                    ->sum('qty_on_hand');
            }

            $qtyTersediaDasar = max(0, $qtyLayak - (float) $stok->qty_reserved);
            $faktorKeDasar = (float) $barang->faktor_ke_dasar;
            $qtyTersediaPerencanaan = $faktorKeDasar > 0
                ? $qtyTersediaDasar / $faktorKeDasar
                : 0;
            $qtyItem = min($sisa, $qtyTersediaPerencanaan);
            $qtyItem = (float) number_format($qtyItem, 4, '.', '');
            if ($qtyItem <= 0) {
                continue;
            }

            $hargaStandar = (float) $barang->harga_jual_standar;
            $qtySatuanDasar = (float) bcmul(
                number_format($qtyItem, 4, '.', ''),
                number_format($faktorKeDasar, 6, '.', ''),
                4,
            );
            if ($hargaStandar <= 0 || ! $barang->id_unit_usaha) {
                continue;
            }

            DB::table('stok')
                ->where('id_gudang', $barang->id_gudang)
                ->where('id_barang', $barang->id_barang)
                ->update([
                    'qty_reserved' => bcadd((string) $stok->qty_reserved, (string) $qtySatuanDasar, 4),
                    'updated_at' => now(),
                ]);

            $alokasiPerBarang[$barang->id_barang]['barang'] = $barang;
            $alokasiPerBarang[$barang->id_barang]['jumlah_dasar'] =
                ($alokasiPerBarang[$barang->id_barang]['jumlah_dasar'] ?? 0.0) + $qtySatuanDasar;
            $alokasiPerBarang[$barang->id_barang]['alokasi'][] = [
                'id_gudang' => (int) $barang->id_gudang,
                'qty_dasar' => $qtySatuanDasar,
            ];
            $sisa -= $qtyItem;
            $jumlahDialokasikan += $qtyItem;
        }

        if ($jumlahDialokasikan <= 0.00005) {
            throw ValidationException::withMessages([
                'status' => 'Tidak ada stok layak yang dapat dialokasikan. Periksa stok, barang jual, harga standar, dan pengaturan kedua entitas.',
            ]);
        }

        foreach ($alokasiPerBarang as $idBarang => $alokasiBarang) {
            $barang = $alokasiBarang['barang'];
            foreach ($alokasiBarang['alokasi'] as $alokasi) {
                $this->simpanRekomendasi(
                    $permintaan,
                    $idPenjual,
                    $idPembeli,
                    (int) $idBarang,
                    (int) $alokasi['id_gudang'],
                    (string) $barang->nama_barang,
                    (int) $barang->id_satuan_dasar,
                    (float) $alokasi['qty_dasar'],
                    (float) $barang->harga_jual_standar,
                    (int) $barang->id_unit_usaha,
                    $berlakuSampai,
                    'penjualan',
                    $pihakPenjual,
                );
            }

            $this->simpanRekomendasi(
                $permintaan,
                $idPenjual,
                $idPembeli,
                (int) $idBarang,
                (int) $idGudangPembeli,
                (string) $barang->nama_barang,
                (int) $barang->id_satuan_dasar,
                (float) $alokasiBarang['jumlah_dasar'],
                (float) $barang->harga_jual_standar,
                (int) $barang->id_unit_usaha,
                $berlakuSampai,
                'pembelian',
                $pihakPembeli,
            );
        }

        $permintaan->qty_dialokasikan = number_format($jumlahDialokasikan, 4, '.', '');
    }

    private function simpanRekomendasi(
        PermintaanAntarEntitas $permintaan,
        int $idPenjual,
        int $idPembeli,
        int $idBarang,
        int $idGudang,
        string $namaBarang,
        int $idSatuan,
        float $qtyDasar,
        float $hargaStandar,
        int $idUnitUsaha,
        Carbon $berlakuSampai,
        string $jenis,
        int $idPihakMitra,
    ): void {
        RekomendasiSurplusDefisit::query()->firstOrCreate(
            [
                'id_pengajuan_gudang' => $permintaan->id_permintaan_gudang,
                'jenis_rekomendasi' => $jenis,
                'id_barang' => $idBarang,
                'id_gudang' => $idGudang,
            ],
            [
                'id_entitas_asal' => $idPenjual,
                'id_entitas_tujuan' => $idPembeli,
                'id_pihak_mitra' => $idPihakMitra,
                'id_barang' => $idBarang,
                'nama_barang' => $namaBarang,
                'id_satuan' => $idSatuan,
                'qty_disetujui' => number_format($qtyDasar, 4, '.', ''),
                'harga_satuan_kesepakatan' => number_format($hargaStandar, 2, '.', ''),
                'id_unit_usaha' => $idUnitUsaha,
                'tgl_berlaku_sampai' => $berlakuSampai,
                'status' => 'siap_dilempar',
            ],
        );
    }

    private function idPihakEntitasMitra(int $idEntitas, int $idMitra): ?int
    {
        $id = DB::table('master_pihak')
            ->where('id_entitas', $idEntitas)
            ->where('id_entitas_mitra', $idMitra)
            ->where('jenis_pihak', 'entitas_lain')
            ->where('is_active', true)
            ->value('id_pihak');

        return $id === null ? null : (int) $id;
    }

}
