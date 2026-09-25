<x-layouts.app title="Validasi & Laporan Survei" eyebrow="Manajemen Survei">


    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-ink-500">Perbandingan Data Baseline Kependudukan RT vs Survei Real Masyarakat.</p>
        </div>

        <div class="flex items-center gap-3">
            <form id="filterForm" method="GET" action="{{ route('survei.validasi.index') }}" class="flex items-center gap-2">
                <select name="bulan" onchange="document.getElementById('filterForm').submit()" class="rounded-sm border border-paper-300 text-sm py-1.5 px-3">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                        </option>
                    @endforeach
                </select>
                <select name="tahun" onchange="document.getElementById('filterForm').submit()" class="rounded-sm border border-paper-300 text-sm py-1.5 px-3">
                    @php
                        $years = $availablePeriods->pluck('tahun')->unique()->sortDesc();
                        if($years->isEmpty()) $years = collect([date('Y')]);
                    @endphp
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('survei.dashboard.index') }}"
               class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-semibold text-ink-700 shadow-sm hover:bg-paper-100 whitespace-nowrap">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    <div class="mt-8 overflow-hidden rounded-sm border border-paper-300 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ink-500">
                <thead class="border-b border-paper-300 bg-paper-50 text-xs uppercase text-ink-700">
                    <tr>
                        <th class="px-6 py-4 font-medium" rowspan="2">Desa / Wilayah</th>
                        <th class="px-6 py-4 font-medium" rowspan="2">Nama RT</th>
                        <th class="border-b border-paper-300 px-6 py-2 text-center font-medium" colspan="2">Data Baseline (RT)</th>
                        <th class="border-b border-paper-300 px-6 py-2 text-center font-medium" colspan="2">Data Riil (Masyarakat)</th>
                        <th class="px-6 py-4 font-medium text-center" rowspan="2">Status</th>
                    </tr>
                    <tr>
                        <th class="px-6 py-2 text-center font-medium">KK</th>
                        <th class="px-6 py-2 text-center font-medium">Jiwa</th>
                        <th class="px-6 py-2 text-center font-medium">KK Terdata</th>
                        <th class="px-6 py-2 text-center font-medium">Jiwa Terdata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse($rts as $rt)
                        @php
                            $isValid = ($rt->kk_terdata == $rt->total_kk_baseline) && ($rt->jiwa_terdata == $rt->total_jiwa_baseline);
                            $isWaiting = ($rt->kk_terdata == 0 && $rt->jiwa_terdata == 0);
                        @endphp
                        <tr class="hover:bg-paper-50">
                            <td class="px-6 py-4 font-medium text-ink-900">{{ $rt->wilayah->nama ?? '-' }}</td>
                            <td class="px-6 py-4">
                                {{ $rt->nama_rt }} / {{ $rt->rw ?? '-' }}
                                @if($rt->dusun)
                                    <br><span class="text-xs text-ink-400">Dusun: {{ $rt->dusun }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-ink-700">{{ $rt->total_kk_baseline }}</td>
                            <td class="px-6 py-4 text-center font-semibold text-ink-700">{{ $rt->total_jiwa_baseline }}</td>
                            <td class="px-6 py-4 text-center font-semibold {{ $rt->kk_terdata != $rt->total_kk_baseline ? 'text-red-600' : 'text-green-600' }}">{{ $rt->kk_terdata }}</td>
                            <td class="px-6 py-4 text-center font-semibold {{ $rt->jiwa_terdata != $rt->total_jiwa_baseline ? 'text-red-600' : 'text-green-600' }}">{{ $rt->jiwa_terdata }}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    @if($isWaiting)
                                        <span class="rounded-sm bg-yellow-50 px-2 py-1 text-xs font-semibold text-yellow-600">Menunggu Data</span>
                                    @elseif($isValid)
                                        <span class="rounded-sm bg-green-50 px-2 py-1 text-xs font-semibold text-green-600">Valid</span>
                                    @else
                                        <span class="rounded-sm bg-red-50 px-2 py-1 text-xs font-semibold text-red-600">Selisih</span>
                                    @endif
                                    
                                    @if($rt->masyarakat_list->count() > 0)
                                        <a href="{{ route('survei.validasi.rt.show', ['id_sesi' => $rt->id_sesi, 'nama_rt' => $rt->nama_rt, 'rw' => $rt->rw]) }}" class="text-xs text-blue-600 hover:text-blue-800 underline mt-1 block">
                                            Lihat Detail
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        


                    @empty
                        <tr class="hover:bg-paper-50">
                            <td colspan="7" class="px-6 py-8 text-center text-ink-500">
                                Belum ada data RT yang terdaftar untuk dibandingkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-paper-300 bg-paper-50 px-6 py-4 text-sm text-ink-500">
            * Halaman ini akan secara otomatis membandingkan data yang diinput oleh Ketua RT dengan jumlah keluarga yang terdata dari link Masyarakat.
        </div>
    </div>
    
    </div>
</x-layouts.app>
