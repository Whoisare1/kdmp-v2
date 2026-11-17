<x-layouts.app :title="$title" eyebrow="Gudang">
    <div class="mb-4 flex items-center justify-between gap-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
        <a href="{{ route('gudang.kerusakan.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            Kembali ke Kerusakan
        </a>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-2">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Kerusakan Barang</p>
            <p class="mt-2 font-display text-xl font-semibold capitalize text-ink-900">{{ $item->jenis_kejadian }}</p>
            <p class="mt-1 text-sm text-ink-600">{{ $item->tanggal?->format('d M Y') }}</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-5">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-ink-600">Gudang</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->gudang->nama_gudang ?? '-' }}</dd>
                <dt class="text-ink-600">Barang</dt>
                <dd class="text-right font-medium text-ink-800">{{ $item->barang->nama_barang ?? '-' }}</dd>
                @if ($item->relationLoaded('batch') && $item->batch)
                    <dt class="text-ink-600">Batch</dt>
                    <dd class="text-right font-medium text-ink-800">
                        {{ $item->batch->kode_batch }}
                        @if ($item->batch->tanggal_kedaluwarsa)
                            · Kedaluwarsa {{ $item->batch->tanggal_kedaluwarsa->format('d M Y') }}
                        @endif
                    </dd>
                @endif
                <dt class="text-ink-600">Status</dt>
                <dd class="text-right font-medium text-sawah-600">{{ $item->status }}</dd>
            </dl>
        </div>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 px-4 py-3">
            <h2 class="font-display text-base font-semibold text-ink-900">Rincian Kerusakan</h2>
        </div>
        <dl class="grid gap-4 p-5 text-sm md:grid-cols-3">
            <div>
                <dt class="text-ink-600">Qty Rusak / Hilang / Susut / Kedaluwarsa</dt>
                <dd class="mt-1 font-medium text-ink-800">
                    {{ rtrim(rtrim(number_format((float) $item->qty, 4, ',', '.'), '0'), ',') }}
                    {{ $item->barang->satuanDasar->kode_satuan ?? '' }}
                </dd>
            </div>
            <div>
                <dt class="text-ink-600">HPP Rata-rata</dt>
                <dd class="mt-1 font-medium text-ink-800">Rp {{ number_format((float) $item->hpp_rata2, 2, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-ink-600">Nilai Kerugian</dt>
                <dd class="mt-1 font-medium text-merah-600">Rp {{ number_format((float) $item->nilai_kerugian, 2, ',', '.') }}</dd>
            </div>
        </dl>
        <div class="border-t border-paper-300 px-5 py-4 text-sm text-ink-700">
            <span class="font-medium">Keterangan:</span> {{ $item->keterangan ?? '-' }}
        </div>
    </div>

    @if ($item->antreanJurnal->isNotEmpty())
        <div class="mt-5 overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <div class="border-b border-paper-300 px-4 py-3">
                <h2 class="font-display text-base font-semibold text-ink-900">Status Jurnal Keuangan</h2>
            </div>
            <div class="divide-y divide-paper-200">
                @foreach ($item->antreanJurnal as $antrean)
                    <dl class="grid gap-3 p-4 text-sm md:grid-cols-3">
                        <div>
                            <dt class="text-ink-600">No. dokumen antrean</dt>
                            <dd class="mt-1 font-mono font-medium text-ink-900">{{ $antrean->no_dokumen }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-600">Status</dt>
                            <dd class="mt-1 font-medium text-ink-900">{{ $antrean->status }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-600">No. jurnal</dt>
                            <dd class="mt-1 font-medium text-ink-900">{{ $antrean->no_jurnal ?? 'Belum tersedia' }}</dd>
                        </div>
                        @if ($antrean->pesan_error)
                            <div class="md:col-span-3">
                                <dt class="text-ink-600">Keterangan dari Keuangan</dt>
                                <dd class="mt-1 text-merah-600">{{ $antrean->pesan_error }}</dd>
                            </div>
                        @endif
                    </dl>
                @endforeach
            </div>
        </div>
    @endif

    @if ($item->jurnal)
        <div class="mt-5 overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <div class="flex items-center justify-between border-b border-paper-300 px-4 py-3">
                <h2 class="font-display text-base font-semibold text-ink-900">Jurnal Akuntansi Terkait</h2>
                <a href="{{ route('akuntansi.jurnal.show', $item->jurnal->id_jurnal) }}" class="text-xs font-medium text-merah-600 hover:underline">
                    Lihat di Modul Jurnal &rarr;
                </a>
            </div>
            <div class="p-4">
                <div class="mb-3 flex flex-wrap items-center gap-4 text-sm">
                    <div>
                        <span class="text-ink-600">Nomor Jurnal:</span>
                        <span class="font-semibold text-ink-900">{{ $item->jurnal->no_jurnal }}</span>
                    </div>
                    <div>
                        <span class="text-ink-600">Tanggal:</span>
                        <span class="font-medium text-ink-800">{{ $item->jurnal->tanggal_jurnal?->format('d M Y') }}</span>
                    </div>
                    <div>
                        <span class="text-ink-600">Kode Transaksi:</span>
                        <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-xs font-semibold text-ink-800">{{ $item->jurnal->kode_transaksi }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-paper-200 font-mono text-xs uppercase text-ink-600">
                                <th class="py-2">Kode Rekening</th>
                                <th class="py-2">Nama Akun</th>
                                <th class="py-2 text-right">Debet</th>
                                <th class="py-2 text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->jurnal->detail as $baris)
                                <tr class="border-b border-paper-100 last:border-0">
                                    <td class="py-2 font-mono text-xs text-ink-700">{{ $baris->kode_anak }}</td>
                                    <td class="py-2 text-ink-900">{{ $baris->coa->nama_rekening ?? '-' }}</td>
                                    <td class="py-2 text-right text-ink-900">{{ (float) $baris->debet > 0 ? 'Rp ' . number_format((float) $baris->debet, 2, ',', '.') : '-' }}</td>
                                    <td class="py-2 text-right text-ink-900">{{ (float) $baris->kredit > 0 ? 'Rp ' . number_format((float) $baris->kredit, 2, ',', '.') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>
