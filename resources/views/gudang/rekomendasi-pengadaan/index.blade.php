<x-layouts.app title="Persetujuan Rekomendasi Pengadaan" eyebrow="Manajemen Gudang">
    <div class="mx-auto max-w-7xl space-y-4">
        <div>
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Manajemen Gudang</p>
            <h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">Persetujuan Rekomendasi Pengadaan</h1>
            <p class="mt-1 text-sm text-ink-600">Periksa kecocokan desa defisit dengan desa surplus. Menyetujui rekomendasi tidak membuat PR atau transaksi.</p>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-sm border border-merah-400/50 bg-merah-50 px-4 py-3 text-sm text-merah-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-x-auto rounded-sm border border-paper-300 bg-paper-50">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60 text-xs text-ink-700">
                    <tr>
                        <th class="px-4 py-3">Desa membutuhkan</th>
                        <th class="px-4 py-3">Desa surplus</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Pengaju</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($items as $item)
                        @php
                            $hasilDefisit = $item->hasilDefisit;
                            $hasilSurplus = $item->hasilSurplus;
                            $periodeLabel = ((int) $hasilDefisit->bulan <= 6 ? 'Ganjil' : 'Genap') . ' ' . $hasilDefisit->tahun;
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <span class="font-semibold text-ink-900">{{ $hasilDefisit->wilayah?->nama ?? 'Desa' }}</span>
                                <span class="block text-xs text-ink-600">{{ $hasilDefisit->komoditas?->nama ?? 'Komoditas' }}</span>
                                <span class="block text-xs text-merah-700">Defisit {{ number_format(abs((float) $hasilDefisit->selisih), 2, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-ink-900">{{ $hasilSurplus->wilayah?->nama ?? 'Desa' }}</span>
                                <span class="block text-xs text-sawah-700">Surplus {{ number_format((float) $hasilSurplus->selisih, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $periodeLabel }}</td>
                            <td class="px-4 py-3">
                                {{ $item->pengaju?->nama ?? 'Pengguna' }}
                                <span class="block text-xs text-ink-600">{{ $item->created_at?->format('d M Y H:i') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium
                                    {{ $item->status === 'disetujui' ? 'bg-sawah-100 text-sawah-800' : ($item->status === 'ditolak' ? 'bg-merah-100 text-merah-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                                @if ($item->catatan_penyetuju)
                                    <span class="mt-1 block max-w-48 text-xs text-ink-600">{{ $item->catatan_penyetuju }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($item->status === 'diajukan' && (int) $item->id_pengaju !== (int) auth()->id())
                                    <div class="flex flex-col items-end gap-2">
                                        <form method="POST" action="{{ route('gudang.rekomendasi-pengadaan.setujui', $item->id_rekomendasi) }}">
                                            @csrf
                                            <button type="submit" class="rounded-sm bg-sawah-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sawah-700">
                                                Setujui Rekomendasi
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('gudang.rekomendasi-pengadaan.tolak', $item->id_rekomendasi) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input name="catatan_penyetuju" maxlength="1000" placeholder="Alasan (opsional)" class="w-36 rounded-sm border border-paper-300 px-2 py-1 text-xs">
                                            <button type="submit" class="rounded-sm border border-merah-300 px-3 py-1.5 text-xs font-semibold text-merah-700 hover:bg-merah-50">
                                                Tolak
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($item->status === 'diajukan')
                                    <span class="text-xs text-ink-600">Pengaju tidak dapat menyetujui pengajuannya sendiri.</span>
                                @else
                                    <span class="text-xs text-ink-600">Sudah diproses</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-600">Belum ada rekomendasi pengadaan yang diajukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $items->links() }}
    </div>
</x-layouts.app>
