<x-layouts.app :title="$title" eyebrow="Modul Pendataan">

    <div class="max-w-2xl bg-white p-4 sm:p-6 rounded-xl border border-paper-200 shadow-sm">
        <div class="mb-4">
            <a href="{{ request('redirect_to', route('pendataan.petani.index')) }}" class="text-xs text-paper-500 hover:text-ink-900">&larr; Kembali</a>
            <h2 class="text-lg font-display font-semibold text-ink-900 mt-1">Hubungkan Komoditas Petani: {{ $desaAktif?->nama ?? 'Desa' }}</h2>
            <p class="text-xs text-paper-500">Mencatat komoditas dan estimasi kapasitas panen untuk petani warga desa ini.</p>
        </div>

        <form action="{{ route('pendataan.potensi.store') }}" method="POST" class="space-y-4">
            @csrf
            @if(request('redirect_to'))
                <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
            @endif

            {{-- Info Desa Aktor --}}
            <div class="p-3 bg-padi-50 border border-padi-200 rounded-lg flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-mono tracking-wider text-padi-700 font-bold">Desa Wilayah Aktor</span>
                    <p class="text-base font-display font-bold text-ink-900 mt-0.5">{{ $desaAktif?->nama ?? '-' }}</p>
                    <p class="text-xs text-paper-500 font-mono">Kec. {{ $desaAktif?->parent?->nama ?? 'Juwana' }}</p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded bg-padi-200 text-padi-900 font-medium">Petani Warga Desa Aktif</span>
            </div>

            <div>
                <label class="block text-xs font-medium text-paper-700 mb-1">Pilih Petani Warga Desa <span class="text-red-500">*</span></label>
                <select name="petani_id" required class="w-full rounded-lg border border-paper-300 p-2 text-sm focus:border-padi-500 focus:outline-none">
                    <option value="">Pilih Petani...</option>
                    @foreach($petaniList as $p)
                        <option value="{{ $p->id }}" @selected(old('petani_id', request('petani_id')) == $p->id)>
                            {{ $p->nama_lengkap }} (RT {{ $p->rt ?: '00' }}/RW {{ $p->rw ?: '00' }} - {{ $p->nomor_hp ?: 'No HP (-)' }})
                        </option>
                    @endforeach
                </select>
                @error('petani_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-paper-700 mb-1">Pilih Item / Subjenis Komoditas <span class="text-red-500">*</span></label>
                <select name="item_komoditas_id" id="item_komoditas_id" required class="w-full rounded-lg border border-paper-300 p-2 text-sm focus:border-padi-500 focus:outline-none">
                    <option value="">Pilih Item Komoditas...</option>
                    @foreach($itemList as $item)
                        @php
                            $isEkraf = str_contains(strtolower($item->masterKomoditas?->kategori?->kode ?? ''), 'ekraf') ||
                                       str_contains(strtolower($item->masterKomoditas?->kategori?->nama ?? ''), 'ekraf');
                            $defaultSatuan = $isEkraf ? 'unit' : 'kg';
                        @endphp
                        <option value="{{ $item->id }}" data-satuan="{{ $item->satuan_standar ?: $defaultSatuan }}" data-is-ekraf="{{ $isEkraf ? '1' : '0' }}" @selected(old('item_komoditas_id') == $item->id)>
                            {{ $item->masterKomoditas?->kategori?->nama }} &rarr; {{ $item->masterKomoditas?->nama }} &rarr; {{ $item->nama }}
                        </option>
                    @endforeach
                </select>
                @error('item_komoditas_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-data="{
                volume: '{{ old('estimasi_volume_panen', '') }}',
                hargaSatuan: '{{ old('harga_satuan', '') }}',
                totalNilai: '{{ old('total_nilai', '') }}',
                satuan: '{{ old('satuan', 'kg') }}',
                formatRupiah(num) {
                    if (!num || isNaN(num)) return '0';
                    return new Intl.NumberFormat('id-ID').format(Math.round(num));
                },
                updateFromSatuan() {
                    let v = parseFloat(this.volume);
                    let s = parseFloat(this.hargaSatuan);
                    if (!isNaN(v) && v > 0 && !isNaN(s) && s >= 0) {
                        this.totalNilai = Math.round(v * s);
                    } else if (!this.hargaSatuan) {
                        this.totalNilai = '';
                    }
                },
                updateFromTotal() {
                    let v = parseFloat(this.volume);
                    let t = parseFloat(this.totalNilai);
                    if (!isNaN(v) && v > 0 && !isNaN(t) && t >= 0) {
                        this.hargaSatuan = Math.round(t / v);
                    } else if (!this.totalNilai) {
                        this.hargaSatuan = '';
                    }
                },
                onVolumeChange() {
                    let v = parseFloat(this.volume);
                    if (!isNaN(v) && v > 0) {
                        if (this.hargaSatuan && !isNaN(parseFloat(this.hargaSatuan))) {
                            this.updateFromSatuan();
                        } else if (this.totalNilai && !isNaN(parseFloat(this.totalNilai))) {
                            this.updateFromTotal();
                        }
                    }
                }
            }" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-speech-input
                            name="estimasi_volume_panen"
                            type="number"
                            step="0.1"
                            label="Estimasi Volume Panen"
                            :required="true"
                            :value="old('estimasi_volume_panen')"
                            placeholder="Contoh: 5000"
                            xmodel="volume"
                            @input="onVolumeChange()"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Satuan Hasil <span class="text-red-500">*</span></label>
                        <select name="satuan" id="satuan_input" x-model="satuan" required class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono focus:border-padi-500 focus:outline-none bg-white">
                            <option value="kg" @selected(old('satuan', 'kg') === 'kg')>kg (Kilogram &mdash; Non-Ekraf)</option>
                            <option value="unit" @selected(old('satuan') === 'unit')>unit (Unit &mdash; Ekraf)</option>
                        </select>
                        @error('satuan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Konversi Harga Real-time (2 Arah) -->
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3.5 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Penetapan Harga Komoditas (Otomatis Konversi 2 Arah)
                        </span>
                        <span class="text-[11px] text-emerald-800 font-mono font-medium">
                            Satuan: <strong x-text="satuan"></strong>
                        </span>
                    </div>
                    <p class="text-[11px] text-emerald-800 leading-tight">
                        Bisa mengisi <strong>Harga Satuan</strong> atau <strong>Total Nilai</strong> (ketik atau tekan mikrofon). Sistem otomatis mengonversi timbal-balik berdasarkan volume panen.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-speech-input
                                name="harga_satuan"
                                type="number"
                                step="1"
                                label="Harga Satuan (Rp)"
                                placeholder="Contoh: 35000"
                                :value="old('harga_satuan')"
                                xmodel="hargaSatuan"
                                @input="updateFromSatuan()"
                            />
                            <template x-if="hargaSatuan && !isNaN(hargaSatuan)">
                                <p class="text-[11px] text-emerald-800 mt-1 font-mono">
                                    Rp <span x-text="formatRupiah(hargaSatuan)"></span> / <span x-text="satuan"></span>
                                </p>
                            </template>
                        </div>
                        <div>
                            <x-speech-input
                                name="total_nilai"
                                type="number"
                                step="1"
                                label="Total Nilai Panen (Rp)"
                                placeholder="Contoh: 70000000"
                                :value="old('total_nilai')"
                                xmodel="totalNilai"
                                @input="updateFromTotal()"
                            />
                            <template x-if="totalNilai && !isNaN(totalNilai)">
                                <p class="text-[11px] text-emerald-800 mt-1 font-mono font-semibold">
                                    Total: Rp <span x-text="formatRupiah(totalNilai)"></span>
                                </p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <x-speech-input
                    name="periode_panen"
                    label="Siklus Panen"
                    autoformat="BULAN"
                    :required="true"
                    :value="old('periode_panen')"
                    placeholder="Contoh: 3 - 4 bulan"
                />
            </div>

            <div>
                <x-speech-input
                    name="keterangan"
                    type="textarea"
                    :rows="2"
                    label="Keterangan Tambahan"
                    :value="old('keterangan')"
                    placeholder="Catatan pola tanam, varietas, irigasi..."
                />
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-2">
                <a href="{{ request('redirect_to', route('pendataan.potensi.index')) }}" class="w-full sm:w-auto text-center px-4 py-2 rounded-lg border border-paper-300 text-xs font-medium hover:bg-paper-50">Batal</a>
                <button type="submit" class="w-full sm:w-auto text-center px-5 py-2 rounded-lg bg-padi-500 text-ink-950 text-xs font-semibold hover:bg-padi-600 shadow-sm">
                    Simpan Relasi Potensi
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const itemSelect = document.getElementById('item_komoditas_id');
            const satuanSelect = document.getElementById('satuan_input');

            if (itemSelect && satuanSelect) {
                itemSelect.addEventListener('change', function () {
                    const selectedOpt = itemSelect.options[itemSelect.selectedIndex];
                    const satuan = selectedOpt.getAttribute('data-satuan');
                    if (satuan && (satuan === 'unit' || satuan === 'kg')) {
                        satuanSelect.value = satuan;
                    } else if (selectedOpt.getAttribute('data-is-ekraf') === '1') {
                        satuanSelect.value = 'unit';
                    } else if (selectedOpt.value) {
                        satuanSelect.value = 'kg';
                    }
                    satuanSelect.dispatchEvent(new Event('change'));
                });
            }
        });
    </script>
</x-layouts.app>
