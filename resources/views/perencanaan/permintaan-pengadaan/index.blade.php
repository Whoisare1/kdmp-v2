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
                <h2 class="font-display text-xl font-semibold text-ink-900">Rekomendasi Surplus dan Defisit Antar-Desa</h2>
                <p class="mt-1 text-sm text-ink-600">Setiap komoditas surplus atau defisit di desa Anda dapat dicocokkan dengan hasil berlawanan dari desa lain pada periode yang sama.</p>
                <p class="mt-1 text-xs text-ink-600">Pilih desa pasangan, lalu klik <strong>Rekomendasikan ke Desa Lain</strong>. Pengajuan masuk ke antrean Admin Gudang untuk ditinjau. Keputusan hanya mengubah status; PR atau transaksi tidak dibuat.</p>
            </div>
            @if ($calonPerencanaanSektor->isEmpty())
                <div class="rounded-sm border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Belum ada hasil kalkulasi surplus atau defisit untuk desa aktif. Untuk menyiapkan data contoh di database lokal, jalankan <code class="rounded bg-amber-100 px-1 py-0.5">php artisan db:seed --class=PerencanaanDemoSeeder</code>, lalu buka kembali halaman ini.
                    <p class="mt-2">Contoh pasangan data demo tahun 2026: Desa Mekar Jaya memiliki surplus Jagung 2.000 kg, sedangkan Desa Sukamaju defisit Jagung 1.000 kg. Ikan Nila juga tersedia sebagai contoh sektor Perikanan. Dari Desa Sukamaju, arahnya menjadi sebaliknya.</p>
                    <a href="{{ route('perencanaan.kebutuhan-komoditas.index') }}" class="ml-1 font-semibold underline">Buka Kalkulasi Kebutuhan Desa</a>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($calonPerencanaanSektor as $sektor => $rencanaSektor)
                        <details class="rounded-sm border border-paper-300 bg-paper-50">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 hover:bg-paper-100">
                                <span class="font-display text-base font-semibold text-ink-900">{{ ucwords(str_replace('_', ' ', $sektor)) }}</span>
                                <span class="text-xs text-ink-600">{{ $rencanaSektor->count() }} komoditas <span aria-hidden="true">⌄</span></span>
                            </summary>
                            <div class="divide-y divide-paper-200 border-t border-paper-200">
                                @forelse ($rencanaSektor as $rencanaKomoditas)
                                    @php
                                        $contoh = $rencanaKomoditas->first();
                                        $namaKomoditas = $contoh['hasil']->komoditas?->nama ?? 'Komoditas tidak ditemukan';
                                        $rencanaPeriode = $rencanaKomoditas->groupBy(fn ($rencana) => $rencana['periode'].'|'.$rencana['hasil']->tahun);
                                    @endphp
                                    <details class="border-b border-paper-200 last:border-b-0">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-ink-900 hover:bg-paper-100">
                                            <span>{{ $namaKomoditas }}</span>
                                            <span class="text-xs font-normal text-ink-600">{{ $rencanaPeriode->count() }} periode panen <span aria-hidden="true">⌄</span></span>
                                        </summary>
                                        <div class="space-y-2 border-t border-paper-200 px-4 py-3">
                                            @foreach ($rencanaPeriode as $periodeKey => $rencanaDalamPeriode)
                                                @php
                                                    [$namaPeriode, $tahunPeriode] = explode('|', $periodeKey, 2);
                                                @endphp
                                                <details class="rounded-sm border border-paper-300 bg-white">
                                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2 text-xs font-semibold text-ink-800 hover:bg-paper-100">
                                                        <span>Periode {{ ucfirst($namaPeriode) }} · Tahun {{ $tahunPeriode }}</span>
                                                        <span class="text-ink-600">{{ $rencanaDalamPeriode->count() }} hasil <span aria-hidden="true">⌄</span></span>
                                                    </summary>
                                                    <div class="space-y-2 border-t border-paper-200 p-3">
                                                        @foreach ($rencanaDalamPeriode as $rencana)
                                                @php
                                                    $hasil = $rencana['hasil'];
                                                    $milikSendiriDefisit = $hasil->status_surplus_defisit === 'defisit';
                                                    $statusLabelSendiri = $milikSendiriDefisit ? 'Defisit' : 'Surplus';
                                                    $statusLabelPasangan = $milikSendiriDefisit ? 'Surplus' : 'Defisit';
                                                    $statusClassSendiri = $milikSendiriDefisit ? 'border-merah-200 bg-merah-50/60 text-merah-700' : 'border-sawah-200 bg-sawah-50/60 text-sawah-800';
                                                    $calonBelumDiajukan = $rencana['calonSupplier']->filter(function ($calon) use ($milikSendiriDefisit, $hasil, $rekomendasiTerkini): bool {
                                                        $idDefisit = $milikSendiriDefisit ? $hasil->id_hasil : $calon->id_hasil;
                                                        $idSurplus = $milikSendiriDefisit ? $calon->id_hasil : $hasil->id_hasil;
                                                        $pengajuan = $rekomendasiTerkini->get($idDefisit.'|'.$idSurplus);

                                                        return ! $pengajuan || $pengajuan->status === 'ditolak';
                                                    })->values();
                                                @endphp
                                                <div class="space-y-3 rounded-sm bg-paper-100/70 px-3 py-3 text-xs">
                                                    <div class="grid gap-3 sm:grid-cols-2">
                                                        <div class="rounded-sm border px-3 py-2 {{ $statusClassSendiri }}">
                                                            <p class="font-semibold">Komoditas {{ $statusLabelSendiri }} Desa Sendiri · {{ $hasil->wilayah?->nama ?? 'Desa aktif' }}</p>
                                                            <p class="mt-1 text-ink-700">Periode {{ ucfirst($rencana['periode']) }} · Tahun {{ $hasil->tahun }}</p>
                                                            <p class="mt-1 text-ink-700">Kebutuhan {{ number_format((float) $hasil->total_kebutuhan, 2, ',', '.') }} · Tersedia {{ number_format((float) $hasil->total_ketersediaan, 2, ',', '.') }}</p>
                                                            <p class="mt-1 font-semibold">{{ $statusLabelSendiri }} {{ number_format(abs((float) $hasil->selisih), 2, ',', '.') }}</p>
                                                        </div>
                                                        <div class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                                            <p class="font-semibold text-ink-900">{{ $statusLabelPasangan }} Komoditas Desa Lain</p>
                                                            @if ($rencana['calonSupplier']->isNotEmpty())
                                                                <p class="mt-1 text-ink-600">Pasangan komoditas sama, periode dan tahun sama; hanya hasil terbaru ditampilkan.</p>
                                                                <ul class="mt-1 space-y-1 text-sawah-800">
                                                                    @foreach ($rencana['calonSupplier'] as $calonPasangan)
                                                                        <li>
                                                                            @php
                                                                                $idDefisitPasangan = $milikSendiriDefisit ? $hasil->id_hasil : $calonPasangan->id_hasil;
                                                                                $idSurplusPasangan = $milikSendiriDefisit ? $calonPasangan->id_hasil : $hasil->id_hasil;
                                                                                $pengajuanPasangan = $rekomendasiTerkini->get($idDefisitPasangan.'|'.$idSurplusPasangan);
                                                                            @endphp
                                                                                <span class="font-semibold">{{ $calonPasangan->wilayah?->nama ?? 'Desa lain' }}</span>
                                                                                · {{ ucfirst($statusLabelPasangan) }} {{ number_format(abs((float) $calonPasangan->selisih), 2, ',', '.') }}
                                                                                <span class="block text-[11px] text-ink-600">Kebutuhan {{ number_format((float) $calonPasangan->total_kebutuhan, 2, ',', '.') }} · Tersedia {{ number_format((float) $calonPasangan->total_ketersediaan, 2, ',', '.') }} · Periode {{ ucfirst(\App\Support\Perencanaan\PeriodeKalkulasi::fromMonth((int) $calonPasangan->bulan)) }} {{ $calonPasangan->tahun }}</span>
                                                                            @if ($pengajuanPasangan && $pengajuanPasangan->status !== 'ditolak')
                                                                                <span class="mt-1 inline-block rounded-sm px-2 py-1 font-semibold {{ $pengajuanPasangan->status === 'disetujui' ? 'bg-sawah-100 text-sawah-800' : 'bg-amber-50 text-amber-800' }}">
                                                                                    {{ $pengajuanPasangan->status === 'disetujui' ? 'Disetujui Admin Gudang' : 'Menunggu Admin Gudang' }}
                                                                                </span>
                                                                            @endif
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @else
                                                                <p class="mt-1 text-ink-600">Belum ada desa lain dengan status {{ strtolower($statusLabelPasangan) }} untuk periode dan tahun ini.</p>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    @if ($calonBelumDiajukan->isNotEmpty())
                                                        <form method="POST" action="{{ route('perencanaan.rekomendasi-pengadaan.store') }}" class="flex flex-wrap items-end gap-2">
                                                            @csrf
                                                            @if ($milikSendiriDefisit)
                                                                <input type="hidden" name="id_hasil_defisit" value="{{ $hasil->id_hasil }}">
                                                            @else
                                                                <input type="hidden" name="id_hasil_surplus" value="{{ $hasil->id_hasil }}">
                                                            @endif
                                                            <label for="pasangan-{{ $hasil->id_hasil }}" class="grid gap-1 text-[11px] font-medium text-ink-700">
                                                                Pilih desa dengan status {{ strtolower($statusLabelPasangan) }}
                                                                <select id="pasangan-{{ $hasil->id_hasil }}" name="{{ $milikSendiriDefisit ? 'id_hasil_surplus' : 'id_hasil_defisit' }}" required class="min-w-60 rounded-sm border border-paper-300 bg-white px-2 py-2 text-xs text-ink-800">
                                                                    <option value="">Pilih desa</option>
                                                                    @foreach ($calonBelumDiajukan as $calonPasangan)
                                                                        <option value="{{ $calonPasangan->id_hasil }}">{{ $calonPasangan->wilayah?->nama ?? 'Desa lain' }} — {{ ucfirst($statusLabelPasangan) }} {{ number_format(abs((float) $calonPasangan->selisih), 2, ',', '.') }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </label>
                                                            <button type="submit" class="rounded-sm bg-merah-600 px-4 py-2 font-semibold text-white shadow-sm hover:bg-merah-700 focus:outline-none focus:ring-2 focus:ring-merah-500 focus:ring-offset-2">
                                                                Rekomendasikan ke Desa Lain
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button type="button" disabled class="inline-flex cursor-not-allowed rounded-sm bg-paper-200 px-4 py-2 font-semibold text-ink-600">
                                                            {{ $rencana['calonSupplier']->isNotEmpty() ? 'Semua pasangan sudah direkomendasikan' : 'Belum ada pasangan untuk direkomendasikan' }}
                                                        </button>
                                                    @endif
                                                </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endforeach
                                        </div>
                                    </details>
                                @empty
                                    <p class="px-4 py-3 text-xs text-ink-600">
                                        Belum ada hasil surplus atau defisit pada sektor ini untuk desa dan periode yang tersedia.
                                    </p>
                                @endforelse
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
