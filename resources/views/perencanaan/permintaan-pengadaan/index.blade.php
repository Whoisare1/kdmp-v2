<x-layouts.app :title="$title" eyebrow="Perencanaan Pengadaan">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div><p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Perencanaan Pengadaan</p><h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">{{ $title }}</h1></div>
        </div>
        <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60"><tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Tujuan</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b border-paper-200 last:border-0">
                            <td class="px-4 py-3 font-medium">{{ $item->kode_permintaan }}</td>
                            <td class="px-4 py-3">{{ $item->tgl_pengajuan?->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $item->gudang?->nama_gudang ?? '—' }}<span class="block text-xs text-ink-600/60">{{ $item->unitUsaha?->nama_unit_usaha ?? '—' }}</span></td>
                            <td class="px-4 py-3">Rp {{ number_format((float) $item->total_nilai, 2, ',', '.') }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $item->status_badge_class }}">{{ $item->status_label }}</span></td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('perencanaan.permintaan-pengadaan.show', $item->id_permintaan) }}" class="text-xs font-medium text-ink-700 hover:text-merah-600">Lihat detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600/60">Belum ada perencanaan pengadaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</x-layouts.app>
