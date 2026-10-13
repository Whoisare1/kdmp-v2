<x-layouts.app title="Laporan Arus Kas" eyebrow="Laporan Keuangan">

    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-semibold text-ink-900">Laporan Arus Kas (Metode Tidak Langsung)</h1>
            <p class="mt-1 text-sm text-ink-600">
                Laporan ini menyajikan informasi perubahan historis atas kas dan setara kas entitas.
            </p>
        </div>
        
        <form method="GET" action="{{ route('akuntansi.laporan.arus-kas') }}" class="flex items-end gap-3 rounded border border-paper-200 bg-white p-3 shadow-sm">
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-600">Bulan</label>
                <select name="bulan" class="rounded border border-paper-300 py-1.5 pl-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                    @foreach(range(1, 12) as $b)
                        <option value="{{ $b }}" @selected($b == $bulan)>
                            {{ date('F', mktime(0, 0, 0, $b, 10)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-600">Tahun</label>
                <select name="tahun" class="rounded border border-paper-300 py-1.5 pl-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
                    @foreach(range(date('Y')-2, date('Y')) as $t)
                        <option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded bg-paper-200 px-4 py-1.5 text-sm font-medium text-ink-700 hover:bg-paper-300 transition">
                Tampilkan
            </button>
        </form>
    </div>

    <div class="overflow-hidden rounded border border-paper-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-paper-200 bg-paper-100">
                    <th class="px-6 py-3 text-left font-semibold text-ink-900" colspan="2">Uraian Arus Kas</th>
                    <th class="px-6 py-3 text-right font-semibold text-ink-900 w-48">Nilai (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                
                {{-- ARUS KAS DARI AKTIVITAS OPERASI --}}
                <tr class="bg-paper-50">
                    <td colspan="3" class="px-6 py-3 font-semibold text-ink-900">Arus Kas dari Aktivitas Operasi</td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-2.5 text-ink-700">Laba Bersih</td>
                    <td class="px-6 py-2.5 text-right font-mono font-medium {{ $labaBersih < 0 ? 'text-merah-600' : 'text-ink-900' }}">
                        {{ number_format($labaBersih, 2, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-2.5 text-ink-700 italic">Penyesuaian untuk merekonsiliasi laba bersih menjadi kas bersih:</td>
                    <td class="px-6 py-2.5"></td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-2 pl-8 text-ink-700">Beban Penyusutan Aset Tetap</td>
                    <td class="px-6 py-2 text-right font-mono {{ $penyusutan < 0 ? 'text-merah-600' : 'text-ink-600' }}">
                        {{ number_format($penyusutan, 2, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-2 pl-8 text-ink-700">(Peningkatan) / Penurunan Piutang</td>
                    <td class="px-6 py-2 text-right font-mono {{ $perubahanPiutang < 0 ? 'text-merah-600' : 'text-ink-600' }}">
                        {{ number_format($perubahanPiutang, 2, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-2 pl-8 text-ink-700">(Peningkatan) / Penurunan Persediaan</td>
                    <td class="px-6 py-2 text-right font-mono {{ $perubahanPersediaan < 0 ? 'text-merah-600' : 'text-ink-600' }}">
                        {{ number_format($perubahanPersediaan, 2, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-2 pl-8 text-ink-700">Peningkatan / (Penurunan) Hutang Lancar</td>
                    <td class="px-6 py-2 text-right font-mono {{ $perubahanHutangLancar < 0 ? 'text-merah-600' : 'text-ink-600' }}">
                        {{ number_format($perubahanHutangLancar, 2, ',', '.') }}
                    </td>
                </tr>
                <tr class="border-t-2 border-paper-200">
                    <td class="w-8"></td>
                    <td class="px-4 py-3 font-semibold text-ink-900">Kas Bersih Disediakan dari Aktivitas Operasi</td>
                    <td class="px-6 py-3 text-right font-mono font-bold {{ $arusKasOperasi < 0 ? 'text-merah-600' : 'text-ink-900' }}">
                        {{ number_format($arusKasOperasi, 2, ',', '.') }}
                    </td>
                </tr>

                {{-- ARUS KAS DARI AKTIVITAS INVESTASI --}}
                <tr class="bg-paper-50 mt-4 border-t border-paper-200">
                    <td colspan="3" class="px-6 py-3 font-semibold text-ink-900">Arus Kas dari Aktivitas Investasi</td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-2 text-ink-700">Perolehan / (Pelepasan) Aset Tetap</td>
                    <td class="px-6 py-2 text-right font-mono {{ $perubahanAsetTetap < 0 ? 'text-merah-600' : 'text-ink-600' }}">
                        {{ number_format($perubahanAsetTetap, 2, ',', '.') }}
                    </td>
                </tr>
                <tr class="border-t-2 border-paper-200">
                    <td class="w-8"></td>
                    <td class="px-4 py-3 font-semibold text-ink-900">Kas Bersih Digunakan untuk Aktivitas Investasi</td>
                    <td class="px-6 py-3 text-right font-mono font-bold {{ $arusKasInvestasi < 0 ? 'text-merah-600' : 'text-ink-900' }}">
                        {{ number_format($arusKasInvestasi, 2, ',', '.') }}
                    </td>
                </tr>

                {{-- ARUS KAS DARI AKTIVITAS PENDANAAN --}}
                <tr class="bg-paper-50 mt-4 border-t border-paper-200">
                    <td colspan="3" class="px-6 py-3 font-semibold text-ink-900">Arus Kas dari Aktivitas Pendanaan</td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-2 text-ink-700">Penerimaan / (Pembayaran) Hutang Jangka Panjang</td>
                    <td class="px-6 py-2 text-right font-mono {{ $perubahanHutangPanjang < 0 ? 'text-merah-600' : 'text-ink-600' }}">
                        {{ number_format($perubahanHutangPanjang, 2, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-2 text-ink-700">Setoran Modal / (Prive)</td>
                    <td class="px-6 py-2 text-right font-mono {{ $perubahanModal < 0 ? 'text-merah-600' : 'text-ink-600' }}">
                        {{ number_format($perubahanModal, 2, ',', '.') }}
                    </td>
                </tr>
                <tr class="border-t-2 border-paper-200">
                    <td class="w-8"></td>
                    <td class="px-4 py-3 font-semibold text-ink-900">Kas Bersih Disediakan dari Aktivitas Pendanaan</td>
                    <td class="px-6 py-3 text-right font-mono font-bold {{ $arusKasPendanaan < 0 ? 'text-merah-600' : 'text-ink-900' }}">
                        {{ number_format($arusKasPendanaan, 2, ',', '.') }}
                    </td>
                </tr>

                {{-- SUMMARY KAS --}}
                <tr class="bg-paper-100 border-t-2 border-paper-300">
                    <td class="w-8"></td>
                    <td class="px-4 py-4 font-bold text-ink-900 uppercase">Kenaikan / (Penurunan) Kas Bersih</td>
                    <td class="px-6 py-4 text-right font-mono font-bold text-lg {{ $netCashFlow < 0 ? 'text-merah-600' : 'text-ink-900' }}">
                        {{ number_format($netCashFlow, 2, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="w-8"></td>
                    <td class="px-4 py-3 font-semibold text-ink-700">Saldo Kas Awal Periode</td>
                    <td class="px-6 py-3 text-right font-mono font-semibold text-ink-700">
                        {{ number_format($kasAwal, 2, ',', '.') }}
                    </td>
                </tr>
                <tr class="border-t-4 border-double border-paper-300 bg-sawah-50">
                    <td class="w-8"></td>
                    <td class="px-4 py-4 font-bold text-ink-900 uppercase">Saldo Kas Akhir Periode</td>
                    <td class="px-6 py-4 text-right font-mono font-bold text-xl text-ink-900">
                        {{ number_format($kasAkhir, 2, ',', '.') }}
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
</x-layouts.app>
