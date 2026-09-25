<x-layouts.app :title="$title" eyebrow="Manajemen Survei">
    @php
        $st2 = $sesi->status_sesi2 ?? 'belum_diisi';
        $st2Cls = match($st2) {
            'sedang_diisi'  => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'selesai'       => 'bg-sawah-100 text-sawah-700 border-sawah-300',
            'terverifikasi' => 'bg-blue-100 text-blue-800 border-blue-300',
            default         => 'bg-paper-200 text-ink-600 border-paper-300',
        };
        $st2Label = $labelStatusSesi2[$st2] ?? 'Belum Diisi';
        
        $wil = $sesi->wilayah;
        $kec = $wil?->parent;
        $kab = $kec?->parent;
        $prov = $kab?->parent;
        $petugasNama = $sesi->petugas->nama ?? 'Admin Desa';
        $kodeSurvei = 'SRV-' . $sesi->tahun . '-' . str_pad($sesi->id, 3, '0', STR_PAD_LEFT);
    @endphp

    {{-- Breadcrumb & Back --}}
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('survei.sesi.show', $sesi->id) }}"
           class="inline-flex items-center gap-1 text-sm text-ink-500 hover:text-ink-800 font-medium">
            ← Kembali ke Detail Sesi Survei
        </a>
        <span class="inline-block rounded-full border px-3 py-1 text-xs font-semibold {{ $st2Cls }}">
            Status Sesi 2: {{ $st2Label }}
        </span>
    </div>

    {{-- 1. HEADER HALAMAN IDENTITAS --}}
    <div class="mb-6 rounded-sm border border-paper-300 bg-paper-50 p-5 shadow-sm">
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4 text-xs text-ink-600">
            <div>
                <span class="block text-ink-400 uppercase text-[10px]">Desa</span>
                <span class="font-bold text-ink-900 text-sm">{{ $wil->nama ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-ink-400 uppercase text-[10px]">Kecamatan</span>
                <span class="font-medium text-ink-800 text-sm">{{ $kec->nama ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-ink-400 uppercase text-[10px]">Kabupaten</span>
                <span class="font-medium text-ink-800 text-sm">{{ $kab->nama ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-ink-400 uppercase text-[10px]">Provinsi</span>
                <span class="font-medium text-ink-800 text-sm">{{ $prov->nama ?? 'Jawa Tengah' }}</span>
            </div>
            <div>
                <span class="block text-ink-400 uppercase text-[10px]">Tahun / Periode</span>
                <span class="font-medium text-ink-800">{{ $sesi->tahun }}</span>
            </div>
            <div>
                <span class="block text-ink-400 uppercase text-[10px]">Kode Survei</span>
                <span class="font-mono font-bold text-merah-700">{{ $kodeSurvei }}</span>
            </div>
            <div>
                <span class="block text-ink-400 uppercase text-[10px]">Petugas Survei</span>
                <span class="font-medium text-ink-800">{{ $petugasNama }}</span>
            </div>
        </div>
    </div>

    {{-- 2. JUDUL SESI & DESKRIPSI --}}
    <div class="mb-6 rounded-sm border border-merah-200 bg-merah-50/40 p-5">
        <h1 class="font-display text-xl font-bold text-ink-900">Sesi 2 — Potensi Produksi Desa</h1>
        <p class="mt-1 text-sm text-ink-600 leading-relaxed">
            Pendataan komoditas yang diproduksi di desa, jumlah produksi, periode/pola panen, pemanfaatan hasil produksi, dan kendala produksi melalui wawancara narasumber / agregator desa.
        </p>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-sm border border-sawah-300 bg-sawah-50 px-4 py-3 text-sm text-sawah-800">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sawah-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 flex items-start gap-3 rounded-sm border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- MAIN STACK --}}
    <div class="space-y-8">

        {{-- 3. BAGIAN NARASUMBER / SUMBER INFORMASI PRODUKSI --}}
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-sm">
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-paper-200 pb-4">
                <div>
                    <h2 class="font-display text-base font-semibold text-ink-900">Sumber Informasi Produksi (Narasumber / Agregator)</h2>
                    <p class="text-xs text-ink-500">
                        Petugas mencatat narasumber seperti Kelompok Tani, Ketua Kelompok Tani, Pengepul, Koperasi, Penyuluh, Perangkat Desa, Pengelola Tambak, atau Petani/Peternak langsung. 1 narasumber dapat memberikan informasi beberapa komoditas.
                    </p>
                </div>
                <button type="button"
                        onclick="openModalTambahNarasumber()"
                        class="inline-flex items-center gap-1.5 rounded-sm bg-merah-500 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-merah-600 shadow-sm transition-colors whitespace-nowrap">
                    + Tambah Narasumber
                </button>
            </div>

            @if($narasumbers->isEmpty())
                <div class="rounded-sm border border-dashed border-paper-300 p-8 text-center text-sm text-ink-500">
                    Belum ada sumber informasi / narasumber terdaftar. Klik <strong>+ Tambah Narasumber</strong> untuk memulai pendataan.
                </div>
            @else
                <div class="overflow-x-auto mb-2">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600">
                                <th class="px-3 py-2">No</th>
                                <th class="px-3 py-2">Nama Narasumber</th>
                                <th class="px-3 py-2">Kategori Narasumber</th>
                                <th class="px-3 py-2">Kontak</th>
                                <th class="px-3 py-2">Komoditas Yang Diberitahukan</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-200">
                            @foreach($narasumbers as $ns)
                                @php
                                    $prodNs = $produksis->where('id_narasumber', $ns->id_narasumber);
                                @endphp
                                <tr class="hover:bg-paper-100/70">
                                    <td class="px-3 py-2.5 font-mono text-ink-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2.5 font-semibold text-ink-900">
                                        {{ $ns->nama_narasumber }}
                                        @if($ns->keterangan)
                                            <span class="block text-[10px] text-ink-400 font-normal">{{ $ns->keterangan }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <span class="inline-block rounded bg-paper-200 px-2 py-0.5 text-[11px] font-semibold text-ink-800">
                                            {{ $ns->kategori }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-ink-600 font-mono">{{ $ns->nomor_kontak ?? '-' }}</td>
                                    <td class="px-3 py-2.5">
                                        @if($prodNs->isEmpty())
                                            <span class="text-ink-400 italic">Belum ada komoditas</span>
                                        @else
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($prodNs as $pItem)
                                                    @php
                                                        $pItemKg = $pItem->jumlah_produksi_kg ?: \Survei\Models\ProduksiNarasumber::konversiKeKg($pItem->jumlah_produksi, $pItem->satuan);
                                                        $isMass = in_array(strtolower(trim($pItem->satuan)), ['kg', 'ton', 'kuintal']);
                                                    @endphp
                                                    <span class="inline-flex items-center gap-1 rounded bg-sawah-100 text-sawah-900 border border-sawah-300 px-2 py-0.5 text-[11px] font-medium">
                                                        <strong class="font-bold">{{ $pItem->komoditas?->nama ?? 'Komoditas' }}</strong>
                                                        <span class="text-sawah-700">
                                                            @if($isMass)
                                                                ({{ number_format($pItemKg, 0, ',', '.') }} kg @if($pItemKg >= 1000) - {{ number_format($pItemKg / 1000, 2, ',', '.') }} ton @endif)
                                                            @else
                                                                ({{ number_format($pItem->jumlah_produksi, 0, ',', '.') }} {{ $pItem->satuan }})
                                                            @endif
                                                        </span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    data-ns-id="{{ $ns->id_narasumber }}"
                                                    data-ns-nama="{{ $ns->nama_narasumber }}"
                                                    data-ns-kategori="{{ $ns->kategori }}"
                                                    onclick="openTambahProduksiFromEl(this)"
                                                    class="rounded-sm bg-merah-500 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-merah-600 shadow-sm">
                                                + Tambah Komoditas
                                            </button>
                                            <button type="button"
                                                    data-ns-id="{{ $ns->id_narasumber }}"
                                                    data-ns-nama="{{ $ns->nama_narasumber }}"
                                                    data-ns-kategori="{{ $ns->kategori }}"
                                                    data-ns-kontak="{{ $ns->nomor_kontak ?? '' }}"
                                                    data-ns-keterangan="{{ $ns->keterangan ?? '' }}"
                                                    onclick="openEditNarasumberFromEl(this)"
                                                    class="rounded-sm border border-paper-300 bg-white px-2 py-1 text-[11px] font-medium text-ink-700 hover:bg-paper-100">
                                                Edit
                                            </button>
                                            <form action="{{ route('survei.sesi2.narasumber.destroy', [$sesi->id, $ns->id_narasumber]) }}" method="POST" onsubmit="return confirm('Hapus narasumber ini beserta data komoditas produksinya?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-sm border border-red-200 px-2 py-1 text-[11px] font-medium text-red-600 hover:bg-red-50">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- 13. RINGKASAN DATA KOMODITAS (CARD FORMAT PER KOMODITAS) --}}
        @if(!$produksis->isEmpty())
            <div class="rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-sm">
                <div class="mb-4 border-b border-paper-200 pb-3">
                    <h2 class="font-display text-base font-semibold text-ink-900">Cards Ringkasan Potensi Komoditas Produksi</h2>
                    <p class="text-xs text-ink-500">Tampilan ringkasan tiap komoditas produksi (satuan standar kg diutamakan).</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($produksis as $prod)
                        @php
                            $prodKg = $prod->jumlah_produksi_kg ?: \Survei\Models\ProduksiNarasumber::konversiKeKg($prod->jumlah_produksi, $prod->satuan);
                            $dijualKg = \Survei\Models\ProduksiNarasumber::konversiKeKg($prod->jumlah_dijual, $prod->satuan);
                            $konsumsiKg = \Survei\Models\ProduksiNarasumber::konversiKeKg($prod->jumlah_dikonsumsi_sendiri, $prod->satuan);
                            $sisaKg = \Survei\Models\ProduksiNarasumber::konversiKeKg($prod->sisa_produksi, $prod->satuan);
                            $isMass = in_array(strtolower(trim($prod->satuan)), ['kg', 'ton', 'kuintal']);

                            $bJson = $prod->bulan_panen_json;
                            $polaP = is_array($bJson) && isset($bJson['pola_panen']) ? $bJson['pola_panen'] : ($prod->periode ?? 'Musiman');
                            $mList = is_array($bJson) ? (isset($bJson['months']) ? $bJson['months'] : array_filter($bJson, fn($v)=>is_string($v))) : [];
                            $mStr = !empty($mList) ? implode(', ', $mList) : 'Tidak dispesifikasi';
                            $kdlList = is_array($prod->kendala_json) ? implode(', ', $prod->kendala_json) : ($prod->kendala_produksi ?: 'Tidak ada kendala');
                        @endphp
                        <div class="rounded-sm border border-paper-300 bg-white p-4 shadow-sm hover:border-merah-300 transition-colors flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div>
                                        <h3 class="font-bold text-sm text-ink-900">{{ $prod->komoditas?->nama ?? '-' }}</h3>
                                        <span class="inline-block rounded bg-paper-200 px-2 py-0.5 text-[10px] font-semibold text-ink-700">
                                            Kategori: {{ $prod->komoditas?->kategori ?? '-' }}
                                        </span>
                                    </div>
                                    <span class="inline-block rounded bg-sawah-100 text-sawah-800 px-2 py-0.5 text-[10px] font-bold">
                                        Lengkap
                                    </span>
                                </div>

                                <div class="text-xs space-y-1.5 border-t border-paper-100 pt-2.5 text-ink-700">
                                    <div class="flex justify-between">
                                        <span class="text-ink-400">Narasumber:</span>
                                        <span class="font-semibold text-ink-900">{{ $prod->narasumber?->nama_narasumber ?? '-' }} ({{ $prod->narasumber?->kategori }})</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-ink-400">Total Produksi:</span>
                                        <span class="font-mono font-bold text-merah-700">
                                            @if($isMass)
                                                {{ number_format($prodKg, 0, ',', '.') }} kg
                                                @if($prodKg >= 1000)
                                                    <span class="text-ink-500 font-normal text-[11px]">({{ number_format($prodKg / 1000, 2, ',', '.') }} ton)</span>
                                                @endif
                                            @else
                                                {{ number_format($prod->jumlah_produksi, 0, ',', '.') }} {{ $prod->satuan }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-ink-400">Periode / Pola:</span>
                                        <span class="font-medium text-ink-800">{{ $prod->periode }} ({{ $polaP }})</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-ink-400">Bulan Tersedia:</span>
                                        <span class="font-medium text-sawah-800">{{ $mStr }}</span>
                                    </div>
                                    <div class="pt-1 text-[11px]">
                                        <span class="text-ink-400 block">Kendala:</span>
                                        <span class="text-ink-800 font-medium line-clamp-2" title="{{ $kdlList }}">{{ $kdlList }}</span>
                                    </div>
                                    <div class="text-[10px] text-ink-400 pt-1 border-t border-paper-100">
                                        Sumber: {{ $prod->sumber_data }} ({{ $prod->tanggal_data ? $prod->tanggal_data->format('d M Y') : '-' }})
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-end gap-2 border-t border-paper-100 pt-2.5">
                                <button type="button"
                                        data-prod="{{ json_encode($prod) }}"
                                        onclick="openEditProduksiFromEl(this)"
                                        class="rounded-sm border border-paper-300 bg-white px-3 py-1 text-xs font-medium text-ink-700 hover:bg-paper-100">
                                    Edit
                                </button>
                                <form action="{{ route('survei.sesi2.produksi.destroy', [$sesi->id, $prod->id_produksi_ns]) }}" method="POST" onsubmit="return confirm('Hapus data produksi komoditas ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-sm border border-red-200 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 14. DAFTAR SEMUA KOMODITAS YANG DITEMUKAN (TABEL LENGKAP) --}}
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-sm">
            <div class="mb-4 border-b border-paper-200 pb-3">
                <h2 class="font-display text-base font-semibold text-ink-900">Daftar Semua Komoditas Yang Ditemukan</h2>
                <p class="text-xs text-ink-500">Tabel seluruh data komoditas produksi hasil survei lapangan dari berbagai narasumber (standar kg diutamakan).</p>
            </div>

            @if($produksis->isEmpty())
                <div class="rounded-sm border border-dashed border-paper-300 p-8 text-center text-xs text-ink-400">
                    Belum ada data komoditas produksi. Silakan tambah narasumber dan masukkan data komoditas produksi.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600">
                                <th class="px-3 py-2">Komoditas</th>
                                <th class="px-3 py-2">Kategori</th>
                                <th class="px-3 py-2">Narasumber</th>
                                <th class="px-3 py-2 text-right">Total Produksi Utama (kg)</th>
                                <th class="px-3 py-2">Periode</th>
                                <th class="px-3 py-2">Bulan Tersedia</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-200">
                            @foreach($produksis as $prod)
                                @php
                                    $prodKg = $prod->jumlah_produksi_kg ?: \Survei\Models\ProduksiNarasumber::konversiKeKg($prod->jumlah_produksi, $prod->satuan);
                                    $isMass = in_array(strtolower(trim($prod->satuan)), ['kg', 'ton', 'kuintal']);
                                    $bJson = $prod->bulan_panen_json;
                                    $mList = is_array($bJson) ? (isset($bJson['months']) ? $bJson['months'] : array_filter($bJson, fn($v)=>is_string($v))) : [];
                                    $bPanenStr = !empty($mList) ? implode(', ', $mList) : '-';
                                @endphp
                                <tr class="hover:bg-paper-100/70">
                                    <td class="px-3 py-2.5 font-bold text-ink-900">{{ $prod->komoditas?->nama ?? '-' }}</td>
                                    <td class="px-3 py-2.5">
                                        <span class="inline-block rounded bg-paper-200 px-2 py-0.5 text-[10px] font-medium text-ink-700">
                                            {{ $prod->komoditas?->kategori ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <div class="font-medium text-ink-800">{{ $prod->narasumber?->nama_narasumber ?? '-' }}</div>
                                        <div class="text-[10px] text-ink-400">{{ $prod->narasumber?->kategori }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold text-merah-700">
                                        @if($isMass)
                                            {{ number_format($prodKg, 0, ',', '.') }} kg
                                            @if($prodKg >= 1000)
                                                <div class="text-[10px] text-ink-500 font-normal">({{ number_format($prodKg / 1000, 2, ',', '.') }} ton)</div>
                                            @endif
                                        @else
                                            {{ number_format($prod->jumlah_produksi, 0, ',', '.') }} {{ $prod->satuan }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-ink-700 font-medium">{{ $prod->periode }}</td>
                                    <td class="px-3 py-2.5 text-sawah-800 font-semibold">{{ $bPanenStr }}</td>
                                    <td class="px-3 py-2.5">
                                        <span class="inline-block rounded bg-sawah-100 text-sawah-800 px-2 py-0.5 text-[10px] font-semibold">
                                            Lengkap
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    data-prod="{{ json_encode($prod) }}"
                                                    onclick="openEditProduksiFromEl(this)"
                                                    class="rounded-sm border border-paper-300 bg-white px-2 py-1 text-[11px] font-medium text-ink-700 hover:bg-paper-100">
                                                Edit
                                            </button>
                                            <form action="{{ route('survei.sesi2.produksi.destroy', [$sesi->id, $prod->id_produksi_ns]) }}" method="POST" onsubmit="return confirm('Hapus data komoditas ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-sm border border-red-200 px-2 py-1 text-[11px] font-medium text-red-600 hover:bg-red-50">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>


        {{-- 18. STATUS SESI & ACTION BAR --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-paper-200 pt-5">
            <form action="{{ route('survei.sesi2.draft', $sesi->id) }}" method="POST">
                @csrf
                <button type="submit" class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-xs font-semibold text-ink-700 hover:bg-paper-100">
                    💾 Simpan Draft Sesi 2
                </button>
            </form>

            <form action="{{ route('survei.sesi2.selesaikan', $sesi->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin data Sesi 2 — Potensi Produksi Desa sudah lengkap dan benar?')">
                @csrf
                <button type="submit" class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-white hover:bg-merah-600 shadow-sm transition-colors">
                    ✓ Selesaikan Sesi 2
                </button>
            </form>
        </div>

    </div>

    {{-- MODAL 1: Tambah / Edit Narasumber --}}
    <div id="modalNarasumber" class="fixed inset-0 z-50 hidden overflow-y-auto bg-ink-900/60 backdrop-blur-sm" onclick="if(event.target === this) closeModal('modalNarasumber')">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-md border border-paper-300 bg-paper-50 shadow-2xl" onclick="event.stopPropagation()">
                {{-- Modal Header --}}
                <div class="px-5 py-4 border-b border-paper-200 bg-paper-100/90 flex items-center justify-between rounded-t-md">
                    <div>
                        <h3 id="modalNarasumberTitle" class="font-display text-base font-semibold text-ink-900">Tambah Sumber Informasi / Narasumber</h3>
                        <p class="text-xs text-ink-500">Mencatat narasumber yang memberikan informasi potensi produksi desa.</p>
                    </div>
                    <button type="button" onclick="closeModal('modalNarasumber')" class="flex items-center justify-center w-8 h-8 rounded-full bg-paper-200 text-ink-700 hover:bg-merah-500 hover:text-white transition-colors shadow-sm" title="Tutup Modal (Esc)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-5">
                    <form id="formNarasumber" method="POST" action="{{ route('survei.sesi2.narasumber.store', $sesi->id) }}">
                        @csrf
                        <input type="hidden" name="_method" id="methodNarasumber" value="POST">

                        <div class="mb-4">
                            <label class="block text-xs font-medium text-ink-800 mb-1">Nama Narasumber / Sumber Informasi <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_narasumber" id="ns_nama" required class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none" placeholder="Contoh: Budi Santoso (Ketua Kelompok Tani Tani Makmur)">
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-medium text-ink-800 mb-1">Kategori Narasumber <span class="text-red-500">*</span></label>
                            <select name="kategori" id="ns_kategori" required class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                                <option value="">-- Pilih Kategori Narasumber --</option>
                                @foreach($kategoriNarasumber as $kat)
                                    <option value="{{ $kat }}">{{ $kat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-medium text-ink-800 mb-1">Nomor Kontak (Opsional)</label>
                            <input type="text" name="nomor_kontak" id="ns_kontak" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none" placeholder="08123456789">
                        </div>

                        <div class="mb-2">
                            <label class="block text-xs font-medium text-ink-800 mb-1">Keterangan Tambahan (Opsional)</label>
                            <textarea name="keterangan" id="ns_keterangan" rows="2" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none" placeholder="Catatan lokasi/wilayah kelola narasumber..."></textarea>
                        </div>
                    </form>
                </div>

                {{-- Modal Footer --}}
                <div class="px-5 py-3 border-t border-paper-200 bg-paper-100/90 flex justify-end gap-2 rounded-b-md">
                    <button type="button" onclick="closeModal('modalNarasumber')" class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">Batal / Tutup</button>
                    <button type="submit" form="formNarasumber" class="rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-white hover:bg-merah-600">Simpan Narasumber</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: Input / Edit Data Potensi Produksi Komoditas --}}
    <div id="modalProduksi" class="fixed inset-0 z-50 hidden overflow-y-auto bg-ink-900/60 backdrop-blur-sm" onclick="if(event.target === this) closeModal('modalProduksi')">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-4xl rounded-md border border-paper-300 bg-paper-50 shadow-2xl" onclick="event.stopPropagation()">
                {{-- Modal Header --}}
                <div class="px-5 py-4 border-b border-paper-200 bg-paper-100/90 flex items-center justify-between rounded-t-md">
                <div>
                    <h3 id="modalProduksiTitle" class="font-display text-base font-semibold text-ink-900">Input Data Potensi Produksi Komoditas</h3>
                    <div id="badgeNarasumberInfo" class="mt-1 inline-flex items-center gap-1.5 rounded bg-merah-100 text-merah-800 px-2.5 py-0.5 text-xs font-semibold">
                        <span>Narasumber: -</span>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modalProduksi')" class="flex items-center justify-center w-8 h-8 rounded-full bg-paper-200 text-ink-700 hover:bg-merah-500 hover:text-white transition-colors shadow-sm" title="Tutup Modal (Esc)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

                {{-- Modal Body --}}
                <div class="p-5 sm:p-6 space-y-4">
                    <form id="formProduksi" method="POST" action="{{ route('survei.sesi2.produksi.store', $sesi->id) }}">
                    @csrf
                    <input type="hidden" name="_method" id="methodProduksi" value="POST">
                    <input type="hidden" name="id_narasumber" id="prod_id_narasumber">
                    <input type="hidden" name="jumlah_dijual" id="prod_jumlah_dijual" value="0">
                    <input type="hidden" name="jumlah_dikonsumsi_sendiri" id="prod_jumlah_dikonsumsi_sendiri" value="0">

                    {{-- 1 & 2. PILIH KATEGORI & NAMA KOMODITAS MASTER --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">1. Kategori Komoditas <span class="text-red-500">*</span></label>
                            <select id="filter_kategori_komoditas" onchange="filterKomoditasByKategori()" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                                <option value="">-- Semua Kategori --</option>
                                @foreach($kategoriKomoditas as $kat)
                                    <option value="{{ $kat }}">{{ $kat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">2. Nama Komoditas (Hasil Produksi) <span class="text-red-500">*</span></label>
                            <div class="relative mb-1">
                                <input type="text"
                                       id="search_komoditas_input"
                                       oninput="filterKomoditasWithSearch()"
                                       onfocus="filterKomoditasWithSearch()"
                                       onkeydown="handleSearchKeyDown(event)"
                                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-1.5 text-xs focus:border-merah-400 focus:outline-none placeholder-ink-400"
                                       placeholder="Cari / ketik komoditas (misal: sapi)..."
                                       autocomplete="off">
                                <div id="komoditas_dropdown_list" class="hidden absolute left-0 right-0 top-full mt-1 z-30 max-h-56 overflow-y-auto rounded border border-paper-300 bg-white shadow-lg text-xs divide-y divide-paper-100">
                                </div>
                            </div>
                            <select name="id_komoditas" id="prod_id_komoditas" required onchange="onKomoditasChange()" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                                <option value="">-- Pilih Komoditas Hasil Produksi --</option>
                                @foreach($komoditasList as $k)
                                    <option value="{{ $k->id }}" data-kategori="{{ $k->kategori }}">
                                        {{ $k->nama }} ({{ $k->kategori }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Warning jika Kategori Komoditas vs Narasumber tidak cocok --}}
                    <div id="warningCategoryMismatch" class="hidden mb-3 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-300 p-2.5 rounded-sm">
                        Catatan Kesesuaian: Narasumber ini berkategori <span id="warnNsCat" class="font-bold"></span>, sedangkan komoditas yang dipilih berkategori <span id="warnKomCat" class="font-bold"></span>. Pastikan data narasumber ini memang benar.
                    </div>

                    {{-- 3 & 4. JUMLAH PRODUKSI, SATUAN, & PERIODE --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">3. Jumlah Produksi <span class="text-red-500">*</span></label>
                            <input type="number" step="any" name="jumlah_produksi" id="prod_jumlah_produksi" required min="0" oninput="hitungKonversiKg()" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">Satuan <span class="text-red-500">*</span></label>
                            <select name="satuan" id="prod_satuan" required onchange="hitungKonversiKg()" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                                @foreach($satuanList as $sat)
                                    <option value="{{ $sat }}">{{ $sat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">4. Periode Produksi <span class="text-red-500">*</span></label>
                            <select name="periode" id="prod_periode" required class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                                @foreach($periodeList as $per)
                                    <option value="{{ $per }}">{{ $per }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Konversi Satuan Dasar Display (kg) --}}
                    <div class="mb-3 text-xs font-medium text-merah-800 bg-merah-50 border border-merah-200 px-3 py-1.5 rounded-sm flex justify-between items-center">
                        <span>Satuan Standar Disimpan Sistem (Normalisasi):</span>
                        <span id="display_konversi_kg" class="font-bold font-mono text-sm text-merah-700">0 kg</span>
                    </div>

                    {{-- 5. KALENDER KETERSEDIAAN / POLA PANEN 12 BULAN (2 KOLOM SIDE-BY-SIDE) --}}
                    <div class="mb-4 rounded-sm border border-paper-300 bg-paper-100/70 p-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <div>
                                <label class="block text-xs font-bold text-ink-900 uppercase tracking-wide">
                                    5. Kalender Produksi / Ketersediaan Komoditas (12 Bulan)
                                </label>
                                <p class="text-[11px] text-ink-500">Tentukan bulan ketersediaan/panen dan jumlah produksi per bulan (Januari - Desember).</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-medium text-ink-800">Pola Panen:</label>
                                <select name="pola_panen" id="prod_pola_panen" class="rounded-sm border border-paper-300 bg-white px-2 py-1 text-xs focus:border-merah-400 focus:outline-none">
                                    @foreach($polaPanenList as $pola)
                                        <option value="{{ $pola }}">{{ $pola }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @php
                            $bKeys = array_keys($bulanPanenList);
                            $col1Keys = array_slice($bKeys, 0, 6); // Jan - Jun
                            $col2Keys = array_slice($bKeys, 6, 6); // Jul - Des
                        @endphp

                        {{-- Grid 2 Kolom Sejajar (Jan-Jun di kiri, Jul-Des di kanan) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                            {{-- Kolom Kiri: Jan - Jun --}}
                            <div class="overflow-x-auto bg-white rounded border border-paper-300">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="bg-paper-200/80 font-mono text-[10px] uppercase text-ink-700">
                                            <th class="px-2 py-1 text-center">Bulan</th>
                                            <th class="px-2 py-1 text-center">Panen</th>
                                            <th class="px-2 py-1">Jumlah Produksi</th>
                                            <th class="px-2 py-1">Satuan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-paper-200">
                                        @foreach($col1Keys as $bKode)
                                            @php $bNama = $bulanPanenList[$bKode]; @endphp
                                            <tr class="hover:bg-paper-50">
                                                <td class="px-2 py-1 font-semibold text-ink-800 text-center">{{ $bNama }} ({{ $bKode }})</td>
                                                <td class="px-2 py-1 text-center">
                                                    <input type="checkbox" name="bulan_panen_active[]" value="{{ $bKode }}" id="chk_month_{{ $bKode }}" onchange="toggleMonthInput('{{ $bKode }}')" class="chk_bulan_panen h-4 w-4 text-merah-600 rounded border-paper-300">
                                                </td>
                                                <td class="px-2 py-1">
                                                    <input type="number" step="any" min="0" name="bulan_panen_qty[{{ $bKode }}]" id="qty_month_{{ $bKode }}" disabled oninput="recalcMonthlySum()" class="w-full rounded border border-paper-300 bg-paper-100 px-2 py-0.5 text-xs font-mono disabled:opacity-50 focus:bg-white focus:outline-none" placeholder="0">
                                                </td>
                                                <td class="px-2 py-1 font-mono text-ink-500 text-[11px] label_satuan_display">kg</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Kolom Kanan: Jul - Des --}}
                            <div class="overflow-x-auto bg-white rounded border border-paper-300">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="bg-paper-200/80 font-mono text-[10px] uppercase text-ink-700">
                                            <th class="px-2 py-1 text-center">Bulan</th>
                                            <th class="px-2 py-1 text-center">Panen</th>
                                            <th class="px-2 py-1">Jumlah Produksi</th>
                                            <th class="px-2 py-1">Satuan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-paper-200">
                                        @foreach($col2Keys as $bKode)
                                            @php $bNama = $bulanPanenList[$bKode]; @endphp
                                            <tr class="hover:bg-paper-50">
                                                <td class="px-2 py-1 font-semibold text-ink-800 text-center">{{ $bNama }} ({{ $bKode }})</td>
                                                <td class="px-2 py-1 text-center">
                                                    <input type="checkbox" name="bulan_panen_active[]" value="{{ $bKode }}" id="chk_month_{{ $bKode }}" onchange="toggleMonthInput('{{ $bKode }}')" class="chk_bulan_panen h-4 w-4 text-merah-600 rounded border-paper-300">
                                                </td>
                                                <td class="px-2 py-1">
                                                    <input type="number" step="any" min="0" name="bulan_panen_qty[{{ $bKode }}]" id="qty_month_{{ $bKode }}" disabled oninput="recalcMonthlySum()" class="w-full rounded border border-paper-300 bg-paper-100 px-2 py-0.5 text-xs font-mono disabled:opacity-50 focus:bg-white focus:outline-none" placeholder="0">
                                                </td>
                                                <td class="px-2 py-1 font-mono text-ink-500 text-[11px] label_satuan_display">kg</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- 6. KENDALA PRODUKSI --}}
                    <div class="mb-4 rounded-sm border border-paper-300 bg-paper-100/60 p-3">
                        <label class="block text-xs font-bold text-ink-900 uppercase tracking-wide mb-1">
                            6. Kendala Produksi (Pilihan Ganda)
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2 text-xs">
                            @foreach($kendalaList as $kdl)
                                <label class="flex items-center gap-1.5 bg-white border border-paper-300 rounded px-2 py-1 cursor-pointer hover:bg-paper-50">
                                    <input type="checkbox" name="kendala_json[]" value="{{ $kdl }}" class="chk_kendala h-3.5 w-3.5 text-merah-600 rounded">
                                    <span class="text-[11px] text-ink-800 truncate" title="{{ $kdl }}">{{ $kdl }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-ink-700 mb-1">Keterangan Kendala Detail (Opsional)</label>
                            <textarea name="kendala_produksi" id="prod_kendala_produksi" rows="2" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-1.5 text-xs focus:border-merah-400 focus:outline-none" placeholder="Misal: Produksi menurun karena serangan hama atau keterbatasan irigasi"></textarea>
                        </div>
                    </div>

                    {{-- 7. SUMBER DATA HARUS TERLACAK --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-2">
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">7. Sumber Data / Metode Pendataan</label>
                            <select name="sumber_data" id="prod_sumber_data" required class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                                @foreach($sumberDataList as $sbr)
                                    <option value="{{ $sbr }}">{{ $sbr }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-800 mb-1">Tanggal Pendataan Lapangan</label>
                            <input type="date" name="tanggal_data" id="prod_tanggal_data" value="{{ date('Y-m-d') }}" class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-xs focus:border-merah-400 focus:outline-none">
                        </div>
                    </div>

                </form>

                {{-- Modal Footer --}}
                <div class="px-5 py-3 border-t border-paper-200 bg-paper-100/90 flex justify-end gap-2 rounded-b-md">
                    <button type="button" onclick="closeModal('modalProduksi')" class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">Batal / Tutup</button>
                    <button type="submit" form="formProduksi" class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-white hover:bg-merah-600 shadow-sm">Simpan Data Produksi</button>
                </div>
            </div>
        </div>
    </div>

    {{-- JS Functions --}}
    <script>
        const narasumberList = @json($narasumbers);

        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        function openEditNarasumberFromEl(el) {
            openEditNarasumber(
                el.getAttribute('data-ns-id'),
                el.getAttribute('data-ns-nama'),
                el.getAttribute('data-ns-kategori'),
                el.getAttribute('data-ns-kontak'),
                el.getAttribute('data-ns-keterangan')
            );
        }

        function openEditProduksiFromEl(el) {
            try {
                const prod = JSON.parse(el.getAttribute('data-prod'));
                openEditProduksi(prod);
            } catch(e) {
                console.error('Error parsing production json data:', e);
            }
        }

        function openModalTambahNarasumber() {
            document.getElementById('modalNarasumberTitle').innerText = 'Tambah Sumber Informasi / Narasumber';
            document.getElementById('formNarasumber').action = "{{ route('survei.sesi2.narasumber.store', $sesi->id) }}";
            document.getElementById('methodNarasumber').value = 'POST';
            document.getElementById('ns_nama').value = '';
            document.getElementById('ns_kategori').value = '';
            document.getElementById('ns_kontak').value = '';
            document.getElementById('ns_keterangan').value = '';
            openModal('modalNarasumber');
        }

        function openEditNarasumber(id, nama, kategori, kontak, ket) {
            document.getElementById('modalNarasumberTitle').innerText = 'Edit Data Narasumber';
            document.getElementById('formNarasumber').action = "/survei/sesi/{{ $sesi->id }}/sesi2/narasumber/" + id;
            document.getElementById('methodNarasumber').value = 'PUT';
            document.getElementById('ns_nama').value = nama;
            document.getElementById('ns_kategori').value = kategori;
            document.getElementById('ns_kontak').value = kontak;
            document.getElementById('ns_keterangan').value = ket;
            openModal('modalNarasumber');
        }

        function setVal(id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val !== undefined && val !== null ? val : '';
        }
        function setHtml(id, html) {
            const el = document.getElementById(id);
            if (el) el.innerHTML = html;
        }
        function setAttr(id, attr, val) {
            const el = document.getElementById(id);
            if (el) el[attr] = val;
        }

        function openTambahProduksiFromEl(el) {
            const nsId = el.getAttribute('data-ns-id');
            const nsNama = el.getAttribute('data-ns-nama');
            const nsKategori = el.getAttribute('data-ns-kategori');
            openTambahProduksi(nsId, nsNama, nsKategori);
        }

        function openEditProduksiFromEl(el) {
            try {
                const prodData = JSON.parse(el.getAttribute('data-prod'));
                if (prodData) {
                    openEditProduksi(prodData);
                }
            } catch(e) {
                console.error('Error parsing production data for edit:', e);
            }
        }

        let currentNsCategory = '';

        function openTambahProduksi(nsId, nsNama, nsKategori) {
            currentNsCategory = nsKategori || '';
            setHtml('modalProduksiTitle', 'Input Data Produksi Komoditas');
            setHtml('badgeNarasumberInfo', 'Narasumber: <strong>' + nsNama + '</strong> (' + nsKategori + ')');
            setAttr('formProduksi', 'action', "{{ route('survei.sesi2.produksi.store', $sesi->id) }}");
            setVal('methodProduksi', 'POST');
            setVal('prod_id_narasumber', nsId);
            setVal('prod_id_komoditas', '');
            setVal('filter_kategori_komoditas', '');
            setVal('search_komoditas_input', '');
            setVal('prod_jumlah_produksi', '');
            setVal('prod_satuan', 'kg');
            setVal('prod_periode', 'Musiman');
            setVal('prod_pola_panen', 'Musiman');
            setVal('prod_jumlah_dijual', '0');
            setVal('prod_jumlah_dikonsumsi_sendiri', '0');
            setVal('prod_kendala_produksi', '');
            setVal('prod_sumber_data', 'Wawancara Narasumber / Pelaku Produksi');
            
            reset12MonthGrid();
            resetCheckboxes('chk_kendala');

            filterKomoditasByKategori();
            const drop = document.getElementById('komoditas_dropdown_list');
            if (drop) drop.classList.add('hidden');
            hitungKonversiKg();
            hitungsisa();
            openModal('modalProduksi');
        }

        function openEditProduksi(prod) {
            const nsObj = narasumberList.find(n => n.id_narasumber == prod.id_narasumber);
            currentNsCategory = nsObj ? nsObj.kategori : '';

            setHtml('modalProduksiTitle', 'Edit Data Produksi Komoditas');
            setHtml('badgeNarasumberInfo', 'Narasumber: <strong>' + (nsObj ? nsObj.nama_narasumber : '-') + '</strong> (' + currentNsCategory + ')');
            setAttr('formProduksi', 'action', "/survei/sesi/{{ $sesi->id }}/sesi2/produksi/" + prod.id_produksi_ns);
            setVal('methodProduksi', 'PUT');
            setVal('prod_id_narasumber', prod.id_narasumber);
            setVal('search_komoditas_input', prod.komoditas ? prod.komoditas.nama : '');

            const drop = document.getElementById('komoditas_dropdown_list');
            if (drop) drop.classList.add('hidden');

            setVal('prod_id_komoditas', prod.id_komoditas);
            setVal('prod_jumlah_produksi', prod.jumlah_produksi);
            setVal('prod_satuan', prod.satuan || 'kg');
            setVal('prod_periode', prod.periode || 'Musiman');
            setVal('prod_jumlah_dijual', prod.jumlah_dijual);
            setVal('prod_jumlah_dikonsumsi_sendiri', prod.jumlah_dikonsumsi_sendiri);
            setVal('prod_kendala_produksi', prod.kendala_produksi || '');
            setVal('prod_sumber_data', prod.sumber_data || 'Wawancara Narasumber / Pelaku Produksi');
            
            if (prod.tanggal_data) {
                setVal('prod_tanggal_data', prod.tanggal_data.substring(0, 10));
            }

            reset12MonthGrid();
            const bJson = prod.bulan_panen_json;
            if (bJson) {
                if (bJson.pola_panen) {
                    setVal('prod_pola_panen', bJson.pola_panen);
                }
                const activeM = bJson.months || (Array.isArray(bJson) ? bJson : []);
                const qtyM = bJson.monthly_qty || {};

                activeM.forEach(mCode => {
                    const chk = document.getElementById('chk_month_' + mCode);
                    const qtyIn = document.getElementById('qty_month_' + mCode);
                    if (chk) {
                        chk.checked = true;
                        if (qtyIn) {
                            qtyIn.disabled = false;
                            qtyIn.classList.remove('bg-paper-100');
                            qtyIn.classList.add('bg-white');
                            if (qtyM[mCode] !== undefined) qtyIn.value = qtyM[mCode];
                        }
                    }
                });
            }

            setCheckboxes('chk_kendala', prod.kendala_json);

            onKomoditasChange();
            hitungKonversiKg();
            hitungsisa();
            recalcMonthlySum();
            openModal('modalProduksi');
        }

        function toggleMonthInput(mCode) {
            const chk = document.getElementById('chk_month_' + mCode);
            const qtyIn = document.getElementById('qty_month_' + mCode);
            if (chk && qtyIn) {
                if (chk.checked) {
                    qtyIn.disabled = false;
                    qtyIn.classList.remove('bg-paper-100');
                    qtyIn.classList.add('bg-white');
                } else {
                    qtyIn.disabled = true;
                    qtyIn.value = '';
                    qtyIn.classList.add('bg-paper-100');
                    qtyIn.classList.remove('bg-white');
                }
            }
            recalcMonthlySum();
        }

        function reset12MonthGrid() {
            document.querySelectorAll('.chk_bulan_panen').forEach(chk => {
                chk.checked = false;
                const mCode = chk.value;
                const qtyIn = document.getElementById('qty_month_' + mCode);
                if (qtyIn) {
                    qtyIn.disabled = true;
                    qtyIn.value = '';
                    qtyIn.classList.add('bg-paper-100');
                    qtyIn.classList.remove('bg-white');
                }
            });
            recalcMonthlySum();
        }

        function recalcMonthlySum() {
            let total = 0;
            const sat = (document.getElementById('prod_satuan')?.value || 'kg').toLowerCase().trim();
            
            document.querySelectorAll('.chk_bulan_panen').forEach(chk => {
                if (chk.checked) {
                    const mCode = chk.value;
                    const qtyIn = document.getElementById('qty_month_' + mCode);
                    const val = qtyIn && qtyIn.value ? parseFloat(qtyIn.value) || 0 : 0;
                    total += val;
                }
            });

            // Auto-sync total ke field "Jumlah Produksi" jika ada bulan yang dicentang
            const anyChecked = document.querySelectorAll('.chk_bulan_panen:checked').length > 0;
            if (anyChecked) {
                const prodEl = document.getElementById('prod_jumlah_produksi');
                if (prodEl) {
                    prodEl.value = total > 0 ? total : '';
                    hitungKonversiKg();
                }
            }

            const sumEl = document.getElementById('sum_monthly_qty');
            if (sumEl) {
                let totalKg = total;
                if (sat === 'ton') totalKg = total * 1000;
                else if (sat === 'kuintal') totalKg = total * 100;
                let displaySumStr = total.toLocaleString('id-ID') + ' ' + sat;
                if (sat === 'kg' && total >= 1000) {
                    displaySumStr += ' (' + (total / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' ton)';
                } else if (sat !== 'kg' && ['ton', 'kuintal'].includes(sat)) {
                    displaySumStr += ' (' + totalKg.toLocaleString('id-ID') + ' kg)';
                }
                sumEl.innerText = displaySumStr;
            }
        }

        function autoDistributeTotalToMonths() {
            const totalProd = parseFloat(document.getElementById('prod_jumlah_produksi').value) || 0;
            const checkedMonths = Array.from(document.querySelectorAll('.chk_bulan_panen')).filter(chk => chk.checked);

            if (totalProd <= 0) {
                alert('Silakan isi field "Jumlah Produksi" terlebih dahulu.');
                return;
            }

            if (checkedMonths.length === 0) {
                alert('Silakan centang setidaknya 1 bulan panen pada tabel di atas.');
                return;
            }

            const share = Math.round((totalProd / checkedMonths.length) * 100) / 100;
            checkedMonths.forEach(chk => {
                const mCode = chk.value;
                const qtyIn = document.getElementById('qty_month_' + mCode);
                if (qtyIn) {
                    qtyIn.disabled = false;
                    qtyIn.classList.remove('bg-paper-100');
                    qtyIn.classList.add('bg-white');
                    qtyIn.value = share;
                }
            });

            recalcMonthlySum();
        }

        function useMonthlySumAsTotal() {
            let total = 0;
            document.querySelectorAll('.chk_bulan_panen').forEach(chk => {
                if (chk.checked) {
                    const mCode = chk.value;
                    const qtyIn = document.getElementById('qty_month_' + mCode);
                    if (qtyIn && qtyIn.value) {
                        total += parseFloat(qtyIn.value) || 0;
                    }
                }
            });
            if (total > 0) {
                document.getElementById('prod_jumlah_produksi').value = total;
                hitungKonversiKg();
            } else {
                alert('Belum ada nilai produksi bulanan yang diisi.');
            }
        }

        function filterKomoditasByKategori() {
            filterKomoditasWithSearch();
        }

        function selectKomoditasItem(id, nama, kategori) {
            const sel = document.getElementById('prod_id_komoditas');
            const searchIn = document.getElementById('search_komoditas_input');
            const katSel = document.getElementById('filter_kategori_komoditas');

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

            const drop = document.getElementById('komoditas_dropdown_list');
            if (drop) drop.classList.add('hidden');

            onKomoditasChange();
        }

        function filterKomoditasWithSearch() {
            const searchIn = document.getElementById('search_komoditas_input');
            const drop = document.getElementById('komoditas_dropdown_list');
            const katSel = document.getElementById('filter_kategori_komoditas');
            const sel = document.getElementById('prod_id_komoditas');

            if (!searchIn || !drop || !sel) return;

            const searchVal = searchIn.value.trim();
            const searchLower = searchVal.toLowerCase();
            let selectedKat = katSel ? katSel.value : '';

            // Extract all options from select (excluding empty placeholder and previous new: options)
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

            // Filter options matching search input and selected category
            let matches = options.filter(item => {
                const matchesSearch = !searchLower || item.fullText.toLowerCase().includes(searchLower) || item.nama.toLowerCase().includes(searchLower);
                const matchesKat = !selectedKat || item.kategori === selectedKat;
                return matchesSearch && matchesKat;
            });

            // Smart fallback: if user typed something but 0 matches found due to selected category filter,
            // search across ALL categories and auto-switch category to the first match!
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

            // Render floating dropdown items
            let html = '';
            if (matches.length > 0) {
                matches.forEach(item => {
                    const safeNama = item.nama.replace(/'/g, "\\'");
                    const safeKat = item.kategori.replace(/'/g, "\\'");
                    html += `<div onclick="selectKomoditasItem('${item.id}', '${safeNama}', '${safeKat}')" class="px-3 py-2 hover:bg-paper-100 cursor-pointer border-b border-paper-100 flex items-center justify-between">
                        <span class="font-medium text-ink-900">${item.nama}</span>
                        <span class="rounded bg-paper-200 text-ink-600 px-1.5 py-0.5 text-[10px]">${item.kategori}</span>
                    </div>`;
                });
            }

            const exactExists = options.some(item => item.nama.toLowerCase() === searchLower);
            if (searchVal.length > 0 && !exactExists) {
                const safeVal = searchVal.replace(/'/g, "\\'");
                const newKat = selectedKat || 'Hasil Pertanian / Lainnya';
                html += `<div onclick="selectKomoditasItem('new:${safeVal}', '${safeVal}', '${newKat}')" class="px-3 py-2 bg-merah-50 hover:bg-merah-100 text-merah-800 font-semibold cursor-pointer border-b border-paper-100 flex items-center justify-between">
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

            // Synchronize hidden select dropdown options
            for (let i = 0; i < sel.options.length; i++) {
                const opt = sel.options[i];
                if (!opt.value) continue;
                const isMatched = matches.some(m => m.id === opt.value);
                opt.style.display = isMatched ? '' : 'none';
            }

            if (matches.length > 0 && searchLower.length > 0) {
                sel.value = matches[0].id;
                onKomoditasChange();
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
                onKomoditasChange();
            }

            checkCategoryMismatch();
        }

        function handleSearchKeyDown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const drop = document.getElementById('komoditas_dropdown_list');
                const firstItem = drop ? drop.querySelector('div[onclick]') : null;
                if (firstItem) {
                    firstItem.click();
                }
            } else if (e.key === 'Escape') {
                const drop = document.getElementById('komoditas_dropdown_list');
                if (drop) drop.classList.add('hidden');
            }
        }

        document.addEventListener('click', function(e) {
            const searchIn = document.getElementById('search_komoditas_input');
            const drop = document.getElementById('komoditas_dropdown_list');
            if (searchIn && drop && !searchIn.contains(e.target) && !drop.contains(e.target)) {
                drop.classList.add('hidden');
            }
        });

        function onKomoditasChange() {
            const sel = document.getElementById('prod_id_komoditas');
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.value) {
                const kat = opt.getAttribute('data-kategori');
                if (kat) {
                    document.getElementById('filter_kategori_komoditas').value = kat;
                }
            }
            checkCategoryMismatch();
        }

        function checkCategoryMismatch() {
            const sel = document.getElementById('prod_id_komoditas');
            const opt = sel.options[sel.selectedIndex];
            const warnBox = document.getElementById('warningCategoryMismatch');
            
            if (opt && opt.value && currentNsCategory) {
                const komKat = opt.getAttribute('data-kategori');
                const nsKatLower = currentNsCategory.toLowerCase();
                const komKatLower = komKat ? komKat.toLowerCase() : '';

                let mismatch = false;
                if (nsKatLower.includes('ternak') && !komKatLower.includes('ternak')) mismatch = true;
                else if ((nsKatLower.includes('nelayan') || nsKatLower.includes('budidaya') || nsKatLower.includes('tambak')) && !komKatLower.includes('ikan')) mismatch = true;
                else if ((nsKatLower.includes('tani') || nsKatLower.includes('petani')) && (komKatLower.includes('ternak') || komKatLower.includes('ikan'))) mismatch = true;

                if (mismatch) {
                    document.getElementById('warnNsCat').innerText = currentNsCategory;
                    document.getElementById('warnKomCat').innerText = komKat;
                    warnBox.classList.remove('hidden');
                    return;
                }
            }
            warnBox.classList.add('hidden');
        }

        function hitungKonversiKg() {
            const jml = parseFloat(document.getElementById('prod_jumlah_produksi').value) || 0;
            const sat = (document.getElementById('prod_satuan').value || 'kg').toLowerCase().trim();
            
            let kg = jml;
            if (sat === 'ton') kg = jml * 1000;
            else if (sat === 'kuintal') kg = jml * 100;

            let text = kg.toLocaleString('id-ID') + ' kg';
            if (kg >= 1000) {
                const tonVal = (kg / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 });
                text += ' (' + tonVal + ' ton)';
            }

            document.getElementById('display_konversi_kg').innerText = text;
            document.querySelectorAll('.label_satuan_display').forEach(el => el.innerText = sat);
            hitungsisa();
        }

        function hitungsisa() {
            const prodEl = document.getElementById('prod_jumlah_produksi');
            if (!prodEl) return;
            const prod = parseFloat(prodEl.value) || 0;
            const jual = parseFloat(document.getElementById('prod_jumlah_dijual')?.value) || 0;
            const kons = parseFloat(document.getElementById('prod_jumlah_dikonsumsi_sendiri')?.value) || 0;
            const sat = document.getElementById('prod_satuan')?.value || 'kg';
            const sisa = prod - (jual + kons);

            const displayEl = document.getElementById('prod_sisa_display');
            if (displayEl) {
                displayEl.value = Math.max(0, sisa).toLocaleString('id-ID') + ' ' + sat;
            }

            const warn = document.getElementById('warning_overload');
            if (warn) {
                if ((jual + kons) > prod && prod > 0) {
                    warn.classList.remove('hidden');
                } else {
                    warn.classList.add('hidden');
                }
            }
        }

        function resetCheckboxes(cls) {
            document.querySelectorAll('.' + cls).forEach(chk => chk.checked = false);
        }

        function setCheckboxes(cls, arr) {
            resetCheckboxes(cls);
            if (Array.isArray(arr)) {
                document.querySelectorAll('.' + cls).forEach(chk => {
                    if (arr.includes(chk.value)) chk.checked = true;
                });
            }
        }

        // Close modals on Escape key press
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('modalNarasumber');
                closeModal('modalProduksi');
            }
        });
    </script>
</x-layouts.app>
