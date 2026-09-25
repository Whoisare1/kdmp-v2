{{-- Component Speech Scripts --}}
<script>
    // Placeholder untuk fitur Web Speech API (Voice-to-Text).
    // Komponen ini dipanggil dengan <x-speech-scripts /> di berbagai view (Sesi 1-4).
    document.addEventListener('alpine:init', () => {
        Alpine.data('speechScripts', () => ({
            isListening: false,
            recognition: null,
            init() {
                if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    this.recognition = new SpeechRecognition();
                    this.recognition.lang = 'id-ID';
                    this.recognition.continuous = false;
                }
            },
            toggleSpeech(targetRef) {
                if(!this.recognition) {
                    alert('Web Speech API tidak didukung browser ini.');
                    return;
                }
                if(this.isListening) {
                    this.recognition.stop();
                } else {
                    this.recognition.onstart = () => { this.isListening = true; };
                    this.recognition.onend = () => { this.isListening = false; };
                    this.recognition.onresult = (event) => {
                        let transcript = event.results[0][0].transcript;
                        if(this.$refs[targetRef]) {
                            this.$refs[targetRef].value = transcript;
                        }
                    };
                    this.recognition.start();
                }
            }
        }));
    });
</script>
