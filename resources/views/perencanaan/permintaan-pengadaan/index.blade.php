<x-layouts.app :title="$title" eyebrow="Perencanaan Pengadaan">
    <div class="mx-auto max-w-6xl space-y-4">
        @if (session('status'))
            <div class="rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">
                <span class="font-semibold text-sawah-700">Berhasil:</span> {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-sm border border-merah-400/50 bg-merah-50 px-4 py-3 text-sm text-merah-800">
                <span class="font-semibold">Gagal:</span> {{ session('error') }}
            </div>
        @endif
        <div>
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Perencanaan Pengadaan</p>
            <h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">Rekomendasi Pengadaan Desa</h1>
        </div>
        <section class="space-y-4">
            <div>
                <h2 class="font-display text-xl font-semibold text-ink-900">Kebutuhan yang Perlu Direncanakan</h2>
                <p class="mt-1 text-sm text-ink-600">Daftar ini menunjukkan komoditas defisit desa aktif dan desa yang berpotensi memasok dari surplusnya.</p>
                <p class="mt-1 text-xs text-ink-600">Ajukan rekomendasi ke Admin Gudang untuk ditinjau. Persetujuan hanya mengubah status rekomendasi; PR atau transaksi tidak dibuat.</p>
            </div>
            @if ($calonPerencanaanSektor->isEmpty())
                <div class="rounded-sm border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Belum ada hasil kalkulasi berstatus defisit untuk desa aktif. Lengkapi data dasar dan standar AKG, lalu jalankan kalkulasi terlebih dahulu.
                    <a href="{{ route('perencanaan.kebutuhan-komoditas.index') }}" class="ml-1 font-semibold underline">Buka Kalkulasi Kebutuhan Desa</a>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($calonPerencanaanSektor as $sektor => $rencanaSektor)
                        <details class="rounded-sm border border-paper-300 bg-paper-50">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 hover:bg-paper-100">
                                <span class="font-display text-base font-semibold capitalize text-ink-900">{{ str_replace('_', ' ', $sektor) }}</span>
                                <span class="text-xs text-ink-600">{{ $rencanaSektor->count() }} komoditas defisit <span aria-hidden="true">⌄</span></span>
                            </summary>
                            <div class="divide-y divide-paper-200 border-t border-paper-200">
                                @foreach ($rencanaSektor as $rencanaKomoditas)
                                    @php
                                        $contoh = $rencanaKomoditas->first();
                                        $namaKomoditas = $contoh['hasil']->komoditas?->nama ?? 'Komoditas tidak ditemukan';
                                        $totalDefisit = $rencanaKomoditas->sum(fn ($rencana) => abs((float) $rencana['hasil']->selisih));
                                    @endphp
                                    <div class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <h3 class="text-sm font-semibold text-ink-950">{{ $namaKomoditas }}</h3>
                                            <span class="text-xs font-medium text-merah-700">Total defisit: {{ number_format($totalDefisit, 2, ',', '.') }}</span>
                                        </div>
                                        <div class="mt-2 space-y-2">
                                            @foreach ($rencanaKomoditas as $rencana)
                                                @php
                                                    $hasil = $rencana['hasil'];
                                                    $desaSumber = $rencana['calonSupplier']
                                                        ->map(fn ($supplier) => $supplier->wilayah?->nama ?? '—')
                                                        ->unique()
                                                        ->join(', ');
                                                    $pengajuan = $rekomendasiTerkini->get($hasil->id_hasil);
                                                @endphp
                                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-sm bg-paper-100/70 px-3 py-2 text-xs">
                                                    <div>
                                                        <span class="font-medium text-ink-800">Periode {{ ucfirst($rencana['periode']) }} {{ $hasil->tahun }}</span>
                                                        <span class="text-ink-600">· Kurang {{ number_format(abs((float) $hasil->selisih), 2, ',', '.') }}</span>
                                                        <span class="block text-[11px] text-ink-600">Desa surplus yang cocok: {{ $desaSumber ?: 'Belum ada desa surplus terhitung pada periode ini' }}</span>
                                                    </div>
                                                    @if ($pengajuan && $pengajuan->status !== 'ditolak')
                                                        <span class="shrink-0 rounded-sm px-3 py-1.5 font-semibold {{ $pengajuan->status === 'disetujui' ? 'bg-sawah-100 text-sawah-800' : 'bg-amber-50 text-amber-800' }}">
                                                            {{ $pengajuan->status === 'disetujui' ? 'Disetujui Admin Gudang' : 'Menunggu Admin Gudang' }}
                                                        </span>
                                                    @elseif ($rencana['calonSupplier']->isNotEmpty())
                                                        <form method="POST" action="{{ route('perencanaan.rekomendasi-pengadaan.store') }}" class="flex flex-wrap items-center gap-2">
                                                            @csrf
                                                            <input type="hidden" name="id_hasil_defisit" value="{{ $hasil->id_hasil }}">
                                                            <label class="sr-only" for="sumber-surplus-{{ $hasil->id_hasil }}">Pilih desa surplus</label>
                                                            <select id="sumber-surplus-{{ $hasil->id_hasil }}" name="id_hasil_surplus" required class="max-w-52 rounded-sm border border-paper-300 bg-white px-2 py-1.5 text-xs text-ink-800">
                                                                <option value="">Pilih desa surplus</option>
                                                                @foreach ($rencana['calonSupplier'] as $sumberSurplus)
                                                                    <option value="{{ $sumberSurplus->id_hasil }}">{{ $sumberSurplus->wilayah?->nama ?? 'Desa' }}</option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit" class="rounded-sm bg-merah-500 px-3 py-1.5 font-semibold text-white hover:bg-merah-600">
                                                                Ajukan Rekomendasi
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="shrink-0 rounded-sm bg-paper-200 px-3 py-1.5 text-ink-600">Belum ada pasangan surplus</span>
                                                    @endif
                                                    @if ($pengajuan?->status === 'ditolak')
                                                        <div class="w-full rounded-sm border border-merah-200 bg-merah-50 px-3 py-2 text-[11px] text-merah-800">
                                                            Pengajuan sebelumnya ditolak{{ $pengajuan->catatan_penyetuju ? ': ' . $pengajuan->catatan_penyetuju : '.' }}
                                                            Silakan ajukan kembali jika diperlukan.
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
