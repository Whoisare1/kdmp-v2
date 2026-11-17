<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-ink-900">Riwayat Susut Konsinyasi</h1>
            <p class="mt-1 text-sm text-ink-600">Pencatatan barang titipan rusak, susut, hilang, atau kedaluwarsa.</p>
        </div>
        <a href="{{ route('konsinyasi.susut.create') }}" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">+ Catat Susut</a>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-sm border border-sawah-500/40 bg-sawah-50 px-4 py-3 text-sm text-sawah-700">{{ session('success') }}</div>
    @endif

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
            <table class="min-w-[1000px] w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">Kode / Tanggal</th>
                        <th class="px-4 py-3 font-medium">Barang / Kiriman</th>
                        <th class="px-4 py-3 font-medium">Kejadian</th>
                        <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                        <th class="px-4 py-3 font-medium">Penanggung</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai HPP</th>
                        <th class="px-4 py-3 font-medium">Dicatat oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php($stok = $item->stokKonsinyasi)
                        <tr class="border-b border-paper-200 last:border-0">
                            <td class="px-4 py-3"><p class="font-medium text-ink-900">{{ $item->kode_susut }}</p><p class="mt-1 text-xs text-ink-600">{{ $item->tanggal?->format('d M Y') }}</p></td>
                            <td class="px-4 py-3"><p>{{ $stok?->barang?->nama_barang ?? '-' }}</p><p class="mt-1 text-xs text-ink-600">{{ $stok?->kiriman?->kode_kiriman ?? '-' }}</p></td>
                            <td class="px-4 py-3"><p>{{ ['rusak' => 'Rusak', 'susut_alami' => 'Susut alami', 'hilang' => 'Hilang', 'kadaluarsa' => 'Kedaluwarsa'][$item->jenis_kejadian] ?? $item->jenis_kejadian }}</p>@if ($item->catatan)<p class="mt-1 text-xs text-ink-600">{{ $item->catatan }}</p>@endif</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $item->qty, 4, ',', '.') }} {{ $stok?->barang?->satuanDasar?->kode_satuan }}</td>
                            <td class="px-4 py-3">{{ ucfirst($item->penanggung_susut) }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format((float) $item->nilai_susut, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $item->pengguna?->nama ?? $item->entitasPencatat?->nama_entitas ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-ink-600">Belum ada susut konsinyasi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>
