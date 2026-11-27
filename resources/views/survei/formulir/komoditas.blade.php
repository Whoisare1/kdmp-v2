<x-layouts.app :title="$title" eyebrow="Modul Survei Lapangan">

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="max-w-3xl bg-white p-6 rounded-xl border border-paper-200 shadow-sm" x-data="{
        kategoriId: '',
        masterId: '',
        selectedItem: '',
        satuan: 'kg',
        categories: {{ Js::from($kategoriList) }},
        get masters() {
            if (!this.kategoriId) return [];
            let kat = this.categories.find(c => c.id == this.kategoriId);
            return kat ? kat.master_komoditas : [];
        },
        get items() {
            if (!this.masterId) return [];
            let m = this.masters.find(m => m.id == this.masterId);
            return m ? m.items : [];
        },
        updateSatuan() {
            let item = this.items.find(i => i.id == this.selectedItem);
            if (item && item.satuan_standar) {
                this.satuan = item.satuan_standar;
            }
        }
    }">
        <div class="mb-5 border-b border-paper-200 pb-4">
            <div class="flex items-center gap-2 text-xs font-mono text-padi-700 font-semibold mb-1">
                <span>SURVEI POTENSI KOMODITAS DESA</span>
            </div>
            <h2 class="text-xl font-display font-semibold text-ink-900">Formulir Potensi Komoditas Petani: {{ $desaAktif?->nama ?? 'Desa' }}</h2>
            <p class="text-xs text-paper-500 mt-1">
                Mendata produsen/petani warga desa ini beserta komoditas unggulan yang dihasilkan untuk pemetaan potensi dan kebutuhan barter koperasi.
            </p>
        </div>

        <form action="{{ route('survei.formulir.komoditas.store') }}" method="POST" class="space-y-4">
            @csrf

            {{-- 1. IDENTITAS PETANI / PRODUSEN DESA --}}
            <div class="p-4 rounded-xl bg-paper-50 border border-paper-200 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-mono uppercase tracking-wider text-paper-700 font-semibold">
                        1. Identitas Petani / Produsen (Desa: {{ $desaAktif?->nama ?? '-' }})
                    </h3>
                    <span class="text-[11px] px-2 py-0.5 rounded bg-padi-100 text-padi-800 font-medium">Warga Desa Aktif</span>
                </div>

                {{-- Banner Lokasi Desa Terkunci --}}
                <div class="p-2.5 bg-white border border-paper-200 rounded-lg flex items-center justify-between text-xs">
                    <div>
                        <span class="text-paper-500">Desa Domisili Petani:</span>
                        <strong class="text-ink-900 ml-1">{{ $desaAktif?->nama }}</strong>
                    </div>
                    <div class="text-paper-500 font-mono text-[11px]">
                        Kec. {{ $desaAktif?->parent?->nama ?? 'Juwana' }}, Kab. {{ $desaAktif?->parent?->parent?->nama ?? 'Pati' }}
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Nama Lengkap Petani <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" placeholder="Contoh: Sutrisno Hartono" required
                               list="daftar-petani"
                               class="w-full rounded-lg border border-paper-300 p-2 text-sm bg-white focus:border-padi-500 focus:outline-none">
                        @if($petaniExisting->isNotEmpty())
                            <datalist id="daftar-petani">
                                @foreach($petaniExisting as $pe)
                                    <option value="{{ $pe->nama_lengkap }}">RT {{ $pe->rt }}/RW {{ $pe->rw }} - {{ $pe->nomor_hp }}</option>
                                @endforeach
                            </datalist>
                        @endif
                        @error('nama_lengkap') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Nomor HP / WhatsApp</label>
                        <input type="text" name="nomor_hp" value="{{ old('nomor_hp') }}" placeholder="Contoh: 081234567890"
                               class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono bg-white focus:border-padi-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-paper-700 mb-1">Alamat Dusun / Jalan</label>
                        <input type="text" name="alamat" value="{{ old('alamat') }}" placeholder="Nama Dusun / Jalan..."
                               class="w-full rounded-lg border border-paper-300 p-2 text-sm bg-white focus:border-padi-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-paper-700 mb-1">RT</label>
                            <input type="text" name="rt" value="{{ old('rt') }}" placeholder="01"
                                   class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono bg-white focus:border-padi-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-paper-700 mb-1">RW</label>
                            <input type="text" name="rw" value="{{ old('rw') }}" placeholder="02"
                                   class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono bg-white focus:border-padi-500 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. HIERARKI KOMODITAS (KATEGORI -> MASTER -> ITEM) --}}
            <div class="p-4 rounded-xl bg-paper-50 border border-paper-200 space-y-3">
                <h3 class="text-xs font-mono uppercase tracking-wider text-paper-700 font-semibold">
                    2. Komoditas yang Dihasilkan Petani Ini
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select x-model="kategoriId" @change="masterId = ''; selectedItem = ''" required class="w-full rounded-lg border border-paper-300 p-2 text-sm bg-white focus:border-padi-500 focus:outline-none">
                            <option value="">Pilih Kategori...</option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.nama"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Master Komoditas <span class="text-red-500">*</span></label>
                        <select x-model="masterId" @change="selectedItem = ''; updateSatuan()" required :disabled="!kategoriId" class="w-full rounded-lg border border-paper-300 p-2 text-sm bg-white focus:border-padi-500 focus:outline-none disabled:bg-paper-100">
                            <option value="">Pilih Master...</option>
                            <template x-for="m in masters" :key="m.id">
                                <option :value="m.id" x-text="m.nama"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Item / Subjenis <span class="text-red-500">*</span></label>
                        <select name="item_komoditas_id" x-model="selectedItem" @change="updateSatuan()" required :disabled="!masterId" class="w-full rounded-lg border border-paper-300 p-2 text-sm bg-white font-semibold focus:border-padi-500 focus:outline-none disabled:bg-paper-100">
                            <option value="">Pilih Item Subjenis...</option>
                            <template x-for="it in items" :key="it.id">
                                <option :value="it.id" x-text="it.nama"></option>
                            </template>
                        </select>
                        @error('item_komoditas_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- 3. INFORMASI KAPASITAS PRODUKSI --}}
            <div class="p-4 rounded-xl bg-paper-50 border border-paper-200 space-y-3">
                <h3 class="text-xs font-mono uppercase tracking-wider text-paper-700 font-semibold">
                    3. Informasi Kapasitas Produksi & Lahan
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Luas Lahan (Ha)</label>
                        <input type="number" step="0.01" name="luas_lahan_ha" value="{{ old('luas_lahan_ha', 0.5) }}" placeholder="Contoh: 1.25"
                               class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono bg-white focus:border-padi-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Est. Volume Panen <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="estimasi_volume_panen" value="{{ old('estimasi_volume_panen') }}" placeholder="1000" required
                               class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono bg-white focus:border-padi-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Satuan <span class="text-red-500">*</span></label>
                        <input type="text" name="satuan" x-model="satuan" required
                               class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono bg-white focus:border-padi-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Periode Panen <span class="text-red-500">*</span></label>
                        <select name="periode_panen" required class="w-full rounded-lg border border-paper-300 p-2 text-sm bg-white focus:border-padi-500 focus:outline-none">
                            <option value="Per Musim (3-4 Bulan)">Per Musim (3-4 Bulan)</option>
                            <option value="Per Bulan">Per Bulan</option>
                            <option value="Per Minggu">Per Minggu</option>
                            <option value="Per Tahun">Per Tahun</option>
                            <option value="Harian">Harian</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Keterangan Tambahan Produksi</label>
                    <textarea name="keterangan" rows="2" placeholder="Contoh: Pengairan irigasi teknis, varietas lokal unggul..."
                              class="w-full rounded-lg border border-paper-300 p-2 text-sm bg-white focus:border-padi-500 focus:outline-none">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3">
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-padi-500 text-ink-950 text-xs font-semibold hover:bg-padi-600 shadow-sm">
                    Simpan Data Potensi Petani Desa
                </button>
            </div>
        </form>
    </div>

</x-layouts.app>
