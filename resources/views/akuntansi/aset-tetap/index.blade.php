<x-layouts.app title="Aset Tetap" eyebrow="Akuntansi">

    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-semibold text-ink-900">Aset Tetap & Penyusutan</h1>
            <p class="mt-1 text-sm text-ink-600">
                Kelola daftar aset tetap entitas. Sistem akan menghitung dan memposting jurnal penyusutan secara otomatis saat Tutup Bulan.
            </p>
        </div>
        @if($idEntitas)
            <a href="{{ route('akuntansi.aset-tetap.create') }}" class="rounded bg-merah-500 px-4 py-2 text-sm font-medium text-white hover:bg-merah-600 transition shadow">
                + Tambah Aset
            </a>
        @endif
    </div>

    @if(! $idEntitas)
        <div class="rounded border border-padi-500 bg-padi-100 px-4 py-3 text-sm text-ink-700">
            ⚠️ Harap pilih <strong>Entitas</strong> terlebih dahulu untuk mengelola Aset Tetap.
        </div>
    @else
        <form method="GET" action="{{ route('akuntansi.aset-tetap.index') }}" class="mb-5 flex items-center gap-3">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama atau kode aset..."
                   class="w-full max-w-sm rounded border border-paper-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-merah-400">
            <button type="submit" class="rounded bg-paper-200 px-4 py-2 text-sm font-medium text-ink-700 hover:bg-paper-300 transition">
                Cari
            </button>
        </form>

        @if(session('success'))
            <div class="mb-4 rounded border border-sawah-300 bg-sawah-50 px-4 py-3 text-sm text-sawah-800">
                {{ session('success') }}
            </div>
        @endif

        @if($aset->isEmpty())
            <div class="rounded border border-dashed border-paper-300 bg-paper-50 py-12 text-center text-sm text-ink-400">
                Tidak ada data aset tetap.
            </div>
        @else
            <div class="overflow-hidden rounded border border-paper-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-paper-200 bg-paper-100 text-xs font-medium uppercase tracking-wide text-ink-500">
                            <th class="px-4 py-2.5 text-left">Kode & Nama</th>
                            <th class="px-4 py-2.5 text-left">Kategori</th>
                            <th class="px-4 py-2.5 text-left">Tgl Perolehan</th>
                            <th class="px-4 py-2.5 text-right">Nilai Perolehan</th>
                            <th class="px-4 py-2.5 text-right">Akum. Penyusutan</th>
                            <th class="px-4 py-2.5 text-right">Nilai Buku</th>
                            <th class="px-4 py-2.5 text-center">Status</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-paper-100">
                        @foreach($aset as $a)
                            @php
                                $nilaiBuku = $a->nilai_perolehan - $a->akum_penyusutan;
                            @endphp
                            <tr class="hover:bg-paper-50 transition">
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-ink-900">{{ $a->nama_aset }}</div>
                                    <div class="font-mono text-xs text-ink-500">{{ $a->kode_aset }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-ink-700">{{ $a->kategori }}</td>
                                <td class="px-4 py-2.5 text-ink-700">{{ $a->tgl_perolehan->format('d M Y') }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-ink-900">
                                    {{ number_format($a->nilai_perolehan, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-ink-900">
                                    {{ number_format($a->akum_penyusutan, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold text-ink-900">
                                    {{ number_format($nilaiBuku, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @php
                                        $statusColor = match($a->status) {
                                            'AKTIF' => 'bg-sawah-100 text-sawah-700',
                                            'DIJUAL' => 'bg-blue-100 text-blue-700',
                                            'RUSAK' => 'bg-merah-100 text-merah-700',
                                            default => 'bg-paper-100 text-ink-500',
                                        };
                                    @endphp
                                    <span class="rounded px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                                        {{ $a->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <a href="{{ route('akuntansi.aset-tetap.edit', $a->id_aset) }}" class="text-merah-500 hover:text-merah-700 hover:underline text-xs">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $aset->links() }}
            </div>
        @endif
    @endif
</x-layouts.app>
