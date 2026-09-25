<x-layouts.app title="Tambah Data Produsen" eyebrow="Master Data">
    <div class="mb-5 flex items-center justify-end">
        <a href="{{ route('survei.produsen.index') }}" class="text-sm font-medium text-ink-500 hover:text-ink-900">Batal</a>
    </div>

    <div class="rounded-sm border border-paper-300 bg-white p-6 shadow-sm">
        @if ($errors->any())
            <div class="mb-6 rounded-sm bg-red-50 p-4 border border-red-200">
                <ul class="list-inside list-disc text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('survei.produsen.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">Wilayah / Desa</label>
                    <select name="id_wilayah" required class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                        @if ($wilayahs->count() > 1)
                            <option value="">-- Pilih Desa --</option>
                        @endif
                        @foreach ($wilayahs as $wilayah)
                            <option value="{{ $wilayah->id }}" {{ (old('id_wilayah') == $wilayah->id || $wilayahs->count() == 1) ? 'selected' : '' }}>{{ $wilayah->nama }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">Kategori</label>
                    <select name="kategori" required class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Kelompok Tani" {{ old('kategori') == 'Kelompok Tani' ? 'selected' : '' }}>Kelompok Tani</option>
                        <option value="Ekraf" {{ old('kategori') == 'Ekraf' ? 'selected' : '' }}>Ekonomi Kreatif (Ekraf)</option>
                    </select>
                </div>

                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-ink-700">Nama Anggota/Panitia</label>
                        <input type="text" name="nama_anggota" required placeholder="Masukkan Nama Lengkap" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-ink-700">Nomor WhatsApp (Opsional)</label>
                        <input type="text" name="no_wa" placeholder="08xxxxxxxxxx" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                    </div>
                </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="rounded-sm bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
