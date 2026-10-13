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

    @if($items->isEmpty() && !request('q'))
        <div class="rounded-sm border border-paper-300 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-paper-100 text-ink-500">
                <svg class="h-8 w-8 text-merah-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <h3 class="font-display text-lg font-semibold text-ink-900">Belum Ada Sesi Survei</h3>
            <p class="mx-auto mt-1.5 max-w-md text-sm text-ink-500 leading-relaxed">
                Belum ada jadwal survei yang dibuat. Buat sesi baru untuk menghasilkan token tautan publik pengisian data lapangan bagi Ketua RT, keluarga, dan produsen.
            </p>
            <div class="mt-6">
                <a href="{{ route($routeBase . '.create') }}"
                   class="inline-flex items-center gap-2 rounded-sm bg-merah-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-merah-600 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Sesi Baru Sekarang
                </a>
            </div>

            <div class="mx-auto mt-10 grid max-w-2xl grid-cols-1 gap-4 border-t border-paper-200 pt-8 sm:grid-cols-3 text-left">
                <div class="rounded-sm border border-paper-200 bg-paper-50 p-4">
                    <div class="text-[11px] font-mono font-semibold uppercase tracking-wider text-merah-600 mb-1">1. Buat Sesi</div>
                    <p class="text-xs text-ink-600 leading-relaxed">Pilih desa target dan periode bulan/tahun pelaksanaan survei.</p>
                </div>
                <div class="rounded-sm border border-paper-200 bg-paper-50 p-4">
                    <div class="text-[11px] font-mono font-semibold uppercase tracking-wider text-merah-600 mb-1">2. Bagikan Token</div>
                    <p class="text-xs text-ink-600 leading-relaxed">Dapatkan 3 link mandiri (RT, Warga, Produsen) untuk dibagikan via WhatsApp.</p>
                </div>
                <div class="rounded-sm border border-paper-200 bg-paper-50 p-4">
                    <div class="text-[11px] font-mono font-semibold uppercase tracking-wider text-merah-600 mb-1">3. Rekap & Validasi</div>
                    <p class="text-xs text-ink-600 leading-relaxed">Pantau otomatis persentase kelengkapan data per RT secara real-time.</p>
                </div>
            </div>
        </div>
    @else
        <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50 shadow-sm">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600">
                        <th class="px-4 py-3 font-semibold">Penanggung Jawab</th>
                        <th class="px-4 py-3 font-semibold">Desa / Wilayah</th>
                        <th class="px-4 py-3 font-semibold">Tahun</th>
                        <th class="px-4 py-3 font-semibold">Bulan</th>
                        <th class="px-4 py-3 font-semibold">Tanggal Pelaksanaan</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
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
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-ink-400">
                                Tidak ada sesi survei yang cocok dengan kata kunci pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

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
