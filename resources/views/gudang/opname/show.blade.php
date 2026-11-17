<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4 flex items-center justify-between gap-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
        <a href="{{ route('gudang.opname.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            Kembali ke Daftar Opname
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="mb-4 rounded-sm border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-sm border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-5 grid gap-4 md:grid-cols-2">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Kode Opname</p>
            <p class="mt-2 font-display text-xl font-semibold text-ink-900">{{ $item->kode_opname }}</p>
            <p class="mt-1 text-sm text-ink-600">{{ $item->tanggal?->format('d M Y') }}</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-ink-600">Gudang</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->gudang->nama_gudang ?? '-' }}</dd>
                <dt class="text-ink-600">Status</dt>
                <dd class="text-right">
                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase {{ $item->status === 'diposting' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $item->status }}
                    </span>
                </dd>
                @if ($item->approved_by)
                    <dt class="text-ink-600">Disetujui Oleh</dt>
                    <dd class="text-right font-medium text-ink-800">{{ $item->approver->nama ?? 'Pengguna #' . $item->approved_by }}</dd>
                @endif
            </dl>
        </div>
    </div>

    @if ($item->status === 'draft')
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-sm border border-amber-200 bg-amber-50/70 p-4">
            <div>
                <h3 class="text-sm font-semibold text-amber-900">Konfirmasi & Persetujuan Manajer</h3>
                <p class="mt-0.5 text-xs text-amber-800">
                    Status saat ini masih <strong>Draft</strong>. Stok fisik gudang dan kartu stok belum disesuaikan. Periksa rincian selisih di bawah ini, lalu klik tombol setujui untuk memposting penyesuaian.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('gudang.opname.destroy', $item->id_opname) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dan menghapus draft opname ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-sm border border-red-300 bg-white px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">
                        Hapus Draft
                    </button>
                </form>
                <form action="{{ route('gudang.opname.approve', $item->id_opname) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui dan memposting penyesuaian stok ini?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-sm bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                        ✓ Setujui & Posting Penyesuaian
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-display text-base font-semibold text-ink-900">Hasil Perhitungan Stok</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">No</th>
                        <th class="px-4 py-3 font-medium">Barang</th>
                        <th class="px-4 py-3 text-right font-medium">Qty Sistem</th>
                        <th class="px-4 py-3 text-right font-medium">Qty Fisik</th>
                        <th class="px-4 py-3 text-right font-medium">Selisih</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai Selisih</th>
                        <th class="px-4 py-3 font-medium">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($item->detail as $detail)
                        <tr class="border-b border-paper-200 last:border-0">
                            <td class="px-4 py-3 text-ink-600">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-ink-800">{{ $detail->barang->nama_barang ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ rtrim(rtrim(number_format((float) $detail->qty_sistem, 4, ',', '.'), '0'), ',') }}
                                {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ rtrim(rtrim(number_format((float) $detail->qty_fisik, 4, ',', '.'), '0'), ',') }}
                                {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium {{ (float) $detail->selisih < 0 ? 'text-red-600' : ((float) $detail->selisih > 0 ? 'text-emerald-600' : 'text-ink-800') }}">
                                {{ (float) $detail->selisih > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $detail->selisih, 4, ',', '.'), '0'), ',') }}
                                {{ $detail->barang->satuanDasar->kode_satuan ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                Rp {{ number_format((float) $detail->nilai_selisih, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-ink-700">{{ $detail->keterangan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-600/60">Belum ada detail opname.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($item->antreanJurnal->isNotEmpty())
        <div class="mt-5 overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <div class="border-b border-paper-300 px-4 py-3">
                <h2 class="font-display text-base font-semibold text-ink-900">Status Jurnal Keuangan</h2>
            </div>
            <div class="divide-y divide-paper-200">
                @foreach ($item->antreanJurnal as $antrean)
                    <dl class="grid gap-3 p-4 text-sm md:grid-cols-4">
                        <div>
                            <dt class="text-ink-600">Jenis penyesuaian</dt>
                            <dd class="mt-1 font-medium text-ink-900">{{ str_replace('_', ' ', $antrean->jenis_transaksi) }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-600">Unit usaha</dt>
                            <dd class="mt-1 font-medium text-ink-900">{{ $antrean->unitUsaha->nama_unit_usaha ?? 'Unit #' . $antrean->id_unit_usaha }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-600">Status</dt>
                            <dd class="mt-1 font-medium text-ink-900">{{ $antrean->status }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-600">No. jurnal</dt>
                            <dd class="mt-1 font-medium text-ink-900">{{ $antrean->no_jurnal ?? 'Belum tersedia' }}</dd>
                        </div>
                        @if ($antrean->pesan_error)
                            <div class="md:col-span-4">
                                <dt class="text-ink-600">Keterangan dari Keuangan</dt>
                                <dd class="mt-1 text-merah-600">{{ $antrean->pesan_error }}</dd>
                            </div>
                        @endif
                    </dl>
                @endforeach
            </div>
        </div>
    @endif
</x-layouts.app>
