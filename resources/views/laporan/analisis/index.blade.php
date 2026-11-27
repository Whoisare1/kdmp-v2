<x-layouts.app :title="$title" eyebrow="Analisis Strategis Koperasi">

    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-padi-100 text-padi-800">
                    {{ $desaAktif?->nama ?? 'Desa' }}
                </span>
                <span class="text-xs text-paper-500 font-mono">
                    Kec. {{ $desaAktif?->parent?->nama ?? 'Juwana' }}, Kab. {{ $desaAktif?->parent?->parent?->nama ?? 'Pati' }}
                </span>
            </div>
            <h2 class="text-xl font-display font-semibold text-ink-900">Analisis Potensi & Peluang Barter Koperasi</h2>
            <p class="text-xs text-paper-500">Analisis keunggulan komoditas desa guna membuka peluang barter komoditas antar-koperasi.</p>
        </div>

        <button onclick="window.print()" class="px-3 py-1.5 rounded-lg border border-paper-300 bg-white text-xs font-medium hover:bg-paper-50">
            Cetak Analisis
        </button>
    </div>

    {{-- Penjelasan Alur Barter Komoditas --}}
    <div class="mb-6 p-4 rounded-xl bg-padi-50 border border-padi-200 text-xs text-ink-900">
        <div>
            <strong class="font-semibold text-sm text-padi-900 block mb-0.5">Konsep Barter Komoditas Antar-Koperasi Desa (KDMP)</strong>
            Desa Anda (<strong class="text-ink-950">{{ $desaAktif?->nama ?? 'Desa' }}</strong>) yang memiliki surplus komoditas tertentu dapat bertukar dengan desa mitra yang menghasilkan komoditas berbeda untuk memenuhi kebutuhan konsumsi dan cadangan pangan tanpa bergantung pada tengkulak luar.
        </div>
    </div>

    {{-- Seksi 1: Komoditas Surplus Desa Ini yang Siap Dibarter --}}
    <div class="mb-6 bg-white p-6 rounded-xl border border-padi-300 shadow-sm">
        <div class="flex items-center justify-between pb-3 border-b border-paper-200 mb-4">
            <div>
                <span class="text-[10px] font-mono uppercase tracking-wider text-padi-700 font-bold">Kapasitas Desa Anda</span>
                <h3 class="font-display font-bold text-ink-900 text-lg mt-0.5">
                    Komoditas Unggulan {{ $desaAktif?->nama ?? 'Desa Anda' }} untuk Barter
                </h3>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                Siap Ditawarkan
            </span>
        </div>

        @if(empty($komoditasDesaAktif))
            <p class="text-xs text-paper-400 py-4 text-center">Belum ada komoditas tercatat untuk desa ini. Silakan input melalui menu Pendataan Potensi Komoditas.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($komoditasDesaAktif as $k)
                    <div class="p-3.5 bg-paper-50 rounded-xl border border-paper-200">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-ink-900">{{ $k['komoditas'] }}</span>
                            <span class="text-[10px] text-paper-500 font-mono">{{ $k['petani'] }} Petani</span>
                        </div>
                        <p class="text-lg font-mono font-bold text-emerald-800">
                            {{ number_format($k['volume']) }} <span class="text-xs font-normal text-paper-600">{{ $k['satuan'] }}</span>
                        </p>
                        <p class="text-[11px] text-paper-500 mt-1">Kapasitas produksi potensial desa</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Seksi 2: Matriks Peluang Kerjasama dengan Desa Lain --}}
    <h3 class="font-display text-base font-semibold text-ink-900 mb-3">
        Peluang Kemitraan Barter Antar-Desa
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        @forelse($komoditasUnggulan as $namaDesa => $komoditasArr)
            <div class="bg-white p-5 rounded-xl border border-paper-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-paper-100 mb-3">
                        <div>
                            <h3 class="font-display font-semibold text-ink-900 text-base">{{ $namaDesa }}</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-sawah-100 text-sawah-900">
                            {{ count($komoditasArr) }} Komoditas
                        </span>
                    </div>

                    <p class="text-xs font-mono uppercase text-paper-400 font-semibold mb-2">Potensi Produksi:</p>
                    <ul class="space-y-2 mb-4">
                        @foreach($komoditasArr as $k)
                            <li class="flex items-center justify-between p-2 rounded-lg bg-paper-50 text-xs">
                                <span class="font-medium text-ink-900">{{ $k['komoditas'] }}</span>
                                <span class="font-mono font-bold text-sawah-900">
                                    {{ number_format($k['volume']) }} {{ $k['satuan'] }}
                                    <span class="font-normal text-paper-500 text-[10px]">({{ $k['petani'] }} petani)</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="pt-3 border-t border-paper-100 flex items-center justify-between text-xs">
                    <span class="text-paper-500">Status Kemitraan</span>
                    <span class="text-emerald-700 font-semibold">Tersedia untuk Barter &check;</span>
                </div>
            </div>
        @empty
            <div class="col-span-2 bg-white p-8 text-center rounded-xl border border-paper-200 text-sm text-paper-400">
                Belum ada data komoditas desa yang dapat dianalisis.
            </div>
        @endforelse
    </div>

</x-layouts.app>
