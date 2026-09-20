<?php

namespace Survei\Controllers;

use App\Http\Controllers\Controller;
use Survei\Models\SesiSurvei;
use App\Models\Survei\ProduksiProdusen;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PublicSurveiProdusenController extends Controller
{
    /**
     * Daftar komoditas yang disarankan — sesuai sistemsurvey.txt poin 4.
     * Kelompok Tani: hanya komoditas pangan pokok sehari-hari yang disebutkan di .txt.
     * Ekraf: .txt tidak menyebut item spesifik (hanya "Kreativitas Pangan/Benda Desa"),
     *         sehingga tidak ada chip saran — responden isi sendiri.
     */
    private array $saranKomoditas = [
        'Pertanian'  => ['Beras', 'Cabai', 'Bawang Merah', 'Bawang Putih', 'Kedelai'],
        'Perkebunan' => ['Kopi', 'Gula'],
        'Peternakan' => ['Ayam', 'Telur Ayam'],
        'Kelautan'   => ['Garam'],
        'Pangan'     => [],   // Ekraf Pangan: kreativitas bebas, tidak ada daftar tetap di .txt
        'Non Pangan' => [],   // Ekraf Non Pangan: kreativitas bebas, tidak ada daftar tetap di .txt
    ];

    /** Satuan umum */
    private array $satuanList = ['kg', 'ton', 'kwintal', 'gram', 'liter', 'buah', 'ikat', 'pack', 'karung', 'lusin'];

    /**
     * Tampilkan halaman survei publik Produsen.
     */
    public function show(string $token): View
    {
        $sesi = SesiSurvei::where('token_produsen', $token)
            ->with(['wilayah'])
            ->firstOrFail();

        // Validasi periode: hanya boleh diakses bulan aktif
        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, 'Tautan survei Produsen ini sudah kedaluwarsa. Survei hanya berlaku untuk periode '
                . $sesi->bulan . '/' . $sesi->tahun . '.');
        }

        $bulans = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('survei.public.produsen', [
            'sesi'           => $sesi,
            'token'          => $token,
            'bulans'         => $bulans,
            'saranKomoditas' => $this->saranKomoditas,
            'satuanList'     => $this->satuanList,
        ]);
    }

    /**
     * Simpan batch komoditas dalam satu POST.
     * Payload: nama_responden + komoditas[] (array of items)
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_produsen', $token)->firstOrFail();

        if ($sesi->bulan != (int) date('n') || $sesi->tahun != (int) date('Y')) {
            abort(403, 'Tautan survei ini sudah kedaluwarsa.');
        }

        $request->validate([
            'nama_responden'    => 'required|string|max:100',
            'komoditas'         => 'required|array|min:1',
            'komoditas.*.nama_komoditas'  => 'required|string|max:100',
            'komoditas.*.kategori'        => 'required|in:Kelompok Tani,Ekraf',
            'komoditas.*.sub_kategori'    => 'nullable|string|max:50',
            'komoditas.*.jumlah_produksi' => 'nullable|numeric|min:0',
            'komoditas.*.satuan'          => 'nullable|string|max:30',
            'komoditas.*.bulan_produksi'  => 'required|integer|min:1|max:12',
            'komoditas.*.keterangan'      => 'nullable|string|max:500',
        ], [
            'komoditas.required'                   => 'Harap tambah minimal 1 komoditas ke daftar.',
            'komoditas.*.nama_komoditas.required'  => 'Nama komoditas tidak boleh kosong.',
            'komoditas.*.bulan_produksi.required'  => 'Bulan produksi wajib dipilih.',
        ]);

        // Bersihkan nama responden dari titik (voice-to-text)
        $namaResponden = ucwords(strtolower(trim(str_replace('.', '', $request->nama_responden))));

        $rows = [];
        $now  = now();

        foreach ($request->komoditas as $item) {
            $rows[] = [
                'id_sesi'         => $sesi->id,
                'id_wilayah'      => $sesi->id_wilayah,
                'nama_responden'  => $namaResponden,
                'kategori'        => $item['kategori'],
                'sub_kategori'    => $item['sub_kategori'] ?? null,
                'nama_komoditas'  => ucwords(strtolower(trim(str_replace('.', '', $item['nama_komoditas'])))),
                'jumlah_produksi' => filled($item['jumlah_produksi']) ? $item['jumlah_produksi'] : null,
                'satuan'          => $item['satuan'] ?? null,
                'bulan_produksi'  => (int) $item['bulan_produksi'],
                'tahun_produksi'  => $sesi->tahun,
                'keterangan'      => filled($item['keterangan'] ?? null)
                    ? trim(str_replace('.', '', $item['keterangan']))
                    : null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        ProduksiProdusen::insert($rows);

        $jumlah = count($rows);
        return redirect()->back()->with('success',
            "✅ Terima kasih, {$namaResponden}! {$jumlah} komoditas berhasil dicatat."
        );
    }

    /**
     * Hapus satu entri produksi (dari admin, bukan publik — tapi sediakan jika diperlukan).
     */
    public function destroy(string $token, int $id): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_produsen', $token)->firstOrFail();

        $item = ProduksiProdusen::where('id', $id)->where('id_sesi', $sesi->id)->firstOrFail();
        $item->delete();

        return redirect()->back()->with('success', 'Data produksi berhasil dihapus.');
    }
}
