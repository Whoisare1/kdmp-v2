<x-layouts.app title="Detail Responden RT" eyebrow="Manajemen Survei">

    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink-900">Detail Responden {{ $rtDesa->nama_rt }} / {{ $rtDesa->rw ?? '-' }}</h1>
            <p class="text-sm text-ink-500">Desa {{ $rtDesa->wilayah->nama ?? '-' }} {{ $rtDesa->dusun ? ' - Dusun '.$rtDesa->dusun : '' }}</p>
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-paper-300 p-5">
            <p class="text-sm font-medium text-ink-500 mb-1">Ketua RT</p>
            <p class="text-lg font-bold text-ink-900">{{ $rtDesa->nama_ketua_rt ?? '-' }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-paper-300 p-5">
            <p class="text-sm font-medium text-ink-500 mb-1">Target Baseline</p>
            <p class="text-lg font-bold text-ink-900">{{ $rtDesa->total_kk_baseline }} KK / {{ $rtDesa->total_jiwa_baseline }} Jiwa</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-paper-300 p-5">
            <p class="text-sm font-medium text-ink-500 mb-1">Data Survei Masuk</p>
            <p class="text-lg font-bold text-ink-900">{{ $masyarakatDesaList->count() }} KK Terdata</p>
        </div>
    </div>

    <h2 class="text-lg font-bold text-ink-900 mb-4">Daftar Formulir Responden</h2>

    <div class="space-y-6">
        @forelse($masyarakatDesaList as $m)
            <div class="bg-white rounded-xl shadow-sm border border-paper-300 overflow-hidden">
                <div class="bg-paper-50 px-6 py-4 border-b border-paper-300 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-ink-900 text-lg">{{ $m->nama_kepala_keluarga }}</h3>
                        <p class="text-sm text-ink-500 flex items-center gap-2 mt-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            {{ $m->nomor_hp ?? '-' }}
                        </p>
                    </div>
                    <div class="text-right text-sm">
                        <p class="font-medium text-ink-700">Tanggal Pengisian</p>
                        <p class="text-ink-500">{{ $m->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Anggota Keluarga -->
                    <div>
                        <h4 class="font-semibold text-ink-800 mb-3 flex items-center gap-2 border-b border-paper-200 pb-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Anggota Keluarga ({{ $m->anggota->count() }} Jiwa)
                        </h4>
                        @if($m->anggota->count() > 0)
                            <table class="w-full text-sm text-left text-ink-600">
                                <thead class="text-xs uppercase bg-paper-50 text-ink-500">
                                    <tr>
                                        <th class="px-3 py-2 rounded-tl-md">Umur</th>
                                        <th class="px-3 py-2">Kategori Usia</th>
                                        <th class="px-3 py-2 rounded-tr-md">Jenis Kelamin</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-paper-200">
                                    @foreach($m->anggota as $ang)
                                    @php
                                        $umur = (int) $ang->umur;
                                        if ($umur <= 4) $kat = 'Balita';
                                        elseif ($umur <= 9) $kat = 'Anak-anak';
                                        elseif ($umur <= 19) $kat = 'Remaja';
                                        elseif ($umur <= 59) $kat = 'Dewasa';
                                        else $kat = 'Lansia';
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2">{{ $ang->umur }} Tahun</td>
                                        <td class="px-3 py-2 font-medium text-ink-700">{{ $kat }}</td>
                                        <td class="px-3 py-2">{{ $ang->jenis_kelamin }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-sm text-ink-400 italic">Tidak ada data anggota keluarga.</p>
                        @endif
                    </div>

                    <!-- Konsumsi -->
                    <div>
                        <h4 class="font-semibold text-ink-800 mb-3 flex items-center gap-2 border-b border-paper-200 pb-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Data Konsumsi ({{ $m->konsumsi->count() }} Item)
                        </h4>
                        @if($m->konsumsi->count() > 0)
                            <table class="w-full text-sm text-left text-ink-600">
                                <thead class="text-xs uppercase bg-paper-50 text-ink-500">
                                    <tr>
                                        <th class="px-3 py-2 rounded-tl-md">Komoditas</th>
                                        <th class="px-3 py-2">Jumlah</th>
                                        <th class="px-3 py-2 rounded-tr-md">Sumber</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-paper-200">
                                    @foreach($m->konsumsi as $kon)
                                    <tr>
                                        <td class="px-3 py-2 font-medium">{{ $kon->nama_komoditas }}</td>
                                        <td class="px-3 py-2">{{ $kon->jumlah_konsumsi }} {{ $kon->satuan }}</td>
                                        <td class="px-3 py-2 text-xs">
                                            <span class="bg-paper-100 px-2 py-1 rounded">{{ $kon->sumber_pemenuhan }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-sm text-ink-400 italic">Tidak ada data konsumsi.</p>
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
