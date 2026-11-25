<x-layouts.app title="Laporan Kalkulasi — Rekapitulasi Neraca Komoditas" eyebrow="Kalkulasi dan Perencanaan">
    <style>
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            @media print {
                html,
                body,
                body > div,
                body > div > div,
                main {
                    height: auto !important;
                    min-height: 0 !important;
                    overflow: visible !important;
                }

                body {
                    background: white !important;
                }

                .print-report {
                    width: 100% !important;
                    overflow: visible !important;
                }

                .print-report table {
                    table-layout: fixed;
                    width: 100%;
                    font-size: 7px;
                }

                .print-report th,
                .print-report td {
                    padding: 3px 4px !important;
                    line-height: 1.25;
                    overflow-wrap: anywhere;
                }

                .print-report th:nth-child(1),
                .print-report td:nth-child(1) { width: 3%; }
                .print-report th:nth-child(2),
                .print-report td:nth-child(2) { width: 11%; }
                .print-report th:nth-child(3),
                .print-report td:nth-child(3) { width: 8%; }
                .print-report th:nth-child(4),
                .print-report td:nth-child(4) { width: 6%; }
                .print-report th:nth-child(5),
                .print-report td:nth-child(5) { width: 8%; }
                .print-report th:nth-child(6),
                .print-report td:nth-child(6),
                .print-report th:nth-child(7),
                .print-report td:nth-child(7),
                .print-report th:nth-child(8),
                .print-report td:nth-child(8),
                .print-report th:nth-child(10),
                .print-report td:nth-child(10) { width: 9%; }
                .print-report th:nth-child(9),
                .print-report td:nth-child(9) { width: 7%; }
                .print-report th:nth-child(11),
                .print-report td:nth-child(11) { width: 21%; }

                .print-report tr {
                    break-inside: avoid;
                }

                .print-report details:not([open]) > :not(summary) {
                    display: block !important;
                }

                .print-report details > summary {
                    display: none !important;
                }
            }
    </style>

    {{-- Navigasi kalkulasi (disembunyikan saat print) --}}
    <div class="print:hidden">
        <x-perencanaan.nav-m2 active="laporan" />
    </div>

    @php
        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
    @endphp

    {{-- Filter & Action Bar (Disembunyikan saat Print) --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 shadow-2xs print:hidden">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            {{-- Pemilah Desa --}}
            <div class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-paper-100 px-3 py-1.5 text-xs font-medium text-ink-800">
                <span class="font-mono text-[10px] uppercase text-ink-600/70">Desa:</span>
                <select name="id_wilayah" onchange="this.form.submit()" class="bg-transparent font-semibold text-ink-950 focus:outline-none cursor-pointer">
                    @foreach ($daftarWilayah as $dw)
                        <option value="{{ $dw->id }}" {{ $idWilayah == $dw->id ? 'selected' : '' }}>
                            Desa {{ $dw->nama }} {{ $dw->id == $idWilayahAsal ? '(Desa Anda)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <select name="tahun" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                @foreach (range(2025, 2030) as $t)
                    <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                @endforeach
            </select>

            <select name="periode" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="ganjil" {{ ($periode ?? 'ganjil') === 'ganjil' ? 'selected' : '' }}>Periode Ganjil (Januari–Juni)</option>
                <option value="genap" {{ ($periode ?? 'ganjil') === 'genap' ? 'selected' : '' }}>Periode Genap (Juli–Desember)</option>
            </select>

            <select name="kategori" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Kategori</option>
                @foreach ($daftarKategori as $kat)
                    <option value="{{ $kat }}" {{ $kategori === $kat ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $kat)) }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="rounded-sm border border-paper-300 bg-paper-100 px-3 py-2 text-xs font-medium text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Status</option>
                <option value="surplus" {{ $statusFilter === 'surplus' ? 'selected' : '' }}>Surplus</option>
                <option value="defisit" {{ $statusFilter === 'defisit' ? 'selected' : '' }}>Defisit</option>
                <option value="seimbang" {{ $statusFilter === 'seimbang' ? 'selected' : '' }}>Seimbang</option>
            </select>

            <button type="submit" class="rounded-sm border border-paper-300 bg-paper-200/80 px-3 py-2 text-xs font-medium text-ink-800 hover:border-merah-400">
                Filter Laporan
            </button>
            @if ($bulan || $kategori || $statusFilter)
                <a href="{{ route('perencanaan.laporan.index', ['tahun' => $tahun]) }}" class="text-xs text-ink-600 hover:text-merah-600 hover:underline">
                    Reset
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            <button type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-sm bg-ink-900 px-4 py-2 text-xs font-semibold text-paper-50 shadow-xs hover:bg-ink-800">
                <span>🖨</span>
                <span>Cetak / Ekspor PDF</span>
            </button>
        </div>
    </div>

    {{-- Dokumen Laporan (Print-Ready) --}}
    <div class="print-report rounded-sm border border-paper-300 bg-paper-50 p-8 shadow-2xs print:border-0 print:p-0 print:shadow-none">
        {{-- Kop Laporan --}}
        <div class="border-b-2 border-ink-900 pb-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-mono text-xs uppercase tracking-widest text-ink-600">KOPERASI DESA MERAH PUTIH (KDMP)</p>
                    <h1 class="font-display text-2xl font-bold text-ink-950">Laporan Rekapitulasi Neraca Komoditas Pangan</h1>
                    <p class="mt-1 text-sm text-ink-700">
                        Wilayah: <strong>{{ $wilayah?->nama ?? 'Wilayah Desa' }}</strong>
                        • Periode: <strong>{{ $periode === 'ganjil' ? 'Ganjil (Januari–Juni)' : 'Genap (Juli–Desember)' }} {{ $tahun }}</strong>
                    </p>
                </div>
                <div class="text-right">
                    <span class="rounded bg-paper-200 px-2 py-1 font-mono text-xs text-ink-800">LAPORAN KALKULASI</span>
                    <p class="mt-2 text-xs text-ink-600">Dicetak: {{ now()->format('d M Y, H:i') }} WIB</p>
                    <p class="text-xs text-ink-600">Oleh: {{ auth()->user()->nama ?? 'Sistem' }}</p>
                </div>
            </div>
        </div>

        {{-- Ringkasan Parameter Demografi & Status --}}
        <div class="my-6 grid grid-cols-2 gap-4 border-b border-paper-200 pb-6 sm:grid-cols-4 text-xs">
            <div>
                <span class="text-ink-600 font-mono text-[10px] uppercase">Basis Penduduk Desa</span>
                <p class="font-mono text-base font-bold text-ink-950">{{ number_format($totalPenduduk, 0, ',', '.') }} Jiwa</p>
            </div>
            <div>
                <span class="text-ink-600 font-mono text-[10px] uppercase">Total Komoditas Terhitung</span>
                <p class="font-mono text-base font-bold text-ink-950">{{ $items->count() }} Komoditas</p>
            </div>
            <div>
                <span class="text-ink-600 font-mono text-[10px] uppercase">Status Komoditas</span>
                <p class="font-mono text-xs font-semibold text-ink-900">
                    <span class="text-sawah-600">{{ $totalSurplus }} Surplus</span> |
                    <span class="text-padi-600">{{ $totalSeimbang }} Seimbang</span> |
                    <span class="text-merah-600">{{ $totalDefisit }} Defisit</span>
                </p>
            </div>
            <div>
                <span class="text-ink-600 font-mono text-[10px] uppercase">Akumulasi Kebutuhan</span>
                <p class="font-mono text-base font-bold text-merah-600">{{ number_format($totalKebutuhan, 2, ',', '.') }}</p>
            </div>
        </div>

        {{-- Ringkasan status dapat dibuka saat diperlukan agar laporan lebih ringkas. --}}
        <div class="my-6 space-y-3">
            <details class="rounded-sm border border-merah-300 bg-merah-50/40">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                    <span class="font-display text-sm font-bold text-merah-800">🔴 Komoditas Defisit</span>
                    <span class="font-mono text-xs font-semibold text-merah-800">{{ $totalDefisit }} komoditas <span aria-hidden="true">⌄</span></span>
                </summary>
                <div class="space-y-2 border-t border-merah-200 p-3">
                    @forelse ($daftarDefisitPerSektor as $sektor => $listDefisit)
                        <details class="rounded-sm border border-merah-200 bg-white">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2.5 text-xs font-semibold text-ink-900">
                                <span>Sektor {{ $sektor }}</span>
                                <span class="font-normal text-ink-600">{{ $listDefisit->count() }} komoditas <span aria-hidden="true">⌄</span></span>
                            </summary>
                            <div class="divide-y divide-paper-100 border-t border-paper-200 px-3">
                                @foreach ($listDefisit as $hDef)
                                    <div class="flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
                                        <div>
                                            <span class="font-semibold text-ink-950">{{ $hDef->komoditas?->nama }}</span>
                                            <span class="text-[11px] text-ink-600">(Butuh {{ number_format($hDef->total_kebutuhan, 1, ',', '.') }} · Panen {{ number_format($hDef->total_ketersediaan, 1, ',', '.') }})</span>
                                        </div>
                                        <span class="font-mono font-bold text-merah-700">−{{ number_format(abs($hDef->selisih), 1, ',', '.') }} {{ $satuanMap[$hDef->id_komoditas] ?? 'KG' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <p class="py-2 text-center text-xs text-ink-600">Tidak ada komoditas defisit pada periode ini.</p>
                    @endforelse
                </div>
            </details>

            <details class="rounded-sm border border-sawah-300 bg-sawah-50/40">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                    <span class="font-display text-sm font-bold text-sawah-800">🟢 Komoditas Surplus</span>
                    <span class="font-mono text-xs font-semibold text-sawah-800">{{ $totalSurplus }} komoditas <span aria-hidden="true">⌄</span></span>
                </summary>
                <div class="space-y-2 border-t border-sawah-200 p-3">
                    @forelse ($daftarSurplusPerSektor as $sektor => $listSurplus)
                        <details class="rounded-sm border border-sawah-200 bg-white">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2.5 text-xs font-semibold text-ink-900">
                                <span>Sektor {{ $sektor }}</span>
                                <span class="font-normal text-ink-600">{{ $listSurplus->count() }} komoditas <span aria-hidden="true">⌄</span></span>
                            </summary>
                            <div class="divide-y divide-paper-100 border-t border-paper-200 px-3">
                                @foreach ($listSurplus as $hSur)
                                    <div class="flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
                                        <div>
                                            <span class="font-semibold text-ink-950">{{ $hSur->komoditas?->nama }}</span>
                                            <span class="text-[11px] text-ink-600">(Panen {{ number_format($hSur->total_ketersediaan, 1, ',', '.') }} · Butuh {{ number_format($hSur->total_kebutuhan, 1, ',', '.') }})</span>
                                        </div>
                                        <span class="font-mono font-bold text-sawah-700">+{{ number_format($hSur->selisih, 1, ',', '.') }} {{ $satuanMap[$hSur->id_komoditas] ?? 'KG' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <p class="py-2 text-center text-xs text-ink-600">Tidak ada komoditas surplus pada periode ini.</p>
                    @endforelse
                </div>
            </details>
        </div>

        {{-- Tabel Matriks Rekapitulasi --}}
        <details class="overflow-hidden rounded-sm border border-paper-200">
            <summary class="cursor-pointer list-none bg-paper-100 px-3 py-3 text-sm font-semibold text-ink-900">
                Rincian lengkap neraca ({{ $items->count() }} baris) <span class="float-right font-normal text-ink-600">Klik untuk melihat ⌄</span>
            </summary>
            <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b-2 border-paper-300 bg-paper-100 font-mono text-[11px] uppercase tracking-wide text-ink-700">
                        <th class="py-2.5 px-3 text-center w-10">No</th>
                        <th class="py-2.5 px-3">Komoditas</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3 text-center">Satuan</th>
                        <th class="py-2.5 px-3 text-center">Periode</th>
                        <th class="py-2.5 px-3 text-right">Kebutuhan Kalkulasi</th>
                        <th class="py-2.5 px-3 text-right">Ketersediaan Survei</th>
                        <th class="py-2.5 px-3 text-right">Selisih</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3 text-right">Kecukupan</th>
                        <th class="py-2.5 px-3">Rekomendasi Tindak Lanjut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($items as $index => $item)
                        @php
                            $satuan = $satuanMap[$item->id_komoditas] ?? 'KG';
                        @endphp
                        <tr class="hover:bg-paper-100/40">
                            <td class="py-2 px-3 text-center font-mono text-ink-600">{{ $index + 1 }}</td>
                            <td class="py-2 px-3 font-semibold text-ink-950">{{ $item->komoditas?->nama }}</td>
                            <td class="py-2 px-3 text-ink-700 font-mono uppercase text-[10px]">
                                {{ str_replace('_', ' ', $item->komoditas?->kategori ?? '—') }}
                            </td>
                            <td class="py-2 px-3 text-center font-mono text-ink-600">{{ $satuan }}</td>
                            <td class="py-2 px-3 text-center font-mono text-ink-700">
                                {{ $namaBulanList[$item->bulan] ?? $item->bulan }}/{{ $item->tahun }}
                            </td>
                            <td class="py-2 px-3 text-right font-mono text-ink-900 font-medium">
                                {{ number_format($item->total_kebutuhan, 2, ',', '.') }}
                            </td>
                            <td class="py-2 px-3 text-right font-mono text-ink-900 font-medium">
                                {{ number_format($item->total_ketersediaan, 2, ',', '.') }}
                            </td>
                            <td class="py-2 px-3 text-right font-mono font-bold
                                  {{ $item->selisih < 0 ? 'text-merah-600' : ($item->selisih > 0 ? 'text-sawah-600' : 'text-ink-700') }}">
                                {{ $item->selisih > 0 ? '+' : '' }}{{ number_format($item->selisih, 2, ',', '.') }}
                            </td>
                            <td class="py-2 px-3 text-center">
                                <span class="rounded px-1.5 py-0.5 font-mono text-[9px] font-bold uppercase
                                      {{ $item->status_surplus_defisit === 'surplus' ? 'bg-sawah-100 text-sawah-600' : ($item->status_surplus_defisit === 'defisit' ? 'bg-merah-100 text-merah-700' : 'bg-padi-100 text-padi-600') }}">
                                    {{ $item->status_surplus_defisit }}
                                </span>
                            </td>
                            <td class="py-2 px-3 text-right font-mono text-ink-800">
                                {{ number_format($item->persentase_kecukupan, 1, ',', '.') }}%
                            </td>
                            <td class="py-2 px-3 text-[11px] text-ink-700">
                                @if ($item->status_surplus_defisit === 'defisit')
                                    @if ($idWilayah === $idWilayahAsal)
                                        <a href="{{ route('perencanaan.permintaan-pengadaan.index') }}" class="font-semibold text-merah-700 underline print:hidden">Lihat rekomendasi pengadaan</a>
                                    @else
                                        <span class="font-medium text-merah-700">Defisit desa pembanding</span>
                                    @endif
                                @elseif ($item->status_surplus_defisit === 'surplus')
                                    @if ($idWilayah === $idWilayahAsal)
                                        <a href="{{ route('perencanaan.neraca-komoditas.show', $item->id_hasil) }}" class="font-semibold text-sawah-700 underline print:hidden">Lihat tindak lanjut Gudang</a>
                                    @else
                                        <span class="text-sawah-700">Potensi tindak lanjut Gudang</span>
                                    @endif
                                @else
                                    <span class="text-ink-600">Kebutuhan Mandiri Tercukupi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-8 text-center text-ink-600">
                                Tidak ada data neraca komoditas yang memenuhi kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </details>

        {{-- Lembar Pengesahan / Tanda Tangan --}}
        <div class="mt-12 grid grid-cols-2 gap-8 border-t border-paper-300 pt-6 text-xs text-ink-800">
            <div>
                <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600">Catatan Perencanaan:</p>
                <p class="mt-1 text-[11px] text-ink-600 leading-relaxed">
                    Data neraca dihitung berdasarkan estimasi konsumsi standar gizi per kapita dan ketersediaan panen lokal yang dilaporkan melalui survei. Dokumen ini menjadi dasar keputusan pengadaan komoditas.
                </p>
            </div>
            <div class="text-right">
                <p class="font-mono text-xs text-ink-700">Desa {{ $wilayah?->nama ?? '' }}, {{ now()->format('d F Y') }}</p>
                <p class="mt-1 text-xs text-ink-900 font-semibold">Mengetahui &amp; Menyetujui,</p>
                <div class="my-8">
                    <span class="font-mono text-[10px] text-ink-600/60">[ Tanda Tangan Elektronik Sistem ]</span>
                </div>
                <p class="font-semibold text-ink-950 underline">{{ auth()->user()->nama ?? 'Manajer Koperasi' }}</p>
                <p class="text-[11px] text-ink-600 font-mono">Manajer Koperasi Desa Merah Putih</p>
            </div>
        </div>
    </div>
</x-layouts.app>
