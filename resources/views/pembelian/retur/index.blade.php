<x-layouts.app :title="$title" eyebrow="Retur Pembelian">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-paper-300 bg-paper-100 p-4 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">{{ session('error') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('pembelian.pembelian.show', $pembelian) }}" class="inline-flex items-center rounded-sm border border-paper-300 px-3 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">
            ← Kembali ke Pembelian
        </a>
        <a href="{{ route('pembelian.pembelian.index') }}" class="inline-flex items-center rounded-sm border border-paper-300 px-3 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">
            Daftar Pembelian
        </a>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        <div class="md:col-span-2 xl:col-span-2 rounded-sm border border-paper-300 bg-paper-50 p-5 sm:p-6">
            <h2 class="mb-4 font-semibold text-ink-900">Retur {{ $pembelian->kode_pembelian }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-[760px] w-full text-sm">
                    <thead class="bg-paper-100 text-left"><tr><th class="px-3 py-2">Kode</th><th class="px-3 py-2">Tanggal Retur</th><th class="px-3 py-2">Penyelesaian</th><th class="px-3 py-2">Nilai</th><th class="px-3 py-2">Status</th><th class="px-3 py-2"></th></tr></thead>
                    <tbody>
                        @forelse ($returs as $retur)
                            <tr class="border-t border-paper-200">
                                <td class="px-3 py-2">{{ $retur->kode_retur }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $retur->tgl_retur?->format('d M Y') ?? 'N/A' }}</td>
                                <td class="px-3 py-2">{{ str_replace('_', ' ', ucfirst($retur->jenis_penyelesaian)) }}</td>
                                <td class="px-3 py-2">Rp {{ number_format($retur->total_nilai, 2, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ ucfirst($retur->status) }}</td>
                                <td class="px-3 py-2">
                                    @if ($retur->status === 'diajukan')
                                        <form method="POST" action="{{ route('pembelian.retur.approve', $retur) }}" class="flex gap-2">
                                            @csrf @method('PATCH')
                                            @if ($retur->jenis_penyelesaian === 'uang')
                                                <select name="id_kas_bank" required class="border border-paper-300 px-2 py-1"><option value="">Kas/Bank</option>@foreach (\App\Models\Master\KasBank::all() as $kas) <option value="{{ $kas->id_kas_bank }}">{{ $kas->nama_kas_bank }}</option>@endforeach</select>
                                            @endif
                                            <button class="text-xs font-medium text-merah-600">Setujui</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center">Belum ada retur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('pembelian.retur.store') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 sm:p-6 space-y-4">
            @csrf
            <input type="hidden" name="id_pembelian" value="{{ $pembelian->id_pembelian }}">
            <h2 class="font-semibold text-ink-900">Ajukan Retur</h2>
            <select name="jenis_penyelesaian" required class="w-full border border-paper-300 px-3 py-2"><option value="">Penyelesaian</option><option value="uang">Diganti uang</option><option value="potong_hutang">Potong hutang</option><option value="ganti_barang">Ganti barang</option></select>
            <textarea name="alasan" class="w-full border border-paper-300 px-3 py-2" placeholder="Alasan retur"></textarea>
            @foreach ($pembelian->detail as $idx => $line)
                <div class="border-t border-paper-200 pt-2 text-sm">
                    <div>{{ $line->barang->nama_barang ?? 'N/A' }}</div>
                    @php
                        $faktorDasar = (float) $line->faktor_konversi;
                        $hargaDasar = $faktorDasar > 0
                            ? (float) $line->harga_satuan_input / $faktorDasar
                            : (float) $line->harga_satuan_input;
                    @endphp
                    <div class="mt-1 text-xs text-ink-600">
                        HPP retur: Rp {{ number_format($hargaDasar, 2, ',', '.') }} / {{ $line->barang->satuanDasar?->kode_satuan ?? 'satuan dasar' }}
                    </div>
                    <input type="hidden" name="items[{{ $idx }}][id_barang]" value="{{ $line->id_barang }}">
                    <input type="number" name="items[{{ $idx }}][qty_dasar]" step="0.01" min="0" placeholder="Qty" class="mt-1 w-full border border-paper-300 px-2 py-1">
                    <div class="mt-1 rounded-sm bg-paper-100 px-2 py-1 text-xs text-ink-600">
                        HPP otomatis dari harga beli pembelian ini
                    </div>
                </div>
            @endforeach
            <button class="w-full rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50">Ajukan Retur</button>
        </form>
    </div>
</x-layouts.app>