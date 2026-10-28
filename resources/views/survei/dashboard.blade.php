<x-layouts.app title="Dashboard Survei" eyebrow="Manajemen Survei">
    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-ink-500">Ringkasan pelaksanaan survei lapangan.</p>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Daftar Sesi Survei -->
        <a href="{{ route('survei.sesi.index') }}" class="group block rounded-sm border border-paper-300 bg-paper-50 p-6 hover:border-merah-400 hover:bg-paper-100 shadow-sm transition-colors">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-merah-100 text-merah-600 transition-colors group-hover:bg-merah-500 group-hover:text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </div>
            <h2 class="font-display text-lg font-medium text-ink-900">Daftar Sesi Survei</h2>
            <p class="mt-2 text-sm text-ink-500">Kelola jadwal sesi dan generate link token survei desa.</p>
        </a>

        <!-- Card 2: Master RT -->
        <a href="{{ route('survei.rt.index') }}" class="group block rounded-sm border border-paper-300 bg-paper-50 p-6 hover:border-blue-400 hover:bg-paper-100 shadow-sm transition-colors">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 transition-colors group-hover:bg-blue-500 group-hover:text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h2 class="font-display text-lg font-medium text-ink-900">Data RT</h2>
            <p class="mt-2 text-sm text-ink-500">Kelola daftar RT untuk kebutuhan data baseline.</p>
        </a>

        <!-- Card 3: Master Produsen -->
        <a href="{{ route('survei.produsen.index') }}" class="group block rounded-sm border border-paper-300 bg-paper-50 p-6 hover:border-green-400 hover:bg-paper-100 shadow-sm transition-colors">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-green-600 transition-colors group-hover:bg-green-500 group-hover:text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h2 class="font-display text-lg font-medium text-ink-900">Produsen Desa</h2>
            <p class="mt-2 text-sm text-ink-500">Kelola nama anggota Kelompok Tani dan Pelaku Ekraf.</p>
        </a>

        <!-- Card 4: Validasi & Laporan -->
        <a href="{{ route('survei.validasi.index') }}" class="group block rounded-sm border border-paper-300 bg-paper-50 p-6 hover:border-purple-400 hover:bg-paper-100 shadow-sm transition-colors">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-purple-600 transition-colors group-hover:bg-purple-500 group-hover:text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <h2 class="font-display text-lg font-medium text-ink-900">Survei Masyarakat</h2>
            <p class="mt-2 text-sm text-ink-500">Kelola dan lihat hasil survei masyarakat.</p>
        </a>
    </div>
</x-layouts.app>
