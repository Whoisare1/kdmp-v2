<x-layouts.app :title="$title" eyebrow="M5 Pembelian">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full gap-2 sm:max-w-md">
            <input name="q" value="{{ request('q') }}" placeholder="Cari kode atau supplier..." class="min-w-0 flex-1 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-emerald-500">Cari</button>
        </form>
        <a href="{{ route("$routeBase.create") }}" class="inline-flex items-center justify-center rounded-sm bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ Tambah Pembelian</a>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-sm">
                <thead class="bg-paper-100 text-left text-ink-700">
                    <tr>
                        <th class="px-4 py-3">Kode</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Supplier</th><th class="px-4 py-3">Pembayaran</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-t border-paper-200 hover:bg-paper-100/70">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $item->kode_pembelian }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-700">{{ $item->tanggal_transaksi?->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $item->pihak?->nama_pihak ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium capitalize text-ink-900">{{ $item->jenis_pembayaran }}</span>
                                @if ($item->kasBank)
                                    <span class="block text-xs text-ink-500">{{ $item->kasBank->nama }}</span>
                                @elseif ($item->jenis_pembayaran === 'kredit' && $item->tgl_jatuh_tempo)
                                    <span class="block text-xs text-amber-700">Tempo: {{ $item->tgl_jatuh_tempo->format('d/m/y') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($item->total_pembelian, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 capitalize">{{ $item->status }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right space-x-2">
                                <a href="{{ route("$routeBase.show", $item) }}" class="font-medium text-emerald-700 hover:text-emerald-800">Lihat</a>
                            
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-ink-600">Belum ada pembelian. Gunakan tombol Tambah Pembelian untuk membuat transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>