<x-layouts.app :title="$title" eyebrow="Survei — Sesi 1">

    {{-- ═══════════════════════════════════════════════════════════════════
         HEADER
    ════════════════════════════════════════════════════════════════════ --}}
    @php
        $wilayah = $sesi->wilayah;
        $kec     = $wilayah?->parent;
        $kab     = $kec?->parent;
    @endphp

    <div class="mb-6 flex flex-col gap-1">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('survei.sesi1.show', $sesi->id) }}"
               class="text-xs font-medium text-ink-500 hover:text-ink-800 hover:underline">
                ← Kembali ke Sesi 1
            </a>
        </div>
        <h1 class="font-display text-2xl font-semibold text-ink-900">Input Data Demografi Narasumber</h1>
        <div class="flex flex-wrap items-center gap-2 mt-1">
            <span class="text-sm text-ink-600 font-medium">{{ $narasumber->nama_narasumber }}</span>
            <span class="text-ink-300">•</span>
            <span class="rounded-full bg-paper-200 px-2.5 py-0.5 text-xs font-medium text-ink-600">{{ $narasumber->kategori }}</span>
            @if($narasumber->nomor_kontak)
                <span class="text-ink-300">•</span>
                <span class="text-xs text-ink-400 font-mono">{{ $narasumber->nomor_kontak }}</span>
            @endif
            @php
                $stCls = match($narasumber->status) {
                    'draft'         => 'bg-yellow-100 text-yellow-800',
                    'lengkap'       => 'bg-sawah-100 text-sawah-700',
                    'terverifikasi' => 'bg-blue-100 text-blue-800',
                    default         => 'bg-ink-100 text-ink-500',
                };
            @endphp
            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stCls }}">
                {{ \Survei\Models\NarasumberSesi::LABEL_STATUS[$narasumber->status] ?? $narasumber->status }}
            </span>
        </div>

        {{-- Info Survei ringkas --}}
        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-400">
            <span>Desa: <strong class="text-ink-600">{{ $wilayah?->nama ?? '-' }}</strong></span>
            <span>Kec: <strong class="text-ink-600">{{ $kec?->nama ?? '-' }}</strong></span>
            <span>Periode: <strong class="text-ink-600">{{ $sesi->tahun }}</strong></span>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-sm border border-sawah-300 bg-sawah-50 px-4 py-3 text-sm text-sawah-800">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sawah-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form method="POST"
          action="{{ route('survei.sesi1.demografi.store', [$sesi->id, $narasumber->id_narasumber]) }}"
          id="form-demografi"
          novalidate>
        @csrf

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- ═══════════════════════════════════════════════════════════════
                 KOLOM UTAMA: Jumlah KK + Matriks Demografi
            ════════════════════════════════════════════════════════════════ --}}
            <div class="xl:col-span-2 space-y-6">

                {{-- ─── INFORMASI NARASUMBER ───────────────────────────────── --}}
                <div class="rounded-sm border border-paper-300 bg-paper-50 px-5 py-5 space-y-4">
                    <h2 class="font-display text-base font-semibold text-ink-900">Informasi Narasumber</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="nama_narasumber" class="mb-1 block text-sm font-medium text-ink-800">
                                Nama Narasumber <span class="text-merah-500">*</span>
                            </label>
                            <input type="text" id="nama_narasumber" name="nama_narasumber" required maxlength="100"
                                   value="{{ old('nama_narasumber', $narasumber->nama_narasumber) }}"
                                   class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                        </div>
                        <div>
                            <label for="kategori" class="mb-1 block text-sm font-medium text-ink-800">
                                Kategori <span class="text-merah-500">*</span>
                            </label>
                            <select id="kategori" name="kategori" required
                                    class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                                @foreach($kategoriList as $kat)
                                    <option value="{{ $kat }}" {{ old('kategori', $narasumber->kategori) === $kat ? 'selected' : '' }}>
                                        {{ $kat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="nomor_kontak" class="mb-1 block text-sm font-medium text-ink-800">
                                Nomor Kontak <span class="text-xs font-normal text-ink-400">(opsional)</span>
                            </label>
                            <input type="text" id="nomor_kontak" name="nomor_kontak" maxlength="30"
                                   value="{{ old('nomor_kontak', $narasumber->nomor_kontak) }}"
                                   placeholder="Contoh: 0812-3456-7890"
                                   class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                        </div>
                        <div>
                            <label for="keterangan" class="mb-1 block text-sm font-medium text-ink-800">
                                Keterangan <span class="text-xs font-normal text-ink-400">(opsional)</span>
                            </label>
                            <input type="text" id="keterangan" name="keterangan" maxlength="500"
                                   value="{{ old('keterangan', $narasumber->keterangan) }}"
                                   placeholder="Contoh: Ketua RT 01 Dusun Ngroto"
                                   class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                        </div>
                    </div>
                </div>

                {{-- ─── JUMLAH KK ─────────────────────────────────────────── --}}
                <div class="rounded-sm border border-paper-300 bg-paper-50 px-5 py-5">
                    <h2 class="font-display text-base font-semibold text-ink-900 mb-4">Jumlah Kepala Keluarga</h2>
                    <div class="max-w-xs">
                        <label for="jumlah_kk" class="mb-1.5 block text-sm font-medium text-ink-800">
                            Jumlah KK <span class="text-merah-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="number"
                                   id="jumlah_kk"
                                   name="jumlah_kk"
                                   required
                                   min="0"
                                   value="{{ old('jumlah_kk', $jumlahKK) }}"
                                   placeholder="0"
                                   class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2.5 text-lg font-semibold tabular-nums focus:border-merah-400 focus:outline-none @error('jumlah_kk') border-merah-400 @enderror">
                            <span class="text-sm font-medium text-ink-600 whitespace-nowrap">KK</span>
                        </div>
                        @error('jumlah_kk')
                            <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-ink-400">
                            Jumlah kepala keluarga yang dilaporkan narasumber ini.
                        </p>
                    </div>
                </div>

                {{-- ─── MATRIKS DEMOGRAFI ──────────────────────────────────── --}}
                <div class="rounded-sm border border-paper-300 bg-paper-50">
                    <div class="border-b border-paper-200 px-5 py-4">
                        <h2 class="font-display text-base font-semibold text-ink-900">Jumlah Penduduk per Kelompok Umur</h2>
                        <p class="mt-0.5 text-xs text-ink-500">
                            Isi jumlah Laki-laki dan Perempuan. Kolom Total dihitung otomatis.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="tabel-demografi">
                            <thead class="border-b border-paper-200 bg-paper-100">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-ink-500 w-32">Kelompok Umur</th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-ink-500">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v1h20v-1c0-3.3-6.7-5-10-5z"/></svg>
                                            Laki-laki
                                        </span>
                                    </th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-ink-500">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5 text-pink-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v1h20v-1c0-3.3-6.7-5-10-5z"/></svg>
                                            Perempuan
                                        </span>
                                    </th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-ink-500">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-paper-200">
                                @foreach($kelompokUmur as $ku)
                                    @php
                                        $demAda  = $demografiAda->get($ku);
                                        $oldL    = old("jumlah_laki.$ku",    $demAda?->jumlah_laki    ?? 0);
                                        $oldP    = old("jumlah_perempuan.$ku", $demAda?->jumlah_perempuan ?? 0);
                                    @endphp
                                    <tr class="hover:bg-paper-100 transition-colors" data-row="{{ $ku }}">
                                        <td class="px-4 py-2.5">
                                            <span class="font-medium text-ink-800">{{ $ku }}</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number"
                                                   id="laki_{{ $ku }}"
                                                   name="jumlah_laki[{{ $ku }}]"
                                                   min="0"
                                                   value="{{ $oldL }}"
                                                   data-col="laki"
                                                   data-row="{{ $ku }}"
                                                   class="w-full rounded-sm border border-paper-300 bg-white px-2 py-1.5 text-center text-sm tabular-nums focus:border-blue-400 focus:outline-none demografi-input"
                                                   oninput="hitungTotal('{{ $ku }}')">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number"
                                                   id="perempuan_{{ $ku }}"
                                                   name="jumlah_perempuan[{{ $ku }}]"
                                                   min="0"
                                                   value="{{ $oldP }}"
                                                   data-col="perempuan"
                                                   data-row="{{ $ku }}"
                                                   class="w-full rounded-sm border border-paper-300 bg-white px-2 py-1.5 text-center text-sm tabular-nums focus:border-pink-400 focus:outline-none demografi-input"
                                                   oninput="hitungTotal('{{ $ku }}')">
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            <span id="total_{{ $ku }}"
                                                  class="font-semibold text-ink-900 tabular-nums">
                                                {{ $oldL + $oldP }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-paper-300 bg-paper-100">
                                <tr>
                                    <td class="px-4 py-3 font-bold text-ink-900">TOTAL</td>
                                    <td class="px-3 py-3 text-center">
                                        <span id="total_col_laki" class="font-bold tabular-nums text-ink-900">
                                            {{ $demografiAda->sum('jumlah_laki') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span id="total_col_perempuan" class="font-bold tabular-nums text-ink-900">
                                            {{ $demografiAda->sum('jumlah_perempuan') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span id="total_grand" class="font-bold text-merah-600 text-base tabular-nums">
                                            {{ $demografiAda->sum(fn($d) => $d->jumlah_laki + $d->jumlah_perempuan) }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Peringatan jika ada nilai negatif --}}
                    <div id="alert-negatif" class="hidden mx-5 mb-4 rounded-sm border border-merah-200 bg-merah-50 px-3 py-2 text-xs text-merah-800">
                        ⚠ Nilai tidak boleh negatif. Periksa kembali data yang dimasukkan.
                    </div>
                </div>

            </div>{{-- end kolom utama --}}

            {{-- ═══════════════════════════════════════════════════════════════
                 KOLOM KANAN: Sumber Data + Simpan
            ════════════════════════════════════════════════════════════════ --}}
            <div class="space-y-5">

                {{-- Summary Realtime --}}
                <div class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-500 mb-3">Ringkasan</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-600">Total Penduduk</span>
                            <span id="summary_total" class="font-bold text-ink-900 tabular-nums">
                                {{ $demografiAda->sum(fn($d) => $d->jumlah_laki + $d->jumlah_perempuan) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-600">Laki-laki</span>
                            <span id="summary_laki" class="font-medium text-blue-700 tabular-nums">
                                {{ $demografiAda->sum('jumlah_laki') }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-600">Perempuan</span>
                            <span id="summary_perempuan" class="font-medium text-pink-700 tabular-nums">
                                {{ $demografiAda->sum('jumlah_perempuan') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Sumber Data --}}
                <div class="rounded-sm border border-paper-300 bg-paper-50 px-5 py-5">
                    <h3 class="font-display text-sm font-semibold text-ink-900 mb-4">Sumber Data</h3>

                    <div class="space-y-3">
                        <div>
                            <label for="sumber_data" class="mb-1 block text-sm font-medium text-ink-800">
                                Jenis Sumber <span class="text-merah-500">*</span>
                            </label>
                            <select id="sumber_data" name="sumber_data" required
                                    class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none @error('sumber_data') border-merah-400 @enderror">
                                @foreach($sumberDataList as $sd)
                                    <option value="{{ $sd }}"
                                            {{ old('sumber_data', $sumberAda?->sumber_data) === $sd ? 'selected' : '' }}>
                                        {{ $sd }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sumber_data')
                                <p class="mt-1 text-xs text-merah-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tanggal_data" class="mb-1 block text-sm font-medium text-ink-800">
                                Tanggal Data
                                <span class="text-xs font-normal text-ink-400">(opsional)</span>
                            </label>
                            <input type="date" id="tanggal_data" name="tanggal_data"
                                   value="{{ old('tanggal_data', $sumberAda?->tanggal_data?->format('Y-m-d')) }}"
                                   class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                        </div>

                        <div>
                            <label for="keterangan_sumber" class="mb-1 block text-sm font-medium text-ink-800">
                                Keterangan
                                <span class="text-xs font-normal text-ink-400">(opsional)</span>
                            </label>
                            <textarea id="keterangan_sumber" name="keterangan_sumber"
                                      rows="3" maxlength="500"
                                      placeholder="Contoh: Data dari dokumen monografi desa tahun 2025"
                                      class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-400 focus:outline-none resize-none">{{ old('keterangan_sumber', $sumberAda?->keterangan_sumber) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="rounded-sm border border-paper-300 bg-paper-50 px-5 py-5 space-y-3">
                    <p class="text-xs text-ink-500 leading-relaxed">
                        <strong>Draft</strong> — menyimpan data tanpa mengubah status narasumber.<br>
                        <strong>Tandai Lengkap</strong> — menandai data narasumber ini sudah lengkap.
                    </p>

                    {{-- Simpan Draft --}}
                    <button type="submit" name="aksi" value="draft"
                            class="w-full rounded-sm border border-paper-300 bg-white px-4 py-2.5 text-sm font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600 transition-colors">
                        Simpan Draft
                    </button>

                    {{-- Simpan & Tandai Lengkap --}}
                    <button type="submit" name="aksi" value="lengkap"
                            class="w-full rounded-sm bg-merah-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-merah-600 transition-colors">
                        Simpan & Tandai Lengkap
                    </button>

                    <div class="pt-1">
                        <a href="{{ route('survei.sesi1.show', $sesi->id) }}"
                           class="block text-center text-xs font-medium text-ink-400 hover:text-ink-700 hover:underline">
                            Kembali tanpa menyimpan
                        </a>
                    </div>
                </div>

            </div>{{-- end kolom kanan --}}

        </div>{{-- end grid --}}

    </form>

    {{-- ═══════════════════════════════════════════════════════════════════
         JAVASCRIPT: Hitung Total Otomatis
    ════════════════════════════════════════════════════════════════════ --}}
    <script>
        const kelompokUmur = @json($kelompokUmur);

        function parseInt0(val) {
            const n = parseInt(val, 10);
            return isNaN(n) || n < 0 ? 0 : n;
        }

        function hitungTotal(ku) {
            const laki     = parseInt0(document.getElementById('laki_' + ku).value);
            const perempuan = parseInt0(document.getElementById('perempuan_' + ku).value);
            const total    = laki + perempuan;

            document.getElementById('total_' + ku).textContent = total.toLocaleString('id-ID');
            hitungGrandTotal();
        }

        function hitungGrandTotal() {
            let totalLaki      = 0;
            let totalPerempuan = 0;
            let totalGrand     = 0;
            let adaNegatif     = false;

            kelompokUmur.forEach(function(ku) {
                const lInput = document.getElementById('laki_' + ku);
                const pInput = document.getElementById('perempuan_' + ku);

                const lVal = parseInt(lInput.value, 10);
                const pVal = parseInt(pInput.value, 10);

                if (lVal < 0 || pVal < 0) adaNegatif = true;

                const l = parseInt0(lInput.value);
                const p = parseInt0(pInput.value);

                totalLaki      += l;
                totalPerempuan += p;
                totalGrand     += l + p;
            });

            document.getElementById('total_col_laki').textContent      = totalLaki.toLocaleString('id-ID');
            document.getElementById('total_col_perempuan').textContent  = totalPerempuan.toLocaleString('id-ID');
            document.getElementById('total_grand').textContent          = totalGrand.toLocaleString('id-ID');
            document.getElementById('summary_laki').textContent         = totalLaki.toLocaleString('id-ID');
            document.getElementById('summary_perempuan').textContent    = totalPerempuan.toLocaleString('id-ID');
            document.getElementById('summary_total').textContent        = totalGrand.toLocaleString('id-ID');

            const alertEl = document.getElementById('alert-negatif');
            if (adaNegatif) {
                alertEl.classList.remove('hidden');
            } else {
                alertEl.classList.add('hidden');
            }
        }

        // Validasi sebelum submit
        document.getElementById('form-demografi').addEventListener('submit', function(e) {
            let valid = true;

            // Cek nilai negatif
            kelompokUmur.forEach(function(ku) {
                const lVal = parseInt(document.getElementById('laki_' + ku).value, 10);
                const pVal = parseInt(document.getElementById('perempuan_' + ku).value, 10);
                if (lVal < 0 || pVal < 0) valid = false;
            });

            const kk = parseInt(document.getElementById('jumlah_kk').value, 10);
            if (isNaN(kk) || kk < 0) valid = false;

            if (!valid) {
                e.preventDefault();
                document.getElementById('alert-negatif').classList.remove('hidden');
                document.getElementById('alert-negatif').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // Hitung saat halaman dimuat (untuk mengisi ulang dari old() values)
        document.addEventListener('DOMContentLoaded', function() {
            hitungGrandTotal();
        });
    </script>

</x-layouts.app>
