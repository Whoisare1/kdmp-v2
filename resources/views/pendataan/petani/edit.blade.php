<x-layouts.app :title="$title" eyebrow="Modul Pendataan">

    <div class="max-w-3xl bg-white p-4 sm:p-6 rounded-xl border border-paper-200 shadow-sm">
        <div class="mb-4">
            <a href="{{ route('pendataan.petani.index') }}" class="text-xs text-paper-500 hover:text-ink-900">&larr; Kembali ke Data Petani</a>
            <h2 class="text-lg font-display font-semibold text-ink-900 mt-1">Edit Petani: {{ $petani->nama_lengkap }}</h2>
            <p class="text-xs text-paper-500">Pembaruan data petani warga desa.</p>
        </div>

        <form action="{{ route('pendataan.petani.update', $petani) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- Info Desa Aktor --}}
            <div class="p-3 bg-padi-50 border border-padi-200 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <span class="text-[10px] uppercase font-mono tracking-wider text-padi-700 font-bold">Desa Domisili Petani</span>
                    <p class="text-base font-display font-bold text-ink-900 mt-0.5">{{ $petani->desa ?: ($desaAktif?->nama ?? '-') }}</p>
                    <p class="text-xs text-paper-500 font-mono">Kec. {{ $petani->kecamatan ?: ($desaAktif?->parent?->nama ?? 'Juwana') }}</p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded bg-padi-200 text-padi-900 font-medium">Desa Aktif</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-speech-input
                        name="nama_lengkap"
                        label="Nama Lengkap Petani"
                        :required="true"
                        :value="old('nama_lengkap', $petani->nama_lengkap)"
                        placeholder="Contoh: Bambang Triyono"
                    />
                </div>

                <div>
                    <x-speech-input
                        name="nomor_hp"
                        type="tel"
                        label="Nomor HP / WhatsApp"
                        :value="old('nomor_hp', $petani->nomor_hp)"
                        placeholder="081234567890"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <x-speech-input
                        name="alamat"
                        label="Alamat Dusun / Jalan"
                        :value="old('alamat', $petani->alamat)"
                        placeholder="Nama Dusun / Jalan..."
                    />
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <x-speech-input
                            name="rt"
                            label="RT"
                            autoformat="RT"
                            :value="old('rt', $petani->rt)"
                            placeholder="01"
                        />
                    </div>
                    <div>
                        <x-speech-input
                            name="rw"
                            label="RW"
                            autoformat="RW"
                            :value="old('rw', $petani->rw)"
                            placeholder="02"
                        />
                    </div>
                </div>
            </div>

            {{-- Komoditas Terlampir --}}
            <div class="pt-4 border-t border-paper-200">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-mono uppercase tracking-wider text-paper-700 font-bold">
                        Komoditas Terlampir Petani Ini
                    </h3>
                    <a href="{{ route('pendataan.potensi.create', ['petani_id' => $petani->id, 'redirect_to' => route('pendataan.petani.edit', $petani)]) }}" class="text-xs text-blue-600 hover:underline">
                        + Tambah via Form Khusus
                    </a>
                </div>

                @if($petani->items->isEmpty())
                    <p class="text-xs text-paper-400 bg-paper-50 p-3 rounded-lg border border-paper-200">
                        Belum ada komoditas yang terhubung dengan petani ini. Anda dapat menambahkannya melalui form di bawah.
                    </p>
                @else
                    <div class="overflow-x-auto rounded-lg border border-paper-200 mb-4">
                        <table class="w-full min-w-[640px] text-left text-xs">
                            <thead class="bg-paper-50 border-b border-paper-200 font-mono text-[11px] text-paper-600">
                                <tr>
                                    <th class="px-3 py-2">Komoditas</th>
                                    <th class="px-3 py-2">Kategori</th>
                                    <th class="px-3 py-2">Estimasi Panen</th>
                                    <th class="px-3 py-2">Harga Satuan</th>
                                    <th class="px-3 py-2">Total Nilai</th>
                                    <th class="px-3 py-2">Siklus Panen</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-paper-100">
                                @foreach($petani->items as $it)
                                    <tr class="hover:bg-paper-50/50">
                                        <td class="px-3 py-2 font-medium text-ink-900">{{ $it->nama }}</td>
                                        <td class="px-3 py-2 text-paper-500">{{ $it->masterKomoditas?->kategori?->nama }}</td>
                                        <td class="px-3 py-2 font-mono font-semibold text-sawah-900">{{ number_format($it->pivot->estimasi_volume_panen) }} {{ $it->pivot->satuan }}</td>
                                        <td class="px-3 py-2 font-mono text-emerald-800 font-medium">
                                            @if($it->pivot->harga_satuan)
                                                Rp {{ number_format($it->pivot->harga_satuan, 0, ',', '.') }}<span class="text-[10px] text-paper-500 font-sans">/{{ $it->pivot->satuan }}</span>
                                            @else
                                                <span class="text-paper-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 font-mono text-emerald-700 font-bold">
                                            @if($it->pivot->total_nilai)
                                                Rp {{ number_format($it->pivot->total_nilai, 0, ',', '.') }}
                                            @else
                                                <span class="text-paper-400 font-normal">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-paper-600 font-medium">{{ $it->pivot->periode_panen ?: '-' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="{{ route('pendataan.potensi.edit', ['potensi' => $it->pivot->id, 'redirect_to' => route('pendataan.petani.edit', $petani)]) }}" class="text-blue-600 hover:underline mr-2 font-medium">
                                                Edit
                                            </a>
                                            <a href="{{ route('pendataan.potensi.destroy', $it->pivot->id) }}"
                                               onclick="event.preventDefault(); if(confirm('Hapus komoditas {{ $it->nama }} dari petani ini?')) { document.getElementById('del-kom-{{ $it->pivot->id }}').submit(); }"
                                               class="text-red-600 hover:underline">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Tambah Komoditas Baru Sekaligus --}}
                <div class="mt-4 p-4 rounded-xl bg-paper-50 border border-paper-200 space-y-3" x-data="{
                    volume: '{{ old('tambah_estimasi_volume_panen', '') }}',
                    hargaSatuan: '{{ old('tambah_harga_satuan', '') }}',
                    totalNilai: '{{ old('tambah_total_nilai', '') }}',
                    satuan: 'kg',
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
                }">
                    <h4 class="text-xs font-semibold text-ink-900 uppercase tracking-wide">
                        + Tambah Komoditas Baru ke Petani Ini
                    </h4>

                    <div>
                        <label class="block text-xs font-medium text-paper-700 mb-1">Pilih Item Komoditas</label>
                        <select name="tambah_item_komoditas_id" id="tambah_item_komoditas_id" class="w-full rounded-lg border border-paper-300 p-2 text-sm bg-white focus:border-padi-500 focus:outline-none">
                            <option value="">Pilih Komoditas jika ingin menambahkan...</option>
                            @foreach($itemList as $item)
                                @php
                                    $isEkraf = str_contains(strtolower($item->masterKomoditas?->kategori?->kode ?? ''), 'ekraf') ||
                                               str_contains(strtolower($item->masterKomoditas?->kategori?->nama ?? ''), 'ekraf');
                                    $defaultSatuan = $isEkraf ? 'unit' : 'kg';
                                @endphp
                                <option value="{{ $item->id }}" data-satuan="{{ $item->satuan_standar ?: $defaultSatuan }}" data-is-ekraf="{{ $isEkraf ? '1' : '0' }}">
                                    {{ $item->masterKomoditas?->kategori?->nama }} &rarr; {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <x-speech-input
                                name="tambah_estimasi_volume_panen"
                                type="number"
                                step="0.1"
                                label="Estimasi Volume Panen"
                                placeholder="Contoh: 1000"
                                xmodel="volume"
                                @input="onVolumeChange()"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-paper-700 mb-1">Satuan</label>
                            <select name="tambah_satuan" id="tambah_satuan" x-model="satuan" class="w-full rounded-lg border border-paper-300 p-2 text-sm font-mono bg-white focus:border-padi-500 focus:outline-none">
                                <option value="kg">kg (Kilogram)</option>
                                <option value="unit">unit (Ekraf)</option>
                            </select>
                        </div>

                        <div>
                            <x-speech-input
                                name="tambah_periode_panen"
                                label="Siklus Panen"
                                autoformat="BULAN"
                                placeholder="Contoh: 3 - 4 bulan"
                                :value="old('tambah_periode_panen')"
                            />
                        </div>
                    </div>

                    <!-- Konversi Harga Real-time (2 Arah) -->
                    <div class="rounded-xl border border-emerald-200 bg-white/80 p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-emerald-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Penetapan Harga Komoditas (Otomatis Konversi 2 Arah)
                            </span>
                            <span class="text-[11px] text-emerald-800 font-mono font-medium">
                                Satuan: <strong x-text="satuan"></strong>
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-speech-input
                                    name="tambah_harga_satuan"
                                    type="number"
                                    step="1"
                                    label="Harga Satuan (Rp)"
                                    placeholder="Contoh: 35000"
                                    :value="old('tambah_harga_satuan')"
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
                                    name="tambah_total_nilai"
                                    type="number"
                                    step="1"
                                    label="Total Nilai Panen (Rp)"
                                    placeholder="Contoh: 70000000"
                                    :value="old('tambah_total_nilai')"
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
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-2">
                <a href="{{ route('pendataan.petani.show', $petani) }}" class="w-full sm:w-auto text-center px-4 py-2 rounded-lg border border-paper-300 text-xs font-medium hover:bg-paper-50">Batal</a>
                <button type="submit" class="w-full sm:w-auto text-center px-5 py-2 rounded-lg bg-padi-500 text-ink-950 text-xs font-semibold hover:bg-padi-600 shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>

        {{-- Hidden Delete Forms for commodities --}}
        @foreach($petani->items as $it)
            <form id="del-kom-{{ $it->pivot->id }}" action="{{ route('pendataan.potensi.destroy', $it->pivot->id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const itemSelect = document.getElementById('tambah_item_komoditas_id');
            const satuanSelect = document.getElementById('tambah_satuan');

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
