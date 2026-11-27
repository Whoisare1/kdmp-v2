<x-layouts.app :title="$title" eyebrow="Master Data Komoditas">
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-6">
            <h2 class="font-display text-xl font-semibold text-ink-900 mb-4">Tambah Item / Subjenis Komoditas</h2>

            <form action="{{ route('master.item-komoditas.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-ink-600/80 mb-1">Master Komoditas Induk <span class="text-merah-600">*</span></label>
                    <select name="id_master_komoditas" id="id_master_komoditas" required class="block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="">Pilih Master Komoditas...</option>
                        @foreach($masterList as $m)
                            @php
                                $isEkraf = str_contains(strtolower($m->kategori?->kode ?? ''), 'ekraf') || str_contains(strtolower($m->kategori?->nama ?? ''), 'ekraf');
                            @endphp
                            <option value="{{ $m->id }}" data-is-ekraf="{{ $isEkraf ? '1' : '0' }}" @selected(old('id_master_komoditas') == $m->id)>
                                {{ $m->nama }} (Kategori: {{ $m->kategori?->nama }})
                            </option>
                        @endforeach
                    </select>
                    @error('id_master_komoditas') <p class="mt-1 text-xs text-merah-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-ink-600/80 mb-1">Kode Item <span class="text-merah-600">*</span></label>
                    <input type="text" name="kode" value="{{ old('kode') }}" required placeholder="Contoh: BERAS-MENTIK, CABAI-RAWIT" class="block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-900 placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none uppercase">
                    @error('kode') <p class="mt-1 text-xs text-merah-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-ink-600/80 mb-1">Nama Subjenis / Item Komoditas <span class="text-merah-600">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama') }}" required placeholder="Contoh: Beras Mentik Wangi, Cabai Rawit Merah" class="block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-900 placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none">
                    @error('nama') <p class="mt-1 text-xs text-merah-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-ink-600/80 mb-1">Satuan Standar <span class="text-merah-600">*</span></label>
                    <select name="satuan_standar" id="satuan_standar" required class="block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm font-mono text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="kg" @selected(old('satuan_standar', 'kg') === 'kg')>kg (Kilogram — Pertanian, Peternakan, Perikanan)</option>
                        <option value="unit" @selected(old('satuan_standar') === 'unit')>unit (Unit — Ekonomi Kreatif)</option>
                    </select>
                    <p class="text-[11px] text-ink-600/60 mt-1">Otomatis: Kategori Ekraf menggunakan satuan <strong>unit</strong>, selain Ekraf menggunakan <strong>kg</strong>.</p>
                    @error('satuan_standar') <p class="mt-1 text-xs text-merah-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-ink-600/80 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" placeholder="Keterangan singkat..." class="block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-900 placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none">{{ old('deskripsi') }}</textarea>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="rounded-sm border-paper-300 text-merah-600 focus:ring-merah-500">
                    <label for="is_active" class="text-sm text-ink-800">Item Komoditas Aktif</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-paper-200">
                    <a href="{{ route('master.item-komoditas.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">
                        Batal
                    </a>
                    <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                        Simpan Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('id_master_komoditas')?.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const isEkraf = opt.getAttribute('data-is-ekraf') === '1';
            const satuanSelect = document.getElementById('satuan_standar');
            if (satuanSelect) {
                satuanSelect.value = isEkraf ? 'unit' : 'kg';
            }
        });
    </script>
</x-layouts.app>
