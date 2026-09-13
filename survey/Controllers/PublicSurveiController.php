<?php

namespace Survei\Controllers;

use App\Http\Controllers\Controller;
use Survei\Models\SesiSurvei;
use Survei\Models\ModulSurvei;
use Survei\Models\Pertanyaan;
use Survei\Models\Jawaban;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PublicSurveiController extends Controller
{
    /** Landing page: daftar modul + status pengisian masing-masing. */
    public function show(string $token): View
    {
        $sesi = SesiSurvei::where('token_publik', $token)
            ->with(['wilayah.parent.parent', 'petugas'])
            ->firstOrFail();

        $moduls = ModulSurvei::where('is_active', true)
            ->with(['pertanyaan' => fn($q) => $q->where('is_active', true)])
            ->orderBy('id')
            ->get();

        // Hitung status tiap modul berdasarkan jawaban yang sudah masuk
        $idsJawaban = Jawaban::where('id_sesi', $sesi->id)
            ->pluck('id_pertanyaan')
            ->toArray();

        $modulStatus = $moduls->map(function ($modul) use ($idsJawaban) {
            $totalPertanyaan = $modul->pertanyaan->count();
            $terisi = $modul->pertanyaan
                ->filter(fn($p) => in_array($p->id, $idsJawaban))
                ->count();

            if ($totalPertanyaan === 0) {
                $status = 'kosong';
            } elseif ($terisi === 0) {
                $status = 'belum';
            } elseif ($terisi < $totalPertanyaan) {
                $status = 'sebagian';
            } else {
                $status = 'selesai';
            }

            return [
                'modul'   => $modul,
                'total'   => $totalPertanyaan,
                'terisi'  => $terisi,
                'status'  => $status,
            ];
        });

        return view('survei.public.isi', [
            'sesi'        => $sesi,
            'modulStatus' => $modulStatus,
            'token'       => $token,
        ]);
    }

    /** Form isi satu modul tertentu. */
    public function showModul(string $token, int $modulId): View
    {
        $sesi = SesiSurvei::where('token_publik', $token)
            ->with(['wilayah.parent.parent', 'petugas'])
            ->firstOrFail();

        $modul = ModulSurvei::where('is_active', true)->findOrFail($modulId);

        $pertanyaans = Pertanyaan::where('id_modul', $modulId)
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get();

        // Ambil jawaban yang sudah diisi untuk pre-fill
        $jawabanLama = Jawaban::where('id_sesi', $sesi->id)
            ->whereIn('id_pertanyaan', $pertanyaans->pluck('id'))
            ->get()
            ->keyBy('id_pertanyaan');

        return view('survei.public.modul', [
            'sesi'        => $sesi,
            'modul'       => $modul,
            'pertanyaans' => $pertanyaans,
            'jawabanLama' => $jawabanLama,
            'token'       => $token,
        ]);
    }

    /** Simpan jawaban satu modul, lalu kembali ke landing. */
    public function storeModul(Request $request, string $token, int $modulId): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_publik', $token)->firstOrFail();
        $modul = ModulSurvei::where('is_active', true)->findOrFail($modulId);

        $pertanyaanIds = Pertanyaan::where('id_modul', $modulId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $data = $request->validate([
            'jawaban'   => 'required|array',
            'jawaban.*' => 'nullable',
        ]);

        foreach ($data['jawaban'] ?? [] as $id_pertanyaan => $nilai) {
            // Hanya proses pertanyaan milik modul ini
            if (!in_array((int) $id_pertanyaan, $pertanyaanIds)) continue;

            if ($nilai === null || $nilai === '') continue;

            $pertanyaan = Pertanyaan::find($id_pertanyaan);
            if (!$pertanyaan) continue;

            // Upsert: kalau sudah ada jawaban, update; kalau belum, insert
            $jawaban = Jawaban::firstOrNew([
                'id_sesi'        => $sesi->id,
                'id_pertanyaan'  => $id_pertanyaan,
            ]);
            $jawaban->id_modul = $modulId;

            if (in_array($pertanyaan->tipe_jawaban, ['angka'])) {
                $clean = str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9.,]/', '', (string) $nilai));
                $jawaban->nilai_angka = (float) $clean;
                $jawaban->nilai_teks  = null;
            } else {
                $jawaban->nilai_teks  = (string) $nilai;
                $jawaban->nilai_angka = null;
            }

            $jawaban->sumber = 'manual';
            $jawaban->save();
        }

        return redirect()
            ->route('survei.public.show', $token)
            ->with('success', 'Data modul "' . $modul->nama . '" berhasil disimpan.');
    }

    /** (Legacy) simpan semua jawaban sekaligus — masih dipertahankan untuk kompatibilitas. */
    public function store(Request $request, string $token): RedirectResponse
    {
        $sesi = SesiSurvei::where('token_publik', $token)->firstOrFail();

        $data = $request->validate([
            'jawaban'   => 'required|array',
            'jawaban.*' => 'nullable',
        ]);

        foreach ($data['jawaban'] ?? [] as $id_pertanyaan => $nilai) {
            if ($nilai === null || $nilai === '') continue;

            $pertanyaan = Pertanyaan::find($id_pertanyaan);
            if (!$pertanyaan) continue;

            $jawaban = Jawaban::firstOrNew([
                'id_sesi'       => $sesi->id,
                'id_pertanyaan' => $id_pertanyaan,
            ]);
            $jawaban->id_modul = $pertanyaan->id_modul;

            if ($pertanyaan->tipe_jawaban === 'angka') {
                $clean = str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9.,]/', '', (string) $nilai));
                $jawaban->nilai_angka = (float) $clean;
            } else {
                $jawaban->nilai_teks = (string) $nilai;
            }

            $jawaban->sumber = 'manual';
            $jawaban->save();
        }

        $sesi->update(['status' => 'terkirim']);

        return redirect()->route('survei.public.show', $token)
            ->with('success', 'Terima kasih, data survei berhasil dikirim.');
    }
}
