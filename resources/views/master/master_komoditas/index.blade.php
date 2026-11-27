<x-layouts.app :title="$title" eyebrow="Tingkat 2 Hierarki Komoditas">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="kategori_id" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Kategori</option>
                @foreach($kategoriList as $kat)
                    <option value="{{ $kat->id }}" @selected($kategoriId == $kat->id)>{{ $kat->nama }}</option>
                @endforeach
            </select>
            <input
                type="text"
                name="q"
                value="{{ $search ?? request('q') }}"
                placeholder="Cari master komoditas..."
                class="w-64 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >
            <button type="submit" class="rounded-sm border border-paper-300 px-3 py-2 text-sm text-ink-700 hover:border-merah-400">
                Filter
            </button>
            @if($kategoriId || ($search ?? request('q')))
                <a href="{{ route('master.master-komoditas.index') }}" class="text-xs text-ink-600/70 hover:text-ink-900 px-2 py-2">Reset</a>
            @endif
        </form>

        <a href="{{ route('master.master-komoditas.create') }}" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
            + Tambah Master
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-sm border border-sawah-200 bg-sawah-50 p-3 text-xs text-sawah-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-sm border border-merah-200 bg-merah-50 p-3 text-xs text-merah-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">Kode</th>
                        <th class="px-4 py-3 font-medium">Master Komoditas</th>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 text-center font-medium">Subjenis / Item</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse($items as $m)
                        <tr class="hover:bg-paper-100/70">
                            <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $m->kode }}</td>
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $m->nama }}</td>
                            <td class="px-4 py-3 text-xs text-ink-700">
                                <span class="inline-flex items-center rounded-sm bg-paper-200/80 px-2 py-0.5 font-mono text-xs text-ink-800">
                                    {{ $m->kategori?->nama ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('master.item-komoditas.index', ['master_id' => $m->id]) }}" class="inline-flex items-center rounded-sm bg-paper-200 px-2 py-0.5 font-mono text-xs text-ink-800 hover:bg-paper-300">
                                    {{ $m->items_count }} Item
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-sm px-2 py-0.5 font-mono text-xs {{ $m->is_active ? 'bg-sawah-100 text-sawah-800' : 'bg-paper-200 text-ink-600' }}">
                                    {{ $m->is_active ? 'Aktif' : 'Non-aktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('master.master-komoditas.edit', $m) }}" class="text-xs font-medium text-ink-700 hover:text-merah-600 mr-2">Ubah</a>
                                <form action="{{ route('master.master-komoditas.destroy', $m) }}" method="POST" class="inline" onsubmit="return confirm('Hapus master komoditas ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-medium text-merah-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600/60">
                                Belum ada master komoditas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($items->hasPages())
        <div class="mt-4">
            {{ $items->links() }}
        </div>
    @endif
</x-layouts.app>
