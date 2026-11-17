<x-layouts.app :title="$title" eyebrow="Gudang">
    @if (session('status'))
        <div class="mb-4 rounded-sm border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="mb-4">
        <a href="{{ route('gudang.index') }}" class="text-sm font-medium text-ink-700 hover:text-merah-600">
            &larr; Kembali ke Gudang
        </a>
    </div>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari batch / barang / gudang..."
                class="w-72 rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm placeholder:text-ink-600/40 focus:border-merah-400 focus:outline-none"
            >
            <select name="status" class="rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm text-ink-700 focus:border-merah-400 focus:outline-none">
                <option value="">Semua batch aktif</option>
                <option value="segera" @selected($filterStatus === 'segera')>Kedaluwarsa ≤ 30 hari</option>
                <option value="kedaluwarsa" @selected($filterStatus === 'kedaluwarsa')>Sudah kedaluwarsa</option>
                <option value="tanpa-tanggal" @selected($filterStatus === 'tanpa-tanggal')>Tanpa tanggal</option>
            </select>
            <button class="rounded-sm border border-paper-300 px-3 py-2 text-sm text-ink-700 hover:border-merah-400">
                Terapkan
            </button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-sm border border-paper-300 bg-paper-50">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead>
                <tr class="border-b border-paper-300 bg-paper-200/60 font-mono text-[11px] uppercase tracking-wide text-ink-600/70">
                    <th class="px-4 py-3 font-medium">Kode Batch</th>
                    <th class="px-4 py-3 font-medium">Barang</th>
                    <th class="px-4 py-3 font-medium">Gudang</th>
                    <th class="px-4 py-3 text-right font-medium">Saldo</th>
                    <th class="px-4 py-3 font-medium">Kedaluwarsa</th>
                    <th class="px-4 py-3 text-center font-medium">Status</th>
                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    @php
                        $expired = $item->tanggal_kedaluwarsa !== null && $item->tanggal_kedaluwarsa < now()->toDateString();
                        $nearExpiry = $item->tanggal_kedaluwarsa !== null
                            && ! $expired
                            && $item->tanggal_kedaluwarsa <= now()->addDays(30)->toDateString();
                    @endphp
                    <tr class="border-b border-paper-200 last:border-0 hover:bg-paper-100/70">
                        @php
                            $batchAwalBelumDiketahui = $item->is_stok_awal
                                && $item->tanggal_kedaluwarsa === null
                                && (float) $item->qty_on_hand > 0;
                            $oldBatchId = (string) old('source_batch_id', '');
                            $gunakanInputLama = $oldBatchId === (string) $item->id_batch;
                            $alokasiLama = $gunakanInputLama
                                ? old('batches', [['qty' => number_format((float) $item->qty_on_hand, 4, '.', '')]])
                                : [['qty' => number_format((float) $item->qty_on_hand, 4, '.', '')]];
                            $bolehKlasifikasi = auth()->user()?->hasPeran('admin_gudang')
                                || auth()->user()?->hasPeran('manajer');
                        @endphp
                        <td class="px-4 py-3 font-mono text-xs text-ink-800">
                            {{ $item->kode_batch }}
                            @if ($item->is_stok_awal)
                                <span class="mt-1 block font-sans text-[11px] text-ink-600/60">Stok awal</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-800">{{ $item->kode_barang }} · {{ $item->nama_barang }}</td>
                        <td class="px-4 py-3 text-ink-700">{{ $item->nama_gudang }}</td>
                        <td class="px-4 py-3 text-right font-medium text-ink-800">
                            {{ rtrim(rtrim(number_format((float) $item->qty_on_hand, 4, ',', '.'), '0'), ',') }}
                            {{ $item->kode_satuan }}
                        </td>
                        <td class="px-4 py-3 text-ink-700">
                            {{ $item->tanggal_kedaluwarsa ? \Illuminate\Support\Carbon::parse($item->tanggal_kedaluwarsa)->format('d M Y') : 'Tidak diketahui' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($item->tanggal_kedaluwarsa === null)
                                <span class="rounded-sm bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Tanggal belum diketahui</span>
                            @elseif ($expired)
                                <span class="rounded-sm bg-merah-100 px-2 py-1 text-xs font-medium text-merah-700">Kedaluwarsa</span>
                            @elseif ($nearExpiry)
                                <span class="rounded-sm bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Segera kedaluwarsa</span>
                            @else
                                <span class="rounded-sm bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right align-top">
                            @if ($batchAwalBelumDiketahui && $bolehKlasifikasi)
                                <details @if($gunakanInputLama) open @endif class="min-w-64 text-left">
                                    <summary class="cursor-pointer text-xs font-semibold text-merah-700 hover:text-merah-800">Isi tanggal / bagi batch</summary>
                                    <form method="POST" action="{{ route('gudang.batch.klasifikasikan-stok-awal', $item->id_batch) }}"
                                        class="klasifikasiBatchForm mt-3 space-y-3 rounded-sm border border-paper-300 bg-white p-3"
                                        data-target="{{ number_format((float) $item->qty_on_hand, 4, '.', '') }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="source_batch_id" value="{{ $item->id_batch }}">
                                        <p class="text-[11px] text-ink-600">
                                            Bagi saldo {{ rtrim(rtrim(number_format((float) $item->qty_on_hand, 4, ',', '.'), '0'), ',') }} {{ $item->kode_satuan }} sesuai jumlah fisik untuk tiap tanggal. Total stok tidak berubah.
                                        </p>
                                        <div class="alokasiBatchRows space-y-2">
                                            @foreach ($alokasiLama as $index => $alokasi)
                                                <div class="alokasiBatchRow grid gap-2 rounded-sm bg-paper-50 p-2" data-allocation-row>
                                                    <label class="text-[11px] font-medium text-ink-700">Kode batch (boleh kosong)
                                                        <input type="text" name="batches[{{ $index }}][kode_batch]" maxlength="100" value="{{ $alokasi['kode_batch'] ?? '' }}" class="mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs">
                                                    </label>
                                                    <label class="text-[11px] font-medium text-ink-700">Tanggal kedaluwarsa
                                                        <input type="date" name="batches[{{ $index }}][tanggal_kedaluwarsa]" value="{{ $alokasi['tanggal_kedaluwarsa'] ?? '' }}" required class="mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs">
                                                    </label>
                                                    <label class="text-[11px] font-medium text-ink-700">Jumlah ({{ $item->kode_satuan }})
                                                        <input type="number" name="batches[{{ $index }}][qty]" min="0.0001" step="0.0001" value="{{ $alokasi['qty'] ?? '' }}" required class="alokasiBatchQty mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs">
                                                    </label>
                                                    <button type="button" class="hapusAlokasiBatch justify-self-end text-[11px] font-medium text-merah-700 hover:text-merah-800">Hapus baris</button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="button" class="tambahAlokasiBatch rounded-sm border border-paper-300 px-2 py-1.5 text-xs font-medium text-ink-700 hover:border-merah-400">+ Tambah tanggal lain</button>
                                        <p class="totalAlokasiBatch text-[11px] font-semibold text-ink-700"></p>
                                        <button type="submit" class="w-full rounded-sm bg-merah-600 px-3 py-2 text-xs font-semibold text-white hover:bg-merah-700">Simpan pembagian batch</button>
                                    </form>
                                </details>
                            @elseif ($batchAwalBelumDiketahui)
                                <span class="text-xs text-ink-500">Perlu Admin Gudang / Manajer</span>
                            @else
                                <span class="text-xs text-ink-500">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-600/60">
                            Tidak ada batch dengan filter tersebut.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>

    <script>
        document.querySelectorAll('.klasifikasiBatchForm').forEach(form => {
            const rows = form.querySelector('.alokasiBatchRows');
            const totalLabel = form.querySelector('.totalAlokasiBatch');
            const target = Number(form.dataset.target) || 0;
            let nextIndex = [...rows.querySelectorAll('input[name]')]
                .map(input => Number(input.name.match(/^batches\[(\d+)\]/)?.[1] ?? -1))
                .reduce((maximum, index) => Math.max(maximum, index), -1) + 1;

            function hitungTotal() {
                const total = [...rows.querySelectorAll('.alokasiBatchQty')]
                    .reduce((sum, input) => sum + (Number(input.value) || 0), 0);
                totalLabel.textContent = `Total pembagian: ${total.toLocaleString('id-ID', { maximumFractionDigits: 4 })} / ${target.toLocaleString('id-ID', { maximumFractionDigits: 4 })}`;
                totalLabel.classList.toggle('text-merah-700', Math.abs(total - target) >= 0.00005);
                totalLabel.classList.toggle('text-emerald-700', Math.abs(total - target) < 0.00005);
            }

            function tambahBaris() {
                const index = nextIndex++;
                const row = document.createElement('div');
                row.className = 'alokasiBatchRow grid gap-2 rounded-sm bg-paper-50 p-2';
                row.setAttribute('data-allocation-row', '');
                row.innerHTML = `
                    <label class="text-[11px] font-medium text-ink-700">Kode batch (boleh kosong)
                        <input type="text" name="batches[${index}][kode_batch]" maxlength="100" class="mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs">
                    </label>
                    <label class="text-[11px] font-medium text-ink-700">Tanggal kedaluwarsa
                        <input type="date" name="batches[${index}][tanggal_kedaluwarsa]" required class="mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs">
                    </label>
                    <label class="text-[11px] font-medium text-ink-700">Jumlah
                        <input type="number" name="batches[${index}][qty]" min="0.0001" step="0.0001" required class="alokasiBatchQty mt-1 w-full rounded-sm border border-paper-300 px-2 py-1.5 text-xs">
                    </label>
                    <button type="button" class="hapusAlokasiBatch justify-self-end text-[11px] font-medium text-merah-700 hover:text-merah-800">Hapus baris</button>
                `;
                rows.appendChild(row);
                row.querySelector('.alokasiBatchQty').addEventListener('input', hitungTotal);
                row.querySelector('.hapusAlokasiBatch').addEventListener('click', () => {
                    row.remove();
                    hitungTotal();
                });
                hitungTotal();
            }

            rows.querySelectorAll('.alokasiBatchQty').forEach(input => input.addEventListener('input', hitungTotal));
            rows.querySelectorAll('.hapusAlokasiBatch').forEach(button => button.addEventListener('click', () => {
                if (rows.querySelectorAll('[data-allocation-row]').length <= 1) return;
                button.closest('[data-allocation-row]').remove();
                hitungTotal();
            }));
            form.querySelector('.tambahAlokasiBatch').addEventListener('click', tambahBaris);
            form.addEventListener('submit', event => {
                const total = [...rows.querySelectorAll('.alokasiBatchQty')]
                    .reduce((sum, input) => sum + (Number(input.value) || 0), 0);
                if (Math.abs(total - target) >= 0.00005) {
                    event.preventDefault();
                    alert('Total pembagian batch harus sama dengan saldo awal.');
                    return;
                }
                if (!confirm('Simpan tanggal dan pembagian batch ini? Jumlah stok tetap, tetapi urutan FEFO untuk pengeluaran berikutnya akan mengikuti tanggal yang dicatat.')) {
                    event.preventDefault();
                }
            });
            hitungTotal();
        });
    </script>
</x-layouts.app>
