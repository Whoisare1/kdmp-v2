<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} &mdash; Ngobar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-[#f6f5ec] font-sans text-ink-900 antialiased" x-data="{ sidebarOpen: true }">
    <div class="flex h-full overflow-hidden">
        
        {{-- Sidebar Component --}}
        <div x-show="sidebarOpen" x-transition.opacity class="shrink-0 transition-all duration-300 print:hidden">
            <x-sidebar />
        </div>

        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            {{-- Top Navbar --}}
            <header class="sticky top-0 z-40 flex items-center justify-between border-b border-paper-200 bg-white px-6 py-3 shadow-sm print:hidden">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-paper-500 hover:text-ink-900 focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div class="hidden sm:block">
                        <h1 class="font-display text-lg font-semibold text-ink-900 leading-tight">{{ $title ?? 'Ngobar' }}</h1>
                        @if(isset($eyebrow))
                        <p class="font-mono text-[10px] uppercase tracking-widest text-paper-500 mt-0.5">{{ $eyebrow }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3 sm:gap-4">
                    @auth
                        @php
                            $navUser = auth()->user();
                            $navKatalogPeran = \App\Services\DemoPeranService::katalogPeran();
                            $navPeranAktif = \App\Services\DemoPeranService::kodePeranAktif($navUser);
                            $navEntitasId = $navUser->id_entitas ?? session('entitas_aktif_pilihan');
                        @endphp

                        {{-- Quick Switcher Peran Demo (Login Otomatis & Sinkronisasi Sidebar) --}}
                        <form action="{{ route('demo.switch-peran') }}" method="POST" class="hidden lg:flex items-center gap-1.5 bg-paper-50 border border-paper-200 rounded-lg px-2.5 py-1 shadow-sm">
                            @csrf
                            <input type="hidden" name="mode" value="login">
                            @if($navEntitasId)
                                <input type="hidden" name="id_entitas" value="{{ $navEntitasId }}">
                            @endif
                            <span class="text-[10px] font-mono uppercase tracking-wider text-paper-500">Demo Peran:</span>
                            <select
                                name="peran"
                                onchange="this.form.submit()"
                                class="text-xs font-semibold text-ink-900 bg-transparent border-0 py-0.5 pl-1 pr-6 focus:ring-0 cursor-pointer"
                                title="Pindah peran demo sekaligus login otomatis"
                            >
                                @foreach($navKatalogPeran as $kode => $info)
                                    <option value="{{ $kode }}" @selected($navPeranAktif === $kode)>
                                        {{ $info['ikon'] }} {{ $info['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        @if($navUser->id_entitas === null)
                            <form action="{{ route('admin.set_entitas') }}" method="POST" class="hidden md:flex items-center gap-2">
                                @csrf
                                <select name="id_entitas" onchange="this.form.submit()" class="text-xs border-paper-300 bg-white text-ink-950 font-medium rounded-lg py-1.5 pl-3 pr-8 focus:ring-padi-500 focus:border-padi-500 shadow-sm" style="color: #0f172a !important; background-color: #ffffff !important;">
                                    <option value="" style="color: #0f172a !important; background-color: #ffffff !important;">Semua Entitas (Global)</option>
                                    @foreach(\App\Models\Tenant\Entitas::where('status', 'APPROVED')->get() as $ent)
                                        <option value="{{ $ent->id_entitas }}" {{ session('entitas_aktif_pilihan') == $ent->id_entitas ? 'selected' : '' }} style="color: #0f172a !important; background-color: #ffffff !important;">
                                            {{ $ent->nama_entitas }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        <div class="text-right leading-tight hidden sm:block">
                            <p class="text-sm font-semibold text-ink-900">{{ $navUser->nama }}</p>
                            <p class="text-[11px] text-paper-500 uppercase font-medium mt-0.5">
                                @if($navUser->id_entitas === null)
                                    Super Admin {{ session('entitas_aktif_pilihan') ? '(Mode Entitas)' : '(Global)' }}
                                @else
                                    {{ $navUser->entitas?->nama_entitas }}
                                @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-lg border border-paper-300 bg-white px-3 py-1.5 text-xs font-medium text-ink-700 hover:border-merah-500 hover:text-merah-600 hover:bg-merah-50 transition-colors shadow-sm">
                                Keluar
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg bg-padi-500 hover:bg-padi-600 px-3.5 py-1.5 text-xs font-semibold text-ink-950 transition-colors shadow-sm">
                            Masuk Sistem Desa
                        </a>
                    @endauth
                </div>
            </header>

            {{-- Main Content Container --}}
            <main class="flex-1 overflow-y-auto overflow-x-hidden min-w-0 px-4 py-8 sm:px-6 lg:px-8">
                @if (session('info'))
                    <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 shadow-sm">
                        {{ session('info') }}
                    </div>
                @endif
                @if (session('status'))
                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">
                        {{ session('status') }}
                    </div>
                @endif
                @if (session('warning'))
                    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                @if (session('success'))
                    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Script Global: Pengaman Klik Ganda (Double-Submit Protection) di Sisi Klien --}}
    <script>
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (form && form.method && form.method.toUpperCase() !== 'GET') {
                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return false;
                }
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    form.dataset.submitting = 'true';
                    setTimeout(() => {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
                    }, 50);
                }
            }
        });

        window.addEventListener('pageshow', function (event) {
            document.querySelectorAll('form[data-submitting]').forEach(f => {
                delete f.dataset.submitting;
                const btn = f.querySelector('button[type="submit"], input[type="submit"]');
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            });
        });
    </script>
</body>
</html>
