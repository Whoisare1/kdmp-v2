<x-layouts.app :title="$title" eyebrow="M5 Pembelian">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif

    @if (isset($hasModalAwal) && !$hasModalAwal)
        <div class="mb-5 rounded-sm border border-amber-300 bg-amber-50 p-4 shadow-xs">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-display text-sm font-semibold text-amber-900">Koperasi Belum Memiliki Modal Kas Awal</h4>
                    <p class="mt-1 text-xs leading-relaxed text-amber-800">
                        Koperasi baru wajib mencatat transaksi <strong>Setoran Simpanan Pokok</strong> anggota atau <strong>Saldo Awal Kas</strong> terlebih dahulu sebelum diizinkan membuat Order Pembelian.
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <a href="{{ route('keuangan.kas-transaksi.create') }}" class="inline-flex items-center gap-1.5 rounded-sm bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700 transition">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Catat Simpanan Pokok (Kas Masuk)
                        </a>
                        <a href="{{ route('akuntansi.saldo-awal.index') }}" class="inline-flex items-center gap-1.5 rounded-sm border border-amber-400 bg-white px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-50 transition">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            Input Saldo Awal Akuntansi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full gap-2 sm:max-w-md">
            <input name="q" value="{{ request('q') }}" placeholder="Cari kode atau supplier..." class="min-w-0 flex-1 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-emerald-500">Cari</button>
        </form>
        @if (isset($hasModalAwal) && !$hasModalAwal)
            <button type="button" disabled title="Wajib catat Setoran Simpanan Pokok atau Saldo Awal Kas terlebih dahulu"
                    class="inline-flex items-center justify-center rounded-sm bg-paper-300 px-4 py-2 text-sm font-medium text-paper-500 cursor-not-allowed">
                🔒 + Tambah Pembelian (Terkunci)
            </button>
        @else
            <a href="{{ route("$routeBase.create") }}" class="inline-flex items-center justify-center rounded-sm bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ Tambah Pembelian</a>
        @endif
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