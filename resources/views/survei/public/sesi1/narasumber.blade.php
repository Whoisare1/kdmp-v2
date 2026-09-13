<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Demografi – {{ $narasumber->nama_narasumber }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper-100 font-sans text-ink-900 antialiased min-h-screen pb-16">

    {{-- Header --}}
    <header class="bg-merah-600 text-white shadow-md px-4 py-4 sticky top-0 z-10">
        <div class="max-w-lg mx-auto">
            <a href="{{ route('survei.public.sesi1.show', $token) }}"
               class="text-xs opacity-75 hover:opacity-100 flex items-center gap-1 mb-1">
                ← Kembali ke daftar narasumber
            </a>
            <h1 class="font-semibold text-base leading-tight">{{ $narasumber->nama_narasumber }}</h1>
            <p class="text-xs opacity-80 mt-0.5">{{ $narasumber->kategori }} &bull; {{ $sesi->wilayah->nama ?? '' }} &bull; {{ $sesi->tahun }}</p>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 mt-5">

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ route('survei.public.sesi1.demografi.store', [$token, $narasumber->id_narasumber]) }}"
              id="form-demografi" novalidate>
            @csrf

            {{-- Informasi Narasumber --}}
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm p-4 mb-4 space-y-3">
                <h2 class="font-semibold text-sm text-ink-900 flex items-center">
                    Informasi Narasumber
                    <button type="button" onclick="speakText('Informasi Narasumber. Silakan isi nama, kategori, nomor kontak, dan keterangan.')" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-paper-100 text-ink-500 hover:bg-paper-200 ml-2" title="Bacakan">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"></path></svg>
                    </button>
                </h2>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Nama Narasumber *</label>
                    <div class="relative">
                        <input type="text" id="nama_narasumber" name="nama_narasumber" required maxlength="100"
                               value="{{ old('nama_narasumber', $narasumber->nama_narasumber) }}"
                               class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2 text-sm pr-10 focus:border-merah-500 focus:outline-none">
                        <button type="button" onclick="startDictation('nama_narasumber', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Kategori *</label>
                        <select name="kategori" required
                                class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2 text-sm focus:border-merah-500 focus:outline-none">
                            @foreach($kategoriList as $kat)
                                <option value="{{ $kat }}" {{ old('kategori', $narasumber->kategori) === $kat ? 'selected' : '' }}>
                                    {{ $kat }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-700 mb-1">Nomor Kontak <span class="font-normal text-ink-400">(opsional)</span></label>
                        <div class="relative">
                            <input type="text" id="nomor_kontak" name="nomor_kontak" maxlength="30"
                                   value="{{ old('nomor_kontak', $narasumber->nomor_kontak) }}"
                                   placeholder="0812-xxxx-xxxx"
                                   class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2 text-sm pr-10 focus:border-merah-500 focus:outline-none">
                            <button type="button" onclick="startDictation('nomor_kontak', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Keterangan <span class="font-normal text-ink-400">(opsional)</span></label>
                    <div class="relative">
                        <input type="text" id="keterangan" name="keterangan" maxlength="500"
                               value="{{ old('keterangan', $narasumber->keterangan) }}"
                               placeholder="Contoh: Ketua RT 01 Dusun Ngroto"
                               class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2 text-sm pr-10 focus:border-merah-500 focus:outline-none">
                        <button type="button" onclick="startDictation('keterangan', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Jumlah KK --}}
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-sm text-ink-900 mb-3 flex items-center">
                    Jumlah Kepala Keluarga
                    <button type="button" onclick="speakText('Berapa jumlah total kepala keluarga di desa ini?')" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-paper-100 text-ink-500 hover:bg-paper-200 ml-2" title="Bacakan">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"></path></svg>
                    </button>
                </h2>
                <div class="flex items-center gap-3">
                    <input type="number" id="jumlah_kk" name="jumlah_kk"
                           required min="0"
                           value="{{ old('jumlah_kk', $jumlahKK) }}"
                           placeholder="0"
                           class="flex-1 rounded-lg border border-paper-300 bg-white px-3 py-3 text-xl font-bold text-center tabular-nums focus:border-merah-500 focus:outline-none @error('jumlah_kk') border-red-400 @enderror">
                    <span class="text-sm font-medium text-ink-600">KK</span>
                </div>
                @error('jumlah_kk')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Matriks Demografi --}}
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm overflow-hidden mb-4">
                <div class="px-4 py-3 border-b border-paper-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-sm text-ink-900">Jumlah Penduduk per Kelompok Umur</h2>
                        <p class="text-xs text-ink-400 mt-0.5">Isi Laki-laki dan Perempuan. Total otomatis dihitung.</p>
                    </div>
                    <button type="button" onclick="speakText('Silakan isi jumlah penduduk laki-laki dan perempuan berdasarkan masing-masing kelompok umur: Balita, Anak, Remaja, Dewasa, dan Lansia.')" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-paper-100 text-ink-500 hover:bg-paper-200 flex-shrink-0" title="Bacakan Petunjuk">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-paper-50 border-b border-paper-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-ink-500">Kelompok</th>
                                <th class="px-2 py-2 text-center text-xs font-semibold text-blue-600">L</th>
                                <th class="px-2 py-2 text-center text-xs font-semibold text-pink-600">P</th>
                                <th class="px-2 py-2 text-center text-xs font-semibold text-ink-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-50">
                            @foreach($kelompokUmur as $ku)
                                @php
                                    $demAda = $demografiAda->get($ku);
                                    $oldL   = old("jumlah_laki.$ku",   $demAda?->jumlah_laki   ?? 0);
                                    $oldP   = old("jumlah_perempuan.$ku", $demAda?->jumlah_perempuan ?? 0);
                                @endphp
                                <tr data-row="{{ $ku }}">
                                    <td class="px-4 py-2">
                                        <p class="font-medium text-ink-800 text-xs">{{ $ku }}</p>
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="number"
                                               id="laki_{{ $ku }}"
                                               name="jumlah_laki[{{ $ku }}]"
                                               min="0" value="{{ $oldL }}"
                                               inputmode="numeric"
                                               class="w-full rounded-lg border border-paper-300 bg-white px-1 py-2 text-center text-sm tabular-nums focus:border-blue-400 focus:outline-none"
                                               oninput="hitungTotal('{{ $ku }}')">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="number"
                                               id="perempuan_{{ $ku }}"
                                               name="jumlah_perempuan[{{ $ku }}]"
                                               min="0" value="{{ $oldP }}"
                                               inputmode="numeric"
                                               class="w-full rounded-lg border border-paper-300 bg-white px-1 py-2 text-center text-sm tabular-nums focus:border-pink-400 focus:outline-none"
                                               oninput="hitungTotal('{{ $ku }}')">
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <span id="total_{{ $ku }}" class="font-semibold text-sm text-ink-900 tabular-nums">
                                            {{ $oldL + $oldP }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-paper-200 bg-paper-50">
                            <tr>
                                <td class="px-4 py-2 font-bold text-xs text-ink-900">TOTAL</td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-ink-900" id="total_col_laki">
                                    {{ $demografiAda->sum('jumlah_laki') }}
                                </td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-ink-900" id="total_col_perempuan">
                                    {{ $demografiAda->sum('jumlah_perempuan') }}
                                </td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-merah-600" id="total_grand">
                                    {{ $demografiAda->sum(fn($d) => $d->jumlah_laki + $d->jumlah_perempuan) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div id="alert-negatif" style="display:none" class="mx-4 mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                    ⚠ Nilai tidak boleh negatif.
                </div>
            </div>

            {{-- Sumber Data --}}
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm p-4 mb-5 space-y-3">
                <h2 class="font-semibold text-sm text-ink-900">Sumber Data</h2>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Jenis Sumber *</label>
                    <select name="sumber_data" required
                            class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm focus:border-merah-500 focus:outline-none">
                        @foreach($sumberDataList as $sd)
                            <option value="{{ $sd }}"
                                    {{ old('sumber_data', $sumberAda?->sumber_data) === $sd ? 'selected' : '' }}>
                                {{ $sd }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Tanggal Data <span class="font-normal text-ink-400">(opsional)</span></label>
                    <input type="date" name="tanggal_data"
                           value="{{ old('tanggal_data', $sumberAda?->tanggal_data?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm focus:border-merah-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Keterangan <span class="font-normal text-ink-400">(opsional)</span></label>
                    <div class="relative">
                        <textarea id="keterangan_sumber" name="keterangan_sumber" rows="2" maxlength="500"
                                  placeholder="Contoh: Data dari monografi desa 2025"
                                  class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm pr-10 focus:border-merah-500 focus:outline-none resize-none">{{ old('keterangan_sumber', $sumberAda?->keterangan_sumber) }}</textarea>
                        <button type="button" onclick="startDictation('keterangan_sumber', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 pt-2 text-ink-400 hover:text-merah-600 items-start" title="Gunakan Suara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="space-y-2 pb-6">
                <button type="submit" name="aksi" value="lengkap"
                        class="w-full rounded-xl bg-merah-600 py-3.5 text-sm font-semibold text-white shadow-md hover:bg-merah-700">
                    Simpan & Tandai Lengkap
                </button>
                <button type="submit" name="aksi" value="draft"
                        class="w-full rounded-xl border border-paper-300 bg-white py-3 text-sm font-medium text-ink-700 hover:bg-paper-100">
                    Simpan sebagai Draft
                </button>
                <a href="{{ route('survei.public.sesi1.show', $token) }}"
                   class="block text-center text-xs text-ink-400 hover:text-ink-600 py-2">
                    Kembali tanpa menyimpan
                </a>
            </div>

        </form>
    </main>

    <script>
        const kelompokUmur = @json($kelompokUmur);

        function parseInt0(v) { const n = parseInt(v, 10); return isNaN(n) || n < 0 ? 0 : n; }

        function hitungTotal(ku) {
            const l = parseInt0(document.getElementById('laki_' + ku).value);
            const p = parseInt0(document.getElementById('perempuan_' + ku).value);
            document.getElementById('total_' + ku).textContent = (l + p).toLocaleString('id-ID');
            hitungGrand();
        }

        function hitungGrand() {
            let tL = 0, tP = 0, negatif = false;
            kelompokUmur.forEach(ku => {
                const lv = parseInt(document.getElementById('laki_' + ku).value, 10);
                const pv = parseInt(document.getElementById('perempuan_' + ku).value, 10);
                if (lv < 0 || pv < 0) negatif = true;
                tL += parseInt0(document.getElementById('laki_' + ku).value);
                tP += parseInt0(document.getElementById('perempuan_' + ku).value);
            });
            document.getElementById('total_col_laki').textContent     = tL.toLocaleString('id-ID');
            document.getElementById('total_col_perempuan').textContent = tP.toLocaleString('id-ID');
            document.getElementById('total_grand').textContent         = (tL + tP).toLocaleString('id-ID');
            document.getElementById('alert-negatif').style.display     = negatif ? 'block' : 'none';
        }

        document.getElementById('form-demografi').addEventListener('submit', function(e) {
            let valid = true;
            kelompokUmur.forEach(ku => {
                if (parseInt(document.getElementById('laki_' + ku).value, 10) < 0) valid = false;
                if (parseInt(document.getElementById('perempuan_' + ku).value, 10) < 0) valid = false;
            });
            if (parseInt(document.getElementById('jumlah_kk').value, 10) < 0) valid = false;
            if (!valid) { e.preventDefault(); hitungGrand(); }
        });

        document.addEventListener('DOMContentLoaded', hitungGrand);
    </script>
    <x-speech-scripts />
</body>
</html>
