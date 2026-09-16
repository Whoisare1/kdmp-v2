<x-layouts.app :title="$title" eyebrow="Modul M1 — Survei Lapangan">
    @if (session('status'))
        <div class="mb-4 rounded-sm border border-sawah-300 bg-sawah-50 p-4 text-sm text-sawah-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="mb-4 rounded-sm border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari wilayah/catatan..."
                class="w-64 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >
            <select name="status" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">-- Semua Status --</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="terkirim" {{ request('status') === 'terkirim' ? 'selected' : '' }}>Terkirim</option>
                <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
            <button class="rounded-sm border border-paper-300 px-3 py-2 text-sm text-ink-700 hover:border-merah-400">
                Filter
            </button>
        </form>

        <div class="flex items-center gap-2">
            <span class="rounded-sm border border-paper-300 px-3 py-2 text-xs text-ink-600/70">
                Total Sesi: {{ $items->total() }}
            </span>
        </div>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                    <th class="px-4 py-3 font-medium">ID Sesi</th>
                    <th class="px-4 py-3 font-medium">Wilayah Desa</th>
                    <th class="px-4 py-3 font-medium">Tahun / Periode</th>
                    <th class="px-4 py-3 font-medium">Tanggal Survei</th>
                    <th class="px-4 py-3 font-medium">Petugas</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3 font-mono font-medium text-ink-900">#{{ $item->id }}</td>
                        <td class="px-4 py-3 font-medium text-ink-800">{{ $item->wilayah?->nama ?? 'Wilayah #' . $item->id_wilayah }}</td>
                        <td class="px-4 py-3 text-ink-800">
                            Tahun {{ $item->tahun }}
                            @if ($item->bulan)
                                (Bulan {{ $item->bulan }})
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-800">{{ $item->tanggal_survei ? $item->tanggal_survei->format('d M Y') : '—' }}</td>
                        <td class="px-4 py-3 text-ink-800">{{ $item->petugas?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $badgeClass = match($item->status) {
                                    'disetujui' => 'bg-sawah-100 text-sawah-700 border border-sawah-300',
                                    'terkirim' => 'bg-amber-100 text-amber-700 border border-amber-300',
                                    'ditolak' => 'bg-merah-100 text-merah-700 border border-merah-300',
                                    default => 'bg-paper-200 text-ink-600 border border-paper-300',
                                };
                            @endphp
                            <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium uppercase tracking-wide {{ $badgeClass }}">
                                {{ $item->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('survei.sesi.show', $item->id) }}"
                                   class="rounded-sm border border-paper-300 px-2.5 py-1 text-xs font-medium text-ink-700 hover:border-ink-500">
                                    Detail
                                </a>

                                <form action="{{ route('survei.sesi.sinkron', $item->id) }}" method="POST"
                                      onsubmit="return confirm('Jalankan sinkronisasi data survei sesi #{{ $item->id }} ke modul Perencanaan (M2/M3)?');">
                                    @csrf
                                    <button type="submit"
                                            class="rounded-sm bg-sawah-600 px-3 py-1 text-xs font-medium text-paper-50 hover:bg-sawah-700">
                                        ⚡ Sinkronkan ke Perencanaan
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-600/60">
                            Belum ada sesi survei.
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
