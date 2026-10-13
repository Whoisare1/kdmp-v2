<x-layouts.app title="Detail Jurnal #{{ $jurnal->id_jurnal }}" eyebrow="Akuntansi">

    {{-- ===== Back nav ===== --}}
    <div class="mb-5">
        <a href="{{ route('akuntansi.jurnal.index', ['tahun' => $jurnal->periode_tahun, 'bulan' => $jurnal->periode_bulan]) }}"
           class="inline-flex items-center gap-1 text-sm text-ink-500 hover:text-ink-800 transition">
            ← Kembali ke Buku Jurnal
        </a>
    </div>

    {{-- ===== Header Jurnal ===== --}}
    <div class="mb-6 rounded border border-paper-200 bg-white shadow-sm">
        <div class="flex items-start justify-between gap-6 border-b border-paper-200 px-6 py-4">
            <div>
                <p class="font-mono text-xs text-ink-400">No. Jurnal</p>
                <h1 class="font-display text-lg font-bold text-ink-900 mt-0.5">{{ $jurnal->no_jurnal }}</h1>
            </div>
            <div class="text-right">
                @php
                    $statusColor = match($jurnal->status) {
                        'POSTED'   => 'bg-sawah-100 text-sawah-700 border-sawah-500',
                        'DRAFT'    => 'bg-padi-100 text-padi-600 border-padi-500',
                        'REVERSED' => 'bg-paper-200 text-ink-400 border-paper-300',
                        default    => 'bg-paper-100 text-ink-500 border-paper-200',
                    };
                @endphp
                <div class="flex items-center gap-3 justify-end">
                    <span class="rounded border px-3 py-1 text-sm font-semibold {{ $statusColor }}">
                        {{ $jurnal->status }}
                    </span>
                    @if($jurnal->status === 'POSTED')
                        <form action="{{ route('akuntansi.jurnal.balik', $jurnal->id_jurnal) }}" method="POST" onsubmit="return confirm('Yakin ingin membalik (reverse) jurnal ini? Proses ini akan membuat jurnal baru dengan arah kebalikan.');">
                            @csrf
                            <input type="hidden" name="alasan" value="Koreksi Otomatis dari Detail Jurnal">
                            <button type="submit" class="rounded bg-merah-500 px-3 py-1 text-sm font-medium text-white hover:bg-merah-600 transition">
                                Balik Jurnal
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-px bg-paper-200 md:grid-cols-4">
            @php
                $meta = [
                    'Tanggal'        => \Carbon\Carbon::parse($jurnal->tanggal_jurnal)->translatedFormat('d F Y'),
                    'Periode'        => \Carbon\Carbon::create($jurnal->periode_tahun, $jurnal->periode_bulan, 1)->translatedFormat('F Y'),
                    'Kode Transaksi' => $jurnal->kode_transaksi ?? '—',
                    'Jenis Jurnal'   => $jurnal->jenis_jurnal,
                ];
            @endphp
            @foreach($meta as $label => $val)
                <div class="bg-white px-5 py-3">
                    <p class="text-xs text-ink-400">{{ $label }}</p>
                    <p class="mt-0.5 text-sm font-medium text-ink-800">{{ $val }}</p>
                </div>
            @endforeach
        </div>

        @if($jurnal->keterangan)
            <div class="border-t border-paper-200 bg-paper-50 px-6 py-3">
                <p class="text-xs text-ink-400 mb-0.5">Keterangan</p>
                <p class="text-sm text-ink-800">{{ $jurnal->keterangan }}</p>
            </div>
        @endif

        {{-- Relasi: jurnal asal / pembalik --}}
        @if($jurnalAsal || $jurnalPembalik)
            <div class="border-t border-paper-200 px-6 py-3 flex flex-wrap gap-4 text-xs">
                @if($jurnalAsal)
                    <span class="text-ink-500">
                        Jurnal pembalik dari:
                        <a href="{{ route('akuntansi.jurnal.show', $jurnalAsal->id_jurnal) }}"
                           class="font-mono text-merah-500 hover:underline">
                            {{ $jurnalAsal->no_jurnal }}
                        </a>
                    </span>
                @endif
                @if($jurnalPembalik)
                    <span class="text-ink-500">
                        Dibalik oleh:
                        <a href="{{ route('akuntansi.jurnal.show', $jurnalPembalik->id_jurnal) }}"
                           class="font-mono text-merah-500 hover:underline">
                            {{ $jurnalPembalik->no_jurnal }}
                        </a>
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- ===== Tabel Detail ===== --}}
    <div class="rounded border border-paper-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-paper-200 bg-paper-50 px-5 py-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-ink-900">Baris Jurnal</h2>
            <span class="text-xs text-ink-500">Double-entry</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-paper-200 bg-paper-100 text-xs font-medium uppercase tracking-wide text-ink-500">
                    <th class="px-4 py-2.5 text-left w-8">#</th>
                    <th class="px-4 py-2.5 text-left">Kode Akun</th>
                    <th class="px-4 py-2.5 text-left">Nama Rekening</th>
                    <th class="px-4 py-2.5 text-left">Kelompok</th>
                    <th class="px-4 py-2.5 text-left">Keterangan Baris</th>
                    <th class="px-4 py-2.5 text-right">Debet (Rp)</th>
                    <th class="px-4 py-2.5 text-right">Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @foreach($jurnal->detail as $baris)
                    <tr class="hover:bg-paper-50 transition">
                        <td class="px-4 py-2.5 text-xs text-ink-400">{{ $baris->urutan }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-ink-700">{{ $baris->kode_anak }}</td>
                        <td class="px-4 py-2.5 text-ink-800 font-medium">
                            {{ $baris->akun?->nama_rekening ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-xs text-ink-500">
                            {{ $baris->akun?->kelompok ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-xs text-ink-600">
                            {{ $baris->keterangan ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono text-ink-900">
                            @if($baris->debet > 0)
                                {{ number_format($baris->debet, 0, ',', '.') }}
                            @else
                                <span class="text-ink-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono text-ink-900">
                            @if($baris->kredit > 0)
                                {{ number_format($baris->kredit, 0, ',', '.') }}
                            @else
                                <span class="text-ink-300">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-paper-300 bg-paper-100 font-semibold text-sm">
                    <td colspan="5" class="px-4 py-3 text-right text-xs uppercase tracking-wider text-ink-500">TOTAL</td>
                    <td class="px-4 py-3 text-right font-mono text-ink-900">
                        {{ number_format($jurnal->total_debet, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-ink-900">
                        {{ number_format($jurnal->total_kredit, 0, ',', '.') }}
                    </td>
                </tr>
                @if(abs($jurnal->total_debet - $jurnal->total_kredit) > 0.01)
                    <tr>
                        <td colspan="7" class="px-4 py-2 text-xs text-merah-600 bg-merah-50">
                            ⚠️ Jurnal tidak balance — selisih Rp {{ number_format(abs($jurnal->total_debet - $jurnal->total_kredit), 0, ',', '.') }}
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="7" class="px-4 py-2 text-xs text-sawah-600 bg-sawah-100">
                            ✅ Balance
                        </td>
                    </tr>
                @endif
            </tfoot>
        </table>
    </div>

    {{-- ===== Info Posted By ===== --}}
    @if($jurnal->posted_at)
        <p class="mt-4 text-xs text-ink-400">
            Diposting pada {{ \Carbon\Carbon::parse($jurnal->posted_at)->translatedFormat('d F Y H:i') }}
        </p>
    @endif

</x-layouts.app>
