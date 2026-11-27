<x-layouts.app :title="$title" eyebrow="Laporan Produsen Komoditas">

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
            <h2 class="text-xl font-display font-semibold text-ink-900">Laporan Petani per Komoditas: {{ $desaAktif?->nama ?? 'Desa' }}</h2>
            <p class="text-xs text-paper-500">Daftar petani produsen warga desa ini yang menghasilkan setiap item komoditas.</p>
        </div>

        <button onclick="window.print()" class="px-3 py-1.5 rounded-lg border border-paper-300 bg-white text-xs font-medium hover:bg-paper-50">
            Cetak / PDF
        </button>
    </div>

    {{-- Filter Panel --}}
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
                    <a href="{{ route('laporan.petani-komoditas.index') }}" class="text-xs text-paper-500 hover:text-ink-900">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Daftar Kartu Komoditas Beserta Petani --}}
    <div class="space-y-4">
        @forelse($items as $item)
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm overflow-hidden">
                <div class="bg-paper-50 px-5 py-3 border-b border-paper-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-bold text-ink-900 uppercase tracking-wide">{{ $item->masterKomoditas?->kategori?->nama }}</span>
                            <span class="text-xs text-paper-400">&rarr;</span>
                            <span class="text-xs font-semibold text-paper-700">{{ $item->masterKomoditas?->nama }}</span>
                        </div>
                        <h3 class="text-base font-display font-bold text-emerald-800 mt-0.5">{{ $item->nama }}</h3>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold font-mono">
                            {{ $item->petani->count() }} Petani Produsen
                        </span>
                        <a href="{{ route('master.item-komoditas.show', $item) }}" class="text-blue-600 hover:underline">
                            Detail Komoditas &rarr;
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-xs">
                        <thead class="border-b border-paper-200 text-[10px] font-mono uppercase text-paper-500 bg-paper-50/50">
                            <tr>
                                <th class="px-5 py-2">Nama Petani</th>
                                <th class="px-5 py-2">Kontak</th>
                                <th class="px-5 py-2">Alamat Domisili</th>
                                <th class="px-5 py-2 text-right">Estimasi Panen</th>
                                <th class="px-5 py-2">Periode Panen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-100">
                            @foreach($item->petani as $p)
                                <tr class="hover:bg-paper-50/60">
                                    <td class="px-5 py-2.5 font-semibold text-ink-900">
                                        <a href="{{ route('pendataan.petani.show', $p) }}" class="text-blue-700 hover:underline">
                                            {{ $p->nama_lengkap }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-2.5 font-mono text-paper-600">{{ $p->nomor_hp ?: '-' }}</td>
                                    <td class="px-5 py-2.5 text-paper-600">
                                        {{ $p->alamat ?: '-' }} (RT {{ $p->rt ?: '00' }}/RW {{ $p->rw ?: '00' }})
                                    </td>
                                    <td class="px-5 py-2.5 text-right font-mono font-bold text-ink-950">
                                        {{ number_format($p->pivot->estimasi_volume_panen) }} {{ $p->pivot->satuan }}
                                    </td>
                                    <td class="px-5 py-2.5 text-paper-600">{{ $p->pivot->periode_panen }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="p-8 bg-white rounded-xl border border-paper-200 text-center text-paper-400 text-xs">
                Tidak ada komoditas dengan petani terdaftar yang cocok dengan filter untuk desa ini.
            </div>
        @endforelse
    </div>

</x-layouts.app>
