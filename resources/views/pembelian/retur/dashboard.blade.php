<x-layouts.app :title="$title" eyebrow="M5 Pembelian / Retur">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
                <p class="text-sm text-ink-600">Pantau barang pembelian yang masih dapat diretur dan catatan retur sebelumnya.</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Retur Pembelian</h1>
        </div>
        <a href="{{ route("$routeBase.create") }}" class="inline-flex items-center justify-center rounded-sm bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Ajukan Retur</a>
    </div>

    <section class="mb-8">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="font-semibold text-ink-900">Barang dari Pembelian</h2>
                       <p class="mt-1 text-sm text-ink-600">Saldo qty dan catatan retur dikelompokkan per pembelian dan barang.</p>
            </div>
            <span class="text-xs font-medium text-ink-600">{{ $purchases->sum(fn ($purchase) => $purchase->returItems->count()) }} jenis barang</span>
        </div>

        <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <div class="overflow-x-auto">
                <table class="min-w-[1020px] w-full text-sm">
                    <thead class="bg-emerald-50 text-left text-emerald-950">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Pembelian</th>
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Supplier</th>
                            <th class="px-4 py-3 font-semibold">Barang</th>
                            <th class="px-4 py-3 text-right font-semibold">Dibeli</th>
                            <th class="px-4 py-3 text-right font-semibold">Diajukan</th>
                            <th class="px-4 py-3 text-right font-semibold">Sudah Diretur</th>
                            <th class="px-4 py-3 text-right font-semibold">Sisa</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hasPurchaseLines = false; @endphp
                        @foreach ($purchases as $purchase)
                            @foreach ($purchase->returItems as $line)
                                @php $hasPurchaseLines = true; @endphp
                                <tr class="border-t border-paper-200 hover:bg-emerald-50/40">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-ink-900">{{ $purchase->kode_pembelian }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-ink-700">{{ $purchase->tanggal_transaksi?->format('d M Y') ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $purchase->pihak?->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $line->barang?->nama_barang ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $line->qty_dasar, 2, ',', '.') }} {{ $line->barang?->satuanDasar?->kode_satuan }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $line->qty_diajukan, 2, ',', '.') }} {{ $line->barang?->satuanDasar?->kode_satuan }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $line->qty_diretur, 2, ',', '.') }} {{ $line->barang?->satuanDasar?->kode_satuan }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $line->qty_sisa_retur > 0 ? 'text-emerald-800' : 'text-ink-500' }}">{{ number_format((float) $line->qty_sisa_retur, 2, ',', '.') }} {{ $line->barang?->satuanDasar?->kode_satuan }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($line->qty_sisa_retur > 0)
                                            <a href="{{ route("$routeBase.create", ['id_pembelian' => $purchase->id_pembelian]) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">Retur</a>
                                        @else
                                            <span class="text-xs text-ink-500">Habis diretur</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        @unless ($hasPurchaseLines)
                            <tr><td colspan="9" class="px-4 py-10 text-center text-ink-600">Belum ada barang dari pembelian yang sudah diterima.</td></tr>
                        @endunless
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section>
        <div class="mb-3">
            <h2 class="font-semibold text-ink-900">Catatan Retur</h2>
            <p class="mt-1 text-sm text-ink-600">Riwayat barang yang sudah diajukan untuk retur.</p>
        </div>
        <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <div class="overflow-x-auto">
                <table class="min-w-[850px] w-full text-sm">
                    <thead class="bg-paper-100 text-left text-ink-700">
                        <tr>
                            <th class="px-4 py-3">Kode Retur</th>
                            <th class="px-4 py-3">Pembelian</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Barang Diretur</th>
                            <th class="px-4 py-3">Penyelesaian</th>
                            <th class="px-4 py-3 text-right">Nilai</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $return)
                            <tr class="border-t border-paper-200 align-top hover:bg-paper-100/60">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $return->kode_retur }}</td>
                                <td class="px-4 py-3">{{ $return->pembelian?->kode_pembelian ?? '-' }}<span class="mt-0.5 block text-xs text-ink-600">{{ $return->pembelian?->pihak?->nama ?? '' }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $return->tgl_retur?->format('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <ul class="space-y-1">
                                        @foreach ($return->detail as $returnLine)
                                            <li>{{ $returnLine->barang?->nama_barang ?? 'Barang' }} <span class="text-ink-600">x {{ number_format((float) $returnLine->qty_dasar, 2, ',', '.') }} {{ $returnLine->barang?->satuanDasar?->kode_satuan }}</span></li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 capitalize">{{ str_replace('_', ' ', $return->jenis_penyelesaian) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format((float) $return->total_nilai, 2, ',', '.') }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-sm px-2 py-1 text-xs font-medium {{ $return->status === 'selesai' ? 'bg-emerald-50 text-emerald-800' : ($return->status === 'disetujui' ? 'bg-cyan-50 text-cyan-800' : 'bg-amber-50 text-amber-800') }}">{{ ucfirst($return->status) }}</span></td>
                                <td class="px-4 py-3">
                                    @if ($return->status === 'diajukan')
                                        <form method="POST" action="{{ route('pembelian.retur.approve', $return) }}" class="flex min-w-28 flex-col gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-left text-xs font-semibold text-emerald-700 hover:text-emerald-900">Tandai selesai</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-ink-500">Tercatat</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-ink-600">Belum ada catatan retur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $returns->links() }}</div>
    </section>
</x-layouts.app>
