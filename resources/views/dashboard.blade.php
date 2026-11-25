<x-layouts.app title="Dasbor Utama Sistem & Simulator Peran" eyebrow="Ekosistem Koperasi Desa Merah Putih (KDMP)">
    @php
        $user = auth()->user();
        $entitasAktifId = $user?->id_entitas ?? session('entitas_aktif_pilihan') ?? $daftarEntitas->first()?->id_entitas;
        $entitasAktifObj = $daftarEntitas->firstWhere('id_entitas', $entitasAktifId);
        $tabAwal = request('tab', 'alur');
    @endphp

    <div
        x-data="{
            tabAktif: '{{ $tabAwal }}',
            subViewAlur: 'e2e',
            selectedTahap: {{ max(1, min(9, (int) request('tahap', 1))) }},
            selectedRole: '{{ $peranAktif }}',
            searchKomoditas: '',
            kategoriKomoditas: 'all',
            filterWilayahNeraca: 'all'
        }"
        class="space-y-6"
    >
        {{-- 1. CONTROL BAR: DROPDOWN PERAN DEMO & STATUS LOGIN INTERNAL --}}
        <div class="rounded-2xl bg-[#14170f] text-white p-5 sm:p-6 shadow-xl border border-ink-800" style="background: linear-gradient(135deg, #14170f 0%, #1c211a 60%, #262d22 100%);">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2 rounded-full bg-padi-500/20 border border-padi-500/40 px-3 py-1 text-[11px] font-mono uppercase tracking-wider text-padi-200 font-semibold">
                        🎯 Simulator Peran &amp; Dasbor Koperasi Desa
                    </div>
                    <h2 class="font-display text-xl sm:text-2xl font-bold text-white">
                        Dasbor Ekosistem Koperasi Desa (KDMP)
                    </h2>
                    <p class="text-xs sm:text-sm text-paper-200 max-w-3xl leading-relaxed">
                        Pilih peran pada dropdown di bawah untuk <strong>beralih akun &amp; login otomatis</strong> serta menyinkronkan hak akses menu sidebar secara instan.
                    </p>
                </div>

                {{-- Form Switch Peran + Entitas (High Contrast Dropdown) --}}
                <form action="{{ route('demo.switch-peran') }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 bg-black/50 p-4 rounded-xl border border-white/20 shrink-0 shadow-lg">
                    @csrf
                    <input type="hidden" name="mode" value="login">

                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-padi-300 font-bold mb-1">
                            1. Pilih Entitas Desa
                        </label>
                        <select
                            name="id_entitas"
                            class="w-full sm:w-60 rounded-lg border border-paper-300 bg-white px-3 py-2 text-xs font-bold text-ink-950 focus:border-padi-500 focus:outline-none shadow-xs"
                            style="background-color: #ffffff !important; color: #0f172a !important;"
                        >
                            @foreach($daftarEntitas as $ent)
                                <option
                                    value="{{ $ent->id_entitas }}"
                                    @selected((int) $entitasAktifId === (int) $ent->id_entitas)
                                    class="text-ink-950 bg-white font-semibold"
                                    style="background-color: #ffffff !important; color: #0f172a !important;"
                                >
                                    {{ $ent->kode_entitas }} — {{ $ent->wilayah?->nama ?? $ent->nama_entitas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-padi-300 font-bold mb-1">
                            2. Pilih Peran Demo (Auto-Login)
                        </label>
                        <select
                            name="peran"
                            onchange="this.form.submit()"
                            class="w-full sm:w-72 rounded-lg border-2 border-padi-500 bg-white px-3 py-2 text-xs font-bold text-ink-950 focus:border-padi-600 focus:outline-none shadow-xs"
                            style="background-color: #ffffff !important; color: #0f172a !important;"
                        >
                            @foreach($katalogPeran as $kode => $info)
                                <option
                                    value="{{ $kode }}"
                                    @selected($peranAktif === $kode)
                                    class="text-ink-950 bg-white font-bold"
                                    style="background-color: #ffffff !important; color: #0f172a !important;"
                                >
                                    {{ $info['ikon'] }} {{ $info['urutan'] }}. {{ $info['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="rounded-lg bg-padi-500 hover:bg-padi-600 px-4 py-2 text-xs font-bold text-ink-950 transition-colors shadow-sm cursor-pointer whitespace-nowrap">
                        Terapkan
                    </button>
                </form>
            </div>

            {{-- Status Login Saat Ini --}}
            <div class="mt-4 pt-4 border-t border-white/15 flex flex-wrap items-center justify-between gap-3 text-xs text-paper-200">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-paper-300 font-medium">Sedang Login:</span>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-white/20 px-2.5 py-1 font-bold text-white border border-white/20">
                        👤 {{ $user?->nama }} ({{ $user?->email }})
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-padi-500/25 border border-padi-500/40 px-2.5 py-1 font-bold text-padi-200">
                        🏘️ {{ $user?->isSuperAdmin() ? ('Super Admin Pusat' . ($entitasAktifObj ? ' • Entitas: ' . $entitasAktifObj->nama_entitas : '')) : ($user?->entitas?->nama_entitas ?? 'Entitas Desa') }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/20 border border-emerald-500/30 px-2.5 py-1 font-semibold text-emerald-200">
                        Peran: {{ $katalogPeran[$peranAktif]['nama'] ?? 'Pengguna' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 2. METRIK UTAMA EKOSISTEM (KPI STRIP) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="rounded-xl border border-paper-300 bg-white p-3.5 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-mono uppercase text-ink-500 font-semibold">Koperasi Desa</span>
                    <span class="text-base">🏘️</span>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-ink-950">{{ $ringkasan['jumlah_entitas'] }}</div>
                <div class="text-[11px] text-ink-600 mt-0.5">Entitas Terdaftar</div>
            </div>

            <div class="rounded-xl border border-paper-300 bg-white p-3.5 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-mono uppercase text-ink-500 font-semibold">Komoditas</span>
                    <span class="text-base">🌾</span>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-ink-950">{{ $ringkasan['jumlah_komoditas'] }}</div>
                <div class="text-[11px] text-ink-600 mt-0.5">Katalog Pangan Desa</div>
            </div>

            <div class="rounded-xl border border-paper-300 bg-white p-3.5 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-mono uppercase text-emerald-600 font-semibold">Surplus Pangan</span>
                    <span class="text-base">📈</span>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-emerald-700">{{ $ringkasan['total_surplus'] }}</div>
                <div class="text-[11px] text-emerald-800 mt-0.5">Komoditas Berlebih</div>
            </div>

            <div class="rounded-xl border border-paper-300 bg-white p-3.5 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-mono uppercase text-rose-600 font-semibold">Defisit Pangan</span>
                    <span class="text-base">📉</span>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-rose-700">{{ $ringkasan['total_defisit'] }}</div>
                <div class="text-[11px] text-rose-800 mt-0.5">Kebutuhan Pengadaan</div>
            </div>

            <div class="rounded-xl border border-paper-300 bg-white p-3.5 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-mono uppercase text-ink-500 font-semibold">Barang Gudang</span>
                    <span class="text-base">📦</span>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-ink-950">{{ $ringkasan['jumlah_barang'] }}</div>
                <div class="text-[11px] text-ink-600 mt-0.5">SKU Tercatat</div>
            </div>

            <div class="rounded-xl border border-paper-300 bg-white p-3.5 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-mono uppercase text-ink-500 font-semibold">Transaksi POS</span>
                    <span class="text-base">🧾</span>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-ink-950">{{ $ringkasan['jumlah_penjualan'] }}</div>
                <div class="text-[11px] text-ink-600 mt-0.5">Penjualan Kasir</div>
            </div>
        </div>

        {{-- 3. TAB NAVIGASI DASBOR UTAMA --}}
        <div class="border-b border-paper-300 flex items-center gap-2 overflow-x-auto pb-1 text-xs">
            <button
                type="button"
                @click="tabAktif = 'alur'"
                :class="tabAktif === 'alur' ? 'border-b-2 border-padi-500 text-ink-950 font-bold bg-paper-100/80' : 'text-ink-600 hover:text-ink-900'"
                class="px-4 py-2.5 rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer"
            >
                <span>🎯</span>
                <span>Alur Kerja &amp; Simulator Peran</span>
            </button>

            <button
                type="button"
                @click="tabAktif = 'katalog'"
                :class="tabAktif === 'katalog' ? 'border-b-2 border-padi-500 text-ink-950 font-bold bg-paper-100/80' : 'text-ink-600 hover:text-ink-900'"
                class="px-4 py-2.5 rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer"
            >
                <span>🌾</span>
                <span>Katalog Komoditas Desa</span>
                <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[10px] text-ink-700">{{ $daftarKomoditas->count() }}</span>
            </button>

            <button
                type="button"
                @click="tabAktif = 'neraca'"
                :class="tabAktif === 'neraca' ? 'border-b-2 border-padi-500 text-ink-950 font-bold bg-paper-100/80' : 'text-ink-600 hover:text-ink-900'"
                class="px-4 py-2.5 rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer"
            >
                <span>⚖️</span>
                <span>Ketahanan Pangan Antar-Desa (Surplus / Defisit)</span>
                <span class="rounded bg-emerald-100 text-emerald-800 px-1.5 py-0.5 font-mono text-[10px]">{{ $ringkasan['total_surplus'] }} S</span>
                <span class="rounded bg-rose-100 text-rose-800 px-1.5 py-0.5 font-mono text-[10px]">{{ $ringkasan['total_defisit'] }} D</span>
            </button>

            <button
                type="button"
                @click="tabAktif = 'entitas'"
                :class="tabAktif === 'entitas' ? 'border-b-2 border-padi-500 text-ink-950 font-bold bg-paper-100/80' : 'text-ink-600 hover:text-ink-900'"
                class="px-4 py-2.5 rounded-t-lg transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer"
            >
                <span>🏘️</span>
                <span>Direktori Koperasi Terdaftar</span>
                <span class="rounded bg-paper-200 px-1.5 py-0.5 font-mono text-[10px] text-ink-700">{{ $daftarEntitas->count() }}</span>
            </button>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 1: ALUR USER FLOW END-TO-END & WEWENANG PERAN                        --}}
        {{-- ========================================================================= --}}
        <div x-show="tabAktif === 'alur'" x-cloak class="space-y-6">
            @php
                $pKas = (int) ($progressE2e['kas_bank'] ?? 0);
                $pBarang = (int) ($progressE2e['barang'] ?? 0);
                $pSaldo = (int) ($progressE2e['saldo_awal'] ?? 0);
                $pSurvei = (int) ($progressE2e['sesi_survei'] ?? 0);
                $pKalkulasi = (int) ($progressE2e['hasil_kalkulasi'] ?? 0);
                $pPr = (int) ($progressE2e['pr'] ?? 0);
                $pBeli = (int) ($progressE2e['pembelian'] ?? 0);
                $pGrn = (int) ($progressE2e['penerimaan'] ?? 0);
                $pStok = (int) ($progressE2e['stok_tersedia'] ?? 0);
                $pJual = (int) ($progressE2e['penjualan'] ?? 0);
                $pKonsinyasi = (int) ($progressE2e['konsinyasi'] ?? 0);
                $pRusak = (int) ($progressE2e['kerusakan'] ?? 0);
                $pOpname = (int) ($progressE2e['opname'] ?? 0);
                $pJurnal = (int) ($progressE2e['jurnal_posted'] ?? 0);
                $pOutbox = (int) ($progressE2e['outbox_pending'] ?? 0);
                $pPiutang = (int) ($progressE2e['piutang_terbuka'] ?? 0);
                $pHutang = (int) ($progressE2e['hutang_terbuka'] ?? 0);
                $pClosed = (int) ($progressE2e['periode_closed'] ?? 0);
                $pShu = (int) ($progressE2e['config_shu'] ?? 0);

                $statusTahap = [
                    1 => [
                        'done' => ($pKas > 0 && $pBarang > 0 && $pSaldo > 0),
                        'partial' => ($pKas > 0 || $pBarang > 0 || $pSaldo > 0),
                        'badge' => ($pKas > 0 && $pBarang > 0 && $pSaldo > 0) ? '✓ Siap' : (($pKas > 0 || $pBarang > 0 || $pSaldo > 0) ? '⏳ Sebagian' : '○ Belum'),
                        'alert' => false,
                        'ringkasan' => "Barang {$pBarang} • Kas {$pKas} • Saldo {$pSaldo}",
                    ],
                    2 => [
                        'done' => ($pSurvei > 0),
                        'partial' => false,
                        'badge' => ($pSurvei > 0) ? '✓ Ada Data' : '○ Belum',
                        'alert' => false,
                        'ringkasan' => "Sesi Survei: {$pSurvei}",
                    ],
                    3 => [
                        'done' => ($pKalkulasi > 0 && $pPr > 0),
                        'partial' => ($pKalkulasi > 0 || $pPr > 0),
                        'badge' => ($pKalkulasi > 0 && $pPr > 0) ? '✓ Lengkap' : (($pKalkulasi > 0 || $pPr > 0) ? '⏳ Proses' : '○ Belum'),
                        'alert' => false,
                        'ringkasan' => "Neraca {$pKalkulasi} • PR {$pPr}",
                    ],
                    4 => [
                        'done' => ($pBeli > 0),
                        'partial' => false,
                        'badge' => ($pBeli > 0) ? '✓ Ada PO' : '○ Belum',
                        'alert' => false,
                        'ringkasan' => "Pembelian: {$pBeli}",
                    ],
                    5 => [
                        'done' => ($pGrn > 0 && $pStok > 0),
                        'partial' => ($pGrn > 0 || $pStok > 0),
                        'badge' => ($pGrn > 0 && $pStok > 0) ? '✓ Masuk Stok' : (($pGrn > 0 || $pStok > 0) ? '⏳ Parsial' : '○ Belum'),
                        'alert' => false,
                        'ringkasan' => "GRN {$pGrn} • Batch {$pStok}",
                    ],
                    6 => [
                        'done' => ($pJual > 0),
                        'partial' => ($pKonsinyasi > 0),
                        'badge' => ($pJual > 0) ? '✓ Berjalan' : (($pKonsinyasi > 0) ? '⏳ Konsinyasi' : '○ Belum'),
                        'alert' => false,
                        'ringkasan' => "POS {$pJual} • Konsinyasi {$pKonsinyasi}",
                    ],
                    7 => [
                        'done' => ($pOpname > 0 || $pRusak > 0),
                        'partial' => false,
                        'badge' => ($pOpname > 0 || $pRusak > 0) ? '✓ Tercatat' : '○ Opsional',
                        'alert' => false,
                        'ringkasan' => "Opname {$pOpname} • Rusak {$pRusak}",
                    ],
                    8 => [
                        'done' => ($pJurnal > 0 && $pOutbox === 0),
                        'partial' => ($pJurnal > 0 && $pOutbox > 0),
                        'badge' => ($pOutbox > 0) ? "⚠ {$pOutbox} Outbox" : (($pJurnal > 0) ? '✓ Terposting' : '○ Belum'),
                        'alert' => ($pOutbox > 0),
                        'ringkasan' => "Jurnal {$pJurnal} • Outbox {$pOutbox}",
                    ],
                    9 => [
                        'done' => ($pClosed > 0 && $pShu > 0),
                        'partial' => ($pClosed > 0 || $pShu > 0),
                        'badge' => ($pClosed > 0 && $pShu > 0) ? '✓ Ditutup' : (($pClosed > 0 || $pShu > 0) ? '⏳ Siap Tutup' : '○ Terbuka'),
                        'alert' => false,
                        'ringkasan' => "Closed {$pClosed} • SHU {$pShu}",
                    ],
                ];

                $tahapSelesaiCount = collect($statusTahap)->where('done', true)->count();
            @endphp

            {{-- Header + Mode Switch (User Flow E2E vs RBAC 7 Peran) --}}
            <div class="rounded-2xl border border-paper-300 bg-white p-4 sm:p-5 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-[#14170f] px-2.5 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider text-padi-300">
                            Alur Bisnis End-to-End
                        </span>
                        <span class="rounded-md bg-emerald-100 border border-emerald-300 px-2.5 py-0.5 font-mono text-[10px] font-bold text-emerald-900">
                            {{ $tahapSelesaiCount }} / 9 Tahap Terpenuhi ({{ $entitasAktifObj?->kode_entitas ?? 'Entitas' }})
                        </span>
                    </div>
                    <h3 class="font-display text-base sm:text-lg font-bold text-ink-950">
                        Alur Operasional End-to-End: Inisialisasi Master &amp; Saldo Awal hingga Tutup Bulan / Tahun (SHU)
                    </h3>
                    <p class="text-xs text-ink-700">
                        Ikuti Tahap 1 sampai Tahap 9 secara berurutan agar data stok Moving Average, outbox jurnal gudang, dan laporan keuangan terkonsolidasi 100% sesuai panduan.
                    </p>
                </div>

                <div class="inline-flex rounded-xl bg-paper-100 p-1 border border-paper-300 shrink-0 self-start lg:self-center">
                    <button
                        type="button"
                        @click="subViewAlur = 'e2e'"
                        :class="subViewAlur === 'e2e' ? 'bg-[#14170f] text-white shadow-xs' : 'text-ink-700 hover:text-ink-950'"
                        class="rounded-lg px-3.5 py-2 text-xs font-bold transition-colors cursor-pointer whitespace-nowrap"
                    >
                        🧭 Alur Bisnis E2E (Tahap 1–9)
                    </button>
                    <button
                        type="button"
                        @click="subViewAlur = 'peran'"
                        :class="subViewAlur === 'peran' ? 'bg-[#14170f] text-white shadow-xs' : 'text-ink-700 hover:text-ink-950'"
                        class="rounded-lg px-3.5 py-2 text-xs font-bold transition-colors cursor-pointer whitespace-nowrap"
                    >
                        🔐 Wewenang 7 Peran (RBAC)
                    </button>
                </div>
            </div>

            {{-- SUB-VIEW A: 9 TAHAP USER FLOW END-TO-END --}}
            <div x-show="subViewAlur === 'e2e'" x-cloak class="space-y-6">
                {{-- Stepper 9 Tahap --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-9 gap-2.5">
                    @foreach($alurE2e as $tahapNum => $tahapInfo)
                        @php
                            $st = $statusTahap[$tahapNum] ?? ['done' => false, 'partial' => false, 'alert' => false, 'badge' => '○ Belum', 'ringkasan' => ''];
                            $badgeClass = $st['alert']
                                ? 'bg-amber-100 text-amber-900 border-amber-300'
                                : ($st['done']
                                    ? 'bg-emerald-100 text-emerald-900 border-emerald-300'
                                    : ($st['partial']
                                        ? 'bg-sky-100 text-sky-900 border-sky-300'
                                        : 'bg-paper-100 text-ink-700 border-paper-300'));
                        @endphp
                        <button
                            type="button"
                            @click="selectedTahap = {{ $tahapNum }}"
                            :class="selectedTahap === {{ $tahapNum }}
                                ? 'ring-2 ring-padi-500 bg-[#1c211a] text-white shadow-md -translate-y-0.5 border-[#1c211a]'
                                : 'bg-white text-ink-950 hover:bg-paper-100 border-paper-300'"
                            class="rounded-xl border p-3 text-left transition-all duration-150 flex flex-col justify-between gap-2 cursor-pointer relative"
                        >
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-lg">{{ $tahapInfo['ikon'] }}</span>
                                <span class="font-mono text-[10px] font-bold uppercase tracking-wider"
                                      :class="selectedTahap === {{ $tahapNum }} ? 'text-padi-300' : 'text-ink-600'">
                                    Tahap {{ $tahapNum }}
                                </span>
                            </div>

                            <div>
                                <p class="font-display text-xs font-bold leading-snug line-clamp-2"
                                   :class="selectedTahap === {{ $tahapNum }} ? 'text-white' : 'text-ink-950'">
                                    {{ $tahapInfo['judul_singkat'] }}
                                </p>
                            </div>

                            <div class="pt-1 border-t"
                                 :class="selectedTahap === {{ $tahapNum }} ? 'border-white/15' : 'border-paper-200'">
                                <span class="inline-flex items-center rounded border px-1.5 py-0.5 font-mono text-[9px] font-bold {{ $badgeClass }}">
                                    {{ $st['badge'] }}
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Detail Tahap Terpilih --}}
                @foreach($alurE2e as $tahapNum => $tahapInfo)
                    @php
                        $st = $statusTahap[$tahapNum] ?? ['done' => false, 'partial' => false, 'alert' => false, 'badge' => '○ Belum', 'ringkasan' => ''];
                        $hasStageAccess = $user && ($user->isSuperAdmin() || $user->hasPeran('manajer') || $user->hasPeran($tahapInfo['peran_kode']));
                        $prevTahap = $tahapNum > 1 ? $tahapNum - 1 : null;
                        $nextTahap = $tahapNum < count($alurE2e) ? $tahapNum + 1 : null;
                    @endphp
                    <div
                        x-show="selectedTahap === {{ $tahapNum }}"
                        x-cloak
                        class="rounded-2xl border border-paper-300 bg-white p-5 sm:p-6 shadow-sm space-y-6"
                    >
                        {{-- Header Detail Tahap --}}
                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 border-b border-paper-200 pb-5">
                            <div class="flex items-start gap-4">
                                <span class="text-3xl sm:text-4xl p-3 rounded-xl bg-paper-100 border border-paper-200 shrink-0">
                                    {{ $tahapInfo['ikon'] }}
                                </span>
                                <div class="space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded bg-[#14170f] px-2 py-0.5 font-mono text-[11px] font-bold text-padi-300">
                                            TAHAP {{ $tahapNum }} — {{ $tahapInfo['judul_singkat'] }}
                                        </span>
                                        <span class="rounded border px-2 py-0.5 font-mono text-[11px] font-bold {{ $st['done'] ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : ($st['alert'] ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-paper-100 text-ink-800 border-paper-300') }}">
                                            {{ $st['badge'] }} — {{ $st['ringkasan'] }}
                                        </span>
                                        <span class="rounded-full bg-paper-100 border border-paper-300 px-2.5 py-0.5 text-[11px] font-semibold text-ink-800">
                                            Penanggung Jawab: {{ $tahapInfo['peran_nama'] }}
                                        </span>
                                    </div>
                                    <h3 class="font-display text-lg sm:text-xl font-bold text-ink-950">
                                        {{ $tahapInfo['judul'] }}
                                    </h3>
                                    <p class="text-xs sm:text-sm text-ink-700 max-w-3xl leading-relaxed">
                                        {{ $tahapInfo['tujuan'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                @if($prevTahap)
                                    <button
                                        type="button"
                                        @click="selectedTahap = {{ $prevTahap }}"
                                        class="rounded-lg border border-paper-300 bg-paper-50 hover:bg-paper-200 px-3 py-2 text-xs font-bold text-ink-800 transition-colors cursor-pointer"
                                    >
                                        ← Tahap {{ $prevTahap }}
                                    </button>
                                @endif

                                @if(!$hasStageAccess)
                                    <form action="{{ route('demo.switch-peran') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="peran" value="{{ $tahapInfo['peran_kode'] }}">
                                        <input type="hidden" name="id_entitas" value="{{ $entitasAktifId }}">
                                        <input type="hidden" name="mode" value="login">
                                        <input type="hidden" name="redirect_to" value="{{ route('dashboard', ['tab' => 'alur', 'tahap' => $tahapNum]) }}">
                                        <button type="submit" class="rounded-lg bg-padi-500 hover:bg-padi-600 px-3.5 py-2 text-xs font-bold text-ink-950 transition-colors shadow-sm cursor-pointer">
                                            🔑 Login Sebagai {{ $tahapInfo['peran_nama'] }}
                                        </button>
                                    </form>
                                @endif

                                @if($nextTahap)
                                    <button
                                        type="button"
                                        @click="selectedTahap = {{ $nextTahap }}"
                                        class="rounded-lg bg-[#14170f] hover:bg-ink-900 px-3.5 py-2 text-xs font-bold text-white transition-colors cursor-pointer"
                                    >
                                        Lanjut Tahap {{ $nextTahap }} →
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Panel Khusus Tahap 8: Antrean Outbox Jurnal Gudang & Status Rekonsiliasi --}}
                        @if((int) $tahapNum === 8)
                            <div class="rounded-xl border {{ $pOutbox > 0 ? 'border-amber-400 bg-amber-50' : 'border-emerald-300 bg-emerald-50/70' }} p-4 sm:p-5 space-y-4">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base">{{ $pOutbox > 0 ? '⚡' : '✅' }}</span>
                                            <h4 class="font-display text-sm font-bold {{ $pOutbox > 0 ? 'text-amber-950' : 'text-emerald-950' }}">
                                                Outbox Antrean Jurnal Gudang (antrean_jurnal_gudang → Jurnal Umum)
                                            </h4>
                                        </div>
                                        <p class="text-xs {{ $pOutbox > 0 ? 'text-amber-900' : 'text-emerald-900' }} leading-relaxed">
                                            @if($pOutbox > 0)
                                                Terdapat <strong>{{ $pOutbox }} antrean jurnal gudang</strong> (MENUNGGU / GAGAL) yang perlu diposting ke Buku Besar sebelum menjalankan Tutup Bulan pada Tahap 9. Klik tombol di kanan atau jalankan <code class="rounded bg-white/80 px-1.5 py-0.5 font-mono text-[11px]">php artisan jurnal:proses-gudang</code>.
                                            @else
                                                Seluruh antrean outbox jurnal gudang saat ini telah diproses bersih (0 antrean menunggu). Anda juga dapat memicu pengecekan manual di bawah kapan saja.
                                            @endif
                                        </p>
                                    </div>

                                    @if($user && ($user->isSuperAdmin() || $user->hasPeran('manajer') || $user->hasPeran('akuntan')))
                                        <form action="{{ route('akuntansi.jurnal.proses-gudang') }}" method="POST" class="shrink-0">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-[#14170f] hover:bg-ink-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-colors cursor-pointer flex items-center gap-2">
                                                <span>⚡ Proses Outbox Jurnal Gudang</span>
                                                <span class="rounded bg-padi-500 px-1.5 py-0.5 font-mono text-[10px] text-ink-950">{{ $pOutbox }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('demo.switch-peran') }}" method="POST" class="shrink-0">
                                            @csrf
                                            <input type="hidden" name="peran" value="akuntan">
                                            <input type="hidden" name="id_entitas" value="{{ $entitasAktifId }}">
                                            <input type="hidden" name="mode" value="login">
                                            <input type="hidden" name="redirect_to" value="{{ route('dashboard', ['tab' => 'alur', 'tahap' => 8]) }}">
                                            <button type="submit" class="rounded-lg bg-padi-500 hover:bg-padi-600 px-4 py-2.5 text-xs font-bold text-ink-950 shadow-sm transition-colors cursor-pointer">
                                                🔑 Login Akuntan untuk Proses Outbox
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 border-t border-black/10">
                                    <div class="rounded-lg bg-white border border-paper-300 px-3 py-2">
                                        <div class="text-[10px] font-mono uppercase text-ink-500">Jurnal Terposting</div>
                                        <div class="font-mono text-base font-bold text-ink-950">{{ $pJurnal }}</div>
                                    </div>
                                    <div class="rounded-lg bg-white border border-paper-300 px-3 py-2">
                                        <div class="text-[10px] font-mono uppercase text-ink-500">Outbox Pending/Gagal</div>
                                        <div class="font-mono text-base font-bold {{ $pOutbox > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $pOutbox }}</div>
                                    </div>
                                    <div class="rounded-lg bg-white border border-paper-300 px-3 py-2">
                                        <div class="text-[10px] font-mono uppercase text-ink-500">Piutang Terbuka</div>
                                        <div class="font-mono text-base font-bold text-ink-950">{{ $pPiutang }}</div>
                                    </div>
                                    <div class="rounded-lg bg-white border border-paper-300 px-3 py-2">
                                        <div class="text-[10px] font-mono uppercase text-ink-500">Hutang Terbuka</div>
                                        <div class="font-mono text-base font-bold text-ink-950">{{ $pHutang }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Aturan Bisnis & Efek Jurnal --}}
                        <div class="rounded-xl border border-amber-300 bg-amber-50/60 p-4 space-y-2">
                            <h4 class="font-display text-xs font-bold text-ink-950 uppercase tracking-wider flex items-center gap-1.5">
                                <span>📐 Aturan Bisnis, Rumus &amp; Efek Jurnal (Panduan E2E)</span>
                            </h4>
                            <ul class="space-y-1.5 text-xs text-ink-900">
                                @foreach($tahapInfo['efek_jurnal_aturan'] ?? [] as $aturan)
                                    <li class="flex items-start gap-2 leading-relaxed">
                                        <span class="text-padi-700 font-bold">•</span>
                                        <span>{{ $aturan }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Kartu Langkah Aksi Tahap Ini --}}
                        <div class="space-y-3">
                            <h4 class="font-display text-xs font-bold text-ink-950 uppercase tracking-wider">
                                Langkah Eksekusi Berurutan — Tahap {{ $tahapNum }} ({{ $tahapInfo['judul_singkat'] }})
                            </h4>
                            <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3.5">
                                @foreach($tahapInfo['aksi'] ?? [] as $aksi)
                                    @php
                                        $peranAksi = $aksi['peran_akses'] ?? $tahapInfo['peran_kode'];
                                        $canAccessStep = $user && ($user->isSuperAdmin() || $user->hasPeran('manajer') || $user->hasPeran($peranAksi));
                                        $namaPeranAksi = $katalogPeran[$peranAksi]['nama'] ?? strtoupper($peranAksi);
                                    @endphp
                                    <div class="rounded-xl border border-paper-300 bg-paper-50 p-4 flex flex-col justify-between gap-3 hover:border-padi-500 transition-all">
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <span class="rounded bg-padi-500/20 px-2 py-0.5 font-mono text-[10px] font-bold text-padi-800">
                                                    Langkah {{ $aksi['step'] }}
                                                </span>
                                                <span class="rounded bg-white border border-paper-300 px-2 py-0.5 text-[10px] font-semibold text-ink-700">
                                                    {{ $namaPeranAksi }}
                                                </span>
                                            </div>
                                            <h5 class="font-display text-sm font-bold text-ink-950 leading-snug">
                                                {{ $aksi['judul'] }}
                                            </h5>
                                            <p class="text-xs text-ink-700 mt-1.5 leading-relaxed">
                                                {{ $aksi['deskripsi'] }}
                                            </p>
                                        </div>

                                        <div class="pt-3 border-t border-paper-200 space-y-2">
                                            @if($canAccessStep)
                                                <a href="{{ route($aksi['route']) }}" class="inline-flex w-full items-center justify-between rounded-lg bg-[#14170f] hover:bg-ink-900 px-3 py-2 text-xs font-bold text-white transition-colors">
                                                    <span>Buka {{ $aksi['judul'] }}</span>
                                                    <span>→</span>
                                                </a>
                                            @else
                                                <form action="{{ route('demo.switch-peran') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="peran" value="{{ $peranAksi }}">
                                                    <input type="hidden" name="id_entitas" value="{{ $entitasAktifId }}">
                                                    <input type="hidden" name="mode" value="login">
                                                    <input type="hidden" name="redirect_to" value="{{ route($aksi['route']) }}">
                                                    <button type="submit" class="w-full inline-flex items-center justify-between rounded-lg bg-padi-500 hover:bg-padi-600 px-3 py-2 text-xs font-bold text-ink-950 transition-colors cursor-pointer">
                                                        <span>🔑 Login &amp; Buka</span>
                                                        <span>→</span>
                                                    </button>
                                                </form>
                                            @endif

                                            @if(!empty($aksi['route_sekunder']))
                                                @if($canAccessStep)
                                                    <a href="{{ route($aksi['route_sekunder']) }}" class="inline-flex w-full items-center justify-between rounded-lg border border-paper-300 bg-white hover:bg-paper-100 px-3 py-1.5 text-[11px] font-bold text-ink-800 transition-colors">
                                                        <span>{{ $aksi['label_sekunder'] ?? 'Buka Halaman Terkait' }}</span>
                                                        <span>↗</span>
                                                    </a>
                                                @else
                                                    <form action="{{ route('demo.switch-peran') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="peran" value="{{ $peranAksi }}">
                                                        <input type="hidden" name="id_entitas" value="{{ $entitasAktifId }}">
                                                        <input type="hidden" name="mode" value="login">
                                                        <input type="hidden" name="redirect_to" value="{{ route($aksi['route_sekunder']) }}">
                                                        <button type="submit" class="w-full inline-flex items-center justify-between rounded-lg border border-paper-300 bg-white hover:bg-paper-100 px-3 py-1.5 text-[11px] font-bold text-ink-800 transition-colors cursor-pointer">
                                                            <span>🔑 {{ $aksi['label_sekunder'] ?? 'Login & Buka' }}</span>
                                                            <span>↗</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- SUB-VIEW B: PEMBAGIAN WEWENANG 7 PERAN (RBAC) --}}
            <div x-show="subViewAlur === 'peran'" x-cloak class="space-y-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-2.5">
                    @foreach($katalogPeran as $kode => $info)
                        @php
                            $isCurrentLoginRole = ($peranAktif === $kode);
                        @endphp
                        <button
                            type="button"
                            @click="selectedRole = '{{ $kode }}'"
                            :class="selectedRole === '{{ $kode }}'
                                ? 'ring-2 ring-padi-500 bg-[#1c211a] text-white shadow-md -translate-y-0.5'
                                : 'bg-white text-ink-950 hover:bg-paper-100 border-paper-300'"
                            class="rounded-xl border p-3 text-left transition-all duration-150 flex flex-col justify-between gap-2 cursor-pointer relative"
                        >
                            @if($isCurrentLoginRole)
                                <span class="absolute -top-2 right-2 rounded-full bg-emerald-600 text-white px-2 py-0.2 text-[9px] font-bold shadow-xs">
                                    AKTIF LOGIN
                                </span>
                            @endif
                            <div class="flex items-center justify-between">
                                <span class="text-xl">{{ $info['ikon'] }}</span>
                                <span class="font-mono text-[10px] font-bold uppercase tracking-wider"
                                      :class="selectedRole === '{{ $kode }}' ? 'text-padi-300' : 'text-ink-600'">
                                    Alur #{{ $info['urutan'] }}
                                </span>
                            </div>
                            <div>
                                <p class="font-display text-xs font-bold leading-tight"
                                   :class="selectedRole === '{{ $kode }}' ? 'text-white' : 'text-ink-950'">
                                    {{ $info['nama'] }}
                                </p>
                                <p class="text-[10px] mt-0.5 line-clamp-1"
                                   :class="selectedRole === '{{ $kode }}' ? 'text-paper-300' : 'text-ink-600'">
                                    {{ $info['tahap'] }}
                                </p>
                            </div>
                        </button>
                    @endforeach
                </div>

                @foreach($katalogPeran as $kode => $info)
                    <div
                        x-show="selectedRole === '{{ $kode }}'"
                        x-cloak
                        class="rounded-2xl border border-paper-300 bg-white p-6 shadow-sm space-y-6"
                    >
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-paper-200 pb-5">
                            <div class="flex items-start gap-4">
                                <span class="text-4xl p-3 rounded-xl bg-paper-100 border border-paper-200">{{ $info['ikon'] }}</span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-padi-500/15 border border-padi-500/30 px-2 py-0.5 font-mono text-xs font-bold text-padi-700">
                                            Alur #{{ $info['urutan'] }}
                                        </span>
                                        <h3 class="font-display text-xl font-bold text-ink-950">{{ $info['nama'] }}</h3>
                                        @if($peranAktif === $kode)
                                            <span class="rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 text-xs font-bold">
                                                ✓ Akun Sedang Aktif
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-ink-700 mt-1 max-w-2xl">{{ $info['ringkasan'] ?? $info['deskripsi'] ?? '' }}</p>
                                </div>
                            </div>

                            @if($peranAktif !== $kode)
                                <form action="{{ route('demo.switch-peran') }}" method="POST" class="shrink-0">
                                    @csrf
                                    <input type="hidden" name="peran" value="{{ $kode }}">
                                    <input type="hidden" name="id_entitas" value="{{ $entitasAktifId }}">
                                    <input type="hidden" name="mode" value="login">
                                    <button type="submit" class="rounded-lg bg-padi-500 hover:bg-padi-600 px-4 py-2.5 text-xs font-bold text-ink-950 transition-colors shadow-sm flex items-center gap-2 cursor-pointer">
                                        <span>🔑 Beralih &amp; Login Sebagai {{ $info['nama'] }}</span>
                                        <span>→</span>
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="rounded-xl border border-emerald-300 bg-emerald-50/70 p-4 space-y-2">
                                <h4 class="font-display text-xs font-bold text-emerald-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>✅ Data &amp; Fitur yang Dikelola</span>
                                </h4>
                                <ul class="space-y-1.5 text-xs text-emerald-950">
                                    @foreach($info['data_dikelola'] ?? [] as $wewenang)
                                        <li class="flex items-start gap-2">
                                            <span class="text-emerald-700 font-bold">•</span>
                                            <span>{{ $wewenang }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="rounded-xl border border-rose-300 bg-rose-50/70 p-4 space-y-2">
                                <h4 class="font-display text-xs font-bold text-rose-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🔒 Batasan Hak Akses (Pemisahan Wewenang)</span>
                                </h4>
                                <ul class="space-y-1.5 text-xs text-rose-950">
                                    @foreach($info['data_dibatasi'] ?? [] as $batasan)
                                        <li class="flex items-start gap-2">
                                            <span class="text-rose-700 font-bold">•</span>
                                            <span>{{ $batasan }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <h4 class="font-display text-xs font-bold text-ink-950 uppercase tracking-wider">
                                Pintasan Modul Berdasarkan Peran — {{ $info['nama'] }}
                            </h4>
                            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                @foreach($info['alur_kerja'] ?? [] as $step)
                                    @php
                                        $userHasAccess = $user && ($user->isSuperAdmin() || $user->hasPeran('manajer') || $user->hasPeran($kode));
                                    @endphp
                                    <div class="rounded-xl border border-paper-300 bg-paper-50 p-4 flex flex-col justify-between gap-3 hover:border-padi-500 transition-all">
                                        <div>
                                            <div class="flex items-center justify-between text-xs mb-1.5">
                                                <span class="rounded bg-padi-500/20 px-2 py-0.5 font-mono text-[10px] font-bold text-padi-700">
                                                    Langkah {{ $step['langkah'] ?? $step['step'] ?? 1 }}
                                                </span>
                                            </div>
                                            <h5 class="font-display text-xs font-bold text-ink-950 leading-snug">{{ $step['judul'] }}</h5>
                                            <p class="text-[11px] text-ink-600 mt-1 leading-relaxed">{{ $step['deskripsi'] }}</p>
                                        </div>

                                        <div class="pt-2 border-t border-paper-200">
                                            @if($userHasAccess)
                                                <a href="{{ route($step['route']) }}" class="inline-flex w-full items-center justify-between rounded-lg bg-[#14170f] hover:bg-ink-900 px-3 py-2 text-xs font-bold text-white transition-colors">
                                                    <span>Buka Modul Ini</span>
                                                    <span>→</span>
                                                </a>
                                            @else
                                                <form action="{{ route('demo.switch-peran') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="peran" value="{{ $kode }}">
                                                    <input type="hidden" name="id_entitas" value="{{ $entitasAktifId }}">
                                                    <input type="hidden" name="mode" value="login">
                                                    <input type="hidden" name="redirect_to" value="{{ route($step['route']) }}">
                                                    <button type="submit" class="w-full inline-flex items-center justify-between rounded-lg bg-padi-500 hover:bg-padi-600 px-3 py-2 text-xs font-bold text-ink-950 transition-colors cursor-pointer">
                                                        <span>🔑 Login &amp; Buka</span>
                                                        <span>→</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pintasan Cepat Seluruh Modul Internal --}}
            <div class="rounded-2xl bg-white border border-paper-300 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-paper-200 pb-3">
                    <div>
                        <h3 class="font-display text-base font-bold text-ink-950">
                            Pintasan Cepat Modul Operasional Internal Desa
                        </h3>
                        <p class="text-xs text-ink-600">Akses langsung ke seluruh proses operasional koperasi desa aktif:</p>
                    </div>
                    <span class="inline-flex items-center rounded bg-paper-100 border border-paper-300 px-3 py-1 text-xs font-mono font-bold text-ink-800">
                        {{ $entitasAktifObj?->nama_entitas ?? 'Entitas Koperasi' }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <a href="{{ route('perencanaan.dashboard') }}" class="rounded-xl border border-paper-300 bg-paper-50 p-4 hover:border-padi-500 transition-all flex flex-col justify-between gap-2">
                        <div>
                            <span class="text-2xl">📊</span>
                            <h4 class="font-display text-xs font-bold text-ink-950 mt-1">Perencanaan &amp; Neraca</h4>
                            <p class="text-[11px] text-ink-600 mt-0.5">Analisis surplus-defisit.</p>
                        </div>
                        <span class="text-[11px] font-bold text-padi-600">Buka →</span>
                    </a>

                    <a href="{{ route('perencanaan.permintaan-pengadaan.index') }}" class="rounded-xl border border-paper-300 bg-paper-50 p-4 hover:border-padi-500 transition-all flex flex-col justify-between gap-2">
                        <div>
                            <span class="text-2xl">📑</span>
                            <h4 class="font-display text-xs font-bold text-ink-950 mt-1">Rekomendasi Pengadaan</h4>
                            <p class="text-[11px] text-ink-600 mt-0.5">Rencana pengadaan desa.</p>
                        </div>
                        <span class="text-[11px] font-bold text-padi-600">Buka →</span>
                    </a>

                    <a href="{{ route('gudang.stok.index') }}" class="rounded-xl border border-paper-300 bg-paper-50 p-4 hover:border-padi-500 transition-all flex flex-col justify-between gap-2">
                        <div>
                            <span class="text-2xl">🏭</span>
                            <h4 class="font-display text-xs font-bold text-ink-950 mt-1">Gudang &amp; Stok</h4>
                            <p class="text-[11px] text-ink-600 mt-0.5">{{ $ringkasan['jumlah_barang'] }} jenis barang.</p>
                        </div>
                        <span class="text-[11px] font-bold text-padi-600">Buka →</span>
                    </a>

                    <a href="{{ route('pembelian.pembelian.index') }}" class="rounded-xl border border-paper-300 bg-paper-50 p-4 hover:border-padi-500 transition-all flex flex-col justify-between gap-2">
                        <div>
                            <span class="text-2xl">🛒</span>
                            <h4 class="font-display text-xs font-bold text-ink-950 mt-1">Pembelian (PO)</h4>
                            <p class="text-[11px] text-ink-600 mt-0.5">Order pengadaan stok.</p>
                        </div>
                        <span class="text-[11px] font-bold text-padi-600">Buka →</span>
                    </a>

                    <a href="{{ route('penjualan.penjualan.index') }}" class="rounded-xl border border-paper-300 bg-paper-50 p-4 hover:border-padi-500 transition-all flex flex-col justify-between gap-2">
                        <div>
                            <span class="text-2xl">🧾</span>
                            <h4 class="font-display text-xs font-bold text-ink-950 mt-1">Kasir (POS)</h4>
                            <p class="text-[11px] text-ink-600 mt-0.5">{{ $ringkasan['jumlah_penjualan'] }} transaksi.</p>
                        </div>
                        <span class="text-[11px] font-bold text-padi-600">Buka →</span>
                    </a>

                    <a href="{{ route('akuntansi.jurnal.index') }}" class="rounded-xl border border-paper-300 bg-paper-50 p-4 hover:border-padi-500 transition-all flex flex-col justify-between gap-2">
                        <div>
                            <span class="text-2xl">📒</span>
                            <h4 class="font-display text-xs font-bold text-ink-950 mt-1">Akuntansi &amp; Jurnal</h4>
                            <p class="text-[11px] text-ink-600 mt-0.5">Jurnal &amp; buku besar.</p>
                        </div>
                        <span class="text-[11px] font-bold text-padi-600">Buka →</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 2: KATALOG KOMODITAS DESA                                             --}}
        {{-- ========================================================================= --}}
        <div x-show="tabAktif === 'katalog'" x-cloak class="space-y-4">
            <div class="rounded-2xl border border-paper-300 bg-white p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-paper-200 pb-4">
                    <div>
                        <h3 class="font-display text-lg font-bold text-ink-950">
                            🌾 Katalog Komoditas Unggulan Desa
                        </h3>
                        <p class="text-xs text-ink-600">
                            Daftar komoditas pertanian dan pangan yang dibudidayakan serta diperjualbelikan lintas koperasi desa.
                        </p>
                    </div>

                    {{-- Filter & Pencarian Sederhana --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            x-model="kategoriKomoditas"
                            class="rounded-lg border border-paper-300 bg-white px-3 py-1.5 text-xs text-ink-900 focus:border-padi-500 outline-none"
                        >
                            <option value="all">Semua Kategori</option>
                            @foreach($kategoriTersedia as $kat)
                                <option value="{{ $kat }}">{{ ucfirst($kat) }}</option>
                            @endforeach
                        </select>

                        <input
                            type="text"
                            x-model="searchKomoditas"
                            placeholder="Cari nama komoditas..."
                            class="rounded-lg border border-paper-300 bg-white px-3 py-1.5 text-xs text-ink-900 focus:border-padi-500 outline-none w-48"
                        >
                    </div>
                </div>

                {{-- Grid Kartu Komoditas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @forelse($daftarKomoditas as $kom)
                        <div
                            x-show="(kategoriKomoditas === 'all' || '{{ $kom->kategori }}' === kategoriKomoditas) && (searchKomoditas === '' || '{{ strtolower($kom->nama) }}'.includes(searchKomoditas.toLowerCase()))"
                            class="rounded-xl border border-paper-200 bg-paper-50 p-4 hover:border-padi-500 transition-all flex flex-col justify-between gap-3 shadow-2xs"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded bg-padi-500/15 border border-padi-500/30 px-2 py-0.5 font-mono text-[10px] font-bold text-padi-700 uppercase">
                                        {{ str_replace('_', ' ', $kom->kategori ?? 'Umum') }}
                                    </span>
                                    <span class="rounded bg-paper-200 px-2 py-0.5 font-mono text-[10px] text-ink-700">
                                        Satuan: Kg
                                    </span>
                                </div>
                                <h4 class="font-display text-sm font-bold text-ink-950 mt-2.5">{{ $kom->nama }}</h4>
                                <p class="text-xs text-ink-600 mt-1 line-clamp-2">
                                    {{ $kom->deskripsi ?? 'Komoditas pangan lokal binaan koperasi desa dan kelompok tani.' }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-paper-200 flex items-center justify-between text-[11px]">
                                <span class="font-mono text-ink-500">ID: #{{ $kom->id }}</span>
                                <a href="{{ route('perencanaan.kebutuhan-komoditas.index') }}" class="font-semibold text-padi-700 hover:text-padi-800">
                                    Lihat Neraca →
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-8 text-center text-xs text-ink-500 italic">
                            Belum ada komoditas terdaftar di master data.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 3: KETAHANAN PANGAN ANTAR-DESA (SURPLUS / DEFISIT)                    --}}
        {{-- ========================================================================= --}}
        <div x-show="tabAktif === 'neraca'" x-cloak class="space-y-4">
            <div class="rounded-2xl border border-paper-300 bg-white p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-paper-200 pb-4">
                    <div>
                        <h3 class="font-display text-lg font-bold text-ink-950">
                            ⚖️ Neraca Ketahanan Pangan &amp; Potensi Barter Antar-Desa
                        </h3>
                        <p class="text-xs text-ink-600">
                            Hasil perhitungan selisih ketersediaan dan kebutuhan komoditas desa untuk acuan kerja sama konsinyasi / barter (Tahun {{ $tahunAktif }} Bulan {{ $bulanAktif }}).
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <select
                            x-model="filterWilayahNeraca"
                            class="rounded-lg border border-paper-300 bg-white px-3 py-1.5 text-xs text-ink-900 focus:border-padi-500 outline-none"
                        >
                            <option value="all">Semua Wilayah Desa</option>
                            @foreach($daftarWilayah as $w)
                                <option value="{{ $w->id }}">{{ $w->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Callout Transparansi Aman --}}
                <div class="rounded-xl border border-padi-500/40 bg-padi-50/80 p-3.5 text-xs text-ink-800 flex items-start gap-2.5">
                    <span class="text-lg">ℹ️</span>
                    <div>
                        <strong class="font-bold text-ink-950">Prinsip Transparansi Tanpa Membocorkan Rahasia Bisnis:</strong>
                        <p class="mt-0.5 text-ink-700 leading-relaxed">
                            Data neraca komoditas di bawah merupakan data status agregat pangan. Rincian negosiasi harga, nominal kontrak pembelian (PO), transaksi kasir, serta pembukuan kas/jurnal terlindungi secara aman di dalam sistem masing-masing desa.
                        </p>
                    </div>
                </div>

                {{-- Tabel Neraca Pangan --}}
                <div class="overflow-x-auto rounded-xl border border-paper-200">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-paper-200 bg-paper-100/80 font-mono text-[11px] uppercase tracking-wider text-ink-700">
                            <tr>
                                <th class="px-4 py-3">Wilayah Desa</th>
                                <th class="px-4 py-3">Komoditas Pangan</th>
                                <th class="px-4 py-3 text-right">Ketersediaan</th>
                                <th class="px-4 py-3 text-right">Kebutuhan</th>
                                <th class="px-4 py-3 text-right">Selisih</th>
                                <th class="px-4 py-3 text-center">Status Neraca</th>
                                <th class="px-4 py-3 text-right">Rekomendasi Alur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-200 bg-white">
                            @forelse($neracaSurplusDefisit as $row)
                                <tr
                                    x-show="filterWilayahNeraca === 'all' || filterWilayahNeraca == '{{ $row->id_wilayah }}'"
                                    class="hover:bg-paper-50 transition"
                                >
                                    <td class="px-4 py-3 font-medium text-ink-900">
                                        {{ $row->wilayah?->nama ?? 'Wilayah #' . $row->id_wilayah }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-ink-950">
                                        {{ $row->komoditas?->nama ?? 'Komoditas #' . $row->id_komoditas }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono">
                                        {{ number_format((float)$row->total_ketersediaan, 1) }} Kg
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono">
                                        {{ number_format((float)$row->total_kebutuhan, 1) }} Kg
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold {{ $row->selisih >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $row->selisih >= 0 ? '+' : '' }}{{ number_format((float)$row->selisih, 1) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($row->status_surplus_defisit === 'surplus')
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 text-[10px] font-bold">
                                                ▲ SURPLUS (BERLEBIH)
                                            </span>
                                        @elseif($row->status_surplus_defisit === 'defisit')
                                            <span class="inline-flex items-center rounded-full bg-rose-100 text-rose-800 border border-rose-300 px-2.5 py-0.5 text-[10px] font-bold">
                                                ▼ DEFISIT (KURANG)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-blue-100 text-blue-800 border border-blue-300 px-2.5 py-0.5 text-[10px] font-bold">
                                                ● SEIMBANG
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if($row->status_surplus_defisit === 'surplus')
                                            <a href="{{ route('gudang.pencocokan-surplus-defisit.index') }}" class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-emerald-700">
                                                Tawarkan Konsinyasi →
                                            </a>
                                        @else
                                            <a href="{{ route('perencanaan.permintaan-pengadaan.index') }}" class="inline-flex items-center gap-1 rounded bg-amber-500 px-2.5 py-1 text-[11px] font-semibold text-ink-950 hover:bg-amber-600">
                                                Ajukan Pengadaan →
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-xs text-ink-500 italic">
                                        Belum ada kalkulasi neraca komoditas pada periode ini. Masuk sebagai <strong>Manajer Koperasi</strong> untuk melakukan kalkulasi kebutuhan di modul Perencanaan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 4: DIREKTORI KOPERASI DESA TERHUBUNG                                  --}}
        {{-- ========================================================================= --}}
        <div x-show="tabAktif === 'entitas'" x-cloak class="space-y-4">
            <div class="rounded-2xl border border-paper-300 bg-white p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-paper-200 pb-4">
                    <div>
                        <h3 class="font-display text-lg font-bold text-ink-950">
                            🏘️ Direktori Koperasi Desa Terhubung
                        </h3>
                        <p class="text-xs text-ink-600">
                            Entitas ekonomi desa yang telah terverifikasi dan saling terhubung dalam jaringan barter &amp; pemetaan komoditas.
                        </p>
                    </div>
                    @if($user && $user->isSuperAdmin())
                        <a href="{{ route('master.entitas.create') }}" class="rounded-lg bg-merah-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-merah-700 transition">
                            + Tambah Koperasi Baru
                        </a>
                    @endif
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($daftarEntitas as $ent)
                        <div class="rounded-xl border border-paper-200 bg-paper-50 p-5 flex flex-col justify-between gap-4 hover:border-padi-500 transition-all shadow-2xs">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded bg-padi-500/15 border border-padi-500/30 px-2.5 py-0.5 font-mono text-xs font-bold text-padi-700">
                                        {{ $ent->kode_entitas }}
                                    </span>
                                    <span class="rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 text-[10px] font-bold">
                                        ● Aktif
                                    </span>
                                </div>
                                <h4 class="font-display text-base font-bold text-ink-950 mt-3">{{ $ent->nama_entitas }}</h4>
                                <p class="text-xs text-ink-600 mt-1">
                                    Wilayah: <strong class="text-ink-800">{{ $ent->wilayah?->nama ?? 'Wilayah Desa' }}</strong>
                                </p>
                                <p class="text-[11px] text-ink-500 mt-0.5 font-mono">
                                    Jenis: {{ $ent->jenis_entitas ?? 'Koperasi Desa' }} • Tahun Buku: {{ $ent->tahun_buku_awal }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-paper-200 flex items-center justify-between text-xs">
                                <span class="font-mono text-ink-600 text-[11px]">
                                    {{ $ent->pengguna_count ?? $ent->pengguna->count() }} Akun Pengelola
                                </span>

                                <form action="{{ route('demo.switch-peran') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id_entitas" value="{{ $ent->id_entitas }}">
                                    <input type="hidden" name="peran" value="{{ $peranAktif }}">
                                    <input type="hidden" name="mode" value="login">
                                    <button type="submit" class="rounded bg-paper-200 hover:bg-paper-300 text-ink-800 font-semibold px-2.5 py-1 text-[11px] transition cursor-pointer">
                                        Pilih Desa Ini →
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-8 text-center text-xs text-ink-500 italic">
                            Belum ada entitas koperasi terdaftar.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-layouts.app>
