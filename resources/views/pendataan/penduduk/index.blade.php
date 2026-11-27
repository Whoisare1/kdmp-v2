<x-layouts.app :title="$title" eyebrow="Modul Pendataan">

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-padi-100 text-padi-800">
                    {{ $desaAktif?->nama ?? 'Desa' }}
                </span>
                <span class="text-xs text-paper-500 font-mono">
                    Kec. {{ $desaAktif?->parent?->nama ?? 'Juwana' }}, Kab. {{ $desaAktif?->parent?->parent?->nama ?? 'Pati' }}
                </span>
            </div>
            <h2 class="text-xl font-display font-semibold text-ink-900 mt-1">Pendataan Penduduk: {{ $desaAktif?->nama ?? 'Desa' }}</h2>
            <p class="text-xs text-paper-500">Pencatatan data demografi dan jumlah penduduk desa berdasarkan 5 kelompok umur standar.</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
            <form method="GET" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                <input type="number" name="tahun" value="{{ $tahun }}" placeholder="Tahun"
                       class="flex-1 sm:w-28 rounded-lg border border-paper-300 bg-white px-3 py-1.5 text-xs font-mono focus:outline-none">
                <button class="rounded-lg bg-ink-900 text-white px-3 py-1.5 text-xs font-medium hover:bg-ink-800 shrink-0">Filter Tahun</button>
                @if($tahun)
                    <a href="{{ route('pendataan.penduduk.index') }}" class="text-xs text-paper-500 hover:text-ink-900 shrink-0">Reset</a>
                @endif
            </form>
            <a href="{{ route('pendataan.penduduk.create') }}" class="inline-flex justify-center items-center rounded-lg bg-padi-500 hover:bg-padi-600 text-ink-950 font-semibold px-4 py-2 text-xs shadow-sm transition w-full sm:w-auto text-center">
                + Input Data Penduduk
            </a>
        </div>
    </div>

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
                    <th class="px-4 py-3 text-right">Aksi</th>
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
                        <td class="px-4 py-3 text-right font-sans">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('pendataan.penduduk.edit', $row) }}" class="text-xs text-blue-600 hover:underline">Edit</a>
                                <form action="{{ route('pendataan.penduduk.destroy', $row) }}" method="POST" onsubmit="return confirm('Hapus data tahun ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-paper-400 font-sans text-xs">
                            Belum ada catatan penduduk untuk desa ini. Silakan klik tombol <strong>+ Input Data Penduduk</strong>.
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
