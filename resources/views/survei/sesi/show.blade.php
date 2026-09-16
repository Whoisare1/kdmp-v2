<x-layouts.app :title="$title" eyebrow="Modul M1 — Survei Lapangan">
    @if (session('status'))
        <div class="mb-4 rounded-sm border border-sawah-300 bg-sawah-50 p-4 text-sm text-sawah-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="mb-4 rounded-sm border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-bold text-ink-900">Sesi Survei #{{ $sesi->id }} — {{ $sesi->wilayah?->nama ?? 'Wilayah #' . $sesi->id_wilayah }}</h1>
            <p class="text-xs text-ink-600/70">Tahun {{ $sesi->tahun }} • Tanggal: {{ $sesi->tanggal_survei ? $sesi->tanggal_survei->format('d M Y') : '—' }} • Petugas: {{ $sesi->petugas?->nama ?? '—' }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('survei.sesi.index') }}"
               class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-700 hover:border-ink-500">
                ← Kembali ke Daftar
            </a>

            <form action="{{ route('survei.sesi.sinkron', $sesi->id) }}" method="POST"
                  onsubmit="return confirm('Jalankan sinkronisasi data survei sesi ini ke modul Perencanaan (M2/M3)?');">
                @csrf
                <button type="submit"
                        class="rounded-sm bg-sawah-600 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-sawah-700">
                    ⚡ Sinkronkan ke Perencanaan
                </button>
            </form>
        </div>
    </div>

    {{-- Detail Informasi --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Wilayah Desa</p>
            <p class="mt-1 font-display text-lg font-semibold text-ink-900">{{ $sesi->wilayah?->nama }}</p>
            <p class="mt-1 text-xs text-ink-600/70">ID Wilayah: {{ $sesi->id_wilayah }}</p>
        </div>
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Tahun & Bulan</p>
            <p class="mt-1 font-display text-lg font-semibold text-ink-900">Tahun {{ $sesi->tahun }}</p>
            <p class="mt-1 text-xs text-ink-600/70">{{ $sesi->bulan ? 'Bulan ' . $sesi->bulan : 'Tahunan' }}</p>
        </div>
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Status Sesi</p>
            <p class="mt-1 font-display text-lg font-semibold uppercase text-ink-900">{{ $sesi->status }}</p>
            <p class="mt-1 text-xs text-ink-600/70">Total {{ $sesi->jawaban->count() }} modul jawaban</p>
        </div>
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Token Publik</p>
            <p class="mt-1 font-mono text-xs text-ink-800 truncate">{{ $sesi->token_publik ?? '—' }}</p>
            <p class="mt-1 text-xs text-ink-600/70">Kedaluwarsa: {{ $sesi->token_kadaluarsa ? $sesi->token_kadaluarsa->format('d M Y H:i') : '—' }}</p>
        </div>
    </div>

    {{-- Rincian Jawaban Survei --}}
    <div class="rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 bg-paper-200/60 px-4 py-3 font-mono text-xs uppercase tracking-wide text-ink-700">
            Daftar Jawaban & Payload Modul Survei
        </div>

        <div class="divide-y divide-paper-200">
            @forelse ($sesi->jawaban as $jawaban)
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-semibold text-ink-800">
                            Modul: {{ $jawaban->modul?->nama ?? $jawaban->modul?->kode ?? 'Modul #' . $jawaban->id_modul }}
                            @if ($jawaban->pertanyaan)
                                • Pertanyaan: {{ $jawaban->pertanyaan->kode_pertanyaan }}
                            @endif
                        </span>
                        <span class="text-xs text-ink-600/60">Sumber: {{ $jawaban->sumber }}</span>
                    </div>

                    @if ($jawaban->nilai_angka !== null)
                        <p class="mt-2 text-sm text-ink-800 font-medium">Nilai Angka: {{ $jawaban->nilai_angka }} {{ $jawaban->satuan }}</p>
                    @endif

                    @if ($jawaban->nilai_teks)
                        <p class="mt-2 text-sm text-ink-700">{{ $jawaban->nilai_teks }}</p>
                    @endif

                    @if ($jawaban->nilai_json)
                        <div class="mt-2 rounded bg-paper-200/60 p-3 font-mono text-xs text-ink-800 overflow-x-auto">
                            <pre>{{ json_encode($jawaban->nilai_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-sm text-ink-600/60">
                    Belum ada jawaban pada sesi survei ini.
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
