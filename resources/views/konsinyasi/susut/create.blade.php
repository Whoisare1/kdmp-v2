<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-4">
        <a href="{{ route('konsinyasi.susut.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">&larr; Kembali ke Riwayat Susut</a>
    </div>

    <div class="mx-auto max-w-3xl rounded-sm border border-paper-300 bg-paper-50 p-6">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-ink-900">Catat Barang Konsinyasi Rusak atau Susut</h1>
            <p class="mt-1 text-sm text-ink-600">Pencatatan mengurangi sisa stok titipan dan menambahkan mutasi susut. Konsinyasi tidak membuat atau memposting jurnal.</p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-sm border border-merah-500/40 bg-merah-50 px-4 py-3 text-sm text-merah-700">
                <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('konsinyasi.susut.store') }}" class="space-y-5">
            @csrf
            <div>
                <label for="id_stok_konsinyasi" class="mb-1 block text-sm font-medium text-ink-700">Barang titipan</label>
                <select id="id_stok_konsinyasi" name="id_stok_konsinyasi" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
                    <option value="">Pilih kiriman dan barang</option>
                    @foreach ($items as $stock)
                        @php
                            $shipment = $stock->kiriman;
                            $owner = $shipment?->jenis_pemilik === 'pihak'
                                ? ($shipment->pihakPemilik?->nama ?? 'Penitip')
                                : ($shipment?->koperasiPemilik?->nama_entitas ?? 'Desa pemilik');
                            $unit = $stock->barang?->satuanDasar?->kode_satuan ?? '';
                        @endphp
                        <option value="{{ $stock->id_stok_konsinyasi }}" @selected((string) old('id_stok_konsinyasi', $selectedId) === (string) $stock->id_stok_konsinyasi)>
                            {{ $shipment?->kode_kiriman }} — {{ $stock->barang?->nama_barang }} — Pemilik: {{ $owner }} — Sisa: {{ number_format((float) $stock->qty_sisa, 4, ',', '.') }} {{ $unit }} — Penanggung: {{ ucfirst($shipment?->penanggung_susut ?? '-') }}
                        </option>
                    @endforeach
                </select>
                @error('id_stok_konsinyasi')<p class="mt-1 text-sm text-merah-600">{{ $message }}</p>@enderror
                @if ($items->isEmpty())
                    <p class="mt-2 text-sm text-ink-600">Tidak ada stok titipan aktif yang dapat dicatat susutnya.</p>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="tanggal" class="mb-1 block text-sm font-medium text-ink-700">Tanggal kejadian</label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
                    @error('tanggal')<p class="mt-1 text-sm text-merah-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jenis_kejadian" class="mb-1 block text-sm font-medium text-ink-700">Jenis kejadian</label>
                    <select id="jenis_kejadian" name="jenis_kejadian" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
                        <option value="rusak" @selected(old('jenis_kejadian') === 'rusak')>Rusak</option>
                        <option value="susut_alami" @selected(old('jenis_kejadian') === 'susut_alami')>Susut alami</option>
                        <option value="hilang" @selected(old('jenis_kejadian') === 'hilang')>Hilang</option>
                        <option value="kadaluarsa" @selected(old('jenis_kejadian') === 'kadaluarsa')>Kedaluwarsa</option>
                    </select>
                    @error('jenis_kejadian')<p class="mt-1 text-sm text-merah-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="qty" class="mb-1 block text-sm font-medium text-ink-700">Jumlah susut</label>
                    <input id="qty" type="number" name="qty" value="{{ old('qty') }}" min="0.0001" step="0.0001" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">
                    @error('qty')<p class="mt-1 text-sm text-merah-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="catatan" class="mb-1 block text-sm font-medium text-ink-700">Keterangan (opsional)</label>
                <textarea id="catatan" name="catatan" rows="3" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm">{{ old('catatan') }}</textarea>
                @error('catatan')<p class="mt-1 text-sm text-merah-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('konsinyasi.susut.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700">Batal</a>
                <button type="submit" @disabled($items->isEmpty()) class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 disabled:opacity-50">Simpan Susut</button>
            </div>
        </form>
    </div>
</x-layouts.app>
