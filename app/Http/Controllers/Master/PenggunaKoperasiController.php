<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesEntitasAktif;
use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\Peran;
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
        $idEntitas = $this->requireEntitasAktifId();

        $query = Pengguna::with('peran')
            ->where('id_entitas', $idEntitas);

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('nama')->paginate(15)->withQueryString();

        return view('master.pengguna.index', [
            'title'     => $this->title,
            'routeBase' => $this->routeBase,
            'items'     => $items,
        ]);
    }

    public function create(): View
    {
        $perans = Peran::where('kode', '!=', 'super_admin')->get();

        return view('master.pengguna.create', [
            'title'     => 'Tambah Staf Baru',
            'routeBase' => $this->routeBase,
            'perans'    => $perans,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();

        $validated = $request->validate([
            'nama'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:150', 'unique:pengguna,email'],
            'password'    => ['required', 'string', 'min:8'],
            'peran_ids'   => ['required', 'array', 'min:1'],
            'peran_ids.*' => ['exists:peran,id_peran'],
        ], [
            'peran_ids.required' => 'Pilih minimal satu peran untuk staf ini.',
        ]);

        $staf = Pengguna::create([
            'id_entitas' => $idEntitas,
            'nama'       => $validated['nama'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'is_active'  => true,
        ]);

        $staf->peran()->sync($validated['peran_ids']);

        return redirect()->route("{$this->routeBase}.index")
            ->with('status', "Staf {$staf->nama} berhasil didaftarkan.");
    }

    public function edit(int $id): View
    {
        $idEntitas = $this->requireEntitasAktifId();

        $user = Pengguna::with('peran')
            ->where('id_entitas', $idEntitas)
            ->findOrFail($id);

        $perans = Peran::where('kode', '!=', 'super_admin')->get();

        return view('master.pengguna.edit', [
            'title'     => 'Edit Data Staf',
            'routeBase' => $this->routeBase,
            'user'      => $user,
            'perans'    => $perans,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();

        $user = Pengguna::where('id_entitas', $idEntitas)->findOrFail($id);

        $validated = $request->validate([
            'nama'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:150', Rule::unique('pengguna', 'email')->ignore($user->id)],
            'password'    => ['nullable', 'string', 'min:8'],
            'is_active'   => ['required', 'boolean'],
            'peran_ids'   => ['required', 'array', 'min:1'],
            'peran_ids.*' => ['exists:peran,id_peran'],
        ]);

        $payload = [
            'nama'      => $validated['nama'],
            'email'     => $validated['email'],
            'is_active' => (bool) $validated['is_active'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->peran()->sync($validated['peran_ids']);

        return redirect()->route("{$this->routeBase}.index")
            ->with('status', "Data staf {$user->nama} berhasil diperbarui.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $idEntitas = $this->requireEntitasAktifId();

        if (auth()->id() === $id) {
            return back()->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri saat sedang masuk.']);
        }

        $user = Pengguna::where('id_entitas', $idEntitas)->findOrFail($id);
        $nama = $user->nama;

        $user->peran()->detach();
        $user->delete();

        return redirect()->route("{$this->routeBase}.index")
            ->with('status', "Akun staf {$nama} berhasil dihapus.");
    }
}

