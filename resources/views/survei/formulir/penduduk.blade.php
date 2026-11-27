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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-paper-200 shadow-sm" x-data="{
            balita: {{ old('balita', 0) }},
            anak: {{ old('anak', 0) }},
            remaja: {{ old('remaja', 0) }},
            dewasa: {{ old('dewasa', 0) }},
            lansia: {{ old('lansia', 0) }},
            get total() {
                return (parseInt(this.balita) || 0) +
                       (parseInt(this.anak) || 0) +
                       (parseInt(this.remaja) || 0) +
                       (parseInt(this.dewasa) || 0) +
                       (parseInt(this.lansia) || 0);
            }
        }">
            <div class="mb-5 border-b border-paper-200 pb-4">
                <div class="flex items-center gap-2 text-xs font-mono text-padi-700 font-semibold mb-1">
                    <span>WILAYAH PENDATAAN DESA</span>
                </div>
                <h2 class="text-xl font-display font-semibold text-ink-900">Formulir Pendataan Penduduk: {{ $desaAktif?->nama ?? 'Desa' }}</h2>
                <p class="text-xs text-paper-500 mt-1">
                    Instrumen survei demografi penduduk per kelompok umur untuk desa ini. Data total terhitung otomatis dari seluruh kategori rentang usia.
                </p>
            </div>

            <form action="{{ route('survei.formulir.penduduk.store') }}" method="POST" class="space-y-4">
                @csrf

                {{-- Banner Kunci Wilayah Desa Aktif --}}
                <div class="p-3.5 bg-padi-50 border border-padi-200 rounded-xl flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-wider text-padi-700 font-bold">Desa Aktor Terpilih</span>
                        <p class="text-base font-display font-bold text-ink-900">{{ $desaAktif?->nama ?? '-' }}</p>
                        <p class="text-xs text-paper-600">Kec. {{ $desaAktif?->parent?->nama ?? 'Juwana' }}, Kab. {{ $desaAktif?->parent?->parent?->nama ?? 'Pati' }}</p>
                    </div>
                    <div class="text-right">
                        <label class="block text-xs font-medium text-paper-700 mb-1">Tahun Pendataan <span class="text-red-500">*</span></label>
                        <input type="number" name="tahun" value="{{ old('tahun', date('Y')) }}" min="2000" max="{{ date('Y')+1 }}" required
                               class="w-32 rounded-lg border border-paper-300 p-2 text-sm font-mono font-bold bg-white focus:border-padi-500 focus:outline-none text-center">
                    </div>
                </div>

                {{-- 5 Kelompok Umur --}}
                <div class="p-4 rounded-xl bg-paper-50 border border-paper-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-mono uppercase tracking-wider text-paper-700 font-semibold">
                            Jumlah Penduduk Berdasarkan 5 Rentang Umur (Jiwa)
                        </h3>
                        <span class="text-[11px] text-paper-500">Standar Klasifikasi</span>
                    </div>

                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-paper-200">
                            <div>
                                <span class="text-xs font-semibold text-ink-900">1. Balita</span>
                                <span class="ml-2 text-[11px] font-mono text-paper-500">Rentang: 0 – 4 tahun</span>
                            </div>
                            <input type="number" name="balita" x-model.number="balita" min="0" required
                                   class="w-36 rounded-lg border border-paper-300 p-1.5 text-sm font-mono text-right focus:border-padi-500 focus:outline-none">
                        </div>

                        <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-paper-200">
                            <div>
                                <span class="text-xs font-semibold text-ink-900">2. Anak-anak</span>
                                <span class="ml-2 text-[11px] font-mono text-paper-500">Rentang: 5 – 9 tahun</span>
                            </div>
                            <input type="number" name="anak" x-model.number="anak" min="0" required
                                   class="w-36 rounded-lg border border-paper-300 p-1.5 text-sm font-mono text-right focus:border-padi-500 focus:outline-none">
                        </div>

                        <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-paper-200">
                            <div>
                                <span class="text-xs font-semibold text-ink-900">3. Remaja</span>
                                <span class="ml-2 text-[11px] font-mono text-paper-500">Rentang: 10 – 19 tahun</span>
                            </div>
                            <input type="number" name="remaja" x-model.number="remaja" min="0" required
                                   class="w-36 rounded-lg border border-paper-300 p-1.5 text-sm font-mono text-right focus:border-padi-500 focus:outline-none">
                        </div>

                        <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-paper-200">
                            <div>
                                <span class="text-xs font-semibold text-ink-900">4. Dewasa</span>
                                <span class="ml-2 text-[11px] font-mono text-paper-500">Rentang: 20 – 59 tahun</span>
                            </div>
                            <input type="number" name="dewasa" x-model.number="dewasa" min="0" required
                                   class="w-36 rounded-lg border border-paper-300 p-1.5 text-sm font-mono text-right focus:border-padi-500 focus:outline-none">
                        </div>

                        <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-paper-200">
                            <div>
                                <span class="text-xs font-semibold text-ink-900">5. Lansia</span>
                                <span class="ml-2 text-[11px] font-mono text-paper-500">Rentang: 60 tahun ke atas</span>
                            </div>
                            <input type="number" name="lansia" x-model.number="lansia" min="0" required
                                   class="w-36 rounded-lg border border-paper-300 p-1.5 text-sm font-mono text-right focus:border-padi-500 focus:outline-none">
                        </div>
                    </div>

                    {{-- Total Otomatis --}}
                    <div class="flex items-center justify-between p-3 bg-padi-100 rounded-lg border border-padi-300 font-bold text-ink-950 mt-2">
                        <span class="text-xs uppercase tracking-wide">Total Penduduk (Otomatis)</span>
                        <div class="text-right">
                            <span class="text-lg font-mono" x-text="total.toLocaleString('id-ID')">0</span>
                            <span class="text-xs font-normal ml-1">Jiwa</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-paper-700 mb-1">Catatan Survei / Keterangan</label>
                    <textarea name="keterangan" rows="2" placeholder="Catatan hasil survei lapangan penduduk desa..."
                              class="w-full rounded-lg border border-paper-300 p-2 text-sm focus:border-padi-500 focus:outline-none">{{ old('keterangan') }}</textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-padi-500 text-ink-950 text-xs font-semibold hover:bg-padi-600 shadow-sm">
                        Simpan Data Penduduk Desa
                    </button>
                </div>
            </form>
        </div>

        {{-- Panel Kanan: Riwayat Pendataan Desa Ini --}}
        <div class="space-y-4">
            <div class="bg-white p-5 rounded-xl border border-paper-200 shadow-sm">
                <h3 class="text-sm font-display font-semibold text-ink-900 mb-3 flex items-center gap-1.5">
                    Riwayat Demografi {{ $desaAktif?->nama ?? 'Desa' }}
                </h3>

                @if($riwayatData->isEmpty())
                    <p class="text-xs text-paper-400">Belum ada riwayat pendataan untuk desa ini.</p>
                @else
                    <div class="space-y-2">
                        @foreach($riwayatData as $row)
                            <div class="p-3 bg-paper-50 rounded-lg border border-paper-200">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-bold font-mono text-ink-900">Tahun {{ $row->tahun }}</span>
                                    <span class="text-xs font-bold font-mono text-padi-700">{{ number_format($row->total_penduduk) }} Jiwa</span>
                                </div>
                                <div class="grid grid-cols-5 text-[10px] text-paper-500 text-center border-t border-paper-200 pt-1 mt-1 font-mono">
                                    <div><span class="block text-[9px] text-paper-400">Balita</span>{{ $row->balita }}</div>
                                    <div><span class="block text-[9px] text-paper-400">Anak</span>{{ $row->anak }}</div>
                                    <div><span class="block text-[9px] text-paper-400">Remaja</span>{{ $row->remaja }}</div>
                                    <div><span class="block text-[9px] text-paper-400">Dewasa</span>{{ $row->dewasa }}</div>
                                    <div><span class="block text-[9px] text-paper-400">Lansia</span>{{ $row->lansia }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($pertanyaanList->isNotEmpty())
                <div class="bg-white p-5 rounded-xl border border-paper-200 shadow-sm">
                    <h3 class="text-xs font-mono uppercase tracking-wider text-paper-700 font-semibold mb-2">
                        Pertanyaan Acuan Survei
                    </h3>
                    <ul class="text-xs text-paper-600 space-y-2">
                        @foreach($pertanyaanList as $pt)
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-padi-600">•</span>
                                <span>{{ $pt->teks_pertanyaan }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

</x-layouts.app>
