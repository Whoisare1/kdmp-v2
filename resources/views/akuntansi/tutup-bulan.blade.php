<x-layouts.app title="Tutup Bulan" eyebrow="Akuntansi">
    <div class="mx-auto max-w-2xl rounded-sm border border-dashed border-paper-300 bg-paper-50 p-8">
        <h2 class="font-display text-lg font-semibold">Delapan Validasi Tutup Bulan</h2>
        <p class="mt-2 text-sm text-ink-700">
            Belum diimplementasikan di kerangka ini. Saat dibangun, halaman ini
            akan menjalankan <code class="rounded bg-paper-200 px-1 py-0.5 font-mono text-xs">TutupBukuService::validasiTutupBulan()</code>
            dan menampilkan status kedelapan pemeriksaan (dokumen draft, jurnal
            posted, keseimbangan D=K, kecocokan Persediaan/Piutang/Hutang/
            Persediaan Konsinyasi dengan Buku Besar, dan rekonsiliasi konsinyasi
            antar desa) sebelum tombol "Tutup Bulan" bisa ditekan.
        </p>
        <ol class="mt-4 space-y-2 font-mono text-xs text-ink-600">
            <li>1. Tidak ada dokumen berstatus draft</li>
            <li>2. Semua jurnal periode ini POSTED</li>
            <li>3. Total Debet = Total Kredit</li>
            <li>4. Saldo Persediaan (BB) = nilai fisik stok</li>
            <li>5. Saldo Piutang (BB) = buku pembantu piutang</li>
            <li>6. Saldo Hutang (BB) = buku pembantu hutang</li>
            <li>7. Saldo Persediaan Konsinyasi (BB) = stok titipan</li>
            <li>8. Piutang konsinyasi pemilik = Hutang konsinyasi penerima</li>
        </ol>
    </div>
</x-layouts.app>
