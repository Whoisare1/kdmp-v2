<x-layouts.app title="Pencocokan Surplus-Defisit" eyebrow="Manajemen Gudang">
    <div class="space-y-5">
        <div>
            <p class="text-sm font-medium text-padi-700">Rekomendasi antar desa</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Pencocokan Surplus-Defisit</h1>
            <p class="mt-2 max-w-3xl text-sm text-ink-600">
                Data berikut berasal dari kalkulasi yang sudah disetujui. Gudang membantu menemukan desa yang cocok,
                lalu mencocokkannya dengan stok gudang yang belum dipesan. Ini berupa rekomendasi dan tidak membuat
                persetujuan maupun pengiriman.
            </p>
            <p class="mt-1 max-w-3xl text-sm text-ink-600">
                Jumlah cocok adalah perkiraan untuk setiap pasangan secara terpisah; jumlah ini belum dialokasikan
                dan tidak mengurangi surplus atau defisit desa mana pun.
            </p>
            <p class="mt-1 max-w-3xl text-sm text-ink-600">
                Stok disetarakan ke satuan perencanaan memakai konversi per barang. Barang tanpa konversi yang sesuai tidak ikut dihitung.
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-sm border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $errors->first() }}</div>
        @endif

        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <label class="text-sm font-medium text-ink-700">
                Tahun
                <input type="number" name="tahun" min="2000" max="2100" value="{{ $tahun }}"
                    class="mt-1 block w-28 rounded-sm border border-paper-300 bg-white px-3 py-2">
            </label>
            <label class="text-sm font-medium text-ink-700">
                Bulan
                <select name="bulan" class="mt-1 block rounded-sm border border-paper-300 bg-white px-3 py-2">
                    <option value="">Semua bulan</option>
                    @foreach (range(1, 12) as $nomorBulan)
                        <option value="{{ $nomorBulan }}" @selected($bulan === $nomorBulan)>
                            {{ \Carbon\Carbon::create()->month($nomorBulan)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-ink-700">
                Status permintaan
                <select name="status_permintaan" class="mt-1 block rounded-sm border border-paper-300 bg-white px-3 py-2">
                    <option value="semua" @selected($statusPermintaan === 'semua')>Semua status</option>
                    <option value="terkirim" @selected($statusPermintaan === 'terkirim')>Menunggu tindak lanjut</option>
                    <option value="disetujui" @selected($statusPermintaan === 'disetujui')>Disetujui</option>
                    <option value="ditolak" @selected($statusPermintaan === 'ditolak')>Ditolak</option>
                    <option value="dibatalkan" @selected($statusPermintaan === 'dibatalkan')>Dibatalkan</option>
                </select>
            </label>
            <button type="submit" class="rounded-sm bg-ink-800 px-4 py-2 text-sm font-semibold text-white hover:bg-ink-900">
                Tampilkan
            </button>
        </form>

        @forelse ($neracaDesaSaya as $hasil)
            @php
                $kandidat = $pasangan->get($hasil->id_hasil, collect());
                $satuan = $hasil->satuan_perencanaan;
                $isSurplus = $hasil->status_surplus_defisit === 'surplus';
            @endphp
            <section class="rounded-sm border border-paper-300 bg-paper-50">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-paper-200 p-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-ink-900">{{ $hasil->komoditas?->nama ?? 'Komoditas' }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase {{ $isSurplus ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $hasil->status_surplus_defisit }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-ink-600">
                            {{ \Carbon\Carbon::create()->month($hasil->bulan)->translatedFormat('F') }} {{ $hasil->tahun }}
                            · {{ $isSurplus ? 'Surplus' : 'Kekurangan' }}
                            {{ number_format(abs((float) $hasil->selisih), 2, ',', '.') }} {{ $satuan }}
                        </p>
                        <p class="mt-1 text-sm text-ink-600">
                            Stok tersedia: {{ number_format((float) $hasil->qty_stok_tersedia, 2, ',', '.') }} {{ $satuan ?? '' }}
                            @if ($isSurplus)
                                · Stok barang yang diizinkan dijual: {{ number_format((float) $hasil->qty_stok_dapat_dijual, 2, ',', '.') }} {{ $satuan ?? '' }}
                            @endif
                        </p>
                        <p class="mt-1 text-sm font-semibold text-ink-800">
                            {{ $isSurplus ? 'Maksimum yang dapat ditawarkan' : 'Kebutuhan yang belum terpenuhi stok gudang' }}:
                            {{ number_format((float) $hasil->qty_tindak_lanjut, 2, ',', '.') }} {{ $satuan ?? '' }}
                        </p>
                        @unless ($hasil->satuan_cocok)
                            <p class="mt-1 text-sm text-amber-800">Satuan perencanaan tidak tunggal atau belum tersedia; stok belum dapat dicocokkan.</p>
                        @endunless
                    </div>

                </div>

                <div class="p-4">
                    <h3 class="text-sm font-semibold text-ink-800">
                        {{ $isSurplus ? 'Desa yang berpotensi membutuhkan' : 'Desa yang berpotensi menyediakan' }}
                    </h3>
                    @if ($kandidat->isEmpty())
                        <p class="mt-2 text-sm text-ink-600">Belum ada pasangan {{ $isSurplus ? 'defisit' : 'surplus' }} untuk komoditas dan periode ini.</p>
                    @else
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full min-w-[34rem] text-left text-sm">
                                <thead class="border-b border-paper-200 text-xs uppercase text-ink-600">
                                    <tr>
                                        <th class="px-3 py-2">{{ $isSurplus ? 'Desa defisit' : 'Desa surplus' }}</th>
                                        <th class="px-3 py-2">{{ $isSurplus ? 'Kebutuhan' : 'Ketersediaan surplus' }}</th>
                                        <th class="px-3 py-2">Potensi jumlah cocok</th>
                                        <th class="px-3 py-2">Permintaan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($kandidat as $pasanganDesa)
                                        <tr class="border-b border-paper-100 last:border-0">
                                            <td class="px-3 py-3 font-medium text-ink-900">{{ $pasanganDesa['hasil']->wilayah?->nama ?? 'Desa' }}</td>
                                            <td class="px-3 py-3 text-ink-700">{{ number_format((float) $pasanganDesa['hasil']->qty_tindak_lanjut, 2, ',', '.') }} {{ $satuan }}</td>
                                            <td class="px-3 py-3 font-semibold text-ink-900">{{ number_format($pasanganDesa['jumlah_cocok'], 2, ',', '.') }} {{ $satuan }}</td>
                                            <td class="px-3 py-3">
                                                @php
                                                    $kunciPasangan = min((int) $hasil->id_hasil, (int) $pasanganDesa['hasil']->id_hasil).':'.max((int) $hasil->id_hasil, (int) $pasanganDesa['hasil']->id_hasil);
                                                @endphp
                                                @if ($permintaanAktif->has($kunciPasangan))
                                                    <span class="text-xs font-medium text-ink-600">Sudah dikirim</span>
                                                @else
                                                    <form method="POST" action="{{ route('gudang.permintaan-antar-entitas.store') }}">
                                                        @csrf
                                                        <input type="hidden" name="id_hasil_pengaju" value="{{ $hasil->id_hasil }}">
                                                        <input type="hidden" name="id_hasil_penerima" value="{{ $pasanganDesa['hasil']->id_hasil }}">
                                                        <button type="submit" class="rounded-sm bg-ink-800 px-3 py-2 text-xs font-semibold text-white hover:bg-ink-900">
                                                            Kirim {{ $isSurplus ? 'tawaran jual' : 'permintaan beli' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        @empty
            <div class="rounded-sm border border-dashed border-paper-300 bg-paper-50 px-5 py-12 text-center">
                <h2 class="font-semibold text-ink-900">Belum ada hasil kalkulasi untuk ditampilkan</h2>
                <p class="mt-2 text-sm text-ink-600">Hasil yang belum disetujui tidak ditampilkan. Silakan periksa kembali tahun atau bulan yang dipilih.</p>
            </div>
        @endforelse

        <section class="rounded-sm border border-paper-300 bg-paper-50">
            <div class="border-b border-paper-200 p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-semibold text-ink-900">Permintaan antar entitas</h2>
                    @if ($jumlahMenungguRespons > 0)
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900">
                            {{ $jumlahMenungguRespons }} menunggu tindak lanjut
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-ink-600">Riwayat tawaran jual dan permintaan beli yang terkait entitas aktif. Permintaan masuk berstatus terkirim dapat disetujui atau ditolak di sini.</p>
            </div>
            @if ($riwayatPermintaan->isEmpty())
                <p class="p-4 text-sm text-ink-600">
                    @if ($statusPermintaan === 'semua')
                        Belum ada permintaan antar entitas.
                    @else
                        Tidak ada permintaan dengan status {{ ucfirst(str_replace('_', ' ', $statusPermintaan)) }}.
                    @endif
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[48rem] text-left text-sm">
                        <thead class="border-b border-paper-200 text-xs uppercase text-ink-600">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Arah</th>
                                <th class="px-4 py-3">Jenis</th>
                                <th class="px-4 py-3">Komoditas</th>
                                <th class="px-4 py-3">Jumlah rekomendasi</th>
                                <th class="px-4 py-3">Entitas terkait</th>
                                <th class="px-4 py-3">Status / Tindak lanjut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayatPermintaan as $permintaan)
                                @php
                                    $permintaanKeluar = (int) $permintaan->id_entitas_pengaju === $entitasId;
                                    $entitasTerkait = $permintaanKeluar ? $permintaan->penerima : $permintaan->pengaju;
                                @endphp
                                <tr class="border-b border-paper-100 last:border-0">
                                    <td class="px-4 py-3 text-ink-700">{{ $permintaan->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $permintaanKeluar ? 'Keluar' : 'Masuk' }}</td>
                                    <td class="px-4 py-3 text-ink-700">{{ $permintaan->jenis_permintaan === 'tawaran_jual' ? 'Tawaran jual' : 'Permintaan beli' }}</td>
                                    <td class="px-4 py-3 font-medium text-ink-900">{{ $permintaan->nama_komoditas }}</td>
                                    <td class="px-4 py-3 text-ink-700">{{ number_format((float) $permintaan->qty_rekomendasi, 2, ',', '.') }} {{ $permintaan->satuan }}</td>
                                    <td class="px-4 py-3 text-ink-700">{{ $entitasTerkait?->nama_entitas ?? 'Entitas lain' }} · {{ \Carbon\Carbon::create()->month($permintaan->bulan)->translatedFormat('F') }} {{ $permintaan->tahun }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-ink-700">{{ ucfirst(str_replace('_', ' ', $permintaan->status)) }}</span>
                                        @if (! $permintaanKeluar && $permintaan->status === 'terkirim')
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <form method="POST" action="{{ route('gudang.permintaan-antar-entitas.respond', $permintaan->id_permintaan_gudang) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="disetujui">
                                                    <button type="submit" class="rounded-sm bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('gudang.permintaan-antar-entitas.respond', $permintaan->id_permintaan_gudang) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="ditolak">
                                                    <button type="submit" class="rounded-sm border border-paper-300 bg-white px-3 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-500">Tolak</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
