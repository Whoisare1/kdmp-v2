<x-layouts.app :title="$title" eyebrow="Kalkulasi Kebutuhan">
    {{-- Navigasi kalkulasi --}}
    <x-perencanaan.nav-m2 active="kebutuhan" />

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

    @php
        $statusProses = $items->isNotEmpty() ? 'BERHASIL DIHITUNG' : 'BELUM DIHITUNG';
        $kebutuhanPerKomoditas = $items->groupBy('id_komoditas');
        $selectedMasterIds = array_map('intval', (array) old(
            'master_komoditas_ids',
            $masterKomoditas->pluck('id_komoditas')->all()
        ));
        $kategoriMaster = $masterKomoditas
            ->groupBy(fn ($master) => $master->komoditas?->kategori ?: 'lainnya');
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-ink-700">
            Desa yang dihitung:
            <strong class="font-semibold text-ink-950">{{ $wilayah?->nama ?? 'Desa aktif' }}</strong>
            <span class="text-xs text-ink-600">(mengikuti desa akun yang sedang masuk)</span>
        </p>
        <a href="{{ route('perencanaan.dashboard', ['tahun' => $tahun]) }}" class="text-xs font-medium text-merah-600 hover:underline">
            Lihat perbandingan desa &rarr;
        </a>
    </div>

    {{-- Parameter Kalkulasi & Panel Status Proses --}}
    <div class="mb-6 rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-paper-200 pb-3">
            <div>
                <h2 class="font-display text-base font-semibold text-ink-900">Parameter Perhitungan Kebutuhan Pangan Desa</h2>
                <p class="text-xs text-ink-600">Rumus: <code>Kebutuhan = Populasi(Kelompok Umur) &times; Standar Gizi Harian &times; Hari Kalender &times; Faktor Musiman</code></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (($totalBarisTanpaStandar ?? 0) > 0)
                    <a href="{{ route('perencanaan.standar-kebutuhan.index') }}"
                       class="inline-flex items-center gap-1 rounded bg-amber-100 border border-amber-400/40 px-2 py-0.5 font-mono text-[11px] font-semibold text-amber-800 hover:underline">
                        <span>⚠</span>
                        <span>{{ $totalBarisTanpaStandar }} Baris Standar Belum Lengkap</span>
                    </a>
                @else
                    <span class="inline-flex items-center gap-1 rounded bg-sawah-100 border border-sawah-500/30 px-2 py-0.5 font-mono text-[11px] font-semibold text-sawah-600">
                        <span>✓</span>
                        <span>Standar Gizi Terpenuhi</span>
                    </span>
                @endif
                <span class="font-mono text-xs text-ink-600">Status:</span>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 font-mono text-xs font-semibold
                      {{ $items->isNotEmpty() ? 'bg-sawah-100 text-sawah-600 border border-sawah-500/30' : 'bg-merah-100 text-merah-700 border border-merah-400/40' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $items->isNotEmpty() ? 'bg-sawah-600' : 'bg-merah-600' }}"></span>
                    {{ $statusProses }}
                </span>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 text-xs text-ink-800">
            <div class="rounded-sm border border-paper-200 bg-paper-100/70 p-3">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Periode 6 Bulanan</span>
                <p class="mt-1 font-semibold text-ink-950 text-sm">
                    {{ request('periode') ? ucfirst(request('periode')) : 'Semua Periode' }} {{ $tahun }}
                </p>
                <p class="text-[11px] text-ink-600">Periode enam bulan</p>
            </div>
            <div class="rounded-sm border border-paper-200 bg-paper-100/70 p-3">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Basis Data Populasi Survei</span>
                <p class="mt-1 font-semibold text-ink-950 text-sm">
                    {{ number_format($totalPenduduk, 0, ',', '.') }} Jiwa
                </p>
                <p class="text-[11px] {{ $adaDataDemografi ? 'text-sawah-600' : 'text-merah-600' }}">
                    {{ $adaDataDemografi ? '✓ Demografi desa terhubung' : '⚠ Belum ada demografi' }}
                </p>
            </div>
            <div class="rounded-sm border border-paper-200 bg-paper-100/70 p-3">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Komoditas Terhitung</span>
                <p class="mt-1 font-semibold text-ink-950 text-sm">
                    {{ $totalKomoditasTerhitung }} Komoditas
                </p>
                <p class="text-[11px] text-ink-600">{{ $items->count() }} Baris Kelompok Umur</p>
            </div>
            <div class="rounded-sm border border-paper-200 bg-paper-100/70 p-3">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Total Volume Kebutuhan</span>
                <p class="mt-1 font-semibold text-merah-600 text-sm">
                    {{ number_format($ringkasanKebutuhan, 2, ',', '.') }}
                </p>
                <p class="text-[11px] text-ink-600">Akumulasi seluruh kelompok</p>
            </div>
        </div>
    </div>

    {{-- Filter & Action Bar --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="tahun" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                @foreach (range(2025, 2030) as $t)
                    <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                @endforeach
            </select>

            <select name="periode" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Periode --</option>
                <option value="ganjil" {{ request('periode') === 'ganjil' ? 'selected' : '' }}>Ganjil (Januari–Juni)</option>
                <option value="genap" {{ request('periode') === 'genap' ? 'selected' : '' }}>Genap (Juli–Desember)</option>
            </select>

            <select name="id_komoditas" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Komoditas --</option>
                @foreach ($daftarKomoditas as $k)
                    <option value="{{ $k->id }}" {{ $idKomoditas == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                @endforeach
            </select>

            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari komoditas..."
                class="w-48 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-200/80 px-3 py-2 text-xs font-medium text-ink-800 hover:border-merah-400">
                Filter
            </button>
            @if ($bulan || $idKomoditas || request('q'))
                <a href="{{ route('perencanaan.kebutuhan-komoditas.index', ['tahun' => $tahun]) }}" class="text-xs text-ink-600 hover:text-merah-600 hover:underline">
                    Reset
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            <a href="#master-komoditas" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-700 hover:border-merah-400">
                Atur Master Desa
            </a>

            @if ($masterKomoditas->isNotEmpty())
                <form method="POST" action="{{ route('perencanaan.kebutuhan-komoditas.kalkulasi') }}">
                    @csrf
                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                    <input type="hidden" name="periode" value="{{ request('periode', $bulan ? ($bulan <= 6 ? 'ganjil' : 'genap') : (now()->month <= 6 ? 'ganjil' : 'genap')) }}">
                    <input type="hidden" name="redirect_to" value="neraca">
                    <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                        Hitung Semua Komoditas
                    </button>
                </form>
            @else
                <a href="#master-komoditas" class="rounded-sm bg-amber-100 px-4 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-200">
                    Atur Master Komoditas Dahulu
                </a>
            @endif
        </div>
    </div>

    <section class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-paper-200 px-4 py-3">
            <div>
                <h3 class="font-display text-base font-semibold text-ink-950">Komoditas Desa {{ $wilayah?->nama }} berdasarkan Kategori</h3>
                <p class="text-xs text-ink-600">Pilih kategori untuk melihat komoditas dan hasil kebutuhan. Hasil kalkulasi ditampilkan di Neraca.</p>
            </div>
            <span class="font-mono text-xs text-ink-600">{{ $komoditasMasterTampil->count() }} komoditas master</span>
        </div>

        @if ($komoditasMasterTampil->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-ink-600">
                @if ($masterKomoditas->isEmpty())
                    Master komoditas desa belum diatur. Pilih kategori dan komoditas di bagian Master Kalkulasi Desa.
                @else
                    Tidak ada komoditas yang cocok dengan filter.
                @endif
            </p>
        @else
            <div class="divide-y divide-paper-200">
                @foreach ($kategoriMaster as $kategoriKomoditas => $komoditasDalamKategori)
                    @php
                        $idKomoditasDalamKategori = $komoditasDalamKategori->pluck('id_komoditas')->all();
                        $komoditasKategoriTampil = $komoditasMasterTampil
                            ->whereIn('id_komoditas', $idKomoditasDalamKategori)
                            ->values();
                    @endphp
                    @if ($komoditasKategoriTampil->isNotEmpty())
                        <details class="group">
                            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 px-4 py-3 hover:bg-paper-100">
                                <span class="font-semibold capitalize text-ink-900">{{ str_replace('_', ' ', $kategoriKomoditas) }}</span>
                                <span class="font-mono text-xs text-ink-600">{{ $komoditasKategoriTampil->count() }} komoditas <span aria-hidden="true">⌄</span></span>
                            </summary>
                            <div class="border-t border-paper-200 px-4 py-3">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs text-ink-600">Kebutuhan {{ str_replace('_', ' ', $kategoriKomoditas) }} di Desa {{ $wilayah?->nama }}.</p>
                                    <form method="POST" action="{{ route('perencanaan.kebutuhan-komoditas.kalkulasi') }}">
                                        @csrf
                                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                                        <input type="hidden" name="periode" value="{{ request('periode', $bulan ? ($bulan <= 6 ? 'ganjil' : 'genap') : (now()->month <= 6 ? 'ganjil' : 'genap')) }}">
                                        <input type="hidden" name="kategori" value="{{ $kategoriKomoditas }}">
                                        <input type="hidden" name="redirect_to" value="neraca">
                                        <button type="submit" class="rounded-sm bg-merah-500 px-3 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                                            Hitung kategori ini
                                        </button>
                                    </form>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-[760px] w-full border-collapse text-left text-xs">
                                        <thead class="bg-paper-100 text-[10px] uppercase tracking-wide text-ink-600">
                                            <tr>
                                                <th class="px-3 py-2 font-semibold">Komoditas</th>
                                                <th class="px-3 py-2 text-right font-semibold">Total kebutuhan</th>
                                                <th class="px-3 py-2 text-center font-semibold">Kelompok umur</th>
                                                <th class="px-3 py-2 font-semibold">Satuan</th>
                                                <th class="px-3 py-2 font-semibold">Status</th>
                                                <th class="px-3 py-2 text-right font-semibold">Rincian</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-paper-200">
                                            @foreach ($komoditasKategoriTampil as $master)
                                                @php
                                                    $barisKomoditas = $kebutuhanPerKomoditas->get($master->id_komoditas, collect());
                                                    $standarLengkap = $barisKomoditas->isNotEmpty()
                                                        && $barisKomoditas->every(fn ($baris) => $baris->id_standar && (float) $baris->per_kapita_harian >= 0);
                                                @endphp
                                                <tr class="hover:bg-paper-100/70">
                                                    <td class="whitespace-nowrap px-3 py-2 font-semibold text-ink-950">{{ $master->komoditas?->nama ?? 'Komoditas' }}</td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-right font-mono font-semibold text-merah-700">
                                                        {{ $barisKomoditas->isNotEmpty() ? number_format($barisKomoditas->sum('kebutuhan_bulanan'), 2, ',', '.') : '—' }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-center text-ink-700">
                                                        {{ $barisKomoditas->isNotEmpty() ? $barisKomoditas->pluck('kelompok_umur')->unique()->count() . ' kelompok' : '—' }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-ink-700">{{ $barisKomoditas->first()?->satuan ?? '—' }}</td>
                                                    <td class="whitespace-nowrap px-3 py-2">
                                                        @if ($barisKomoditas->isEmpty())
                                                            <span class="text-ink-600">Belum dihitung</span>
                                                        @else
                                                            <span class="{{ $standarLengkap ? 'text-sawah-700' : 'text-amber-800' }}">
                                                                {{ $standarLengkap ? 'Standar lengkap' : 'Standar belum lengkap' }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-right">
                                                        @if ($barisKomoditas->isNotEmpty())
                                                            <a href="{{ route('perencanaan.kebutuhan-komoditas.show', $barisKomoditas->first()->id) }}" class="font-semibold text-merah-600 hover:underline">Lihat</a>
                                                        @else
                                                            <span class="text-ink-400">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>
                    @endif
                @endforeach
            </div>
        @endif
    </section>

    <div id="master-komoditas" class="mt-6 rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-2xs">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3 border-b border-paper-200 pb-3">
            <div>
                <p class="font-mono text-[10px] uppercase tracking-wider text-merah-600">Master Kalkulasi Desa</p>
                <h2 class="mt-1 font-display text-lg font-semibold text-ink-950">Komoditas untuk Desa {{ $wilayah?->nama ?? 'Anda' }}</h2>
                <p class="mt-1 max-w-2xl text-xs text-ink-600">
                    Pilih komoditas yang relevan untuk dihitung di desa ini. Master ini hanya berlaku untuk desa Anda dan menjadi daftar pilihan saat kalkulasi dijalankan.
                </p>
            </div>
            <span class="rounded-sm bg-paper-200 px-2.5 py-1 font-mono text-xs font-semibold text-ink-800">
                {{ $masterKomoditas->count() }} komoditas dipilih
            </span>
        </div>

        @if (isset($errors) && $errors->has('master_komoditas_ids'))
            <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-3 py-2 text-xs text-merah-700">
                {{ $errors->first('master_komoditas_ids') }}
            </div>
        @endif

        <form method="POST" action="{{ route('perencanaan.kebutuhan-komoditas.master') }}">
            @csrf
            @if ($daftarKomoditas->isEmpty())
                <p class="rounded-sm border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900">
                    Belum ada komoditas aktif di master komoditas sistem. Tambahkan atau aktifkan komoditas terlebih dahulu.
                </p>
            @else
                <div class="divide-y divide-paper-200 rounded-sm border border-paper-200">
                    @foreach ($daftarKomoditas->groupBy(fn ($komoditas) => $komoditas->kategori ?: 'lainnya') as $kategoriKomoditas => $komoditasDalamKategori)
                        <details>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 bg-paper-100/60 px-3 py-2.5 text-xs font-semibold capitalize text-ink-800 hover:bg-paper-100">
                                <span>{{ str_replace('_', ' ', $kategoriKomoditas) }}</span>
                                <span class="font-mono text-[10px] font-normal text-ink-600">{{ $komoditasDalamKategori->count() }} pilihan <span aria-hidden="true">⌄</span></span>
                            </summary>
                            <div class="grid grid-cols-1 gap-x-4 border-t border-paper-200 px-3 py-2 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($komoditasDalamKategori as $komoditasMaster)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-xs text-ink-900 hover:bg-paper-200">
                                        <input
                                            type="checkbox"
                                            name="master_komoditas_ids[]"
                                            value="{{ $komoditasMaster->id }}"
                                            @checked(in_array((int) $komoditasMaster->id, $selectedMasterIds, true))
                                            class="rounded border-paper-300 text-merah-500 focus:ring-merah-400"
                                        >
                                        <span>{{ $komoditasMaster->nama }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-paper-200 pt-3">
                <p class="text-[11px] text-ink-600">Perubahan master tidak menghapus hasil kalkulasi yang sudah tersimpan.</p>
                <button type="submit" @disabled($daftarKomoditas->isEmpty()) class="rounded-sm bg-ink-900 px-4 py-2 text-xs font-semibold text-paper-50 hover:bg-ink-800 disabled:cursor-not-allowed disabled:opacity-50">
                    Simpan Master Desa
                </button>
            </div>
        </form>
    </div>

</x-layouts.app>
