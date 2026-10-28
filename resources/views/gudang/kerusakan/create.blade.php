<x-layouts.app title="Tambah Kerusakan Barang" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <form action="{{ route('gudang.kerusakan.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Gudang</label>
                <select name="id_gudang" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                    @foreach ($gudang as $item)
                        <option value="{{ $item->id_gudang }}">{{ $item->nama_gudang }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Tanggal</label>
                <input type="date" name="tanggal" value="{{ now()->toDateString() }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Barang</label>
                <select name="id_barang" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                    @foreach ($barang as $item)
                        <option value="{{ $item->id_barang }}">{{ $item->nama_barang }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-700">Qty Rusak / Hilang / Susut</label>
                <input type="number" min="0" step="0.0001" name="qty" value="0" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-700">Jenis Kejadian</label>
            <select name="jenis_kejadian" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none" required>
                <option value="rusak">Rusak</option>
                <option value="susut">Susut</option>
                <option value="hilang">Hilang</option>
                <option value="kadaluarsa">Kadaluarsa</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-700">Keterangan</label>
            <input type="text" name="keterangan" placeholder="mis. hilang 2 karung / rusak karena benturan / kadaluarsa" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                Simpan Kerusakan
            </button>
            <a href="{{ route('gudang.kerusakan.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">
                Batal
            </a>
        </div>
    </form>
</x-layouts.app>
