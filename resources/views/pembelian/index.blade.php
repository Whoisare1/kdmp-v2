<x-layouts.app :title="$title" eyebrow="Pembelian">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif
    @if (isset($hasModalAwal) && !$hasModalAwal)
        <div class="mb-5 rounded-sm border border-amber-300 bg-amber-50 p-4 shadow-xs">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-display text-sm font-semibold text-amber-900">Koperasi Belum Memiliki Modal Kas Awal</h4>
                    <p class="mt-1 text-xs leading-relaxed text-amber-800">
                        Koperasi baru wajib mencatat transaksi <strong>Setoran Simpanan Pokok</strong> anggota atau <strong>Saldo Awal Kas</strong> terlebih dahulu sebelum diizinkan membuat Order Pembelian.
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <a href="{{ route('keuangan.kas-transaksi.create') }}" class="inline-flex items-center gap-1.5 rounded-sm bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700 transition">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Catat Simpanan Pokok (Kas Masuk)
                        </a>
                        <a href="{{ route('akuntansi.saldo-awal.index') }}" class="inline-flex items-center gap-1.5 rounded-sm border border-amber-400 bg-white px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-50 transition">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            Input Saldo Awal Akuntansi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full gap-2 sm:max-w-md">
            <input name="q" value="{{ request('q') }}" placeholder="Cari kode atau supplier..." class="min-w-0 flex-1 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
            @if ($archiveActive)
                <input type="hidden" name="archive" value="1">
            @endif
            @foreach ($archiveFilters as $filter => $value)
                @if ($filter !== 'archive' && $value !== null)
                    <input type="hidden" name="{{ $filter }}" value="{{ $value }}">
                @endif
            @endforeach
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-emerald-500">Cari</button>
        </form>
        @if (isset($hasModalAwal) && !$hasModalAwal)
            <button type="button" disabled title="Wajib catat Setoran Simpanan Pokok atau Saldo Awal Kas terlebih dahulu"
                    class="inline-flex items-center justify-center rounded-sm bg-paper-300 px-4 py-2 text-sm font-medium text-paper-500 cursor-not-allowed">
                🔒 + Tambah Pembelian (Terkunci)
            </button>
        @else
            <a href="{{ route("$routeBase.create") }}" class="inline-flex items-center justify-center rounded-sm bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ Tambah Pembelian</a>
        @endif
    </div>

    <form method="GET" class="mb-4 rounded-sm border border-paper-300 bg-white p-4">
        <input type="hidden" name="archive" value="1">
        @if (request('q'))
            <input type="hidden" name="q" value="{{ request('q') }}">
        @endif
        <div class="flex flex-wrap items-end gap-3">
            <label class="text-sm">
                <span class="mb-1 block text-ink-700">Tahun</span>
                <input type="number" name="year" min="1900" max="9999" value="{{ $archiveFilters['year'] ?? '' }}" placeholder="Semua" class="w-28 rounded-sm border border-paper-300 bg-white px-3 py-2">
            </label>
            <label class="text-sm">
                <span class="mb-1 block text-ink-700">Bulan</span>
                <select name="month" class="rounded-sm border border-paper-300 bg-white px-3 py-2">
                    <option value="">Semua bulan</option>
                    @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $monthNumber => $monthName)
                        <option value="{{ $monthNumber }}" @selected(($archiveFilters['month'] ?? '') == $monthNumber)>{{ $monthName }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">
                <span class="mb-1 block text-ink-700">Tanggal</span>
                <input type="number" name="day" min="1" max="31" value="{{ $archiveFilters['day'] ?? '' }}" placeholder="Semua" class="w-24 rounded-sm border border-paper-300 bg-white px-3 py-2">
            </label>
            <button type="submit" class="rounded-sm border border-yellow-500 bg-yellow-50 px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">Cari</button>
            @if ($archiveActive || request('q'))
                <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Reset</a>
            @endif
        </div>
        <p class="mt-2 text-xs text-ink-600">Pilih tahun saja untuk rekap tahunan, atau gabungkan dengan bulan dan tanggal untuk melihat transaksi yang lebih spesifik.</p>
    </form>
    @foreach (['year' => 'Tahun', 'month' => 'Bulan', 'day' => 'Tanggal'] as $field => $label)
        @error($field)
            <p class="mb-2 text-sm text-merah-700">{{ $label }} tidak valid: {{ $message }}</p>
        @enderror
    @endforeach

    @if ($yearlySummary)
        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3">
                <p class="text-xs font-medium text-emerald-800">Jumlah pembelian tahun {{ $archiveFilters['year'] }}</p>
                <p class="mt-1 text-xl font-semibold text-emerald-950">{{ number_format((int) $yearlySummary->total_transaksi) }}</p>
            </div>
            <div class="rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3">
                <p class="text-xs font-medium text-emerald-800">Nilai pembelian tahunan</p>
                <p class="mt-1 text-xl font-semibold text-emerald-950">Rp {{ number_format((float) $yearlySummary->total_nilai, 2, ',', '.') }}</p>
            </div>
        </div>
    @endif
    <p class="mb-2 text-sm text-ink-600">{{ $archiveActive ? 'Hasil arsip' : '5 pembelian terbaru' }}@if ($archiveActive) — {{ $items->total() }} transaksi @endif</p>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-sm">
                <thead class="bg-paper-100 text-left text-ink-700">
                    <tr>
                        <th class="px-4 py-3">Kode</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Supplier</th><th class="px-4 py-3">Pembayaran</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-t border-paper-200 hover:bg-paper-100/70">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $item->kode_pembelian }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-700">{{ $item->tanggal_transaksi?->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $item->pihak?->nama_pihak ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-ink-900">{{ $item->sumber_pembelian === 'pengambilan_konsinyasi' ? 'Hutang Konsinyasi' : ucfirst($item->jenis_pembayaran) }}</span>
                                @if ($item->sumber_pembelian === 'pengambilan_konsinyasi')
                                    <span class="block text-xs text-sky-700">Pengambilan dari desa lain</span>
                                @endif
                                @if ($item->kasBank)
                                    <span class="block text-xs text-ink-500">{{ $item->kasBank->nama }}</span>
                                @elseif ($item->jenis_pembayaran === 'kredit' && $item->tgl_jatuh_tempo)
                                    <span class="block text-xs text-amber-700">Tempo: {{ $item->tgl_jatuh_tempo->format('d/m/y') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($item->total_pembelian, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 capitalize">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex rounded-sm px-2 py-0.5 text-xs font-medium {{ $item->status === 'disetujui' || $item->status === 'selesai' ? 'bg-emerald-50 text-emerald-800' : 'bg-paper-200 text-ink-700' }}">{{ ucfirst($item->status) }}</span>
                                    @if ($item->id_jurnal)
                                        <a href="{{ route('akuntansi.jurnal.show', $item->id_jurnal) }}" class="rounded bg-emerald-100 border border-emerald-300 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 hover:bg-emerald-200" title="Buka Jurnal Akuntansi">✓ Jurnal</a>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right"><a href="{{ route("$routeBase.show", $item) }}" class="font-medium text-emerald-700 hover:text-emerald-800">Lihat</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-ink-600">Belum ada pembelian. Gunakan tombol Tambah Pembelian untuk membuat transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($archiveActive)
        <div class="mt-4">{{ $items->links() }}</div>
    @endif
</x-layouts.app>