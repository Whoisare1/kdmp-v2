<x-layouts.app :title="$title" eyebrow="Modul M2 — Kalkulasi Kebutuhan">
    {{-- Sub Navigasi M2 --}}
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
        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $jumlahHariBulan = $bulan ? \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth : 30;
        $statusProses = $items->total() > 0 ? 'BERHASIL DIHITUNG' : 'BELUM DIHITUNG';
    @endphp

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
                      {{ $items->total() > 0 ? 'bg-sawah-100 text-sawah-600 border border-sawah-500/30' : 'bg-merah-100 text-merah-700 border border-merah-400/40' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $items->total() > 0 ? 'bg-sawah-600' : 'bg-merah-600' }}"></span>
                    {{ $statusProses }}
                </span>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 text-xs text-ink-800">
            <div class="rounded-sm border border-paper-200 bg-paper-100/70 p-3">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Periode Perencanaan</span>
                <p class="mt-1 font-semibold text-ink-950 text-sm">
                    {{ $bulan ? $namaBulanList[$bulan] : 'Seluruh Bulan' }} {{ $tahun }}
                </p>
                <p class="text-[11px] text-ink-600">{{ $bulan ? $jumlahHariBulan . ' Hari Kalender' : '12 Bulan' }}</p>
            </div>
            <div class="rounded-sm border border-paper-200 bg-paper-100/70 p-3">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Basis Data Populasi (M1)</span>
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
                <p class="text-[11px] text-ink-600">{{ $items->total() }} Baris Kelompok Umur</p>
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

            <select name="bulan" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Bulan --</option>
                @foreach ($namaBulanList as $num => $nama)
                    <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>{{ $nama }}</option>
                @endforeach
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
            <a href="{{ route('perencanaan.neraca-komoditas.index', ['tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-xs font-medium text-ink-700 hover:border-merah-400">
                Lihat Neraca Komoditas &rarr;
            </a>

            <button type="button"
                    onclick="document.getElementById('modal-kalkulasi').classList.remove('hidden')"
                    class="rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-merah-600">
                ⚡ Jalankan Kalkulasi M2
            </button>
        </div>
    </div>

    {{-- Tabel Hasil Snapshot Kalkulasi Kebutuhan --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-2xs">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/70 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <th class="px-4 py-3 font-medium">Komoditas</th>
                    <th class="px-4 py-3 font-medium">Periode</th>
                    <th class="px-4 py-3 font-medium">Kelompok Umur</th>
                    <th class="px-4 py-3 text-right font-medium">Populasi (Jiwa)</th>
                    <th class="px-4 py-3 text-right font-medium">Standar Per Kapita</th>
                    <th class="px-4 py-3 text-center font-medium">Faktor Musiman</th>
                    <th class="px-4 py-3 text-right font-medium">Kebutuhan Bulanan</th>
                    <th class="px-4 py-3 font-medium">Satuan</th>
                    <th class="px-4 py-3 text-center font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    @php
                        $standarAda = $item->id_standar && (float)$item->per_kapita_harian > 0;
                    @endphp
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3">
                            <span class="font-semibold text-ink-950">{{ $item->komoditas?->nama ?? '—' }}</span>
                            @if ($item->komoditas?->kategori)
                                <span class="block text-[10px] uppercase text-ink-600/70">{{ str_replace('_', ' ', $item->komoditas->kategori) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-ink-700">
                            {{ $namaBulanList[$item->bulan] ?? 'Bln ' . $item->bulan }} {{ $item->tahun }}
                        </td>
                        <td class="px-4 py-3 text-ink-800">
                            <span class="rounded-[2px] bg-paper-200 px-2 py-0.5 font-mono text-xs uppercase tracking-wide text-ink-800">
                                {{ $item->kelompok_umur }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                            {{ number_format($item->jumlah_penduduk, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs">
                            @if ($standarAda)
                                <span class="text-ink-900 font-semibold">{{ number_format($item->per_kapita_harian, 4, ',', '.') }}</span>
                                <span class="text-[10px] text-ink-600/70">/hari</span>
                            @else
                                <div class="inline-flex flex-col items-end">
                                    <span class="rounded bg-amber-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-amber-800">
                                        ⚠ Belum Ada
                                    </span>
                                    <a href="{{ route('perencanaan.standar-kebutuhan.index', ['id_komoditas' => $item->id_komoditas]) }}"
                                       class="text-[10px] text-merah-600 hover:underline">
                                        Atur Standar &rarr;
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs text-ink-700">
                            {{ number_format($item->faktor_musiman, 2, ',', '.') }}x
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ (float)$item->kebutuhan_bulanan > 0 ? 'text-merah-600' : 'text-ink-600' }}">
                            {{ number_format($item->kebutuhan_bulanan, 2, ',', '.') }}
                            @if (!$standarAda)
                                <span class="block text-[9px] font-normal text-amber-700 font-sans">(Standar kosong)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-ink-600">
                            {{ $item->satuan }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('perencanaan.kebutuhan-komoditas.show', $item->id) }}"
                               class="inline-flex items-center rounded-sm border border-paper-300 px-2.5 py-1 text-xs text-ink-700 hover:border-merah-400 hover:text-merah-600">
                                Detail &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-sm text-ink-600/80">
                            <div class="mx-auto max-w-md">
                                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-paper-200 font-mono text-base text-ink-600">
                                    ∅
                                </span>
                                <p class="mt-3 font-display text-base font-semibold text-ink-900">Belum Ada Snapshot Kalkulasi Kebutuhan</p>
                                <p class="mt-1 text-xs text-ink-600">
                                    @if (!$adaDataDemografi)
                                        Data demografi kependudukan untuk Tahun {{ $tahun }} belum tersedia di M1. Masukkan survei demografi terlebih dahulu.
                                    @else
                                        Data demografi siap. Klik tombol <strong class="text-merah-600">"Jalankan Kalkulasi M2"</strong> di atas untuk memproses estimasi kebutuhan komoditas desa secara idempoten.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>

    {{-- Modal Jalankan Kalkulasi --}}
    <div id="modal-kalkulasi" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-sm bg-merah-500 font-bold text-paper-50 text-sm">⚡</span>
                    <div>
                        <h3 class="font-display text-base font-semibold text-ink-955">Jalankan Kalkulasi Kebutuhan M2</h3>
                        <p class="text-[11px] text-ink-600">Proses perhitungan otomatis berbasis demografi &amp; standar gizi</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-kalkulasi').classList.add('hidden')"
                        class="text-ink-600 hover:text-ink-900 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('perencanaan.kebutuhan-komoditas.kalkulasi') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Tahun Perencanaan</label>
                    <select name="tahun" required class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        @foreach (range(2025, 2030) as $t)
                            <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Bulan Perencanaan</label>
                    <select name="bulan" required class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        @foreach ($namaBulanList as $num => $nama)
                            <option value="{{ $num }}" {{ ($bulan ?? date('n')) == $num ? 'selected' : '' }}>{{ $nama }} (Bulan {{ $num }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Komoditas</label>
                    <select name="id_komoditas" class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="">-- Semua Komoditas Aktif --</option>
                        @foreach ($daftarKomoditas as $k)
                            <option value="{{ $k->id }}" {{ $idKomoditas == $k->id ? 'selected' : '' }}>{{ $k->nama }} ({{ $k->kategori }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-ink-600/70">Pilih komoditas tertentu atau hitung seluruh komoditas sekaligus.</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-ink-800">Tampilkan Hasil Pada:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="kebutuhan" checked class="text-merah-500">
                            <span class="font-medium text-ink-900">Kebutuhan</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="dashboard" class="text-merah-500">
                            <span class="font-medium text-ink-900">Dashboard</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 p-2 text-xs cursor-pointer hover:border-merah-400">
                            <input type="radio" name="redirect_to" value="neraca" class="text-merah-500">
                            <span class="font-medium text-ink-900">Neraca</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-sm border border-paper-300/80 bg-paper-200/60 p-3 text-xs text-ink-700">
                    <p class="font-semibold text-ink-900">Formula Kalkulasi:</p>
                    <p class="mt-0.5 font-mono text-[11px] text-ink-800">Populasi &times; Standar Gizi Harian &times; Hari Bulan &times; Faktor Musiman</p>
                    <p class="mt-1 text-[10px] text-ink-600/80">Hasil akan disimpan sebagai snapshot di <code class="text-ink-900">kebutuhan_komoditas</code> dan dimutakhirkan ke <code class="text-ink-900">hasil_kalkulasi</code>.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            onclick="document.getElementById('modal-kalkulasi').classList.add('hidden')"
                            class="rounded-sm border border-paper-300 px-3 py-2 text-xs font-medium text-ink-700 hover:border-paper-400">
                        Batal
                    </button>
                    <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600 shadow-xs">
                        Proses Kalkulasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
