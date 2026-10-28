<x-layouts.guest title="Masuk — Ngobar">
<style>
    .auth-blob { position: absolute; border-radius: 50%; filter: blur(70px); pointer-events: none; }
    .map-pattern { background-image: linear-gradient(rgba(185,138,44,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(185,138,44,0.05) 1px, transparent 1px); background-size: 40px 40px; }
    .input-field { width:100%; border-radius:0.625rem; border:1px solid #333c2d; background:#1c211a; padding:0.625rem 0.875rem; font-size:0.875rem; color:#fbfaf7; outline:none; transition:border-color 0.2s,box-shadow 0.2s; }
    .input-field:focus { border-color:#b98a2c; box-shadow:0 0 0 3px rgba(185,138,44,0.12); }
    .input-field::placeholder { color:rgba(221,217,203,0.35); }
    .btn-primary { width:100%; padding:0.75rem 1rem; border-radius:0.625rem; background:#b98a2c; color:#14170f; font-weight:600; font-size:0.875rem; transition:all 0.2s; box-shadow:0 0 24px rgba(185,138,44,0.25); cursor:pointer; border:none; }
    .btn-primary:hover { background:#93701f; box-shadow:0 0 40px rgba(185,138,44,0.4); transform:translateY(-1px); }
    .card { background:rgba(28,33,26,0.8); backdrop-filter:blur(20px); border:1px solid rgba(51,60,45,0.8); border-radius:1.25rem; padding:2rem; }
    .nav-back { display:inline-flex; align-items:center; gap:6px; color:rgba(221,217,203,0.5); font-size:0.8125rem; text-decoration:none; transition:color 0.2s; }
    .nav-back:hover { color:#e3ead9; }
    .divider { display:flex; align-items:center; gap:12px; color:rgba(221,217,203,0.3); font-size:0.75rem; }
    .divider::before,.divider::after { content:""; flex:1; height:1px; background:rgba(51,60,45,0.6); }
    .badge-pill { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:9999px; font-size:10px; font-family:"IBM Plex Mono",monospace; letter-spacing:0.08em; text-transform:uppercase; }
</style>

<div class="relative min-h-screen map-pattern flex flex-col overflow-hidden" style="background:#14170f">
    <div class="auth-blob" style="width:500px;height:500px;background:#b98a2c;opacity:0.10;top:-100px;right:-100px"></div>
    <div class="auth-blob" style="width:400px;height:400px;background:#4f7351;opacity:0.10;bottom:-80px;left:-80px"></div>

    {{-- Top bar --}}
    <div class="relative z-10 flex items-center justify-between px-6 py-4">
        <a href="{{ route('landing') }}" class="nav-back">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali ke Beranda
        </a>
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-padi-500 flex items-center justify-center text-ink-950 font-display font-bold text-xs">N</div>
            <span class="font-display font-semibold text-paper-50 text-sm">Ngobar</span>
        </div>
    </div>

    {{-- Main content --}}
    <div class="relative z-10 flex flex-1 items-center justify-center px-4 py-8">
        <div class="w-full max-w-sm">

            {{-- Header --}}
            <div class="text-center mb-7">
                <div class="w-14 h-14 rounded-2xl bg-padi-500/15 border border-padi-500/30 flex items-center justify-center text-2xl mx-auto mb-4">🏘️</div>
                <h1 class="font-display text-2xl font-semibold text-paper-50">Selamat Datang</h1>
                <p class="text-paper-400/70 text-sm mt-1">Masuk ke platform Ngobar desa Anda</p>
            </div>

            <div class="card">
                {{-- Error --}}
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-merah-500/40 bg-merah-500/10 px-4 py-3 flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-merah-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <p class="text-sm text-merah-100">{{ $errors->first() }}</p>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-5 rounded-xl border border-sawah-500/40 bg-sawah-500/10 px-4 py-3 flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sawah-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <p class="text-sm text-sawah-100">{{ session('success') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-paper-300/70 mb-1.5">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                               class="input-field" placeholder="email@desamu.id">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-paper-300/70 mb-1.5">Kata Sandi</label>
                        <input type="password" name="password" id="password" required
                               class="input-field" placeholder="••••••••">
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-xs text-paper-400/70 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-ink-600 bg-ink-900 accent-padi-500">
                            Ingat saya
                        </label>
                    </div>
                    <button type="submit" id="btn-login" class="btn-primary">
                        Masuk ke Sistem
                    </button>
                </form>

                <div class="divider my-5">atau</div>

                <a href="{{ route('register') }}" class="block w-full text-center px-4 py-2.5 rounded-xl border border-ink-600 text-paper-300 hover:border-padi-500/50 hover:text-padi-100 text-sm font-medium transition-all duration-200">
                    Belum punya akun? <span class="text-padi-400 font-semibold">Daftar Desa</span>
                </a>
            </div>

            {{-- Info hint --}}
            <div class="mt-4 rounded-xl border border-ink-700/50 bg-ink-900/40 px-4 py-3">
                <p class="text-xs text-paper-500 text-center leading-relaxed">
                    Ngobar tersedia untuk <strong class="text-paper-400">BUMDes, Kelompok Tani, Koperasi Desa,</strong><br>dan organisasi ekonomi desa lainnya.
                </p>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="relative z-10 text-center pb-6">
        <p class="text-xs text-paper-600 font-mono">© 2025 Ngobar — Pemetaan Potensi & Barter Komoditi Desa</p>
    </div>
</div>
</x-layouts.guest>
