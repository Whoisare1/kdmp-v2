<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-5">
        <h1 class="text-2xl font-semibold text-ink-900">Setoran Hasil Konsinyasi</h1>
        <p class="mt-1 text-sm text-ink-600">Riwayat setoran yang melibatkan entitas aktif.</p>
    </div>

    <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <label class="text-sm">
                <span class="mb-1 block text-xs font-medium text-ink-700">Bulan</span>
                <select name="bulan" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
                    <option value="">Semua bulan</option>
                    @foreach ([1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $angkaBulan => $namaBulan)
                        <option value="{{ $angkaBulan }}" @selected((string) ($filters['bulan'] ?? '') === (string) $angkaBulan)>{{ $namaBulan }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">
                <span class="mb-1 block text-xs font-medium text-ink-700">Tahun</span>
                <input type="number" name="tahun" min="2000" max="2100" value="{{ $filters['tahun'] ?? '' }}" placeholder="Semua tahun" class="w-32 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
            </label>
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">Tampilkan</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="min-w-[800px] w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">Kode / Tanggal</th>
                        <th class="px-4 py-3 font-medium">Penyetor</th>
                        <th class="px-4 py-3 font-medium">Penerima Dana</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b border-paper-200 last:border-0">
                            <td class="px-4 py-3"><p class="font-medium text-ink-900">{{ $item->kode_setoran }}</p><p class="mt-1 text-xs text-ink-600">{{ $item->tanggal?->format('d M Y') }}</p></td>
                            <td class="px-4 py-3">{{ $item->koperasiPenyetor?->nama_entitas ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $item->koperasiPenerimaDana?->nama_entitas ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format((float) $item->total_nilai, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $item->status_posting === 'T' ? 'Diposting' : 'Belum diposting' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-ink-600">Belum ada setoran pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>
