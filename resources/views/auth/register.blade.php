<x-layouts.guest title="Daftar Desa — Ngobar">
<style>
    .auth-blob { position: absolute; border-radius: 50%; filter: blur(70px); pointer-events: none; }
    .map-pattern { background-image: linear-gradient(rgba(185,138,44,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(185,138,44,0.05) 1px, transparent 1px); background-size: 40px 40px; }
    .input-field { width:100%; border-radius:0.625rem; border:1px solid #333c2d; background:#1c211a; padding:0.625rem 0.875rem; font-size:0.875rem; color:#fbfaf7; outline:none; transition:border-color 0.2s,box-shadow 0.2s; }
    .input-field:focus { border-color:#b98a2c; box-shadow:0 0 0 3px rgba(185,138,44,0.12); }
    .input-field::placeholder { color:rgba(221,217,203,0.35); }
    .select-field { width:100%; border-radius:0.625rem; border:1px solid #333c2d; background:#1c211a; padding:0.625rem 0.875rem; font-size:0.875rem; color:#fbfaf7; outline:none; transition:border-color 0.2s; appearance:none; }
    .select-field:focus { border-color:#b98a2c; box-shadow:0 0 0 3px rgba(185,138,44,0.12); }
    .btn-primary { width:100%; padding:0.75rem 1rem; border-radius:0.625rem; background:#b98a2c; color:#14170f; font-weight:600; font-size:0.875rem; transition:all 0.2s; box-shadow:0 0 24px rgba(185,138,44,0.25); cursor:pointer; border:none; }
    .btn-primary:hover { background:#93701f; box-shadow:0 0 40px rgba(185,138,44,0.4); transform:translateY(-1px); }
    .card { background:rgba(28,33,26,0.8); backdrop-filter:blur(20px); border:1px solid rgba(51,60,45,0.8); border-radius:1.25rem; padding:2rem; }
    .nav-back { display:inline-flex; align-items:center; gap:6px; color:rgba(221,217,203,0.5); font-size:0.8125rem; text-decoration:none; transition:color 0.2s; }
    .nav-back:hover { color:#e3ead9; }
    .section-title { font-size:0.6875rem; font-family:"IBM Plex Mono",monospace; letter-spacing:0.1em; text-transform:uppercase; color:rgba(185,138,44,0.7); margin-bottom:0.75rem; display:flex; align-items:center; gap:8px; }
    .section-title::after { content:""; flex:1; height:1px; background:rgba(51,60,45,0.6); }
    .error-field { font-size:0.75rem; color:#c1483a; margin-top:4px; }
</style>

<div class="relative min-h-screen map-pattern flex flex-col overflow-hidden" style="background:#14170f">
    <div class="auth-blob" style="width:500px;height:500px;background:#4f7351;opacity:0.10;top:-80px;left:-100px"></div>
    <div class="auth-blob" style="width:400px;height:400px;background:#b98a2c;opacity:0.10;bottom:-100px;right:-80px"></div>

    {{-- Top bar --}}
    <div class="relative z-10 flex items-center justify-between px-6 py-4">
        <a href="{{ route('login') }}" class="nav-back">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Sudah punya akun? Masuk
        </a>
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-padi-500 flex items-center justify-center text-ink-950 font-display font-bold text-xs">N</div>
            <span class="font-display font-semibold text-paper-50 text-sm">Ngobar</span>
        </div>
    </div>

    {{-- Main --}}
    <div class="relative z-10 flex flex-1 items-center justify-center px-4 py-8">
        <div class="w-full max-w-lg">

            {{-- Header --}}
            <div class="text-center mb-7">
                <div class="w-14 h-14 rounded-2xl bg-sawah-500/15 border border-sawah-500/30 flex items-center justify-center text-2xl mx-auto mb-4">🌾</div>
                <h1 class="font-display text-2xl font-semibold text-paper-50">Daftarkan Entitas / Organisasi</h1>
                <p class="text-paper-400/70 text-sm mt-1">BUMDes, Koperasi, UMKM Ekraf, Supplier, atau Perorangan</p>
            </div>

            <div class="card">
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-merah-500/40 bg-merah-500/10 px-4 py-3 flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-merah-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div>
                            <p class="text-sm font-medium text-merah-100 mb-1">Periksa kembali isian Anda:</p>
                            <ul class="text-xs text-merah-200/80 space-y-0.5 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                    @csrf

                    {{-- Info Organisasi --}}
                    <div>
                        <p class="section-title">Info Entitas Bisnis</p>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-paper-300/70 mb-1.5">Jenis Entitas</label>
                                <div class="relative">
                                    <select name="jenis_organisasi" id="jenis_organisasi" class="select-field" required>
                                        <option value="" disabled {{ old('jenis_organisasi') ? '' : 'selected' }}>-- Pilih jenis --</option>
                                        <option value="BUMDes" {{ old('jenis_organisasi') === 'BUMDes' ? 'selected' : '' }}>BUMDes (Badan Usaha Milik Desa)</option>
                                        <option value="Koperasi Desa" {{ old('jenis_organisasi') === 'Koperasi Desa' ? 'selected' : '' }}>Koperasi Desa</option>
                                        <option value="Kelompok Tani" {{ old('jenis_organisasi') === 'Kelompok Tani' ? 'selected' : '' }}>Kelompok Tani</option>
                                        <option value="Gapoktan" {{ old('jenis_organisasi') === 'Gapoktan' ? 'selected' : '' }}>Gapoktan</option>
                                        <option value="Perorangan" {{ old('jenis_organisasi') === 'Perorangan' ? 'selected' : '' }}>Perorangan / Supplier</option>
                                        <option value="Lainnya" {{ old('jenis_organisasi') === 'Lainnya' ? 'selected' : '' }}>UMKM / Lainnya</option>
                                    </select>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-paper-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </div>
                                @error('jenis_organisasi') <p class="error-field">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-paper-300/70 mb-1.5">Nama Entitas / Usaha / Pribadi</label>
                                <input type="text" name="nama_organisasi" id="nama_organisasi" value="{{ old('nama_organisasi') }}" required
                                       class="input-field" placeholder="cth: BUMDes Maju atau Budi Santoso">
                                @error('nama_organisasi') <p class="error-field">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-paper-300/70 mb-1.5">Nama Desa / Wilayah</label>
                                <input type="text" name="nama_desa" id="nama_desa" value="{{ old('nama_desa') }}" required
                                       class="input-field" placeholder="cth: Desa Sukamaju, Kec. Makmur, Kab. Sejahtera">
                                @error('nama_desa') <p class="error-field">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Akun Admin --}}
                    <div>
                        <p class="section-title">Akun Administrator</p>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-paper-300/70 mb-1.5">Nama Lengkap</label>
                                <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required
                                       class="input-field" placeholder="Nama pengelola sistem">
                                @error('nama') <p class="error-field">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-paper-300/70 mb-1.5">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                       class="input-field" placeholder="admin@desamu.id">
                                @error('email') <p class="error-field">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-paper-300/70 mb-1.5">Kata Sandi</label>
                                    <input type="password" name="password" id="password" required
                                           class="input-field" placeholder="Min. 8 karakter">
                                    @error('password') <p class="error-field">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-paper-300/70 mb-1.5">Konfirmasi Sandi</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" required
                                           class="input-field" placeholder="Ulangi sandi">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Terms --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="setuju" required class="w-4 h-4 mt-0.5 rounded border-ink-600 bg-ink-900 accent-padi-500 shrink-0">
                        <span class="text-xs text-paper-400/70 leading-relaxed">
                            Saya menyatakan bahwa data yang diisikan adalah benar dan saya mewakili organisasi/desa yang didaftarkan.
                        </span>
                    </label>

                    <button type="submit" id="btn-register" class="btn-primary">
                        Ajukan Pendaftaran
                    </button>
                </form>
            </div>

            {{-- Info --}}
            <div class="mt-4 rounded-xl border border-padi-500/20 bg-padi-500/5 px-4 py-3">
                <div class="flex items-start gap-3">
                    <span class="text-base mt-0.5">🛡️</span>
                    <p class="text-xs text-paper-500 leading-relaxed">
                        Setelah mendaftar, akun Anda akan masuk ke tahap <strong class="text-paper-400">Verifikasi Admin</strong> demi menjaga keamanan dan kepercayaan jejaring barter Ngobar.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="relative z-10 text-center pb-6">
        <p class="text-xs text-paper-600 font-mono">© 2025 Ngobar — Pemetaan Potensi & Barter Komoditi Desa</p>
    </div>
</div>
</x-layouts.guest>
