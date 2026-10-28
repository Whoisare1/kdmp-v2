<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Entitas;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $pendingEntitas = Entitas::where('status', 'PENDING')->get();
        $approvedEntitas = Entitas::where('status', 'APPROVED')->get();
        
        return view('admin.dashboard', compact('pendingEntitas', 'approvedEntitas'));
    }

    public function approve(Request $request, $id)
    {
        $entitas = Entitas::findOrFail($id);
        $action = $request->input('action'); // 'approve' or 'reject'

        if ($action === 'approve') {
            $entitas->update([
                'status' => 'APPROVED',
                'is_active' => true,
            ]);

            // Aktifkan juga user admin pertama dari entitas ini
            $entitas->pengguna()->update(['is_active' => true]);

            return back()->with('success', 'Entitas ' . $entitas->nama_entitas . ' berhasil disetujui.');
        } elseif ($action === 'reject') {
            $entitas->update([
                'status' => 'REJECTED',
                'is_active' => false,
            ]);
            return back()->with('success', 'Pendaftaran ' . $entitas->nama_entitas . ' ditolak.');
        }

        return back();
    }
}
