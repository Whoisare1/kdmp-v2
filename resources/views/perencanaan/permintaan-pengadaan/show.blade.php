<x-layouts.app :title="$title" eyebrow="Perencanaan Pengadaan">
    <div class="mx-auto max-w-5xl space-y-6">
        @if (session('status'))
            <div class="rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">
                <span class="font-semibold text-sawah-700">Berhasil:</span> {{ session('status') }}
            </div>
        @endif

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Detail PR</p>
                <h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">{{ $item->kode_permintaan }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="rounded-full px-3 py-1 text-xs {{ $item->status_badge_class }}">{{ $item->status_label }}</span>
                @if (in_array($item->status, ['draft', 'diajukan']))
                    <form method="POST" action="{{ route('perencanaan.permintaan-pengadaan.tolak', $item->id_permintaan) }}" onsubmit="return confirm('Apakah Anda yakin ingin menolak permintaan pengadaan ini?');" class="inline">
                        @csrf
                        <button type="submit" class="rounded-sm border border-merah-300 bg-white px-3 py-2 text-sm font-medium text-merah-700 hover:bg-merah-50 shadow-sm transition">
                            ✕ Tolak PR
                        </button>
                    </form>
                    <form method="POST" action="{{ route('perencanaan.permintaan-pengadaan.setujui', $item->id_permintaan) }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-sm bg-sawah-600 px-4 py-2 text-sm font-medium text-white hover:bg-sawah-700 shadow-sm transition">
                            ✓ Setujui PR
                        </button>
                    </form>
                @elseif ($item->status === 'ditolak')
                    <form method="POST" action="{{ route('perencanaan.permintaan-pengadaan.draft', $item->id_permintaan) }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100 shadow-sm transition">
                            ↺ Kembalikan ke Draft
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="grid gap-4 rounded-sm border border-paper-300 bg-paper-50 p-5 md:grid-cols-3">
            <div><p class="text-xs text-ink-600/60">Tanggal pengajuan</p><p class="mt-1 font-medium">{{ $item->tgl_pengajuan?->format('d M Y') }}</p></div>
            <div><p class="text-xs text-ink-600/60">Unit usaha</p><p class="mt-1 font-medium">{{ $item->unitUsaha?->nama_unit_usaha ?? '—' }}</p></div>
            <div><p class="text-xs text-ink-600/60">Gudang tujuan</p><p class="mt-1 font-medium">{{ $item->gudang?->nama_gudang ?? '—' }}</p></div>
        </div>
        <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <table class="w-full text-left text-sm"><thead class="border-b border-paper-300 bg-paper-200/60"><tr><th class="px-4 py-3">Barang</th><th class="px-4 py-3">Jumlah</th><th class="px-4 py-3">Harga perkiraan</th><th class="px-4 py-3">Subtotal</th></tr></thead><tbody>
                @foreach ($item->detail as $detail)
                    <tr class="border-b border-paper-200 last:border-0"><td class="px-4 py-3">{{ $detail->barang?->nama_barang ?? '—' }}</td><td class="px-4 py-3">{{ number_format((float) $detail->jumlah_diminta, 2, ',', '.') }}</td><td class="px-4 py-3">Rp {{ number_format((float) $detail->harga_perkiraan, 2, ',', '.') }}</td><td class="px-4 py-3">Rp {{ number_format((float) $detail->subtotal, 2, ',', '.') }}</td></tr>
                @endforeach
            </tbody></table>
        </div>
        @if ($item->status === 'ditolak')
            <div class="rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
                <span class="font-semibold text-merah-900">Status: Ditolak</span> — Permintaan pengadaan ini telah ditolak dan tidak diteruskan ke antrean Order Pembelian.
                @if ($item->catatan)
                    <p class="mt-1 text-xs text-merah-700 font-mono">{{ $item->catatan }}</p>
                @endif
            </div>
        @endif

        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('perencanaan.permintaan-pengadaan.index') }}" class="inline-block rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">Kembali</a>
            @if (in_array($item->status, ['draft', 'diajukan']))
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('perencanaan.permintaan-pengadaan.tolak', $item->id_permintaan) }}" onsubmit="return confirm('Apakah Anda yakin ingin menolak permintaan pengadaan ini?');" class="inline">
                        @csrf
                        <button type="submit" class="rounded-sm border border-merah-300 bg-white px-4 py-2 text-sm font-medium text-merah-700 hover:bg-merah-50 shadow-sm transition">
                            ✕ Tolak Permintaan Ini
                        </button>
                    </form>
                    <form method="POST" action="{{ route('perencanaan.permintaan-pengadaan.setujui', $item->id_permintaan) }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-sm bg-sawah-600 px-4 py-2 text-sm font-medium text-white hover:bg-sawah-700 shadow-sm transition">
                            ✓ Setujui Permintaan Pengadaan Ini
                        </button>
                    </form>
                </div>
            @elseif ($item->status === 'ditolak')
                <form method="POST" action="{{ route('perencanaan.permintaan-pengadaan.draft', $item->id_permintaan) }}" class="inline">
                    @csrf
                    <button type="submit" class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100 shadow-sm transition">
                        ↺ Kembalikan Status ke Draft
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
