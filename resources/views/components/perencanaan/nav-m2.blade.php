@props(['active' => 'dashboard', 'subActive' => null])

@php
    $isParameterActive = ! request()->routeIs('perencanaan.standar-kebutuhan.*')
        && (in_array($active, ['parameter', 'demografi', 'potensi'], true)
            || request()->routeIs('perencanaan.demografi.*')
            || request()->routeIs('perencanaan.potensi-produksi.*'));

    $activeSub = $subActive ?? (
        request()->routeIs('perencanaan.demografi.*') || $active === 'demografi' ? 'demografi' : (
            request()->routeIs('perencanaan.potensi-produksi.*') || $active === 'potensi' ? 'potensi' : 'demografi'
        )
    );

    $dataDasarMenu = [
        [
            'id' => 'demografi',
            'label' => 'Rekap Penduduk',
            'route' => 'perencanaan.demografi.index',
            'icon' => '👥',
        ],
        [
            'id' => 'potensi',
            'label' => 'Komoditi & Kategori Panen',
            'route' => 'perencanaan.potensi-produksi.index',
            'icon' => '🌾',
        ],
    ];
@endphp

<div class="mb-6 border-b border-paper-300 bg-paper-50/60 pb-0">
    <div class="flex items-center justify-between px-1 pb-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="rounded bg-merah-500/10 px-2 py-0.5 font-mono text-[11px] font-semibold tracking-wider text-merah-600">
                    KALKULASI & PERENCANAAN
                </span>
                <span class="text-xs text-ink-600/60">•</span>
                <p class="font-mono text-xs uppercase tracking-wide text-ink-600/70">
                    Kalkulasi Kebutuhan &amp; Perencanaan Pengadaan
                </p>
            </div>
            <p class="mt-1 text-xs text-ink-600">
                Penyelarasan kebutuhan konsumsi pangan desa dengan potensi ketersediaan panen lokal.
            </p>
        </div>
    </div>

    {{-- Sub Navigation untuk dua data dasar kalkulasi --}}
    @if ($isParameterActive)
        <div class="flex flex-wrap items-center gap-2 border-t border-paper-200/80 bg-paper-100/60 px-3 py-2">
            <span class="font-mono text-[10px] uppercase tracking-wider text-ink-600/70 mr-1">Data Dasar:</span>
            @foreach ($dataDasarMenu as $pill)
                @php $isPillActive = $activeSub === $pill['id']; @endphp
                <a href="{{ route($pill['route'], array_filter([
                        'id_wilayah' => request()->query('id_wilayah'),
                        'tahun' => request()->query('tahun'),
                    ])) }}"
                   class="inline-flex items-center gap-1.5 rounded-sm px-3 py-1.5 text-xs font-medium transition
                          {{ $isPillActive
                              ? 'bg-ink-900 text-paper-50 shadow-xs'
                              : 'bg-paper-200/70 text-ink-700 hover:bg-paper-200 hover:text-ink-950' }}">
                    <span>{{ $pill['icon'] }}</span>
                    <span>{{ $pill['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
