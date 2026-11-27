<x-layouts.app :title="$title" eyebrow="Master Data Komoditas">
    <div class="mx-auto max-w-2xl space-y-4">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-6">
            <h2 class="font-display text-xl font-semibold text-ink-900 mb-4">Tambah Master Komoditas</h2>

            <form action="{{ route('master.master-komoditas.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-ink-600/80 mb-1">Kategori Induk <span class="text-merah-600">*</span></label>
                    <select name="id_kategori" required class="block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <option value="">Pilih Kategori...</option>
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat->id }}" @selected(old('id_kategori') == $kat->id)>{{ $kat->nama }}</option>
                        @endforeach
                    </select>
                    @error('id_kategori') <p class="mt-1 text-xs text-merah-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-ink-600/80 mb-1">Kode Master <span class="text-merah-600">*</span></label>
                    <input type="text" name="kode" value="{{ old('kode') }}" required placeholder="Contoh: BERAS, CABAI, BAWANG" class="block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-900 placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none uppercase">
                    @error('kode') <p class="mt-1 text-xs text-merah-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-ink-600/80 mb-1">Nama Master Komoditas <span class="text-merah-600">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama') }}" required placeholder="Contoh: Beras, Cabai, Ikan" class="block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-900 placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none">
                    @error('nama') <p class="mt-1 text-xs text-merah-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wide text-ink-600/80 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" placeholder="Keterangan singkat..." class="block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-900 placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none">{{ old('deskripsi') }}</textarea>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="rounded-sm border-paper-300 text-merah-600 focus:ring-merah-500">
                    <label for="is_active" class="text-sm text-ink-800">Master Komoditas Aktif</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-paper-200">
                    <a href="{{ route('master.master-komoditas.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">
                        Batal
                    </a>
                    <button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                        Simpan Master
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
