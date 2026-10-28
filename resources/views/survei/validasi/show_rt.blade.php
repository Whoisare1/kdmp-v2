<x-layouts.app title="Detail Responden RT" eyebrow="Manajemen Survei">

    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink-900">Detail Responden {{ $rtDesa->nama_rt }} / {{ $rtDesa->rw ?? '-' }}</h1>
            <p class="text-sm text-ink-500">{{ str_starts_with(strtolower($rtDesa->wilayah->nama ?? ''), 'desa') ? $rtDesa->wilayah->nama : 'Desa ' . ($rtDesa->wilayah->nama ?? '-') }} {{ $rtDesa->dusun ? ' - Dusun '.$rtDesa->dusun : '' }}</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('survei.validasi.index', ['bulan' => $rtDesa->sesi->bulan, 'tahun' => $rtDesa->sesi->tahun]) }}"
               class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-semibold text-ink-700 shadow-sm hover:bg-paper-100 whitespace-nowrap">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center gap-2 shadow-xs">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-paper-300 p-5">
            <p class="text-sm font-medium text-ink-500 mb-1">Ketua RT</p>
            <p class="text-lg font-bold text-ink-900">{{ $rtDesa->nama_ketua_rt ?? '-' }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-paper-300 p-5">
            <p class="text-sm font-medium text-ink-500 mb-1">Target Baseline</p>
            <p class="text-lg font-bold text-ink-900">{{ $rtDesa->total_jiwa_baseline }} Jiwa</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-paper-300 p-5">
            <p class="text-sm font-medium text-ink-500 mb-1">Data Survei Masuk</p>
            <p class="text-lg font-bold text-ink-900">{{ $masyarakatDesaList->sum(fn($m) => $m->anggota->count() > 0 ? $m->anggota->count() : 1) }} Jiwa</p>
        </div>
    </div>

    <h2 class="text-lg font-bold text-ink-900 mb-4">Daftar Formulir Responden</h2>

    <div class="space-y-6">
        @forelse($masyarakatDesaList as $m)
            @php
                $totalJiwa = $m->anggota->count() > 0 ? $m->anggota->count() : 1;
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-paper-300 overflow-hidden">
                <div class="bg-paper-50 px-6 py-4 border-b border-paper-300 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h3 class="font-bold text-ink-900 text-lg">{{ $m->nama_kepala_keluarga ?: 'Kepala Keluarga' }}</h3>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink-500 mt-1">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                {{ $m->nomor_hp ?? '-' }}
                            </span>
                            <span>📍 {{ $m->desa ?? '-' }}, RT {{ $m->rt ?? '-' }} / RW {{ $m->rw ?? '-' }}@if($m->dusun) (Dusun {{ $m->dusun }})@endif</span>
                            @if($m->nama_jalan || $m->nomor_rumah || $m->detail_alamat)
                                <span>• {{ $m->nama_jalan }} {{ $m->nomor_rumah }} {{ $m->detail_alamat }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-left sm:text-right text-xs">
                            <span class="text-ink-400 block">Waktu Submit</span>
                            <span class="font-medium text-ink-700">{{ $m->created_at->format('d M Y, H:i') }} WIB</span>
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800">
                                Total {{ $totalJiwa }} Jiwa
                            </span>
                        </div>
                        <form method="POST" action="{{ route('survei.validasi.masyarakat.destroy', $m->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data responden {{ $m->nama_kepala_keluarga }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-ink-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Hapus Data Responden">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Anggota Keluarga (Termasuk Kepala Keluarga) -->
                    <div>
                        <h4 class="font-semibold text-ink-800 mb-3 flex items-center justify-between border-b border-paper-200 pb-2">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Anggota & Jiwa Keluarga
                            </span>
                            <span class="text-xs text-ink-500 font-normal">{{ $totalJiwa }} Jiwa terdaftar</span>
                        </h4>
                        @php
                            $getKatUsia = function($umur) {
                                $u = (int) $umur;
                                if ($u <= 4) return 'Balita';
                                if ($u <= 9) return 'Anak-anak';
                                if ($u <= 19) return 'Remaja';
                                if ($u <= 59) return 'Dewasa';
                                return 'Lansia';
                            };
                        @endphp
                        <table class="w-full text-sm text-left text-ink-600">
                            <thead class="text-xs uppercase bg-paper-50 text-ink-500">
                                <tr>
                                    <th class="px-3 py-2 rounded-tl-md">Nama / Status</th>
                                    <th class="px-3 py-2">Umur</th>
                                    <th class="px-3 py-2">Kategori Usia</th>
                                    <th class="px-3 py-2 rounded-tr-md">Jenis Kelamin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-paper-200">
                                @if($m->anggota->count() > 0)
                                    @foreach($m->anggota as $ang)
                                    <tr class="{{ $loop->first ? 'bg-blue-50/30' : '' }}">
                                        <td class="px-3 py-2">
                                            <div class="font-semibold text-ink-900">{{ $ang->nama ?: ($loop->first ? ($m->nama_kepala_keluarga ?: 'Kepala Keluarga') : 'Anggota Keluarga') }}</div>
                                            @if($loop->first)
                                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800">Kepala Keluarga</span>
                                            @else
                                                <span class="text-[10px] text-ink-400">Anggota</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">{{ $ang->umur ? $ang->umur . ' Tahun' : '-' }}</td>
                                        <td class="px-3 py-2 font-medium text-ink-700">{{ $ang->umur ? $getKatUsia($ang->umur) : '-' }}</td>
                                        <td class="px-3 py-2">{{ $ang->jenis_kelamin ?: '-' }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr class="bg-blue-50/30">
                                        <td class="px-3 py-2">
                                            <div class="font-semibold text-ink-900">{{ $m->nama_kepala_keluarga ?: 'Kepala Keluarga' }}</div>
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800">Kepala Keluarga</span>
                                        </td>
                                        <td class="px-3 py-2">{{ $m->umur ? $m->umur . ' Tahun' : '-' }}</td>
                                        <td class="px-3 py-2 font-medium text-ink-700">{{ $m->umur ? $getKatUsia($m->umur) : '-' }}</td>
                                        <td class="px-3 py-2">{{ $m->jenis_kelamin ?: '-' }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Konsumsi Riil -->
                    <div>
                        <h4 class="font-semibold text-ink-800 mb-3 flex items-center justify-between border-b border-paper-200 pb-2">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Data Konsumsi Komoditas
                            </span>
                            <span class="text-xs text-ink-500 font-normal">{{ $m->konsumsi->count() }} Komoditas</span>
                        </h4>
                        @if($m->konsumsi->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-ink-600">
                                    <thead class="text-xs uppercase bg-paper-50 text-ink-500">
                                        <tr>
                                            <th class="px-3 py-2 rounded-tl-md">Komoditas</th>
                                            <th class="px-3 py-2">Jumlah / Bln</th>
                                            <th class="px-3 py-2">Harga</th>
                                            <th class="px-3 py-2 rounded-tr-md">Sumber & Lokasi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-paper-200">
                                        @foreach($m->konsumsi as $kon)
                                        @php
                                            $subtotal = 0;
                                            if ($kon->harga) {
                                                $subtotal = ($kon->tipe_harga === 'Satuan' && $kon->jumlah) 
                                                    ? ($kon->harga * $kon->jumlah) 
                                                    : $kon->harga;
                                            }
                                        @endphp
                                        <tr class="hover:bg-paper-50/50">
                                            <td class="px-3 py-2.5 font-medium text-ink-900">{{ $kon->nama_komoditas }}</td>
                                            <td class="px-3 py-2.5 font-semibold text-ink-800 whitespace-nowrap">
                                                {{ $kon->jumlah }} {{ $kon->satuan }}
                                            </td>
                                            <td class="px-3 py-2.5 whitespace-nowrap">
                                                @if($kon->harga)
                                                    <span class="font-medium text-ink-800">Rp {{ number_format($kon->harga, 0, ',', '.') }}</span>
                                                    <span class="text-[10px] text-ink-500 block">
                                                        {{ $kon->tipe_harga === 'Satuan' ? 'per ' . $kon->satuan : 'total sebulan' }}
                                                    </span>
                                                    @if($kon->tipe_harga === 'Satuan' && $kon->jumlah)
                                                        <span class="text-[11px] font-semibold text-emerald-600 block mt-0.5">
                                                            ≈ Rp {{ number_format($subtotal, 0, ',', '.') }} / bln
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-xs text-ink-400 italic">-</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 text-xs">
                                                @if($kon->sumber_pemenuhan)
                                                    <span class="inline-block bg-paper-100 text-ink-700 px-2 py-0.5 rounded font-medium text-[11px]">{{ $kon->sumber_pemenuhan }}</span>
                                                @endif
                                                @if($kon->lokasi_pemenuhan)
                                                    <span class="block text-ink-500 text-[11px] mt-1 flex items-center gap-1">
                                                        <span>📍</span> {{ $kon->lokasi_pemenuhan }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-ink-400 italic py-2">Tidak ada data konsumsi komoditas.</p>
                        @endif
                    </div>

                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg p-8 text-center text-ink-500 border border-paper-300">
                Belum ada formulir masyarakat yang masuk untuk wilayah ini.
            </div>
        @endforelse
    </div>

</x-layouts.app>
