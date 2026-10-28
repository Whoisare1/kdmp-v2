<x-layouts.app :title="$title" eyebrow="M6 Penjualan">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-ink-600">Transaksi penjualan dan aktivitas kasir</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Kasir / POS</h1>
        </div>
        <a href="{{ route("$routeBase.create") }}" class="inline-flex items-center justify-center gap-2 rounded-sm bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
            <span aria-hidden="true">＋</span> Buka Kasir
        </a>
    </div>

    <section class="mb-5 border-y border-rose-200 bg-rose-50/70 px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-sm bg-rose-600 text-lg text-white" aria-hidden="true">🧾</span>
                <div>
                    <h2 class="text-sm font-semibold text-rose-950">Aktivitas Penjualan</h2>
                    <p class="text-xs text-rose-800">Riwayat transaksi terbaru</p>
                </div>
            </div>
            <a href="{{ route('penjualan.retur.index') }}" class="text-sm font-semibold text-rose-700 hover:text-rose-900">Lihat retur →</a>
        </div>
    </section>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full gap-2 sm:max-w-md">
            <input name="q" value="{{ request('q') }}" placeholder="Cari kode transaksi atau pembeli" class="min-w-0 flex-1 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-rose-400">Cari</button>
        </form>
        <span class="text-sm text-ink-600">{{ $items->total() }} transaksi</span>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-sm">
                <thead class="bg-rose-50 text-left text-rose-950">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Kode</th>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Pembeli</th>
                        <th class="px-4 py-3 font-semibold">Metode</th>
                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-t border-paper-200 hover:bg-rose-50/40">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-ink-900">{{ $item->kode_penjualan }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-700">{{ $item->tanggal_transaksi?->format('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $item->pihak?->nama_pihak ?? 'Pembeli umum' }}</td>
                            <td class="px-4 py-3 capitalize">{{ $item->metode_bayar }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium">Rp {{ number_format((float) $item->total_bayar, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-sm px-2 py-1 text-xs font-medium {{ $item->status === 'selesai' ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right"><a href="{{ route("$routeBase.show", $item) }}" class="font-semibold text-rose-700 hover:text-rose-900">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-ink-600">Belum ada transaksi penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>