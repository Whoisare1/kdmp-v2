<x-layouts.app :title="$title" eyebrow="Tingkat 3 Hierarki Komoditas">
    <div x-data="{
        modalOpen: false,
        selectedItem: '',
        selectedMaster: '',
        selectedDetailUrl: '',
        petaniList: [],
        openPetani(itemNama, masterNama, detailUrl, list) {
            this.selectedItem = itemNama;
            this.selectedMaster = masterNama;
            this.selectedDetailUrl = detailUrl;
            this.petaniList = list;
            this.modalOpen = true;
        }
    }" @keydown.escape.window="modalOpen = false">

        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <select name="master_id" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                    <option value="">Semua Master</option>
                    @foreach($masterList as $m)
                        <option value="{{ $m->id }}" @selected($masterId == $m->id)>{{ $m->nama }} ({{ $m->kategori?->nama }})</option>
                    @endforeach
                </select>

                <select name="status_petani" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-800 focus:border-merah-400 focus:outline-none">
                    <option value="">Semua Status Data</option>
                    <option value="ada" @selected($statusPetani === 'ada')>Sudah Didata Petani</option>
                    <option value="belum" @selected($statusPetani === 'belum')>Belum Didata Petani</option>
                </select>

                <input
                    type="text"
                    name="q"
                    value="{{ $search ?? request('q') }}"
                    placeholder="Cari item komoditas..."
                    class="w-56 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
                >

                <button type="submit" class="rounded-sm border border-paper-300 px-3 py-2 text-sm text-ink-700 hover:border-merah-400">
                    Filter
                </button>

                @if($masterId || $statusPetani || ($search ?? request('q')))
                    <a href="{{ route('master.item-komoditas.index') }}" class="text-xs text-ink-600/70 hover:text-ink-900 px-2 py-2">Reset</a>
                @endif
            </form>

            <a href="{{ route('master.item-komoditas.create') }}" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">
                + Tambah Item
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-sm border border-sawah-200 bg-sawah-50 p-3 text-xs text-sawah-800">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-sm border border-merah-200 bg-merah-50 p-3 text-xs text-merah-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                            <th class="px-4 py-3 font-medium">Kode</th>
                            <th class="px-4 py-3 font-medium">Nama Item / Subjenis</th>
                            <th class="px-4 py-3 font-medium">Master Komoditas</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium">Satuan</th>
                            <th class="px-4 py-3 text-center font-medium">Petani Terdata</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-paper-200">
                        @forelse($items as $it)
                            <tr class="hover:bg-paper-100/70">
                                <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $it->kode }}</td>
                                <td class="px-4 py-3 font-medium text-ink-900">
                                    <a href="{{ route('master.item-komoditas.show', $it) }}" class="text-ink-900 hover:text-merah-600">
                                        {{ $it->nama }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-ink-800">{{ $it->masterKomoditas?->nama ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-ink-700">
                                    <span class="inline-flex items-center rounded-sm bg-paper-200/80 px-2 py-0.5 font-mono text-xs text-ink-800">
                                        {{ $it->masterKomoditas?->kategori?->nama ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $it->satuan_standar }}</td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $petaniJson = $it->petani->map(function ($p) {
                                            $rtRw = [];
                                            if ($p->rt) $rtRw[] = 'RT ' . $p->rt;
                                            if ($p->rw) $rtRw[] = 'RW ' . $p->rw;
                                            $rtRwStr = !empty($rtRw) ? implode(' / ', $rtRw) : '-';

                                            $wilayahDesa = $p->desa ?: ($p->wilayah?->nama ?: '');
                                            $wilayahKec = $p->kecamatan ?: ($p->wilayah?->parent?->nama ?: '');
                                            $wilayahKab = $p->kabupaten ?: '';

                                            return [
                                                'id' => $p->id,
                                                'nama' => $p->nama_lengkap,
                                                'nomor_hp' => $p->nomor_hp ?: '-',
                                                'alamat' => $p->alamat ?: '-',
                                                'rt_rw' => $rtRwStr,
                                                'desa' => $wilayahDesa ?: '-',
                                                'kecamatan' => $wilayahKec ?: '-',
                                                'kabupaten' => $wilayahKab ?: '-',
                                                'estimasi_panen' => $p->pivot?->estimasi_volume_panen ? number_format($p->pivot->estimasi_volume_panen, 0, ',', '.') . ' ' . ($p->pivot->satuan ?? 'kg') : '-',
                                                'harga_satuan' => $p->pivot?->harga_satuan ? 'Rp ' . number_format($p->pivot->harga_satuan, 0, ',', '.') . '/' . ($p->pivot->satuan ?? 'kg') : '-',
                                                'total_nilai' => $p->pivot?->total_nilai ? 'Rp ' . number_format($p->pivot->total_nilai, 0, ',', '.') : '-',
                                                'periode' => $p->pivot?->periode_panen ?: '-',
                                                'show_url' => route('pendataan.petani.show', $p),
                                            ];
                                        });
                                    @endphp

                                    <button type="button"
                                            @click='openPetani(@json($it->nama), @json($it->masterKomoditas?->nama ?? "-"), @json(route("master.item-komoditas.show", $it)), @json($petaniJson))'
                                            class="inline-flex items-center rounded-sm px-2 py-0.5 font-mono text-xs {{ $it->petani_count > 0 ? 'bg-sawah-100 text-sawah-800 hover:bg-sawah-200' : 'bg-paper-200 text-ink-600 hover:bg-paper-300' }} transition-colors cursor-pointer"
                                            title="Klik untuk melihat daftar petani penghasil {{ $it->nama }}">
                                        {{ $it->petani_count }} Petani
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('master.item-komoditas.show', $it) }}" class="text-xs font-medium text-ink-700 hover:text-merah-600 mr-2">Detail</a>
                                    <a href="{{ route('master.item-komoditas.edit', $it) }}" class="text-xs font-medium text-ink-700 hover:text-merah-600 mr-2">Ubah</a>
                                    <form action="{{ route('master.item-komoditas.destroy', $it) }}" method="POST" class="inline" onsubmit="return confirm('Hapus item komoditas ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-medium text-merah-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-600/60">
                                    Belum ada item / subjenis komoditas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($items->hasPages())
            <div class="mt-4">
                {{ $items->links() }}
            </div>
        @endif

        {{-- Modal Daftar Petani Produsen --}}
        <div x-show="modalOpen"
             x-cloak
             x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4">
            <div @click.away="modalOpen = false"
                 class="w-full max-w-2xl rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-xl max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-paper-200 pb-3 mb-4">
                    <div>
                        <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/70">Daftar Petani Produsen</p>
                        <h3 class="font-display text-lg font-semibold text-ink-900" x-text="selectedItem"></h3>
                    </div>
                    <button @click="modalOpen = false" class="text-ink-600 hover:text-ink-900 text-lg leading-none">&times;</button>
                </div>

                <div class="overflow-y-auto flex-1 pr-1 custom-scrollbar">
                    <template x-if="petaniList.length === 0">
                        <p class="py-8 text-center text-sm text-ink-600/60">Belum ada petani yang terdata memproduksi komoditas ini.</p>
                    </template>

                    <div class="space-y-3" x-show="petaniList.length > 0">
                        <template x-for="p in petaniList" :key="p.id">
                            <div class="rounded-sm border border-paper-200 bg-paper-100/60 p-3 hover:border-paper-300 transition-colors">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold text-sm text-ink-900" x-text="p.nama"></p>
                                        <p class="text-xs text-ink-600/80 mt-0.5" x-text="p.desa + ', Kec. ' + p.kecamatan + ' (' + p.rt_rw + ')'"></p>
                                    </div>
                                    <a :href="p.show_url" class="rounded-sm border border-paper-300 bg-paper-50 px-2 py-1 text-xs text-ink-700 hover:border-merah-400 shrink-0">
                                        Profil Petani
                                    </a>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2 pt-2 border-t border-paper-200 text-xs">
                                    <div>
                                        <span class="block text-[10px] font-mono uppercase text-ink-600/60">Estimasi Panen</span>
                                        <span class="font-medium text-ink-900" x-text="p.estimasi_panen"></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-mono uppercase text-ink-600/60">Harga Satuan</span>
                                        <span class="font-medium text-ink-900" x-text="p.harga_satuan"></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-mono uppercase text-ink-600/60">Total Nilai</span>
                                        <span class="font-medium text-sawah-800" x-text="p.total_nilai"></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-mono uppercase text-ink-600/60">Periode</span>
                                        <span class="font-medium text-ink-900" x-text="p.periode"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-paper-200 mt-4">
                    <button @click="modalOpen = false" class="rounded-sm border border-paper-300 px-4 py-1.5 text-xs text-ink-700 hover:border-merah-400">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
