<x-layouts.app title="Edit Aset Tetap" eyebrow="Akuntansi">

    <div class="mb-5">
        <a href="{{ route('akuntansi.aset-tetap.index') }}" class="inline-flex items-center gap-1 text-sm text-ink-500 hover:text-ink-800 transition">
            ← Kembali ke Daftar Aset
        </a>
    </div>

    <div class="mb-5">
        <h1 class="font-display text-xl font-semibold text-ink-900">Edit Aset Tetap</h1>
        <p class="mt-1 text-sm text-ink-600">
            @if($terkunciPerolehan)
                Perbarui informasi aset tetap. Tanggal dan nilai perolehan dikunci karena aset sudah pernah disusutkan.
            @else
                Perbarui informasi aset tetap. Tanggal dan nilai perolehan masih dapat diubah karena aset belum pernah disusutkan.
            @endif
        </p>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('akuntansi.aset-tetap.update', $aset->id_aset) }}" class="rounded border border-paper-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @idempotency

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            
            {{-- Informasi Fisik Aset --}}
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-ink-900 border-b border-paper-200 pb-2">Informasi Fisik</h2>
                
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Kode Aset <span class="text-merah-500">*</span></label>
                    <input type="text" value="{{ $aset->kode_aset }}" disabled
                           class="w-full rounded border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-500 cursor-not-allowed">
                    <p class="text-[10px] text-ink-400 mt-1">* Kode aset tidak dapat diubah</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Nama Aset</label>
                    <input type="text" name="nama_aset" value="{{ old('nama_aset', $aset->nama_aset) }}" required
                           class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Kategori / Kelompok</label>
                    @php $katVal = strtolower((string) old('kategori', $aset->kategori)); @endphp
                    <select name="kategori" id="kategori_aset" required class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                        @foreach($kategoriList as $kode => $label)
                            <option value="{{ $kode }}" @selected($katVal === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Status Aset</label>
                    @php $statusVal = strtolower((string) old('status', $aset->status)); @endphp
                    <select name="status" required class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                        <option value="aktif" @selected($statusVal === 'aktif')>Aktif (Disusutkan sesuai umur bulan)</option>
                        <option value="habis_susut" @selected($statusVal === 'habis_susut')>Habis Susut</option>
                        <option value="dilepas" @selected($statusVal === 'dilepas')>Dilepas / Dijual / Rusak Berat (Hentikan Penyusutan)</option>
                    </select>
                </div>
            </div>

            {{-- Informasi Nilai & Akuntansi --}}
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-ink-900 border-b border-paper-200 pb-2">Informasi Nilai & Akuntansi</h2>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Tanggal Perolehan <span class="text-merah-500">*</span></label>
                        @if($terkunciPerolehan)
                            <input type="date" value="{{ $aset->tgl_perolehan->format('Y-m-d') }}" disabled
                                   class="w-full rounded border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-500 cursor-not-allowed">
                        @else
                            <input type="date" name="tgl_perolehan" value="{{ old('tgl_perolehan', $aset->tgl_perolehan->format('Y-m-d')) }}" required
                                   class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Umur Ekonomis (Bulan)</label>
                        <input type="number" name="umur_bulan" id="umur_bulan" value="{{ old('umur_bulan', $aset->umur_bulan) }}" min="0"
                               class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                        <p id="tanah_hint" class="hidden mt-1 text-[11px] text-ink-500">Tanah tidak disusutkan (umur bulan = 0).</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Nilai Perolehan (Rp) <span class="text-merah-500">*</span></label>
                        @if($terkunciPerolehan)
                            <input type="text" value="{{ number_format($aset->nilai_perolehan, 0, ',', '.') }}" disabled
                                   class="w-full rounded border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-500 font-mono cursor-not-allowed">
                        @else
                            <input type="number" name="nilai_perolehan" value="{{ old('nilai_perolehan', (int) $aset->nilai_perolehan) }}" required min="0" step="1"
                                   class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400 font-mono">
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Nilai Residu (Sisa) (Rp)</label>
                        <input type="number" name="nilai_residu" value="{{ old('nilai_residu', (int)$aset->nilai_residu) }}" required min="0" step="1"
                               class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Akun Buku Besar (Aset Tetap)</label>
                    <select name="kode_akun_aset" required class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                        <option value="">-- Pilih Akun Aset --</option>
                        @foreach($akunAset as $akun)
                            <option value="{{ $akun->kode_anak }}" @selected(old('kode_akun_aset', $aset->kode_akun_aset) == $akun->kode_anak)>
                                {{ $akun->kode_anak }} - {{ $akun->nama_rekening }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="wrap_akun_akum">
                    <label class="block text-xs font-medium text-ink-700 mb-1">Akun Akumulasi Penyusutan</label>
                    <select name="kode_akun_akum" id="kode_akun_akum" class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                        <option value="">-- Pilih Akun Akumulasi (Kosongkan untuk Tanah) --</option>
                        @foreach($akunAkum as $akun)
                            <option value="{{ $akun->kode_anak }}" @selected(old('kode_akun_akum', $aset->kode_akun_akum) == $akun->kode_anak)>
                                {{ $akun->kode_anak }} - {{ $akun->nama_rekening }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="wrap_akun_biaya">
                    <label class="block text-xs font-medium text-ink-700 mb-1">Akun Biaya Penyusutan</label>
                    <select name="kode_akun_biaya" id="kode_akun_biaya" class="w-full rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                        <option value="">-- Pilih Akun Biaya (Kosongkan untuk Tanah) --</option>
                        @foreach($akunBiaya as $akun)
                            <option value="{{ $akun->kode_anak }}" @selected(old('kode_akun_biaya', $aset->kode_akun_biaya) == $akun->kode_anak)>
                                {{ $akun->kode_anak }} - {{ $akun->nama_rekening }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>

        <div class="mt-8 flex justify-end gap-3 border-t border-paper-200 pt-4">
            <a href="{{ route('akuntansi.aset-tetap.index') }}" class="rounded px-4 py-2 text-sm font-medium text-ink-600 hover:bg-paper-100 transition">
                Batal
            </a>
            <button type="submit" class="rounded bg-merah-500 px-6 py-2 text-sm font-medium text-white hover:bg-merah-600 transition shadow">
                Simpan Perubahan
            </button>
        </div>
    </form>

    @include('akuntansi.aset-tetap._kategori-script')

</x-layouts.app>
