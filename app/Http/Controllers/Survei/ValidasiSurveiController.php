<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Survei\RtDesa;

class ValidasiSurveiController extends Controller
{
    public function index(Request $request)
    {
        $availablePeriods = \Survei\Models\SesiSurvei::select('bulan', 'tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();
            
        $bulan = $request->input('bulan', (int)date('n'));
        $tahun = $request->input('tahun', (int)date('Y'));

        // Filter RtDesa by selected period
        $rts = RtDesa::with(['wilayah', 'sesi'])
            ->whereHas('sesi', function ($q) use ($bulan, $tahun) {
                $q->where('bulan', (int)$bulan)
                  ->where('tahun', (int)$tahun);
            })
            ->get();

        foreach ($rts as $rt) {
            preg_match('/\d+/', $rt->nama_rt, $rtMatch);
            preg_match('/\d+/', $rt->rw, $rwMatch);
            
            $rt_digit = $rtMatch[0] ?? '';
            $rw_digit = $rwMatch[0] ?? '';
            
            // Get all MasyarakatDesa for this session with anggota
            $masyarakatDesaList = \App\Models\Survei\MasyarakatDesa::with('anggota')
                ->where('id_sesi', $rt->id_sesi)
                ->get()
                ->filter(function($m) use ($rt_digit, $rw_digit, $rt) {
                     $m_rt = preg_replace('/\D/', '', (string)$m->rt);
                     $m_rw = preg_replace('/\D/', '', (string)$m->rw);
                     
                     // Compare integers to avoid "01" vs "1" mismatch
                     $rt_match = (int)$m_rt === (int)$rt_digit;
                     
                     // If RT didn't provide RW, we don't strictly require it to match
                     $rw_match = true;
                     if (!empty($rt->rw) && $rw_digit !== '') {
                         $rw_match = (int)$m_rw === (int)$rw_digit;
                     }
                     
                     $dusun_match = true;
                     if (!empty($rt->dusun)) {
                         $dusun_match = (strtolower(trim($m->dusun)) == strtolower(trim($rt->dusun)));
                     }
                     
                     // Pastikan desanya juga sama (hilangkan kata "Desa" untuk safety match)
                     $rt_desa_name = trim(str_ireplace('desa ', '', $rt->wilayah->nama ?? ''));
                     $m_desa_name = trim(str_ireplace('desa ', '', $m->desa ?? ''));
                     
                     $desa_match = true;
                     if (!empty($rt_desa_name) && !empty($m_desa_name)) {
                         $desa_match = (strtolower($m_desa_name) === strtolower($rt_desa_name));
                     }

                     return $rt_match && $rw_match && $dusun_match && $desa_match;
                });
                
            $rt->kk_terdata = $masyarakatDesaList->count();
            
            $jiwa = 0;
            foreach ($masyarakatDesaList as $m) {
                $jiwa += $m->anggota->count();
            }
            $rt->jiwa_terdata = $jiwa;
            
            $rt->masyarakat_list = $masyarakatDesaList;
        }

        return view('survei.validasi.index', compact('rts', 'availablePeriods', 'bulan', 'tahun'));
    }

    public function showRtDetail($id_sesi, $nama_rt, $rw = null)
    {
        $rtDesa = \App\Models\Survei\RtDesa::with(['wilayah', 'sesi'])
            ->where('id_sesi', $id_sesi)
            ->where('nama_rt', $nama_rt)
            ->when($rw, function($q) use ($rw) {
                return $q->where('rw', $rw);
            })
            ->firstOrFail();

        preg_match('/\d+/', $rtDesa->nama_rt, $rtMatch);
        preg_match('/\d+/', $rtDesa->rw, $rwMatch);
        
        $rt_digit = $rtMatch[0] ?? '';
        $rw_digit = $rwMatch[0] ?? '';
        
        $masyarakatDesaList = \App\Models\Survei\MasyarakatDesa::with(['anggota', 'konsumsi'])
            ->where('id_sesi', $id_sesi)
            ->get()
            ->filter(function($m) use ($rt_digit, $rw_digit, $rtDesa) {
                 $m_rt = preg_replace('/\D/', '', (string)$m->rt);
                 $m_rw = preg_replace('/\D/', '', (string)$m->rw);
                 
                 $rt_match = (int)$m_rt === (int)$rt_digit;
                 
                 $rw_match = true;
                 if (!empty($rtDesa->rw) && $rw_digit !== '') {
                     $rw_match = (int)$m_rw === (int)$rw_digit;
                 }
                 
                 $dusun_match = true;
                 if (!empty($rtDesa->dusun)) {
                     $dusun_match = (strtolower(trim($m->dusun)) == strtolower(trim($rtDesa->dusun)));
                 }
                 
                 $rt_desa_name = trim(str_ireplace('desa ', '', $rtDesa->wilayah->nama ?? ''));
                 $m_desa_name = trim(str_ireplace('desa ', '', $m->desa ?? ''));
                 
                 $desa_match = true;
                 if (!empty($rt_desa_name) && !empty($m_desa_name)) {
                     $desa_match = (strtolower($m_desa_name) === strtolower($rt_desa_name));
                 }
                 
                 return $rt_match && $rw_match && $dusun_match && $desa_match;
            });

        return view('survei.validasi.show_rt', compact('rtDesa', 'masyarakatDesaList'));
    }
}
