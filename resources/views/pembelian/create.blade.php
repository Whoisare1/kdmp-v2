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

            <section class="mb-4 rounded-sm border border-amber-300 bg-amber-50 p-4">
                <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-amber-950">Permintaan Pengadaan (PR) Siap Diproses</h3>
                        <p class="mt-1 text-sm text-amber-900">
                            @if ($permintaanPembelianPending->isNotEmpty())
                                {{ $permintaanPembelianPending->count() }} permintaan pengadaan telah disetujui dan menunggu penerbitan PO pembelian.
                            @else
                                Semua permintaan pengadaan telah diproses menjadi PO atau belum ada PR baru yang disetujui.
                            @endif
                        </p>
                    </div>
                </div>
                @if ($permintaanPembelianPending->isEmpty())
                    <p class="text-xs text-amber-800">Anda tetap dapat mencatat pembelian langsung secara mandiri menggunakan formulir di bawah.</p>
                @else
                    <form action="{{ route("$routeBase.create") }}" method="GET" class="flex flex-col gap-2 sm:flex-row">
                        <label class="sr-only" for="permintaan_pembelian_select">Pilih permintaan gudang</label>
                        <select id="permintaan_pembelian_select" name="permintaan" required onchange="if(this.value) this.form.submit()" class="min-w-0 flex-1 rounded-sm border border-amber-300 bg-white px-3 py-2 text-sm">
                            <option value="">-- Pilih permintaan --</option>
                            @foreach ($permintaanPembelianPending as $requestItem)
                                <option value="{{ $requestItem->id_permintaan_pembelian }}" @selected($permintaanPembelian?->id_permintaan_pembelian === $requestItem->id_permintaan_pembelian)>
                                    {{ $requestItem->kode_permintaan }} — {{ $requestItem->barang?->nama_barang }} ({{ number_format((float) $requestItem->qty_diminta, 2, ',', '.') }} KG) — {{ $requestItem->sumber_pembelian === 'desa_lain' ? 'Desa lain' : 'Warga lokal' }} — Rekomendasi: {{ $requestItem->supplier_recommendations->pluck('nama')->join(', ') ?: 'Belum ada pemasok yang cocok di data master' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-sm bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800">Pilih Permintaan</button>
                    </form>
                @endif
            </section>

            <form action="{{ route("$routeBase.store") }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="jenis_pembayaran" value="kredit" id="consignment_payment_value" disabled>

                <!-- Pilih Sumber -->
                <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Pilih Sumber Pembelian</h3>
                    @if ($permintaanPembelian)
                        <input type="hidden" name="id_permintaan_pembelian" value="{{ $permintaanPembelian->id_permintaan_pembelian }}" id="id_permintaan_pembelian">
                        <div class="mb-3 rounded-sm border border-sawah-300 bg-sawah-50 p-3">
                            <p class="text-sm text-sawah-900">
                                <strong>Kebutuhan PR Terpilih:</strong> {{ $permintaanPembelian->kode_permintaan }} — 
                                <strong>{{ $permintaanPembelian->barang->nama_barang }}</strong> sebanyak 
                                <strong>{{ number_format((float) $permintaanPembelian->qty_diminta, 2, ',', '.') }} KG</strong> 
                                untuk tujuan gudang <strong>{{ $permintaanPembelian->gudang->nama_gudang }}</strong>.
                            </p>
                        </div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-600">Pilih Asal Pemasok / Sumber Pembelian:</p>
                        <label class="mb-2 flex items-center gap-2">
                            <input type="radio" name="source" value="warga_lokal" required @checked(old('source', $permintaanPembelian->sumber_pembelian) === 'warga_lokal') onchange="toggleForm()" class="rounded">
                            Pembelian dari Warga / Petani Lokal (Petani Desa)
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="source" value="desa_lain" @checked(old('source', $permintaanPembelian->sumber_pembelian) === 'desa_lain') onchange="toggleForm()" class="rounded">
                            Pembelian dari Desa Lain (Koperasi Desa Mitra)
                        </label>
                    @else
                        <label class="flex items-center gap-2 mb-2">
                            <input type="radio" name="source" value="desa_lain" required @checked(old('source', 'warga_lokal') === 'desa_lain') onchange="toggleForm()" class="rounded">
                            Pembelian dari Desa Lain
                        </label>
                        <label class="mb-2 flex items-center gap-2">
                            <input type="radio" name="source" value="warga_lokal" @checked(old('source', 'warga_lokal') === 'warga_lokal') onchange="toggleForm()" class="rounded">
                            Pembelian dari Warga Lokal
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="source" value="pengambilan_konsinyasi" @checked(old('source') === 'pengambilan_konsinyasi') onchange="toggleForm()" class="rounded">
                            Pengambilan Konsinyasi
                        </label>
                    @endif
                </div>

                <!-- Form Pembelian -->
                <div id="quick_purchase_section" class="rounded-sm border border-paper-300 bg-paper-50 p-4" style="display: none;">
                    <h3 id="purchase_heading" class="mb-3 text-sm font-semibold text-ink-900">Data Pembelian</h3>
                    <p class="mb-3 text-xs text-ink-600">Isi jumlah dalam KG dan total harga untuk seluruh jumlah pada baris. Sistem menghitung harga per KG dan harga dasar.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Unit Usaha *</span>
                            <select @unless ($permintaanPembelian) name="id_unit_usaha" @endunless @disabled($permintaanPembelian) data-locked="{{ $permintaanPembelian ? 'true' : 'false' }}" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                @foreach ($barangs->groupBy('id_unit_usaha') as $idUnit => $unitBarang)
                                    <option value="{{ $idUnit }}" @selected(($permintaanPembelian && (int) $idUnit === (int) ($permintaanPembelian->id_unit_usaha ?? $permintaanPembelian->barang->id_unit_usaha)) || (! $permintaanPembelian && (old('id_unit_usaha') == $idUnit || $loop->first)))>
                                        {{ $unitBarang->first()->unitUsaha->nama_unit_usaha ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($permintaanPembelian)
                                <input type="hidden" name="id_unit_usaha" value="{{ $permintaanPembelian->id_unit_usaha ?? $permintaanPembelian->barang->id_unit_usaha }}">
                            @endif
                        </label>
                        <label class="block text-sm">
                            <span class="text-ink-700 font-medium">Gudang *</span>
                            <select @unless ($permintaanPembelian) name="id_gudang" @endunless @disabled($permintaanPembelian) data-locked="{{ $permintaanPembelian ? 'true' : 'false' }}" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                                @foreach ($gudangs as $gudang)
                                    <option value="{{ $gudang->id_gudang }}" @selected(($permintaanPembelian && (int) $gudang->id_gudang === (int) $permintaanPembelian->gudang->id_gudang) || (! $permintaanPembelian && (old('id_gudang') == $gudang->id_gudang || $loop->first)))>
                                        {{ $gudang->nama_gudang }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($permintaanPembelian)
                                <input type="hidden" name="id_gudang" value="{{ $permintaanPembelian->gudang->id_gudang }}">
                            @endif
                        </label>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-sm border border-paper-300">
                        <div class="flex items-center justify-between bg-paper-100 px-3 py-2">
                            <h4 class="text-sm font-semibold text-ink-900">Daftar Barang</h4>
                            <button type="button" onclick="addQuickPurchaseRow()" @if ($permintaanPembelian) hidden @endif class="rounded-sm bg-emerald-600 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-700">
                                + Tambah Baris
                            </button>
                        </div>
                        <datalist id="purchase_barang_catalog">
                            @foreach ($barangs as $barang)
                                @if (in_array((int) $barang->id_barang, $barangKgIds, true))
                                    <option value="{{ $barang->nama_barang }}" data-base-unit="{{ $barang->id_satuan_dasar }}" data-base-code="{{ $barang->satuanDasar?->kode_satuan }}" data-conversions="{{ $barang->konversiSatuan->filter(fn ($conversion) => strtoupper($conversion->satuan?->kode_satuan ?? '') === 'KG')->map(fn ($conversion) => ['id' => (int) $conversion->id_satuan, 'code' => 'KG', 'factor' => (float) $conversion->faktor_ke_dasar, 'default' => (bool) $conversion->is_default_beli])->values()->toJson() }}"></option>
                                @endif
                            @endforeach
                        </datalist>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-paper-100 text-left text-ink-700">
                                    <tr>
                                        <th class="px-3 py-2">Barang</th>
                                        <th class="px-3 py-2">Qty (KG)</th>
                                        <th class="px-3 py-2">Total Harga (Rp)</th>
                                        <th class="px-3 py-2">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="quick_purchase_rows">
                                    <tr class="quick-purchase-row">
                                        <td class="px-3 py-2">
                                            <input type="text" name="items[0][nama_barang]" list="purchase_barang_catalog" value="{{ $permintaanPembelian?->barang?->nama_barang ?? old('items.0.nama_barang') }}" @readonly($permintaanPembelian) required autocomplete="off" oninput="updateQuickPurchaseUnits(this)" placeholder="Ketik nama barang" class="quick-purchase-barang w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                            <input type="hidden" name="items[0][id_satuan]" value="{{ $permintaanPembelian?->id_satuan }}" class="quick-purchase-unit">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.0001" min="0" name="items[0][qty_dasar]" value="{{ $permintaanPembelian?->qty_diminta ?? old('items.0.qty_dasar') }}" @readonly($permintaanPembelian) class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0" name="items[0][total_harga]" value="{{ old('items.0.total_harga', $permintaanPembelian?->harga_total_rekomendasi) }}" class="quick-purchase-price w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            @unless ($permintaanPembelian)
                                                <button type="button" onclick="removeQuickPurchaseRow(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button>
                                            @endunless
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <label class="mt-4 block max-w-sm text-sm">
                        <span class="font-medium text-ink-700">Diskon (Rp)</span>
                        <input id="purchase-discount-amount" type="number" name="diskon" min="0" step="1" value="{{ old('diskon', '0') }}" class="mt-1 w-full rounded-sm border border-paper-300 bg-white px-3 py-2">
                    </label>
                    <label class="mt-3 block max-w-sm text-sm">
                        <span class="font-medium text-ink-700">Diskon (%)</span>
                        <input id="purchase-discount-percent" type="number" min="0" max="100" step="0.01" value="{{ old('diskon_persen', '0') }}" class="mt-1 w-full rounded-sm border border-paper-300 bg-white px-3 py-2">
                        <span class="mt-1 block text-xs text-ink-500">Nilai rupiah dan persentase mengikuti subtotal barang.</span>
                    </label>
                </div>

                <!-- Umum -->
                <div id="purchase_details_section" class="rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Data Pihak Sumber dan Pembayaran</h3>
                    <div class="space-y-3">
                        <label class="block text-sm">
                            <span id="supplier-label" class="text-ink-700 font-medium">Pihak Sumber Pembelian *</span>
                            <input type="text" id="nama_pihak_sumber" name="nama_pihak_sumber" list="purchase_supplier_catalog" value="{{ old('nama_pihak_sumber', $permintaanPembelian?->pihak_rekomendasi?->nama_pihak) }}" required autocomplete="off" placeholder="Ketik nama pemasok / pihak sumber" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                            <datalist id="purchase_supplier_catalog">
                                @foreach ($permintaanPembelian?->supplier_recommendations ?? $pihaks as $pihak)
                                    <option value="{{ $pihak->nama_pihak }}" data-source="{{ $pihak->sumber_rekomendasi ?? ($pihak->jenis_pihak === 'entitas_lain' ? 'desa_lain' : 'warga_lokal') }}" data-standard-price="{{ $pihak->harga_standar_rekomendasi ?? '' }}" data-origin="{{ $pihak->asal_rekomendasi ?? '' }}" data-available="{{ $pihak->jumlah_tersedia_rekomendasi ?? '' }}" data-unit="{{ $pihak->satuan_rekomendasi ?? '' }}"></option>
                                @endforeach
                            </datalist>
                            @if ($permintaanPembelian)
                                <p class="mt-1 text-xs text-ink-500">
                                    Rekomendasi: {{ $permintaanPembelian->supplier_recommendations->pluck('nama')->join(', ') ?: 'Belum ada pemasok yang cocok di data master.' }}
                                </p>
                                @if ($permintaanPembelian->is_external_recommendation && $permintaanPembelian->supplier_recommendations->isNotEmpty())
                                    <ul class="mt-2 space-y-1 text-xs text-ink-600">
                                        @foreach ($permintaanPembelian->supplier_recommendations as $candidate)
                                            <li>{{ $candidate->nama_pihak }} — {{ $candidate->asal_rekomendasi }}; tersedia {{ $candidate->jumlah_tersedia_rekomendasi !== null ? number_format((float) $candidate->jumlah_tersedia_rekomendasi, 2, ',', '.') . ' ' . $candidate->satuan_rekomendasi : 'jumlah belum diketahui' }}{{ $candidate->harga_standar_rekomendasi !== null ? '; harga standar Rp ' . number_format((float) $candidate->harga_standar_rekomendasi, 2, ',', '.') . '/' . $candidate->satuan_rekomendasi : '' }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                        </label>
                        <label id="payment_method_field" class="block text-sm">
                            <span class="text-ink-700 font-medium">Jenis Pembayaran *</span>
                            <select name="jenis_pembayaran" data-previous-value="{{ old('jenis_pembayaran') }}" required onchange="togglePaymentFields()" class="mt-1 w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
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

                <div id="consignment_section" class="rounded-sm border border-sky-300 bg-sky-50 p-4" style="display: none;">
                    <h3 class="mb-2 text-sm font-semibold text-sky-950">Pencatatan Pengambilan Konsinyasi</h3>
                    <p class="text-sm text-sky-900">
                        Catat desa pengirim, komoditas, kuantitas, dan harga per KG yang ditetapkan desa pengirim. Nilai total menjadi hutang konsinyasi saat transaksi disetujui. Pencatatan ini tidak memilih sumber kas/bank dan belum mengubah stok Gudang atau Penjualan.
                    </p>
                </div>

                <div id="purchase_actions" class="flex gap-2">
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
        function selectedPurchaseSource() {
            const selectedSource = document.querySelector('input[name="source"]:checked, input[name="source"][type="hidden"]');
            return selectedSource ? selectedSource.value : 'warga_lokal';
        }

        function toggleForm() {
            const source = selectedPurchaseSource();
            const isPurchase = source === 'desa_lain' || source === 'warga_lokal';
            const isConsignment = source === 'pengambilan_konsinyasi';
            const isTransaction = isPurchase || isConsignment;
            const purchaseSection = document.getElementById('quick_purchase_section');
            const purchaseDetails = document.getElementById('purchase_details_section');
            const consignmentSection = document.getElementById('consignment_section');
            const supplierSelect = document.getElementById('nama_pihak_sumber');

            purchaseSection.style.display = isTransaction ? 'block' : 'none';
            purchaseDetails.style.display = isTransaction ? 'block' : 'none';
            consignmentSection.style.display = isConsignment ? 'block' : 'none';
            document.getElementById('purchase_actions').style.display = isTransaction ? 'flex' : 'none';
            document.querySelectorAll('#quick_purchase_section select, #quick_purchase_section input').forEach((field) => field.disabled = !isTransaction || field.dataset.locked === 'true');
            document.querySelectorAll('#purchase_details_section select, #purchase_details_section input' ).forEach((field) => field.disabled = !isTransaction);
            document.querySelectorAll('#quick_purchase_rows input[name$="[qty_dasar]"], #quick_purchase_rows input[name$="[total_harga]"]').forEach((field) => {
                field.required = isTransaction;
                if (field.name.endsWith('[total_harga]')) {
                    field.min = isConsignment ? '0.01' : '0';
                }
            });
            document.getElementById('purchase_heading').textContent = isConsignment
                ? 'Data Pengambilan Konsinyasi'
                : (source === 'desa_lain' ? 'Data Pembelian dari Desa Lain' : 'Data Pembelian dari Warga Lokal');
            document.getElementById('supplier-label').textContent = isConsignment
                ? 'Desa Pengirim *'
                : 'Pihak Sumber Pembelian *';

            togglePaymentFields();
        }

        function togglePaymentFields() {
            const jenisSelect = document.querySelector('select[name="jenis_pembayaran"]');
            const source = selectedPurchaseSource();
            const isConsignment = source === 'pengambilan_konsinyasi';
            const paymentField = document.getElementById('payment_method_field');
            const consignmentPaymentValue = document.getElementById('consignment_payment_value');
            const jenis = isConsignment ? 'kredit' : (jenisSelect ? jenisSelect.value : '');
            const isPurchase = source === 'desa_lain' || source === 'warga_lokal';
            const isCredit = (isPurchase && jenis === 'kredit') || isConsignment;
            const isCashOrBank = isPurchase && (jenis === 'tunai' || jenis === 'transfer');

            const jatuhTempoSection = document.getElementById('jatuh_tempo_section');
            const jatuhTempoInput = document.querySelector('input[name="tgl_jatuh_tempo"]');
            const sumberUangSection = document.getElementById('sumber_uang_section');
            const sumberUangSelect = document.getElementById('id_kas_bank');

            paymentField.style.display = isConsignment ? 'none' : 'block';
            if (isConsignment && !jenisSelect.disabled) {
                jenisSelect.dataset.previousValue = jenisSelect.value;
            } else if (!isConsignment && jenisSelect.disabled) {
                jenisSelect.value = jenisSelect.dataset.previousValue || '';
            }
            jenisSelect.disabled = isConsignment;
            jenisSelect.required = !isConsignment;
            consignmentPaymentValue.disabled = !isConsignment;
            if (isConsignment) {
                jenisSelect.value = 'kredit';
            }

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

        function updatePurchaseRecommendation(supplierInput) {
            const candidate = Array.from(document.querySelectorAll('#purchase_supplier_catalog option'))
                .find((option) => option.value === supplierInput.value);
            if (!candidate) return;

            const sourceRadio = document.querySelector(`input[name="source"][value="${candidate.dataset.source}"]`);
            if (sourceRadio) {
                sourceRadio.checked = true;
                toggleForm();
            }

            const standardPrice = Number.parseFloat(candidate.dataset.standardPrice);
            if (Number.isFinite(standardPrice) && standardPrice > 0) {
                const qty = Number.parseFloat(document.querySelector('#quick_purchase_rows input[name$="[qty_dasar]"]')?.value) || 0;
                const totalPrice = document.querySelector('#quick_purchase_rows input[name$="[total_harga]"]');
                if (totalPrice) totalPrice.value = (standardPrice * qty).toFixed(2);
                if (typeof syncPurchaseDiscount === 'function') syncPurchaseDiscount('amount');
            }
        }

        function updateQuickPurchaseUnits(barangInput) {
            const row = barangInput.closest('.quick-purchase-row');
            const unitInput = row.querySelector('.quick-purchase-unit');
            const selectedBarang = Array.from(document.querySelectorAll('#purchase_barang_catalog option')).find((option) => option.value === barangInput.value);
            const requestedUnitId = unitInput.value;
            unitInput.value = '';
            if (!selectedBarang) return;

            let conversions = JSON.parse(selectedBarang.dataset.conversions || '[]');
            const baseUnitId = Number(selectedBarang.dataset.baseUnit);
            if (selectedBarang.dataset.baseCode?.toUpperCase() === 'KG' && !conversions.some((conversion) => Number(conversion.id) === baseUnitId)) {
                conversions.push({ id: baseUnitId, code: 'KG', factor: 1, default: true });
            }
            if (conversions.length === 0) return;
            conversions.sort((left, right) => Number(right.default) - Number(left.default));
            const preferred = conversions.find((conversion) => conversion.default) ?? conversions.find((conversion) => Number(conversion.id) === baseUnitId);
            const hasPurchaseRequest = Boolean(document.getElementById('id_permintaan_pembelian'));
            const requestedUnit = conversions.find((conversion) => String(conversion.id) === requestedUnitId);
            unitInput.value = String(hasPurchaseRequest && requestedUnit ? requestedUnit.id : (preferred?.id ?? ''));
        }

        function addQuickPurchaseRow() {
            const tableBody = document.getElementById('quick_purchase_rows');
            const rows = tableBody.querySelectorAll('.quick-purchase-row');
            const index = rows.length;

            const row = document.createElement('tr');
            row.className = 'quick-purchase-row';
            row.innerHTML = `
                <td class="px-3 py-2">
                    <input type="text" name="items[${index}][nama_barang]" list="purchase_barang_catalog" required autocomplete="off" oninput="updateQuickPurchaseUnits(this)" placeholder="Ketik nama barang" class="quick-purchase-barang w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                    <input type="hidden" name="items[${index}][id_satuan]" class="quick-purchase-unit">
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" min="0" name="items[${index}][qty_dasar]" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" min="0" name="items[${index}][total_harga]" class="quick-purchase-price w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5">
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

        function syncPurchaseDiscount(source) {
            const amountInput = document.getElementById('purchase-discount-amount');
            const percentInput = document.getElementById('purchase-discount-percent');
            const subtotal = Array.from(document.querySelectorAll('#quick_purchase_rows input[name$="[total_harga]"]'))
                .reduce((sum, input) => sum + (Number.parseFloat(input.value) || 0), 0);
            if (source === 'percent') {
                const percent = Math.min(100, Math.max(0, Number.parseFloat(percentInput.value) || 0));
                percentInput.value = percent.toFixed(2);
                amountInput.value = Math.min(Math.floor(subtotal), Math.round(subtotal * percent / 100)).toFixed(0);
            } else {
                const amount = Math.min(Math.floor(subtotal), Math.max(0, Math.round(Number.parseFloat(amountInput.value) || 0)));
                amountInput.value = amount.toFixed(0);
                percentInput.value = subtotal > 0 ? (amount / subtotal * 100).toFixed(2) : '0.00';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            toggleForm();
            document.querySelectorAll('.quick-purchase-barang').forEach((input) => updateQuickPurchaseUnits(input));
            document.getElementById('nama_pihak_sumber').addEventListener('input', function () { updatePurchaseRecommendation(this); });
            updatePurchaseRecommendation(document.getElementById('nama_pihak_sumber'));
            document.getElementById('purchase-discount-amount').addEventListener('input', () => syncPurchaseDiscount('amount'));
            document.getElementById('purchase-discount-percent').addEventListener('input', () => syncPurchaseDiscount('percent'));
            document.getElementById('quick_purchase_rows').addEventListener('input', () => syncPurchaseDiscount('amount'));
            syncPurchaseDiscount('amount');
        });
    </script>
</x-layouts.app>




