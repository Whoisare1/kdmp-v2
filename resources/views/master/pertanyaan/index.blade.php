<x-layouts.app :title="$title" eyebrow="Master Data Survei">

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-display font-semibold text-ink-900">Master Pertanyaan Survei</h2>
            <p class="text-xs text-paper-500 mt-0.5">Daftar instrumen pertanyaan survei lapangan untuk kependudukan dan komoditas petani.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" class="flex items-center gap-2">
                <select name="jenis" class="rounded-lg border border-paper-300 bg-white px-3 py-1.5 text-xs text-ink-800 focus:outline-none">
                    <option value="">Semua Jenis Pendataan</option>
                    <option value="penduduk" @selected($jenis === 'penduduk')>Pendataan Penduduk</option>
                    <option value="komoditas" @selected($jenis === 'komoditas')>Potensi Komoditas Petani</option>
                </select>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari pertanyaan..."
                       class="w-44 rounded-lg border border-paper-300 bg-white px-3 py-1.5 text-xs focus:outline-none">
                <button class="rounded-lg bg-ink-900 text-white px-3 py-1.5 text-xs font-medium hover:bg-ink-800">Filter</button>
            </form>
            <a href="{{ route('master.pertanyaan.create') }}" class="rounded-lg bg-padi-500 hover:bg-padi-600 text-ink-950 font-semibold px-4 py-2 text-xs shadow-sm transition">
                + Tambah Pertanyaan
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-paper-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-paper-200 bg-paper-50 font-mono text-[11px] uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-5 py-3 text-center">Urutan</th>
                    <th class="px-5 py-3">Kode</th>
                    <th class="px-5 py-3">Jenis Pendataan</th>
                    <th class="px-5 py-3">Teks Pertanyaan</th>
                    <th class="px-5 py-3">Tipe Input</th>
                    <th class="px-5 py-3 text-center">Wajib</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @forelse($items as $q)
                    <tr class="hover:bg-paper-50/60">
                        <td class="px-5 py-3 text-center font-mono text-xs text-paper-400 font-bold">{{ $q->urutan }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-paper-500">{{ $q->kode_pertanyaan }}</td>
                        <td class="px-5 py-3 text-xs">
                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $q->jenis_pendataan === 'penduduk' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">
                                {{ $q->jenis_pendataan ?: 'komoditas' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-ink-900 font-medium max-w-md">
                            {{ $q->teks_pertanyaan }}
                            @if($q->satuan)
                                <span class="text-xs text-paper-500">({{ $q->satuan }})</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-xs font-mono text-paper-600">{{ $q->tipe_jawaban }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="text-xs {{ $q->wajib_diisi ? 'text-red-500 font-semibold' : 'text-paper-400' }}">
                                {{ $q->wajib_diisi ? 'Ya' : 'Opsional' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-xs space-x-2">
                            <a href="{{ route('master.pertanyaan.edit', $q) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form action="{{ route('master.pertanyaan.destroy', $q) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pertanyaan ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-sm text-paper-400">Belum ada pertanyaan survei.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($items->hasPages())
            <div class="p-4 border-t border-paper-200">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</x-layouts.app>

