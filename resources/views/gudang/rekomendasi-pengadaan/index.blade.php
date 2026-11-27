<x-layouts.app title="Persetujuan Rekomendasi Pengadaan" eyebrow="Manajemen Gudang">
    <div class="mx-auto max-w-7xl space-y-5">

        {{-- ── Header ── --}}
        <div>
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Manajemen Gudang</p>
            <h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">Persetujuan Rekomendasi Pengadaan</h1>
            <p class="mt-1 text-sm text-ink-600">
                Desa yang kekurangan komoditas (defisit) dapat mengirim rekomendasi ke hingga 3 desa yang memiliki
                kelebihan (surplus) sekaligus — sistem "lobi". Desa surplus yang menyetujui lebih dahulu akan menjadi
                pemasok; rekomendasi ke desa lain untuk kebutuhan yang sama ditutup otomatis.
                Menyetujui rekomendasi <strong>tidak</strong> membuat Purchase Request maupun transaksi.
            </p>
        </div>

        {{-- ── Flash messages ── --}}
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

        {{-- ── Filter tabs ── --}}
        @php
            $tabs = [
                null        => 'Semua',
                'diajukan'  => 'Menunggu',
                'disetujui' => 'Disetujui',
                'ditolak'   => 'Ditolak',
            ];
        @endphp
        <div class="flex gap-1 border-b border-paper-300">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('gudang.rekomendasi-pengadaan.index', $key ? ['status' => $key] : []) }}"
                   class="px-4 py-2 text-sm font-medium transition-colors
                       {{ ($filterStatus ?? null) === $key
                           ? 'border-b-2 border-sawah-600 text-sawah-700'
                           : 'text-ink-600 hover:text-ink-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- ── Table ── --}}
        @php
            // Pre-compute lobi counts: how many 'diajukan' rows share the same id_hasil_defisit
            $lobiCount = $items->groupBy('id_hasil_defisit')
                ->map(fn ($group) => $group->where('status', 'diajukan')->count());

            // Track which id_hasil_defisit groups we've already rendered the lobi badge for
            $lobiRendered = [];

            $bulanNama = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
            ];
        @endphp

        <div class="overflow-x-auto rounded-sm border border-paper-300 bg-paper-50">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60 text-xs text-ink-700">
                    <tr>
                        <th class="px-4 py-3">Desa membutuhkan</th>
                        <th class="px-4 py-3">Komoditas</th>
                        <th class="px-4 py-3">Jumlah defisit</th>
                        <th class="px-4 py-3">Desa surplus</th>
                        <th class="px-4 py-3">Jumlah surplus</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Pengaju</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($items as $item)
                        @php
                            $hasilDefisit   = $item->hasilDefisit;
                            $hasilSurplus   = $item->hasilSurplus;
                            $bulan          = (int) $hasilDefisit->bulan;
                            $periodeLabel   = ($bulanNama[$bulan] ?? $bulan) . ' ' . $hasilDefisit->tahun;
                            $idDefisit      = $item->id_hasil_defisit;
                            $isLobi         = ($lobiCount[$idDefisit] ?? 0) > 1;
                            $showLobiBadge  = $isLobi && !in_array($idDefisit, $lobiRendered);
                            if ($showLobiBadge) { $lobiRendered[] = $idDefisit; }
                            $rowBg          = ($isLobi && $item->status === 'diajukan') ? 'bg-amber-50' : '';
                        @endphp
                        <tr class="align-top {{ $rowBg }}">

                            {{-- Desa membutuhkan --}}
                            <td class="px-4 py-3">
                                <span class="font-semibold text-ink-900">
                                    {{ $hasilDefisit->wilayah?->nama ?? '—' }}
                                </span>
                                @if ($showLobiBadge)
                                    <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800">
                                        Lobi {{ $lobiCount[$idDefisit] }} desa
                                    </span>
                                @endif
                            </td>

                            {{-- Komoditas --}}
                            <td class="px-4 py-3 text-ink-900">
                                {{ $hasilDefisit->komoditas?->nama ?? '—' }}
                            </td>

                            {{-- Jumlah defisit --}}
                            <td class="px-4 py-3">
                                <span class="font-medium text-merah-700">
                                    {{ number_format(abs((float) $hasilDefisit->selisih), 2, ',', '.') }}
                                </span>
                                <span class="text-xs text-ink-600">
                                    {{ $hasilDefisit->komoditas?->satuan ?? '' }}
                                </span>
                            </td>

                            {{-- Desa surplus --}}
                            <td class="px-4 py-3 font-semibold text-ink-900">
                                {{ $hasilSurplus->wilayah?->nama ?? '—' }}
                            </td>

                            {{-- Jumlah surplus --}}
                            <td class="px-4 py-3">
                                <span class="font-medium text-sawah-700">
                                    {{ number_format((float) $hasilSurplus->selisih, 2, ',', '.') }}
                                </span>
                                <span class="text-xs text-ink-600">
                                    {{ $hasilSurplus->komoditas?->satuan ?? '' }}
                                </span>
                            </td>

                            {{-- Periode --}}
                            <td class="px-4 py-3 text-ink-700">{{ $periodeLabel }}</td>

                            {{-- Pengaju --}}
                            <td class="px-4 py-3">
                                <span class="text-ink-900">{{ $item->pengaju?->nama ?? '—' }}</span>
                                <span class="block text-xs text-ink-600">
                                    {{ $item->created_at?->format('d M Y H:i') }}
                                </span>
                            </td>

                            {{-- Status + catatan --}}
                            <td class="px-4 py-3">
                                @php
                                    $badgeClass = match($item->status) {
                                        'disetujui' => 'bg-sawah-100 text-sawah-800',
                                        'ditolak'   => 'bg-merah-100 text-merah-800',
                                        default     => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <span class="relative inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium {{ $badgeClass }}">
                                    {{ ucfirst($item->status) }}
                                    @if ($item->catatan_penyetuju)
                                        {{-- Tooltip icon --}}
                                        <span class="group relative cursor-default">
                                            <svg class="h-3 w-3 opacity-60" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0zm-8-3a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-1 4a1 1 0 0 1 2 0v3a1 1 0 1 1-2 0v-3z"
                                                      clip-rule="evenodd" />
                                            </svg>
                                            <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 w-56 -translate-x-1/2 rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs text-ink-700 opacity-0 shadow-sm transition-opacity group-hover:opacity-100">
                                                {{ $item->catatan_penyetuju }}
                                            </span>
                                        </span>
                                    @endif
                                </span>
                            </td>

                            {{-- Tindakan --}}
                            <td class="px-4 py-3 text-right">
                                @if ($item->status === 'diajukan' && (int) $item->id_pengaju !== (int) auth()->id())
                                    <div class="flex flex-col items-end gap-2">
                                        <form method="POST"
                                              action="{{ route('gudang.rekomendasi-pengadaan.setujui', $item->id_rekomendasi) }}"
                                              onsubmit="return confirm('Setujui rekomendasi ini? Alternatif lain untuk kebutuhan yang sama akan ditutup otomatis.')">
                                            @csrf
                                            <button type="submit"
                                                    class="rounded-sm bg-sawah-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sawah-700">
                                                Setujui
                                            </button>
                                        </form>
                                        <form method="POST"
                                              action="{{ route('gudang.rekomendasi-pengadaan.tolak', $item->id_rekomendasi) }}"
                                              class="flex items-center gap-1">
                                            @csrf
                                            <input name="catatan_penyetuju"
                                                   maxlength="1000"
                                                   placeholder="Alasan (opsional)"
                                                   class="w-36 rounded-sm border border-paper-300 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-merah-400">
                                            <button type="submit"
                                                    class="rounded-sm border border-merah-300 px-3 py-1.5 text-xs font-semibold text-merah-700 hover:bg-merah-50">
                                                Tolak
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($item->status === 'diajukan')
                                    <span class="text-xs text-ink-600">Pengaju tidak dapat memutuskan.</span>
                                @else
                                    <span class="text-xs text-ink-600">Sudah diproses</span>
                                    @if ($item->pemutus)
                                        <span class="mt-0.5 block text-xs text-ink-600">
                                            oleh {{ $item->pemutus->nama }}
                                        </span>
                                        <span class="block text-xs text-ink-600/70">
                                            {{ $item->diputuskan_pada?->format('d M Y H:i') }}
                                        </span>
                                    @endif
                                    @if ($item->status === 'disetujui' && $item->catatan_penyetuju && str_contains($item->catatan_penyetuju, 'Draft pembelian'))
                                        @php
                                            preg_match('/Draft pembelian dibuat: (PBL-[^\s|]+)/', $item->catatan_penyetuju ?? '', $matchPbl);
                                        @endphp
                                        @if (!empty($matchPbl[1]))
                                            <a href="{{ route('pembelian.pembelian.index') }}"
                                               class="mt-1 block text-xs font-semibold text-sawah-700 underline">
                                                Draft: {{ $matchPbl[1] }}
                                            </a>
                                        @endif
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-ink-600">
                                Tidak ada rekomendasi pengadaan
                                @if ($filterStatus) dengan status <strong>{{ $filterStatus }}</strong>@endif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ── --}}
        @if ($items->hasPages())
            <div>{{ $items->appends(request()->query())->links() }}</div>
        @endif

    </div>
</x-layouts.app>
