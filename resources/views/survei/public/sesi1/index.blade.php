<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Sesi 1 Demografi – {{ $sesi->wilayah->nama ?? 'Wilayah' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper-100 font-sans text-ink-900 antialiased min-h-screen pb-16">

    {{-- Header --}}
    <header class="bg-merah-600 text-white shadow-md px-4 py-4 sticky top-0 z-10">
            @php
                $wil = $sesi->wilayah;
                $kec = $wil?->parent;
                $kab = $kec?->parent;
                $petugasNama = $sesi->petugas->nama ?? 'Sistem';
                $st1 = $sesi->status_sesi1 ?? 'belum_diisi';
                $st1Label = match($st1) {
                    'sedang_diisi'  => 'Sedang Diisi',
                    'selesai'       => 'Selesai ✓',
                    'terverifikasi' => 'Terverifikasi ✓',
                    default         => 'Belum Diisi',
                };
            @endphp
            <div class="flex items-center justify-between mb-1">
                <a href="{{ route('survei.public.show', $token) }}"
                   class="text-xs opacity-80 hover:opacity-100">
                    ← Kembali ke Modul
                </a>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $st1 === 'selesai' || $st1 === 'terverifikasi' ? 'bg-green-200 text-green-900' : 'bg-yellow-200 text-yellow-900' }}">
                    Status: {{ $st1Label }}
                </span>
            </div>
            <h1 class="font-bold text-base leading-tight">Sesi 1 — Data Demografi Desa</h1>
            <div class="text-xs opacity-90 pt-1 mt-1 border-t border-white/20">
                <div>Desa {{ $wil->nama ?? '' }}, Kec. {{ $kec->nama ?? '-' }}, {{ $kab->nama ?? '-' }}</div>
                <div>Periode: {{ $sesi->bulan }}/{{ $sesi->tahun }} &bull; Petugas: {{ $petugasNama }}</div>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 mt-5 space-y-4">

        {{-- Flash --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm flex items-start gap-3 shadow-sm">
                <svg style="width:18px;height:18px;flex-shrink:0;margin-top:1px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Daftar Narasumber --}}
        <div class="bg-white rounded-xl border border-paper-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-paper-100">
                <h2 class="font-semibold text-sm text-ink-900">Daftar Narasumber</h2>
                <button type="button"
                        onclick="document.getElementById('modal-tambah').style.display='flex'"
                        class="flex items-center gap-1 rounded-lg bg-merah-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-merah-700">
                    <svg style="width:12px;height:12px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah
                </button>
            </div>

            @if($narasumbers->isEmpty())
                <div class="px-4 py-8 text-center text-sm text-ink-400">
                    Belum ada narasumber. Tap <strong>Tambah</strong> untuk memulai.
                </div>
            @else
                <div class="divide-y divide-paper-100">
                    @foreach($narasumbers as $ns)
                        @php
                            $nsSt = $ns->status;
                            $badge = match($nsSt) {
                                'draft'         => ['bg:#fef9c3;color:#854d0e', 'Draft'],
                                'lengkap'       => ['bg:#dcfce7;color:#166534', 'Lengkap'],
                                'terverifikasi' => ['bg:#dbeafe;color:#1e40af', 'Terverifikasi'],
                                default         => ['bg:#f3f4f6;color:#6b7280', 'Belum Diisi'],
                            };
                            $nsKK = $kkPerNarasumber[$ns->id_narasumber] ?? 0;
                        @endphp
                        <div class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm text-ink-900 truncate">{{ $ns->nama_narasumber }}</p>
                                    <p class="text-xs text-ink-500 mt-0.5">{{ $ns->kategori }}
                                        @if($nsSt !== 'belum_diisi') &bull; {{ $nsKK }} KK @endif
                                    </p>
                                </div>
                                <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold" style="{{ $badge[0] }}">
                                    {{ $badge[1] }}
                                </span>
                            </div>
                            <div class="flex gap-2 mt-2.5">
                                <a href="{{ route('survei.public.sesi1.narasumber.show', [$token, $ns->id_narasumber]) }}"
                                   class="flex-1 text-center rounded-lg border border-paper-300 py-1.5 text-xs font-medium text-ink-700 hover:bg-paper-100">
                                    {{ $nsSt === 'belum_diisi' ? 'Isi Data' : 'Ubah Data' }}
                                </a>
                                <button type="button"
                                        onclick="bukaModalEdit({{ json_encode($ns) }}, '{{ route('survei.public.sesi1.narasumber.update', [$token, $ns->id_narasumber]) }}')"
                                        class="rounded-lg border border-paper-300 px-2.5 py-1.5 text-xs text-ink-700 hover:bg-paper-100 flex items-center justify-center"
                                        title="Edit Info Narasumber">
                                    <svg style="width:13px;height:13px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                                <form method="POST"
                                      action="{{ route('survei.public.sesi1.narasumber.destroy', [$token, $ns->id_narasumber]) }}"
                                      onsubmit="return confirm('Hapus narasumber ini beserta data demografinya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="rounded-lg border border-red-200 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50">
                                        <svg style="width:13px;height:13px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Rekap Demografi --}}
        @php
            $totalLaki = collect($rekap)->sum('laki');
            $totalPerempuan = collect($rekap)->sum('perempuan');
            $totalPenduduk = $totalLaki + $totalPerempuan;
        @endphp
        @if($totalPenduduk > 0)
        <div class="bg-white rounded-xl border border-paper-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-paper-100">
                <h2 class="font-semibold text-sm text-ink-900">Rekap Demografi Desa</h2>
                <p class="text-xs text-ink-500 mt-0.5">Total dari {{ $narasumbers->whereNotIn('status', ['belum_diisi'])->count() }} narasumber</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-paper-50 border-b border-paper-100">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-ink-500">Kelompok</th>
                            <th class="px-3 py-2 text-right font-semibold text-ink-500">L</th>
                            <th class="px-3 py-2 text-right font-semibold text-ink-500">P</th>
                            <th class="px-3 py-2 text-right font-semibold text-ink-500">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-paper-50">
                        @foreach($kelompokUmur as $ku)
                            @php $r = $rekap[$ku] @endphp
                            <tr>
                                <td class="px-4 py-2 font-medium text-ink-800">{{ $ku }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-600">{{ number_format($r['laki']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-600">{{ number_format($r['perempuan']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums font-semibold text-ink-900">{{ number_format($r['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-paper-200 bg-paper-50">
                        <tr>
                            <td class="px-4 py-2 font-bold text-ink-900">Total</td>
                            <td class="px-3 py-2 text-right tabular-nums font-bold text-ink-900">{{ number_format($totalLaki) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums font-bold text-ink-900">{{ number_format($totalPerempuan) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums font-bold text-merah-600">{{ number_format($totalPenduduk) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="px-4 py-2.5 border-t border-paper-100 flex justify-between items-center">
                <span class="text-xs text-ink-500">Total KK</span>
                <span class="text-sm font-bold tabular-nums text-ink-900">{{ number_format($totalKK) }} KK</span>
            </div>
        </div>
        @endif

        {{-- Selesaikan --}}
        @if(!$narasumbers->isEmpty())
        <div class="bg-white rounded-xl border border-paper-200 shadow-sm p-4">
            @if($sesi->status_sesi1 === 'selesai' || $sesi->status_sesi1 === 'terverifikasi')
                <div class="text-center py-2">
                    <p class="text-sm font-semibold text-green-700">✓ Sesi 1 sudah diselesaikan</p>
                    <p class="text-xs text-ink-400 mt-1">Data dapat dilihat oleh admin untuk verifikasi.</p>
                </div>
            @else
                <p class="text-xs text-ink-500 mb-3">
                    Setelah semua narasumber terisi, selesaikan Sesi 1 ini.
                </p>
                <button type="button"
                        onclick="document.getElementById('modal-selesai').style.display='flex'"
                        class="w-full rounded-xl py-3 text-sm font-semibold text-white"
                        style="background:#16a34a">
                    Selesaikan Sesi 1
                </button>
            @endif
        </div>
        @endif

    </main>

    {{-- Modal Tambah Narasumber --}}
    <div id="modal-tambah"
         style="display:none;position:fixed;inset:0;z-index:50;align-items:flex-end;background:rgba(0,0,0,0.5)"
         onclick="if(event.target===this)this.style.display='none'">
        <div class="w-full max-w-lg mx-auto bg-white rounded-t-2xl shadow-xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-5 py-4 border-b border-paper-100">
                <h3 class="font-semibold text-ink-900">Tambah Narasumber</h3>
                <button type="button" onclick="document.getElementById('modal-tambah').style.display='none'"
                        class="text-ink-400 text-2xl leading-none">&times;</button>
            </div>
            <form method="POST" action="{{ route('survei.public.sesi1.narasumber.store', $token) }}"
                  class="px-5 py-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Nama Narasumber *</label>
                    <div class="relative">
                        <input type="text" id="add_nama_narasumber" name="nama_narasumber" required maxlength="100"
                               placeholder="Contoh: Budi Santoso"
                               class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm pr-10 focus:border-merah-500 focus:outline-none">
                        <button type="button" onclick="startDictation('add_nama_narasumber', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Kategori *</label>
                    <select name="kategori" required
                            class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm focus:border-merah-500 focus:outline-none">
                        <option value="">-- Pilih --</option>
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat }}">{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Nomor Kontak <span class="font-normal text-ink-400">(opsional)</span></label>
                    <div class="relative">
                        <input type="text" id="add_nomor_kontak" name="nomor_kontak" maxlength="30"
                               placeholder="0812-xxxx-xxxx"
                               class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm pr-10 focus:border-merah-500 focus:outline-none">
                        <button type="button" onclick="startDictation('add_nomor_kontak', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Keterangan <span class="font-normal text-ink-400">(opsional)</span></label>
                    <div class="relative">
                        <input type="text" id="add_keterangan" name="keterangan" maxlength="500"
                               placeholder="Contoh: Ketua RT 01 Dusun Ngroto"
                               class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm pr-10 focus:border-merah-500 focus:outline-none">
                        <button type="button" onclick="startDictation('add_keterangan', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    </div>
                </div>
                <div class="pt-2 pb-2">
                    <button type="submit"
                            class="w-full rounded-xl bg-merah-600 py-3 text-sm font-semibold text-white hover:bg-merah-700">
                        Simpan Narasumber
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Info Narasumber --}}
    <div id="modal-edit"
         style="display:none;position:fixed;inset:0;z-index:50;align-items:flex-end;background:rgba(0,0,0,0.5)"
         onclick="if(event.target===this)this.style.display='none'">
        <div class="w-full max-w-lg mx-auto bg-white rounded-t-2xl shadow-xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-5 py-4 border-b border-paper-100">
                <h3 class="font-semibold text-ink-900">Edit Info Narasumber</h3>
                <button type="button" onclick="document.getElementById('modal-edit').style.display='none'"
                        class="text-ink-400 text-2xl leading-none">&times;</button>
            </div>
            <form id="form-edit-pub" method="POST" action="" class="px-5 py-4 space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Nama Narasumber *</label>
                    <div class="relative">
                        <input type="text" id="pub_edit_nama" name="nama_narasumber" required maxlength="100"
                               class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm pr-10 focus:border-merah-500 focus:outline-none">
                        <button type="button" onclick="startDictation('pub_edit_nama', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Kategori *</label>
                    <select id="pub_edit_kategori" name="kategori" required
                            class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm focus:border-merah-500 focus:outline-none">
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat }}">{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Nomor Kontak <span class="font-normal text-ink-400">(opsional)</span></label>
                    <div class="relative">
                        <input type="text" id="pub_edit_kontak" name="nomor_kontak" maxlength="30"
                               class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm pr-10 focus:border-merah-500 focus:outline-none">
                        <button type="button" onclick="startDictation('pub_edit_kontak', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink-700 mb-1">Keterangan <span class="font-normal text-ink-400">(opsional)</span></label>
                    <div class="relative">
                        <input type="text" id="pub_edit_keterangan" name="keterangan" maxlength="500"
                               class="w-full rounded-lg border border-paper-300 bg-white px-3 py-2.5 text-sm pr-10 focus:border-merah-500 focus:outline-none">
                        <button type="button" onclick="startDictation('pub_edit_keterangan', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400 hover:text-merah-600" title="Gunakan Suara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    </div>
                </div>
                <div class="pt-2 pb-2">
                    <button type="submit"
                            class="w-full rounded-xl bg-merah-600 py-3 text-sm font-semibold text-white hover:bg-merah-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Selesaikan --}}
    <div id="modal-selesai"
         style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);padding:1rem"
         onclick="if(event.target===this)this.style.display='none'">
        <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl p-6 text-center" onclick="event.stopPropagation()">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full" style="background:#f0fdf4">
                <svg style="width:24px;height:24px;color:#16a34a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
                </svg>
            </div>
            <h3 class="font-semibold text-lg text-ink-900 mb-2">Selesaikan Sesi 1?</h3>
            <p class="text-sm text-ink-500 mb-5 leading-relaxed">
                Sesi 1 akan ditandai <strong>Selesai</strong>. Data tetap bisa diperiksa admin.
            </p>
            <form method="POST" action="{{ route('survei.public.sesi1.selesaikan', $token) }}">
                @csrf
                <input type="hidden" name="konfirmasi" value="1">
                <div class="flex gap-3">
                    <button type="button"
                            onclick="document.getElementById('modal-selesai').style.display='none'"
                            class="flex-1 rounded-xl border border-paper-300 py-2.5 text-sm font-medium text-ink-700">
                        Batal
                    </button>
                    <button type="submit"
                            class="flex-1 rounded-xl py-2.5 text-sm font-semibold text-white"
                            style="background:#16a34a">
                        Ya, Selesaikan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalEdit(ns, actionUrl) {
            document.getElementById('form-edit-pub').action = actionUrl;
            document.getElementById('pub_edit_nama').value = ns.nama_narasumber || '';
            document.getElementById('pub_edit_kategori').value = ns.kategori || '';
            document.getElementById('pub_edit_kontak').value = ns.nomor_kontak || '';
            document.getElementById('pub_edit_keterangan').value = ns.keterangan || '';
            document.getElementById('modal-edit').style.display = 'flex';
        }
    </script>
    <x-speech-scripts />
</body>
</html>
