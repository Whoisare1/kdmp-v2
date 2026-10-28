<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Sesi 3 — Standar Konsumsi Komoditas ({{ $sesi->wilayah->nama ?? 'Wilayah' }})</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper-100 font-sans text-ink-900 antialiased min-h-screen pb-12">

    {{-- ═══════════════════════════════════════════════════════════════════
         HEADER HALAMAN
    ════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between px-4 mt-4 max-w-7xl mx-auto">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('survei.public.show', $token) }}"
                   class="text-xs font-medium text-ink-500 hover:text-ink-800 hover:underline">
                    ← Kembali ke Detail Sesi
                </a>
            </div>
            <h1 class="font-display text-2xl font-semibold text-ink-900">Sesi 3 — Standar Konsumsi Komoditas</h1>
            <p class="mt-1 text-sm text-ink-500">Pengumpulan standar konsumsi per orang berdasarkan komoditas, kelompok umur, dan gender.</p>
        </div>

        @php
            $st3    = $sesi->status_sesi3 ?? 'belum_diisi';
            $st3Cls = match($st3) {
                'sedang_diisi'  => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                'selesai'       => 'bg-sawah-100 text-sawah-700 border-sawah-300',
                'terverifikasi' => 'bg-blue-100 text-blue-800 border-blue-300',
                default         => 'bg-ink-100 text-ink-600 border-ink-300',
            };
            $st3Label = $labelStatusSesi3[$st3] ?? 'Belum Diisi';
        @endphp
        <span class="inline-flex items-center self-start rounded-full border px-3 py-1 text-xs font-semibold {{ $st3Cls }}">
            {{ $st3Label }}
        </span>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-sm border border-sawah-300 bg-sawah-50 px-4 py-3 text-sm text-sawah-800">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sawah-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 flex items-start gap-3 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-merah-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="mb-5 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            <p class="font-semibold mb-1">Terdapat kesalahan input:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         INFO SESI (Identitas Survei)
    ════════════════════════════════════════════════════════════════════ --}}
    @php
        $wilayah = $sesi->wilayah;
        $kec     = $wilayah?->parent;
        $kab     = $kec?->parent;
    @endphp
    <div class="max-w-7xl mx-auto px-4">
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach([
            ['label' => 'Desa',         'nilai' => $wilayah?->nama ?? '-'],
            ['label' => 'Kecamatan',    'nilai' => $kec?->nama ?? '-'],
            ['label' => 'Kabupaten',    'nilai' => $kab?->nama ?? '-'],
            ['label' => 'Periode',      'nilai' => $sesi->tahun],
            ['label' => 'Status Sesi 3','nilai' => $st3Label],
        ] as $inf)
            <div class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2.5">
                <p class="text-[10px] font-medium uppercase tracking-wider text-ink-400">{{ $inf['label'] }}</p>
                <p class="mt-0.5 text-sm font-semibold text-ink-800 truncate" title="{{ $inf['nilai'] }}">{{ $inf['nilai'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- ═══════════════════════════════════════════════════════════════
             KOLOM KIRI-TENGAH: Tabel Standar Tersimpan + Aksi
        ════════════════════════════════════════════════════════════════ --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- ─── TABEL STANDAR KONSUMSI TERSIMPAN ──────────────────── --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50">
                <div class="flex items-center justify-between border-b border-paper-200 px-5 py-4">
                    <div>
                        <h2 class="font-display text-base font-semibold text-ink-900">Standar Konsumsi Tersimpan</h2>
                        <p class="mt-0.5 text-xs text-ink-500">{{ $standarAda->count() }} komoditas sudah memiliki standar konsumsi.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('survei.public.sesi3.salin.sesi2', $token) }}" onsubmit="return confirm('Tarik semua komoditas yang diproduksi di Sesi 2 ke daftar ini?')">
                            @csrf
                            <button type="submit"
                                    class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 hover:bg-paper-100 shadow-sm whitespace-nowrap">
                                <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                Tarik dari Sesi 2
                            </button>
                        </form>
                        <button type="button"
                                onclick="bukaModalTambah()"
                                class="flex items-center gap-1.5 rounded-sm bg-merah-500 px-3 py-2 text-xs font-semibold text-white hover:bg-merah-600 shadow-sm whitespace-nowrap">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Isi Standar Komoditas
                        </button>
                    </div>
                </div>

                @if($standarAda->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <svg class="mx-auto mb-3 h-10 w-10 text-ink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm font-medium text-ink-800">Belum ada standar konsumsi yang diisi.</p>
                        <p class="text-xs text-ink-500 mt-1 mb-4">Pilih komoditas manual atau tarik langsung komoditas yang diproduksi dari Sesi 2.</p>
                        <div class="flex items-center justify-center gap-2">
                            <form method="POST" action="{{ route('survei.public.sesi3.salin.sesi2', $token) }}" onsubmit="return confirm('Tarik semua komoditas yang diproduksi di Sesi 2 ke daftar ini?')">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-4 py-2 text-xs font-semibold text-ink-700 hover:bg-paper-100 shadow-sm">
                                    Tarik dari Sesi 2
                                </button>
                            </form>
                            <button type="button" onclick="bukaModalTambah()"
                                    class="inline-flex items-center gap-1.5 rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-white hover:bg-merah-600 shadow-sm">
                                + Isi Standar Komoditas
                            </button>
                        </div>
                    </div>
                @else
                    <div class="divide-y divide-paper-200">
                        @foreach($komoditasList as $kom)
                            @if(isset($standarAda[$kom->id]))
                                @php
                                    $rows    = $standarAda[$kom->id];
                                    $satuan  = $rows->first()->satuan;
                                    $periode = $rows->first()->periode;
                                    $sumber  = $rows->first()->sumber_standar;
                                    $periodeStd = $rows->first()->periode_standar;
                                    $ket     = $rows->first()->keterangan;
                                    // Build 2D array: nilai[umur][gender]
                                    $nilaiGrid = [];
                                    foreach ($rows as $row) {
                                        $nilaiGrid[$row->kategori_umur][$row->kategori_gender] = $row->nilai_konsumsi;
                                    }
                                @endphp
                                <div class="px-5 py-4">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <span class="font-semibold text-sm text-ink-900">{{ $kom->nama }}</span>
                                            <span class="ml-2 inline-block rounded bg-paper-200 px-2 py-0.5 text-[10px] font-medium text-ink-600">{{ $kom->kategori }}</span>
                                            <p class="text-xs text-ink-500 mt-0.5">
                                                Satuan: <strong>{{ $satuan }}</strong> &bull;
                                                Periode: <strong>{{ ucfirst($periode) }}</strong>
                                                @if($sumber) &bull; Sumber: {{ $sumber }} @endif
                                                @if($periodeStd) ({{ $periodeStd }}) @endif
                                            </p>
                                            @if($ket)
                                                <p class="text-xs text-ink-400 mt-0.5 italic">{{ $ket }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                                            <button type="button"
                                                    onclick="bukaModalEdit({{ json_encode($kom) }}, {{ json_encode($nilaiGrid) }}, {{ json_encode(['satuan'=>$satuan,'periode'=>$periode,'sumber_standar'=>$sumber,'periode_standar'=>$periodeStd,'keterangan'=>$ket]) }})"
                                                    class="inline-flex items-center gap-1 rounded-sm border border-paper-300 px-2.5 py-1.5 text-xs font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600 transition-colors">
                                                <svg style="width:13px;height:13px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('survei.public.sesi3.standar.destroy', $token) }}"
                                                  onsubmit="return confirm('Hapus standar konsumsi untuk \"{{ $kom->nama }}\"?')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="id_komoditas" value="{{ $kom->id }}">
                                                <button type="submit"
                                                        class="inline-flex items-center rounded-sm border border-merah-200 px-2 py-1.5 text-xs font-medium text-merah-600 hover:bg-merah-50 hover:border-merah-400 transition-colors">
                                                    <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Tabel standar konsumsi --}}
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs border border-paper-200 rounded-sm">
                                            <thead class="bg-paper-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-ink-600 uppercase tracking-wide text-[10px] border-b border-paper-200">Kelompok Umur</th>
                                                    <th class="px-3 py-2 text-right font-semibold text-ink-600 uppercase tracking-wide text-[10px] border-b border-paper-200">Laki-laki</th>
                                                    <th class="px-3 py-2 text-right font-semibold text-ink-600 uppercase tracking-wide text-[10px] border-b border-paper-200">Perempuan</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-paper-100">
                                                @foreach($kelompokUmur as $ku)
                                                    <tr class="hover:bg-paper-50">
                                                        <td class="px-3 py-2 font-medium text-ink-800">{{ $ku }}</td>
                                                        <td class="px-3 py-2 text-right tabular-nums text-ink-700">
                                                            {{ number_format($nilaiGrid[$ku]['L'] ?? 0, 2, ',', '.') }}
                                                        </td>
                                                        <td class="px-3 py-2 text-right tabular-nums text-ink-700">
                                                            {{ number_format($nilaiGrid[$ku]['P'] ?? 0, 2, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

        </div>{{-- end kolom kiri-tengah --}}

        {{-- ═══════════════════════════════════════════════════════════════
             KOLOM KANAN: Referensi Demografi + Penyelesaian
        ════════════════════════════════════════════════════════════════ --}}
        <div class="space-y-4">

            {{-- ─── Ringkasan Demografi Sesi 1 (READ-ONLY) ───────────── --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50">
                <div class="border-b border-paper-200 px-5 py-4">
                    <h2 class="font-display text-sm font-semibold text-ink-900">Referensi Demografi Desa</h2>
                    <p class="mt-0.5 text-xs text-ink-400">Data dari Sesi 1 — hanya sebagai referensi.</p>
                </div>
                @php
                    $totalL = collect($rekapDemografi)->sum('laki');
                    $totalP = collect($rekapDemografi)->sum('perempuan');
                    $totalPenduduk = $totalL + $totalP;
                @endphp
                @if($totalPenduduk === 0)
                    <div class="px-5 py-6 text-center text-xs text-ink-400">
                        Data demografi belum diisi (Sesi 1 belum lengkap).
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="border-b border-paper-200 bg-paper-100">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-ink-500 uppercase tracking-wide text-[10px]">Kelompok</th>
                                    <th class="px-3 py-2 text-right font-semibold text-ink-500 uppercase tracking-wide text-[10px]">L</th>
                                    <th class="px-3 py-2 text-right font-semibold text-ink-500 uppercase tracking-wide text-[10px]">P</th>
                                    <th class="px-3 py-2 text-right font-semibold text-ink-500 uppercase tracking-wide text-[10px]">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-paper-200">
                                @foreach($kelompokUmur as $ku)
                                    @php $rd = $rekapDemografi[$ku] @endphp
                                    <tr class="hover:bg-paper-100">
                                        <td class="px-3 py-2 font-medium text-ink-700">{{ $ku }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums text-ink-600">{{ number_format($rd['laki']) }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums text-ink-600">{{ number_format($rd['perempuan']) }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums font-semibold text-ink-800">{{ number_format($rd['total']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-paper-300 bg-paper-100">
                                <tr>
                                    <td class="px-3 py-2 font-bold text-ink-900">Total</td>
                                    <td class="px-3 py-2 text-right tabular-nums font-bold text-ink-900">{{ number_format($totalL) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums font-bold text-ink-900">{{ number_format($totalP) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums font-bold text-merah-600">{{ number_format($totalPenduduk) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ─── Summary Cards ─────────────────────────────────────── --}}
            @foreach([
                ['label'=>'Komoditas Terisi', 'nilai'=>$standarAda->count().' / '.($komoditasList->count()).' komoditas', 'cls'=>'text-ink-600','icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['label'=>'Status Sesi 3',    'nilai'=>$st3Label,                    'cls'=>'text-sawah-600','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0'],
            ] as $card)
                <div class="rounded-sm border border-paper-300 bg-paper-50 px-4 py-4 flex items-center gap-3">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-paper-200">
                        <svg class="h-4 w-4 {{ $card['cls'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-ink-500">{{ $card['label'] }}</p>
                        <p class="text-sm font-bold text-ink-900">{{ $card['nilai'] }}</p>
                    </div>
                </div>
            @endforeach

            {{-- ─── Penyelesaian Sesi 3 ───────────────────────────────── --}}
            <div class="rounded-sm border border-paper-300 bg-paper-50">
                <div class="border-b border-paper-200 px-5 py-4">
                    <h2 class="font-display text-sm font-semibold text-ink-900">Penyelesaian Sesi 3</h2>
                    <p class="mt-0.5 text-xs text-ink-500">Simpan draft atau selesaikan sesi ini.</p>
                </div>
                <div class="px-5 py-5 space-y-3">
                    <form method="POST" action="{{ route('survei.public.sesi3.draft', $token) }}">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600 transition-colors">
                            Simpan Draft
                        </button>
                    </form>

                    <hr class="border-paper-200">

                    @if($standarAda->isEmpty())
                        <div class="rounded-sm border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-800">
                            Belum ada standar konsumsi yang diisi. Isi minimal satu komoditas sebelum menyelesaikan.
                        </div>
                    @endif

                    <button type="button"
                            onclick="document.getElementById('modal-selesaikan').classList.remove('hidden')"
                            @if($standarAda->isEmpty()) disabled @endif
                            class="w-full rounded-sm px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors
                                   {{ $standarAda->isEmpty() ? 'bg-ink-300 cursor-not-allowed' : 'bg-merah-500 hover:bg-merah-600' }}">
                        Selesaikan Sesi 3
                    </button>

                    @if($sesi->selesai_sesi3_at)
                        <p class="text-center text-xs text-ink-400">
                            Diselesaikan: {{ $sesi->selesai_sesi3_at->format('d M Y, H:i') }}
                        </p>
                    @endif
                </div>
            </div>

        </div>{{-- end kolom kanan --}}

    </div>{{-- end grid --}}

    {{-- ═══════════════════════════════════════════════════════════════════
         MODAL: Isi / Edit Standar Konsumsi
    ════════════════════════════════════════════════════════════════════ --}}
    <div id="modal-standar"
         class="fixed inset-0 z-50 hidden overflow-y-auto bg-ink-900/60 backdrop-blur-sm"
         onclick="if(event.target===this) tutupModal()">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl rounded-md border border-paper-300 bg-paper-50 shadow-2xl" onclick="event.stopPropagation()">

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-paper-200 bg-paper-100/90 px-5 py-4 rounded-t-md">
                    <div>
                        <h3 id="modal-standar-title" class="font-display text-base font-semibold text-ink-900 flex items-center">
                            Isi Standar Konsumsi
                            <button type="button" onclick="speakText('Silakan isi form standar konsumsi komoditas berikut.')" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-paper-100 text-ink-500 hover:bg-paper-200 ml-2" title="Bacakan">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"></path></svg>
                            </button>
                        </h3>
                        <p id="modal-standar-subtitle" class="text-xs text-ink-500 mt-0.5"></p>
                    </div>
                    <button type="button" onclick="tutupModal()"
                            class="flex items-center justify-center w-8 h-8 rounded-full bg-paper-200 text-ink-700 hover:bg-merah-500 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <form id="form-standar" method="POST" action="" class="p-5 space-y-5">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">
                    <input type="hidden" name="id_komoditas" id="form-id-komoditas">

                    {{-- 1. Pilih Komoditas --}}
                    <div id="row-pilih-komoditas">
                        <label class="block text-xs font-medium text-ink-800 mb-1">
                            1. Pilih Komoditas <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="search-komoditas" placeholder="Ketik nama komoditas..."
                                   autocomplete="off"
                                   class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 pr-10 text-xs focus:border-merah-400 focus:outline-none"
                                   oninput="filterKomoditas(this.value)" onfocus="showDropdown()">
                            <button type="button" onclick="startDictation('search-komoditas', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                            </button>
                            <div id="dropdown-komoditas"
                                 class="absolute z-10 w-full mt-1 bg-white border border-paper-300 rounded-sm shadow-lg max-h-52 overflow-y-auto hidden">
                            </div>
                        </div>
                        <p id="label-komoditas-terpilih" class="mt-1 text-xs text-sawah-700 font-semibold hidden"></p>
                    </div>

                    {{-- 2. Satuan & Periode --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">
                                2. Satuan <span class="text-red-500">*</span>
                            </label>
                            <select name="satuan" id="form-satuan" required
                                    class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                                @foreach($satuanList as $sat)
                                    <option value="{{ $sat }}" {{ $sat === 'kg/orang/bulan' ? 'selected' : '' }}>{{ $sat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">
                                3. Periode <span class="text-red-500">*</span>
                            </label>
                            <select name="periode" id="form-periode" required
                                    class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                                @foreach($periodeList as $val => $lbl)
                                    <option value="{{ $val }}" {{ $val === 'bulanan' ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- 3. Tabel Nilai Konsumsi --}}
                    <div>
                        <label class="block text-xs font-medium text-ink-800 mb-2">
                            4. Nilai Standar Konsumsi per Orang <span class="text-red-500">*</span>
                        </label>
                        <div class="overflow-x-auto border border-paper-200 rounded-sm">
                            <table class="w-full text-xs">
                                <thead class="bg-paper-100 border-b border-paper-200">
                                    <tr>
                                        <th class="px-3 py-2.5 text-left font-semibold text-ink-600 uppercase tracking-wide text-[10px] w-32">Kelompok Umur</th>
                                        <th class="px-3 py-2.5 text-center font-semibold text-ink-600 uppercase tracking-wide text-[10px]">Laki-laki</th>
                                        <th class="px-3 py-2.5 text-center font-semibold text-ink-600 uppercase tracking-wide text-[10px]">Perempuan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-paper-100">
                                    @foreach($kelompokUmur as $ku)
                                        <tr>
                                            <td class="px-3 py-2 font-medium text-ink-800">{{ $ku }}</td>
                                            <td class="px-2 py-1.5 text-center">
                                                <input type="number" name="nilai[{{ $ku }}][L]" id="nilai-{{ $ku }}-L"
                                                       step="0.01" min="0" required value="0"
                                                       class="w-24 rounded-sm border border-paper-300 bg-white px-2 py-1.5 text-xs text-center focus:border-merah-400 focus:outline-none">
                                            </td>
                                            <td class="px-2 py-1.5 text-center">
                                                <input type="number" name="nilai[{{ $ku }}][P]" id="nilai-{{ $ku }}-P"
                                                       step="0.01" min="0" required value="0"
                                                       class="w-24 rounded-sm border border-paper-300 bg-white px-2 py-1.5 text-xs text-center focus:border-merah-400 focus:outline-none">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 4. Sumber Standar --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">
                                5. Sumber Standar <span class="text-red-500">*</span>
                            </label>
                            <select name="sumber_standar" id="form-sumber" required
                                    onchange="toggleSumberLainnya(this)"
                                    class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                                <option value="">-- Pilih Sumber --</option>
                                @foreach($sumberList as $src)
                                    <option value="{{ $src }}">{{ $src }}</option>
                                @endforeach
                            </select>
                            <div class="relative mt-1">
                                <input type="text" id="form-sumber-lainnya" name="sumber_standar_lainnya"
                                       placeholder="Tuliskan sumber standar..."
                                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 pr-10 text-xs focus:border-merah-400 focus:outline-none hidden">
                                <button type="button" onclick="startDictation('form-sumber-lainnya', this)" id="btn-stt-sumber" class="absolute inset-y-0 right-0 items-center pr-3 text-ink-400 hover:text-merah-600 hidden" title="Gunakan Suara">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">
                                6. Tahun/Periode Standar
                            </label>
                            <div class="relative">
                                <input type="text" name="periode_standar" id="form-periode-standar"
                                       placeholder="Contoh: 2024"
                                       maxlength="20"
                                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 pr-10 text-xs focus:border-merah-400 focus:outline-none">
                                <button type="button" onclick="startDictation('form-periode-standar', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-ink-800 mb-1">
                            7. Keterangan <span class="text-xs font-normal text-ink-400">(opsional)</span>
                        </label>
                        <div class="relative">
                            <textarea name="keterangan" id="form-keterangan" rows="2"
                                      placeholder="Catatan tambahan terkait standar konsumsi ini..."
                                      class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 pr-10 text-xs focus:border-merah-400 focus:outline-none resize-none"></textarea>
                            <button type="button" onclick="startDictation('form-keterangan', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 pt-2 text-ink-400 hover:text-merah-600 items-start" title="Gunakan Suara">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-2 border-t border-paper-200 pt-4">
                        <button type="button" onclick="tutupModal()"
                                class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-white hover:bg-merah-600 shadow-sm">
                            Simpan Standar Konsumsi
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         MODAL: Konfirmasi Selesaikan Sesi 3
    ════════════════════════════════════════════════════════════════════ --}}
    <div id="modal-selesaikan"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-sm rounded-sm border border-paper-300 bg-paper-50 shadow-xl">
            <div class="px-6 py-6 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full" style="background:#f0fdf4">
                    <svg style="width:24px;height:24px;color:#16a34a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
                    </svg>
                </div>
                <h3 class="mb-2 font-display text-lg font-semibold text-ink-900">Selesaikan Sesi 3?</h3>
                <p class="mb-6 text-sm text-ink-600 leading-relaxed">
                    Sesi 3 Standar Konsumsi Komoditas akan ditandai sebagai <strong>Selesai</strong>.
                    Data standar konsumsi tetap dapat dilihat dan diubah oleh admin.
                </p>
                <form method="POST" action="{{ route('survei.public.sesi3.selesaikan', $token) }}">
                    @csrf
                    <input type="hidden" name="konfirmasi" value="1">
                    <div class="flex gap-3">
                        <button type="button"
                                onclick="document.getElementById('modal-selesaikan').classList.add('hidden')"
                                class="flex-1 rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">
                            Batal
                        </button>
                        <button type="submit"
                                style="background:#16a34a"
                                class="flex-1 rounded-sm px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                            Ya, Selesaikan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ─── Data Komoditas ───────────────────────────────────────────────────
        const komoditasList = @json($komoditasList->map(fn($k) => ['id' => $k->id, 'nama' => $k->nama, 'kategori' => $k->kategori]));
        const kelompokUmur  = @json($kelompokUmur);
        const storeUrl      = "{{ route('survei.public.sesi3.standar.store', $token) }}";

        let komoditasTerpilih = null;

        // ─── Modal Buka / Tutup ───────────────────────────────────────────────
        function bukaModalTambah() {
            resetForm();
            document.getElementById('modal-standar-title').innerText    = 'Isi Standar Konsumsi';
            document.getElementById('modal-standar-subtitle').innerText = 'Pilih komoditas dan isi nilai standar per kelompok umur';
            document.getElementById('row-pilih-komoditas').classList.remove('hidden');
            document.getElementById('form-standar').action = storeUrl;
            document.getElementById('form-method').value  = 'POST';
            bukaModal();
        }

        function bukaModalEdit(kom, nilaiGrid, meta) {
            resetForm();
            komoditasTerpilih = kom;
            document.getElementById('form-id-komoditas').value = kom.id;
            document.getElementById('modal-standar-title').innerText    = 'Edit Standar Konsumsi';
            document.getElementById('modal-standar-subtitle').innerText = kom.nama + ' (' + kom.kategori + ')';
            document.getElementById('row-pilih-komoditas').classList.add('hidden');
            document.getElementById('label-komoditas-terpilih').innerText = '✓ ' + kom.nama;
            document.getElementById('label-komoditas-terpilih').classList.remove('hidden');
            document.getElementById('form-standar').action = storeUrl;
            document.getElementById('form-method').value  = 'POST';

            // Isi nilai grid
            kelompokUmur.forEach(ku => {
                ['L', 'P'].forEach(g => {
                    const el = document.getElementById('nilai-' + ku + '-' + g);
                    if (el && nilaiGrid[ku] && nilaiGrid[ku][g] !== undefined) {
                        el.value = parseFloat(nilaiGrid[ku][g]);
                    }
                });
            });

            // Isi meta
            if (meta.satuan)         document.getElementById('form-satuan').value = meta.satuan;
            if (meta.periode)        document.getElementById('form-periode').value = meta.periode;
            if (meta.periode_standar) document.getElementById('form-periode-standar').value = meta.periode_standar || '';
            if (meta.keterangan)     document.getElementById('form-keterangan').value = meta.keterangan || '';

            // Sumber
            const sumberSel = document.getElementById('form-sumber');
            const sumberOpts = Array.from(sumberSel.options).map(o => o.value);
            if (meta.sumber_standar && sumberOpts.includes(meta.sumber_standar)) {
                sumberSel.value = meta.sumber_standar;
            } else if (meta.sumber_standar) {
                sumberSel.value = 'Lainnya';
                document.getElementById('form-sumber-lainnya').value = meta.sumber_standar;
                document.getElementById('form-sumber-lainnya').classList.remove('hidden');
            }

            bukaModal();
        }

        function bukaModal() {
            const m = document.getElementById('modal-standar');
            m.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function tutupModal() {
            const m = document.getElementById('modal-standar');
            m.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            hideDropdown();
        }

        // ─── Reset Form ───────────────────────────────────────────────────────
        function resetForm() {
            komoditasTerpilih = null;
            document.getElementById('form-id-komoditas').value   = '';
            document.getElementById('search-komoditas').value    = '';
            document.getElementById('form-satuan').value         = 'kg/orang/bulan';
            document.getElementById('form-periode').value        = 'bulanan';
            document.getElementById('form-sumber').value         = '';
            document.getElementById('form-sumber-lainnya').value = '';
            document.getElementById('form-sumber-lainnya').classList.add('hidden');
            document.getElementById('form-periode-standar').value = '';
            document.getElementById('form-keterangan').value     = '';
            document.getElementById('label-komoditas-terpilih').classList.add('hidden');
            kelompokUmur.forEach(ku => {
                ['L', 'P'].forEach(g => {
                    const el = document.getElementById('nilai-' + ku + '-' + g);
                    if (el) el.value = '0';
                });
            });
            hideDropdown();
        }

        // ─── Dropdown Komoditas ───────────────────────────────────────────────
        function filterKomoditas(q) {
            const lower = q.toLowerCase().trim();
            const dropdown = document.getElementById('dropdown-komoditas');
            if (!lower) { showAllKomoditas(); return; }

            const matches = komoditasList.filter(k =>
                k.nama.toLowerCase().includes(lower) ||
                k.kategori.toLowerCase().includes(lower)
            );

            renderDropdown(matches);
            dropdown.classList.remove('hidden');
        }

        function showAllKomoditas() {
            renderDropdown(komoditasList);
            document.getElementById('dropdown-komoditas').classList.remove('hidden');
        }

        function showDropdown() {
            if (!document.getElementById('search-komoditas').value) {
                showAllKomoditas();
            }
        }

        function hideDropdown() {
            document.getElementById('dropdown-komoditas').classList.add('hidden');
        }

        function renderDropdown(items) {
            const dropdown = document.getElementById('dropdown-komoditas');
            if (items.length === 0) {
                dropdown.innerHTML = '<div class="px-3 py-2 text-xs text-ink-400">Tidak ada komoditas ditemukan.</div>';
                return;
            }
            dropdown.innerHTML = items.map(k => `
                <div onclick="pilihKomoditas(${k.id}, '${k.nama.replace(/'/g,"\\'")}', '${k.kategori.replace(/'/g,"\\'")}')"
                     class="flex items-center justify-between px-3 py-2 text-xs cursor-pointer hover:bg-paper-100 border-b border-paper-100 last:border-0">
                    <span class="font-medium text-ink-800">${k.nama}</span>
                    <span class="text-[10px] bg-paper-200 text-ink-600 px-1.5 py-0.5 rounded">${k.kategori}</span>
                </div>
            `).join('');
        }

        function pilihKomoditas(id, nama, kategori) {
            komoditasTerpilih = { id, nama, kategori };
            document.getElementById('form-id-komoditas').value      = id;
            document.getElementById('search-komoditas').value       = nama;
            document.getElementById('label-komoditas-terpilih').innerText = '✓ ' + nama + ' (' + kategori + ')';
            document.getElementById('label-komoditas-terpilih').classList.remove('hidden');
            hideDropdown();
        }

        // ─── Sumber Lainnya Toggle ────────────────────────────────────────────
        function toggleSumberLainnya(selectElem) {
            const val = selectElem.value;
            const textInput = document.getElementById('form-sumber-lainnya');
            const btnStt = document.getElementById('btn-stt-sumber');
            if (val === 'Lainnya') {
                textInput.classList.remove('hidden');
                textInput.required = true;
                if(btnStt) btnStt.classList.remove('hidden');
                if(btnStt) btnStt.classList.add('flex');
            } else {
                textInput.classList.add('hidden');
                textInput.value = '';
                textInput.required = false;
                if(btnStt) btnStt.classList.add('hidden');
                if(btnStt) btnStt.classList.remove('flex');
            }
        }

        // ─── Modal Selesaikan backdrop ────────────────────────────────────────
        document.getElementById('modal-selesaikan').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
        new MutationObserver(function() {
            const el = document.getElementById('modal-selesaikan');
            el.style.display = el.classList.contains('hidden') ? '' : 'flex';
        }).observe(document.getElementById('modal-selesaikan'), { attributes: true, attributeFilter: ['class'] });

        // ─── Close dropdown on outside click ─────────────────────────────────
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#search-komoditas') && !e.target.closest('#dropdown-komoditas')) {
                hideDropdown();
            }
        });
    </script>
    </div>
    <x-speech-scripts />
</body>
</html>
