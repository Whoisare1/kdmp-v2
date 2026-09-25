<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Sesi 2 — Potensi Produksi Desa ({{ $sesi->wilayah->nama ?? 'Wilayah' }})</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper-100 font-sans text-ink-900 antialiased min-h-screen pb-12">

    @php
        $st2 = $sesi->status_sesi2 ?? 'belum_diisi';
        $st2Label = $labelStatusSesi2[$st2] ?? 'Belum Diisi';
        
        $wil = $sesi->wilayah;
        $kec = $wil?->parent;
        $kab = $kec?->parent;
        $prov = $kab?->parent;
        $petugasNama = $sesi->petugas->nama ?? 'Sistem';
        $kodeSurvei = 'SRV-' . $sesi->tahun . '-' . str_pad($sesi->id, 3, '0', STR_PAD_LEFT);
    @endphp

    {{-- Header --}}
    <header class="bg-merah-600 text-white shadow-md px-4 py-4 sticky top-0 z-10">
        <div class="max-w-xl mx-auto space-y-1">
            <div class="flex items-center justify-between mb-1">
                <a href="{{ url('/survei/isi/' . $token) }}" class="text-xs opacity-80 hover:opacity-100">
                    ← Kembali ke Modul
                </a>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $st2 === 'selesai' || $st2 === 'terverifikasi' ? 'bg-green-200 text-green-900' : 'bg-yellow-200 text-yellow-900' }}">
                    Status: {{ $st2Label }}
                </span>
            </div>
            <h1 class="font-bold text-base leading-tight">Sesi 2 — Potensi Produksi Desa</h1>
            <div class="text-xs opacity-90 pt-1 border-t border-white/20">
                <div>Desa {{ $wil->nama ?? '' }}, Kec. {{ $kec->nama ?? '-' }}, {{ $kab->nama ?? '-' }}, {{ $prov->nama ?? 'Jawa Tengah' }}</div>
                <div>Kode: <strong class="font-mono">{{ $kodeSurvei }}</strong> &bull; Tahun: {{ $sesi->tahun }} &bull; Petugas: {{ $petugasNama }}</div>
            </div>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 mt-5 space-y-5">

        {{-- Flash Notification --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Info Box --}}
        <div class="bg-white rounded-xl border border-paper-200 shadow-sm p-4 text-xs leading-relaxed text-ink-600">
            <strong>Deskripsi Sesi 2:</strong> Pendataan komoditas yang diproduksi di desa, jumlah produksi, periode/pola panen, pemanfaatan hasil produksi, dan kendala produksi melalui wawancara narasumber / agregator desa.
        </div>

        {{-- Section 1: Daftar Narasumber --}}
        <div class="bg-white rounded-xl border border-paper-200 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3 border-b border-paper-100 pb-2">
                <div>
                    <h2 class="font-semibold text-sm text-ink-900">Sumber Informasi Produksi</h2>
                    <p class="text-[11px] text-ink-400">Narasumber / agregator desa yang diwawancarai.</p>
                </div>
                <button type="button" onclick="openModal('modalPublicNs')" class="rounded-lg bg-merah-600 text-white px-3 py-1.5 text-xs font-semibold hover:bg-merah-700 shadow-sm">
                    + Narasumber
                </button>
            </div>

            @if($narasumbers->isEmpty())
                <p class="text-xs text-ink-400 text-center py-4 italic">Belum ada narasumber terdaftar.</p>
            @else
                <div class="space-y-2">
                    @foreach($narasumbers as $ns)
                        @php $prodNs = $produksis->where('id_narasumber', $ns->id_narasumber); @endphp
                        <div class="bg-paper-50 p-3 rounded-lg border border-paper-200">
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <div class="font-semibold text-xs text-ink-900">{{ $ns->nama_narasumber }}</div>
                                    <span class="inline-block rounded bg-paper-200 px-1.5 py-0.5 text-[10px] font-semibold text-ink-700">
                                        {{ $ns->kategori }}
                                    </span>
                                </div>
                                <button type="button" onclick="openTambahProduksiPublic('{{ $ns->id_narasumber }}', '{{ addslashes($ns->nama_narasumber) }}', '{{ $ns->kategori }}')" class="rounded-md bg-merah-600 text-white px-2.5 py-1 text-xs font-medium">
                                    + Input Produksi
                                </button>
                            </div>
                            @if(!$prodNs->isEmpty())
                                <div class="mt-2 flex flex-wrap gap-1 border-t border-paper-200/60 pt-1.5">
                                    @foreach($prodNs as $pItem)
                                        @php
                                            $pItemKg = $pItem->jumlah_produksi_kg ?: \Survei\Models\ProduksiNarasumber::konversiKeKg($pItem->jumlah_produksi, $pItem->satuan);
                                            $isMass = in_array(strtolower(trim($pItem->satuan)), ['kg', 'ton', 'kuintal']);
                                        @endphp
                                        <span class="inline-block rounded bg-sawah-100 text-sawah-800 border border-sawah-300 px-2 py-0.5 text-[10px] font-medium">
                                            {{ $pItem->komoditas?->nama ?? 'Komoditas' }}
                                            @if($isMass)
                                                ({{ number_format($pItemKg, 0, ',', '.') }} kg @if($pItemKg >= 1000) - {{ number_format($pItemKg / 1000, 2, ',', '.') }} ton @endif)
                                            @else
                                                ({{ number_format($pItem->jumlah_produksi, 0, ',', '.') }} {{ $pItem->satuan }})
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Section 2: Ringkasan Data Komoditas (Card Format) --}}
        @if(!$produksis->isEmpty())
            <div class="bg-white rounded-xl border border-paper-200 shadow-sm p-4 space-y-3">
                <h2 class="font-semibold text-sm text-ink-900">Rincian Data Komoditas Produksi</h2>
                <div class="space-y-3">
                    @foreach($produksis as $prod)
                        @php
                            $prodKg = $prod->jumlah_produksi_kg ?: \Survei\Models\ProduksiNarasumber::konversiKeKg($prod->jumlah_produksi, $prod->satuan);
                            $dijualKg = \Survei\Models\ProduksiNarasumber::konversiKeKg($prod->jumlah_dijual, $prod->satuan);
                            $sisaKg = \Survei\Models\ProduksiNarasumber::konversiKeKg($prod->sisa_produksi, $prod->satuan);
                            $isMass = in_array(strtolower(trim($prod->satuan)), ['kg', 'ton', 'kuintal']);

                            $bJson = $prod->bulan_panen_json;
                            $mList = is_array($bJson) ? (isset($bJson['months']) ? $bJson['months'] : array_filter($bJson, fn($v)=>is_string($v))) : [];
                            $bPanen = !empty($mList) ? implode(', ', $mList) : '-';
                            $kdlList = is_array($prod->kendala_json) ? implode(', ', $prod->kendala_json) : ($prod->kendala_produksi ?: 'Tidak ada kendala');
                        @endphp
                        <div class="bg-paper-50 p-3.5 rounded-lg border border-paper-200 text-xs space-y-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-sm text-ink-900">{{ $prod->komoditas?->nama }}</div>
                                    <span class="inline-block rounded bg-paper-200 px-1.5 py-0.5 text-[10px] font-medium text-ink-700">
                                        Kategori: {{ $prod->komoditas?->kategori }}
                                    </span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="text-right font-mono">
                                        <div class="font-bold text-merah-700 text-xs">
                                            @if($isMass)
                                                {{ number_format($prodKg, 0, ',', '.') }} kg
                                                @if($prodKg >= 1000)
                                                    <span class="text-ink-500 font-normal text-[10px]">({{ number_format($prodKg / 1000, 2, ',', '.') }} ton)</span>
                                                @endif
                                            @else
                                                {{ number_format($prod->jumlah_produksi, 0, ',', '.') }} {{ $prod->satuan }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        @php
                                            $prodData = [
                                                'id' => $prod->id_produksi_ns,
                                                'id_narasumber' => $prod->id_narasumber,
                                                'ns_nama' => $prod->narasumber?->nama_narasumber,
                                                'ns_kategori' => $prod->narasumber?->kategori,
                                                'id_komoditas' => $prod->id_komoditas,
                                                'komoditas_nama' => $prod->komoditas?->nama,
                                                'jumlah_produksi' => $prod->jumlah_produksi,
                                                'satuan' => $prod->satuan,
                                                'periode' => $prod->periode,
                                                'pola_panen' => $bJson['pola_panen'] ?? 'Musiman',
                                                'bulan_panen_active' => $mList,
                                                'kendala_json' => is_array($prod->kendala_json) ? $prod->kendala_json : [],
                                                'kendala_produksi' => $prod->kendala_produksi
                                            ];
                                        @endphp
                                        <button type="button" onclick='editProduksiPublic(@json($prodData))' class="text-ink-500 hover:text-ink-700 p-1 rounded-sm hover:bg-paper-200 transition-colors flex items-center justify-center" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <form method="POST" action="/survei/isi/{{ $token }}/sesi2/produksi/{{ $prod->getKey() }}" onsubmit="return confirm('Hapus data produksi komoditas ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-merah-500 hover:text-merah-700 p-1 rounded-sm hover:bg-merah-50 transition-colors flex items-center justify-center" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-1 text-[11px] border-t border-paper-200 pt-2 text-ink-700">
                                <div>Narasumber: <strong>{{ $prod->narasumber?->nama_narasumber }}</strong></div>
                                <div>Periode: <strong>{{ $prod->periode }}</strong></div>
                            </div>
                            <div class="text-[10px] text-ink-500 border-t border-paper-200/60 pt-1">
                                Bulan Panen: <strong class="text-sawah-800">{{ $bPanen }}</strong> &bull; Kendala: {{ $kdlList }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif



        {{-- Actions --}}
        <div class="flex gap-3 pt-2">
            <form action="{{ route('survei.public.sesi2.draft', $token) }}" method="POST" class="flex-1">
                @csrf
                <button type="submit" class="w-full rounded-xl border border-paper-300 bg-white py-3 text-xs font-semibold text-ink-700 shadow-sm">
                    Simpan Draft Sesi 2
                </button>
            </form>
            <form action="{{ route('survei.public.sesi2.selesaikan', $token) }}" method="POST" class="flex-1" onsubmit="return confirm('Apakah Anda yakin ingin menyelesaikan pengisian Sesi 2 — Potensi Produksi Desa?')">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-merah-600 py-3 text-xs font-semibold text-white shadow-sm">
                    Selesaikan Sesi 2
                </button>
            </form>
        </div>

    </main>

    {{-- MODAL 1: TAMBAH NARASUMBER --}}
    <div id="modalPublicNs" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-ink-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('modalPublicNs')"></div>
        
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-sm border border-paper-200">
                    
                    {{-- Modal Header --}}
                    <div class="px-5 py-3.5 border-b border-paper-100 bg-paper-50 flex items-center justify-between">
                        <h3 class="font-semibold text-sm text-ink-900">Tambah Narasumber Produksi</h3>
                        <button type="button" onclick="closeModal('modalPublicNs')" class="flex items-center justify-center w-7 h-7 rounded-full bg-paper-200 text-ink-700 hover:bg-merah-500 hover:text-white transition-colors shadow-sm" title="Tutup Modal (Esc)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-5">
                        <form id="formPublicNs" method="POST" action="{{ route('survei.public.sesi2.narasumber.store', $token) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-ink-700 mb-1 flex items-center justify-between">
                            Nama Narasumber / Sumber Informasi
                            <button type="button" onclick="speakText('Silakan sebutkan nama narasumber atau sumber informasi.')" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-paper-100 text-ink-500 hover:bg-paper-200" title="Bacakan">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"></path></svg>
                            </button>
                        </label>
                        <div class="relative">
                            <input type="text" id="s2_nama_narasumber" name="nama_narasumber" required class="w-full rounded-lg border border-paper-300 px-3 py-2 pr-10 text-xs" placeholder="Misal: Pak Budi (Kelompok Tani)">
                            <button type="button" onclick="startDictation('s2_nama_narasumber', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                            </button>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-xs font-medium text-ink-700 mb-1">Kategori Narasumber</label>
                        <select name="kategori" required class="w-full rounded-lg border border-paper-300 px-3 py-2 text-xs">
                            @foreach($kategoriNarasumber as $kat)
                                <option value="{{ $kat }}">{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>
                        </form>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-5 py-3 border-t border-paper-100 bg-paper-50 flex justify-end gap-2">
                        <button type="button" onclick="closeModal('modalPublicNs')" class="rounded-lg border border-paper-300 px-3 py-2 text-xs">Batal / Tutup</button>
                        <button type="submit" form="formPublicNs" class="rounded-lg bg-merah-600 text-white px-4 py-2 text-xs font-semibold">Simpan Narasumber</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: INPUT PRODUKSI KOMODITAS --}}
    <div id="modalPublicProd" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-ink-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('modalPublicProd')"></div>
        
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl border border-paper-200">
                    
                    {{-- Modal Header --}}
                    <div class="px-5 py-3.5 border-b border-paper-100 bg-paper-50 flex items-center justify-between">
                        <div>
                            <h3 id="modalPublicProdTitle" class="font-semibold text-sm text-ink-900 flex items-center">
                                Input Produksi Komoditas
                                <button type="button" onclick="speakText('Silakan isi form produksi komoditas berikut.')" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-paper-100 text-ink-500 hover:bg-paper-200 ml-2" title="Bacakan">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"></path></svg>
                                </button>
                            </h3>
                            <div id="badgePublicNsInfo" class="mt-1 text-xs text-merah-700 font-semibold bg-merah-50 px-2 py-0.5 rounded inline-block">Narasumber: -</div>
                        </div>
                        <button type="button" onclick="closeModal('modalPublicProd')" class="flex items-center justify-center w-7 h-7 rounded-full bg-paper-200 text-ink-700 hover:bg-merah-500 hover:text-white transition-colors shadow-sm" title="Tutup Modal (Esc)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-5 space-y-3">
                        <form id="formPublicProd" method="POST" action="{{ route('survei.public.sesi2.produksi.store', $token) }}">
                    @csrf
                    <div id="methodPutContainer"></div>
                    <input type="hidden" name="id_narasumber" id="pub_ns_id">

                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-ink-700 mb-1">Kategori Komoditas</label>
                            <select id="pub_filter_kat" onchange="filterKomoditasPublic()" class="w-full rounded-lg border border-paper-300 px-2.5 py-1.5 text-xs">
                                <option value="">-- Semua Kategori --</option>
                                @foreach($kategoriKomoditas as $kat)
                                    <option value="{{ $kat }}">{{ $kat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-700 mb-1">Nama Komoditas (Hasil Produksi) <span class="text-red-500">*</span></label>
                            <div class="relative mb-1">
                                <input type="text"
                                       id="pub_search_komoditas_input"
                                       oninput="filterKomoditasPublicWithSearch()"
                                       onfocus="filterKomoditasPublicWithSearch()"
                                       onkeydown="handlePublicSearchKeyDown(event)"
                                       class="w-full rounded-lg border border-paper-300 px-2.5 py-1.5 text-xs pr-8 focus:border-merah-400 focus:outline-none placeholder-ink-400"
                                       placeholder="Cari (misal: sapi)..."
                                       autocomplete="off">
                                <button type="button" onclick="startDictation('pub_search_komoditas_input', this)" class="absolute inset-y-0 right-0 flex items-center pr-2 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                                </button>
                                <div id="pub_komoditas_dropdown_list" class="hidden absolute left-0 right-0 top-full mt-1 z-30 max-h-56 overflow-y-auto rounded-lg border border-paper-300 bg-white shadow-lg text-xs divide-y divide-paper-100">
                                </div>
                            </div>
                            <select name="id_komoditas" id="pub_komoditas_id" required class="w-full rounded-lg border border-paper-300 px-2.5 py-1.5 text-xs">
                                <option value="">-- Pilih Komoditas --</option>
                                @foreach($komoditasList as $k)
                                    <option value="{{ $k->id }}" data-kategori="{{ $k->kategori }}">{{ $k->nama }} ({{ $k->kategori }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-ink-700 mb-1">Jumlah</label>
                            <input type="number" step="any" name="jumlah_produksi" id="pub_jumlah_produksi" required min="0" class="w-full rounded-lg border border-paper-300 px-2.5 py-1.5 text-xs" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-700 mb-1">Satuan</label>
                            <select name="satuan" id="pub_satuan" required class="w-full rounded-lg border border-paper-300 px-2.5 py-1.5 text-xs">
                                @foreach($satuanList as $sat)
                                    <option value="{{ $sat }}">{{ $sat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-700 mb-1">Periode</label>
                            <select name="periode" id="pub_periode" required class="w-full rounded-lg border border-paper-300 px-2.5 py-1.5 text-xs">
                                @foreach($periodeList as $per)
                                    <option value="{{ $per }}">{{ $per }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Kalender Panen 12 Bulan Grid --}}
                    <div class="mb-3 rounded-lg border border-paper-200 bg-paper-50 p-3">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-[11px] font-bold text-ink-800">Kalender Panen (12 Bulan)</label>
                            <select name="pola_panen" id="pub_pola_panen" class="rounded border border-paper-300 text-[10px] px-1.5 py-0.5">
                                @foreach($polaPanenList as $pola)
                                    <option value="{{ $pola }}">{{ $pola }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-3 gap-1.5 text-[11px]">
                            @foreach($bulanPanenList as $bKode => $bNama)
                                <label class="flex items-center gap-1 bg-white border border-paper-300 rounded px-1.5 py-1 cursor-pointer">
                                    <input type="checkbox" name="bulan_panen_active[]" value="{{ $bKode }}" class="h-3.5 w-3.5 text-merah-600 rounded">
                                    <span class="font-medium">{{ $bKode }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <input type="hidden" name="jumlah_dijual" value="0">
                    <input type="hidden" name="jumlah_dikonsumsi_sendiri" value="0">

                    {{-- Kendala Produksi --}}
                    <div class="mb-2 rounded-lg border border-paper-200 bg-paper-50 p-3">
                        <label class="block text-[11px] font-bold text-ink-800 mb-1">Kendala Produksi</label>
                        <div class="grid grid-cols-2 gap-1.5 mb-2 text-[11px]">
                            @foreach($kendalaList as $kdl)
                                <label class="flex items-center gap-1 bg-white border border-paper-300 rounded px-1.5 py-1 cursor-pointer">
                                    <input type="checkbox" name="kendala_json[]" value="{{ $kdl }}" class="h-3.5 w-3.5 text-merah-600 rounded">
                                    <span class="truncate" title="{{ $kdl }}">{{ $kdl }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="relative mt-2">
                            <textarea id="s2_kendala_produksi" name="kendala_produksi" rows="2" class="w-full rounded-lg border border-paper-300 px-2.5 py-1.5 text-xs pr-8" placeholder="Catatan kendala detail (opsional)..."></textarea>
                            <button type="button" onclick="startDictation('s2_kendala_produksi', this)" class="absolute inset-y-0 right-0 flex items-center pr-2 pt-2 text-ink-400 hover:text-merah-600 items-start" title="Gunakan Suara">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                            </button>
                        </div>
                        <input type="hidden" name="sumber_data" value="Wawancara Narasumber / Pelaku Produksi">
                        </form>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-5 py-3 border-t border-paper-100 bg-paper-50 flex justify-end gap-2">
                        <button type="button" onclick="closeModal('modalPublicProd')" class="rounded-lg border border-paper-300 px-3 py-2 text-xs">Batal / Tutup</button>
                        <button type="submit" form="formPublicProd" class="rounded-lg bg-merah-600 text-white px-4 py-2 text-xs font-semibold">Simpan Produksi</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if(modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            if(modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }
        
        function resetFormProduksiPublic() {
            const form = document.getElementById('formPublicProd');
            form.reset();
            form.action = "{{ route('survei.public.sesi2.produksi.store', $token) }}";
            document.getElementById('methodPutContainer').innerHTML = '';
            
            // Hapus centang kalender & kendala
            document.querySelectorAll('#formPublicProd input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.getElementById('pub_search_komoditas_input').value = '';
        }

        function openTambahProduksiPublic(nsId, nsNama, nsKategori) {
            resetFormProduksiPublic();
            document.getElementById('pub_ns_id').value = nsId;
            document.getElementById('modalPublicProdTitle').innerText = 'Input Produksi: ' + nsNama;
            document.getElementById('badgePublicNsInfo').innerText = 'Narasumber: ' + nsNama + ' (' + nsKategori + ')';
            document.getElementById('pub_filter_kat').value = '';
            document.getElementById('pub_komoditas_id').value = '';
            filterKomoditasPublicWithSearch();
            openModal('modalPublicProd');
        }

        function editProduksiPublic(data) {
            resetFormProduksiPublic();
            
            document.getElementById('formPublicProd').action = `/survei/isi/{{ $token }}/sesi2/produksi/${data.id}`;
            document.getElementById('methodPutContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            
            document.getElementById('pub_ns_id').value = data.id_narasumber;
            document.getElementById('modalPublicProdTitle').innerText = 'Edit Produksi: ' + data.ns_nama;
            document.getElementById('badgePublicNsInfo').innerText = 'Narasumber: ' + data.ns_nama + ' (' + data.ns_kategori + ')';
            
            // Set komoditas
            document.getElementById('pub_komoditas_id').value = data.id_komoditas;
            document.getElementById('pub_search_komoditas_input').value = data.komoditas_nama;
            
            document.getElementById('pub_jumlah_produksi').value = data.jumlah_produksi;
            document.getElementById('pub_satuan').value = data.satuan;
            document.getElementById('pub_periode').value = data.periode;
            document.getElementById('pub_pola_panen').value = data.pola_panen;
            
            // Set checkboxes (Bulan Panen)
            if(data.bulan_panen_active) {
                data.bulan_panen_active.forEach(m => {
                    let cb = document.querySelector(`input[name="bulan_panen_active[]"][value="${m}"]`);
                    if(cb) cb.checked = true;
                });
            }
            
            // Set checkboxes (Kendala)
            if(data.kendala_json) {
                data.kendala_json.forEach(k => {
                    let cb = document.querySelector(`input[name="kendala_json[]"][value="${k}"]`);
                    if(cb) cb.checked = true;
                });
            }
            
            document.getElementById('s2_kendala_produksi').value = data.kendala_produksi || '';
            
            openModal('modalPublicProd');
        }

        function filterKomoditasPublic() {
            filterKomoditasPublicWithSearch();
        }
        function selectKomoditasPublicItem(id, nama, kategori) {
            const sel = document.getElementById('pub_komoditas_id');
            const searchIn = document.getElementById('pub_search_komoditas_input');
            const katSel = document.getElementById('pub_filter_kat');

            if (id.startsWith('new:')) {
                let existsNewOpt = Array.from(sel.options).find(o => o.value === id);
                if (!existsNewOpt) {
                    existsNewOpt = document.createElement('option');
                    existsNewOpt.value = id;
                    existsNewOpt.text = nama + ' (' + (kategori || 'Custom') + ')';
                    existsNewOpt.setAttribute('data-kategori', kategori || '');
                    sel.appendChild(existsNewOpt);
                }
                sel.value = id;
                searchIn.value = id.replace('new:', '');
            } else {
                sel.value = id;
                searchIn.value = nama;
                if (kategori && katSel) {
                    katSel.value = kategori;
                }
            }

            const drop = document.getElementById('pub_komoditas_dropdown_list');
            if (drop) drop.classList.add('hidden');
        }

        function filterKomoditasPublicWithSearch() {
            const searchIn = document.getElementById('pub_search_komoditas_input');
            const drop = document.getElementById('pub_komoditas_dropdown_list');
            const katSel = document.getElementById('pub_filter_kat');
            const sel = document.getElementById('pub_komoditas_id');

            if (!searchIn || !drop || !sel) return;

            const searchVal = searchIn.value.trim();
            const searchLower = searchVal.toLowerCase();
            let selectedKat = katSel ? katSel.value : '';

            const options = [];
            for (let i = 0; i < sel.options.length; i++) {
                const opt = sel.options[i];
                if (!opt.value || opt.value.startsWith('new:')) continue;
                options.push({
                    id: opt.value,
                    nama: opt.text.split(' (')[0].trim(),
                    kategori: opt.getAttribute('data-kategori') || '',
                    fullText: opt.text.trim()
                });
            }

            let matches = options.filter(item => {
                const matchesSearch = !searchLower || item.fullText.toLowerCase().includes(searchLower) || item.nama.toLowerCase().includes(searchLower);
                const matchesKat = !selectedKat || item.kategori === selectedKat;
                return matchesSearch && matchesKat;
            });

            if (matches.length === 0 && searchLower.length > 0 && selectedKat) {
                const allMatches = options.filter(item => {
                    return item.fullText.toLowerCase().includes(searchLower) || item.nama.toLowerCase().includes(searchLower);
                });
                if (allMatches.length > 0) {
                    matches = allMatches;
                    if (matches[0].kategori && katSel) {
                        katSel.value = matches[0].kategori;
                        selectedKat = matches[0].kategori;
                    }
                }
            }

            let html = '';
            if (matches.length > 0) {
                matches.forEach(item => {
                    const safeNama = item.nama.replace(/'/g, "\\'");
                    const safeKat = item.kategori.replace(/'/g, "\\'");
                    html += `<div onclick="selectKomoditasPublicItem('${item.id}', '${safeNama}', '${safeKat}')" class="px-3 py-2 hover:bg-paper-100 cursor-pointer border-b border-paper-100 flex items-center justify-between">
                        <span class="font-medium text-ink-900">${item.nama}</span>
                        <span class="rounded bg-paper-200 text-ink-600 px-1.5 py-0.5 text-[10px]">${item.kategori}</span>
                    </div>`;
                });
            }

            const exactExists = options.some(item => item.nama.toLowerCase() === searchLower);
            if (searchVal.length > 0 && !exactExists) {
                const safeVal = searchVal.replace(/'/g, "\\'");
                const newKat = selectedKat || 'Hasil Pertanian / Lainnya';
                html += `<div onclick="selectKomoditasPublicItem('new:${safeVal}', '${safeVal}', '${newKat}')" class="px-3 py-2 bg-merah-50 hover:bg-merah-100 text-merah-800 font-semibold cursor-pointer border-b border-paper-100 flex items-center justify-between">
                    <span>Tambah Opsi Baru: "${searchVal}"</span>
                    <span class="text-[10px] text-merah-600">Opsi Baru</span>
                </div>`;
            }

            if (html === '') {
                html = `<div class="px-3 py-2 text-ink-400 italic">Ketik untuk mencari komoditas...</div>`;
            }

            drop.innerHTML = html;
            if (searchLower.length > 0 || document.activeElement === searchIn) {
                drop.classList.remove('hidden');
            } else {
                drop.classList.add('hidden');
            }

            for (let i = 0; i < sel.options.length; i++) {
                const opt = sel.options[i];
                if (!opt.value) continue;
                const isMatched = matches.some(m => m.id === opt.value);
                opt.style.display = isMatched ? '' : 'none';
            }

            if (matches.length > 0 && searchLower.length > 0) {
                sel.value = matches[0].id;
            } else if (searchVal.length > 0 && !exactExists) {
                const newId = 'new:' + searchVal;
                let existsNewOpt = Array.from(sel.options).find(o => o.value === newId);
                if (!existsNewOpt) {
                    existsNewOpt = document.createElement('option');
                    existsNewOpt.value = newId;
                    existsNewOpt.text = searchVal + ' (' + (selectedKat || 'Custom') + ')';
                    existsNewOpt.setAttribute('data-kategori', selectedKat || '');
                    sel.appendChild(existsNewOpt);
                }
                sel.value = newId;
            }
        }

        function handlePublicSearchKeyDown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const drop = document.getElementById('pub_komoditas_dropdown_list');
                const firstItem = drop ? drop.querySelector('div[onclick]') : null;
                if (firstItem) {
                    firstItem.click();
                }
            } else if (e.key === 'Escape') {
                const drop = document.getElementById('pub_komoditas_dropdown_list');
                if (drop) drop.classList.add('hidden');
            }
        }

        document.addEventListener('click', function(e) {
            const searchIn = document.getElementById('pub_search_komoditas_input');
            const drop = document.getElementById('pub_komoditas_dropdown_list');
            if (searchIn && drop && !searchIn.contains(e.target) && !drop.contains(e.target)) {
                drop.classList.add('hidden');
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('modalPublicNs');
                closeModal('modalPublicProd');
            }
        });
    </script>
    <x-speech-scripts />
</body>
</html>
