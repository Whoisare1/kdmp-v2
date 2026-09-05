@php
    $groups = [
        [
            'no' => 'M0',
            'label' => 'Master & Periode',
            'route' => 'master.koperasi.index',
            'match' => 'master.*'
        ],
        [
            'no' => 'M1',
            'label' => 'Survey',
            'route' => 'survei.sesi.index',
            'match' => 'survei.*'
        ],
        [
            'no' => 'M2',
            'label' => 'Kalkulasi Kebutuhan',
            'route' => 'perencanaan.kebutuhan-komoditas.index',
            'match' => 'perencanaan.kebutuhan-komoditas.*,perencanaan.neraca-komoditas.*,perencanaan.standar-kebutuhan.*,perencanaan.demografi.*,perencanaan.potensi-produksi.*'
        ],
        [
            'no' => 'M3',
            'label' => 'Perencanaan Pengadaan',
            'route' => 'perencanaan.permintaan-pengadaan.index',
            'match' => 'perencanaan.permintaan-pengadaan.*,perencanaan.perbandingan-harga.*'
        ],
        [
            'no' => 'M5',
            'label' => 'Pembelian',
            'route' => 'pembelian.pembelian.index',
            'match' => 'pembelian.*'
        ],
        [
            'no' => 'M6',
            'label' => 'Penjualan',
            'route' => 'penjualan.penjualan.index',
            'match' => 'penjualan.*'
        ],
        [
            'no' => 'M7',
            'label' => 'Konsinyasi Antar Desa',
            'route' => 'konsinyasi.pengiriman.index',
            'match' => 'konsinyasi.*'
        ],
    ];

    $gudangMenus = [
        [
            'label' => 'Dashboard Gudang',
            'route' => 'gudang.index',
            'match' => 'gudang.index'
        ],
        [
            'label' => 'Posisi Stok',
            'route' => 'gudang.stok.index',
            'match' => 'gudang.stok.*'
        ],
        [
            'label' => 'Penerimaan Barang',
            'route' => 'gudang.penerimaan.index',
            'match' => 'gudang.penerimaan.*'
        ],
        [
            'label' => 'Kerusakan Barang',
            'route' => 'gudang.kerusakan.index',
            'match' => 'gudang.kerusakan.*'
        ],
        [
            'label' => 'Stock Opname',
            'route' => 'gudang.opname.index',
            'match' => 'gudang.opname.*'
        ],
        [
            'label' => 'Kartu Stok',
            'route' => 'gudang.kartu-stok.index',
            'match' => 'gudang.kartu-stok.*'
        ],
    ];

    $finance = [
        'no' => 'M8-9',
        'label' => 'Keuangan & Akuntansi',
        'route' => 'keuangan.piutang.index',
        'match' => 'keuangan.*,akuntansi.*'
    ];

    $gudangActive = request()->routeIs('gudang.*');
@endphp


<aside class="flex h-screen w-64 shrink-0 flex-col bg-ink-900 text-paper-100">

    {{-- Logo --}}
    <div class="flex items-center gap-2 border-b border-ink-700 px-5 py-5">
        <span class="flex h-8 w-8 items-center justify-center rounded-sm bg-merah-500 font-display text-sm font-semibold text-paper-50">
            K
        </span>

        <div class="leading-tight">
            <p class="font-display text-base font-semibold tracking-tight">
                KDMP
            </p>

            <p class="text-[11px] text-paper-300/70">
                Koperasi Desa Merah Putih
            </p>
        </div>
    </div>


    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">

        <p class="px-2 pb-2 font-mono text-[10px] uppercase tracking-widest text-paper-300/50">
            Modul Operasional
        </p>


        <ul class="space-y-0.5">

            {{-- M0 - M3 --}}
            @foreach ($groups as $g)

                @php
                    $active = collect(explode(',', $g['match']))
                        ->contains(fn ($p) => request()->routeIs($p));
                @endphp

                <li>

                    <a
                        href="{{ route($g['route']) }}"
                        class="group flex items-center gap-3 rounded-sm px-2 py-2 text-sm transition
                        {{ $active
                            ? 'bg-ink-700 text-paper-50'
                            : 'text-paper-200/80 hover:bg-ink-800 hover:text-paper-50'
                        }}"
                    >

                        <span
                            class="flex h-6 w-9 shrink-0 items-center justify-center rounded-[2px] border font-mono text-[10px]
                            {{ $active
                                ? 'border-merah-400 text-merah-400'
                                : 'border-ink-600 text-paper-300/60 group-hover:border-paper-300/40'
                            }}"
                        >
                            {{ $g['no'] }}
                        </span>

                        <span>
                            {{ $g['label'] }}
                        </span>

                    </a>

                </li>

            @endforeach


            {{-- M4 GUDANG --}}
            <li class="pt-1">

                <div
                    class="flex items-center gap-3 rounded-sm px-2 py-2 text-sm
                    {{ $gudangActive
                        ? 'bg-ink-700 text-paper-50'
                        : 'text-paper-200/80'
                    }}"
                >

                    <span
                        class="flex h-6 w-9 shrink-0 items-center justify-center rounded-[2px] border font-mono text-[10px]
                        {{ $gudangActive
                            ? 'border-merah-400 text-merah-400'
                            : 'border-ink-600 text-paper-300/60'
                        }}"
                    >
                        M4
                    </span>

                    <span class="font-medium">
                        Gudang
                    </span>

                </div>


                {{-- Submenu Gudang --}}
                <ul class="ml-6 mt-1 space-y-1 border-l border-ink-600 pl-4">

                    @foreach ($gudangMenus as $menu)

                        @php
                            $submenuActive = request()->routeIs($menu['match']);
                        @endphp

                        <li>

                            <a
                                href="{{ route($menu['route']) }}"
                                class="block rounded-sm px-3 py-1.5 text-xs transition
                                {{ $submenuActive
                                    ? 'bg-merah-600/20 text-merah-300'
                                    : 'text-paper-300/70 hover:bg-ink-800 hover:text-paper-50'
                                }}"
                            >

                                {{ $menu['label'] }}

                            </a>

                        </li>

                    @endforeach

                </ul>

            </li>


            {{-- M5 - M7 --}}
            @foreach ($groups as $g)

                @if (in_array($g['no'], ['M5', 'M6', 'M7']))

                    @php
                        $active = collect(explode(',', $g['match']))
                            ->contains(fn ($p) => request()->routeIs($p));
                    @endphp

                    <li>

                        <a
                            href="{{ route($g['route']) }}"
                            class="group flex items-center gap-3 rounded-sm px-2 py-2 text-sm transition
                            {{ $active
                                ? 'bg-ink-700 text-paper-50'
                                : 'text-paper-200/80 hover:bg-ink-800 hover:text-paper-50'
                            }}"
                        >

                            <span
                                class="flex h-6 w-9 shrink-0 items-center justify-center rounded-[2px] border font-mono text-[10px]
                                {{ $active
                                    ? 'border-merah-400 text-merah-400'
                                    : 'border-ink-600 text-paper-300/60 group-hover:border-paper-300/40'
                                }}"
                            >
                                {{ $g['no'] }}
                            </span>

                            <span>
                                {{ $g['label'] }}
                            </span>

                        </a>

                    </li>

                @endif

            @endforeach

        </ul>


        {{-- Fokus Skripsi --}}
        <p class="px-2 pb-2 pt-5 font-mono text-[10px] uppercase tracking-widest text-paper-300/50">
            Fokus Skripsi
        </p>


        @php

            $activeFinance = collect(explode(',', $finance['match']))
                ->contains(fn ($p) => request()->routeIs($p));

        @endphp


        <a
            href="{{ route($finance['route']) }}"
            class="group flex items-center gap-3 rounded-sm border px-2 py-2.5 text-sm transition
            {{ $activeFinance
                ? 'border-merah-400 bg-merah-600/20 text-paper-50'
                : 'border-merah-600/40 text-paper-100 hover:bg-merah-600/10'
            }}"
        >

            <span class="flex h-6 w-9 shrink-0 items-center justify-center rounded-[2px] border border-merah-400 font-mono text-[10px] text-merah-400">

                {{ $finance['no'] }}

            </span>

            <span class="font-medium">

                {{ $finance['label'] }}

            </span>

        </a>

    </nav>


    {{-- Footer Sidebar --}}
    <div class="border-t border-ink-700 px-5 py-4 text-[11px] text-paper-300/60">

        <p>
            Kerangka dibangun — logika Finance
        </p>

        <p>
            menyusul pada pendalaman berikutnya.
        </p>

    </div>

</aside>