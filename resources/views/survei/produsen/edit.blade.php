<x-layouts.app title="Edit Data Produsen" eyebrow="Master Data">
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

        <form action="{{ route('survei.produsen.update', $produsen->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">Wilayah / Desa</label>
                    <select name="id_wilayah" required class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                        @foreach ($wilayahs as $wilayah)
                            <option value="{{ $wilayah->id }}" {{ $produsen->id_wilayah == $wilayah->id ? 'selected' : '' }}>
                                {{ $wilayah->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="mb-2 block text-sm font-semibold text-ink-700">Kategori</label>
                    <select name="kategori" required class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                        <option value="Kelompok Tani" {{ $produsen->kategori == 'Kelompok Tani' ? 'selected' : '' }}>Kelompok Tani</option>
                        <option value="Ekraf" {{ $produsen->kategori == 'Ekraf' ? 'selected' : '' }}>Ekonomi Kreatif (Ekraf)</option>
                    </select>
                </div>

                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-ink-700">Nama Anggota/Panitia</label>
                        <input type="text" name="nama_anggota" required value="{{ $produsen->nama_anggota }}" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-ink-700">Nomor WhatsApp (Opsional)</label>
                        <input type="text" name="no_wa" value="{{ $produsen->no_wa }}" placeholder="08xxxxxxxxxx" class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500">
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="rounded-sm bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    Perbarui Data
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
