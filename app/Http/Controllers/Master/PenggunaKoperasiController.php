<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Tenant\Entitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenggunaKoperasiController extends Controller
{
    use ResolvesEntitasAktif;

    protected string $title = 'Kelola Staf Koperasi';
    protected string $routeBase = 'master.pengguna';

    public function index(Request $request): View
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        $query = Pengguna::with(['peran', 'entitas']);
        $entitasList = $isSuperAdmin ? Entitas::orderBy('nama_entitas')->get() : collect();

        $selectedEntitasId = $request->query('id_entitas');

        if ($isSuperAdmin) {
            if ($selectedEntitasId === 'all') {
                // Tampilkan seluruh pengguna lintas entitas
            } elseif ($selectedEntitasId !== null && $selectedEntitasId !== '') {
                $query->where('id_entitas', (int) $selectedEntitasId);
            } else {
                // Default Super Admin: tampilkan entitas aktif atau seluruh entitas
                $defaultId = $this->entitasAktifId();
                if ($defaultId) {
                    $selectedEntitasId = (string) $defaultId;
                    $query->where('id_entitas', $defaultId);
                } else {
                    $selectedEntitasId = 'all';
                }
            }
        } else {
            $idEntitas = $this->requireEntitasAktifId();
            $query->where('id_entitas', $idEntitas);
            $selectedEntitasId = (string) $idEntitas;
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('nama')->paginate(15)->withQueryString();

        return view('master.pengguna.index', [
            'title'             => $isSuperAdmin ? 'Manajemen Pengguna & Staf Koperasi' : $this->title,
            'routeBase'         => $this->routeBase,
            'items'             => $items,
            'isSuperAdmin'      => $isSuperAdmin,
            'entitasList'       => $entitasList,
            'selectedEntitasId' => $selectedEntitasId,
        ]);
    }

    public function create(Request $request): View
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        $perans = Peran::where('kode', '!=', 'super_admin')->get();
        $entitasList = $isSuperAdmin ? Entitas::where('is_active', true)->orderBy('nama_entitas')->get() : collect();
        $targetEntitasId = (int) ($request->query('id_entitas') ?: $this->entitasAktifId());

        return view('master.pengguna.create', [
            'title'           => $isSuperAdmin ? 'Tambah Akun Pengguna / Staf Baru' : 'Tambah Staf Baru',
            'routeBase'       => $this->routeBase,
            'perans'          => $perans,
            'isSuperAdmin'    => $isSuperAdmin,
            'entitasList'     => $entitasList,
            'targetEntitasId' => $targetEntitasId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        $rules = [
            'nama'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:150', 'unique:pengguna,email'],
            'password'    => ['required', 'string', 'min:8'],
            'peran_ids'   => ['required', 'array', 'min:1'],
            'peran_ids.*' => ['exists:peran,id_peran'],
        ];

        if ($isSuperAdmin) {
            $rules['id_entitas'] = ['required', 'exists:entitas,id_entitas'];
        }

        $validated = $request->validate($rules, [
            'peran_ids.required' => 'Pilih minimal satu peran untuk akun ini.',
            'id_entitas.required' => 'Pilih entitas/koperasi tempat akun ini bertugas.',
        ]);

        $idEntitas = $isSuperAdmin ? (int) $validated['id_entitas'] : $this->requireEntitasAktifId();

        $staf = Pengguna::create([
            'id_entitas' => $idEntitas,
            'nama'       => trim($validated['nama']),
            'email'      => strtolower(trim($validated['email'])),
            'password'   => Hash::make($validated['password']),
            'is_active'  => true,
        ]);

        $staf->peran()->sync($validated['peran_ids']);

        $params = $isSuperAdmin ? ['id_entitas' => $idEntitas] : [];
        return redirect()->route("{$this->routeBase}.index", $params)
            ->with('status', "Akun {$staf->nama} ({$staf->email}) berhasil didaftarkan.");
    }

    public function edit(int $id): View
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        $query = Pengguna::with(['peran', 'entitas']);
        if (! $isSuperAdmin) {
            $query->where('id_entitas', $this->requireEntitasAktifId());
        }

        $user = $query->findOrFail($id);
        $perans = Peran::where('kode', '!=', 'super_admin')->get();
        $entitasList = $isSuperAdmin ? Entitas::where('is_active', true)->orderBy('nama_entitas')->get() : collect();

        return view('master.pengguna.edit', [
            'title'        => 'Edit Data Pengguna / Staf',
            'routeBase'    => $this->routeBase,
            'user'         => $user,
            'perans'       => $perans,
            'isSuperAdmin' => $isSuperAdmin,
            'entitasList'  => $entitasList,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        $query = Pengguna::query();
        if (! $isSuperAdmin) {
            $query->where('id_entitas', $this->requireEntitasAktifId());
        }

        $user = $query->findOrFail($id);

        $rules = [
            'nama'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:150', Rule::unique('pengguna', 'email')->ignore($user->id)],
            'password'    => ['nullable', 'string', 'min:8'],
            'is_active'   => ['required', 'boolean'],
            'peran_ids'   => ['required', 'array', 'min:1'],
            'peran_ids.*' => ['exists:peran,id_peran'],
        ];

        if ($isSuperAdmin) {
            $rules['id_entitas'] = ['nullable', 'exists:entitas,id_entitas'];
        }

        $validated = $request->validate($rules, [
            'peran_ids.required' => 'Pilih minimal satu peran.',
        ]);

        $payload = [
            'nama'      => trim($validated['nama']),
            'email'     => strtolower(trim($validated['email'])),
            'is_active' => (bool) $validated['is_active'],
        ];

        if ($isSuperAdmin && array_key_exists('id_entitas', $validated)) {
            $payload['id_entitas'] = $validated['id_entitas'] ? (int) $validated['id_entitas'] : null;
        }

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->peran()->sync($validated['peran_ids']);

        $params = $isSuperAdmin && $user->id_entitas ? ['id_entitas' => $user->id_entitas] : [];
        return redirect()->route("{$this->routeBase}.index", $params)
            ->with('status', "Data pengguna {$user->nama} berhasil diperbarui.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (auth()->id() === $id) {
            return back()->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri saat sedang masuk.']);
        }

        $query = Pengguna::query();
        if (! $isSuperAdmin) {
            $query->where('id_entitas', $this->requireEntitasAktifId());
        }

        $user = $query->findOrFail($id);
        $nama = $user->nama;

        $user->peran()->detach();
        $user->delete();

        return redirect()->route("{$this->routeBase}.index")
            ->with('status', "Akun pengguna {$nama} berhasil dihapus.");
    }
}
