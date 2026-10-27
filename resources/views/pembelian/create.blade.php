<x-layouts.app :title="$title" eyebrow="Tambah Data">
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            {{ session('error') }}
        </div>
    @endif
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        <div class="md:col-span-2 xl:col-span-2">
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
                        <input type="radio" name="source" value="from_pr" @checked(old('source', $prs->isNotEmpty() ? 'from_pr' : 'quick_purchase') === 'from_pr') onchange="toggleForm()" class="rounded">
                        Dari Permintaan Pengadaan (PR)
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="source" value="quick_purchase" @checked(old('source', $prs->isNotEmpty() ? 'from_pr' : 'quick_purchase') === 'quick_purchase') onchange="toggleForm()" class="rounded">
                        Nota Pembelian Petani (Quick Purchase)
                    </label>
                </div>

                <!-- Form Dari PR -->
                <div id="from_pr_section" class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Data Pembelian dari PR</h3>
                    <label class="block text-sm">
                        <span class="text-ink-700 font-medium">Permintaan Pengadaan *</span>
                        <select id="id_permintaan" name="id_permintaan" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
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
                    <div class="grid gap-4 sm:grid-cols-2">
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
                            <button type="button" onclick="addQuickPurchaseRow()" class="rounded-sm bg-emerald-600 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-700">
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
                                            <select name="items[0][id_barang]" onchange="updateQuickPurchaseUnits(this)" class="quick-purchase-barang w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                                <option value="">-- Pilih Barang --</option>
                                                @foreach ($barangs as $barang)
                                                    <option value="{{ $barang->id_barang }}" data-base-unit="{{ $barang->id_satuan_dasar }}" data-base-code="{{ $barang->satuanDasar?->kode_satuan }}" data-conversions="{{ $barang->konversiSatuan->map(fn ($conversion) => ['id' => (int) $conversion->id_satuan, 'code' => $conversion->satuan?->kode_satuan, 'factor' => (float) $conversion->faktor_ke_dasar, 'default' => (bool) $conversion->is_default_beli])->values()->toJson() }}">{{ $barang->nama_barang }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <select name="items[0][id_satuan]" required disabled class="quick-purchase-unit w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                                <option value="">-- Pilih Barang dahulu --</option>
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
                                <option value="tunai" @selected(old('jenis_pembayaran') === 'tunai')>Tunai</option>
                                <option value="transfer" @selected(old('jenis_pembayaran') === 'transfer')>Transfer</option>
                                <option value="kredit" @selected(old('jenis_pembayaran') === 'kredit')>Kredit</option>
                            </select>
                        </label>
                        <div id="sumber_uang_section" style="display: none;">
                            <label class="block text-sm">
                                <span class="text-ink-700 font-medium">Sumber Uang (Kas / Bank) *</span>
                                <select name="id_kas_bank" id="id_kas_bank" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                    <option value="">-- Pilih Sumber Kas / Bank --</option>
                                    @foreach ($kasBanks as $kasBank)
                                        <option value="{{ $kasBank->id_kas_bank }}" data-jenis="{{ $kasBank->jenis }}" @selected(old('id_kas_bank') == $kasBank->id_kas_bank)>
                                            {{ $kasBank->nama }} ({{ ucfirst($kasBank->jenis) }}{{ $kasBank->no_rekening ? ' - ' . $kasBank->no_rekening : '' }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-ink-500">Pilih akun kas atau rekening bank yang digunakan untuk mendanai pembayaran.</p>
                            </label>
                        </div>
                        <div id="jatuh_tempo_section" style="display: none;">
                            <label class="block text-sm">
                                <span class="text-ink-700 font-medium">Tanggal Jatuh Tempo *</span>
                                <input type="date" name="tgl_jatuh_tempo" min="{{ now()->format('Y-m-d') }}" value="{{ old('tgl_jatuh_tempo') }}" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                <p class="mt-1 text-xs text-ink-500">Wajib diisi untuk pembelian dengan tempo/kredit.</p>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-sm bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">
                        Lanjutkan
                    </button>
                    <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 px-4 py-2 text-ink-700 hover:bg-paper-100">
                        Batal
                    </a>
                </div>
            </form>
        </div>

        <div class="md:col-span-2 xl:col-span-1">
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                <h3 class="mb-3 font-semibold text-ink-900">Alur Pembelian</h3>
                <ol class="list-inside list-decimal space-y-1 text-sm text-ink-700">
                    <li><strong>Draft:</strong> Pembelian dicatat</li>
                    <li><strong>Disetujui:</strong> Status diperbarui di modul Pembelian</li>
                    <li><strong>Retur:</strong> Dicatat terpisah di menu Retur Pembelian</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            const source = document.querySelector('input[name="source"]:checked').value;
            document.getElementById('from_pr_section').style.display = source === 'from_pr' ? 'block' : 'none';
            document.getElementById('quick_purchase_section').style.display = source === 'quick_purchase' ? 'block' : 'none';
            document.querySelectorAll('#from_pr_section select, #from_pr_section input').forEach((field) => field.disabled = source !== 'from_pr');
            document.querySelectorAll('#quick_purchase_section select, #quick_purchase_section input').forEach((field) => field.disabled = source !== 'quick_purchase');
        }

        function togglePaymentFields() {
            const jenisSelect = document.querySelector('select[name="jenis_pembayaran"]');
            const jenis = jenisSelect ? jenisSelect.value : '';
            const isCredit = jenis === 'kredit';
            const isCashOrBank = jenis === 'tunai' || jenis === 'transfer';

            const jatuhTempoSection = document.getElementById('jatuh_tempo_section');
            const jatuhTempoInput = document.querySelector('input[name="tgl_jatuh_tempo"]');
            const sumberUangSection = document.getElementById('sumber_uang_section');
            const sumberUangSelect = document.getElementById('id_kas_bank');

            // Tanggal Jatuh Tempo (Hanya saat Kredit)
            if (jatuhTempoSection && jatuhTempoInput) {
                jatuhTempoSection.style.display = isCredit ? 'block' : 'none';
                jatuhTempoInput.disabled = !isCredit;
                jatuhTempoInput.required = isCredit;
                if (!isCredit) {
                    jatuhTempoInput.value = '';
                }
            }

            // Sumber Uang (Kas / Bank)
            if (sumberUangSection && sumberUangSelect) {
                if (isCashOrBank) {
                    sumberUangSection.style.display = 'block';
                    sumberUangSelect.disabled = false;
                    sumberUangSelect.required = true;

                    // Filter opsi kas vs bank sesuai jenis pembayaran
                    let hasMatchingSelected = false;
                    const options = sumberUangSelect.querySelectorAll('option');
                    options.forEach((opt, idx) => {
                        if (idx === 0) return; // placeholder
                        const optJenis = opt.dataset.jenis;
                        const match = (jenis === 'tunai' && optJenis === 'kas') || (jenis === 'transfer' && optJenis === 'bank');
                        if (match) {
                            opt.style.display = '';
                            opt.disabled = false;
                            if (opt.value === sumberUangSelect.value) {
                                hasMatchingSelected = true;
                            }
                        } else {
                            opt.style.display = 'none';
                            opt.disabled = true;
                        }
                    });

                    // Jika belum ada yang cocok atau baru berganti jenis, pilih opsi pertama yang valid
                    if (!hasMatchingSelected) {
                        const firstValid = Array.from(options).find((opt, idx) => idx > 0 && !opt.disabled);
                        if (firstValid) {
                            sumberUangSelect.value = firstValid.value;
                        }
                    }
                } else {
                    // Jika kredit atau belum dipilih
                    sumberUangSection.style.display = 'none';
                    sumberUangSelect.disabled = true;
                    sumberUangSelect.required = false;
                    sumberUangSelect.value = '';
                }
            }
        }

        function updateQuickPurchaseUnits(barangSelect) {
            const row = barangSelect.closest('.quick-purchase-row');
            const unitSelect = row.querySelector('.quick-purchase-unit');
            const selectedBarang = barangSelect.selectedOptions[0];
            const placeholder = new Option('-- Pilih Satuan --', '');
            unitSelect.replaceChildren(placeholder);

            if (!barangSelect.value) {
                unitSelect.disabled = true;
                return;
            }

            let conversions = JSON.parse(selectedBarang.dataset.conversions || '[]');
            const baseUnitId = Number(selectedBarang.dataset.baseUnit);
            if (!conversions.some((conversion) => Number(conversion.id) === baseUnitId)) {
                conversions.push({
                    id: baseUnitId,
                    code: selectedBarang.dataset.baseCode,
                    factor: 1,
                    default: true,
                });
            }

            conversions.sort((left, right) => Number(right.default) - Number(left.default));
            conversions.forEach((conversion) => {
                unitSelect.add(new Option(conversion.code, String(conversion.id)));
            });

            const preferred = conversions.find((conversion) => conversion.default)
                ?? conversions.find((conversion) => Number(conversion.id) === baseUnitId);
            if (preferred) {
                unitSelect.value = String(preferred.id);
            }
            unitSelect.disabled = document.querySelector('input[name="source"]:checked').value !== 'quick_purchase';
        }

        function addQuickPurchaseRow() {
            const tableBody = document.getElementById('quick_purchase_rows');
            const rows = tableBody.querySelectorAll('.quick-purchase-row');
            const index = rows.length;

            const row = document.createElement('tr');
            row.className = 'quick-purchase-row';
            row.innerHTML = `
                <td class="px-3 py-2">
                    <select name="items[${index}][id_barang]" onchange="updateQuickPurchaseUnits(this)" class="quick-purchase-barang w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($barangs as $barang)
                            <option value="{{ $barang->id_barang }}" data-base-unit="{{ $barang->id_satuan_dasar }}" data-base-code="{{ $barang->satuanDasar?->kode_satuan }}" data-conversions="{{ $barang->konversiSatuan->map(fn ($conversion) => ['id' => (int) $conversion->id_satuan, 'code' => $conversion->satuan?->kode_satuan, 'factor' => (float) $conversion->faktor_ke_dasar, 'default' => (bool) $conversion->is_default_beli])->values()->toJson() }}">{{ $barang->nama_barang }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="px-3 py-2">
                    <select name="items[${index}][id_satuan]" required disabled class="quick-purchase-unit w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                        <option value="">-- Pilih Barang dahulu --</option>
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
            document.querySelectorAll('.quick-purchase-barang').forEach(updateQuickPurchaseUnits);
        });
    </script>
</x-layouts.app>
