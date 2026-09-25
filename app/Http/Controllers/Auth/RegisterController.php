<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jenis_organisasi' => ['required', 'string', 'max:50'],
            'nama_organisasi'  => ['required', 'string', 'max:150'],
            'nama_desa'        => ['required', 'string', 'max:150'],
            'nama'             => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email', 'unique:pengguna,email'],
            'password'         => ['required', 'min:8', 'confirmed'],
            'setuju'           => ['accepted'],
        ], [
            'jenis_organisasi.required' => 'Pilih jenis organisasi terlebih dahulu.',
            'nama_organisasi.required'  => 'Nama organisasi wajib diisi.',
            'nama_desa.required'        => 'Nama desa/wilayah wajib diisi.',
            'nama.required'             => 'Nama lengkap administrator wajib diisi.',
            'email.required'            => 'Email wajib diisi.',
            'email.unique'              => 'Email ini sudah terdaftar. Gunakan email lain atau masuk langsung.',
            'password.min'              => 'Kata sandi minimal 8 karakter.',
            'password.confirmed'        => 'Konfirmasi kata sandi tidak cocok.',
            'setuju.accepted'           => 'Anda harus menyetujui pernyataan di atas untuk mendaftar.',
        ]);

        DB::transaction(function () use ($data) {
            // 1. Buat wilayah desa (sederhana)
            $wilayah = Wilayah::create([
                'tingkat' => 'desa',
                'nama'    => $data['nama_desa'],
            ]);

            // 2. Buat tenant (organisasi desa)
            $namaOrg = $data['nama_organisasi'];
            $kode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $namaOrg), 0, 6))
                  . '-' . strtoupper(substr(md5(uniqid()), 0, 4));

            $entitas = Entitas::create([
                'kode_entitas'    => $kode,
                'nama_entitas'    => $namaOrg,
                'jenis_entitas'   => $data['jenis_organisasi'],
                'id_wilayah'      => $wilayah->id,
                'tahun_buku_awal' => now()->year,
                'is_active'       => false,
                'status'          => 'PENDING'
            ]);

            // 3. Buat pengguna admin
            $pengguna = Pengguna::create([
                'id_entitas'  => $entitas->id_entitas,
                'nama'        => $data['nama'],
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'is_active'   => false,
            ]);
        });

        return redirect()->route('login')
            ->with('success', 'Pendaftaran berhasil! Akun Anda sedang menunggu persetujuan (verifikasi) dari tim kami. Kami akan menghubungi Anda segera.');
    }
}


