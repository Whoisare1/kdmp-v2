<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-ink-900">Pengiriman Konsinyasi</h1>
            <p class="mt-1 text-sm text-ink-600">Riwayat barang titipan yang dikirim atau diterima oleh entitas aktif.</p>
        </div>
        <a href="{{ route('konsinyasi.pengiriman.create') }}" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
            + Buat Pengiriman
        </a>
    </div>

    <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <label class="min-w-56 flex-1 text-sm">
                <span class="mb-1 block text-xs font-medium text-ink-700">Cari kiriman atau pemilik</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
            </label>
            <label class="text-sm">
                <span class="mb-1 block text-xs font-medium text-ink-700">Bulan kirim</span>
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
            <label class="text-sm">
                <span class="mb-1 block text-xs font-medium text-ink-700">Pengingat batas titip</span>
                <select name="perhatian" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
                    <option value="semua" @selected(($filters['perhatian'] ?? 'semua') === 'semua')>Semua pengiriman</option>
                    <option value="perlu" @selected(($filters['perhatian'] ?? '') === 'perlu')>Perlu perhatian</option>
                </select>
            </label>
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">Tampilkan</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="min-w-[850px] w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">Kode / Tanggal</th>
                        <th class="px-4 py-3 font-medium">Pemilik Barang</th>
                        <th class="px-4 py-3 font-medium">Entitas Penerima</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                            <td class="px-4 py-3">
                                <p class="font-medium text-ink-900">{{ $item->kode_kiriman }}</p>
                                <p class="mt-1 text-xs text-ink-600">{{ $item->tgl_kirim?->format('d M Y') }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $item->jenis_pemilik === 'pihak' ? ($item->pihakPemilik?->nama ?? '-') : ($item->koperasiPemilik?->nama_entitas ?? '-') }}</td>
                            <td class="px-4 py-3">{{ $item->koperasiPenerima?->nama_entitas ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span>{{ $item->status }}</span>
                                @php
                                    $stokMasihTersisa = (float) ($item->qty_sisa_total ?? 0) > 0;
                                    $batasTitip = $item->tgl_batas_titip;
                                    $perluPeringatan = $item->status_posting === 'T'
                                        && $stokMasihTersisa
                                        && $batasTitip
                                        && $batasTitip->lte(now()->addDays(30)->endOfDay());
                                @endphp
                                @if ($perluPeringatan)
                                    <span class="mt-1 block w-fit rounded-full px-2 py-1 text-xs font-medium {{ $batasTitip->lt(now()->startOfDay()) ? 'bg-merah-100 text-merah-700' : 'bg-padi-100 text-padi-700' }}">
                                        {{ $batasTitip->lt(now()->startOfDay()) ? 'Melewati batas titip' : 'Batas titip dalam 30 hari' }}
                                    </span>
                                    <span class="mt-1 block text-xs text-ink-600">Sisa: {{ number_format($item->qty_sisa_total, 4, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('konsinyasi.pengiriman.show', $item->id_kiriman) }}" class="text-xs font-medium text-merah-600 hover:text-merah-700">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-ink-600">Belum ada pengiriman pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>
