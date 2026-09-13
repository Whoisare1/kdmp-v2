<x-layouts.app :title="$title" eyebrow="Manajemen Survei">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-semibold">{{ $title }}</h1>
            <p class="mt-1 text-sm text-ink-600">Status Sesi: <span class="font-medium px-2 py-0.5 rounded-full text-xs {{ $item->status === 'DRAFT' ? 'bg-yellow-100 text-yellow-800' : 'bg-sawah-100 text-sawah-600' }}">{{ $item->status }}</span></p>
        </div>
        <a href="{{ route($routeBase . '.index') }}" class="text-sm font-medium text-ink-600 hover:text-ink-800 hover:underline">
            &larr; Kembali ke Daftar Sesi
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-sm bg-sawah-100 p-4 border border-sawah-200 text-sawah-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Informasi Sesi -->
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-6">
            <h2 class="font-display text-lg font-semibold mb-4 border-b border-paper-200 pb-2">Informasi Sesi</h2>
            <table class="w-full text-sm text-left">
                <tbody>
                    <tr class="border-b border-paper-200 last:border-0">
                        <th class="py-3 font-medium text-ink-600 w-1/3">Wilayah</th>
                        <td class="py-3 text-ink-800">{{ $item->wilayah->nama ?? '-' }}</td>
                    </tr>
                    <tr class="border-b border-paper-200 last:border-0">
                        <th class="py-3 font-medium text-ink-600">Periode</th>
                        <td class="py-3 text-ink-800">{{ $item->bulan }} / {{ $item->tahun }}</td>
                    </tr>
                    <tr class="border-b border-paper-200 last:border-0">
                        <th class="py-3 font-medium text-ink-600">Tanggal Survei</th>
                        <td class="py-3 text-ink-800">{{ $item->tanggal_survei ? $item->tanggal_survei->format('d M Y') : '-' }}</td>
                    </tr>
                    <tr class="border-b border-paper-200 last:border-0">
                        <th class="py-3 font-medium text-ink-600">Dibuat Oleh</th>
                        <td class="py-3 text-ink-800">{{ $item->petugas->nama ?? 'Sistem' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Tautan Publik -->
        <div class="rounded-sm border border-merah-200 bg-merah-50 p-6">
            <h2 class="font-display text-lg font-semibold mb-2 text-merah-800">Tautan Pengisian Survei</h2>
            <p class="text-sm text-merah-700 mb-4">
                Bagikan tautan ini kepada surveyor atau petugas lapangan. Mereka tidak perlu login untuk mulai mengisi.
            </p>
            
            <div class="mt-2 rounded-sm border border-merah-300 bg-white p-3 font-mono text-sm break-all text-ink-800">
                {{ url('/survei/isi/' . $item->token_publik) }}
            </div>
            
            <div class="mt-4 flex gap-3">
                <button type="button" onclick="copyAndShowModal('{{ url('/survei/isi/' . $item->token_publik) }}')" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-white hover:bg-merah-600 shadow-sm">
                    Copy Tautan
                </button>
            </div>
        </div>
    </div>

    <!-- ─── SESI-SESI PENGISIAN ──────────────────────────────────────────── -->
    <div class="mt-6 mb-10">
        <h2 class="font-display text-lg font-semibold mb-3 text-ink-900">Sesi Pengisian Data</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Sesi 1: Data Demografi Desa --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-5 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-mono uppercase tracking-wider text-ink-400">Sesi 1</p>
                        <h3 class="font-display text-sm font-semibold text-ink-900 mt-0.5">Demografi Desa</h3>
                        <p class="text-[11px] text-ink-500 mt-1 leading-relaxed line-clamp-2">
                            Jumlah KK dan penduduk.
                        </p>
                    </div>
                    <span class="flex-shrink-0 inline-block rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-paper-100 text-ink-600">
                        {{ \Survei\Models\SesiSurvei::LABEL_STATUS_SESI1[$item->status_sesi1 ?? 'belum_diisi'] }}
                    </span>
                </div>
                <a href="{{ route('survei.sesi1.show', $item->id) }}"
                   class="mt-auto block w-full text-center rounded-sm bg-merah-500 px-4 py-2 text-sm font-semibold text-white hover:bg-merah-600 shadow-sm transition-colors">
                    Buka Sesi 1
                </a>
            </div>

            {{-- Sesi 2: Potensi Produksi Desa --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-5 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-mono uppercase tracking-wider text-ink-400">Sesi 2</p>
                        <h3 class="font-display text-sm font-semibold text-ink-900 mt-0.5">Potensi Produksi</h3>
                        <p class="text-[11px] text-ink-500 mt-1 leading-relaxed line-clamp-2">
                            Data produksi komoditas desa.
                        </p>
                    </div>
                    <span class="flex-shrink-0 inline-block rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-paper-100 text-ink-600">
                        {{ \Survei\Models\SesiSurvei::LABEL_STATUS_SESI2[$item->status_sesi2 ?? 'belum_diisi'] }}
                    </span>
                </div>
                <a href="{{ route('survei.sesi2.show', $item->id) }}"
                   class="mt-auto block w-full text-center rounded-sm bg-merah-500 px-4 py-2 text-sm font-semibold text-white hover:bg-merah-600 shadow-sm transition-colors">
                    Buka Sesi 2
                </a>
            </div>

            {{-- Sesi 3: Standar Konsumsi --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-5 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-mono uppercase tracking-wider text-ink-400">Sesi 3</p>
                        <h3 class="font-display text-sm font-semibold text-ink-900 mt-0.5">Standar Konsumsi</h3>
                        <p class="text-[11px] text-ink-500 mt-1 leading-relaxed line-clamp-2">
                            Konsumsi rata-rata penduduk.
                        </p>
                    </div>
                    <span class="flex-shrink-0 inline-block rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-paper-100 text-ink-600">
                        {{ \Survei\Models\SesiSurvei::LABEL_STATUS_SESI3[$item->status_sesi3 ?? 'belum_diisi'] }}
                    </span>
                </div>
                <a href="{{ route('survei.sesi3.show', $item->id) }}"
                   class="mt-auto block w-full text-center rounded-sm bg-merah-500 px-4 py-2 text-sm font-semibold text-white hover:bg-merah-600 shadow-sm transition-colors">
                    Buka Sesi 3
                </a>
            </div>

            {{-- Sesi 4: Pemenuhan Komoditas --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-5 flex flex-col gap-3 border-l-4 border-l-merah-500">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-mono uppercase tracking-wider text-ink-400">Sesi 4</p>
                        <h3 class="font-display text-sm font-semibold text-ink-900 mt-0.5">Pemenuhan & Harga</h3>
                        <p class="text-[11px] text-ink-500 mt-1 leading-relaxed line-clamp-2">
                            Sumber pembelian & harga komoditas.
                        </p>
                    </div>
                    <span class="flex-shrink-0 inline-block rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-paper-100 text-ink-600">
                        {{ \Survei\Models\SesiSurvei::LABEL_STATUS_SESI4[$item->status_sesi4 ?? 'belum_diisi'] }}
                    </span>
                </div>
                <a href="{{ route('survei.sesi4.show', $item->id) }}"
                   class="mt-auto block w-full text-center rounded-sm bg-merah-600 px-4 py-2 text-sm font-semibold text-white hover:bg-merah-700 shadow-sm transition-colors">
                    Buka Sesi 4
                </a>
            </div>

        </div>
    </div>

    <!-- Simple Success Modal -->
    <div id="successModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/50 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-xl text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sawah-100 mb-4">
                <svg class="h-6 w-6 text-sawah-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="mb-2 font-display text-lg font-semibold text-ink-900">Berhasil Disalin!</h3>
            <p class="mb-6 text-sm text-ink-600">Tautan pengisian survei telah disalin ke clipboard Anda. Silakan bagikan ke petugas lapangan.</p>
            <button type="button" onclick="closeSuccessModal()" class="w-full rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-white hover:bg-merah-600">Tutup</button>
        </div>
    </div>

    <script>
        function copyAndShowModal(text) {
            navigator.clipboard.writeText(text).then(() => {
                document.getElementById('successModal').classList.remove('hidden');
                document.getElementById('successModal').classList.add('flex');
            });
        }
        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            document.getElementById('successModal').classList.remove('flex');
        }
    </script>
</x-layouts.app>
