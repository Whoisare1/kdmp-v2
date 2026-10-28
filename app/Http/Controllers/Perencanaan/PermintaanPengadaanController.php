<?php

namespace App\Http\Controllers\Perencanaan;

use App\Http\Controllers\Concerns\ModuleCrudController;
use App\Models\Master\Barang;
use App\Models\Perencanaan\HasilKalkulasi;
use App\Models\Perencanaan\PermintaanPengadaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermintaanPengadaanController extends ModuleCrudController
{
    protected string $model = PermintaanPengadaan::class;
    protected string $view = 'perencanaan.permintaan-pengadaan';
    protected string $title = 'Permintaan Pengadaan (PR)';
    protected string $routeBase = 'perencanaan.permintaan-pengadaan';
    protected array $withRelations = ['pihak'];

    public function fromHasil(HasilKalkulasi $hasil): RedirectResponse
    {
        $this->assertHasilBisaDiproses($hasil);

        return redirect()->route('perencanaan.permintaan-pengadaan.create', [
            'id_hasil' => $hasil->id_hasil,
        ]);
    }

    public function create(): View
    {
        $idHasil = request()->query('id_hasil');
        $hasil = $idHasil ? HasilKalkulasi::with(['komoditas', 'wilayah'])->find($idHasil) : null;

        if ($idHasil !== null && ! $hasil) {
            abort(404, 'Hasil kalkulasi tidak ditemukan.');
        }

        if ($hasil) {
            $this->assertHasilBisaDiproses($hasil);
        }

        $barang = $hasil
            ? Barang::query()
                ->where('id_komoditas', $hasil->id_komoditas)
                ->where('is_active', true)
                ->first()
            : null;

        return view('perencanaan.permintaan-pengadaan.create', [
            'title' => 'PR - Pengadaan Defisit',
            'routeBase' => $this->routeBase,
            'hasil' => $hasil,
            'barang' => $barang,
            'jumlahDiminta' => $hasil ? max(0, abs((float) $hasil->selisih)) : 0,
            'tanggalPengajuan' => now()->toDateString(),
        ]);
    }

    private function assertHasilBisaDiproses(HasilKalkulasi $hasil): void
    {
        $user = auth()->user();

        if (! $user || ! $user->id_koperasi) {
            abort(403, 'Hanya pengguna desa yang dapat membuat PR.');
        }

        $idWilayahUser = DB::table('koperasi_desa')
            ->where('id_koperasi', $user->id_koperasi)
            ->value('id_wilayah');

        if ((int) $hasil->id_wilayah !== (int) $idWilayahUser) {
            abort(403, 'Hasil kalkulasi tidak milik koperasi aktif Anda.');
        }

        if ($hasil->status_surplus_defisit !== 'defisit') {
            abort(422, 'Hanya komoditas dengan status defisit yang dapat dibuatkan PR.');
        }
    }
}
