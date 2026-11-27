<x-layouts.app :title="$title" eyebrow="Master Data Survei">

    <div class="max-w-2xl bg-white p-6 rounded-xl border border-paper-200 shadow-sm">
        <h2 class="text-lg font-display font-semibold text-ink-900 mb-4">Tambah Pertanyaan Survei Baru</h2>

        <form action="{{ route('master.pertanyaan.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Jenis Pendataan <span class="text-red-500">*</span></label>
                    <select name="jenis_pendataan" required class="w-full rounded-lg border border-paper-300 p-2 text-sm focus:border-padi-500 focus:outline-none">
                        <option value="komoditas" @selected(old('jenis_pendataan') === 'komoditas')>Potensi Komoditas Petani</option>
                        <option value="penduduk" @selected(old('jenis_pendataan') === 'penduduk')>Pendataan Penduduk</option>
                    </select>
                    @error('jenis_pendataan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Kode Pertanyaan <span class="text-red-500">*</span></label>
                    <input type="text" name="kode_pertanyaan" value="{{ old('kode_pertanyaan') }}" placeholder="Contoh: PROD-BERAS-01" required
                           class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono uppercase focus:border-padi-500 focus:outline-none">
                    @error('kode_pertanyaan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-paper-700 mb-1">Teks Pertanyaan <span class="text-red-500">*</span></label>
                <textarea name="teks_pertanyaan" rows="3" placeholder="Tuliskan kalimat pertanyaan survei..." required
                          class="w-full rounded-lg border border-paper-300 p-2 text-sm focus:border-padi-500 focus:outline-none">{{ old('teks_pertanyaan') }}</textarea>
                @error('teks_pertanyaan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Tipe Jawaban <span class="text-red-500">*</span></label>
                    <select name="tipe_jawaban" required class="w-full rounded-lg border border-paper-300 p-2 text-sm focus:border-padi-500 focus:outline-none">
                        <option value="angka">Angka / Numerik</option>
                        <option value="teks">Teks Bebas</option>
                        <option value="pilihan">Pilihan Tunggal</option>
                        <option value="pilihan_ganda">Pilihan Ganda</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Satuan Ukuran</label>
                    <input type="text" name="satuan" value="{{ old('satuan') }}" placeholder="Contoh: Jiwa, Kg, Ton, Ha"
                           class="w-full rounded-lg border border-paper-300 p-2 text-sm focus:border-padi-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Nomor Urut <span class="text-red-500">*</span></label>
                    <input type="number" name="urutan" value="{{ old('urutan', 1) }}" min="1" required
                           class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono focus:border-padi-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Kategori Komoditas (Opsional)</label>
                    <select name="id_kategori_komoditas" class="w-full rounded-lg border border-paper-300 p-2 text-sm focus:border-padi-500 focus:outline-none">
                        <option value="">Tidak terikat kategori</option>
                        @foreach($kategoriList as $k)
                            <option value="{{ $k->id }}" @selected(old('id_kategori_komoditas') == $k->id)>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Master Komoditas (Opsional)</label>
                    <select name="id_master_komoditas" class="w-full rounded-lg border border-paper-300 p-2 text-sm focus:border-padi-500 focus:outline-none">
                        <option value="">Tidak terikat master</option>
                        @foreach($masterList as $m)
                            <option value="{{ $m->id }}" @selected(old('id_master_komoditas') == $m->id)>{{ $m->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-6 pt-2">
                <label class="flex items-center gap-2 text-sm text-ink-800">
                    <input type="checkbox" name="wajib_diisi" value="1" checked class="rounded border-paper-300 text-padi-600 focus:ring-padi-500">
                    Wajib Diisi oleh Responden
                </label>
                <label class="flex items-center gap-2 text-sm text-ink-800">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-paper-300 text-padi-600 focus:ring-padi-500">
                    Status Aktif
                </label>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-paper-200">
                <button type="submit" class="px-5 py-2 rounded-lg bg-padi-500 hover:bg-padi-600 text-ink-950 font-semibold text-xs shadow-sm">
                    Simpan Pertanyaan
                </button>
                <a href="{{ route('master.pertanyaan.index') }}" class="px-4 py-2 rounded-lg border border-paper-300 text-xs text-paper-600 hover:bg-paper-50">
                    Batal
                </a>
            </div>
        </form>
    </div>

</x-layouts.app>

