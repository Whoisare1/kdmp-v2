<x-layouts.app :title="$title" eyebrow="Informasi Detail Komoditas">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('master.item-komoditas.index') }}" class="text-xs text-ink-600/70 hover:text-ink-900">&larr; Kembali ke Daftar Item</a>
            </div>
            <h2 class="font-display text-2xl font-semibold text-ink-900 mt-1">{{ $item->nama }}</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('master.item-komoditas.edit', $item) }}" class="rounded-sm border border-paper-300 px-3 py-1.5 text-xs font-medium text-ink-700 hover:border-merah-400">
                Ubah Item
            </a>
            <a href="{{ route('pendataan.potensi.create', ['redirect_to' => route('master.item-komoditas.show', $item)]) }}" class="rounded-sm bg-merah-500 px-3 py-1.5 text-xs font-medium text-paper-50 hover:bg-merah-600">
                + Tambah Petani
            </a>
        </div>
    </div>

    {{-- Kartu Ringkasan Hierarki --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">1. Kategori</p>
            <p class="font-display text-base font-semibold text-ink-900 mt-1">{{ $item->masterKomoditas?->kategori?->nama ?? '—' }}</p>
            <p class="font-mono text-[11px] text-ink-600/60 mt-0.5">Kode: {{ $item->masterKomoditas?->kategori?->kode }}</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">2. Master Komoditas</p>
            <p class="font-display text-base font-semibold text-ink-900 mt-1">{{ $item->masterKomoditas?->nama ?? '—' }}</p>
            <p class="font-mono text-[11px] text-ink-600/60 mt-0.5">Kode: {{ $item->masterKomoditas?->kode }}</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">3. Item / Subjenis</p>
            <p class="font-display text-base font-semibold text-ink-900 mt-1">{{ $item->nama }}</p>
            <p class="font-mono text-[11px] text-ink-600/60 mt-0.5">Satuan: {{ $item->satuan_standar }}</p>
        </div>

        <div class="rounded-sm border border-paper-300 bg-paper-50 p-4">
            <p class="font-mono text-[10px] uppercase tracking-wide text-ink-600/60">Total Produsen</p>
            <p class="font-display text-base font-semibold text-sawah-800 mt-1">{{ $daftarPetani->count() }} Petani</p>
            <p class="text-[11px] text-ink-600/60 mt-0.5">di {{ count($desaPenghasil) }} Desa</p>
        </div>
    </div>

    {{-- Daftar Desa Penghasil --}}
    <div class="mb-6 rounded-sm border border-paper-300 bg-paper-50 p-5">
        <h3 class="font-mono text-[11px] uppercase tracking-wide text-ink-600/70 mb-3">
            Desa Penghasil Komoditas Ini
        </h3>

        @if(empty($desaPenghasil))
            <p class="text-xs text-ink-600/60">Belum ada data desa yang tercatat memproduksi komoditas ini.</p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach($desaPenghasil as $dp)
                    <span class="inline-flex items-center rounded-sm border border-paper-200 bg-paper-100 px-3 py-1 text-xs text-ink-800">
                        <strong>{{ $dp['desa'] }}</strong> &nbsp;(Kec. {{ $dp['kecamatan'] ?? '-' }}, Kab. {{ $dp['kabupaten'] ?? 'Pati' }})
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Daftar Petani yang Menghasilkan Item Tersebut --}}
    <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50">
        <div class="border-b border-paper-300 bg-paper-200/60 px-4 py-3 flex items-center justify-between">
            <h3 class="font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                Daftar Petani / Produsen Penghasil ({{ $daftarPetani->count() }})
            </h3>
            <span class="font-mono text-[10px] text-ink-600/60">Pencatatan Potensi Produksi</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-paper-300 bg-paper-100 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                        <th class="px-4 py-3 font-medium">Petani</th>
                        <th class="px-4 py-3 font-medium">Kontak</th>
                        <th class="px-4 py-3 font-medium">Lokasi / Dusun</th>
                        <th class="px-4 py-3 font-medium">Luas Lahan</th>
                        <th class="px-4 py-3 font-medium">Estimasi Panen</th>
                        <th class="px-4 py-3 font-medium">Harga / Satuan</th>
                        <th class="px-4 py-3 font-medium">Total Nilai</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse($daftarPetani as $p)
                        <tr class="hover:bg-paper-100/70">
                            <td class="px-4 py-3">
                                <a href="{{ route('pendataan.petani.show', $p) }}" class="font-medium text-ink-900 hover:text-merah-600">
                                    {{ $p->nama_lengkap }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $p->nomor_hp ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-ink-700">
                                <p class="font-medium">{{ $p->desa ?: ($p->wilayah?->nama ?: '—') }}</p>
                                <p class="text-ink-600/60">{{ $p->alamat ?: 'RT ' . ($p->rt ?: '-') . ' / RW ' . ($p->rw ?: '-') }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-800">
                                {{ $p->pivot?->luas_lahan_ha ? number_format($p->pivot->luas_lahan_ha, 2, ',', '.') . ' ha' : '—' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-900 font-medium">
                                {{ $p->pivot?->estimasi_volume_panen ? number_format($p->pivot->estimasi_volume_panen, 0, ',', '.') . ' ' . ($p->pivot->satuan ?? 'kg') : '—' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-800">
                                {{ $p->pivot?->harga_satuan ? 'Rp ' . number_format($p->pivot->harga_satuan, 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-sawah-800">
                                {{ $p->pivot?->total_nilai ? 'Rp ' . number_format($p->pivot->total_nilai, 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('pendataan.petani.show', $p) }}" class="text-xs font-medium text-ink-700 hover:text-merah-600">
                                    Profil
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-ink-600/60">
                                Belum ada petani yang terdata menghasilkan komoditas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
