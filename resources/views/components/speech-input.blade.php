@props([
    'name' => '', 
    'label' => '', 
    'required' => false, 
    'type' => 'text', 
    'placeholder' => '', 
    'min' => null,
    'autoformat' => '', // e.g., 'RT', 'RW'
    'value' => null,
    'xmodel' => '',
    'list' => ''
])

<div x-data="speechInput('{{ $autoformat }}')" class="relative">
    @if($label)
    <label class="block text-xs font-semibold text-ink-600 uppercase tracking-wide mb-1.5">
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
            placeholder="{{ $placeholder }}" 
            {{ $attributes->merge(['class' => 'w-full rounded-xl border-slate-300 bg-white pl-10 pr-12 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 shadow-sm transition-shadow ' . ($type === 'number' ? 'text-lg font-bold text-slate-900 shadow-inner py-3' : '')]) }}
        >
        
        <!-- Microphone Button (Left Side to avoid Datalist arrow collision) -->
        <div class="absolute inset-y-0 left-0 flex items-center pl-2 z-10">
            <button 
                type="button" 
                @click="toggleSpeech()"
                :class="{'text-red-500 animate-pulse bg-red-50': isListening, 'text-ink-400 hover:text-blue-500 hover:bg-blue-50': !isListening}"
                class="p-2 rounded-full transition-all focus:outline-none flex items-center justify-center"
                title="Gunakan suara untuk mengetik"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Status indicator -->
    <div x-show="isListening" x-transition class="absolute -bottom-5 right-0 text-[10px] font-semibold text-red-500 flex items-center gap-1 bg-white px-2 py-0.5 rounded-full shadow-sm border border-red-100 z-10" style="display: none;">
        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>
        Mendengarkan...
    </div>
</div>

@push('scripts')
@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('speechInput', (autoFormat) => ({
            autoFormat: autoFormat,
            isListening: false,
            recognition: null,

            init() {
                if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    this.recognition = new SpeechRecognition();
                    this.recognition.continuous = false;
                    this.recognition.interimResults = false;
                    this.recognition.lang = 'id-ID';

                    this.recognition.onstart = () => {
                        this.isListening = true;
                    };

                    this.recognition.onresult = (event) => {
                        let transcript = event.results[0][0].transcript;
                        const inputElement = this.$refs.inputField;
                        const autoFormatStr = this.autoFormat;
                        
                        if (inputElement) {
                            // Convert Indonesian words to numbers
                            transcript = this.wordsToNumbers(transcript);

                            if (inputElement.type === 'number' || inputElement.type === 'tel') {
                                // Extract digits
                                const numbers = transcript.replace(/[^0-9]/g, '');
                                if (numbers) {
                                    inputElement.value = numbers;
                                } else {
                                    alert('Tolong sebutkan angkanya dengan jelas.');
                                }
                            } else {
                                // Remove ALL periods unconditionally
                                transcript = transcript.replace(/\./g, "");

                                // Auto-formatting (e.g., RT 01)
                                if (autoFormatStr) {
                                    // Extract digits
                                    let digits = transcript.replace(/[^0-9]/g, '');
                                    if (digits) {
                                        // Zero pad if single digit
                                        if (digits.length === 1) {
                                            digits = '0' + digits;
                                        }
                                        transcript = autoFormatStr.toUpperCase() + ' ' + digits;
                                    } else {
                                        // Fallback if no digits found but autoformat is requested
                                        transcript = autoFormatStr.toUpperCase() + ' ' + transcript;
                                    }
                                } else {
                                    // Capitalize first letter for normal text
                                    transcript = transcript.charAt(0).toUpperCase() + transcript.slice(1);
                                }
                                
                                inputElement.value = transcript;
                                inputElement.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }
                    };

                    this.recognition.onerror = (event) => {
                        console.error('Speech recognition error', event.error);
                        this.isListening = false;
                    };

                    this.recognition.onend = () => {
                        this.isListening = false;
                    };
                }
            },

            wordsToNumbers(text) {
                const map = {
                    'nol': 0, 'kosong': 0, 'satu': 1, 'dua': 2, 'tiga': 3, 'empat': 4, 
                    'lima': 5, 'enam': 6, 'tujuh': 7, 'delapan': 8, 'sembilan': 9, 
                    'sepuluh': 10, 'sebelas': 11
                };
                
                // Belas
                text = text.replace(/([a-z]+)\s+belas/gi, (match, p1) => {
                    let key = p1.toLowerCase();
                    return map[key] ? (map[key] + 10).toString() : match;
                });
                
                // Puluh
                text = text.replace(/([a-z]+)\s+puluh(?:\s+([a-z]+))?/gi, (match, p1, p2) => {
                    let k1 = p1.toLowerCase();
                    let val = 0;
                    if (map[k1]) val += map[k1] * 10;
                    if (p2) {
                        let k2 = p2.toLowerCase();
                        if (map[k2]) val += map[k2];
                    }
                    return val > 0 ? val.toString() : match;
                });

                // Satuan
                for (let word in map) {
                    let regex = new RegExp('\\b' + word + '\\b', 'gi');
                    text = text.replace(regex, map[word]);
                }
                
                return text;
            },

            toggleSpeech() {
                if (!this.recognition) {
                    alert('Maaf, Browser Anda tidak mendukung fitur Suara (Voice-to-Text). Gunakan Google Chrome terbaru.');
                    return;
                }

                if (this.isListening) {
                    this.recognition.stop();
                } else {
                    this.recognition.start();
                }
            }
        }));
    });
</script>
@endonce
@endpush
