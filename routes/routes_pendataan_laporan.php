<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cuplikan Rute: Master Komoditas, Pendataan Desa, dan Laporan
|--------------------------------------------------------------------------
|
| Salin atau masukkan rute di bawah ini ke dalam routes/web.php di proyek
| tujuan di dalam grup middleware auth Anda:
|
| Route::middleware('auth')->group(function () {
|     require __DIR__ . '/routes_pendataan_laporan.php';
| });
|
*/

// ===== 1. MASTER KOMODITAS (HIERARKI 3 TINGKAT) =====
Route::prefix('master')->name('master.')->group(function () {
    Route::resource('kategori-komoditas', \App\Http\Controllers\Master\KategoriKomoditasController::class)
        ->parameters(['kategori-komoditas' => 'kategoriKomoditas']);

    Route::resource('master-komoditas', \App\Http\Controllers\Master\MasterKomoditasController::class)
        ->parameters(['master-komoditas' => 'masterKomoditas']);

    Route::resource('item-komoditas', \App\Http\Controllers\Master\ItemKomoditasController::class)
        ->parameters(['item-komoditas' => 'itemKomoditas']);

    Route::resource('pertanyaan', \App\Http\Controllers\Master\MasterPertanyaanController::class)
        ->parameters(['pertanyaan' => 'pertanyaan']);
});

// ===== 2. PENDATAAN DESA (WARGA, PETANI, POTENSI) =====
Route::prefix('pendataan')->name('pendataan.')->group(function () {
    Route::resource('penduduk', \App\Http\Controllers\Pendataan\PendudukDesaController::class);
    Route::resource('petani', \App\Http\Controllers\Pendataan\PetaniController::class);
    Route::get('potensi', fn () => redirect()->route('pendataan.petani.index'))->name('potensi.index');
    Route::resource('potensi', \App\Http\Controllers\Pendataan\PotensiKomoditasController::class)->except(['index']);
});

// ===== 3. LAPORAN & ANALISIS POTENSI =====
Route::prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('laporan.potensi.index');
    })->name('index');
    Route::get('demografi', [\App\Http\Controllers\Laporan\LaporanDemografiController::class, 'index'])->name('demografi.index');
    Route::get('potensi-desa', [\App\Http\Controllers\Laporan\LaporanPotensiController::class, 'index'])->name('potensi.index');
    Route::get('petani-komoditas', [\App\Http\Controllers\Laporan\LaporanPetaniKomoditasController::class, 'index'])->name('petani-komoditas.index');
    Route::get('analisis-potensi', [\App\Http\Controllers\Laporan\LaporanAnalisisPotensiController::class, 'index'])->name('analisis.index');
});
