<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4 flex items-center justify-between gap-4">
        <a href="{{ route('gudang.kartu-stok.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Kartu Stok
        </a>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-2">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Mutasi Kartu Stok</p>
            <p class="mt-2 font-display text-xl font-semibold text-ink-900">#{{ $item->id_kartu }} — {{ $item->barang->nama_barang ?? '-' }}</p>
            <p class="mt-1 text-sm text-ink-600">
                Tanggal: <strong>{{ $item->tanggal?->format('d F Y') }}</strong>
                @if ($item->created_at)
                    · Dicatat pada: {{ $item->created_at->format('H:i') }} WIB
                @endif
            </p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-ink-600">Jenis Mutasi</dt>
                <dd class="text-right">
                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ in_array($item->jenis_mutasi, ['IN', 'ADJ_IN']) ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                        {{ $item->jenis_mutasi === 'IN' ? 'Barang Masuk' : ($item->jenis_mutasi === 'OUT' ? 'Barang Keluar' : ($item->jenis_mutasi === 'ADJ_IN' ? 'Penyesuaian Masuk' : 'Penyesuaian Keluar')) }}
                    </span>
                </dd>
                <dt class="text-ink-600">Petugas Pencatat</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->petugas->nama ?? 'Sistem / Operator' }}</dd>
                <dt class="text-ink-600">Gudang Penyimpanan</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->gudang->nama_gudang ?? '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="text-xs text-ink-600">Kuantitas Mutasi</p>
            <p class="mt-1 font-display text-xl font-semibold {{ in_array($item->jenis_mutasi, ['IN', 'ADJ_IN']) ? 'text-emerald-700' : 'text-red-700' }}">
                {{ in_array($item->jenis_mutasi, ['IN', 'ADJ_IN']) ? '+' : '-' }}{{ rtrim(rtrim(number_format((float) ($item->qty_masuk > 0 ? $item->qty_masuk : $item->qty_keluar), 4, ',', '.'), '0'), ',') }}
                {{ $item->barang->satuanDasar->kode_satuan ?? '' }}
            </p>
            <p class="mt-2 text-xs text-ink-500">Harga Satuan: Rp {{ number_format((float) $item->harga_satuan, 2, ',', '.') }}</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="text-xs text-ink-600">Total Nilai Rupiah Mutasi</p>
            <p class="mt-1 font-display text-xl font-semibold text-ink-900">
                Rp {{ number_format((float) $item->nilai_mutasi, 2, ',', '.') }}
            </p>
            <p class="mt-2 text-xs text-ink-500">Angka ini yang dicatat pada Jurnal Akuntansi</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="text-xs text-ink-600">Saldo Akhir Pasca Mutasi</p>
            <p class="mt-1 font-display text-xl font-semibold text-ink-900">
                {{ rtrim(rtrim(number_format((float) $item->saldo_qty, 4, ',', '.'), '0'), ',') }} {{ $item->barang->satuanDasar->kode_satuan ?? '' }}
            </p>
            <p class="mt-2 text-xs text-ink-700">
                HPP Moving Avg: <strong>Rp {{ number_format((float) $item->hpp_rata2_setelah, 2, ',', '.') }}</strong>
            </p>
        </div>
    </div>

    <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
        <h3 class="font-display text-base font-semibold text-ink-900">Dokumen Sumber & Referensi Transaksi</h3>
        <p class="mt-1 text-xs text-ink-600">
            Jejak audit (*audit trail*) yang menghubungkan pergerakan fisik barang dengan dokumen operasional terkait.
        </p>

        <div class="mt-4 rounded-sm border border-paper-200 bg-paper-100/60 p-4">
            <dl class="grid gap-3 text-sm md:grid-cols-2">
                <div>
                    <dt class="text-xs text-ink-600">Tipe Referensi</dt>
                    <dd class="mt-0.5 font-mono text-sm font-semibold text-ink-900">{{ $item->ref_tipe }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-600">ID Dokumen Sumber</dt>
                    <dd class="mt-0.5 font-mono text-sm font-semibold text-ink-900">#{{ $item->ref_id ?? '-' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-xs text-ink-600">Keterangan Transaksi Terinci</dt>
                    <dd class="mt-1 font-medium text-ink-900">
                        {{ $item->deskripsi_referensi }}
                    </dd>
                </div>
            </dl>

            @if ($item->url_dokumen_sumber)
                <div class="mt-4 border-t border-paper-200 pt-3">
                    <a href="{{ $item->url_dokumen_sumber }}" class="inline-flex items-center gap-1.5 rounded-sm bg-merah-500 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-merah-600">
                        Buka Dokumen Transaksi Asli &rarr;
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

