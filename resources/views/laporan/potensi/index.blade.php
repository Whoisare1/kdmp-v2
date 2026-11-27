<x-layouts.app :title="$title" eyebrow="Laporan Potensi Desa">

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
            <h2 class="text-xl font-display font-semibold text-ink-900">Laporan Potensi Komoditas: {{ $desaAktif?->nama ?? 'Desa' }}</h2>
            <p class="text-xs text-paper-500">Pemetaan komoditas unggulan dan kapasitas panen petani di desa ini.</p>
        </div>

        <button onclick="window.print()" class="px-3 py-1.5 rounded-lg border border-paper-300 bg-white text-xs font-medium hover:bg-paper-50">
            Cetak / PDF
        </button>
    </div>

    {{-- Filter Panel (Kategori, Master, Item) --}}
    <div class="mb-5 bg-white p-4 rounded-xl border border-paper-200 shadow-sm print:hidden">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-[11px] font-medium text-paper-600 mb-1">Kategori</label>
                <select name="kategori_id" class="w-full rounded-lg border border-paper-300 bg-white p-1.5 text-xs text-ink-800 focus:outline-none">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $k)
                        <option value="{{ $k->id }}" @selected($kategoriId == $k->id)>{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-paper-600 mb-1">Master Komoditas</label>
                <select name="master_id" class="w-full rounded-lg border border-paper-300 bg-white p-1.5 text-xs text-ink-800 focus:outline-none">
                    <option value="">Semua Master</option>
                    @foreach($masterList as $m)
                        <option value="{{ $m->id }}" @selected($masterId == $m->id)>{{ $m->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-paper-600 mb-1">Item / Subjenis</label>
                <select name="item_id" class="w-full rounded-lg border border-paper-300 bg-white p-1.5 text-xs text-ink-800 focus:outline-none">
                    <option value="">Semua Item</option>
                    @foreach($itemList as $it)
                        <option value="{{ $it->id }}" @selected($itemId == $it->id)>{{ $it->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-1.5 bg-ink-900 text-white rounded-lg text-xs font-medium hover:bg-ink-800">
                    Filter Data
                </button>
                @if($kategoriId || $masterId || $itemId)
                    <a href="{{ route('laporan.potensi.index') }}" class="text-xs text-paper-500 hover:text-ink-900">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel Data --}}
    <div class="overflow-x-auto rounded-xl border border-paper-200 bg-white shadow-sm">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="border-b border-paper-200 bg-paper-50 font-mono text-[11px] uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-4 py-3">Nama Petani</th>
                    <th class="px-4 py-3">Item Komoditas</th>
                    <th class="px-4 py-3">Master</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3 text-right">Estimasi Panen</th>
                    <th class="px-4 py-3">Siklus</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @forelse($items as $row)
                    <tr class="hover:bg-paper-50/60 text-xs">
                        <td class="px-4 py-3 font-semibold text-ink-900">
                            {{ $row->petani?->nama_lengkap ?? '-' }}
                            <span class="block text-[10px] text-paper-400 font-normal">RT {{ $row->petani?->rt }}/RW {{ $row->petani?->rw }}</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-emerald-900">{{ $row->item?->nama ?? '-' }}</td>
                        <td class="px-4 py-3 text-paper-700">{{ $row->item?->masterKomoditas?->nama ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono bg-paper-100 text-paper-700">
                                {{ $row->item?->masterKomoditas?->kategori?->nama ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-ink-950">
                            {{ number_format($row->estimasi_volume_panen) }} {{ $row->satuan }}
                        </td>
                        <td class="px-4 py-3 text-paper-600">{{ $row->periode_panen }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-paper-400 text-xs">
                            Tidak ada data potensi komoditas yang sesuai filter untuk desa ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-layouts.app>
