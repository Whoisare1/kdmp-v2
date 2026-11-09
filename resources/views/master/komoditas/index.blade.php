<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    <div x-data="{
        showDeleteModal: false,
        deleteNama: '',
        deleteUrl: '',
        confirmDelete(nama, url) {
            this.deleteNama = nama;
            this.deleteUrl = url;
            this.showDeleteModal = true;
        }
    }">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-sawah-500/40 bg-sawah-100 px-4 py-3 text-sm text-ink-900">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            {{ session('error') }}
        </div>
    @endif

    @if(! $idEntitas)
        <div class="mb-5 rounded-sm border border-padi-500 bg-padi-100 px-4 py-3 text-sm text-ink-800">
            ⚠️ Pilih <strong>Entitas</strong> terlebih dahulu pada dropdown di bagian atas halaman untuk menambah atau mengubah Komoditas entitas.
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama komoditas..."
                   class="w-64 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
            <select name="kategori" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                <option value="">Semua Kategori</option>
                @foreach($kategoriList as $key => $label)
                    <option value="{{ $key }}" @selected(request('kategori') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-sm border border-paper-300 bg-white px-4 py-2 text-sm font-medium text-ink-700 hover:border-merah-400">
                Filter
            </button>
        </form>

        @if($idEntitas)
            <a href="{{ route('master.komoditas.create') }}"
               class="inline-flex items-center justify-center rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                + Tambah Komoditas
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/80">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Komoditas</th>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 font-medium">Alias / Sinonim Survei</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse ($items as $item)
                        <tr class="hover:bg-paper-100/70">
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $item->nama }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-xs text-ink-800">
                                    {{ $kategoriList[$item->kategori] ?? ucfirst(str_replace('_', ' ', $item->kategori)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-600">
                                @if(! empty($item->alias_json) && is_array($item->alias_json))
                                    {{ implode(', ', $item->alias_json) }}
                                @else
                                    <span class="text-ink-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $item->is_active ? 'bg-sawah-100 text-sawah-700' : 'bg-paper-200 text-ink-600' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @if($idEntitas)
                                    <a href="{{ route('master.komoditas.edit', $item->id) }}"
                                       class="text-xs font-medium text-ink-700 hover:text-merah-600">Ubah</a>
                                    <button type="button"
                                            @click="confirmDelete('{{ addslashes($item->nama) }}', '{{ route('master.komoditas.destroy', $item->id) }}')"
                                            class="text-xs font-medium text-merah-600 hover:text-merah-800">
                                        Hapus
                                    </button>
                                @else
                                    <span class="text-xs text-ink-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-ink-600/60">
                                Belum ada data Komoditas pada entitas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>

    {{-- Modal Dialog Konfirmasi Hapus Komoditas --}}
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink-950/50 backdrop-blur-xs transition-opacity"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl border border-paper-200"
             @click.away="showDeleteModal = false"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-ink-900">Konfirmasi Hapus Komoditas</h3>
                    <p class="text-xs text-ink-500">Tindakan ini memerlukan kehati-hatian</p>
                </div>
            </div>

            <div class="mb-5 text-sm text-ink-700 leading-relaxed">
                <p>
                    Apakah Anda yakin ingin menghapus komoditas <span class="font-bold text-ink-900" x-text="deleteNama"></span>?
                </p>
                <p class="mt-2 text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-lg p-2.5">
                    ⚠️ <strong>Perhatian:</strong> Komoditas yang dihapus tidak akan dapat lagi dipilih dalam survei panen, pemetaan produsen, maupun kalkulasi perencanaan pangan.
                </p>
            </div>

            <form :action="deleteUrl" method="POST" class="flex justify-end gap-2.5">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDeleteModal = false"
                        class="rounded-lg border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100 transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-medium text-white hover:bg-rose-700 transition-colors shadow-xs">
                    Ya, Hapus Komoditas
                </button>
            </form>
        </div>
    </div>
    </div>
</x-layouts.app>
