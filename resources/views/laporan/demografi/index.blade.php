<x-layouts.app :title="$title" eyebrow="Laporan Kependudukan Desa">

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
            <h2 class="text-xl font-display font-semibold text-ink-900">Laporan Demografi: {{ $desaAktif?->nama ?? 'Desa' }}</h2>
            <p class="text-xs text-paper-500">Sebaran penduduk desa ini berdasarkan lima kelompok umur standar.</p>
        </div>

        <button onclick="window.print()" class="px-3 py-1.5 rounded-lg border border-paper-300 bg-white text-xs font-medium hover:bg-paper-50">
            Cetak / PDF
        </button>
    </div>

    {{-- Filter Panel --}}
    <div class="mb-5 bg-white p-4 rounded-xl border border-paper-200 shadow-sm print:hidden">
        <form method="GET" class="flex items-center gap-3">
            <div>
                <label class="block text-[11px] font-medium text-paper-600 mb-1">Filter Tahun</label>
                <input type="number" name="tahun" value="{{ $tahun }}" placeholder="Semua Tahun" class="rounded-lg border border-paper-300 p-1.5 text-xs font-mono w-32">
            </div>
            <div class="pt-4 flex items-center gap-2">
                <button type="submit" class="px-4 py-1.5 bg-ink-900 text-white rounded-lg text-xs font-medium hover:bg-ink-800">
                    Terapkan Filter
                </button>
                @if($tahun)
                    <a href="{{ route('laporan.demografi.index') }}" class="text-xs text-paper-500 hover:text-ink-900">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Rekapitulasi Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 mb-5">
        <div class="bg-pink-50 p-3 rounded-lg border border-pink-200 text-center">
            <span class="text-[10px] uppercase font-mono text-pink-700 font-bold">Balita (0-4)</span>
            <p class="text-lg font-mono font-bold text-pink-900 mt-0.5">{{ number_format($rekap['totalBalita']) }}</p>
        </div>
        <div class="bg-amber-50 p-3 rounded-lg border border-amber-200 text-center">
            <span class="text-[10px] uppercase font-mono text-amber-700 font-bold">Anak (5-9)</span>
            <p class="text-lg font-mono font-bold text-amber-900 mt-0.5">{{ number_format($rekap['totalAnak']) }}</p>
        </div>
        <div class="bg-cyan-50 p-3 rounded-lg border border-cyan-200 text-center">
            <span class="text-[10px] uppercase font-mono text-cyan-700 font-bold">Remaja (10-19)</span>
            <p class="text-lg font-mono font-bold text-cyan-900 mt-0.5">{{ number_format($rekap['totalRemaja']) }}</p>
        </div>
        <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-200 text-center">
            <span class="text-[10px] uppercase font-mono text-emerald-700 font-bold">Dewasa (20-59)</span>
            <p class="text-lg font-mono font-bold text-emerald-900 mt-0.5">{{ number_format($rekap['totalDewasa']) }}</p>
        </div>
        <div class="bg-purple-50 p-3 rounded-lg border border-purple-200 text-center">
            <span class="text-[10px] uppercase font-mono text-purple-700 font-bold">Lansia (60+)</span>
            <p class="text-lg font-mono font-bold text-purple-900 mt-0.5">{{ number_format($rekap['totalLansia']) }}</p>
        </div>
        <div class="bg-padi-100 p-3 rounded-lg border border-padi-300 text-center">
            <span class="text-[10px] uppercase font-mono text-padi-900 font-bold">Total Jiwa</span>
            <p class="text-lg font-mono font-bold text-ink-950 mt-0.5">{{ number_format($rekap['totalJiwa']) }}</p>
        </div>
    </div>

    {{-- Tabel Data --}}
    <div class="overflow-x-auto rounded-xl border border-paper-200 bg-white shadow-sm">
        <table class="w-full min-w-[700px] text-left text-sm">
            <thead class="border-b border-paper-200 bg-paper-50 font-mono text-[11px] uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-4 py-3 text-center">Tahun</th>
                    <th class="px-4 py-3 text-right">Balita (0-4)</th>
                    <th class="px-4 py-3 text-right">Anak (5-9)</th>
                    <th class="px-4 py-3 text-right">Remaja (10-19)</th>
                    <th class="px-4 py-3 text-right">Dewasa (20-59)</th>
                    <th class="px-4 py-3 text-right">Lansia (60+)</th>
                    <th class="px-4 py-3 text-right font-bold text-ink-900">Total Penduduk</th>
                    <th class="px-4 py-3 text-center">Sumber Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @forelse($items as $row)
                    <tr class="hover:bg-paper-50/60 font-mono text-xs">
                        <td class="px-4 py-3 text-center font-bold text-ink-900">{{ $row->tahun }}</td>
                        <td class="px-4 py-3 text-right text-pink-700">{{ number_format($row->balita) }}</td>
                        <td class="px-4 py-3 text-right text-amber-700">{{ number_format($row->anak) }}</td>
                        <td class="px-4 py-3 text-right text-cyan-700">{{ number_format($row->remaja) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-700">{{ number_format($row->dewasa) }}</td>
                        <td class="px-4 py-3 text-right text-purple-700">{{ number_format($row->lansia) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-ink-950 bg-padi-50/50">{{ number_format($row->total_penduduk) }} Jiwa</td>
                        <td class="px-4 py-3 text-center text-paper-500 font-sans text-[11px]">{{ $row->sumber_data }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-paper-400 font-sans text-xs">
                            Tidak ada data demografi yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-layouts.app>
