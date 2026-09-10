<x-layouts.app :title="$title" eyebrow="Tambah Data">
    <div class="grid gap-6 lg:grid-cols-3">
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

            <form action="{{ route("$routeBase.store") }}" method="POST" class="space-y-4">
                @csrf

                <!-- Pilih Sumber -->
                <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Pilih Sumber Pembelian</h3>
                    <label class="flex items-center gap-2 mb-2">
                        <input type="radio" name="source" value="from_pr" checked onchange="toggleForm()" class="rounded">
                        Dari Permintaan Pengadaan (PR)
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="source" value="quick_purchase" onchange="toggleForm()" class="rounded">
                        Nota Pembelian Petani (Quick Purchase)
                    </label>
                </div>

                <!-- Form Dari PR -->
                <div id="from_pr_section" class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Data Pembelian dari PR</h3>
                    <label class="block text-sm">
                        <span class="text-ink-700 font-medium">Permintaan Pengadaan *</span>
                        <select name="id_permintaan" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                            <option value="">-- Pilih PR yang disetujui --</option>
                            @foreach ($prs as $pr)
                                <option value="{{ $pr->id_permintaan }}">
                                    {{ $pr->kode_permintaan ?? 'N/A' }} - {{ $pr->unitUsaha->nama_unit_usaha ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <!-- Form Quick Purchase -->
                <div id="quick_purchase_section" class="rounded-sm border border-paper-300 bg-paper-50 p-4" style="display: none;">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Data Nota Pembelian Petani</h3>
                    <div class="grid gap-4 grid-cols-2">
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Unit Usaha *</span>
                            <select name="id_unit_usaha" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Unit --</option>
                                @foreach ($barangs->groupBy('id_unit_usaha') as $idUnit => $unitBarang)
                                    <option value="{{ $idUnit }}">{{ $unitBarang->first()->unitUsaha->nama_unit_usaha ?? 'N/A' }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Gudang *</span>
                            <select name="id_gudang" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Gudang --</option>
                                @foreach (\App\Models\Master\Gudang::all() as $gudang)
                                    <option value="{{ $gudang->id_gudang }}">{{ $gudang->nama_gudang }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-sm border border-paper-300">
                        <div class="flex items-center justify-between bg-paper-100 px-3 py-2">
                            <h4 class="text-sm font-semibold text-ink-900">Daftar Barang</h4>
                            <button type="button" onclick="addQuickPurchaseRow()" class="rounded-sm bg-merah-500 px-2 py-1 text-xs font-medium text-paper-50 hover:bg-merah-600">
                                + Tambah Baris
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-paper-100 text-left text-ink-700">
                                    <tr>
                                        <th class="px-3 py-2">Barang</th>
                                        <th class="px-3 py-2">Satuan</th>
                                        <th class="px-3 py-2">Qty</th>
                                        <th class="px-3 py-2">Harga Satuan</th>
                                        <th class="px-3 py-2">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="quick_purchase_rows">
                                    <tr class="quick-purchase-row">
                                        <td class="px-3 py-2">
                                            <select name="items[0][id_barang]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                                <option value="">-- Pilih Barang --</option>
                                                @foreach ($barangs as $barang)
                                                    <option value="{{ $barang->id_barang }}">{{ $barang->nama_barang }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <select name="items[0][id_satuan]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                                <option value="">-- Satuan --</option>
                                                @foreach ($satuans as $satuan)
                                                    <option value="{{ $satuan->id }}">{{ $satuan->kode_satuan }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0" name="items[0][qty_dasar]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0" name="items[0][harga_satuan]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            <button type="button" onclick="removeQuickPurchaseRow(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Umum -->
                <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Data Supplier dan Pembayaran</h3>
                    <div class="space-y-3">
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Supplier *</span>
                            <select name="id_pihak" required class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Supplier --</option>
                                @foreach ($pihaks as $pihak)
                                    <option value="{{ $pihak->id_pihak }}">{{ $pihak->nama_pihak }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Jenis Pembayaran *</span>
                            <select name="jenis_pembayaran" required onchange="togglePaymentFields()" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <option value="">-- Pilih Jenis --</option>
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer</option>
                                <option value="kredit">Kredit</option>
                            </select>
                        </label>
                        <div id="kas_bank_section">
                            <label class="block text-sm">
                                <span class="text-ink-700 font-medium">Kas/Bank *</span>
                                <select name="id_kas_bank" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                    <option value="">-- Pilih Kas/Bank --</option>
                                    @foreach ($kasbanks as $kb)
                                        <option value="{{ $kb->id_kas_bank }}">{{ $kb->nama_kas_bank }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div id="jatuh_tempo_section" style="display: none;">
                            <label class="block text-sm">
                                <span class="text-ink-700 font-medium">Tanggal Jatuh Tempo *</span>
                                <input type="date" name="tgl_jatuh_tempo" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 font-medium text-paper-50 hover:bg-merah-600">
                        Lanjutkan
                    </button>
                    <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 px-4 py-2 text-ink-700 hover:bg-paper-100">
                        Batal
                    </a>
                </div>
            </form>
        </div>

        <div class="lg:col-span-1">
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                <h3 class="mb-3 font-semibold text-ink-900">Alur Pembelian</h3>
                <ol class="list-inside list-decimal space-y-1 text-sm text-ink-700">
                    <li><strong>Draft:</strong> Form dibuat</li>
                    <li><strong>Disetujui:</strong> Manager approve</li>
                    <li><strong>Diterima:</strong> Barang tiba (GRN)</li>
                    <li><strong>Selesai:</strong> Pembayaran selesai</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            const source = document.querySelector('input[name="source"]:checked').value;
            document.getElementById('from_pr_section').style.display = source === 'from_pr' ? 'block' : 'none';
            document.getElementById('quick_purchase_section').style.display = source === 'quick_purchase' ? 'block' : 'none';
        }

        function togglePaymentFields() {
            const jenis = document.querySelector('select[name="jenis_pembayaran"]').value;
            document.getElementById('kas_bank_section').style.display = jenis === 'kredit' ? 'none' : 'block';
            document.getElementById('jatuh_tempo_section').style.display = jenis === 'kredit' ? 'block' : 'none';
        }

        function addQuickPurchaseRow() {
            const tableBody = document.getElementById('quick_purchase_rows');
            const rows = tableBody.querySelectorAll('.quick-purchase-row');
            const index = rows.length;

            const row = document.createElement('tr');
            row.className = 'quick-purchase-row';
            row.innerHTML = `
                <td class="px-3 py-2">
                    <select name="items[${index}][id_barang]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($barangs as $barang)
                            <option value="{{ $barang->id_barang }}">{{ $barang->nama_barang }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="px-3 py-2">
                    <select name="items[${index}][id_satuan]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                        <option value="">-- Satuan --</option>
                        @foreach ($satuans as $satuan)
                            <option value="{{ $satuan->id }}">{{ $satuan->kode_satuan }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" min="0" name="items[${index}][qty_dasar]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" min="0" name="items[${index}][harga_satuan]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                </td>
                <td class="px-3 py-2">
                    <button type="button" onclick="removeQuickPurchaseRow(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button>
                </td>
            `;
            tableBody.appendChild(row);
        }

        function removeQuickPurchaseRow(button) {
            const row = button.closest('.quick-purchase-row');
            if (document.querySelectorAll('.quick-purchase-row').length > 1) {
                row.remove();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            toggleForm();
            togglePaymentFields();
        });
    </script>
</x-layouts.app>
