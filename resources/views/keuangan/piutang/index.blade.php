<x-layouts.app title="Buku Piutang" eyebrow="Keuangan">

    {{-- Filter bar --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari pihak..."
                class="w-52 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >

            <select name="status" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-700 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Status</option>
                <option value="belum_lunas" @selected(request('status') === 'belum_lunas')>Belum Lunas</option>
                <option value="sebagian" @selected(request('status') === 'sebagian')>Sebagian</option>
                <option value="lunas" @selected(request('status') === 'lunas')>Lunas</option>
            </select>

            <button class="rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-700 hover:border-merah-400">
                Cari
            </button>

            @if(request()->hasAny(['q','status']))
                <a href="{{ route('keuangan.piutang.index') }}" class="text-sm text-ink-500 hover:text-merah-500 underline">Reset</a>
            @endif
        </form>
    </div>

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Jatuh Tempo</th>
                    <th class="px-4 py-3 font-medium">Pihak</th>
                    <th class="px-4 py-3 font-medium">Sumber</th>
                    <th class="px-4 py-3 text-right font-medium">Nilai Awal</th>
                    <th class="px-4 py-3 text-right font-medium">Sisa Piutang</th>
                    <th class="px-4 py-3 text-center font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3 text-ink-800">{{ $item->tanggal->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-ink-800 {{ $item->tgl_jatuh_tempo->isPast() && $item->status !== 'lunas' ? 'text-merah-600 font-medium' : '' }}">{{ $item->tgl_jatuh_tempo->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-medium text-ink-900">{{ $item->pihak->nama ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $item->sumber_tipe }} #{{ $item->sumber_id }}</td>
                        <td class="px-4 py-3 text-right text-ink-800">{{ number_format($item->nilai_awal, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-medium text-merah-600">{{ number_format($item->sisa(), 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($item->status === 'lunas')
                                <span class="rounded-full bg-sawah-100 px-2 py-0.5 text-xs text-sawah-700">Lunas</span>
                            @elseif($item->status === 'sebagian')
                                <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700">Sebagian</span>
                            @else
                                <span class="rounded-full bg-merah-100 px-2 py-0.5 text-xs text-merah-700">Belum Lunas</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('keuangan.piutang.show', $item) }}" class="text-xs text-merah-500 hover:underline">Detail &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-ink-600/60">Belum ada data piutang.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $items->links() }}
    </div>
</x-layouts.app>
