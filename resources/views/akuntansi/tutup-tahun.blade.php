<x-layouts.app title="Tutup Tahun" eyebrow="Akuntansi">
    <div class="mx-auto max-w-2xl rounded-sm border border-dashed border-paper-300 bg-paper-50 p-8">
        <h2 class="font-display text-lg font-semibold">Jurnal Penutup &amp; Pembagian SHU</h2>
        <p class="mt-2 text-sm text-ink-700">
            Belum diimplementasikan. Saat dibangun: memvalidasi total persentase
            <code class="rounded bg-paper-200 px-1 py-0.5 font-mono text-xs">config_shu</code>
            tepat 100%, memastikan bulan 1-11 sudah CLOSED dan bulan 12 masih
            OPEN, memposting jurnal penutup (akun 811 Ikhtisar Laba Rugi), lalu
            membagi SHU ke pos-pos sesuai konfigurasi.
        </p>
    </div>
</x-layouts.app>
