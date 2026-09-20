<?php

namespace Survei\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Survei\MasyarakatDesa;
use Survei\Models\SesiSurvei;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicMasyarakatReviewController extends Controller
{
    /**
     * Show the review page
     */
    public function show(string $token, int $id_masyarakat): View
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)
            ->firstOrFail();

        // Eager load everything needed for the review page
        $masyarakat = MasyarakatDesa::with(['anggota', 'konsumsi'])
            ->where('id', $id_masyarakat)
            ->where('id_sesi', $sesi->id)
            ->firstOrFail();

        return view('survei.public.masyarakat_review', [
            'token' => $token,
            'sesi' => $sesi,
            'masyarakat' => $masyarakat,
        ]);
    }

    /**
     * Finalize the submission
     */
    public function submit(Request $request, string $token, int $id_masyarakat)
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)
            ->firstOrFail();

        $masyarakat = MasyarakatDesa::where('id', $id_masyarakat)
            ->where('id_sesi', $sesi->id)
            ->firstOrFail();

        // redirect to thank you page
        return redirect()->route('survei.public.masyarakat.terimakasih', ['token' => $token]);
    }

    /**
     * Show thank you page
     */
    public function terimakasih(string $token)
    {
        $sesi = SesiSurvei::where('token_masyarakat', $token)->firstOrFail();
        
        return view('survei.public.terimakasih', [
            'token' => $token,
            'sesi' => $sesi
        ]);
    }
}
