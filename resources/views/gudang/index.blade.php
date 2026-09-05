<x-layouts.app title="Gudang" eyebrow="Modul Operasional">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('gudang.penerimaan.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Barang Masuk</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Penerimaan Barang</h3>
            <p class="mt-2 text-sm text-ink-700">Catat barang yang masuk ke gudang dan update stok.</p>
        </a>

        <a href="{{ route('gudang.kartu-stok.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Stok</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Kartu Stok</h3>
            <p class="mt-2 text-sm text-ink-700">Lihat riwayat barang masuk, keluar, dan saldo stok.</p>
        </a>

        <a href="{{ route('gudang.stok.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Stok Saat Ini</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Posisi Stok</h3>
            <p class="mt-2 text-sm text-ink-700">Lihat jumlah dan nilai persediaan yang tersedia sekarang.</p>
        </a>

        <a href="{{ route('gudang.opname.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Audit</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Opname</h3>
            <p class="mt-2 text-sm text-ink-700">Hitung fisik barang dan selesaikan selisih stok.</p>
        </a>

        <a href="{{ route('gudang.kerusakan.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Kerusakan</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Barang Rusak</h3>
            <p class="mt-2 text-sm text-ink-700">Catat barang rusak, tidak layak, atau perlu tindakan.</p>
        </a>
    </div>
</x-layouts.app>
