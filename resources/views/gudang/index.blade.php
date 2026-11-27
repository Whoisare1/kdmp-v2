<x-layouts.app title="Gudang" eyebrow="Modul Operasional">
    @if ($jumlahBarangMenipis > 0)
        <a href="{{ route('gudang.stok.index', ['status' => 'menipis']) }}" class="mb-5 flex items-center justify-between gap-4 rounded-sm border border-merah-300 bg-merah-50 p-4 transition hover:bg-merah-100">
            <div>
                <p class="font-mono text-[11px] uppercase tracking-wide text-merah-700">Peringatan Stok Minimum</p>
                <p class="mt-1 text-sm font-medium text-merah-800">{{ $jumlahBarangMenipis }} barang perlu ditinjau untuk pengadaan.</p>
            </div>
            <span class="text-sm font-medium text-merah-700">Lihat daftar &rarr;</span>
        </a>
    @else
        <div class="mb-5 rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/70">Peringatan Stok Minimum</p>
            <p class="mt-1 text-sm text-ink-700">Tidak ada barang yang berada di bawah ambang minimum yang sudah diatur.</p>
        </div>
    @endif
    @if ($pelacakanBatchTersedia && $jumlahBatchKedaluwarsa > 0)
        <a href="{{ route('gudang.batch.index', ['status' => 'kedaluwarsa']) }}" class="mb-5 flex items-center justify-between gap-4 rounded-sm border border-red-300 bg-red-50 p-4 transition hover:bg-red-100">
            <div>
                <p class="font-mono text-[11px] uppercase tracking-wide text-red-800">Peringatan Batch Kedaluwarsa</p>
                <p class="mt-1 text-sm font-medium text-red-900">{{ $jumlahBatchKedaluwarsa }} batch kedaluwarsa masih memiliki saldo stok.</p>
            </div>
            <span class="text-sm font-medium text-red-800">Tinjau batch &rarr;</span>
        </a>
    @endif
    @if ($pelacakanBatchTersedia && $jumlahBatchMendekatiKedaluwarsa > 0)
        <a href="{{ route('gudang.batch.index', ['status' => 'segera']) }}" class="mb-5 flex items-center justify-between gap-4 rounded-sm border border-amber-300 bg-amber-50 p-4 transition hover:bg-amber-100">
            <div>
                <p class="font-mono text-[11px] uppercase tracking-wide text-amber-800">Peringatan Kedaluwarsa</p>
                <p class="mt-1 text-sm font-medium text-amber-900">{{ $jumlahBatchMendekatiKedaluwarsa }} batch akan kedaluwarsa dalam 30 hari.</p>
            </div>
            <span class="text-sm font-medium text-amber-800">Tinjau persediaan</span>
        </a>
    @endif

    @php
        $jumlahRekomendasiMasuk = \App\Models\Perencanaan\RekomendasiPengadaanDesa::where('status', 'diajukan')->count();
    @endphp
    @if ($jumlahRekomendasiMasuk > 0)
        <a href="{{ route('gudang.rekomendasi-pengadaan.index', ['status' => 'diajukan']) }}"
           class="mb-5 flex items-center justify-between gap-4 rounded-sm border border-sawah-300 bg-sawah-50 p-4 transition hover:bg-sawah-100">
            <div>
                <p class="font-mono text-[11px] uppercase tracking-wide text-sawah-700">Rekomendasi Pengadaan Masuk</p>
                <p class="mt-1 text-sm font-medium text-sawah-800">
                    {{ $jumlahRekomendasiMasuk }} rekomendasi desa menunggu persetujuan Anda.
                </p>
            </div>
            <span class="text-sm font-medium text-sawah-700">Tinjau &rarr;</span>
        </a>
    @endif

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

        @if ($pelacakanBatchTersedia)
            <a href="{{ route('gudang.batch.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-merah-400 hover:bg-merah-50">
                <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Pelacakan</p>
                <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Batch &amp; Kedaluwarsa</h3>
                <p class="mt-2 text-sm text-ink-700">Tinjau saldo batch dan tanggal kedaluwarsa persediaan.</p>
            </a>
        @endif

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

        <a href="{{ route('gudang.rekomendasi-pengadaan.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-sawah-400 hover:bg-sawah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Antar Desa</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Rekomendasi Pengadaan</h3>
            <p class="mt-2 text-sm text-ink-700">Setujui atau tolak permintaan surplus dari desa yang membutuhkan.</p>
            @if (($jumlahRekomendasiMasuk ?? 0) > 0)
                <span class="mt-3 inline-block rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                    {{ $jumlahRekomendasiMasuk }} menunggu
                </span>
            @endif
        </a>

        <a href="{{ route('gudang.pencocokan-surplus-defisit.index') }}" class="rounded-sm border border-paper-300 bg-paper-50 p-5 transition hover:border-sawah-400 hover:bg-sawah-50">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Perencanaan</p>
            <h3 class="mt-3 font-display text-xl font-semibold text-ink-900">Pencocokan Surplus-Defisit</h3>
            <p class="mt-2 text-sm text-ink-700">Cocokkan kebutuhan antar desa berdasarkan kalkulasi surplus dan defisit.</p>
        </a>
    </div>
</x-layouts.app>
