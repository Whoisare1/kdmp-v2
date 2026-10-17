<x-layouts.app title="Detail Piutang" eyebrow="Keuangan">
    <div class="mb-5 flex items-center justify-between">
        <a href="{{ route('keuangan.piutang.index') }}" class="text-sm text-ink-500 hover:text-merah-500 hover:underline">&larr; Kembali ke Daftar Piutang</a>
        @if($item->status !== 'lunas')
            <a href="{{ route('keuangan.pelunasan.create', ['jenis' => 'terima_piutang', 'id_pihak' => $item->id_pihak]) }}" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-white hover:bg-merah-600">Terima Pelunasan</a>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-ink-700">Informasi Piutang</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-ink-600">Pihak (Debitur)</dt>
                    <dd class="font-medium text-ink-900">{{ $item->pihak->nama ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-600">Tanggal Transaksi</dt>
                    <dd class="text-ink-900">{{ $item->tanggal->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-600">Jatuh Tempo</dt>
                    <dd class="text-ink-900 {{ $item->tgl_jatuh_tempo->isPast() && $item->status !== 'lunas' ? 'text-merah-600 font-semibold' : '' }}">{{ $item->tgl_jatuh_tempo->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-600">Sumber</dt>
                    <dd class="text-ink-900">{{ $item->sumber_tipe }} (ID: {{ $item->sumber_id }})</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-600">Status</dt>
                    <dd>
                        @if($item->status === 'lunas')
                            <span class="rounded-full bg-sawah-100 px-2 py-0.5 text-xs text-sawah-700">Lunas</span>
                        @elseif($item->status === 'sebagian')
                            <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700">Sebagian</span>
                        @else
                            <span class="rounded-full bg-merah-100 px-2 py-0.5 text-xs text-merah-700">Belum Lunas</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-ink-700">Ringkasan Nilai</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between text-base">
                    <dt class="text-ink-600">Nilai Awal</dt>
                    <dd class="font-medium text-ink-900">Rp {{ number_format($item->nilai_awal, 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between text-base">
                    <dt class="text-ink-600">Telah Dibayar</dt>
                    <dd class="text-sawah-600">Rp {{ number_format($item->nilai_terbayar, 2, ',', '.') }}</dd>
                </div>
                <div class="mt-2 flex justify-between border-t border-paper-300 pt-2 text-lg font-bold">
                    <dt class="text-ink-900">Sisa Piutang</dt>
                    <dd class="text-merah-600">Rp {{ number_format($item->sisa(), 2, ',', '.') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-layouts.app>
