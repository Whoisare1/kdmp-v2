<x-layouts.guest title="Katalog Komoditas & Potensi Pangan Desa — Ngobar">
    <div class="min-h-screen bg-[#14170f] text-paper-100 py-12 px-4 sm:px-6 lg:px-8 map-pattern">
        <div class="max-w-7xl mx-auto space-y-10">

            {{-- Top Navbar Sederhana --}}
            <div class="flex items-center justify-between border-b border-ink-800 pb-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('landing') }}" class="w-9 h-9 rounded-xl bg-padi-500 flex items-center justify-center text-ink-950 font-display font-bold text-base shadow-sm">
                        N
                    </a>
                    <div>
                        <a href="{{ route('landing') }}" class="font-display font-bold text-white text-lg hover:text-padi-300 transition-colors">
                            Ngobar
                        </a>
                        <p class="text-[11px] font-mono text-paper-400">Katalog Komoditas &amp; Potensi Desa</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('landing') }}" class="text-xs text-paper-300 hover:text-white px-3 py-1.5 rounded-lg border border-ink-700 bg-ink-900 transition-colors">
                        ← Kembali ke Beranda
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-ink-950 bg-padi-500 hover:bg-padi-600 px-4 py-2 rounded-lg transition-colors shadow-sm">
                            ← Kembali ke Dasbor Internal
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-ink-950 bg-padi-500 hover:bg-padi-600 px-4 py-2 rounded-lg transition-colors shadow-sm">
                            Masuk Sistem Desa
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Banner Pengantar --}}
            <div class="rounded-2xl border border-ink-700 bg-ink-900/90 p-6 sm:p-8 space-y-3">
                <div class="inline-flex items-center gap-2 rounded-full bg-padi-500/15 border border-padi-500/30 px-3 py-1 text-xs font-mono uppercase tracking-wider text-padi-200 font-semibold">
                    🌾 Peta Komoditi &amp; Ketahanan Pangan Antar-Desa
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-bold text-white">
                    Katalog Komoditas &amp; Potensi Barter Desa
                </h1>
                <p class="text-sm text-paper-300 max-w-3xl leading-relaxed">
                    Halaman transparansi publik untuk melihat ketersediaan komoditas lokal dan status surplus/defisit antar-desa. Rincian negosiasi harga dan dokumen transaksi dilindungi secara aman di dalam sistem operasional masing-masing koperasi desa.
                </p>
            </div>

            {{-- 1. Direktori Koperasi Desa Terdaftar --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-display text-xl font-bold text-white">1. Koperasi Desa Terhubung</h2>
                        <p class="text-xs text-paper-400">Entitas ekonomi desa yang siap berkolaborasi dalam jaringan pertukaran komoditas.</p>
                    </div>
                    <span class="rounded-lg bg-ink-800 border border-ink-700 px-3 py-1 text-xs font-mono text-padi-300">
                        Total: {{ $daftarEntitas->count() }} Desa
                    </span>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @forelse($daftarEntitas as $ent)
                        @php
                            $surplusDesa = $neracaLintasDesa
                                ->where('id_wilayah', $ent->id_wilayah)
                                ->where('status_surplus_defisit', 'surplus');
                            $defisitDesa = $neracaLintasDesa
                                ->where('id_wilayah', $ent->id_wilayah)
                                ->where('status_surplus_defisit', 'defisit');
                        @endphp
                        <div class="rounded-2xl border border-ink-700 bg-ink-900 p-5 flex flex-col justify-between gap-4 hover:border-padi-500/40 transition-all">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded bg-padi-500/15 border border-padi-500/30 px-2.5 py-0.5 font-mono text-xs font-semibold text-padi-200">
                                        {{ $ent->kode_entitas }}
                                    </span>
                                    <span class="rounded-full bg-sawah-500/15 border border-sawah-500/30 px-2.5 py-0.5 text-[11px] text-sawah-100">
                                        ● Aktif
                                    </span>
                                </div>
                                <h3 class="font-display text-base font-bold text-white mt-3">{{ $ent->nama_entitas }}</h3>
                                <p class="text-xs text-paper-400 mt-0.5">
                                    Wilayah: <strong class="text-paper-200">{{ $ent->wilayah?->nama ?? 'Wilayah Desa' }}</strong>
                                </p>
                            </div>

                            <div class="pt-3 border-t border-ink-800 space-y-2 text-xs">
                                <div>
                                    <span class="font-semibold text-sawah-100">🌾 Komoditas Surplus:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @forelse($surplusDesa->take(3) as $item)
                                            <span class="rounded bg-sawah-500/15 border border-sawah-500/30 px-2 py-0.5 text-[11px] text-sawah-100">
                                                {{ $item->komoditas?->nama }} (+{{ number_format((float) $item->selisih, 0, ',', '.') }} kg)
                                            </span>
                                        @empty
                                            <span class="text-paper-500 italic text-[11px]">-</span>
                                        @endforelse
                                    </div>
                                </div>
                                <div>
                                    <span class="font-semibold text-merah-100">⚠️ Komoditas Defisit:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @forelse($defisitDesa->take(3) as $item)
                                            <span class="rounded bg-merah-500/15 border border-merah-500/30 px-2 py-0.5 text-[11px] text-merah-100">
                                                {{ $item->komoditas?->nama }} ({{ number_format((float) $item->selisih, 0, ',', '.') }} kg)
                                            </span>
                                        @empty
                                            <span class="text-paper-500 italic text-[11px]">-</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-xl bg-ink-900 border border-ink-800 p-6 text-center text-xs text-paper-400">
                            Belum ada entitas koperasi terdaftar.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- 2. Tabel Status Neraca Komoditas Publik (Tanpa Kolom Harga) --}}
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="font-display text-xl font-bold text-white">
                            2. Status Ketersediaan &amp; Kebutuhan Pangan Desa (Periode {{ $bulanAktif }}/{{ $tahunAktif }})
                        </h2>
                        <p class="text-xs text-paper-400">
                            Peta kecukupan pangan per wilayah desa untuk menentukan prioritas barter antar-koperasi.
                        </p>
                    </div>

                    {{-- Filter Form --}}
                    <form method="GET" action="{{ route('publik.komoditas') }}" class="flex flex-wrap items-center gap-2">
                        <input
                            type="text"
                            name="q"
                            value="{{ $cari }}"
                            placeholder="Cari komoditas..."
                            class="rounded-lg border border-ink-700 bg-ink-900 px-3 py-1.5 text-xs text-white placeholder-paper-500 focus:border-padi-500 focus:outline-none"
                        >
                        <select name="wilayah" class="rounded-lg border border-ink-700 bg-ink-900 px-3 py-1.5 text-xs text-white">
                            <option value="">Semua Wilayah</option>
                            @foreach($daftarWilayah as $wil)
                                <option value="{{ $wil->id }}" @selected((string) $wilayahFilter === (string) $wil->id)>
                                    {{ $wil->nama }}
                                </option>
                            @endforeach
                        </select>
                        <select name="kategori" class="rounded-lg border border-ink-700 bg-ink-900 px-3 py-1.5 text-xs text-white">
                            <option value="">Semua Kategori</option>
                            @foreach($kategoriTersedia as $kat)
                                <option value="{{ $kat }}" @selected($kategoriFilter === $kat)>
                                    {{ ucfirst($kat) }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-lg bg-padi-500 px-3.5 py-1.5 text-xs font-bold text-ink-950 hover:bg-padi-600 transition-colors">
                            Filter
                        </button>
                    </form>
                </div>

                <div class="overflow-hidden rounded-2xl border border-ink-800 bg-ink-900/90 shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-ink-800 text-xs">
                            <thead class="bg-ink-950 text-paper-400 font-mono text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-3.5 text-left">Wilayah Desa</th>
                                    <th class="px-5 py-3.5 text-left">Komoditas</th>
                                    <th class="px-5 py-3.5 text-left">Kategori</th>
                                    <th class="px-5 py-3.5 text-right">Produksi Lokal</th>
                                    <th class="px-5 py-3.5 text-right">Kebutuhan Warga</th>
                                    <th class="px-5 py-3.5 text-right">Selisih</th>
                                    <th class="px-5 py-3.5 text-center">Status Neraca</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-800/60">
                                @forelse($neracaLintasDesa as $row)
                                    @php
                                        $isSurplus = $row->status_surplus_defisit === 'surplus';
                                        $isDefisit = $row->status_surplus_defisit === 'defisit';
                                    @endphp
                                    <tr class="hover:bg-ink-800/40 transition-colors">
                                        <td class="px-5 py-3.5 font-medium text-paper-200">
                                            {{ $row->wilayah?->nama ?? '-' }}
                                        </td>
                                        <td class="px-5 py-3.5 font-bold text-white">
                                            🌾 {{ $row->komoditas?->nama ?? '-' }}
                                        </td>
                                        <td class="px-5 py-3.5 text-paper-400 capitalize">
                                            {{ $row->komoditas?->kategori ?? 'Umum' }}
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono text-paper-200">
                                            {{ number_format((float) $row->total_ketersediaan, 0, ',', '.') }} KG
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono text-paper-400">
                                            {{ number_format((float) $row->total_kebutuhan, 0, ',', '.') }} KG
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono font-bold {{ $isSurplus ? 'text-sawah-100' : ($isDefisit ? 'text-merah-100' : 'text-paper-300') }}">
                                            {{ $row->selisih > 0 ? '+' : '' }}{{ number_format((float) $row->selisih, 0, ',', '.') }} KG
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            @if($isSurplus)
                                                <span class="inline-flex items-center rounded-full bg-sawah-500/20 border border-sawah-500/30 px-2.5 py-0.5 text-[11px] font-semibold text-sawah-100">
                                                    Surplus (Siap Pasok)
                                                </span>
                                            @elseif($isDefisit)
                                                <span class="inline-flex items-center rounded-full bg-merah-500/20 border border-merah-500/30 px-2.5 py-0.5 text-[11px] font-semibold text-merah-100">
                                                    Defisit (Perlu Pasokan)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-paper-200/10 border border-paper-200/20 px-2.5 py-0.5 text-[11px] text-paper-300">
                                                    Seimbang
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-5 py-8 text-center text-paper-500 text-xs">
                                            Belum ada data neraca komoditas untuk filter yang dipilih.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 3. Daftar Master Komoditas Terdaftar --}}
            <div class="rounded-2xl border border-ink-800 bg-ink-900/90 p-6 space-y-3">
                <h3 class="font-display text-sm font-bold text-white">
                    Daftar Komoditas Terstandarisasi Sistem ({{ $daftarKomoditas->count() }} Komoditas)
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($daftarKomoditas as $kom)
                        <span class="inline-flex items-center gap-2 rounded-lg border border-ink-700 bg-ink-800 px-3 py-1.5 text-xs text-paper-200">
                            <span class="font-bold text-white">{{ $kom->nama }}</span>
                            <span class="font-mono text-[10px] uppercase text-padi-300 bg-padi-500/10 px-1.5 py-0.5 rounded">
                                {{ $kom->kategori ?? 'umum' }}
                            </span>
                        </span>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-layouts.guest>
