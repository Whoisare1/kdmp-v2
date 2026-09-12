<x-layouts.app :title="$title" eyebrow="Detail Pembelian">

```
<div class="grid gap-6 lg:grid-cols-3">

    {{-- Informasi Utama --}}
    <div class="lg:col-span-2">

        @if (session('success'))
            <div class="mb-4 rounded-sm border border-paper-300 bg-paper-100 p-4 text-sm text-ink-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="mb-4 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-semibold text-ink-900">
                        {{ $item->kode_pembelian }}
                    </h2>

                    <p class="mt-1 text-sm text-ink-600">
                        {{ $title }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-sm bg-paper-200 px-2 py-1 text-xs font-medium text-ink-800">
                        {{ ucfirst($item->status) }}
                    </span>

                    <span class="rounded-sm px-2 py-1 text-xs font-medium {{ $item->status_posting === 'T'
                        ? 'bg-paper-200 text-ink-800'
                        : 'bg-merah-50 text-merah-800' }}">
                        Posting: {{ $item->status_posting === 'T' ? 'Ya' : 'Belum' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Informasi Pembelian --}}
        <div class="mb-4 rounded-sm border border-paper-300 bg-paper-50">

            <div class="border-b border-paper-300 px-4 py-3">
                <h3 class="font-semibold text-ink-900">
                    Informasi Pembelian
                </h3>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-2">

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Tanggal Transaksi
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->tanggal_transaksi?->format('d M Y') ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Supplier
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->pihak->nama_pihak ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Unit Usaha
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->unitUsaha->nama_unit_usaha ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Gudang
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ $item->gudang->nama_gudang ?? 'N/A' }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Jenis Pembayaran
                    </div>

                    <div class="mt-1 text-sm text-ink-900">
                        {{ ucfirst($item->jenis_pembayaran) }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-ink-600">
                        Total Pembelian
                    </div>

                    <div class="mt-1 font-semibold text-ink-900">
                        Rp {{ number_format($item->total_pembelian, 2, ',', '.') }}
                    </div>
                </div>

            </div>
        </div>

        {{-- Detail Pembelian --}}
        <div class="mb-4 rounded-sm border border-paper-300 bg-paper-50">

            <div class="border-b border-paper-300 px-4 py-3">
                <h3 class="font-semibold text-ink-900">
                    Detail Pembelian
                </h3>
            </div>

            <div class="overflow-x-auto p-4">
                <table class="min-w-full text-sm">

                    <thead class="bg-paper-100 text-left text-ink-700">
                        <tr>
                            <th class="px-3 py-2">Barang</th>
                            <th class="px-3 py-2 text-right">Qty</th>
                            <th class="px-3 py-2 text-right">Harga Satuan</th>
                            <th class="px-3 py-2 text-right">Subtotal</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-paper-200">

                        @forelse ($item->detail as $line)
                            <tr>

                                <td class="px-3 py-3 text-ink-900">
                                    {{ $line->barang->nama_barang ?? 'N/A' }}
                                </td>

                                <td class="px-3 py-3 text-right text-ink-700">
                                    {{ number_format($line->qty_dasar, 2, ',', '.') }}
                                </td>

                                <td class="px-3 py-3 text-right text-ink-700">
                                    Rp {{ number_format($line->harga_satuan_input, 2, ',', '.') }}
                                </td>

                                <td class="px-3 py-3 text-right font-medium text-ink-900">
                                    Rp {{ number_format($line->subtotal, 2, ',', '.') }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-sm text-ink-500">
                                    Tidak ada detail pembelian.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>
        </div>

        {{-- Status / Instruksi --}}
        @if ($item->status === 'draft')

            <div class="rounded-sm border border-paper-300 bg-paper-100 p-4">
                <h4 class="font-semibold text-ink-900">
                    Pembelian masih Draft
                </h4>

                <p class="mt-1 text-sm text-ink-700">
                    Pembelian perlu disetujui terlebih dahulu sebelum barang dapat diterima.
                </p>
            </div>

        @elseif ($item->status === 'disetujui')

            <div class="rounded-sm border border-paper-300 bg-paper-100 p-4">
                <h4 class="font-semibold text-ink-900">
                    Pembelian Siap Diterima
                </h4>

                <p class="mt-1 text-sm text-ink-700">
                    Pembelian sudah disetujui. Silakan buat GRN ketika barang telah diterima.
                </p>
            </div>

        @elseif ($item->status === 'diterima')

            <div class="rounded-sm border border-paper-300 bg-paper-100 p-4">
                <h4 class="font-semibold text-ink-900">
                    Barang Sudah Diterima
                </h4>

                <p class="mt-1 text-sm text-ink-700">
                    Penerimaan barang telah dicatat dan stok telah diperbarui.
                </p>
            </div>

        @endif

    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-1">

        {{-- Aksi --}}
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">

            <h3 class="mb-3 font-semibold text-ink-900">
                Aksi
            </h3>

            <div class="space-y-2">

                @if ($item->status === 'draft')
                    <form
                        action="{{ route("$routeBase.approve", $item) }}"
                        method="POST"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="w-full rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600"
                        >
                            ✓ Setujui
                        </button>
                    </form>
                @endif

                @if ($item->status === 'disetujui')
                    <a
                        href="{{ route("$routeBase.create-grn", $item) }}"
                        class="block w-full rounded-sm bg-merah-500 px-4 py-2 text-center text-sm font-medium text-paper-50 hover:bg-merah-600"
                    >
                        📦 Buat GRN
                    </a>
                @endif

                @if (in_array($item->status, ['disetujui', 'diterima', 'selesai']))
                    <a
                        href="{{ route("$routeBase.show-retur", $item) }}"
                        class="block w-full rounded-sm border border-paper-300 px-4 py-2 text-center text-sm font-medium text-ink-700 hover:bg-paper-100"
                    >
                        ↩️ Lihat Retur
                    </a>
                @endif

                <a
                    href="{{ route("$routeBase.edit", $item) }}"
                    class="block w-full rounded-sm border border-paper-300 px-4 py-2 text-center text-sm font-medium text-ink-700 hover:bg-paper-100"
                >
                    Edit
                </a>

                <a
                    href="{{ route("$routeBase.index") }}"
                    class="block w-full rounded-sm border border-paper-300 px-4 py-2 text-center text-sm font-medium text-ink-700 hover:bg-paper-100"
                >
                    Kembali
                </a>

            </div>
        </div>

        {{-- Informasi Jurnal --}}
        @if ($item->id_jurnal)

            <div class="mt-4 rounded-sm border border-paper-300 bg-paper-50 p-4">

                <h3 class="mb-3 font-semibold text-ink-900">
                    Jurnal Pembelian
                </h3>

                <div class="space-y-3 text-sm">

                    <div>
                        <div class="font-medium text-ink-600">
                            No Jurnal
                        </div>

                        <div class="mt-1 text-ink-900">
                            {{ $item->jurnal->no_jurnal ?? 'N/A' }}
                        </div>
                    </div>

                    <div>
                        <div class="font-medium text-ink-600">
                            Status
                        </div>

                        <div class="mt-1 text-ink-900">
                            {{ $item->jurnal->status ?? 'N/A' }}
                        </div>
                    </div>

                    <div>
                        <div class="font-medium text-ink-600">
                            Tanggal
                        </div>

                        <div class="mt-1 text-ink-900">
                            {{ $item->jurnal?->tanggal_jurnal?->format('d M Y') ?? 'N/A' }}
                        </div>
                    </div>

                </div>
            </div>

        @endif

    </div>
</div>
```

</x-layouts.app>
