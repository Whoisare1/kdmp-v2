<x-layouts.app :title="$title" eyebrow="Pembelian / Retur">
    @if ($errors->any())
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-ink-600">Ajukan pengembalian barang dari pembelian yang telah disetujui / diterima.</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Ajukan Retur Pembelian</h1>
        </div>
        <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Kembali ke Retur</a>
    </div>

    @if ($purchases->isEmpty())
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-8 text-center text-ink-600">
            <p class="font-medium text-ink-800">Belum Ada Barang Pembelian yang Dapat Diretur</p>
            <p class="mt-1 text-sm">Retur hanya dapat diajukan untuk transaksi pembelian yang berstatus disetujui/diterima dan masih memiliki sisa kuantitas barang.</p>
            <a href="{{ route('pembelian.index') }}" class="mt-4 inline-flex items-center rounded-sm bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Lihat Daftar Pembelian</a>
        </div>
    @else
        @if ($pendingReturnRequests->isNotEmpty())
            <!-- Permintaan Retur dari Gudang -->
            <section class="mb-5 rounded-sm border border-amber-300 bg-amber-50 p-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="font-semibold text-amber-950">Permintaan Retur dari Gudang</h2>
                        <p class="mt-1 text-sm text-amber-900">Ada {{ $pendingReturnRequests->count() }} permintaan retur barang rusak / tidak layak dari penerimaan gudang.</p>
                    </div>
                </div>
                <form method="GET" action="{{ route("$routeBase.create") }}" class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <label class="sr-only" for="return_request_select">Pilih permintaan retur gudang</label>
                    <select id="return_request_select" name="permintaan_retur" onchange="if(this.value) this.form.submit()" required class="min-w-0 flex-1 rounded-sm border border-amber-300 bg-white px-3 py-2 text-sm text-ink-900">
                        <option value="">-- Pilih permintaan retur gudang --</option>
                        @foreach ($pendingReturnRequests as $req)
                            <option value="{{ $req->id_permintaan_retur }}" @selected($selectedReturnRequest?->id_permintaan_retur === $req->id_permintaan_retur)>
                                {{ $req->kode_permintaan }} — {{ $req->barang?->nama_barang }} ({{ number_format((float) $req->qty_diminta, 2, ',', '.') }} KG tidak layak: {{ $req->alasan }}) — Pembelian {{ $req->pembelian->kode_pembelian }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-sm bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800">Tinjau Permintaan</button>
                </form>
            </section>
        @endif

        <!-- Pemilih Pembelian & Barang Mandiri -->
        <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <label for="pilih_barang_retur" class="mb-2 block text-sm font-semibold text-ink-900">Atau Pilih Pembelian & Barang Secara Langsung</label>
            <select id="pilih_barang_retur" onchange="if(this.value) window.location.href = this.value" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
                @foreach ($purchases as $p)
                    @foreach ($p->returItems as $item)
                        @if ($item->qty_sisa_retur > 0)
                            <option value="{{ route("$routeBase.create", ['id_pembelian' => $p->id_pembelian, 'id_barang' => $item->id_barang]) }}"
                                @selected(!$selectedReturnRequest && $selectedPurchase?->id_pembelian == $p->id_pembelian && $selectedLine?->id_barang == $item->id_barang)>
                                {{ $p->kode_pembelian }} — {{ $item->barang?->nama_barang }} (Sisa: {{ number_format((float) $item->qty_sisa_retur, 2, ',', '.') }} KG) — Supplier: {{ $p->pihak?->nama ?? '-' }}
                            </option>
                        @endif
                    @endforeach
                @endforeach
            </select>
        </div>

        @if ($selectedPurchase && $selectedLine)
            @if ($selectedReturnRequest)
                <div class="mb-5 rounded-sm border border-amber-300 bg-amber-100 p-4 text-sm text-amber-950">
                    <p class="font-semibold text-base text-amber-950">📦 Memproses Permintaan Retur Gudang: {{ $selectedReturnRequest->kode_permintaan }}</p>
                    <p class="mt-1">
                        Kuantitas dilaporkan tidak layak: <strong>{{ number_format((float) $selectedReturnRequest->qty_diminta, 2, ',', '.') }} KG</strong>.
                        Catatan Gudang: <em>"{{ $selectedReturnRequest->alasan }}"</em>.
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route("$routeBase.store") }}" class="space-y-5">
                @csrf
                <input type="hidden" name="id_pembelian" value="{{ $selectedPurchase->id_pembelian }}">
                <input type="hidden" name="id_barang" value="{{ $selectedLine->id_barang }}">
                <input type="hidden" name="id_penerimaan" value="{{ $selectedReturnRequest?->id_penerimaan ?? '' }}">

                <!-- Ringkasan Barang Pembelian -->
                <section class="grid gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-xs font-medium uppercase text-ink-600">Nomor Pembelian</p>
                        <p class="mt-1 font-semibold text-ink-900">{{ $selectedPurchase->kode_pembelian }}</p>
                        <p class="text-xs text-ink-500">{{ $selectedPurchase->tanggal_transaksi?->format('d M Y') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-ink-600">Pemasok / Supplier</p>
                        <p class="mt-1 font-semibold text-ink-900">{{ $selectedPurchase->pihak?->nama ?? '-' }}</p>
                        <p class="text-xs text-ink-500 capitalize">{{ str_replace('_', ' ', $selectedPurchase->sumber_pembelian ?? 'warga') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-ink-600">Komoditas / Barang</p>
                        <p class="mt-1 font-semibold text-ink-900">{{ $selectedLine->barang?->nama_barang ?? '-' }}</p>
                        <p class="text-xs text-ink-500">Total Beli: {{ number_format((float) $selectedLine->qty_dasar, 2, ',', '.') }} KG</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-ink-600">Sisa Dapat Diretur</p>
                        <p class="mt-1 text-lg font-bold text-emerald-700">{{ number_format((float) $selectedLine->qty_sisa_retur, 2, ',', '.') }} KG</p>
                        <p class="text-xs text-ink-500">Sudah Diretur: {{ number_format((float) $selectedLine->qty_diretur, 2, ',', '.') }} KG</p>
                    </div>
                </section>

                <!-- Form Input Retur -->
                <section class="space-y-4 rounded-sm border border-paper-300 bg-paper-50 p-4">
                    <h2 class="text-sm font-semibold text-ink-900">Form Pengajuan Retur</h2>
                    
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-700" for="qty_retur">
                                Jumlah Retur (KG) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" max="{{ (float) $selectedLine->qty_sisa_retur }}" 
                                id="qty_retur" name="qty_retur" 
                                value="{{ old('qty_retur', $selectedReturnRequest ? (float) $selectedReturnRequest->qty_diminta : (float) $selectedLine->qty_sisa_retur) }}" 
                                required class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
                            <p class="mt-1 text-xs text-ink-500">Maksimal: {{ number_format((float) $selectedLine->qty_sisa_retur, 2, ',', '.') }} KG</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-700" for="jenis_penyelesaian">
                                Penyelesaian dengan Supplier <span class="text-red-600">*</span>
                            </label>
                            <select id="jenis_penyelesaian" name="jenis_penyelesaian" required class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
                                <option value="">-- Pilih Penyelesaian --</option>
                                <option value="potong_hutang" @selected(old('jenis_penyelesaian', 'potong_hutang') === 'potong_hutang')>Potong Hutang (Mengurangi sisa kewajiban hutang)</option>
                                <option value="uang" @selected(old('jenis_penyelesaian') === 'uang')>Pengembalian Uang (Kas Masuk)</option>
                                <option value="ganti_barang" @selected(old('jenis_penyelesaian') === 'ganti_barang')>Ganti Barang (Barang fisik pengganti)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700" for="alasan">
                            Alasan Retur <span class="text-red-600">*</span>
                        </label>
                        <textarea id="alasan" name="alasan" rows="3" required placeholder="Contoh: Barang cacat, basah, atau tidak sesuai spesifikasi saat pemeriksaan..." class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">{{ old('alasan', $selectedReturnRequest ? 'Barang tidak layak dari pemeriksaan gudang: ' . $selectedReturnRequest->alasan : '') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Batal</a>
                        <button type="submit" class="rounded-sm bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Kirim Pengajuan Retur</button>
                    </div>
                </section>
            </form>
        @endif
    @endif
</x-layouts.app>
