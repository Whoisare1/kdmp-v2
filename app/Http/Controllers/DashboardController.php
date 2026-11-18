<?php

namespace App\Http\Controllers;

use App\Models\Akuntansi\ConfigShu;
use App\Models\Akuntansi\JurnalHeader;
use App\Models\Gudang\AntreanJurnalGudang;
use App\Models\Gudang\KerusakanBarang;
use App\Models\Gudang\OpnameHeader;
use App\Models\Gudang\PenerimaanBarang;
use App\Models\Gudang\Stok;
use App\Models\Keuangan\Hutang;
use App\Models\Keuangan\Piutang;
use App\Models\Konsinyasi\PengirimanKonsinyasi;
use App\Models\Master\Barang;
use App\Models\Master\KasBank;
use App\Models\Master\Komoditas;
use App\Models\Pembelian\Pembelian;
use App\Models\Penjualan\Penjualan;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\PermintaanPengadaan;
use App\Models\Survei\SesiSurvei;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\PeriodeAkuntansi;
use App\Models\Tenant\Wilayah;
use App\Services\DemoPeranService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $katalogPeran = DemoPeranService::katalogPeran();
        $alurE2e = DemoPeranService::alurEndToEnd();
        $user = $request->user();

        $requestedPeran = $request->query('peran');
        if ($requestedPeran && isset($katalogPeran[$requestedPeran])) {
            $request->session()->put('demo_peran_aktif', $requestedPeran);
            $peranAktif = $requestedPeran;
        } else {
            $peranAktif = DemoPeranService::kodePeranAktif($user);
        }

        $daftarEntitas = Entitas::withoutGlobalScopes()
            ->with(['wilayah', 'pengguna'])
            ->withCount('pengguna')
            ->where('is_active', true)
            ->orderBy('id_entitas')
            ->get();

        $entitasAktifId = (int) ($user?->id_entitas ?? session('entitas_aktif_pilihan') ?? $daftarEntitas->first()?->id_entitas);
        $entitasAktifObj = $daftarEntitas->firstWhere('id_entitas', $entitasAktifId);
        $wilayahAktifId = $entitasAktifObj?->id_wilayah;

        $daftarWilayah = Wilayah::orderBy('nama')->get();

        // 1. Data Katalog Komoditas Terdaftar Lintas Desa
        $daftarKomoditas = Komoditas::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();

        $kategoriTersedia = $daftarKomoditas->pluck('kategori')->filter()->unique()->values();

        // 2. Data Pemetaan Neraca Ketahanan Pangan Antar-Desa (Surplus / Defisit)
        $periodeTerbaru = HasilKalkulasi::query()
            ->select('tahun', 'bulan')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->first();

        $tahunAktif = (int) ($request->query('tahun') ?: ($periodeTerbaru->tahun ?? date('Y')));
        $bulanAktif = (int) ($request->query('bulan') ?: ($periodeTerbaru->bulan ?? 1));

        $neracaSurplusDefisit = HasilKalkulasi::with(['wilayah', 'komoditas'])
            ->where('tahun', $tahunAktif)
            ->where('bulan', $bulanAktif)
            ->orderBy('id_wilayah')
            ->orderByDesc('selisih')
            ->get();

        $totalSurplus = $neracaSurplusDefisit->where('status_surplus_defisit', 'surplus')->count();
        $totalDefisit = $neracaSurplusDefisit->where('status_surplus_defisit', 'defisit')->count();

        // 3. Progres Alur Bisnis End-to-End (Tahap 1 s/d 9) untuk Entitas Aktif
        $progressE2e = [
            // Tahap 1: M0 Master & Saldo Awal
            'kas_bank'        => KasBank::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->count(),
            'barang'          => Barang::forEntitas($entitasAktifId)->count(),
            'saldo_awal'      => JurnalHeader::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->where('jenis_jurnal', 'SALDO_AWAL')->where('status', 'POSTED')->count(),
            // Tahap 2: M1 Survei Lapangan
            'sesi_survei'     => $wilayahAktifId ? SesiSurvei::where('id_wilayah', $wilayahAktifId)->count() : SesiSurvei::count(),
            // Tahap 3: M2 & M3 Neraca & PR
            'hasil_kalkulasi' => $wilayahAktifId ? HasilKalkulasi::where('id_wilayah', $wilayahAktifId)->count() : HasilKalkulasi::count(),
            'pr'              => PermintaanPengadaan::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->count(),
            // Tahap 4: M5 Pembelian
            'pembelian'       => Pembelian::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->count(),
            // Tahap 5: M4 Penerimaan & Stok
            'penerimaan'      => PenerimaanBarang::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->count(),
            'stok_tersedia'   => Stok::whereHas('gudang', fn ($q) => $q->withoutGlobalScopes()->where('id_entitas', $entitasAktifId))->where('qty_on_hand', '>', 0)->count(),
            // Tahap 6: M6 Penjualan & M7 Konsinyasi
            'penjualan'       => Penjualan::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->count(),
            'konsinyasi'      => PengirimanKonsinyasi::where(fn ($q) => $q->where('id_entitas_pemilik', $entitasAktifId)->orWhere('id_entitas_penerima', $entitasAktifId))->count(),
            // Tahap 7: M4 Kerusakan & Opname
            'kerusakan'       => KerusakanBarang::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->count(),
            'opname'          => OpnameHeader::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->count(),
            // Tahap 8: M8/M9 Outbox, Jurnal & Keuangan
            'jurnal_posted'   => JurnalHeader::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->where('status', 'POSTED')->count(),
            'outbox_pending'  => AntreanJurnalGudang::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->whereIn('status', ['MENUNGGU', 'GAGAL'])->count(),
            'piutang_terbuka' => Piutang::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->whereIn('status', ['belum_lunas', 'sebagian'])->count(),
            'hutang_terbuka'  => Hutang::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->whereIn('status', ['belum_lunas', 'sebagian'])->count(),
            // Tahap 9: M8 Tutup Bulan & Tutup Tahun
            'periode_closed'  => PeriodeAkuntansi::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->whereIn('status', ['CLOSED', 'LOCKED'])->count(),
            'config_shu'      => ConfigShu::withoutGlobalScopes()->where('id_entitas', $entitasAktifId)->count(),
        ];

        // 4. Ringkasan Metrik Cepat (KPI Strip)
        $ringkasan = [
            'jumlah_barang'    => $progressE2e['barang'],
            'jumlah_komoditas' => $daftarKomoditas->count(),
            'jumlah_entitas'   => $daftarEntitas->count(),
            'jumlah_penjualan' => $progressE2e['penjualan'],
            'piutang_terbuka'  => $progressE2e['piutang_terbuka'],
            'hutang_terbuka'   => $progressE2e['hutang_terbuka'],
            'total_surplus'    => $totalSurplus,
            'total_defisit'    => $totalDefisit,
        ];

        return view('dashboard', compact(
            'ringkasan',
            'katalogPeran',
            'alurE2e',
            'progressE2e',
            'peranAktif',
            'daftarEntitas',
            'daftarWilayah',
            'daftarKomoditas',
            'kategoriTersedia',
            'neracaSurplusDefisit',
            'tahunAktif',
            'bulanAktif'
        ));
    }
}
