<x-layouts.app :title="$title" eyebrow="M6 Penjualan">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-ink-600">Transaksi penjualan lokal</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $item->kode_penjualan }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($hasReturnableItems)
                <a href="{{ route('penjualan.retur.create', ['id_penjualan' => $item->id_penjualan]) }}" class="rounded-sm bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Ajukan Retur</a>
            @elseif ($item->returs->isNotEmpty())
                <span class="rounded-sm bg-paper-200 px-3 py-2 text-xs font-semibold text-ink-600">Semua Barang Telah Diretur</span>
            @endif
            <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 px-3 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Kembali ke POS</a>
        </div>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Tanggal</p>
            <p class="mt-1 font-semibold text-rose-950">{{ $item->tanggal_transaksi?->format('d M Y') ?? '-' }}</p>
        </div>
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Pembeli</p>
            <p class="mt-1 font-semibold text-rose-950">{{ $item->pihak?->nama ?? '-' }}</p>
        </div>
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Metode</p>
            <p class="mt-1 font-semibold capitalize text-rose-950">{{ $item->metode_bayar }}</p>
        </div>
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Total</p>
            <p class="mt-1 font-semibold text-rose-950">Rp {{ number_format((float) $item->total_bayar, 0, ',', '.') }}</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-semibold text-ink-900">Barang terjual</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[650px] w-full text-sm">
                <thead class="bg-rose-50 text-left text-rose-950">
                    <tr>
                        <th class="px-4 py-3">Barang</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        <th class="px-4 py-3">Satuan</th>
                        <th class="px-4 py-3 text-right">Harga</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($item->detail as $line)
                        <tr class="border-t border-paper-200">
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $line->barang?->nama_barang ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $line->qty_dasar, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $line->barang?->satuanDasar?->kode_satuan ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format((float) $line->harga_satuan, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium">Rp {{ number_format((float) $line->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-ink-600">Tidak ada detail barang.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-paper-300 bg-paper-100">
                    <tr><th colspan="4" class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Rp {{ number_format((float) $item->total_bayar, 0, ',', '.') }}</th></tr>
                </tfoot>
            </table>
        </div>
    </section>

    @if ($item->returs && $item->returs->isNotEmpty())
        <section class="mt-6 overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <div class="border-b border-paper-300 px-4 py-3">
                <h2 class="font-semibold text-ink-900">Riwayat Retur Penjualan ({{ $item->returs->count() }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[650px] w-full text-sm">
                    <thead class="bg-rose-50 text-left text-rose-950">
                        <tr>
                            <th class="px-4 py-3">Kode Retur</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Penyelesaian</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Total Nilai</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-paper-200">
                        @foreach ($item->returs as $retur)
                            <tr class="hover:bg-rose-50/30">
                                <td class="px-4 py-3 font-semibold text-ink-900">
                                    <a href="{{ route('penjualan.retur.show', $retur) }}" class="text-rose-700 hover:underline">
                                        {{ $retur->kode_retur }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-ink-700">{{ $retur->tgl_retur?->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 py-3 capitalize text-ink-700">{{ str_replace('_', ' ', $retur->jenis_penyelesaian) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-sm px-2 py-0.5 text-xs font-semibold {{ $retur->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ ucfirst($retur->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-rose-700">Rp {{ number_format((float) $retur->total_nilai, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('penjualan.retur.show', $retur) }}" class="rounded-sm border border-paper-300 px-2.5 py-1 text-xs font-medium text-ink-700 hover:bg-paper-100">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</x-layouts.app>
