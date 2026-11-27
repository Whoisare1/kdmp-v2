<x-layouts.app :title="$title" eyebrow="Modul Pendataan">

    <div class="max-w-2xl bg-white p-4 sm:p-6 rounded-xl border border-paper-200 shadow-sm" x-data="{
        balita: {{ old('balita', $penduduk->balita) }},
        anak: {{ old('anak', $penduduk->anak) }},
        remaja: {{ old('remaja', $penduduk->remaja) }},
        dewasa: {{ old('dewasa', $penduduk->dewasa) }},
        lansia: {{ old('lansia', $penduduk->lansia) }},
        get total() {
            return (parseInt(this.balita) || 0) +
                   (parseInt(this.anak) || 0) +
                   (parseInt(this.remaja) || 0) +
                   (parseInt(this.dewasa) || 0) +
                   (parseInt(this.lansia) || 0);
        }
    }">
        <div class="mb-4">
            <a href="{{ route('pendataan.penduduk.index') }}" class="text-xs text-paper-500 hover:text-ink-900">&larr; Kembali ke Data Penduduk</a>
            <h2 class="text-lg font-display font-semibold text-ink-900 mt-1">Edit Data Penduduk Tahun {{ $penduduk->tahun }}</h2>
            <p class="text-xs text-paper-500">Pembaruan jumlah penduduk per 5 kelompok umur. Total penduduk dihitung otomatis.</p>
        </div>

        <form action="{{ route('pendataan.penduduk.update', $penduduk) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-3 bg-padi-50 border border-padi-200 rounded-lg">
                    <span class="text-[10px] uppercase font-mono tracking-wider text-padi-700 font-bold">Desa Wilayah Aktor</span>
                    <p class="text-base font-display font-bold text-ink-900 mt-0.5">{{ $penduduk->wilayah?->nama ?? $desaAktif?->nama }}</p>
                    <p class="text-xs text-paper-500 font-mono">Kec. {{ $penduduk->wilayah?->parent?->nama ?? 'Juwana' }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Tahun Pendataan</label>
                    <input type="number" value="{{ $penduduk->tahun }}" readonly
                           class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono bg-paper-100 cursor-not-allowed">
                </div>
            </div>

            {{-- 5 Kelompok Umur --}}
            <div class="p-4 rounded-xl bg-paper-50 border border-paper-200/80 space-y-3">
                <h3 class="text-xs font-mono uppercase tracking-wider text-paper-600 font-semibold mb-2">5 Kelompok Umur Penduduk (Jiwa)</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-speech-input
                            name="balita"
                            type="number"
                            label="1. Balita (0–4 tahun)"
                            :required="true"
                            min="0"
                            x-model.number="balita"
                        />
                    </div>

                    <div>
                        <x-speech-input
                            name="anak"
                            type="number"
                            label="2. Anak-anak (5–9 tahun)"
                            :required="true"
                            min="0"
                            x-model.number="anak"
                        />
                    </div>

                    <div>
                        <x-speech-input
                            name="remaja"
                            type="number"
                            label="3. Remaja (10–19 tahun)"
                            :required="true"
                            min="0"
                            x-model.number="remaja"
                        />
                    </div>

                    <div>
                        <x-speech-input
                            name="dewasa"
                            type="number"
                            label="4. Dewasa (20–59 tahun)"
                            :required="true"
                            min="0"
                            x-model.number="dewasa"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <x-speech-input
                            name="lansia"
                            type="number"
                            label="5. Lansia (60 tahun ke atas)"
                            :required="true"
                            min="0"
                            x-model.number="lansia"
                        />
                    </div>
                </div>

                {{-- Total Penduduk Auto Calculate --}}
                <div class="mt-4 p-3 rounded-lg bg-padi-100 border border-padi-300 flex items-center justify-between">
                    <span class="text-xs font-semibold text-ink-900 uppercase">Total Penduduk (Otomatis):</span>
                    <span class="text-lg font-mono font-bold text-ink-950" x-text="total.toLocaleString('id-ID') + ' Jiwa'"></span>
                </div>
            </div>

            <div>
                <x-speech-input
                    name="keterangan"
                    type="textarea"
                    :rows="2"
                    label="Keterangan / Catatan"
                    :value="old('keterangan', $penduduk->keterangan)"
                />
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-2">
                <a href="{{ route('pendataan.penduduk.index') }}" class="w-full sm:w-auto text-center px-4 py-2 rounded-lg border border-paper-300 text-xs font-medium hover:bg-paper-50">Batal</a>
                <button type="submit" class="w-full sm:w-auto text-center px-5 py-2 rounded-lg bg-padi-500 text-ink-950 text-xs font-semibold hover:bg-padi-600 shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</x-layouts.app>
