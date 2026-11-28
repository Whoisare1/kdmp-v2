<x-layouts.app :title="$title" eyebrow="Gudang">
    {{-- CSS Khusus Cetak / Print --}}
    <style>
        @media print {
            aside, header, nav, .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            .print-only {
                display: block !important;
            }
            .card-box {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            table {
                font-size: 11px !important;
            }
            th, td {
                padding: 4px 6px !important;
            }
        }
        .print-only {
            display: none;
        }
    </style>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-4 no-print">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-sm border border-paper-400 bg-paper-50 px-3.5 py-2 text-xs font-semibold text-ink-800 hover:bg-paper-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Cetak / Export Laporan
        </button>
    </div>

    {{-- KOP Resmi Saat Dicetak --}}
    <div class="print-only mb-6 border-b-2 border-black pb-3 text-center">
        <h1 class="text-lg font-bold uppercase tracking-wider">{{ $entitas->nama_entitas ?? 'KOPERASI DESA MERAH PUTIH' }}</h1>
        <p class="text-xs text-gray-600">{{ $entitas->alamat ?? 'Unit Usaha Gudang & Persediaan Barang' }}</p>
        <h2 class="mt-2 text-base font-bold uppercase">KARTU STOK MUTASI PERSEDIAAN</h2>
        @if ($selectedBarang)
            <p class="text-xs font-semibold">Komoditas / Barang: {{ $selectedBarang->nama_barang }} ({{ $selectedBarang->satuanDasar->kode_satuan ?? 'Satuan Dasar' }})</p>
        @endif
        @if ($tanggalMulai || $tanggalSelesai)
            <p class="text-xs text-gray-600">
                Periode: {{ $tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('d M Y') : 'Awal' }} s/d {{ $tanggalSelesai ? \Carbon\Carbon::parse($tanggalSelesai)->format('d M Y') : 'Sekarang' }}
            </p>
        @endif
    </div>

    {{-- Form Filter Periode & Barang (Tanpa Input Gudang) --}}
    <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4 no-print">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-700">Pilih Barang</label>
                <select name="id_barang" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    <option value="">Semua barang</option>
                    @foreach ($barangList as $b)
                        <option value="{{ $b->id_barang }}" {{ request('id_barang') == $b->id_barang ? 'selected' : '' }}>
                            {{ $b->nama_barang }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-ink-700">Periode Bulan & Tahun</label>
                <div class="grid grid-cols-2 gap-2">
                    <select name="bulan" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-2 text-sm focus:border-merah-400 focus:outline-none">
                        <option value="">Bulan</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                    <input type="number" name="tahun" placeholder="Tahun" min="2000" max="2100" value="{{ request('tahun', now()->year) }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-ink-700">Rentang Tanggal Khusus</label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" title="Mulai" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-2 text-xs focus:border-merah-400 focus:outline-none">
                    <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" title="Selesai" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-2 text-xs focus:border-merah-400 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-ink-700">Jenis Mutasi</label>
                <select name="jenis_mutasi" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    <option value="">Semua jenis mutasi</option>
                    @foreach (['IN' => 'Barang Masuk', 'OUT' => 'Barang Keluar', 'ADJ_IN' => 'Penyesuaian Masuk', 'ADJ_OUT' => 'Penyesuaian Keluar'] as $value => $label)
                        <option value="{{ $value }}" {{ request('jenis_mutasi') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2 sm:col-span-2 lg:col-span-4 justify-end mt-2">
                <button type="submit" class="rounded-sm bg-ink-800 px-5 py-2 text-xs font-semibold text-white hover:bg-ink-900">
                    Terapkan Filter
                </button>
                <a href="{{ route('gudang.kartu-stok.export', request()->all()) }}" class="inline-flex items-center justify-center gap-1.5 rounded-sm border border-emerald-500 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800 shadow-sm hover:bg-emerald-100 print:hidden">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-1.5 rounded-sm border border-paper-400 bg-white px-3 py-2 text-xs font-medium text-ink-700 shadow-sm hover:border-merah-500 hover:text-merah-600 print:hidden">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak
                </button>
                <a href="{{ route('gudang.kartu-stok.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400 print:hidden">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Tabel Riwayat Mutasi Kartu Stok --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 card-box">
        <div class="border-b border-paper-300 px-4 py-3 no-print flex items-center justify-between">
            <h2 class="font-display text-base font-semibold text-ink-900">
                Riwayat Mutasi Persediaan
                @if ($selectedBarang)
                    — <span class="text-merah-600">{{ $selectedBarang->nama_barang }}</span>
                @endif
            </h2>
            <span class="text-xs text-ink-500 font-mono">Metode: Moving Average</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        @if (! $selectedBarang)
                            <th class="px-4 py-3 font-medium">Barang</th>
                        @endif
                        <th class="px-4 py-3 font-medium">Jenis</th>
                        <th class="px-4 py-3 font-medium">Keterangan / Dokumen Sumber</th>
                        <th class="px-4 py-3 text-right font-medium">Harga/HPP</th>
                        <th class="px-4 py-3 text-right font-medium">Masuk</th>
                        <th class="px-4 py-3 text-right font-medium">Keluar</th>
                        <th class="px-4 py-3 text-right font-medium">Saldo Qty</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai Mutasi</th>
                        <th class="px-4 py-3 text-right font-medium no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-ink-800">
                                {{ $item->tanggal?->format('d/m/Y') }}
                            </td>
                            @if (! $selectedBarang)
                                <td class="px-4 py-3 font-medium text-ink-900">
                                    {{ $item->barang->nama_barang ?? '-' }}
                                </td>
                            @endif
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ in_array($item->jenis_mutasi, ['IN', 'ADJ_IN']) ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $item->jenis_mutasi }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-ink-800 text-xs">
                                <span class="font-medium text-ink-900">{{ $item->deskripsi_referensi }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-ink-700 whitespace-nowrap">
                                Rp {{ number_format((float) $item->harga_satuan, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 whitespace-nowrap">
                                @if ((float) $item->qty_masuk > 0)
                                    +{{ rtrim(rtrim(number_format((float) $item->qty_masuk, 4, ',', '.'), '0'), ',') }}
                                    <span class="text-[10px] font-normal text-ink-500">{{ $item->barang->satuanDasar->kode_satuan ?? '' }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-semibold text-red-700 whitespace-nowrap">
                                @if ((float) $item->qty_keluar > 0)
                                    -{{ rtrim(rtrim(number_format((float) $item->qty_keluar, 4, ',', '.'), '0'), ',') }}
                                    <span class="text-[10px] font-normal text-ink-500">{{ $item->barang->satuanDasar->kode_satuan ?? '' }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-bold text-ink-900 whitespace-nowrap">
                                {{ rtrim(rtrim(number_format((float) $item->saldo_qty, 4, ',', '.'), '0'), ',') }}
                                <span class="text-[10px] font-normal text-ink-500">{{ $item->barang->satuanDasar->kode_satuan ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-medium text-ink-800 whitespace-nowrap">
                                Rp {{ number_format((float) $item->nilai_mutasi, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap no-print">
                                <a href="{{ route('gudang.kartu-stok.show', $item->id_kartu) }}" class="rounded-sm border border-paper-300 bg-white px-2.5 py-1 text-xs font-semibold text-ink-700 hover:border-merah-400 hover:text-merah-600">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $selectedBarang ? '9' : '10' }}" class="px-4 py-10 text-center text-sm text-ink-600/60">
                                Belum ada riwayat mutasi stok untuk kriteria yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Lembar Tanda Tangan Cetak --}}
    <div class="print-only mt-10 grid grid-cols-2 text-center text-xs">
        <div>
            <p>Dibuat Oleh,</p>
            <p class="font-semibold text-gray-700">Petugas Gudang Koperasi</p>
            <div class="mt-16 border-b border-black w-40 mx-auto"></div>
            <p class="mt-1">{{ auth()->user()->nama ?? 'Operator Gudang' }}</p>
        </div>
        <div>
            <p>Mengetahui / Menyetujui,</p>
            <p class="font-semibold text-gray-700">Manajer Koperasi</p>
            <div class="mt-16 border-b border-black w-40 mx-auto"></div>
            <p class="mt-1">( ............................................ )</p>
        </div>
    </div>

    {{-- Pagination (Hanya di layar monitor) --}}
    @if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-4 no-print">
            {{ $items->links() }}
        </div>
    @endif
</x-layouts.app>
