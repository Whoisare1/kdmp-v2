<x-layouts.app title="Report Surplus & Defisit" eyebrow="Perencanaan">
    <div class="mx-auto max-w-6xl space-y-5">

        {{-- Header --}}
        <div>
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Perencanaan</p>
            <h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">Report Surplus &amp; Defisit</h1>
            <p class="mt-1 text-sm text-ink-600">Hasil kalkulasi terbaru yang sudah disetujui untuk desa dan periode yang dipilih.</p>
        </div>

        {{-- Filter --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-700">Desa</label>
                <select name="id_wilayah" class="rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-800 focus:outline-none focus:ring-1 focus:ring-merah-400">
                    @foreach ($daftarWilayah as $dw)
                        <option value="{{ $dw->id }}" @selected($idWilayah == $dw->id)>
                            {{ $dw->nama }}{{ $dw->id == $idWilayahAsal ? ' (Desa Anda)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-700">Tahun</label>
                <select name="tahun" class="rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-800 focus:outline-none focus:ring-1 focus:ring-merah-400">
                    @foreach (range(2025, 2030) as $t)
                        <option value="{{ $t }}" @selected($tahun == $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-700">Periode</label>
                <select name="periode" class="rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-800 focus:outline-none focus:ring-1 focus:ring-merah-400">
                    <option value="ganjil" @selected(($periode ?? 'ganjil') === 'ganjil')>Ganjil (Jan–Jun)</option>
                    <option value="genap" @selected(($periode ?? 'ganjil') === 'genap')>Genap (Jul–Des)</option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-700">Kategori</label>
                <select name="kategori" class="rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-800 focus:outline-none focus:ring-1 focus:ring-merah-400">
                    <option value="">Semua Kategori</option>
                    @foreach ($daftarKategori as $kat)
                        <option value="{{ $kat }}" @selected($kategori === $kat)>{{ ucwords(str_replace('_', ' ', $kat)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-ink-700">Status</label>
                <select name="status" class="rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-800 focus:outline-none focus:ring-1 focus:ring-merah-400">
                    <option value="">Semua Status</option>
                    <option value="surplus" @selected($statusFilter === 'surplus')>Surplus</option>
                    <option value="defisit" @selected($statusFilter === 'defisit')>Defisit</option>
                    <option value="seimbang" @selected($statusFilter === 'seimbang')>Seimbang</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-sm bg-merah-600 px-4 py-2 text-sm font-medium text-white hover:bg-merah-700">
                    Tampilkan
                </button>
                @if ($kategori || $statusFilter)
                    <a href="{{ route('perencanaan.laporan.index', ['tahun' => $tahun]) }}"
                       class="text-sm text-ink-600 hover:text-merah-600 hover:underline">Reset</a>
                @endif
            </div>
        </form>

        {{-- Kartu Ringkasan --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-3">
                <p class="text-xs text-ink-600">Total Komoditas</p>
                <p class="mt-1 text-xl font-semibold text-ink-900">{{ $items->count() }}</p>
            </div>
            <div class="rounded-sm border border-sawah-200 bg-sawah-50 px-4 py-3">
                <p class="text-xs text-sawah-700">Surplus</p>
                <p class="mt-1 text-xl font-semibold text-sawah-800">{{ $totalSurplus }}</p>
            </div>
            <div class="rounded-sm border border-merah-200 bg-merah-50 px-4 py-3">
                <p class="text-xs text-merah-700">Defisit</p>
                <p class="mt-1 text-xl font-semibold text-merah-800">{{ $totalDefisit }}</p>
            </div>
            <div class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-3">
                <p class="text-xs text-ink-600">Seimbang</p>
                <p class="mt-1 text-xl font-semibold text-ink-900">{{ $totalSeimbang }}</p>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto rounded-sm border border-paper-300 bg-paper-50">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60 text-xs text-ink-700">
                    <tr>
                        <th class="px-4 py-3">Komoditas</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-right">Kebutuhan</th>
                        <th class="px-4 py-3 text-right">Ketersediaan</th>
                        <th class="px-4 py-3 text-right">Selisih</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @php
                        $namaBulan = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                                      7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
                    @endphp
                    @forelse ($items as $item)
                        @php
                            $satuan = $satuanMap[$item->id_komoditas] ?? '';
                        @endphp
                        <tr class="hover:bg-paper-100/60">
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $item->komoditas?->nama ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink-600">
                                {{ ucwords(str_replace('_', ' ', $item->komoditas?->kategori ?? '—')) }}
                            </td>
                            <td class="px-4 py-3 text-ink-700">
                                {{ $namaBulan[$item->bulan] ?? $item->bulan }} {{ $item->tahun }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ number_format((float) $item->total_kebutuhan, 2, ',', '.') }}
                                <span class="text-xs text-ink-500">{{ $satuan }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-ink-800">
                                {{ number_format((float) $item->total_ketersediaan, 2, ',', '.') }}
                                <span class="text-xs text-ink-500">{{ $satuan }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold
                                {{ $item->selisih < 0 ? 'text-merah-700' : ($item->selisih > 0 ? 'text-sawah-700' : 'text-ink-700') }}">
                                {{ $item->selisih > 0 ? '+' : '' }}{{ number_format((float) $item->selisih, 2, ',', '.') }}
                                <span class="text-xs font-normal text-ink-500">{{ $satuan }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ $item->status_surplus_defisit === 'surplus'
                                        ? 'bg-sawah-100 text-sawah-800'
                                        : ($item->status_surplus_defisit === 'defisit'
                                            ? 'bg-merah-100 text-merah-800'
                                            : 'bg-paper-200 text-ink-700') }}">
                                    {{ ucfirst($item->status_surplus_defisit ?? '—') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-600">
                                Tidak ada data untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-layouts.app>
