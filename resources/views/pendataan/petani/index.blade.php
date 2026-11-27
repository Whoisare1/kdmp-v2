<x-layouts.app :title="$title" eyebrow="Modul Pendataan">

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header Halaman --}}
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
            <h2 class="text-xl font-display font-semibold text-ink-900 mt-1">Petani Komoditas: {{ $desaAktif?->nama ?? 'Desa' }}</h2>
            <p class="text-xs text-paper-500">Pencarian dan pemetaan petani produsen berdasarkan hierarki komoditas yang dihasilkan di desa.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <a href="{{ route('pendataan.petani.create') }}" class="flex-1 sm:flex-initial inline-flex justify-center items-center rounded-lg bg-padi-500 hover:bg-padi-600 text-ink-950 font-semibold px-4 py-2 text-xs shadow-sm transition text-center">
                + Tambah Petani
            </a>
            <a href="{{ route('pendataan.potensi.create') }}" class="flex-1 sm:flex-initial inline-flex justify-center items-center rounded-lg border border-paper-300 bg-white hover:bg-paper-50 text-ink-900 font-medium px-3.5 py-2 text-xs shadow-sm transition text-center">
                + Hubungkan Komoditas
            </a>
        </div>
    </div>

    {{-- Filter Berjenjang (Kategori -> Master Komoditas -> Item Komoditas) --}}
    <div class="mb-6 rounded-xl border border-paper-200 bg-white p-4 shadow-sm"
         x-data="cascadingFilter()">
        <form method="GET" action="{{ route('pendataan.petani.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- 1. Kategori --}}
                <div>
                    <label class="block text-[11px] font-mono uppercase tracking-wider text-paper-600 mb-1">
                        1. Kategori Komoditas
                    </label>
                    <select name="kategori_id"
                            x-model="selectedKategori"
                            @change="onKategoriChange()"
                            class="w-full rounded-lg border border-paper-300 bg-white p-2 text-xs focus:border-padi-500 focus:outline-none">
                        <option value="">Semua Kategori</option>
                        @foreach($kategoriList as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Master Komoditas --}}
                <div>
                    <label class="block text-[11px] font-mono uppercase tracking-wider text-paper-600 mb-1">
                        2. Master Komoditas
                    </label>
                    <select name="master_id"
                            x-model="selectedMaster"
                            @change="onMasterChange()"
                            :disabled="!selectedKategori"
                            class="w-full rounded-lg border border-paper-300 bg-white p-2 text-xs focus:border-padi-500 focus:outline-none disabled:bg-paper-100 disabled:text-paper-400">
                        <option value="">Semua Master Komoditas</option>
                        <template x-for="m in availableMasters" :key="m.id">
                            <option :value="m.id" x-text="m.nama" :selected="m.id == selectedMaster"></option>
                        </template>
                    </select>
                </div>

                {{-- 3. Item / Subjenis --}}
                <div>
                    <label class="block text-[11px] font-mono uppercase tracking-wider text-paper-600 mb-1">
                        3. Item / Subjenis Komoditas
                    </label>
                    <select name="item_id"
                            x-model="selectedItem"
                            :disabled="!selectedMaster"
                            class="w-full rounded-lg border border-paper-300 bg-white p-2 text-xs focus:border-padi-500 focus:outline-none disabled:bg-paper-100 disabled:text-paper-400">
                        <option value="">Semua Item Komoditas</option>
                        <template x-for="it in availableItems" :key="it.id">
                            <option :value="it.id" x-text="it.nama + ' (' + it.satuan_standar + ')'" :selected="it.id == selectedItem"></option>
                        </template>
                    </select>
                </div>

                {{-- 4. Pencarian Petani --}}
                <div>
                    <label class="block text-[11px] font-mono uppercase tracking-wider text-paper-600 mb-1">
                        Cari Petani / Domisili
                    </label>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Nama petani, HP, alamat..."
                           class="w-full rounded-lg border border-paper-300 bg-white p-2 text-xs focus:border-padi-500 focus:outline-none">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-paper-100">
                <p class="text-[11px] text-paper-500">
                    Filter berurutan: Pilih Kategori &rarr; Pilih Master &rarr; Pilih Item &rarr; Klik Terapkan.
                </p>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    @if($kategoriId || $masterId || $itemId || $search)
                        <a href="{{ route('pendataan.petani.index') }}" class="flex-1 sm:flex-initial text-center px-3 py-1.5 rounded-lg border border-paper-300 text-xs font-medium text-paper-600 hover:bg-paper-50">
                            Reset Filter
                        </a>
                    @endif
                    <button type="submit" class="flex-1 sm:flex-initial px-4 py-1.5 rounded-lg bg-ink-900 text-white text-xs font-semibold hover:bg-ink-800 shadow-sm transition text-center">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Tabel Petani Komoditas --}}
    <div class="overflow-x-auto rounded-xl border border-paper-200 bg-white shadow-sm">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-paper-200 bg-paper-50 font-mono text-[11px] uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-5 py-3">Nama Petani & Domisili</th>
                    <th class="px-5 py-3">Kontak (HP)</th>
                    <th class="px-5 py-3">Komoditas yang Diproduksi & Harga</th>
                    <th class="px-5 py-3 text-right">Total Nilai Panen</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @forelse($items as $p)
                    <tr class="hover:bg-paper-50/60 text-xs">
                        <td class="px-5 py-3 font-semibold text-ink-900">
                            <a href="{{ route('pendataan.petani.show', $p) }}" class="text-blue-700 hover:underline font-semibold text-sm">
                                {{ $p->nama_lengkap }}
                            </a>
                            <p class="text-[11px] text-paper-500 font-normal mt-0.5">
                                {{ $p->alamat ?: '-' }} (RT {{ $p->rt ?: '00' }} / RW {{ $p->rw ?: '00' }})
                            </p>
                            <p class="text-[10px] text-paper-400 font-mono">
                                Desa {{ $p->desa ?: ($p->wilayah?->nama ?? '-') }}, Kec. {{ $p->kecamatan ?: ($p->wilayah?->parent?->nama ?? 'Juwana') }}
                            </p>
                        </td>
                        <td class="px-5 py-3 font-mono text-paper-700">
                            {{ $p->nomor_hp ?: '-' }}
                        </td>
                        <td class="px-5 py-3">
                            @if($p->items->isEmpty())
                                <span class="text-paper-400 italic">Belum ada komoditas terhubung</span>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach($p->items as $it)
                                        @php
                                            $isMatch = ($itemId && $it->id == $itemId) || ($masterId && $it->id_master_komoditas == $masterId);
                                            $hargaSat = (float) ($it->pivot->harga_satuan ?? 0);
                                            $totVal = (float) ($it->pivot->total_nilai ?? 0);
                                            if ($totVal <= 0 && $hargaSat > 0) {
                                                $totVal = (float) $it->pivot->estimasi_volume_panen * $hargaSat;
                                            }
                                        @endphp
                                        <div class="inline-flex flex-col px-2.5 py-1.5 rounded-lg border text-xs
                                            {{ $isMatch ? 'bg-padi-50 border-padi-300 ring-1 ring-padi-400' : 'bg-paper-50 border-paper-200' }}">
                                            <div class="flex items-center gap-1.5 font-semibold text-ink-900">
                                                <span>{{ $it->nama }}</span>
                                                <span class="text-paper-500 font-mono text-[11px]">({{ number_format($it->pivot->estimasi_volume_panen) }} {{ $it->pivot->satuan }})</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5 font-mono text-[11px]">
                                                @if($hargaSat > 0)
                                                    <span class="text-emerald-700 font-medium">Rp {{ number_format($hargaSat) }}/{{ $it->pivot->satuan }}</span>
                                                    <span class="text-paper-400">&bull;</span>
                                                    <span class="font-bold text-ink-900">Rp {{ number_format($totVal) }}</span>
                                                @else
                                                    <span class="text-paper-400 italic">Harga belum dicatat</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            @php
                                $totalNilaiPetani = $p->items->sum(function($it) {
                                    $h = (float) ($it->pivot->harga_satuan ?? 0);
                                    $t = (float) ($it->pivot->total_nilai ?? 0);
                                    return $t > 0 ? $t : ($it->pivot->estimasi_volume_panen * $h);
                                });
                            @endphp
                            @if($totalNilaiPetani > 0)
                                <span class="font-mono font-bold text-sm text-emerald-900 block">
                                    Rp {{ number_format($totalNilaiPetani) }}
                                </span>
                                <span class="text-[10px] text-paper-400 font-mono">{{ $p->items->count() }} Komoditas</span>
                            @else
                                <span class="text-paper-400 text-xs italic">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2 text-xs">
                                <a href="{{ route('pendataan.petani.show', $p) }}" class="text-paper-600 hover:text-ink-900 font-medium">Detail</a>
                                <a href="{{ route('pendataan.petani.edit', $p) }}" class="text-blue-600 hover:underline font-medium">Edit</a>
                                <form action="{{ route('pendataan.petani.destroy', $p) }}" method="POST" onsubmit="return confirm('Hapus data petani ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-paper-400 text-xs">
                            Tidak ditemukan petani yang memproduksi komoditas dengan kriteria filter tersebut.
                            @if($kategoriId || $masterId || $itemId || $search)
                                <br><a href="{{ route('pendataan.petani.index') }}" class="text-blue-600 hover:underline mt-1 inline-block">Reset Filter</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>

    {{-- Script Cascading Dropdown --}}
    <script>
        function cascadingFilter() {
            const rawData = @json($kategoriList);
            return {
                selectedKategori: '{{ $kategoriId ?? "" }}',
                selectedMaster: '{{ $masterId ?? "" }}',
                selectedItem: '{{ $itemId ?? "" }}',
                availableMasters: [],
                availableItems: [],
                init() {
                    this.updateMasters();
                    this.updateItems();
                    this.$nextTick(() => {
                        this.selectedMaster = '{{ $masterId ?? "" }}';
                        this.selectedItem = '{{ $itemId ?? "" }}';
                    });
                },
                onKategoriChange() {
                    this.selectedMaster = '';
                    this.selectedItem = '';
                    this.updateMasters();
                    this.availableItems = [];
                },
                onMasterChange() {
                    this.selectedItem = '';
                    this.updateItems();
                },
                updateMasters() {
                    if (!this.selectedKategori) {
                        this.availableMasters = [];
                        return;
                    }
                    const kat = rawData.find(k => k.id == this.selectedKategori);
                    this.availableMasters = kat ? (kat.master_komoditas || []) : [];
                },
                updateItems() {
                    if (!this.selectedMaster) {
                        this.availableItems = [];
                        return;
                    }
                    let foundMaster = null;
                    for (const kat of rawData) {
                        if (kat.master_komoditas) {
                            const m = kat.master_komoditas.find(item => item.id == this.selectedMaster);
                            if (m) {
                                foundMaster = m;
                                break;
                            }
                        }
                    }
                    this.availableItems = foundMaster ? (foundMaster.items || []) : [];
                }
            };
        }
    </script>

</x-layouts.app>
