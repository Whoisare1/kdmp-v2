@props([
    'name' => '', 
    'label' => '', 
    'required' => false, 
    'type' => 'text', 
    'placeholder' => '', 
    'min' => null,
    'step' => null,
    'large' => false,
    'autoformat' => '', // e.g., 'RT', 'RW'
    'value' => null,
    'xmodel' => '',
    'list' => ''
])

<div x-data="speechInput('{{ $autoformat }}')" class="relative">
    @if($label)
    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endif
    
    <div class="relative flex items-center">
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            value="{{ $value ?? old($name) }}"
            x-ref="inputField"
            @if($xmodel) x-model="{{ $xmodel }}" @endif
            @if($list) list="{{ $list }}" @endif
            @if($required) required @endif
            @if($min !== null) min="{{ $min }}" @endif
            @if($step !== null) step="{{ $step }}" @endif
            placeholder="{{ $placeholder }}" 
            {{ $attributes->merge(['class' => 'w-full rounded-xl border-slate-300 bg-white pl-10 pr-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 shadow-sm transition-shadow ' . ($large ? 'text-lg font-bold text-slate-900 shadow-inner py-3' : '')]) }}
        >
        
        <!-- Microphone Button (Left Side) -->
        <div class="absolute inset-y-0 left-0 flex items-center pl-2 z-10">
            <button 
                type="button" 
                @click="toggleSpeech()"
                :class="{'text-red-600 animate-pulse bg-red-100 ring-2 ring-red-400': isListening, 'text-slate-400 hover:text-blue-500 hover:bg-blue-50': !isListening}"
                class="p-2 rounded-full transition-all focus:outline-none flex items-center justify-center cursor-pointer"
                title="Gunakan suara untuk mengetik"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Status indicator -->
    <div x-show="isListening" x-cloak class="absolute -bottom-5 right-0 text-[10px] font-semibold text-red-600 flex items-center gap-1 bg-white px-2 py-0.5 rounded-full shadow-sm border border-red-200 z-20">
        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>
        Mendengarkan...
    </div>
</div>

@push('scripts')
@once
<script>
(function() {
    // Shared Singleton Speech Recognition Manager
    window.__kdmpSpeechManager = window.__kdmpSpeechManager || {
        recognition: null,
        activeComponent: null,
        
        getRecognition() {
            if (!this.recognition && ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                this.recognition = new SpeechRecognition();
                this.recognition.continuous = false;
                this.recognition.interimResults = true;
                this.recognition.lang = 'id-ID';
                this.recognition.maxAlternatives = 10;

                this.recognition.onstart = () => {
                    if (this.activeComponent) {
                        this.activeComponent.isListening = true;
                        const el = this.activeComponent.$refs.inputField;
                        if (el) {
                            this.activeComponent.originalPlaceholder = el.placeholder || '';
                            el.placeholder = 'Mendengarkan... (silakan bicara)';
                        }
                    }
                };

                this.recognition.onresult = (event) => {
                    if (!this.activeComponent) return;

                    let interimTranscript = '';
                    let finalAlternatives = [];

                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        if (event.results[i].isFinal) {
                            for (let j = 0; j < event.results[i].length; j++) {
                                finalAlternatives.push(event.results[i][j].transcript.trim());
                            }
                        } else {
                            interimTranscript += event.results[i][0].transcript;
                        }
                    }

                    const inputEl = this.activeComponent.$refs.inputField;
                    if (inputEl && interimTranscript) {
                        inputEl.placeholder = 'Mendengar: "' + interimTranscript + '"...';
                    }

                    if (finalAlternatives.length > 0) {
                        this.activeComponent.handleResult(finalAlternatives);
                        this.stop();
                    }
                };

                this.recognition.onerror = (event) => {
                    if (this.activeComponent) {
                        this.activeComponent.isListening = false;
                        this.activeComponent.resetPlaceholder();
                    }
                    if (event.error === 'not-allowed') {
                        alert('Akses mikrofon tidak diizinkan. Silakan beri izin mikrofon di pengaturan browser Anda (klik ikon gembok di sebelah URL).');
                    } else if (event.error === 'network') {
                        alert('Gagal terhubung ke server pengenalan suara Google. Pastikan perangkat Anda terhubung ke internet.');
                    }
                    this.activeComponent = null;
                };

                this.recognition.onend = () => {
                    if (this.activeComponent) {
                        this.activeComponent.isListening = false;
                        this.activeComponent.resetPlaceholder();
                        this.activeComponent = null;
                    }
                };
            }
            return this.recognition;
        },

        start(component) {
            const rec = this.getRecognition();
            if (!rec) {
                alert('Browser Anda belum mendukung fitur input suara langsung. Harap gunakan Google Chrome atau Microsoft Edge.');
                return false;
            }

            if (this.activeComponent) {
                this.stop();
            }

            this.activeComponent = component;
            try {
                rec.start();
                return true;
            } catch (e) {
                console.warn('Speech recognition start retry:', e);
                try {
                    rec.stop();
                    setTimeout(() => rec.start(), 150);
                    return true;
                } catch (err) {
                    console.error('Failed to start speech recognition:', err);
                    this.activeComponent = null;
                    return false;
                }
            }
        },

        stop() {
            if (this.recognition) {
                try {
                    this.recognition.stop();
                } catch (e) {}
            }
            if (this.activeComponent) {
                this.activeComponent.isListening = false;
                this.activeComponent.resetPlaceholder();
                this.activeComponent = null;
            }
        }
    };

    function registerSpeechInputComponent() {
        if (!window.Alpine || window.__speechInputRegistered) return;
        window.__speechInputRegistered = true;

        Alpine.data('speechInput', (autoFormat) => ({
            autoFormat: autoFormat || '',
            isListening: false,
            originalPlaceholder: '',

            init() {
                // Siap digunakan
            },

            toggleSpeech() {
                const mgr = window.__kdmpSpeechManager;
                if (this.isListening) {
                    mgr.stop();
                } else {
                    mgr.start(this);
                }
            },

            resetPlaceholder() {
                const el = this.$refs.inputField;
                if (el && this.originalPlaceholder) {
                    el.placeholder = this.originalPlaceholder;
                }
            },

            handleResult(finalAlternatives) {
                const inputElement = this.$refs.inputField;
                if (!inputElement || finalAlternatives.length === 0) return;

                let chosenText = finalAlternatives[0];
                const autoFormatStr = this.autoFormat;

                // 1. Cek Datalist jika ada (misal komoditas)
                const listId = inputElement.getAttribute('list');
                let options = [];
                if (listId) {
                    const dl = document.getElementById(listId);
                    if (dl) {
                        options = Array.from(dl.querySelectorAll('option')).map(o => o.value).filter(Boolean);
                    }
                }

                if (options.length > 0) {
                    let bestMatch = null;
                    let highestScore = 0;
                    for (const candidate of finalAlternatives) {
                        for (const opt of options) {
                            const score = this.calculateSimilarity(candidate, opt);
                            if (score > highestScore) {
                                highestScore = score;
                                bestMatch = opt;
                            }
                        }
                    }
                    if (bestMatch && highestScore >= 0.55) {
                        chosenText = bestMatch;
                    }
                }

                // 2. Format Angka jika input number atau tel
                if (inputElement.type === 'tel') {
                    let digits = chosenText.replace(/[^0-9]/g, '');
                    if (digits) {
                        inputElement.value = digits;
                        if (inputElement._x_model) {
                            try { inputElement._x_model.set(digits); } catch(e) {}
                        }
                    } else {
                        this.resetPlaceholder();
                        return;
                    }
                } else if (inputElement.type === 'number') {
                    chosenText = this.wordsToNumbers(chosenText);
                    // Bersihkan agar hanya angka dan desimal murni yang tersisa
                    const cleanNumber = this.parseCleanNumber(chosenText);
                    if (cleanNumber !== '') {
                        inputElement.value = cleanNumber;
                        if (inputElement._x_model) {
                            try { inputElement._x_model.set(cleanNumber); } catch(e) {}
                        }
                    } else {
                        this.resetPlaceholder();
                        return;
                    }
                } else {
                    // 3. Format Teks Normal
                    chosenText = this.correctLabialWords(chosenText);
                    chosenText = chosenText.replace(/\./g, "");

                    if (autoFormatStr) {
                        let digits = chosenText.replace(/[^0-9]/g, '');
                        if (digits) {
                            if (digits.length === 1) digits = '0' + digits;
                            chosenText = autoFormatStr.toUpperCase() + ' ' + digits;
                        } else {
                            chosenText = autoFormatStr.toUpperCase() + ' ' + chosenText;
                        }
                    } else {
                        chosenText = chosenText.charAt(0).toUpperCase() + chosenText.slice(1);
                    }

                    inputElement.value = chosenText;
                    if (inputElement._x_model) {
                        try { inputElement._x_model.set(chosenText); } catch(e) {}
                    }
                }

                // Trigger event agar x-model Alpine bereaksi
                inputElement.dispatchEvent(new Event('input', { bubbles: true }));
                inputElement.dispatchEvent(new Event('change', { bubbles: true }));
                this.resetPlaceholder();
            },

            // Bersihkan hasil konversi angka menjadi string angka valid untuk HTML5 input number
            parseCleanNumber(str) {
                if (!str) return '';
                let s = String(str).toLowerCase().trim();
                
                // Hapus awalan "rp", "idr", "rupiah"
                s = s.replace(/^(rp|idr)\.?\s*/i, '').replace(/\s*rupiah$/i, '').trim();

                // Hapus tanda baca di ujung (titik, koma, spasi)
                s = s.replace(/[.,\s]+$/, '').trim();
                if (!s || s === '.') return '';

                // Tangani pemisah ribuan bertitik Indonesia: misal "150.000", "1.500.000", "192.800"
                if (/^\d{1,3}(\.\d{3})+$/.test(s)) {
                    s = s.replace(/\./g, '');
                } else if (/^\d+,\d+$/.test(s)) {
                    // Koma sebagai desimal (contoh: 1,5 -> 1.5)
                    s = s.replace(',', '.');
                } else if (/^\d+(\.\d+)?$/.test(s)) {
                    // Format angka/desimal standar yang sudah valid
                } else {
                    // Bersihkan format campuran (misal: "150.000,50")
                    if (s.includes('.') && s.includes(',')) {
                        s = s.replace(/\./g, '').replace(',', '.');
                    } else if ((s.match(/\./g) || []).length > 1) {
                        s = s.replace(/\./g, '');
                    } else if (s.includes(',')) {
                        s = s.replace(',', '.');
                    }
                    s = s.replace(/[^0-9.]/g, '');
                }

                // Hapus sisa titik di ujung jika ada
                s = s.replace(/\.+$/, '');

                if (s === '' || isNaN(Number(s))) {
                    return '';
                }

                return s;
            },

            // Koreksi Fonetik B & W Indonesia
            correctLabialWords(text) {
                if (!text) return text;
                const map = {
                    'walai': 'balai', 'welakang': 'belakang', 'warat': 'barat',
                    'wiru': 'biru', 'wudi': 'budi', 'wambang': 'bambang',
                    'wawang': 'bawang', 'weras': 'beras', 'wayam': 'bayam',
                    'wuncis': 'buncis', 'barna': 'warna', 'barga': 'warga',
                    'barung': 'warung', 'betan': 'wetan', 'baduk': 'waduk',
                    'bortel': 'wortel'
                };
                return text.replace(/\b[a-zA-Z]+\b/g, (word) => {
                    const lower = word.toLowerCase();
                    if (map[lower]) {
                        return word[0] === word[0].toUpperCase() 
                            ? map[lower].charAt(0).toUpperCase() + map[lower].slice(1) 
                            : map[lower];
                    }
                    return word;
                });
            },

            normalizeCommodity(text) {
                if (!text) return '';
                let t = text.toLowerCase().trim();

                const bwMap = {
                    'weras': 'beras', 'wawang': 'bawang', 'wayam': 'bayam',
                    'wuncis': 'buncis', 'bortel': 'wortel', 'walai': 'balai',
                    'welakang': 'belakang', 'barung': 'warung'
                };
                for (const [k, v] of Object.entries(bwMap)) {
                    t = t.replace(new RegExp(`\\b${k}\\b`, 'gi'), v);
                }

                const synonyms = {
                    'telor': 'telur ayam',
                    'cabe rawit': 'cabai rawit',
                    'cabe merah': 'cabai merah',
                    'lombok rawit': 'cabai rawit',
                    'lombok merah': 'cabai merah',
                    'lombok': 'cabai rawit',
                    'cabe': 'cabai rawit',
                    'brambang': 'bawang merah',
                    'elpiji': 'gas lpg 3kg',
                    'gas elpiji': 'gas lpg 3kg',
                    'gas melon': 'gas lpg 3kg',
                    'terigu': 'tepung terigu',
                    'minyak kelapa': 'minyak goreng',
                    'minyak sawit': 'minyak goreng'
                };
                for (const [k, v] of Object.entries(synonyms)) {
                    t = t.replace(new RegExp(`\\b${k}\\b`, 'gi'), v);
                }

                t = t.replace(/\b\d+(\.\d+)?\s*(kilo|kg|liter|ltr|gram|gr|ons|bungkus|butir|biji|ikat|papan|potong|tabung|kaleng)?\b/gi, '');
                t = t.replace(/\b(kilo|kg|liter|ltr|gram|gr|ons|bungkus|butir|biji|ikat|papan|potong|tabung|kaleng)\b/gi, '');
                return t.replace(/\s+/g, ' ').trim();
            },

            calculateSimilarity(s1, s2) {
                const norm1 = this.normalizeCommodity(s1);
                const norm2 = this.normalizeCommodity(s2);
                if (norm1 === norm2 && norm1.length > 0) return 1.0;

                const c1 = norm1.replace(/[^a-z0-9]/gi, '');
                const c2 = norm2.replace(/[^a-z0-9]/gi, '');
                if (c1 === c2 && c1.length > 0) return 1.0;

                const words1 = norm1.split(' ').filter(Boolean);
                const words2 = norm2.split(' ').filter(Boolean);
                if (words1.length > 0 && words2.length > 0) {
                    const intersect = words1.filter(w => words2.includes(w));
                    if (intersect.length === words1.length || intersect.length === words2.length) {
                        return 0.95;
                    }
                }

                if (c1.includes(c2) || c2.includes(c1)) return 0.92;

                const p1 = c1.replace(/[bwv]/g, 'b');
                const p2 = c2.replace(/[bwv]/g, 'b');
                if (p1 === p2 && p1.length > 0) return 0.98;

                const m = c1.length, n = c2.length;
                if (m === 0 || n === 0) return 0.0;

                const d = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));
                for (let i = 0; i <= m; i++) d[i][0] = i;
                for (let j = 0; j <= n; j++) d[0][j] = j;

                for (let i = 1; i <= m; i++) {
                    for (let j = 1; j <= n; j++) {
                        const ch1 = c1[i - 1], ch2 = c2[j - 1];
                        let cost = (ch1 === ch2) ? 0 : 
                            ((ch1 === 'b' || ch1 === 'w' || ch1 === 'v') && (ch2 === 'b' || ch2 === 'w' || ch2 === 'v')) ? 0.05 : 1.0;
                        d[i][j] = Math.min(
                            d[i - 1][j] + 1,
                            d[i][j - 1] + 1,
                            d[i - 1][j - 1] + cost
                        );
                    }
                }
                const maxLen = Math.max(m, n);
                return Math.max(0, (maxLen - d[m][n]) / maxLen);
            },

            // Parsing Angka Lisan Bahasa Indonesia Majemuk (Juta, Ribu, Ratus, Belas, Puluh, Setengah)
            wordsToNumbers(text) {
                if (!text) return '';
                let str = String(text).toLowerCase().trim();

                // Bersihkan awalan Rp / Rupiah
                str = str.replace(/^(rp|idr)\.?\s*/i, '').replace(/\s*rupiah$/i, '').trim();

                // Bersihkan tanda baca di ujung seperti titik dan koma dari engine suara
                str = str.replace(/[.,\s]+$/, '').trim();

                // Tangani singkatan informal: 15rb, 15 rb, 15k, 15 k
                str = str.replace(/(\d+)\s*(rb|ribu)/gi, '$1 ribu');
                str = str.replace(/(\d+)\s*k\b/gi, '$1 ribu');
                str = str.replace(/(\d+)\s*(jt|juta)/gi, '$1 juta');

                // Jika sudah berupa angka bertitik ribuan (contoh: 15.000 atau 15000)
                if (/^\d{1,3}(\.\d{3})+$/.test(str)) {
                    return str.replace(/\./g, '');
                }
                if (!isNaN(str) && str !== '') {
                    return str;
                }

                // Tangani kata "setengah"
                str = str.replace(/([a-z\d]+)\s+setengah/gi, (match, p1) => {
                    const units = {'satu':1,'dua':2,'tiga':3,'empat':4,'lima':5,'enam':6,'tujuh':7,'delapan':8,'sembilan':9,'sepuluh':10};
                    const val = !isNaN(p1) ? parseFloat(p1) : (units[p1.toLowerCase()] || 0);
                    return val > 0 ? String(val + 0.5) : '0.5';
                });
                str = str.replace(/\bsetengah\b/gi, '0.5');

                const parseUpTo999 = (s) => {
                    s = s.trim();
                    if (!isNaN(s) && s !== '') return parseFloat(s);
                    let total = 0;

                    // Ratusan
                    if (s.startsWith('seratus')) {
                        total += 100;
                        s = s.substring(7).trim();
                    } else {
                        const ratusMatch = s.match(/^(.*?)\s*ratus\s*(.*)$/i);
                        if (ratusMatch) {
                            const rCount = this.wordsToNumbers(ratusMatch[1]);
                            total += (parseFloat(rCount) || 1) * 100;
                            s = ratusMatch[2].trim();
                        }
                    }

                    const numMap = [
                        ['sembilan puluh', 90], ['delapan puluh', 80], ['tujuh puluh', 70],
                        ['enam puluh', 60], ['lima puluh', 50], ['empat puluh', 40],
                        ['tiga puluh', 30], ['dua puluh', 20], ['sepuluh', 10],
                        ['sembilan belas', 19], ['delapan belas', 18], ['tujuh belas', 17],
                        ['enam belas', 16], ['lima belas', 15], ['empat belas', 14],
                        ['tiga belas', 13], ['dua belas', 12], ['sebelas', 11],
                        ['sembilan', 9], ['delapan', 8], ['tujuh', 7], ['enam', 6],
                        ['lima', 5], ['empat', 4], ['tiga', 3], ['dua', 2], ['satu', 1],
                        ['nol', 0], ['kosong', 0]
                    ];

                    for (const [w, val] of numMap) {
                        if (s === w || s.startsWith(w + ' ') || s.endsWith(' ' + w)) {
                            total += val;
                            s = s.replace(new RegExp(`\\b${w}\\b`, 'i'), '').trim();
                        }
                    }

                    if (!isNaN(s) && s !== '') {
                        total += parseFloat(s);
                    }
                    return total;
                };

                let grandTotal = 0;
                let remaining = str;

                // Juta
                const jutaMatch = remaining.match(/^(.*?)\s*juta\s*(.*)$/i);
                if (jutaMatch) {
                    grandTotal += parseUpTo999(jutaMatch[1]) * 1000000;
                    remaining = (jutaMatch[2] || '').trim();
                }

                // Ribu
                const ribuMatch = remaining.match(/^(.*?)\s*ribu\s*(.*)$/i);
                if (ribuMatch) {
                    const rPart = ribuMatch[1].trim();
                    if (rPart === 'se' || rPart === '') {
                        grandTotal += 1000;
                    } else {
                        grandTotal += parseUpTo999(rPart) * 1000;
                    }
                    remaining = (ribuMatch[2] || '').trim();
                }

                if (remaining !== '') {
                    grandTotal += parseUpTo999(remaining);
                }

                return grandTotal > 0 ? String(grandTotal) : str;
            }
        }));
    }

    if (window.Alpine) {
        registerSpeechInputComponent();
    } else {
        document.addEventListener('alpine:init', registerSpeechInputComponent);
    }
})();
</script>
@endonce
@endpush
