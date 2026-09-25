<x-layouts.app :title="$title" eyebrow="Konsinyasi">
    <div class="mb-4">
        <a href="{{ route('konsinyasi.pengiriman.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Pengiriman Konsinyasi
        </a>
    </div>

    <div class="mx-auto max-w-6xl rounded-sm border border-paper-300 bg-paper-50 p-6">
        <form method="POST" action="{{ route('konsinyasi.pengiriman.store') }}" class="space-y-6">
            @csrf

            <div>
                <h2 class="mb-3 font-display text-base font-semibold text-ink-900">Informasi Pengiriman</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Kode Kiriman</label>
                        <input type="text" name="kode_kiriman" value="{{ old('kode_kiriman', 'KRM-' . now()->format('ymdHis')) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Tanggal Kirim</label>
                        <input type="date" name="tgl_kirim" value="{{ old('tgl_kirim', now()->format('Y-m-d')) }}" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Desa Pemilik</label>
                        <select id="koperasiPemilik" name="id_koperasi_pemilik" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih desa pemilik</option>
                            @foreach ($koperasi as $item)
                                <option value="{{ $item->id_koperasi }}" {{ old('id_koperasi_pemilik') == $item->id_koperasi ? 'selected' : '' }}>{{ $item->nama_koperasi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Desa Penerima</label>
                        <select id="koperasiPenerima" name="id_koperasi_penerima" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih desa penerima</option>
                            @foreach ($koperasi as $item)
                                <option value="{{ $item->id_koperasi }}" {{ old('id_koperasi_penerima') == $item->id_koperasi ? 'selected' : '' }}>{{ $item->nama_koperasi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Gudang Asal</label>
                        <select id="gudangAsal" name="id_gudang_asal" required disabled class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih desa pemilik terlebih dahulu</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Gudang Tujuan</label>
                        <select id="gudangTujuan" name="id_gudang_tujuan" required disabled class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="">Pilih desa penerima terlebih dahulu</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Model Imbalan</label>
                        <select name="model_imbalan" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="selisih_harga" {{ old('model_imbalan', 'selisih_harga') === 'selisih_harga' ? 'selected' : '' }}>Selisih harga</option>
                            <option value="komisi_persen" {{ old('model_imbalan') === 'komisi_persen' ? 'selected' : '' }}>Komisi persen</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Persen Komisi</label>
                        <input type="number" name="persen_komisi" value="{{ old('persen_komisi', 0) }}" min="0" max="100" step="0.01" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Batas Waktu Titipan</label>
                        <input type="date" name="tgl_batas_titip" value="{{ old('tgl_batas_titip') }}" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-700">Penanggung Susut</label>
                        <select name="penanggung_susut" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">
                            <option value="pemilik" {{ old('penanggung_susut', 'pemilik') === 'pemilik' ? 'selected' : '' }}>Pemilik</option>
                            <option value="penerima" {{ old('penanggung_susut') === 'penerima' ? 'selected' : '' }}>Penerima</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink-700">Catatan</label>
                        <textarea name="catatan_pengiriman" rows="2" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-merah-400 focus:outline-none">{{ old('catatan_pengiriman') }}</textarea>
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-display text-base font-semibold text-ink-900">Barang Titipan</h2>
                    <button type="button" onclick="tambahBaris()" class="rounded-sm bg-sawah-500 px-3 py-1.5 text-xs font-medium text-paper-50 hover:bg-sawah-600">+ Tambah Barang</button>
                </div>
                <div class="overflow-x-auto rounded-sm border border-paper-300">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                            <th class="px-3 py-2 font-medium">Barang</th><th class="px-3 py-2 font-medium">Qty Dasar</th><th class="px-3 py-2 font-medium">Harga Titip</th><th class="px-3 py-2 font-medium">Harga Jual Saran</th><th class="px-3 py-2 text-center font-medium">Aksi</th>
                        </tr></thead>
                        <tbody id="itemRows"><tr class="barangRow border-b border-paper-200 last:border-0">
                            <td class="px-3 py-2"><select name="items[0][id_barang]" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs"><option value="">Pilih barang</option>@foreach ($barang as $item)<option value="{{ $item->id_barang }}">{{ $item->kode_barang }} - {{ $item->nama_barang }}</option>@endforeach</select></td>
                            <td class="px-3 py-2"><input type="number" name="items[0][qty_dasar]" value="1" min="0.0001" step="0.0001" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs"></td>
                            <td class="px-3 py-2"><input type="number" name="items[0][harga_titip_satuan]" value="0" min="0" step="0.01" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs"></td>
                            <td class="px-3 py-2"><input type="number" name="items[0][harga_jual_saran]" value="0" min="0" step="0.01" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs"></td>
                            <td class="px-3 py-2 text-center"><button type="button" onclick="hapusBaris(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button></td>
                        </tr></tbody>
                    </table>
                </div>
            </div>

            @if ($errors->any())<div class="rounded-sm border border-merah-500/40 bg-merah-50 px-3 py-2 text-sm text-merah-700"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="flex items-center justify-end gap-3"><a href="{{ route('konsinyasi.pengiriman.index') }}" class="rounded-sm border border-paper-300 px-4 py-2 text-sm text-ink-700 hover:border-merah-400">Batal</a><button type="submit" class="rounded-sm bg-merah-500 px-4 py-2 text-sm font-medium text-paper-50 hover:bg-merah-600">Simpan Draft</button></div>
        </form>
    </div>

    <script>
        let rowCount = 1;
        const barangOptions = `@foreach ($barang as $item)<option value="{{ $item->id_barang }}">{{ $item->kode_barang }} - {{ $item->nama_barang }}</option>@endforeach`;
        function tambahBaris() { document.getElementById('itemRows').insertAdjacentHTML('beforeend', `<tr class="barangRow border-b border-paper-200 last:border-0"><td class="px-3 py-2"><select name="items[${rowCount}][id_barang]" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs"><option value="">Pilih barang</option>${barangOptions}</select></td><td class="px-3 py-2"><input type="number" name="items[${rowCount}][qty_dasar]" value="1" min="0.0001" step="0.0001" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs"></td><td class="px-3 py-2"><input type="number" name="items[${rowCount}][harga_titip_satuan]" value="0" min="0" step="0.01" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs"></td><td class="px-3 py-2"><input type="number" name="items[${rowCount}][harga_jual_saran]" value="0" min="0" step="0.01" class="w-full rounded-sm border border-paper-300 bg-paper-50 px-2 py-1.5 text-xs"></td><td class="px-3 py-2 text-center"><button type="button" onclick="hapusBaris(this)" class="text-xs text-merah-600 hover:text-merah-700">Hapus</button></td></tr>`); rowCount++; }
        function hapusBaris(button) { const rows = document.querySelectorAll('.barangRow'); if (rows.length > 1) button.closest('tr').remove(); }

        const gudangData = @json($gudangData);
        const oldGudangAsal = @json(old('id_gudang_asal'));
        const oldGudangTujuan = @json(old('id_gudang_tujuan'));

        function isiGudang(select, koperasiId, placeholder, excludedId = null, selectedId = null) {
            select.innerHTML = `<option value="">${placeholder}</option>`;
            const pilihan = gudangData.filter(gudang => String(gudang.koperasi) === String(koperasiId) && String(gudang.id) !== String(excludedId));

            pilihan.forEach(gudang => {
                const option = new Option(gudang.nama, gudang.id);
                if (String(gudang.id) === String(selectedId)) option.selected = true;
                select.add(option);
            });

            select.disabled = pilihan.length === 0;
        }

        function filterGudang() {
            const pemilik = document.getElementById('koperasiPemilik').value;
            const penerima = document.getElementById('koperasiPenerima').value;
            const asal = document.getElementById('gudangAsal');
            const tujuan = document.getElementById('gudangTujuan');
            const selectedAsal = asal.value || oldGudangAsal;
            const selectedTujuan = tujuan.value || oldGudangTujuan;

            isiGudang(asal, pemilik, 'Pilih gudang asal', null, selectedAsal);
            const asalId = asal.value;
            isiGudang(tujuan, penerima, 'Pilih gudang tujuan', asalId, selectedTujuan);
        }

        document.getElementById('koperasiPemilik').addEventListener('change', filterGudang);
        document.getElementById('koperasiPenerima').addEventListener('change', filterGudang);
        filterGudang();
    </script>
</x-layouts.app>
