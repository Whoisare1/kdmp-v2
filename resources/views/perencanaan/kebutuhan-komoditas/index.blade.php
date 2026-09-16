<x-layouts.app :title="$title" eyebrow="Modul M2 — Perencanaan">
    {{-- Ringkasan Statistik --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Komoditas Terhitung</p>
            <p class="mt-1 font-display text-2xl font-semibold text-ink-900">{{ $totalKomoditasTerhitung }} Komoditas</p>
            <p class="mt-1 text-xs text-ink-600/70">Periode Tahun {{ $tahun }} {{ $bulan ? 'Bulan ' . $bulan : '(Semua Bulan)' }}</p>
        </div>
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Total Estimasi Kebutuhan</p>
            <p class="mt-1 font-display text-2xl font-semibold text-merah-600">{{ number_format($ringkasanKebutuhan, 2, ',', '.') }}</p>
            <p class="mt-1 text-xs text-ink-600/70">Total akumulasi volume kebutuhan komoditas</p>
        </div>
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Basis Data Demografi (M1)</p>
            <p class="mt-1 font-display text-2xl font-semibold text-sawah-600">{{ number_format($totalPenduduk, 0, ',', '.') }} Jiwa</p>
            <p class="mt-1 text-xs {{ $adaDataDemografi ? 'text-sawah-600' : 'text-merah-600' }}">
                {{ $adaDataDemografi ? '✓ Data demografi desa tersedia' : '⚠ Belum ada data demografi' }}
            </p>
        </div>
    </div>

    {{-- Filter & Action Bar --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="tahun" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                @foreach (range(2025, 2030) as $t)
                    <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                @endforeach
            </select>

            <select name="bulan" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Bulan --</option>
                @php
                    $namaBulanList = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                @endphp
                @foreach ($namaBulanList as $num => $nama)
                    <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>{{ $nama }}</option>
                @endforeach
            </select>

            <select name="id_komoditas" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Komoditas --</option>
                @foreach ($daftarKomoditas as $k)
                    <option value="{{ $k->id }}" {{ $idKomoditas == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">
                Filter
            </button>
        </form>

        <div class="flex items-center gap-2">
            <a href="{{ route('perencanaan.neraca-komoditas.index', ['tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                Lihat Neraca Komoditas →
            </a>

            <button type="button"
                    onclick="document.getElementById('modal-kalkulasi').classList.remove('hidden')"
                    class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                ⚡ Jalankan Kalkulasi M2
            </button>
        </div>
    </div>

    {{-- Tabel Hasil Kalkulasi Kebutuhan --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                    <th class="px-4 py-3 font-medium">Komoditas</th>
                    <th class="px-4 py-3 font-medium">Periode</th>
                    <th class="px-4 py-3 font-medium">Kelompok Umur</th>
                    <th class="px-4 py-3 text-right font-medium">Populasi (Jiwa)</th>
                    <th class="px-4 py-3 text-right font-medium">Standar Harian</th>
                    <th class="px-4 py-3 text-center font-medium">Faktor Musiman</th>
                    <th class="px-4 py-3 text-right font-medium">Kebutuhan Bulanan</th>
                    <th class="px-4 py-3 font-medium">Satuan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3 font-medium text-ink-900">
                            {{ $item->komoditas?->nama ?? '—' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-ink-700">
                            {{ $namaBulanList[$item->bulan] ?? 'Bln ' . $item->bulan }} {{ $item->tahun }}
                        </td>
                        <td class="px-4 py-3 text-ink-800">
                            <span class="rounded-full bg-paper-200/80 px-2.5 py-0.5 text-xs font-medium uppercase tracking-wide text-ink-700">
                                {{ $item->kelompok_umur }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                            {{ number_format($item->jumlah_penduduk, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                            {{ number_format($item->per_kapita_harian, 4, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs text-ink-700">
                            {{ number_format($item->faktor_musiman, 2, ',', '.') }}x
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs font-semibold text-merah-600">
                            {{ number_format($item->kebutuhan_bulanan, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-ink-600">
                            {{ $item->satuan }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-ink-600/70">
                            <p class="font-display text-base font-semibold text-ink-800">Belum ada data kalkulasi kebutuhan</p>
                            <p class="mt-1 text-xs text-ink-600">
                                @if (!$adaDataDemografi)
                                    Data demografi penduduk untuk Tahun {{ $tahun }} belum diisi. Masukkan data demografi M1 terlebih dahulu.
                                @else
                                    Klik tombol <strong class="text-merah-600">"Jalankan Kalkulasi M2"</strong> di atas untuk memproses estimasi kebutuhan komoditas pangan desa.
                                @endif
                            </p>
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
    <div id="modal-kalkulasi" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink-950/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-xl">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <h3 class="font-display text-base font-semibold text-ink-900">Jalankan Kalkulasi Kebutuhan M2</h3>
                <button type="button" onclick="document.getElementById('modal-kalkulasi').classList.add('hidden')"
                        class="text-ink-600 hover:text-ink-900">&times;</button>
            </div>

            <form method="POST" action="{{ route('perencanaan.kebutuhan-komoditas.kalkulasi') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-700">Tahun Perencanaan</label>
                    <select name="tahun" required class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        @foreach (range(2025, 2030) as $t)
                            <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-700">Bulan Perencanaan</label>
                    <select name="bulan" required class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        @foreach ($namaBulanList as $num => $nama)
                            <option value="{{ $num }}" {{ ($bulan ?? date('n')) == $num ? 'selected' : '' }}>{{ $nama }} (Bulan {{ $num }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-700">Komoditas</label>
                    <select name="id_komoditas" class="w-full rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="">-- Semua Komoditas Aktif --</option>
                        @foreach ($daftarKomoditas as $k)
                            <option value="{{ $k->id }}" {{ $idKomoditas == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-ink-600/70">Pilih komoditas tertentu atau hitung seluruh komoditas sekaligus.</p>
                </div>

                <div class="rounded-sm bg-paper-200/70 p-3 text-xs text-ink-700">
                    <p class="font-medium text-ink-900">Formula Kalkulasi:</p>
                    <p class="mt-0.5 font-mono text-[11px] text-ink-600">Populasi × Standar Gizi Harian × Jumlah Hari Bulan × Faktor Musiman</p>
                    <p class="mt-1 text-[11px] text-ink-600/80">Hasil akan disimpan sebagai snapshot di <code class="text-ink-800">kebutuhan_komoditas</code> dan diagregasi ke <code class="text-ink-800">hasil_kalkulasi</code>.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            onclick="document.getElementById('modal-kalkulasi').classList.add('hidden')"
                            class="rounded-sm border border-paper-300 px-3 py-2 text-sm text-ink-700 hover:border-paper-400">
                        Batal
                    </button>
                    <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                        Proses Kalkulasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
