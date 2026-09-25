<?php

use App\Http\Controllers\Akuntansi\AsetTetapController;
use App\Http\Controllers\Akuntansi\ConfigShuController;
use App\Http\Controllers\Akuntansi\JurnalController;
use App\Http\Controllers\Akuntansi\LaporanController;
use App\Http\Controllers\Akuntansi\TutupBulanController;
use App\Http\Controllers\Akuntansi\TutupTahunController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Gudang\GudangDashboardController;
use App\Http\Controllers\Gudang\KartuStokController;
use App\Http\Controllers\Gudang\KerusakanBarangController;
use App\Http\Controllers\Gudang\OpnameController;
use App\Http\Controllers\Gudang\PenerimaanBarangController;
use App\Http\Controllers\Gudang\StokController;
use App\Http\Controllers\Keuangan\HutangController;
use App\Http\Controllers\Keuangan\KasTransaksiController;
use App\Http\Controllers\Keuangan\PelunasanController;
use App\Http\Controllers\Keuangan\PiutangController;
use App\Http\Controllers\Keuangan\SimpananController;
use App\Http\Controllers\Konsinyasi\MarketplaceController;
use App\Http\Controllers\Konsinyasi\PengirimanKonsinyasiController;
use App\Http\Controllers\Konsinyasi\RekonsiliasiController;
use App\Http\Controllers\Konsinyasi\SetoranKonsinyasiController;
use App\Http\Controllers\Konsinyasi\StokKonsinyasiController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Master\BarangController;
use App\Http\Controllers\Master\CoaController;
use App\Http\Controllers\Master\GudangController as MasterGudangController;
use App\Http\Controllers\Master\KasBankController;
use App\Http\Controllers\Master\KomoditasController;
use App\Http\Controllers\Master\KoperasiController;
use App\Http\Controllers\Master\PeriodeController;
use App\Http\Controllers\Master\PihakController;
use App\Http\Controllers\Master\SatuanController;
use App\Http\Controllers\Pembelian\PembelianController;
use App\Http\Controllers\Pembelian\ReturPembelianController;
use App\Http\Controllers\Penjualan\PenjualanController;
use App\Http\Controllers\Penjualan\ReturPenjualanController;
use App\Http\Controllers\Perencanaan\DemografiController;
use App\Http\Controllers\Perencanaan\KebutuhanKomoditasController;
use App\Http\Controllers\Perencanaan\NeracaKomoditasController;
use App\Http\Controllers\Perencanaan\PerbandinganHargaController;
use App\Http\Controllers\Perencanaan\PermintaanPengadaanController;
use App\Http\Controllers\Perencanaan\PotensiProduksiController;
use App\Http\Controllers\Perencanaan\StandarKebutuhanController;
use Survei\Controllers\PertanyaanController;
use Survei\Controllers\SesiSurveiController;
use Survei\Controllers\Sesi1DemografiController;
use Survei\Controllers\Sesi2ProduksiController;
use Survei\Controllers\Sesi3StandarKonsumsiController;
use Survei\Controllers\PublicSesi1Controller;
use Survei\Controllers\PublicSesi2Controller;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth')->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// ===== Public Routes =====
// Survei RT
Route::get('/survei/isi/rt/{token}', [\Survei\Controllers\PublicSurveiRtController::class, 'show'])->name('survei.public.rt.show');
Route::post('/survei/isi/rt/{token}', [\Survei\Controllers\PublicSurveiRtController::class, 'store'])->name('survei.public.rt.store');
Route::put('/survei/isi/rt/{token}/{id}', [\Survei\Controllers\PublicSurveiRtController::class, 'update'])->name('survei.public.rt.update');

// Survei Produsen (Kelompok Tani & Ekraf)
Route::get('/survei/isi/produsen/{token}', [\Survei\Controllers\PublicSurveiProdusenController::class, 'show'])->name('survei.public.produsen.show');
Route::post('/survei/isi/produsen/{token}', [\Survei\Controllers\PublicSurveiProdusenController::class, 'store'])->name('survei.public.produsen.store');
Route::delete('/survei/isi/produsen/{token}/{id}', [\Survei\Controllers\PublicSurveiProdusenController::class, 'destroy'])->name('survei.public.produsen.destroy');

// Survei Masyarakat (Data Keluarga & Alamat)
Route::get('/survei/isi/masyarakat/{token}', [\Survei\Controllers\PublicSurveiMasyarakatController::class, 'show'])
    ->name('survei.public.masyarakat.show');
Route::post('/survei/isi/masyarakat/{token}', [\Survei\Controllers\PublicSurveiMasyarakatController::class, 'store'])
    ->name('survei.public.masyarakat.store');

// Survei Masyarakat (Anggota Keluarga)
Route::get('/survei/isi/masyarakat/{token}/{id_masyarakat}/anggota', [\Survei\Controllers\PublicSurveiMasyarakatController::class, 'showAnggota'])
    ->name('survei.public.masyarakat.anggota.show');
Route::post('/survei/isi/masyarakat/{token}/{id_masyarakat}/anggota', [\Survei\Controllers\PublicSurveiMasyarakatController::class, 'storeAnggota'])
    ->name('survei.public.masyarakat.anggota.store');

// Survei Masyarakat (Sesi 3 - Konsumsi)
Route::get('/survei/isi/masyarakat/{token}/{id_masyarakat}/sesi3', [\Survei\Controllers\PublicMasyarakatSesi3Controller::class, 'show'])
    ->name('survei.public.masyarakat.sesi3.show');
Route::post('/survei/isi/masyarakat/{token}/{id_masyarakat}/sesi3', [\Survei\Controllers\PublicMasyarakatSesi3Controller::class, 'store'])
    ->name('survei.public.masyarakat.sesi3.store');

// Survei Masyarakat (Review & Submit)
Route::get('/survei/isi/masyarakat/{token}/{id_masyarakat}/review', [\Survei\Controllers\PublicMasyarakatReviewController::class, 'show'])
    ->name('survei.public.masyarakat.review.show');
Route::post('/survei/isi/masyarakat/{token}/{id_masyarakat}/submit', [\Survei\Controllers\PublicMasyarakatReviewController::class, 'submit'])
    ->name('survei.public.masyarakat.review.submit');
Route::get('/survei/isi/masyarakat/{token}/terimakasih', [\Survei\Controllers\PublicMasyarakatReviewController::class, 'terimakasih'])
    ->name('survei.public.masyarakat.terimakasih');

// Survei Masyarakat (Legacy Landing)
Route::get('/survei/isi/{token}', [\Survei\Controllers\PublicSurveiController::class, 'show'])->name('survei.public.show');
Route::post('/survei/isi/{token}', [\Survei\Controllers\PublicSurveiController::class, 'store'])->name('survei.public.store');
Route::get('/survei/isi/{token}/modul/{modul}', [\Survei\Controllers\PublicSurveiController::class, 'showModul'])->name('survei.public.modul.show');
Route::post('/survei/isi/{token}/modul/{modul}', [\Survei\Controllers\PublicSurveiController::class, 'storeModul'])->name('survei.public.modul.store');

// ─── Sesi 1 Demografi — Publik (tanpa login) ─────────────────────────────────
Route::get('/survei/isi/{token}/sesi1', [PublicSesi1Controller::class, 'show'])->name('survei.public.sesi1.show');
Route::post('/survei/isi/{token}/sesi1/narasumber', [PublicSesi1Controller::class, 'storeNarasumber'])->name('survei.public.sesi1.narasumber.store');
Route::put('/survei/isi/{token}/sesi1/narasumber/{narasumber}', [PublicSesi1Controller::class, 'updateNarasumber'])->name('survei.public.sesi1.narasumber.update');
Route::get('/survei/isi/{token}/sesi1/narasumber/{narasumber}', [PublicSesi1Controller::class, 'showNarasumber'])->name('survei.public.sesi1.narasumber.show');
Route::post('/survei/isi/{token}/sesi1/narasumber/{narasumber}/demografi', [PublicSesi1Controller::class, 'storeDemografi'])->name('survei.public.sesi1.demografi.store');
Route::delete('/survei/isi/{token}/sesi1/narasumber/{narasumber}', [PublicSesi1Controller::class, 'destroyNarasumber'])->name('survei.public.sesi1.narasumber.destroy');
Route::post('/survei/isi/{token}/sesi1/selesaikan', [PublicSesi1Controller::class, 'selesaikan'])->name('survei.public.sesi1.selesaikan');
Route::post('/survei/isi/{token}/sesi1/salin-sebelumnya', [PublicSesi1Controller::class, 'salinSebelumnya'])->name('survei.public.sesi1.salin_sebelumnya');

// ─── Sesi 2 Potensi Produksi — Publik (tanpa login) ─────────────────────────
Route::get('/survei/isi/{token}/sesi2', [PublicSesi2Controller::class, 'show'])->name('survei.public.sesi2.show');
Route::post('/survei/isi/{token}/sesi2/narasumber', [PublicSesi2Controller::class, 'storeNarasumber'])->name('survei.public.sesi2.narasumber.store');
Route::post('/survei/isi/{token}/sesi2/produksi', [PublicSesi2Controller::class, 'storeProduksi'])->name('survei.public.sesi2.produksi.store');
Route::put('/survei/isi/{token}/sesi2/produksi/{produksi}', [PublicSesi2Controller::class, 'updateProduksi'])->name('survei.public.sesi2.produksi.update');
Route::delete('/survei/isi/{token}/sesi2/produksi/{produksi}', [PublicSesi2Controller::class, 'destroyProduksi'])->name('survei.public.sesi2.produksi.destroy');
Route::post('/survei/isi/{token}/sesi2/simpan-draft', [PublicSesi2Controller::class, 'simpanDraft'])->name('survei.public.sesi2.draft');
Route::post('/survei/isi/{token}/sesi2/selesaikan', [PublicSesi2Controller::class, 'selesaikan'])->name('survei.public.sesi2.selesaikan');

// ─── Sesi 3 Standar Konsumsi — Publik (tanpa login) ────────────────────────
Route::get('/survei/isi/{token}/sesi3', [\Survei\Controllers\PublicSesi3Controller::class, 'show'])->name('survei.public.sesi3.show');
Route::post('/survei/isi/{token}/sesi3/standar', [\Survei\Controllers\PublicSesi3Controller::class, 'store'])->name('survei.public.sesi3.standar.store');
Route::post('/survei/isi/{token}/sesi3/salin-dari-sesi2', [\Survei\Controllers\PublicSesi3Controller::class, 'salinDariSesi2'])->name('survei.public.sesi3.salin.sesi2');
Route::delete('/survei/isi/{token}/sesi3/standar', [\Survei\Controllers\PublicSesi3Controller::class, 'destroy'])->name('survei.public.sesi3.standar.destroy');
Route::post('/survei/isi/{token}/sesi3/simpan-draft', [\Survei\Controllers\PublicSesi3Controller::class, 'simpanDraft'])->name('survei.public.sesi3.draft');
Route::post('/survei/isi/{token}/sesi3/selesaikan', [\Survei\Controllers\PublicSesi3Controller::class, 'selesaikan'])->name('survei.public.sesi3.selesaikan');

// ─── Sesi 4 Pemenuhan & Harga — Publik (tanpa login) ──────────────────────
Route::get('/survei/isi/{token}/sesi4', [\Survei\Controllers\PublicSesi4Controller::class, 'show'])->name('survei.public.sesi4.show');
Route::post('/survei/isi/{token}/sesi4/pemenuhan', [\Survei\Controllers\PublicSesi4Controller::class, 'store'])->name('survei.public.sesi4.pemenuhan.store');
Route::delete('/survei/isi/{token}/sesi4/pemenuhan/{pemenuhan}', [\Survei\Controllers\PublicSesi4Controller::class, 'destroy'])->name('survei.public.sesi4.pemenuhan.destroy');
Route::post('/survei/isi/{token}/sesi4/simpan-draft', [\Survei\Controllers\PublicSesi4Controller::class, 'simpanDraft'])->name('survei.public.sesi4.draft');
Route::post('/survei/isi/{token}/sesi4/selesaikan', [\Survei\Controllers\PublicSesi4Controller::class, 'selesaikan'])->name('survei.public.sesi4.selesaikan');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== M0 — Master & Periode =====
    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('koperasi', KoperasiController::class);
        Route::resource('komoditas', KomoditasController::class);
        Route::resource('satuan', SatuanController::class);
        Route::resource('barang', BarangController::class);
        Route::resource('pihak', PihakController::class);
        Route::resource('kas-bank', KasBankController::class);
        Route::resource('gudang', MasterGudangController::class);
        Route::resource('periode', PeriodeController::class)->only(['index', 'edit', 'update']);
        Route::resource('coa', CoaController::class);
    });

    // ===== M1 — Survey =====
    Route::prefix('survei')->name('survei.')->group(function () {
        Route::view('dashboard', 'survei.dashboard')->name('dashboard.index');
        Route::resource('sesi', SesiSurveiController::class);
        
        // Master Data Baru
        Route::resource('rt', \App\Http\Controllers\Survei\RtDesaController::class);
        Route::resource('produsen', \App\Http\Controllers\Survei\ProdusenDesaController::class);
        // Hapus entri komoditas produksi dari halaman admin
        Route::delete('produksi/{id}', function (int $id) {
            $item = \App\Models\Survei\ProduksiProdusen::findOrFail($id);
            $item->delete();
            return redirect()->route('survei.produsen.index')->with('success', 'Data komoditas berhasil dihapus.');
        })->name('produksi.destroy');
        Route::get('validasi', [\App\Http\Controllers\Survei\ValidasiSurveiController::class, 'index'])->name('validasi.index');
        Route::get('validasi/rt/{id_sesi}/{nama_rt}/{rw?}', [\App\Http\Controllers\Survei\ValidasiSurveiController::class, 'showRtDetail'])->name('validasi.rt.show');

        Route::resource('pertanyaan', PertanyaanController::class);

        // ─── Sesi 1: Data Demografi Desa ─────────────────────────────────────
        Route::get('sesi/{sesi}/sesi1', [Sesi1DemografiController::class, 'show'])
            ->name('sesi1.show');
        Route::post('sesi/{sesi}/sesi1/narasumber', [Sesi1DemografiController::class, 'storeNarasumber'])
            ->name('sesi1.narasumber.store');
        Route::put('sesi/{sesi}/sesi1/narasumber/{narasumber}', [Sesi1DemografiController::class, 'updateNarasumber'])
            ->name('sesi1.narasumber.update');
        Route::get('sesi/{sesi}/sesi1/narasumber/{narasumber}', [Sesi1DemografiController::class, 'showNarasumber'])
            ->name('sesi1.narasumber.show');
        Route::post('sesi/{sesi}/sesi1/narasumber/{narasumber}/demografi', [Sesi1DemografiController::class, 'storeDemografi'])
            ->name('sesi1.demografi.store');
        Route::delete('sesi/{sesi}/sesi1/narasumber/{narasumber}', [Sesi1DemografiController::class, 'destroyNarasumber'])
            ->name('sesi1.narasumber.destroy');
        Route::post('sesi/{sesi}/sesi1/simpan-draft', [Sesi1DemografiController::class, 'simpanDraft'])
            ->name('sesi1.draft');
        Route::post('sesi/{sesi}/sesi1/selesaikan', [Sesi1DemografiController::class, 'selesaikan'])
            ->name('sesi1.selesaikan');
        Route::post('sesi/{sesi}/sesi1/salin-sebelumnya', [Sesi1DemografiController::class, 'salinSebelumnya'])
            ->name('sesi1.salin_sebelumnya');

        // ─── Sesi 2: Potensi Produksi Desa ───────────────────────────────────
        Route::get('sesi/{sesi}/sesi2', [Sesi2ProduksiController::class, 'show'])
            ->name('sesi2.show');
        Route::post('sesi/{sesi}/sesi2/narasumber', [Sesi2ProduksiController::class, 'storeNarasumber'])
            ->name('sesi2.narasumber.store');
        Route::put('sesi/{sesi}/sesi2/narasumber/{narasumber}', [Sesi2ProduksiController::class, 'updateNarasumber'])
            ->name('sesi2.narasumber.update');
        Route::delete('sesi/{sesi}/sesi2/narasumber/{narasumber}', [Sesi2ProduksiController::class, 'destroyNarasumber'])
            ->name('sesi2.narasumber.destroy');
        Route::post('sesi/{sesi}/sesi2/produksi', [Sesi2ProduksiController::class, 'storeProduksi'])
            ->name('sesi2.produksi.store');
        Route::put('sesi/{sesi}/sesi2/produksi/{produksi}', [Sesi2ProduksiController::class, 'updateProduksi'])
            ->name('sesi2.produksi.update');
        Route::delete('sesi/{sesi}/sesi2/produksi/{produksi}', [Sesi2ProduksiController::class, 'destroyProduksi'])
            ->name('sesi2.produksi.destroy');
        Route::post('sesi/{sesi}/sesi2/simpan-draft', [Sesi2ProduksiController::class, 'simpanDraft'])
            ->name('sesi2.draft');
        Route::post('sesi/{sesi}/sesi2/selesaikan', [Sesi2ProduksiController::class, 'selesaikan'])
            ->name('sesi2.selesaikan');

        // ─── Sesi 3: Standar Konsumsi Komoditas ──────────────────────────────
        Route::get('sesi/{sesi}/sesi3', [Sesi3StandarKonsumsiController::class, 'show'])
            ->name('sesi3.show');
        Route::post('sesi/{sesi}/sesi3/standar', [Sesi3StandarKonsumsiController::class, 'store'])
            ->name('sesi3.standar.store');
        Route::post('sesi/{sesi}/sesi3/salin-dari-sesi2', [Sesi3StandarKonsumsiController::class, 'salinDariSesi2'])
            ->name('sesi3.salin.sesi2');
        Route::delete('sesi/{sesi}/sesi3/standar', [Sesi3StandarKonsumsiController::class, 'destroy'])
            ->name('sesi3.standar.destroy');
        Route::post('sesi/{sesi}/sesi3/simpan-draft', [Sesi3StandarKonsumsiController::class, 'simpanDraft'])
            ->name('sesi3.draft');
        Route::post('sesi/{sesi}/sesi3/selesaikan', [Sesi3StandarKonsumsiController::class, 'selesaikan'])
            ->name('sesi3.selesaikan');

        // ─── Sesi 4: Pemenuhan & Harga Komoditas ──────────────────────────────
        Route::get('sesi/{sesi}/sesi4', [\Survei\Controllers\Sesi4PemenuhanController::class, 'show'])
            ->name('sesi4.show');
        Route::post('sesi/{sesi}/sesi4/pemenuhan', [\Survei\Controllers\Sesi4PemenuhanController::class, 'store'])
            ->name('sesi4.pemenuhan.store');
        Route::delete('sesi/{sesi}/sesi4/pemenuhan/{pemenuhan}', [\Survei\Controllers\Sesi4PemenuhanController::class, 'destroy'])
            ->name('sesi4.pemenuhan.destroy');
        Route::post('sesi/{sesi}/sesi4/simpan-draft', [\Survei\Controllers\Sesi4PemenuhanController::class, 'simpanDraft'])
            ->name('sesi4.draft');
        Route::post('sesi/{sesi}/sesi4/selesaikan', [\Survei\Controllers\Sesi4PemenuhanController::class, 'selesaikan'])
            ->name('sesi4.selesaikan');
    });

    // ===== M2/M3 — Kalkulasi Kebutuhan & Perencanaan =====
    Route::prefix('perencanaan')->name('perencanaan.')->group(function () {
        Route::resource('demografi', DemografiController::class);
        Route::resource('potensi-produksi', PotensiProduksiController::class);
        Route::resource('standar-kebutuhan', StandarKebutuhanController::class);
        Route::resource('kebutuhan-komoditas', KebutuhanKomoditasController::class)
            ->only(['index', 'show']);
        Route::resource('neraca-komoditas', NeracaKomoditasController::class)
            ->only(['index', 'show']);
        Route::resource('perbandingan-harga', PerbandinganHargaController::class);
        Route::resource('permintaan-pengadaan', PermintaanPengadaanController::class);
    });

    // ===== M4 — Gudang =====
    Route::prefix('gudang')->name('gudang.')->group(function () {
        Route::get('/', [GudangDashboardController::class, 'index'])->name('index');
        Route::resource('penerimaan', PenerimaanBarangController::class);
        Route::resource('opname', OpnameController::class);
        Route::resource('kerusakan', KerusakanBarangController::class);
        Route::resource('kartu-stok', KartuStokController::class)->only(['index', 'show']);
        Route::resource('stok', StokController::class)->only(['index', 'show']);
    });

    // ===== M5 â€” Pembelian =====
    Route::prefix('pembelian')->name('pembelian.')->group(function () {
    Route::resource('pembelian', PembelianController::class);

    Route::patch('pembelian/{pembelian}/approve', [PembelianController::class, 'approve'])
        ->name('approve');

    Route::patch('pembelian/{pembelian}/cancel', [PembelianController::class, 'cancel'])
        ->name('cancel');

    Route::get('pembelian/{pembelian}/grn/create', [PembelianController::class, 'createGrn'])
        ->name('create-grn');

    Route::post('pembelian/{pembelian}/grn', [PembelianController::class, 'storeGrn'])
        ->name('store-grn');

    Route::get('pembelian/{pembelian}/retur', [PembelianController::class, 'showRetur'])
        ->name('show-retur');

    Route::resource('retur', ReturPembelianController::class);
    Route::patch('retur/{retur}/approve', [ReturPembelianController::class, 'approve'])
        ->name('retur.approve');
    });

    // ===== M6 — Penjualan =====
    Route::prefix('penjualan')->name('penjualan.')->group(function () {
        Route::resource('penjualan', PenjualanController::class);
        Route::resource('retur', ReturPenjualanController::class);
    });

    // ===== M7 — Konsinyasi Antar Desa =====
    Route::prefix('konsinyasi')->name('konsinyasi.')->group(function () {
        Route::resource('marketplace', MarketplaceController::class);
        Route::get('pengiriman/{id}/posting', [PengirimanKonsinyasiController::class, 'postingPage'])
            ->name('pengiriman.posting-page');
        Route::post('pengiriman/{id}/posting', [PengirimanKonsinyasiController::class, 'posting'])
            ->name('pengiriman.posting');
        Route::resource('pengiriman', PengirimanKonsinyasiController::class);
        Route::resource('stok', StokKonsinyasiController::class)->only(['index', 'show']);
        Route::resource('setoran', SetoranKonsinyasiController::class);
        Route::get('rekonsiliasi', [RekonsiliasiController::class, 'index'])->name('rekonsiliasi.index');
    });

    // ===== M8/M9 — Keuangan & Akuntansi (fokus pendalaman besok) =====
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::resource('piutang', PiutangController::class)->only(['index', 'show']);
        Route::resource('hutang', HutangController::class)->only(['index', 'show']);
        // AJAX: ambil piutang/hutang terbuka milik pihak tertentu (harus SEBELUM resource)
        Route::get('pelunasan/pihak/{pihak}/terbuka', [PelunasanController::class, 'terbuka'])
            ->name('pelunasan.terbuka');
        // Pelunasan tidak bisa di-edit/hapus setelah posted — pembatalan via jurnal balik
        Route::resource('pelunasan', PelunasanController::class)->except(['edit', 'update', 'destroy']);
        Route::resource('kas-transaksi', KasTransaksiController::class);
        Route::resource('simpanan', SimpananController::class);
    });

    Route::prefix('akuntansi')->name('akuntansi.')->group(function () {
        Route::resource('jurnal', JurnalController::class)->only(['index', 'show']);
        Route::resource('aset-tetap', AsetTetapController::class);
        Route::resource('config-shu', ConfigShuController::class);
        Route::get('tutup-bulan', [TutupBulanController::class, 'index'])->name('tutup-bulan.index');
        Route::post('tutup-bulan', [TutupBulanController::class, 'store'])->name('tutup-bulan.store');
        Route::get('tutup-tahun', [TutupTahunController::class, 'index'])->name('tutup-tahun.index');
        Route::post('tutup-tahun', [TutupTahunController::class, 'store'])->name('tutup-tahun.store');

        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('neraca-saldo', [LaporanController::class, 'neracaSaldo'])->name('neraca-saldo');
            Route::get('buku-besar', [LaporanController::class, 'bukuBesar'])->name('buku-besar');
            Route::get('neraca', [LaporanController::class, 'neraca'])->name('neraca');
            Route::get('laba-rugi', [LaporanController::class, 'labaRugi'])->name('laba-rugi');
            Route::get('arus-kas', [LaporanController::class, 'arusKas'])->name('arus-kas');
        });
    });
});