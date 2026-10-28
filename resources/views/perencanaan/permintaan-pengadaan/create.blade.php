<x-layouts.app :title="$title" eyebrow="Modul M3 — Perencanaan Pengadaan">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="rounded-sm border border-merah-200 bg-merah-50 p-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-wide text-merah-700">Draft PR Dari Hasil Kalkulasi</p>
                    <h2 class="mt-1 font-display text-2xl font-semibold text-ink-900">PR - Pengadaan Defisit</h2>
                </div>
                @if ($hasil)
                    <span class="rounded-full bg-merah-100 px-3 py-1 text-xs font-medium uppercase text-merah-700">
                        {{ $hasil->status_surplus_defisit }}
                    </span>
                @endif
            </div>
        </div>

        @if ($hasil)
            <div class="grid gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 md:grid-cols-3">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">Komoditas</p>
                    <p class="mt-1 text-base font-semibold text-ink-900">{{ $hasil->komoditas?->nama ?? '—' }}</p>
                </div>
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">Periode</p>
                    <p class="mt-1 text-base font-semibold text-ink-900">{{ date('F', mktime(0,0,0,$hasil->bulan,1)) }} {{ $hasil->tahun }}</p>
                </div>
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">Kebutuhan yang kurang</p>
                    <p class="mt-1 text-base font-semibold text-merah-600">{{ number_format(abs((float) $hasil->selisih), 2, ',', '.') }}</p>
                </div>
            </div>
        @endif

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-6">
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-ink-700">Supplier / Pihak</label>
                    <input type="text" value="Toko Sembako Sumber Rejeki" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Tanggal Pengajuan</label>
                    <input type="date" value="{{ $tanggalPengajuan }}" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Barang / Item</label>
                    <input type="text" value="{{ $barang?->nama_barang ?? ($hasil?->komoditas?->nama ?? '—') }}" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Jumlah Diminta</label>
                    <input type="text" value="{{ number_format($jumlahDiminta, 2, ',', '.') }}" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Harga Perkiraan</label>
                    <input type="text" value="Rp 0" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-700">Subtotal</label>
                    <input type="text" value="Rp 0" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none" readonly>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between gap-4 border-t border-paper-300 pt-4">
                <a href="{{ route('perencanaan.neraca-komoditas.index', ['tahun' => $hasil?->tahun ?? now()->year, 'bulan' => $hasil?->bulan]) }}" class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                    ← Kembali ke Neraca
                </a>
                <button type="button" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                    Simpan Draft PR
                </button>
            </div>
        </div>
    </div>
</x-layouts.app>
