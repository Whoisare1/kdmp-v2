<x-layouts.app :title="$title" eyebrow="Modul M2 — Neraca & Hasil Kalkulasi">
    {{-- Ringkasan Status Neraca --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-sm border border-sawah-500/30 bg-sawah-100 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-sawah-600">Komoditas Surplus</p>
            <p class="mt-1 font-display text-3xl font-semibold text-sawah-600">{{ $totalSurplus }}</p>
            <p class="mt-1 text-xs text-ink-600/80">Produksi lokal melebihi kebutuhan desa</p>
        </div>
        <div class="rounded-sm border border-merah-500/30 bg-merah-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-merah-600">Komoditas Defisit</p>
            <p class="mt-1 font-display text-3xl font-semibold text-merah-600">{{ $totalDefisit }}</p>
            <p class="mt-1 text-xs text-ink-600/80">Perlu pengadaan dari luar / supplier (M3)</p>
        </div>
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Komoditas Seimbang</p>
            <p class="mt-1 font-display text-3xl font-semibold text-ink-800">{{ $totalSeimbang }}</p>
            <p class="mt-1 text-xs text-ink-600/70">Kebutuhan terpenuhi tepat oleh produksi</p>
        </div>
    </div>

    {{-- Filter Bar --}}
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

            <select name="status_surplus_defisit" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Status --</option>
                <option value="surplus" {{ $statusFilter === 'surplus' ? 'selected' : '' }}>Surplus Saja</option>
                <option value="defisit" {{ $statusFilter === 'defisit' ? 'selected' : '' }}>Defisit Saja</option>
                <option value="seimbang" {{ $statusFilter === 'seimbang' ? 'selected' : '' }}>Seimbang</option>
            </select>

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">
                Filter
            </button>
        </form>

        <div>
            <a href="{{ route('perencanaan.kebutuhan-komoditas.index', ['tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                ← Kembali ke Detail Kebutuhan M2
            </a>
        </div>
    </div>

    {{-- Tabel Neraca Komoditas (Hasil Kalkulasi M2) --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                    <th class="px-4 py-3 font-medium">Komoditas</th>
                    <th class="px-4 py-3 font-medium">Periode</th>
                    <th class="px-4 py-3 text-right font-medium">Total Kebutuhan</th>
                    <th class="px-4 py-3 text-right font-medium">Produksi / Ketersediaan</th>
                    <th class="px-4 py-3 text-right font-medium">Selisih</th>
                    <th class="px-4 py-3 text-center font-medium">Status Neraca</th>
                    <th class="px-4 py-3 text-right font-medium">Kecukupan</th>
                    <th class="px-4 py-3 text-center font-medium">Versi</th>
                    <th class="px-4 py-3 text-center font-medium">Aksi</th>
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
                        <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                            {{ number_format($item->total_kebutuhan, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                            {{ number_format($item->total_ketersediaan, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs font-semibold {{ $item->selisih < 0 ? 'text-merah-600' : ($item->selisih > 0 ? 'text-sawah-600' : 'text-ink-700') }}">
                            {{ number_format($item->selisih, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($item->status_surplus_defisit === 'surplus')
                                <span class="rounded-full bg-sawah-100 px-2.5 py-0.5 font-mono text-xs font-medium uppercase text-sawah-600">
                                    SURPLUS
                                </span>
                            @elseif ($item->status_surplus_defisit === 'defisit')
                                <span class="rounded-full bg-merah-100 px-2.5 py-0.5 font-mono text-xs font-medium uppercase text-merah-700">
                                    DEFISIT
                                </span>
                            @else
                                <span class="rounded-full bg-paper-200 px-2.5 py-0.5 font-mono text-xs font-medium uppercase text-ink-700">
                                    SEIMBANG
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-ink-800">
                            {{ number_format($item->persentase_kecukupan, 1, ',', '.') }}%
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs text-ink-600">
                            v{{ $item->versi }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($item->status_surplus_defisit === 'defisit')
                                <a href="{{ route('perencanaan.permintaan-pengadaan.from-hasil', ['hasil' => $item->id_hasil]) }}"
                                   class="inline-flex items-center rounded-sm bg-merah-500 px-3 py-1.5 text-xs font-medium text-paper-50 hover:bg-merah-600">
                                    Buat Permintaan Pengadaan
                                </a>
                            @else
                                <span class="text-xs text-ink-600/60">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-sm text-ink-600/70">
                            <p class="font-display text-base font-semibold text-ink-800">Belum ada data neraca komoditas</p>
                            <p class="mt-1 text-xs text-ink-600">
                                Jalankan kalkulasi kebutuhan di modul Kebutuhan Komoditas untuk menghasilkan neraca komoditas desa.
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
</x-layouts.app>
