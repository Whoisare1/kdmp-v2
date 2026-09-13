<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Survei Komoditas – {{ $sesi->wilayah->nama ?? 'Wilayah' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper-100 font-sans text-ink-900 antialiased min-h-screen pb-12">

    @php
        $wil = $sesi->wilayah;
        $kec = $wil?->parent;
        $kab = $kec?->parent;
        $petugasNama = $sesi->petugas->nama ?? 'Sistem';
    @endphp

    {{-- Header --}}
    <header class="bg-merah-600 text-white shadow-md px-4 py-5 sticky top-0 z-10">
        <div class="max-w-lg mx-auto space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider bg-white/20 px-2.5 py-0.5 rounded-full text-white">
                    Progres: {{ $sesi->progress_sesi_text }} Sesi
                </span>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $sesi->status === 'SELESAI' ? 'bg-green-200 text-green-900' : 'bg-yellow-200 text-yellow-900' }}">
                    Status: {{ $sesi->status }}
                </span>
            </div>
            
            <h1 class="font-bold text-xl leading-tight">{{ $wil->nama ?? 'Wilayah Tidak Diketahui' }}</h1>
            
            <div class="text-xs opacity-90 space-y-0.5 font-medium pt-1 border-t border-white/20">
                <div><span class="opacity-75">Kecamatan:</span> {{ $kec->nama ?? '-' }} &bull; <span class="opacity-75">Kabupaten:</span> {{ $kab->nama ?? '-' }}</div>
                <div><span class="opacity-75">Periode:</span> {{ $sesi->bulan }}/{{ $sesi->tahun }} &bull; <span class="opacity-75">Petugas:</span> {{ $petugasNama }}</div>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 mt-6 space-y-4">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div>
            <h2 class="text-base font-semibold text-ink-800 mb-1">Sesi Pengisian Data</h2>
            <p class="text-xs text-ink-500 mb-4">Pilih sesi yang ingin diisi. Data tersimpan secara instan.</p>

            {{-- Sesi 1: Data Demografi Desa --}}
            @php
                $st1 = $sesi->status_sesi1 ?? 'belum_diisi';
                $st1Label = match($st1) {
                    'sedang_diisi'  => 'Sedang Diisi',
                    'selesai'       => 'Selesai ✓',
                    'terverifikasi' => 'Terverifikasi ✓',
                    default         => 'Belum Diisi',
                };
                $st1BtnText = match($st1) {
                    'sedang_diisi'  => 'Lanjutkan Sesi 1 →',
                    'selesai'       => 'Tinjau Sesi 1 →',
                    'terverifikasi' => 'Tinjau Sesi 1 →',
                    default         => 'Mulai Isi Sesi 1 →',
                };
                $st1BadgeStyle = ($st1 === 'selesai' || $st1 === 'terverifikasi')
                    ? 'background:#dcfce7;color:#166534'
                    : ($st1 === 'sedang_diisi' ? 'background:#fef9c3;color:#854d0e' : 'background:#f3f4f6;color:#6b7280');
                $st1BtnStyle = ($st1 === 'selesai' || $st1 === 'terverifikasi')
                    ? 'background:#16a34a'
                    : 'background:#dc2626';
            @endphp
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm overflow-hidden mb-3">
                <div class="p-4 flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-mono text-ink-400 uppercase tracking-wider">Sesi 1</p>
                        <h3 class="font-semibold text-sm text-ink-900 mt-0.5">Data Demografi Desa</h3>
                        <p class="text-xs text-ink-500 mt-1">Jumlah KK dan penduduk berdasarkan kelompok umur & gender.</p>
                    </div>
                    <span class="flex-shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold" style="{{ $st1BadgeStyle }}">
                        {{ $st1Label }}
                    </span>
                </div>
                <div class="border-t border-paper-100 px-4 py-3">
                    <a href="{{ route('survei.public.sesi1.show', $token) }}"
                       class="block w-full text-center text-white text-xs font-semibold py-2.5 rounded-lg transition-colors shadow-sm"
                       style="{{ $st1BtnStyle }}">
                        {{ $st1BtnText }}
                    </a>
                </div>
            </div>

            {{-- Sesi 2: Potensi Produksi Desa --}}
            @php
                $st2 = $sesi->status_sesi2 ?? 'belum_diisi';
                $st2Label = match($st2) {
                    'sedang_diisi'  => 'Sedang Diisi',
                    'selesai'       => 'Selesai ✓',
                    'terverifikasi' => 'Terverifikasi ✓',
                    default         => 'Belum Diisi',
                };
                $st2BtnText = match($st2) {
                    'sedang_diisi'  => 'Lanjutkan Sesi 2 →',
                    'selesai'       => 'Tinjau Sesi 2 →',
                    'terverifikasi' => 'Tinjau Sesi 2 →',
                    default         => 'Mulai Isi Sesi 2 →',
                };
                $st2BadgeStyle = ($st2 === 'selesai' || $st2 === 'terverifikasi')
                    ? 'background:#dcfce7;color:#166534'
                    : ($st2 === 'sedang_diisi' ? 'background:#fef9c3;color:#854d0e' : 'background:#f3f4f6;color:#6b7280');
                $st2BtnStyle = ($st2 === 'selesai' || $st2 === 'terverifikasi')
                    ? 'background:#16a34a'
                    : 'background:#dc2626';
            @endphp
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm overflow-hidden mb-3">
                <div class="p-4 flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-mono text-ink-400 uppercase tracking-wider">Sesi 2</p>
                        <h3 class="font-semibold text-sm text-ink-900 mt-0.5">Potensi Produksi Desa</h3>
                        <p class="text-xs text-ink-500 mt-1">Potensi produksi komoditas desa, jumlah dijual, dikonsumsi, & sisa.</p>
                    </div>
                    <span class="flex-shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold" style="{{ $st2BadgeStyle }}">
                        {{ $st2Label }}
                    </span>
                </div>
                <div class="border-t border-paper-100 px-4 py-3">
                    <a href="{{ route('survei.public.sesi2.show', $token) }}"
                       class="block w-full text-center text-white text-xs font-semibold py-2.5 rounded-lg transition-colors shadow-sm"
                       style="{{ $st2BtnStyle }}">
                        {{ $st2BtnText }}
                    </a>
                </div>
            </div>

            {{-- Sesi 3: Standar Konsumsi Komoditas --}}
            @php
                $st3 = $sesi->status_sesi3 ?? 'belum_diisi';
                $st3Label = match($st3) {
                    'sedang_diisi'  => 'Sedang Diisi',
                    'selesai'       => 'Selesai ✓',
                    'terverifikasi' => 'Terverifikasi ✓',
                    default         => 'Belum Diisi',
                };
                $st3BtnText = match($st3) {
                    'sedang_diisi'  => 'Lanjutkan Sesi 3 →',
                    'selesai'       => 'Tinjau Sesi 3 →',
                    'terverifikasi' => 'Tinjau Sesi 3 →',
                    default         => 'Mulai Isi Sesi 3 →',
                };
                $st3BadgeStyle = ($st3 === 'selesai' || $st3 === 'terverifikasi')
                    ? 'background:#dcfce7;color:#166534'
                    : ($st3 === 'sedang_diisi' ? 'background:#fef9c3;color:#854d0e' : 'background:#f3f4f6;color:#6b7280');
                $st3BtnStyle = ($st3 === 'selesai' || $st3 === 'terverifikasi')
                    ? 'background:#16a34a'
                    : 'background:#dc2626';
            @endphp
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm overflow-hidden mb-3">
                <div class="p-4 flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-mono text-ink-400 uppercase tracking-wider">Sesi 3</p>
                        <h3 class="font-semibold text-sm text-ink-900 mt-0.5">Standar Konsumsi Komoditas</h3>
                        <p class="text-xs text-ink-500 mt-1">Kebutuhan konsumsi komoditas penduduk desa.</p>
                    </div>
                    <span class="flex-shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold" style="{{ $st3BadgeStyle }}">
                        {{ $st3Label }}
                    </span>
                </div>
                <div class="border-t border-paper-100 px-4 py-3">
                    <a href="{{ route('survei.public.sesi3.show', $token) }}"
                       class="block w-full text-center text-white text-xs font-semibold py-2.5 rounded-lg transition-colors shadow-sm"
                       style="{{ $st3BtnStyle }}">
                        {{ $st3BtnText }}
                    </a>
                </div>
            </div>

            {{-- Sesi 4: Pemenuhan & Harga Komoditas --}}
            @php
                $st4 = $sesi->status_sesi4 ?? 'belum_diisi';
                $st4Label = match($st4) {
                    'sedang_diisi'  => 'Sedang Diisi',
                    'selesai'       => 'Selesai ✓',
                    'terverifikasi' => 'Terverifikasi ✓',
                    default         => 'Belum Diisi',
                };
                $st4BtnText = match($st4) {
                    'sedang_diisi'  => 'Lanjutkan Sesi 4 →',
                    'selesai'       => 'Tinjau Sesi 4 →',
                    'terverifikasi' => 'Tinjau Sesi 4 →',
                    default         => 'Mulai Isi Sesi 4 →',
                };
                $st4BadgeStyle = ($st4 === 'selesai' || $st4 === 'terverifikasi')
                    ? 'background:#dcfce7;color:#166534'
                    : ($st4 === 'sedang_diisi' ? 'background:#fef9c3;color:#854d0e' : 'background:#f3f4f6;color:#6b7280');
                $st4BtnStyle = ($st4 === 'selesai' || $st4 === 'terverifikasi')
                    ? 'background:#16a34a'
                    : 'background:#dc2626';
            @endphp
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm overflow-hidden mb-3">
                <div class="p-4 flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-mono text-ink-400 uppercase tracking-wider">Sesi 4</p>
                        <h3 class="font-semibold text-sm text-ink-900 mt-0.5">Pemenuhan & Harga Komoditas</h3>
                        <p class="text-xs text-ink-500 mt-1">Standar pemenuhan dan harga komoditas di masyarakat.</p>
                    </div>
                    <span class="flex-shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold" style="{{ $st4BadgeStyle }}">
                        {{ $st4Label }}
                    </span>
                </div>
                <div class="border-t border-paper-100 px-4 py-3">
                    <a href="{{ route('survei.public.sesi4.show', $token) }}"
                       class="block w-full text-center text-white text-xs font-semibold py-2.5 rounded-lg transition-colors shadow-sm"
                       style="{{ $st4BtnStyle }}">
                        {{ $st4BtnText }}
                    </a>
                </div>
            </div>

        </div>

    </main>

    <footer class="max-w-lg mx-auto px-4 mt-8 text-center text-xs text-ink-400">
        Tautan Akses Publik &bull; Sistem Survei Ketahanan Pangan
    </footer>

</body>
</html>
