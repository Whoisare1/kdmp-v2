<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>{{ $modul->nama }} – Survei {{ $sesi->wilayah->nama ?? '' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper-100 font-sans text-ink-900 antialiased min-h-screen pb-16">

    {{-- Header --}}
    <header class="bg-merah-600 text-white shadow-md px-4 py-4 sticky top-0 z-10">
        <div class="max-w-lg mx-auto">
            <a href="{{ route('survei.public.show', $token) }}"
               class="text-xs opacity-75 hover:opacity-100 flex items-center gap-1 mb-1">
                ← Kembali ke daftar modul
            </a>
            <h1 class="font-semibold text-lg leading-tight">{{ $modul->nama }}</h1>
            <p class="text-sm opacity-80">{{ $sesi->wilayah->nama ?? '' }} &bull; {{ $sesi->bulan }}/{{ $sesi->tahun }}</p>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 mt-6">

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($pertanyaans->isEmpty())
            <div class="bg-white border border-paper-200 rounded-xl p-8 text-center text-ink-500 text-sm shadow-sm">
                Belum ada pertanyaan untuk modul ini.
            </div>
        @else

        <form action="{{ route('survei.public.modul.store', [$token, $modul->id]) }}" method="POST" class="space-y-4">
            @csrf

            @foreach($pertanyaans as $p)
                @php
                    $jawabanLamaItem = $jawabanLama[$p->id] ?? null;
                    $nilaiLama = $jawabanLamaItem
                        ? ($p->tipe_jawaban === 'angka' ? $jawabanLamaItem->nilai_angka : $jawabanLamaItem->nilai_teks)
                        : null;
                    $sudahDiisi = $nilaiLama !== null && $nilaiLama !== '';
                @endphp

                <div class="bg-white rounded-xl border {{ $sudahDiisi ? 'border-green-200' : 'border-paper-200' }} shadow-sm p-4">

                    {{-- Nomor + label pertanyaan --}}
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <label for="p_{{ $p->id }}" class="flex-1 text-sm font-medium text-ink-800 leading-snug">
                            <span class="inline-block text-xs text-ink-400 font-normal mr-1">{{ $loop->iteration }}.</span>
                            {{ $p->teks_pertanyaan }}
                            @if($p->wajib_diisi)
                                <span class="text-red-500 ml-0.5">*</span>
                            @endif
                        </label>

                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            {{-- Status badge --}}
                            @if($sudahDiisi)
                                <span class="inline-block w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            @endif

                            {{-- TTS: dengarkan pertanyaan --}}
                            <button type="button"
                                    onclick="speakText('{{ addslashes($p->teks_pertanyaan) }}')"
                                    class="h-8 w-8 flex items-center justify-center rounded-full text-ink-400 hover:bg-paper-200 hover:text-merah-600 transition-colors"
                                    title="Dengarkan pertanyaan">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Input area --}}
                    <div class="flex items-center gap-2">
                        @if($p->tipe_jawaban === 'pilihan' && !empty($p->aturan_validasi_json['opsi']))
                            <select id="p_{{ $p->id }}" name="jawaban[{{ $p->id }}]"
                                    {{ $p->wajib_diisi ? 'required' : '' }}
                                    class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm
                                           focus:border-merah-500 focus:ring-1 focus:ring-merah-500 focus:outline-none">
                                <option value="">-- Pilih --</option>
                                @foreach($p->aturan_validasi_json['opsi'] as $opsi)
                                    <option value="{{ $opsi }}" {{ $nilaiLama == $opsi ? 'selected' : '' }}>
                                        {{ $opsi }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input
                                type="{{ $p->tipe_jawaban === 'angka' ? 'text' : 'text' }}"
                                inputmode="{{ $p->tipe_jawaban === 'angka' ? 'decimal' : 'text' }}"
                                id="p_{{ $p->id }}"
                                name="jawaban[{{ $p->id }}]"
                                value="{{ old('jawaban.' . $p->id, $nilaiLama) }}"
                                {{ $p->wajib_diisi ? 'required' : '' }}
                                class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm
                                       focus:border-merah-500 focus:ring-1 focus:ring-merah-500 focus:outline-none
                                       placeholder:text-ink-400"
                                placeholder="{{ $sudahDiisi ? 'Ubah jawaban...' : 'Ketik jawaban...' }}"
                            >

                            {{-- STT mic button --}}
                            <button type="button"
                                    onclick="startSpeechToText('p_{{ $p->id }}', this)"
                                    class="btn-mic flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full
                                           bg-paper-100 text-ink-500 hover:bg-merah-50 hover:text-merah-600
                                           transition-colors border border-paper-200 hidden"
                                    title="Isi dengan suara">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                            </button>
                        @endif
                    </div>

                    @if($p->satuan)
                        <p class="text-xs text-ink-400 mt-1.5">Satuan: <strong>{{ $p->satuan }}</strong></p>
                    @endif
                </div>
            @endforeach

            {{-- Tombol simpan --}}
            <div class="pt-2 pb-4 space-y-3">
                <button type="submit"
                        class="w-full bg-merah-600 text-white font-semibold text-base py-3.5 rounded-xl
                               shadow-md hover:bg-merah-700 active:bg-merah-800 transition-colors">
                    Simpan Data Modul Ini
                </button>
                <a href="{{ route('survei.public.show', $token) }}"
                   class="block w-full text-center text-sm text-ink-500 hover:text-ink-700 py-2">
                    ← Kembali ke daftar modul (tanpa menyimpan)
                </a>
            </div>

        </form>
        @endif

    </main>

    <script>
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            document.querySelectorAll('.btn-mic').forEach(btn => btn.classList.remove('hidden'));
        }

        function speakText(text) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                window.speechSynthesis.speak(utterance);
            }
        }

        function startSpeechToText(inputId, btnElement) {
            if (!SpeechRecognition) {
                alert('Browser Anda tidak mendukung fitur input suara. Gunakan Google Chrome.');
                return;
            }

            const recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.interimResults = true;
            recognition.maxAlternatives = 1;

            const origClass = btnElement.className;
            btnElement.classList.remove('bg-paper-100', 'text-ink-500', 'border-paper-200');
            btnElement.classList.add('bg-red-100', 'text-red-600', 'border-red-300', 'animate-pulse');

            recognition.onresult = (event) => {
                let result = '';
                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    result += event.results[i][0].transcript;
                }
                const wordMap = {
                    'nol': '0', 'kosong': '0', 'satu': '1', 'dua': '2', 'tiga': '3',
                    'empat': '4', 'lima': '5', 'enam': '6', 'tujuh': '7',
                    'delapan': '8', 'sembilan': '9', 'sepuluh': '10',
                    'seratus': '100', 'seribu': '1000',
                };
                let clean = result.replace(/[.,!?]+$/, '').trim();
                if (wordMap[clean.toLowerCase()]) clean = wordMap[clean.toLowerCase()];
                const el = document.getElementById(inputId);
                if (el) el.value = clean;
            };

            recognition.onerror = (event) => {
                if (event.error === 'not-allowed') {
                    alert('Izin mikrofon ditolak. Pastikan situs dibuka lewat HTTPS atau localhost.');
                }
            };

            recognition.onend = () => { btnElement.className = origClass; };
            recognition.start();
        }
    </script>

</body>
</html>
