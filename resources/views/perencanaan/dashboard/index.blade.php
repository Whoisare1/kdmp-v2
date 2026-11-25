<x-layouts.app title="Dashboard Kalkulasi dan Perencanaan" eyebrow="Kalkulasi dan Perencanaan Pangan">
    {{-- Flash Notifications --}}
    @if (session('status'))
        <div class="mb-5 flex items-center justify-between rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">
            <div class="flex items-center gap-2">
                <span class="font-bold text-sawah-600">✓ Berhasil:</span>
                <span>{{ session('status') }}</span>
            </div>
            <span class="font-mono text-xs text-ink-600/70">{{ now()->format('H:i') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 flex items-center justify-between rounded-sm border border-merah-400 bg-merah-50 px-4 py-3 text-sm text-merah-700">
            <div class="flex items-center gap-2">
                <span class="font-bold text-merah-600">⚠ Gagal:</span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if (!empty($isKomparasi))
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-sm border border-blue-300 bg-blue-50 px-4 py-3 text-xs text-blue-900 shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="text-base">ℹ️</span>
                <span><strong>Mode Komparasi:</strong> Anda sedang melihat data statistik dan komoditas <strong>Desa {{ $wilayah?->nama }}</strong> untuk perbandingan.</span>
            </div>
            <a href="{{ route('perencanaan.dashboard') }}" class="rounded bg-blue-600 px-2.5 py-1 text-[11px] font-semibold text-white transition hover:bg-blue-700">
                Kembali ke Desa Anda
            </a>
        </div>
    @endif

    @php
        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $periodeKalkulasi = $periode ?: (now()->month <= 6 ? 'ganjil' : 'genap');
    @endphp

    <div class="mb-4">
        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-ink-600/70">Ringkasan Desa</p>
        <h2 class="mt-1 font-display text-2xl font-semibold text-ink-950">Dashboard — {{ $wilayah?->nama ?? 'Desa' }}</h2>
        <p class="mt-1 text-sm text-ink-600">Pilih desa untuk melihat hasil survei, jumlah penduduk, potensi komoditas, dan neracanya.</p>
    </div>

    <div class="mb-3 flex items-center justify-between border-l-4 border-merah-500 bg-paper-50 px-4 py-3">
        <div>
            <h2 class="font-display text-lg font-semibold text-ink-950">Ringkasan Status Kebutuhan</h2>
            <p class="mt-0.5 text-xs text-ink-600">Surplus, seimbang, dan defisit komoditas pada periode terpilih.</p>
        </div>
        <span class="hidden font-mono text-[10px] uppercase tracking-wider text-ink-600/70 sm:block">Dashboard Kalkulasi</span>
    </div>

    {{-- Filter & Action Header --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-sm border border-paper-300 bg-paper-50 p-3 shadow-2xs">
        <form method="GET" action="{{ route('perencanaan.dashboard') }}" class="flex flex-wrap items-center gap-3">
            {{-- Pemilah Desa (Komparasi Antar Desa) --}}
            <div class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 px-3 py-1.5 text-xs font-medium text-ink-800">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Desa:</span>
                <select name="id_wilayah" onchange="this.form.submit()" class="bg-transparent font-semibold text-ink-950 focus:outline-none cursor-pointer">
                    @foreach ($daftarWilayah as $dw)
                        <option value="{{ $dw->id }}" {{ $idWilayah == $dw->id ? 'selected' : '' }}>
                            Desa {{ $dw->nama }} {{ $dw->id == $idWilayahAsal ? '(Desa Anda)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Kategori --}}
            <select name="kategori" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Kategori</option>
                @foreach ($daftarKategori as $kat)
                    <option value="{{ $kat }}" {{ $kategori === $kat ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $kat)) }}
                    </option>
                @endforeach
            </select>

            {{-- Filter Tahun --}}
            <select name="tahun" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                @foreach (range(2025, 2030) as $t)
                    <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                @endforeach
            </select>

            {{-- Filter periode enam bulanan --}}
            <select name="periode" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Periode</option>
                <option value="ganjil" {{ request('periode') === 'ganjil' ? 'selected' : '' }}>Periode Ganjil (Januari–Juni)</option>
                <option value="genap" {{ request('periode') === 'genap' ? 'selected' : '' }}>Periode Genap (Juli–Desember)</option>
            </select>

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-200/80 px-3 py-2 text-xs font-medium text-ink-800 hover:border-merah-400 hover:text-merah-600">
                Terapkan Filter
            </button>
            @if ($periode || $kategori)
                <a href="{{ route('perencanaan.dashboard', ['tahun' => $tahun]) }}" class="text-xs text-ink-600 hover:text-merah-600 hover:underline">
                    Reset
                </a>
            @endif
        </form>

        {{-- Tombol Utama --}}
        <div class="flex items-center gap-2">
            @unless ($isKomparasi)
                <a href="{{ route('perencanaan.kebutuhan-komoditas.index', ['tahun' => $tahun, 'periode' => $periodeKalkulasi]) }}" class="inline-flex items-center gap-2 rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                    <span>∑</span>
                    <span>Kalkulasi &amp; Neraca</span>
                </a>
            @else
                <span class="rounded-sm border border-blue-300 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-900">
                    Mode lihat saja — kalkulasi hanya untuk desa Anda
                </span>
            @endunless
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-3">
            <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600">Hasil Survei</p>
            @if ($sesiSurveiTerakhir)
                <p class="mt-1 text-sm font-semibold text-ink-900">Sesi #{{ $sesiSurveiTerakhir->id }}</p>
                <p class="text-xs text-ink-600">{{ $sesiSurveiTerakhir->tanggal_survei?->format('d/m/Y') ?? 'Tanggal tidak tersedia' }}</p>
            @else
                <p class="mt-1 text-sm font-medium text-ink-600">Belum ada sesi survei</p>
            @endif
        </div>
        <div class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-3">
            <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600">Jumlah Penduduk</p>
            <p class="mt-1 text-lg font-semibold text-ink-900">{{ number_format($totalPenduduk, 0, ',', '.') }} <span class="text-xs font-normal text-ink-600">jiwa</span></p>
            <a href="{{ route('perencanaan.demografi.index', ['id_wilayah' => $idWilayah, 'tahun' => $tahun]) }}" class="text-xs font-medium text-merah-600 hover:underline">Lihat rekap penduduk</a>
        </div>
        <div class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-3">
            <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600">Potensi Komoditi</p>
            <p class="mt-1 text-lg font-semibold text-ink-900">{{ number_format($jumlahCatatanProduksi, 0, ',', '.') }} <span class="text-xs font-normal text-ink-600">catatan panen</span></p>
            <a href="{{ route('perencanaan.potensi-produksi.index', ['id_wilayah' => $idWilayah, 'tahun' => $tahun]) }}" class="text-xs font-medium text-merah-600 hover:underline">Lihat komoditi desa</a>
        </div>
    </div>

    {{-- Banner Status Terkini & Waktu Kalkulasi --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-l-4 border-merah-500 bg-paper-50 px-4 py-3 text-xs text-ink-800 shadow-2xs">
        <div class="flex flex-wrap items-center gap-3">
            <span class="font-semibold text-ink-900">Status Snapshot Terkini:</span>
            <span>
                Periode Terpilih: <strong>{{ $periode ? ucfirst($periode) . ' ' : 'Semua Periode ' }}{{ $tahun }}</strong>
            </span>
            <span class="text-paper-300">|</span>
            <span>
                Snapshot Versi: <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-ink-800">v{{ $versiAktif }}</span>
            </span>
            <span class="text-paper-300">|</span>
            <span>
                Terakhir Dihitung:
                <strong>{{ $waktuKalkulasiTerakhir ? \Carbon\Carbon::parse($waktuKalkulasiTerakhir)->format('d M Y, H:i') . ' WIB' : 'Belum Pernah' }}</strong>
            </span>
        </div>
        <div>
            @if ($adaDataDemografi)
                <span class="inline-flex items-center gap-1 rounded bg-sawah-100 px-2 py-0.5 font-mono text-[11px] font-medium text-sawah-600">
                    ✓ Data Demografi Survei Siap ({{ number_format($totalPenduduk, 0, ',', '.') }} Jiwa)
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded bg-merah-100 px-2 py-0.5 font-mono text-[11px] font-medium text-merah-600">
                    ⚠ Data Demografi Belum Tersedia di Survei
                </span>
            @endif
        </div>
    </div>

    {{-- Pilihan kategori dan daftar komoditas hasil kalkulasi --}}
    @if ($distribusiNeraca->isEmpty())
        <div class="mb-6 rounded-sm border border-paper-300 bg-paper-50 p-5 text-sm text-ink-600 shadow-2xs">
            Belum ada hasil kalkulasi untuk desa dan periode yang dipilih.
        </div>
    @else
        <div class="mb-5 space-y-2">
            <p class="text-[11px] font-mono uppercase tracking-wide text-ink-600">Neraca Komoditas per Kategori</p>
            @foreach ($distribusiNeraca as $kategoriKomoditas => $komoditasKategori)
                <details class="rounded-sm border border-paper-300 bg-paper-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 hover:bg-paper-100">
                        <span class="font-semibold text-ink-900">{{ ucwords(str_replace('_', ' ', $kategoriKomoditas)) }}</span>
                        <span class="text-xs text-ink-600">{{ $komoditasKategori->count() }} komoditas <span aria-hidden="true">⌄</span></span>
                    </summary>
                    <div class="space-y-2 border-t border-paper-200 bg-paper-100/40 p-3">
                        @forelse ($komoditasKategori as $ringkasanKomoditas)
                            @php
                                $statusKomoditas = $ringkasanKomoditas['status_terbaru'];
                                $kelasStatus = match ($statusKomoditas) {
                                    'surplus' => 'bg-sawah-100 text-sawah-700',
                                    'defisit' => 'bg-merah-50 text-merah-700',
                                    default => 'bg-paper-200 text-ink-700',
                                };
                            @endphp
                            <details class="rounded-sm border border-paper-200 bg-paper-50">
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 px-3 py-2">
                                    <span class="font-medium text-ink-900">{{ $ringkasanKomoditas['komoditas']?->nama ?? 'Komoditas' }}</span>
                                    <span class="flex flex-wrap items-center gap-3 text-[11px] text-ink-600">
                                        <span>
                                            @if ($ringkasanKomoditas['total_kebutuhan'] === null)
                                                Kebutuhan belum dihitung
                                            @else
                                                Kebutuhan {{ number_format($ringkasanKomoditas['total_kebutuhan'], 1, ',', '.') }}
                                            @endif
                                        </span>
                                        <span>Tersedia {{ number_format($ringkasanKomoditas['total_ketersediaan'], 1, ',', '.') }}</span>
                                        <span class="rounded px-1.5 py-0.5 font-semibold {{ $kelasStatus }}">{{ $statusKomoditas === 'belum_dihitung' ? 'Belum dihitung' : ucfirst($statusKomoditas) }}</span>
                                        <span aria-hidden="true">⌄</span>
                                    </span>
                                </summary>
                                <div class="divide-y divide-paper-200 border-t border-paper-200 px-3">
                                    @if ($ringkasanKomoditas['items']->isNotEmpty())
                                        @foreach ($ringkasanKomoditas['items'] as $hasilPeriode)
                                            <div class="flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
                                                <span class="text-ink-700">{{ $namaBulanList[$hasilPeriode->bulan] ?? "Bulan {$hasilPeriode->bulan}" }} {{ $hasilPeriode->tahun }}</span>
                                                <span class="text-ink-600">Kebutuhan {{ number_format((float) $hasilPeriode->total_kebutuhan, 1, ',', '.') }} · Tersedia {{ number_format((float) $hasilPeriode->total_ketersediaan, 1, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    @elseif (!empty($ringkasanKomoditas['produksi_tanpa_kalkulasi']))
                                        @foreach ($ringkasanKomoditas['produksi_tanpa_kalkulasi'] as $catatanProduksi)
                                            <div class="flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
                                                <span class="text-ink-700">{{ $namaBulanList[$catatanProduksi->bulan] ?? "Bulan {$catatanProduksi->bulan}" }} {{ $catatanProduksi->tahun }}</span>
                                                <span class="text-ink-600">Produksi tercatat {{ number_format((float) $catatanProduksi->jumlah_produksi, 1, ',', '.') }} {{ $catatanProduksi->satuan }} · Belum ada hasil kalkulasi</span>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </details>
                        @empty
                            <p class="rounded-sm bg-paper-50 px-3 py-2 text-xs text-ink-600">
                                Belum ada hasil kalkulasi untuk sektor ini pada filter yang dipilih.
                            </p>
                        @endforelse
                    </div>
                </details>
            @endforeach
        </div>
    @endif

    {{-- Ringkasan status dan defisit ditampilkan berurutan agar mudah dibaca. --}}
    <div class="mb-6 space-y-4">
        <section class="rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs">
                <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                    <div>
                        <h3 class="font-display text-base font-semibold text-ink-900">Distribusi Status Neraca Komoditas</h3>
                        <p class="text-xs text-ink-600">Jumlah komoditas surplus, seimbang, dan defisit pada hasil kalkulasi</p>
                    </div>
                    <span class="font-mono text-xs text-ink-600/70">{{ $totalKomoditasTerhitung }} Komoditas</span>
                </div>

                {{-- Stacked Progress Bar --}}
                <div class="mt-4">
                    <div class="flex h-4 w-full overflow-hidden rounded-xs bg-paper-200">
                        @if ($totalKomoditasTerhitung > 0)
                            <div style="width: {{ $persenSurplus }}%" class="bg-sawah-500 transition-all" title="Surplus: {{ $persenSurplus }}%"></div>
                            <div style="width: {{ $persenSeimbang }}%" class="bg-padi-500 transition-all" title="Seimbang: {{ $persenSeimbang }}%"></div>
                            <div style="width: {{ $persenDefisit }}%" class="bg-merah-500 transition-all" title="Defisit: {{ $persenDefisit }}%"></div>
                        @else
                            <div class="w-full bg-paper-300" title="Belum ada data"></div>
                        @endif
                    </div>

                    {{-- Legend --}}
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-4 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-[2px] bg-sawah-500"></span>
                            <span class="text-ink-800">Surplus: <strong>{{ $totalSurplus }}</strong> ({{ $persenSurplus }}%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-[2px] bg-padi-500"></span>
                            <span class="text-ink-800">Seimbang: <strong>{{ $totalSeimbang }}</strong> ({{ $persenSeimbang }}%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-[2px] bg-merah-500"></span>
                            <span class="text-ink-800">Defisit: <strong>{{ $totalDefisit }}</strong> ({{ $persenDefisit }}%)</span>
                        </div>
                    </div>
                </div>

        </section>

        <section class="rounded-sm border border-merah-500/30 bg-paper-50 p-5 shadow-2xs">
                <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                    <div>
                        <h3 class="font-display text-base font-semibold text-merah-700">Defisit Terbesar (Prioritas Pengadaan)</h3>
                        <p class="text-xs text-ink-600">Komoditas yang memerlukan pemenuhan pasokan luar</p>
                    </div>
                    <span class="rounded bg-merah-100 px-2 py-0.5 font-mono text-xs font-semibold text-merah-700">
                        {{ $komoditasDefisitTerbesar->count() }} Item
                    </span>
                </div>

                <div class="mt-4 divide-y divide-paper-200">
                    @forelse ($komoditasDefisitTerbesar as $item)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-ink-950">{{ $item->komoditas?->nama }}</p>
                                    <p class="text-xs text-ink-600">
                                        Sektor: {{ ucfirst(str_replace('_', ' ', strtolower(trim((string) ($item->komoditas?->kategori ?: 'lainnya'))))) }} ·
                                        Kebutuhan: {{ number_format($item->total_kebutuhan, 1, ',', '.') }} | Ketersediaan: {{ number_format($item->total_ketersediaan, 1, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-bold text-merah-600">
                                        {{ number_format($item->selisih, 1, ',', '.') }}
                                    </p>
                                    <span class="text-[10px] text-ink-600/70">Kecukupan: {{ number_format($item->persentase_kecukupan, 1, ',', '.') }}%</span>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-between">
                                <a href="{{ route('perencanaan.neraca-komoditas.show', $item->id_hasil) }}" class="text-xs text-ink-700 hover:text-merah-600 hover:underline">
                                    Lihat Detail &rarr;
                                </a>
                                @if (!empty($isKomparasi))
                                    <span class="text-[11px] text-ink-600">Desa pembanding · lihat saja</span>
                                @else
                                    <a href="{{ route('perencanaan.rekomendasi-pengadaan.index') }}" class="text-xs font-semibold text-merah-700 hover:underline">
                                        Lihat rekomendasi pengadaan
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-ink-600">
                            <p class="font-semibold text-sawah-600">Tidak ada komoditas defisit</p>
                            <p class="mt-1 text-ink-600/70">Seluruh komoditas pada periode ini berada dalam status seimbang atau surplus.</p>
                        </div>
                    @endforelse
                </div>
        </section>
    </div>

</x-layouts.app>
