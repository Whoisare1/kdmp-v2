<x-layouts.app :title="$title" eyebrow="Penerimaan Barang">

```
<div class="grid gap-6 lg:grid-cols-3">

    {{-- Form Penerimaan --}}
    <div class="lg:col-span-2">

        @if ($errors->any())
            <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4">
                <h4 class="font-medium text-merah-900">Validasi Gagal:</h4>
                <ul class="mt-2 list-inside list-disc text-sm text-merah-800">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 rounded-sm border border-hijau-300 bg-hijau-50 p-4 text-sm text-hijau-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-sm border border-paper-300 bg-paper-50">

            <div class="border-b border-paper-300 px-4 py-3">
                <h3 class="font-semibold text-ink-900">Barang yang Dipesan</h3>
                <p class="mt-1 text-sm text-ink-600">
                    Masukkan jumlah barang yang layak dan tidak layak diterima.
                </p>
            </div>

            <div class="p-4">

                <form action="{{ route("$routeBase.store-grn", $pembelian) }}" method="POST">
                    @csrf

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-paper-100 text-left text-ink-700">
                                <tr>
                                    <th class="px-3 py-2">Barang</th>
                                    <th class="px-3 py-2 text-right">Qty Dipesan</th>
                                    <th class="px-3 py-2 text-right">Qty Layak</th>
                                    <th class="px-3 py-2 text-right">Qty Tidak Layak</th>
                                    <th class="px-3 py-2 text-right">Harga Satuan</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-paper-200">
                                @forelse ($pembelian->detail as $idx => $line)
                                    <tr>

                                        <td class="px-3 py-3">
                                            <div class="font-medium text-ink-900">
                                                {{ $line->barang->nama_barang ?? 'N/A' }}
                                            </div>

                                            <input
                                                type="hidden"
                                                name="items[{{ $idx }}][id_detail]"
                                                value="{{ $line->id_detail }}"
                                            >
                                        </td>

                                        <td class="px-3 py-3 text-right text-ink-700">
                                            {{ number_format($line->qty_dasar, 2, ',', '.') }}
                                        </td>

                                        <td class="px-3 py-3">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ $line->qty_dasar }}"
                                                name="items[{{ $idx }}][qty_layak]"
                                                class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-right"
                                                value="{{ old("items.$idx.qty_layak", $line->qty_dasar) }}"
                                                required
                                            >
                                        </td>

                                        <td class="px-3 py-3">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ $line->qty_dasar }}"
                                                name="items[{{ $idx }}][qty_tidak_layak]"
                                                class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-right"
                                                value="{{ old("items.$idx.qty_tidak_layak", 0) }}"
                                            >
                                        </td>

                                        <td class="px-3 py-3">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                name="items[{{ $idx }}][harga_satuan]"
                                                class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-right"
                                                value="{{ old("items.$idx.harga_satuan", $line->harga_satuan_input) }}"
                                                required
                                            >
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-sm text-ink-500">
                                            Tidak ada detail pembelian.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-sm border border-paper-300 bg-paper-100 p-4">
                        <h4 class="font-semibold text-ink-900">Instruksi Penerimaan</h4>

                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-ink-700">
                            <li>
                                Isi <strong>Qty Layak</strong> dengan jumlah barang yang normal/tidak cacat.
                            </li>
                            <li>
                                Isi <strong>Qty Tidak Layak</strong> dengan jumlah barang yang cacat atau rusak.
                            </li>
                            <li>
                                Barang tidak layak akan dibuatkan <strong>draft retur</strong> secara otomatis.
                            </li>
                            <li>
                                <strong>Harga Satuan</strong> dapat dikoreksi apabila berbeda dengan harga pada PO.
                            </li>
                            <li>
                                Barang dengan Qty Layak akan masuk ke stok dan diproses menggunakan perhitungan HPP yang berlaku.
                            </li>
                        </ul>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button
                            type="submit"
                            class="rounded-sm bg-merah-500 px-4 py-2 font-medium text-paper-50 hover:bg-merah-600"
                        >
                            ✓ Terima Barang
                        </button>

                        <a
                            href="{{ route("$routeBase.show", $pembelian) }}"
                            class="rounded-sm border border-paper-300 px-4 py-2 text-ink-700 hover:bg-paper-100"
                        >
                            Batal
                        </a>
                    </div>

                </form>

            </div>
        </div>
    </div>

    {{-- Informasi PO --}}
    <div class="lg:col-span-1">

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <h3 class="mb-3 font-semibold text-ink-900">Informasi PO</h3>

            <div class="space-y-3 text-sm">

                <div>
                    <div class="font-medium text-ink-600">Kode Pembelian</div>
                    <div class="mt-1 text-ink-900">
                        {{ $pembelian->kode_pembelian }}
                    </div>
                </div>

                <div>
                    <div class="font-medium text-ink-600">Supplier</div>
                    <div class="mt-1 text-ink-900">
                        {{ $pembelian->pihak->nama_pihak ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="font-medium text-ink-600">Tanggal Transaksi</div>
                    <div class="mt-1 text-ink-900">
                        {{ $pembelian->tanggal_transaksi?->format('d M Y') ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="font-medium text-ink-600">Total PO</div>
                    <div class="mt-1 font-semibold text-ink-900">
                        Rp {{ number_format($pembelian->total_pembelian, 2, ',', '.') }}
                    </div>
                </div>

            </div>
        </div>

        {{-- Alur GRN --}}
        <div class="mt-4 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <h3 class="mb-3 font-semibold text-ink-900">Alur Penerimaan</h3>

            <ol class="list-inside list-decimal space-y-2 text-sm text-ink-700">
                <li>Input Qty Layak dan Qty Tidak Layak.</li>
                <li>Koreksi harga satuan jika diperlukan.</li>
                <li>Klik <strong>Terima Barang</strong>.</li>
                <li>Sistem akan:
                    <ul class="mt-1 list-inside list-disc space-y-1 pl-4">
                        <li>Mencatat penerimaan barang.</li>
                        <li>Memasukkan barang layak ke stok.</li>
                        <li>Menghitung nilai stok sesuai mekanisme HPP.</li>
                        <li>Membuat draft retur untuk barang tidak layak.</li>
                        <li>Mengubah status pembelian menjadi diterima.</li>
                    </ul>
                </li>
            </ol>
        </div>

    </div>
</div>
```

</x-layouts.app>
