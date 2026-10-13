<x-layouts.app :title="$title" eyebrow="M5 Pembelian / Retur">
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-ink-600">Pilih transaksi pembelian yang sudah disetujui.</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Ajukan Retur Pembelian</h1>
        </div>
        <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-50">Kembali ke Retur</a>
    </div>

    <form method="GET" action="{{ route("$routeBase.create") }}" class="mb-5 flex flex-col gap-3 rounded-sm border border-paper-300 bg-paper-50 p-4 sm:flex-row sm:items-end">
        <label class="min-w-0 flex-1 text-sm">
            <span class="mb-1 block font-medium text-ink-700">Pembelian</span>
            <select name="id_pembelian" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                <option value="">Pilih pembelian yang disetujui</option>
                @foreach ($purchases as $purchaseOption)
                    <option value="{{ $purchaseOption->id_pembelian }}" @selected($pembelian?->id_pembelian === $purchaseOption->id_pembelian)>
                        {{ $purchaseOption->kode_pembelian }} - {{ $purchaseOption->pihak?->nama ?? 'Supplier' }} - {{ $purchaseOption->tanggal_transaksi?->format('d M Y') }}
                    </option>
                @endforeach
            </select>
        </label>
        <button class="rounded-sm border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Tampilkan barang</button>
    </form>

    @if ($pembelian)
        <div class="mb-4 grid gap-3 sm:grid-cols-3">
            <div class="border-l-4 border-emerald-600 bg-emerald-50 px-4 py-3">
                <p class="text-xs font-medium uppercase text-emerald-800">Kode Pembelian</p>
                <p class="mt-1 font-semibold text-emerald-950">{{ $pembelian->kode_pembelian }}</p>
            </div>
            <div class="border-l-4 border-emerald-600 bg-emerald-50 px-4 py-3">
                <p class="text-xs font-medium uppercase text-emerald-800">Supplier</p>
                <p class="mt-1 font-semibold text-emerald-950">{{ $pembelian->pihak?->nama ?? '-' }}</p>
            </div>
            <div class="border-l-4 border-emerald-600 bg-emerald-50 px-4 py-3">
                <p class="text-xs font-medium uppercase text-emerald-800">Tanggal Transaksi</p>
                <p class="mt-1 font-semibold text-emerald-950">{{ $pembelian->tanggal_transaksi?->format('d M Y') ?? '-' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route("$routeBase.store") }}" class="space-y-5">
            @csrf
            <input type="hidden" name="id_pembelian" value="{{ $pembelian->id_pembelian }}">
            <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
                <div class="border-b border-paper-300 px-4 py-3">
                    <h2 class="font-semibold text-ink-900">Barang dan Kuantitas Retur</h2>
                    <p class="mt-1 text-sm text-ink-600">Isi jumlah pada barang yang akan diretur. Jumlah retur dibatasi oleh sisa yang belum diretur.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[860px] w-full text-sm">
                        <thead class="bg-emerald-50 text-left text-emerald-950">
                            <tr>
                                <th class="px-4 py-3">Barang</th>
                                <th class="px-4 py-3 text-right">Dibeli</th>
                                <th class="px-4 py-3 text-right">Diajukan</th>
                                <th class="px-4 py-3 text-right">Sudah Diretur</th>
                                <th class="px-4 py-3 text-right">Sisa</th>
                                <th class="px-4 py-3 text-right">Qty Retur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pembelian->returItems as $index => $line)
                                <tr class="border-t border-paper-200">
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-ink-900">{{ $line->barang?->nama_barang ?? '-' }}</span>
                                        <span class="mt-0.5 block text-xs text-ink-600">HPP Rp {{ number_format((float) $line->hpp_retur_dasar, 2, ',', '.') }} / {{ $line->barang?->satuanDasar?->kode_satuan ?? 'unit' }}</span>
                                        <input type="hidden" name="items[{{ $index }}][id_barang]" value="{{ $line->id_barang }}">
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $line->qty_dasar, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $line->qty_diajukan, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $line->qty_diretur, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $line->qty_sisa_retur > 0 ? 'text-emerald-800' : 'text-ink-500' }}">{{ number_format((float) $line->qty_sisa_retur, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" name="items[{{ $index }}][qty_dasar]" step="0.0001" min="0" max="{{ $line->qty_sisa_retur }}" value="{{ old("items.$index.qty_dasar") }}" @disabled($line->qty_sisa_retur <= 0) class="w-28 rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-right disabled:bg-paper-100 disabled:text-ink-500" aria-label="Jumlah retur {{ $line->barang?->nama_barang }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                <label class="block text-sm">
                    <span class="mb-1 block font-medium text-ink-700">Alasan retur</span>
                    <textarea name="alasan" rows="3" maxlength="2000" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2" placeholder="Tuliskan alasan pengembalian barang">{{ old('alasan') }}</textarea>
                </label>
                <div class="space-y-3 rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium text-ink-700">Catatan penyelesaian</span>
                        <select name="jenis_penyelesaian" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                            <option value="">Pilih catatan</option>
                            <option value="uang" @selected(old('jenis_penyelesaian') === 'uang')>Pengembalian uang</option>
                            <option value="potong_hutang" @selected(old('jenis_penyelesaian') === 'potong_hutang')>Potong hutang</option>
                            <option value="ganti_barang" @selected(old('jenis_penyelesaian') === 'ganti_barang')>Ganti barang</option>
                        </select>
                    </label>
                    <button class="w-full rounded-sm bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Simpan Pengajuan Retur</button>
                </div>
            </div>
        </form>
    @elseif ($purchases->isEmpty())
        <div class="border border-dashed border-paper-300 bg-paper-50 px-4 py-12 text-center">
            <p class="font-medium text-ink-800">Belum ada pembelian yang dapat diretur.</p>
            <p class="mt-1 text-sm text-ink-600">Barang akan muncul setelah pembelian disetujui.</p>
        </div>
    @endif
</x-layouts.app>
