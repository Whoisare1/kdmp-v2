<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <div class="mx-auto max-w-5xl rounded-sm border border-paper-300 bg-paper-50 p-6">
        <form method="POST" action="{{ route('gudang.penerimaan.store') }}" class="space-y-5" id="formPenerimaan">
            @csrf

            <!-- Header Section -->
            <div>
                <h3 class="mb-3 text-sm font-semibold text-ink-700">Informasi Penerimaan</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Kode Penerimaan</label>
                        <input type="text" name="kode_penerimaan" value="{{ old('kode_penerimaan', 'PNR-' . now()->format('ymdHis')) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Tanggal Terima</label>
                        <input type="date" name="tanggal_terima" value="{{ old('tanggal_terima', now()->format('Y-m-d')) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Gudang</label>
                        <select name="id_gudang" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih gudang</option>
                            @foreach ($gudang as $item)
                                <option value="{{ $item->id_gudang }}" {{ old('id_gudang') == $item->id_gudang ? 'selected' : '' }}>
                                    {{ $item->nama_gudang }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Pihak</label>
                        <select name="id_pihak" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih pihak</option>
                            @foreach ($pihak as $item)
                                <option value="{{ $item->id_pihak }}" {{ old('id_pihak') == $item->id_pihak ? 'selected' : '' }}>
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink-700">Catatan</label>
                        <textarea name="catatan" rows="2" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">{{ old('catatan') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Item Section -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-ink-700">Daftar Barang Masuk</h3>
                    <button type="button" onclick="tambahBaris()" class="rounded-sm bg-sawah-500 px-3 py-1.5 text-xs font-medium text-paper-50 hover:bg-sawah-600">
                        + Tambah Barang
                    </button>
                </div>

                <div class="overflow-x-auto rounded-sm border border-paper-300">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                                <th class="px-3 py-2 font-medium">Barang</th>
                                <th class="px-3 py-2 font-medium">Qty</th>
                                <th class="px-3 py-2 font-medium">Harga Satuan</th>
                                <th class="px-3 py-2 text-right font-medium">Subtotal</th>
                                <th class="px-3 py-2 text-center font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="itemRows">
                            <tr class="barangRow border-b border-paper-200 last:border-0 bg-paper-50 hover:bg-paper-100">
                                <td class="px-3 py-2">
                                    <select name="items[0][id_barang]" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs focus:border-merah-400 focus:outline-none barangSelect">
                                        <option value="">Pilih barang</option>
                                        @foreach ($barang as $item)
                                            <option value="{{ $item->id_barang }}">{{ $item->kode_barang }} - {{ $item->nama_barang }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.0001" name="items[0][qty]" value="1" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs focus:border-merah-400 focus:outline-none qtyInput" min="0.0001">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.0001" name="items[0][harga_satuan]" value="0" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs focus:border-merah-400 focus:outline-none hargaInput" min="0">
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-xs subtotal">0</td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" onclick="hapusBaris(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 text-right text-sm">
                    <span class="font-medium text-ink-700">Total: </span>
                    <span class="font-mono text-ink-600" id="totalSubtotal">0</span>
                </div>
            </div>

            @if ($errors->any())
                <div class="rounded-sm border border-merah-500/40 bg-merah-50 px-3 py-2 text-sm text-merah-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('gudang.penerimaan.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">
                    Batal
                </a>
                <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                    Simpan Penerimaan
                </button>
            </div>
        </form>
    </div>

    <script>
        let rowCount = 1;

        function tambahBaris() {
            const tbody = document.getElementById('itemRows');
            const row = document.createElement('tr');
            row.className = 'barangRow border-b border-paper-200 last:border-0 bg-paper-50 hover:bg-paper-100';
            
            const barangOptions = `
                @foreach ($barang as $item)
                    <option value="{{ $item->id_barang }}">{{ $item->kode_barang }} - {{ $item->nama_barang }}</option>
                @endforeach
            `;

            row.innerHTML = `
                <td class="px-3 py-2">
                    <select name="items[${rowCount}][id_barang]" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs focus:border-merah-400 focus:outline-none barangSelect">
                        <option value="">Pilih barang</option>
                        ${barangOptions}
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.0001" name="items[${rowCount}][qty]" value="1" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs focus:border-merah-400 focus:outline-none qtyInput" min="0.0001">
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.0001" name="items[${rowCount}][harga_satuan]" value="0" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs focus:border-merah-400 focus:outline-none hargaInput" min="0">
                </td>
                <td class="px-3 py-2 text-right font-mono text-xs subtotal">0</td>
                <td class="px-3 py-2 text-center">
                    <button type="button" onclick="hapusBaris(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button>
                </td>
            `;

            tbody.appendChild(row);
            rowCount++;
            attachInputListeners();
        }

        function hapusBaris(btn) {
            btn.closest('tr').remove();
            hitungTotal();
        }

        function attachInputListeners() {
            document.querySelectorAll('.qtyInput, .hargaInput').forEach(input => {
                input.removeEventListener('input', hitungTotal);
                input.addEventListener('input', hitungTotal);
            });
        }

        function hitungTotal() {
            let total = 0;
            document.querySelectorAll('.barangRow').forEach(row => {
                const qty = parseFloat(row.querySelector('.qtyInput').value) || 0;
                const harga = parseFloat(row.querySelector('.hargaInput').value) || 0;
                const subtotal = qty * harga;
                row.querySelector('.subtotal').textContent = subtotal.toLocaleString('id-ID', { minimumFractionDigits: 2 });
                total += subtotal;
            });
            document.getElementById('totalSubtotal').textContent = total.toLocaleString('id-ID', { minimumFractionDigits: 2 });
        }

        // Attach listeners on page load
        attachInputListeners();
    </script>
</x-layouts.app>
