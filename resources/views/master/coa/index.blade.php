<x-layouts.app :title="$title" eyebrow="M0 Master">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full gap-2 sm:max-w-md">
            <input name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama rekening..." class="min-w-0 flex-1 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
            <button class="rounded-sm border border-paper-300 px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">Cari</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-paper-100 text-left text-ink-700">
                    <tr>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Kode Rekening</th>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Nama Rekening</th>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Kelompok</th>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Tipe</th>
                        <th class="border-b border-paper-300 px-4 py-3 font-medium">Saldo Normal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse($items as $item)
                        <tr class="hover:bg-paper-100/50">
                            <td class="px-4 py-3 font-mono text-ink-900 {{ $item->is_transaction === 'F' ? 'font-bold' : '' }}">
                                {{ $item->kode_anak }}
                            </td>
                            <td class="px-4 py-3 text-ink-900 {{ $item->is_transaction === 'F' ? 'font-bold' : '' }}" style="padding-left: {{ ($item->level - 1) * 1.5 + 1 }}rem;">
                                {{ $item->nama_rekening }}
                            </td>
                            <td class="px-4 py-3 text-ink-700">{{ $item->kelompok }}</td>
                            <td class="px-4 py-3">
                                @if($item->is_transaction === 'F')
                                    <span class="inline-flex items-center rounded-sm bg-paper-200 px-2 py-0.5 text-xs font-medium text-ink-800">
                                        Header
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-sm bg-sawah-100 px-2 py-0.5 text-xs font-medium text-sawah-800">
                                        Transaksi
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($item->posisi_normal === 'D')
                                    <span class="inline-flex items-center rounded-sm border border-merah-200 bg-merah-50 px-2 py-0.5 text-xs font-medium text-merah-700">
                                        Debet
                                    </span>
                                @elseif($item->posisi_normal === 'K')
                                    <span class="inline-flex items-center rounded-sm border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                        Kredit
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-ink-500">
                                @if(request('q'))
                                    Tidak ada rekening yang cocok dengan pencarian "{{ request('q') }}".
                                @else
                                    Belum ada data Chart of Accounts (COA).
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
