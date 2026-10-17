<x-layouts.app title="Daftar Jurnal" eyebrow="Akuntansi">

    {{-- ===== Header ===== --}}
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-semibold text-ink-900">Buku Jurnal</h1>
            <p class="mt-1 text-sm text-ink-600">
                Daftar semua jurnal yang telah diposting. Jurnal dibuat otomatis dari transaksi atau manual via Kas Masuk/Keluar.
            </p>
        </div>
        @if($idEntitas && $totalPosted > 0)
            <div class="shrink-0 rounded border border-paper-200 bg-paper-50 px-4 py-2 text-right">
                <p class="text-xs text-ink-500">Total Jurnal POSTED</p>
                <p class="font-mono text-base font-bold text-ink-900">{{ number_format($totalPosted) }} entri</p>
            </div>
        @endif
    </div>

    @if(! $idEntitas)
        <div class="rounded border border-padi-500 bg-padi-100 px-4 py-3 text-sm text-ink-700">
            ⚠️ Harap pilih <strong>Entitas</strong> terlebih dahulu untuk melihat jurnal.
        </div>
    @else

    {{-- ===== Filter ===== --}}
    <form method="GET" action="{{ route('akuntansi.jurnal.index') }}"
          class="mb-5 flex flex-wrap items-end gap-3 rounded border border-paper-200 bg-paper-50 p-4">

        <div>
            <label class="block text-xs font-medium text-ink-700 mb-1">Tahun</label>
            <input type="number" name="tahun" value="{{ $tahun }}" min="2000" max="2099"
                   class="rounded border border-paper-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400 w-24">
        </div>

        <div>
            <label class="block text-xs font-medium text-ink-700 mb-1">Bulan</label>
            <select name="bulan" class="rounded border border-paper-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                <option value="">Semua</option>
                @foreach(range(1,12) as $b)
                    <option value="{{ $b }}" @selected($b == $bulan)>
                        {{ \Carbon\Carbon::create()->month($b)->locale('id')->monthName }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-ink-700 mb-1">Jenis Jurnal</label>
            <select name="jenis" class="rounded border border-paper-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                <option value="">Semua</option>
                @foreach(['OTOMATIS','MANUAL','SALDO_AWAL','PENYESUAIAN','PEMBALIK','PENUTUP'] as $j)
                    <option value="{{ $j }}" @selected($j === $jenis)>{{ $j }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-ink-700 mb-1">Cari</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="No. jurnal, keterangan, kode transaksi..."
                   class="w-full rounded border border-paper-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
        </div>

        <button type="submit"
                class="rounded bg-merah-500 px-4 py-1.5 text-sm font-medium text-white hover:bg-merah-600 transition">
            Tampilkan
        </button>
        <a href="{{ route('akuntansi.jurnal.index', ['tahun' => $tahun]) }}"
           class="rounded border border-paper-300 px-4 py-1.5 text-sm text-ink-600 hover:bg-paper-200 transition">
            Reset
        </a>
    </form>

    {{-- ===== Tabel ===== --}}
    @if($jurnal->isEmpty())
        <div class="rounded border border-dashed border-paper-300 bg-paper-50 py-12 text-center text-sm text-ink-400">
            Tidak ada jurnal untuk filter yang dipilih.
            @if(!$q && !$jenis)
                <br>Pastikan sudah ada transaksi yang diposting di tahun <strong>{{ $tahun }}</strong>.
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded border border-paper-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-paper-200 bg-paper-100 text-xs font-medium uppercase tracking-wide text-ink-500">
                        <th class="px-4 py-2.5 text-left">Tanggal</th>
                        <th class="px-4 py-2.5 text-left">No. Jurnal</th>
                        <th class="px-4 py-2.5 text-left">Kode</th>
                        <th class="px-4 py-2.5 text-left">Jenis</th>
                        <th class="px-4 py-2.5 text-left max-w-xs">Keterangan</th>
                        <th class="px-4 py-2.5 text-right">Total (Rp)</th>
                        <th class="px-4 py-2.5 text-center">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-100">
                    @foreach($jurnal as $j)
                        <tr class="hover:bg-paper-50 transition {{ $j->status === 'REVERSED' ? 'opacity-50' : '' }}">
                            <td class="px-4 py-2.5 font-mono text-xs text-ink-700 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($j->tanggal_jurnal)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs text-ink-800 whitespace-nowrap">
                                {{ $j->no_jurnal }}
                            </td>
                            <td class="px-4 py-2.5">
                                @if($j->kode_transaksi)
                                    <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-xs text-ink-700">
                                        {{ $j->kode_transaksi }}
                                    </span>
                                @else
                                    <span class="text-xs text-ink-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                @php
                                    $jenisColor = match($j->jenis_jurnal) {
                                        'OTOMATIS'   => 'bg-blue-50 text-blue-700',
                                        'MANUAL'     => 'bg-padi-100 text-padi-600',
                                        'SALDO_AWAL' => 'bg-paper-200 text-ink-600',
                                        'PENYESUAIAN'=> 'bg-amber-50 text-amber-700',
                                        'PEMBALIK'   => 'bg-merah-50 text-merah-600',
                                        'PENUTUP'    => 'bg-ink-800 text-paper-100',
                                        default      => 'bg-paper-100 text-ink-600',
                                    };
                                @endphp
                                <span class="rounded px-1.5 py-0.5 text-xs font-medium {{ $jenisColor }}">
                                    {{ $j->jenis_jurnal }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-ink-800 max-w-xs">
                                <span class="line-clamp-1">{{ $j->keterangan ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-ink-900 whitespace-nowrap">
                                {{ number_format($j->total_debet, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                @php
                                    $statusColor = match($j->status) {
                                        'POSTED'   => 'bg-sawah-100 text-sawah-700',
                                        'DRAFT'    => 'bg-padi-100 text-padi-600',
                                        'REVERSED' => 'bg-paper-200 text-ink-400 line-through',
                                        default    => 'bg-paper-100 text-ink-500',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                                    {{ $j->status }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <a href="{{ route('akuntansi.jurnal.show', $j->id_jurnal) }}"
                                   class="text-xs text-merah-500 hover:text-merah-700 hover:underline transition">
                                    Detail →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($jurnal->hasPages())
            <div class="mt-4">
                {{ $jurnal->links() }}
            </div>
        @endif

        <p class="mt-2 text-xs text-ink-400">
            Menampilkan {{ $jurnal->firstItem() }}–{{ $jurnal->lastItem() }} dari {{ $jurnal->total() }} jurnal
        </p>
    @endif

    @endif {{-- end if idEntitas --}}

</x-layouts.app>
