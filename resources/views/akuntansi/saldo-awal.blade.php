<x-layouts.app title="Input Saldo Awal" eyebrow="Akuntansi">

    {{-- ===== Header ===== --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-semibold text-ink-900">Input Saldo Awal</h1>
        <p class="mt-1 text-sm text-ink-600">
            Masukkan saldo neraca pembuka tahun buku. Berlaku untuk semua jenis entitas:
            koperasi, BUMDes, kelompok tani, kelompok pengrajin, atau UMKM.
        </p>
    </div>

    {{-- ===== Notifikasi ===== --}}
    @if(session('success'))
        <div class="mb-4 rounded border border-sawah-500 bg-sawah-100 px-4 py-3 text-sm text-sawah-600 font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded border border-merah-400 bg-merah-50 px-4 py-3 text-sm text-merah-600">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded border border-merah-400 bg-merah-50 px-4 py-3 text-sm text-merah-600">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if(! $idEntitas)
        <div class="rounded border border-padi-500 bg-padi-100 px-4 py-3 text-sm text-ink-700">
            ⚠️ Harap pilih <strong>Entitas</strong> terlebih dahulu (dari menu atas) sebelum mengisi saldo awal.
        </div>
    @else

    {{-- ===== Filter Tahun ===== --}}
    <form method="GET" action="{{ route('akuntansi.saldo-awal.index') }}"
          class="mb-6 flex flex-wrap items-end gap-3 rounded border border-paper-200 bg-paper-50 p-4">
        <div>
            <label class="block text-xs font-medium text-ink-700 mb-1">Tahun Buku</label>
            <input type="number" name="tahun" value="{{ $tahun }}" min="2000" max="2099"
                   class="rounded border border-paper-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-padi-500 w-28">
        </div>
        <button type="submit"
                class="rounded bg-paper-200 px-4 py-1.5 text-sm font-medium text-ink-700 hover:bg-paper-300 transition">
            Ganti Tahun
        </button>
    </form>

    {{-- ===== Info jika sudah ada jurnal ===== --}}
    @if($sudahAdaJurnal)
        <div class="mb-5 rounded border border-padi-500 bg-padi-100 px-4 py-3 text-sm text-ink-700">
            ℹ️ Sudah ada saldo awal untuk tahun <strong>{{ $tahun }}</strong> (Jurnal #{{ $idJurnalPembuka }}).
            Jika kamu simpan ulang, data lama otomatis diganti.
        </div>
    @endif

    {{-- ===== Panduan Konsep ===== --}}
    <div class="mb-6 rounded border border-paper-200 bg-paper-50 px-5 py-4 text-sm text-ink-700">
        <p class="font-semibold text-ink-900 mb-2">💡 Cara mengisi saldo awal</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="rounded bg-blue-50 border border-blue-200 px-3 py-2">
                <p class="font-semibold text-blue-800 mb-1">Sisi Kiri — DEBET (Aset)</p>
                <p class="text-blue-700">Apa yang dimiliki entitas: kas, bank, persediaan barang, piutang, aset tetap</p>
                <p class="text-blue-600 mt-1 italic">Contoh: Kas tunai Rp 5.000.000</p>
            </div>
            <div class="rounded bg-amber-50 border border-amber-200 px-3 py-2">
                <p class="font-semibold text-amber-800 mb-1">Sisi Kanan — KREDIT (Modal + Kewajiban)</p>
                <p class="text-amber-700">Dari mana uangnya berasal: modal awal, hutang ke supplier, simpanan anggota</p>
                <p class="text-amber-600 mt-1 italic">Contoh: Modal Awal Rp 5.000.000</p>
            </div>
        </div>
        <p class="mt-3 text-xs text-ink-500">
            <strong>Aturan:</strong> Total Debet harus = Total Kredit.
            Kamu tidak perlu pilih D/K — cukup pilih akun dan isi nilainya, sistem otomatis menentukan posisinya.
        </p>
    </div>

    {{-- ===== Form Utama ===== --}}
    <form method="POST" action="{{ route('akuntansi.saldo-awal.store') }}" id="form-saldo-awal">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

            {{-- ========== KOLOM KIRI: DEBET (Aset & Biaya) ========== --}}
            <div class="rounded border border-blue-200 bg-white shadow-sm">
                <div class="border-b border-blue-200 bg-blue-50 px-4 py-3">
                    <h2 class="text-sm font-semibold text-blue-900">Sisi Debet — Aset</h2>
                    <p class="text-xs text-blue-600 mt-0.5">Kas, Bank, Persediaan, Piutang, Aset Tetap, dll.</p>
                </div>
                <div class="p-4 space-y-2" id="tbody-debet">
                    {{-- Baris debet pertama --}}
                    <div class="baris-debet flex gap-2 items-center" data-index="0">
                        <input type="hidden" name="baris[d0][posisi]" value="D">
                        <select name="baris[d0][kode_anak]"
                                class="flex-1 rounded border border-paper-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 select-debet"
                                onchange="hitungBalance()">
                            <option value="">— Pilih akun Aset —</option>
                            @foreach($akunList->whereIn('posisi_normal', ['D'])->groupBy('kelompok') as $kel => $grup)
                                <optgroup label="{{ $kel }}">
                                    @foreach($grup as $akun)
                                        <option value="{{ $akun->kode_anak }}">{{ $akun->kode_anak }} — {{ $akun->nama_rekening }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <input type="number" name="baris[d0][nilai]" value="0" min="0" step="1"
                               class="w-36 rounded border border-paper-300 px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-blue-400 input-nilai"
                               oninput="hitungBalance()">
                        <button type="button" onclick="hapusBaris(this, 'debet')"
                                class="text-merah-400 hover:text-merah-600 text-sm px-1 transition" title="Hapus baris">✕</button>
                    </div>
                </div>
                <div class="px-4 pb-4">
                    <button type="button" id="btn-tambah-debet"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1 transition">
                        <span class="text-base leading-none">+</span> Tambah Akun Aset
                    </button>
                </div>
                <div class="border-t border-blue-100 bg-blue-50 px-4 py-2 flex justify-between text-sm">
                    <span class="text-blue-700 font-medium">Total Debet</span>
                    <span id="total-debet" class="font-mono font-bold text-blue-900">Rp 0</span>
                </div>
            </div>

            {{-- ========== KOLOM KANAN: KREDIT (Modal & Kewajiban) ========== --}}
            <div class="rounded border border-amber-200 bg-white shadow-sm">
                <div class="border-b border-amber-200 bg-amber-50 px-4 py-3">
                    <h2 class="text-sm font-semibold text-amber-900">Sisi Kredit — Modal & Kewajiban</h2>
                    <p class="text-xs text-amber-600 mt-0.5">Modal Awal, Hutang, Simpanan Anggota, dll.</p>
                </div>
                <div class="p-4 space-y-2" id="tbody-kredit">
                    {{-- Baris kredit pertama --}}
                    <div class="baris-kredit flex gap-2 items-center" data-index="0">
                        <input type="hidden" name="baris[k0][posisi]" value="K">
                        <select name="baris[k0][kode_anak]"
                                class="flex-1 rounded border border-paper-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 select-kredit"
                                onchange="hitungBalance()">
                            <option value="">— Pilih akun Modal/Kewajiban —</option>
                            @foreach($akunList->whereIn('posisi_normal', ['K'])->groupBy('kelompok') as $kel => $grup)
                                <optgroup label="{{ $kel }}">
                                    @foreach($grup as $akun)
                                        <option value="{{ $akun->kode_anak }}">{{ $akun->kode_anak }} — {{ $akun->nama_rekening }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <input type="number" name="baris[k0][nilai]" value="0" min="0" step="1"
                               class="w-36 rounded border border-paper-300 px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-amber-400 input-nilai"
                               oninput="hitungBalance()">
                        <button type="button" onclick="hapusBaris(this, 'kredit')"
                                class="text-merah-400 hover:text-merah-600 text-sm px-1 transition" title="Hapus baris">✕</button>
                    </div>
                </div>
                <div class="px-4 pb-4">
                    <button type="button" id="btn-tambah-kredit"
                            class="text-xs text-amber-600 hover:text-amber-800 font-medium flex items-center gap-1 transition">
                        <span class="text-base leading-none">+</span> Tambah Akun Modal/Kewajiban
                    </button>
                </div>
                <div class="border-t border-amber-100 bg-amber-50 px-4 py-2 flex justify-between text-sm">
                    <span class="text-amber-700 font-medium">Total Kredit</span>
                    <span id="total-kredit" class="font-mono font-bold text-amber-900">Rp 0</span>
                </div>
            </div>
        </div>

        {{-- ===== Status Balance ===== --}}
        <div id="panel-balance" class="mb-4 rounded border border-paper-200 bg-paper-50 px-4 py-3 flex items-center justify-between">
            <div class="text-sm">
                <span class="text-ink-600">Selisih Debet − Kredit: </span>
                <span id="selisih" class="font-mono font-bold text-merah-500">Rp 0</span>
            </div>
            <div id="badge-balance" class="hidden text-sm font-semibold text-sawah-600">
                ✅ Balance — siap disimpan
            </div>
            <div id="badge-tidak-balance" class="text-xs text-merah-500">
                Isi kedua sisi hingga selisih = 0
            </div>
        </div>

        {{-- ===== Tombol Aksi ===== --}}
        <div class="flex gap-3">
            <button type="submit"
                    class="rounded bg-merah-500 px-6 py-2 text-sm font-medium text-white hover:bg-merah-600 transition">
                Simpan Saldo Awal
            </button>
            <a href="{{ route('akuntansi.saldo-awal.index', ['tahun' => $tahun]) }}"
               class="rounded border border-paper-300 px-5 py-2 text-sm font-medium text-ink-700 hover:bg-paper-200 transition">
                Reset
            </a>
        </div>

    </form>

    {{-- ===== Saldo Tersimpan ===== --}}
    @if($saldoTersimpan->isNotEmpty())
        <div class="mt-8 rounded border border-paper-200 bg-white shadow-sm">
            <div class="border-b border-paper-200 px-5 py-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-ink-900">Saldo Awal Tersimpan — {{ $tahun }}</h2>
                <span class="text-xs {{ $isBalance ? 'text-sawah-600 font-semibold' : 'text-merah-500' }}">
                    {{ $isBalance ? '✅ Balance' : '⚠️ Tidak Balance' }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-paper-50">
                        <tr class="border-b border-paper-200 text-ink-500 uppercase tracking-wide">
                            <th class="px-4 py-2 text-left">Akun</th>
                            <th class="px-4 py-2 text-left">Kelompok</th>
                            <th class="px-3 py-2 text-right">Debet</th>
                            <th class="px-4 py-2 text-right">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($saldoTersimpan as $baris)
                            <tr class="border-b border-paper-100 hover:bg-paper-50">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-ink-800">{{ $baris->kode_anak }}</div>
                                    <div class="text-ink-500 text-[11px]">{{ $baris->nama_rekening }}</div>
                                </td>
                                <td class="px-4 py-2 text-ink-600">{{ $baris->kelompok }}</td>
                                <td class="px-3 py-2 text-right font-mono text-ink-700">
                                    {{ $baris->debet > 0 ? number_format($baris->debet, 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-2 text-right font-mono text-ink-700">
                                    {{ $baris->kredit > 0 ? number_format($baris->kredit, 0, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-paper-50 font-semibold border-t-2 border-paper-300">
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-ink-700">Total</td>
                            <td class="px-3 py-2 text-right font-mono {{ $isBalance ? 'text-sawah-600' : 'text-merah-500' }}">
                                {{ number_format($totalDebet, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-right font-mono {{ $isBalance ? 'text-sawah-600' : 'text-merah-500' }}">
                                {{ number_format($totalKredit, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    @endif {{-- end if idEntitas --}}

    <script>
    (function () {
        {{-- Options untuk kolom Debet (posisi_normal = D) --}}
        const optDebet = `@foreach($akunList->whereIn('posisi_normal', ['D'])->groupBy('kelompok') as $kel => $grup)<optgroup label="{{ $kel }}">@foreach($grup as $akun)<option value="{{ $akun->kode_anak }}">{{ $akun->kode_anak }} — {{ $akun->nama_rekening }}</option>@endforeach</optgroup>@endforeach`;

        {{-- Options untuk kolom Kredit (posisi_normal = K) --}}
        const optKredit = `@foreach($akunList->whereIn('posisi_normal', ['K'])->groupBy('kelompok') as $kel => $grup)<optgroup label="{{ $kel }}">@foreach($grup as $akun)<option value="{{ $akun->kode_anak }}">{{ $akun->kode_anak }} — {{ $akun->nama_rekening }}</option>@endforeach</optgroup>@endforeach`;

        let idxD = 1, idxK = 1;

        const tbodyD = document.getElementById('tbody-debet');
        const tbodyK = document.getElementById('tbody-kredit');

        function formatRp(n) {
            return 'Rp ' + Math.round(Math.abs(n)).toLocaleString('id-ID');
        }

        window.hitungBalance = function () {
            let d = 0, k = 0;
            document.querySelectorAll('.baris-debet .input-nilai').forEach(el => d += parseFloat(el.value) || 0);
            document.querySelectorAll('.baris-kredit .input-nilai').forEach(el => k += parseFloat(el.value) || 0);

            document.getElementById('total-debet').textContent  = formatRp(d);
            document.getElementById('total-kredit').textContent = formatRp(k);

            const sel = d - k;
            const selEl = document.getElementById('selisih');
            selEl.textContent = (sel >= 0 ? '+' : '−') + 'Rp ' + Math.round(Math.abs(sel)).toLocaleString('id-ID');

            const balanced = Math.abs(sel) < 1 && (d > 0 || k > 0);
            document.getElementById('badge-balance').classList.toggle('hidden', !balanced);
            document.getElementById('badge-tidak-balance').classList.toggle('hidden', balanced);
            selEl.className = balanced
                ? 'font-mono font-bold text-sawah-600'
                : 'font-mono font-bold text-merah-500';
        };

        function buatBarisDebet(i) {
            const div = document.createElement('div');
            div.className = 'baris-debet flex gap-2 items-center';
            div.dataset.index = i;
            div.innerHTML = `
                <input type="hidden" name="baris[d${i}][posisi]" value="D">
                <select name="baris[d${i}][kode_anak]"
                        class="flex-1 rounded border border-paper-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 select-debet"
                        onchange="hitungBalance()">
                    <option value="">— Pilih akun Aset —</option>
                    ${optDebet}
                </select>
                <input type="number" name="baris[d${i}][nilai]" value="0" min="0" step="1"
                       class="w-36 rounded border border-paper-300 px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-blue-400 input-nilai"
                       oninput="hitungBalance()">
                <button type="button" onclick="hapusBaris(this,'debet')"
                        class="text-merah-400 hover:text-merah-600 text-sm px-1 transition" title="Hapus">✕</button>`;
            return div;
        }

        function buatBarisKredit(i) {
            const div = document.createElement('div');
            div.className = 'baris-kredit flex gap-2 items-center';
            div.dataset.index = i;
            div.innerHTML = `
                <input type="hidden" name="baris[k${i}][posisi]" value="K">
                <select name="baris[k${i}][kode_anak]"
                        class="flex-1 rounded border border-paper-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 select-kredit"
                        onchange="hitungBalance()">
                    <option value="">— Pilih akun Modal/Kewajiban —</option>
                    ${optKredit}
                </select>
                <input type="number" name="baris[k${i}][nilai]" value="0" min="0" step="1"
                       class="w-36 rounded border border-paper-300 px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-amber-400 input-nilai"
                       oninput="hitungBalance()">
                <button type="button" onclick="hapusBaris(this,'kredit')"
                        class="text-merah-400 hover:text-merah-600 text-sm px-1 transition" title="Hapus">✕</button>`;
            return div;
        }

        document.getElementById('btn-tambah-debet').addEventListener('click', () => {
            tbodyD.appendChild(buatBarisDebet(idxD++));
        });

        document.getElementById('btn-tambah-kredit').addEventListener('click', () => {
            tbodyK.appendChild(buatBarisKredit(idxK++));
        });

        window.hapusBaris = function (btn, sisi) {
            const container = sisi === 'debet' ? tbodyD : tbodyK;
            const rows = container.querySelectorAll(sisi === 'debet' ? '.baris-debet' : '.baris-kredit');
            if (rows.length <= 1) {
                // Reset nilai ke 0 instead of removing last row
                btn.closest('div').querySelector('.input-nilai').value = 0;
                btn.closest('div').querySelector('select').value = '';
            } else {
                btn.closest('div').remove();
            }
            hitungBalance();
        };

        hitungBalance();
    })();
    </script>

</x-layouts.app>
