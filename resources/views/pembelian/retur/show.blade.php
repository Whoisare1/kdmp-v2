<x-layouts.app :title="$title" eyebrow="Detail Retur Pembelian">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">{{ session('error') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-ink-600">Retur Pembelian</p>
            <h1 class="font-display text-2xl font-semibold text-ink-900">{{ $item->kode_retur }}</h1>
        </div>
        <span class="rounded-sm bg-paper-200 px-3 py-1 text-sm font-medium text-ink-800">{{ ucfirst($item->status) }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-5">
            <section class="rounded-sm border border-paper-300 bg-paper-50 p-5">
                <h2 class="mb-4 font-semibold text-ink-900">Informasi Retur</h2>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-ink-600">Pembelian Asal</dt>
                        <dd class="mt-1 font-medium text-ink-900">
                            <a href="{{ route('pembelian.pembelian.show', $item->pembelian) }}" class="text-emerald-700 hover:underline">{{ $item->pembelian?->kode_pembelian ?? '-' }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-ink-600">Pemasok</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $item->pembelian?->pihak?->nama_pihak ?? $item->pembelian?->pihak?->nama ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-ink-600">Tanggal Retur</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $item->tgl_retur?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-ink-600">Penyelesaian</dt>
                        <dd class="mt-1 font-medium capitalize text-ink-900">{{ str_replace('_', ' ', $item->jenis_penyelesaian) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-ink-600">Nilai Retur</dt>
                        <dd class="mt-1 font-semibold text-ink-900">Rp {{ number_format((float) $item->total_nilai, 2, ',', '.') }}</dd>
                    </div>
                    @if ($item->diajukan_jurnal_pada)
                        <div>
                            <dt class="text-sm text-ink-600">Pengajuan Jurnal</dt>
                            <dd class="mt-1 font-medium text-ink-900">{{ $item->diajukan_jurnal_pada->format('d M Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($item->alasan)
                    <div class="mt-4 border-t border-paper-200 pt-3">
                        <h3 class="text-sm font-medium text-ink-600">Alasan</h3>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink-900">{{ $item->alasan }}</p>
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
                <div class="border-b border-paper-300 px-5 py-3">
                    <h2 class="font-semibold text-ink-900">Barang Diretur</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-paper-100 text-left text-ink-700">
                            <tr>
                                <th class="px-4 py-3">Barang</th>
                                <th class="px-4 py-3 text-right">Jumlah</th>
                                <th class="px-4 py-3 text-right">HPP</th>
                                <th class="px-4 py-3 text-right">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->detail as $line)
                                <tr class="border-t border-paper-200">
                                    <td class="px-4 py-3">{{ $line->barang?->nama_barang ?? 'Barang' }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $line->qty_dasar, 2, ',', '.') }} {{ $line->barang?->satuanDasar?->kode_satuan ?? '' }}</td>
                                    <td class="px-4 py-3 text-right">Rp {{ number_format((float) $line->hpp_rata2, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">Rp {{ number_format((float) $line->nilai, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="h-fit rounded-sm border border-paper-300 bg-paper-50 p-5">
            <h2 class="mb-3 font-semibold text-ink-900">Aksi</h2>
            @if (($item->status === 'diajukan' || ($item->status === 'selesai' && $item->status_posting !== 'T' && $item->jenis_penyelesaian !== 'ganti_barang')) && ! $item->id_jurnal)
                <p class="mb-3 text-sm text-ink-600">Periksa rincian retur, lalu ajukan jurnal. Sistem akan memproses retur dan membuat jurnal yang sesuai.</p>
                <form method="POST" action="{{ route('pembelian.retur.ajukan-akuntansi', $item) }}" onsubmit="return confirm('Proses retur dan posting jurnal sekarang?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="w-full rounded-sm bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Ajukan Jurnal</button>
                </form>
            @else
                @if ($item->id_jurnal)
                    <div class="rounded-sm border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                        Jurnal retur sudah diposting otomatis.
                    </div>
                @endif
                @if (in_array($item->status, ['disetujui', 'selesai'], true))
                    <a href="{{ route('pembelian.retur.invoice', $item) }}" class="mt-2 block rounded-sm border border-paper-300 px-4 py-2 text-center text-sm font-medium text-ink-700 hover:bg-paper-100">Invoice Retur</a>
                @endif
            @endif
            <a href="{{ route('pembelian.retur.index') }}" class="mt-3 block rounded-sm border border-paper-300 px-4 py-2 text-center text-sm font-medium text-ink-700 hover:bg-paper-100">Kembali ke Retur</a>
        </aside>
    </div>
</x-layouts.app>
