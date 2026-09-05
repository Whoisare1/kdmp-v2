<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <div class="mb-5 flex items-center justify-between gap-4">
        <form method="GET" class="flex items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari barang / gudang..."
                class="w-72 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >
            <button class="rounded-sm border border-paper-300 px-3 py-2 text-sm text-ink-700 hover:border-merah-400">
                Cari
            </button>
        </form>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                    <th class="px-4 py-3 font-medium">Gudang</th>
                    <th class="px-4 py-3 font-medium">Barang</th>
                    <th class="px-4 py-3 text-right font-medium">Qty Tersedia</th>
                    <th class="px-4 py-3 text-right font-medium">Qty Dipesan</th>
                    <th class="px-4 py-3 text-right font-medium">HPP Rata2</th>
                    <th class="px-4 py-3 text-right font-medium">Nilai Persediaan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        <td class="px-4 py-3 text-ink-800">{{ $item->gudang->nama_gudang ?? '-' }}</td>
                        <td class="px-4 py-3 text-ink-800">{{ $item->barang->nama_barang ?? '-' }}</td>
                        <td class="px-4 py-3 text-right text-ink-800">
                            {{ rtrim(rtrim(number_format((float) $item->qty_on_hand, 4, ',', '.'), '0'), ',') }}
                            {{ $item->barang->satuanDasar->kode_satuan ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-right text-ink-800">
                            {{ rtrim(rtrim(number_format((float) $item->qty_reserved, 4, ',', '.'), '0'), ',') }}
                            {{ $item->barang->satuanDasar->kode_satuan ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-right text-ink-800">Rp {{ number_format((float) $item->hpp_rata2, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-ink-800">Rp {{ number_format((float) $item->nilai_persediaan, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600/60">
                            Belum ada stok. Jalankan penerimaan barang terlebih dahulu.
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
