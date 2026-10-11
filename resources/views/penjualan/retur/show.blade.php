<x-layouts.app :title="$title" eyebrow="Retur Penjualan">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-ink-600">Dokumen Retur Penjualan</p>
            <div class="mt-1 flex items-center gap-3">
                <h1 class="text-2xl font-semibold text-ink-900">{{ $item->kode_retur }}</h1>
                <span class="inline-flex rounded-sm px-2.5 py-1 text-xs font-semibold {{ $item->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ ucfirst($item->status) }}
                </span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($item->status === 'diajukan')
                <form method="POST" action="{{ route("$routeBase.update", $item) }}" onsubmit="return confirm('Selesaikan retur ini? Stok barang akan kembali bertambah pada kasir/POS.')">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="rounded-sm bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        ✓ Selesaikan Retur
                    </button>
                </form>
            @endif
            <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 px-3 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Daftar Retur</a>
            @if ($item->penjualan)
                <a href="{{ route('penjualan.penjualan.show', $item->penjualan) }}" class="rounded-sm border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-50">Lihat Nota Penjualan</a>
            @endif
        </div>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Tanggal Retur</p>
            <p class="mt-1 font-semibold text-rose-950">{{ $item->tgl_retur?->format('d M Y') ?? '—' }}</p>
        </div>
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Transaksi Penjualan</p>
            <p class="mt-1 font-semibold text-rose-950">{{ $item->penjualan?->kode_penjualan ?? '—' }}</p>
        </div>
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Penyelesaian</p>
            <p class="mt-1 font-semibold capitalize text-rose-950">{{ str_replace('_', ' ', $item->jenis_penyelesaian) }}</p>
        </div>
        <div class="border-l-4 border-rose-600 bg-rose-50 px-4 py-3">
            <p class="text-xs font-medium uppercase text-rose-800">Total Nilai Retur</p>
            <p class="mt-1 font-semibold text-rose-950">Rp {{ number_format((float) $item->total_nilai, 0, ',', '.') }}</p>
        </div>
    </div>

    @if ($item->alasan)
        <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="text-xs font-medium uppercase text-ink-600">Alasan Pengembalian</p>
            <p class="mt-1 text-sm text-ink-900">{{ $item->alasan }}</p>
        </div>
    @endif

    <section class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-semibold text-ink-900">Rincian Barang yang Dikembalikan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[650px] w-full text-sm">
                <thead class="bg-rose-50 text-left text-rose-950">
                    <tr>
                        <th class="px-4 py-3">Barang</th>
                        <th class="px-4 py-3 text-right">Qty Diretur</th>
                        <th class="px-4 py-3">Satuan</th>
                        <th class="px-4 py-3 text-right">Harga Satuan</th>
                        <th class="px-4 py-3 text-right">Subtotal Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($item->detail as $line)
                        <tr class="border-t border-paper-200">
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $line->barang?->nama_barang ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-rose-700">{{ number_format((float) $line->qty_dasar, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $line->barang?->satuanDasar?->kode_satuan ?? 'unit' }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format((float) $line->harga_satuan, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium">Rp {{ number_format((float) $line->nilai, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-ink-600">Tidak ada detail barang retur.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-paper-300 bg-paper-100">
                    <tr>
                        <th colspan="4" class="px-4 py-3 text-right">Total Nilai Retur</th>
                        <th class="px-4 py-3 text-right text-rose-700">Rp {{ number_format((float) $item->total_nilai, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</x-layouts.app>

