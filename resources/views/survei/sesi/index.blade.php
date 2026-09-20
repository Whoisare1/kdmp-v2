<x-layouts.app :title="$title" eyebrow="Manajemen Survei">

    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-ink-500">Daftar sesi yang digunakan sebagai token pengisian kuesioner.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('survei.dashboard.index') }}"
               class="flex items-center gap-1.5 rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-semibold text-ink-700 shadow-sm hover:bg-paper-100 whitespace-nowrap">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>

            <a href="{{ route($routeBase . '.create') }}"
               class="flex items-center gap-1.5 rounded-sm bg-merah-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-merah-600 whitespace-nowrap">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Sesi Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-sm border border-sawah-300 bg-sawah-50 px-4 py-3 text-sm text-sawah-800">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sawah-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600">
                    <th class="px-4 py-3 font-semibold">ID PETUGAS</th>
                    <th class="px-4 py-3 font-semibold">ID WILAYAH</th>
                    <th class="px-4 py-3 font-semibold">TAHUN</th>
                    <th class="px-4 py-3 font-semibold">BULAN</th>
                    <th class="px-4 py-3 font-semibold">TANGGAL SURVEI</th>
                    <th class="px-4 py-3 font-semibold">STATUS</th>
                    <th class="px-4 py-3 text-right font-semibold">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-200">
                @forelse ($items as $item)
                    @php
                        $bulans = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        $namaBulan = $bulans[$item->bulan] ?? $item->bulan;

                        $progressText = $item->progress_sesi_text;
                        $countDone = (int) explode('/', $progressText)[0];
                        $statusCls = match(true) {
                            $countDone >= 4 => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                            $countDone > 0  => 'bg-amber-100 text-amber-800 border-amber-300',
                            default         => 'bg-paper-200 text-ink-600 border-paper-300',
                        };
                    @endphp
                    <tr class="hover:bg-paper-100/70 transition-colors">
                        <td class="px-4 py-3 text-ink-900 font-medium text-xs">
                            {{ $item->petugas->nama ?? 'Sistem' }}
                        </td>
                        <td class="px-4 py-3 font-medium text-ink-900">
                            {{ $item->wilayah->nama ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-ink-700 text-xs font-mono">
                            {{ $item->tahun }}
                        </td>
                        <td class="px-4 py-3 text-ink-700 text-xs">
                            {{ $namaBulan }}
                        </td>
                        <td class="px-4 py-3 text-ink-600 text-xs font-mono">
                            {{ $item->tanggal_survei ? $item->tanggal_survei->format('d M Y') : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $statusCls }}">
                                {{ $progressText }} Sesi
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route($routeBase . '.show', $item->id) }}"
                                   class="rounded-sm border border-paper-300 bg-white px-2.5 py-1 text-xs font-medium text-ink-700 hover:border-merah-400 hover:text-merah-600 transition-colors">
                                    Detail
                                </a>
                                <button type="button"
                                        onclick="openDeleteModal('{{ route($routeBase . '.destroy', $item->id) }}')"
                                        class="rounded-sm border border-red-200 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-ink-400">
                            Belum ada sesi survei. Klik <strong>Buat Sesi Baru</strong> untuk memulai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>

    {{-- Delete Modal --}}
    <div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-sm rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-xl text-center">
            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h3 class="mb-1 font-display text-lg font-semibold text-ink-900">Konfirmasi Hapus</h3>
            <p class="mb-5 text-sm text-ink-500 leading-relaxed">Apakah Anda yakin ingin menghapus sesi survei ini? Data narasumber terkait juga akan terhapus.</p>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDeleteModal()" class="flex-1 rounded-sm border border-paper-300 bg-white py-2 text-sm font-medium text-ink-700 hover:bg-paper-100">Batal</button>
                <form id="deleteForm" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full rounded-sm bg-merah-500 py-2 text-sm font-semibold text-white hover:bg-merah-600">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal(actionUrl) {
            document.getElementById('deleteForm').action = actionUrl;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }
    </script>
</x-layouts.app>
