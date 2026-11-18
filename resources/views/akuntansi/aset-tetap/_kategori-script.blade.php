<script>
    (function () {
        const kat = document.getElementById('kategori_aset');
        const umur = document.getElementById('umur_bulan');
        const hint = document.getElementById('tanah_hint');
        const wrapAkum = document.getElementById('wrap_akun_akum');
        const wrapBiaya = document.getElementById('wrap_akun_biaya');
        const akum = document.getElementById('kode_akun_akum');
        const biaya = document.getElementById('kode_akun_biaya');

        function syncTanah() {
            const isTanah = kat && kat.value === 'tanah';
            if (umur) {
                umur.readOnly = isTanah;
                umur.required = !isTanah;
                umur.min = isTanah ? '0' : '1';
                if (isTanah) {
                    umur.value = 0;
                } else if (Number(umur.value) < 1) {
                    umur.value = 48;
                }
            }
            if (hint) hint.classList.toggle('hidden', !isTanah);
            if (wrapAkum) wrapAkum.classList.toggle('opacity-50', isTanah);
            if (wrapBiaya) wrapBiaya.classList.toggle('opacity-50', isTanah);
            if (akum) {
                akum.required = !isTanah;
                if (isTanah) akum.value = '';
            }
            if (biaya) {
                biaya.required = !isTanah;
                if (isTanah) biaya.value = '';
            }
        }

        if (kat) {
            kat.addEventListener('change', syncTanah);
            syncTanah();
        }
    })();
</script>

