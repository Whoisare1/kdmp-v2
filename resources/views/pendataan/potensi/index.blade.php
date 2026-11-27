<x-layouts.app :title="$title" eyebrow="Modul Pendataan">

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-padi-100 text-padi-800">
                    {{ $desaAktif?->nama ?? 'Desa' }}
                </span>
                <span class="text-xs text-paper-500 font-mono">
                    Kec. {{ $desaAktif?->parent?->nama ?? 'Juwana' }}, Kab. {{ $desaAktif?->parent?->parent?->nama ?? 'Pati' }}
                </span>
            </div>
            <h2 class="text-xl font-display font-semibold text-ink-900 mt-1">Petani Sesuai Komoditas: {{ $desaAktif?->nama ?? 'Desa' }}</h2>
            <p class="text-xs text-paper-500">Daftar petani dipetakan berdasarkan komoditas yang diproduksi dan estimasi hasil panen.</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
            <form method="GET" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                <select name="kategori_id" class="w-full sm:w-auto rounded-lg border border-paper-300 bg-white px-3 py-1.5 text-xs text-ink-800 focus:outline-none">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $k)
                        <option value="{{ $k->id }}" @selected($kategoriId == $k->id)>{{ $k->nama }}</option>
                    @endforeach
                </select>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari petani/item..."
                       class="w-full sm:w-36 rounded-lg border border-paper-300 bg-white px-3 py-1.5 text-xs focus:outline-none">
                <button class="w-full sm:w-auto rounded-lg bg-ink-900 text-white px-3 py-1.5 text-xs font-medium hover:bg-ink-800">Filter</button>
                @if($kategoriId || $search)
                    <a href="{{ route('pendataan.potensi.index') }}" class="text-xs text-paper-500 hover:text-ink-900">Reset</a>
                @endif
            </form>
            <a href="{{ route('pendataan.potensi.create') }}" class="inline-flex justify-center items-center rounded-lg bg-padi-500 hover:bg-padi-600 text-ink-950 font-semibold px-4 py-2 text-xs shadow-sm transition w-full sm:w-auto text-center">
                + Hubungkan Potensi
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-paper-200 bg-white shadow-sm">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-paper-200 bg-paper-50 font-mono text-[11px] uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-4 py-3">Nama Petani</th>
                    <th class="px-4 py-3">Item Komoditas</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3 text-right">Estimasi Panen</th>
                    <th class="px-4 py-3 text-right">Harga Satuan</th>
                    <th class="px-4 py-3 text-right">Total Nilai Panen</th>
                    <th class="px-4 py-3 text-center">Siklus Panen</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @forelse($items as $row)
                    <tr class="hover:bg-paper-50/60 text-xs">
                        <td class="px-4 py-3 font-semibold text-ink-900">
                            <a href="{{ route('pendataan.petani.show', $row->petani) }}" class="text-blue-700 hover:underline">
                                {{ $row->petani?->nama_lengkap ?? '-' }}
                            </a>
                            <span class="block text-[10px] text-paper-400 font-normal">RT {{ $row->petani?->rt }}/RW {{ $row->petani?->rw }}</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-emerald-900">
                            {{ $row->item?->nama ?? '-' }}
                            <span class="block text-[10px] text-paper-400">Master: {{ $row->item?->masterKomoditas?->nama }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono bg-paper-100 text-paper-700">
                                {{ $row->item?->masterKomoditas?->kategori?->nama ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-ink-900">
                            {{ number_format($row->estimasi_volume_panen) }} {{ $row->satuan }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-medium text-ink-900">
                            @if($row->harga_satuan > 0)
                                Rp {{ number_format($row->harga_satuan) }}/{{ $row->satuan }}
                            @else
                                <span class="text-paper-400 italic">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-emerald-800">
                            @php
                                $tot = (float) $row->total_nilai;
                                if ($tot <= 0 && $row->harga_satuan > 0) {
                                    $tot = (float) $row->estimasi_volume_panen * (float) $row->harga_satuan;
                                }
                            @endphp
                            @if($tot > 0)
                                Rp {{ number_format($tot) }}
                            @else
                                <span class="text-paper-400 italic">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-paper-600 font-medium">{{ $row->periode_panen }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('pendataan.potensi.edit', $row) }}" class="text-xs text-blue-600 hover:underline">Edit</a>
                                <form action="{{ route('pendataan.potensi.destroy', $row) }}" method="POST" onsubmit="return confirm('Hapus relasi komoditas petani ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-paper-400 text-xs">
                            Belum ada relasi potensi komoditas petani yang dicatat untuk desa ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>

</x-layouts.app>
