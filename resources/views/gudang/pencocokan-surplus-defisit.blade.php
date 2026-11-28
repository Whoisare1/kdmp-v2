<x-layouts.app title="Permintaan Antar-Desa" eyebrow="Manajemen Gudang">
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-padi-700">Persetujuan dan tindak lanjut</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Permintaan Antar-Desa</h1>
            <p class="mt-2 max-w-3xl text-sm text-ink-600">
                Gudang mengelola persetujuan permintaan masuk dan keluar. Setelah kedua entitas menyetujui,
                rekomendasi dapat dilempar ke daftar Penjualan atau Pembelian. Transaksi tetap dibuat oleh modul terkait.
            </p>
        </div>

        @if (session('success'))
            <div class="flex items-center justify-between rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-sm border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="rounded-sm border border-paper-300 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between border-b border-paper-200 pb-3">
                <div>
                    <h2 class="font-semibold text-ink-900">Permintaan Masuk</h2>
                    <p class="mt-0.5 text-xs text-ink-500">Tawaran atau permintaan dari desa mitra yang perlu ditinjau Gudang.</p>
                </div>
                @if ($jumlahMenungguRespons > 0)
                    <span class="rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-bold text-white">
                        {{ $jumlahMenungguRespons }} Perlu Ditanggapi
                    </span>
                @endif
            </div>

            @if ($permintaanMasuk->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500">Belum ada permintaan masuk dari desa mitra.</p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[48rem] text-left text-sm">
                        <thead class="border-b border-paper-200 bg-paper-50 text-xs uppercase text-ink-600">
                            <tr>
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Desa Pengirim</th>
                                <th class="px-3 py-2">Jenis</th>
                                <th class="px-3 py-2">Komoditas &amp; Jumlah</th>
                                <th class="px-3 py-2">Persetujuan</th>
                                <th class="px-3 py-2 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-100">
                            @foreach ($permintaanMasuk as $masuk)
                                @php
                                    $persetujuanLengkap = $masuk->tgl_persetujuan_pengaju !== null
                                        && $masuk->tgl_persetujuan_penerima !== null;
                                    $siapDilempar = $persetujuanLengkap
                                        && in_array($masuk->status, ['terkirim', 'disetujui'], true);
                                @endphp
                                <tr>
                                    <td class="px-3 py-2.5 text-xs text-ink-600">{{ $masuk->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-3 py-2.5 font-medium text-ink-900">{{ $masuk->pengaju?->nama_entitas ?? 'Desa Mitra' }}</td>
                                    <td class="px-3 py-2.5">
                                        @if ($masuk->jenis_permintaan === 'tawaran_jual')
                                            <span class="inline-flex rounded-sm bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800">Tawaran barang</span>
                                        @else
                                            <span class="inline-flex rounded-sm bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-800">Permintaan barang</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 font-semibold text-ink-900">
                                        {{ $masuk->nama_komoditas }}: {{ number_format((float) ($masuk->qty_dialokasikan ?? $masuk->qty_rekomendasi), 2, ',', '.') }} {{ $masuk->satuan }}
                                        @if ($masuk->qty_dialokasikan !== null)
                                            <span class="mt-0.5 block text-xs font-normal text-ink-500">
                                                Dialokasikan dari permintaan awal {{ number_format((float) $masuk->qty_rekomendasi, 2, ',', '.') }} {{ $masuk->satuan }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if ($masuk->status === 'dilempar')
                                            <span class="text-xs font-semibold text-emerald-700">Sudah dilempar</span>
                                        @elseif ($masuk->status === 'ditolak')
                                            <span class="text-xs font-semibold text-merah-700">Ditolak</span>
                                        @elseif ($masuk->status === 'kadaluarsa')
                                            <span class="text-xs font-semibold text-ink-500">Kedaluwarsa</span>
                                        @elseif ($siapDilempar)
                                            <span class="text-xs font-semibold text-emerald-700">Disetujui kedua entitas</span>
                                        @elseif ($masuk->status === 'disetujui')
                                            <span class="text-xs font-semibold text-amber-700">Persetujuan lama belum tercatat lengkap</span>
                                        @elseif ($masuk->status === 'terkirim')
                                            <span class="text-xs font-semibold text-amber-700">
                                                Persetujuan {{ ($masuk->tgl_persetujuan_pengaju ? 1 : 0) + ($masuk->tgl_persetujuan_penerima ? 1 : 0) }}/2
                                            </span>
                                        @else
                                            <span class="text-xs font-semibold text-ink-500">{{ $masuk->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right">
                                        @if ($siapDilempar)
                                            <form method="POST" action="{{ route('gudang.permintaan-antar-entitas.dispatch', $masuk->id_permintaan_gudang) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-sm bg-emerald-700 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-800">
                                                    Lempar ke Penjualan/Pembelian
                                                </button>
                                            </form>
                                        @elseif ($masuk->status === 'dilempar')
                                            <span class="text-xs text-emerald-700">Menunggu tindak lanjut transaksi.</span>
                                        @elseif ($masuk->status === 'terkirim' && $masuk->tgl_persetujuan_penerima === null)
                                            <div class="flex items-center justify-end gap-1.5">
                                                <form method="POST" action="{{ route('gudang.permintaan-antar-entitas.respond', $masuk->id_permintaan_gudang) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="disetujui">
                                                    <button type="submit" class="rounded-sm bg-emerald-700 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-800">Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('gudang.permintaan-antar-entitas.respond', $masuk->id_permintaan_gudang) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="ditolak">
                                                    <button type="submit" class="rounded-sm border border-paper-300 bg-white px-3 py-1 text-xs font-semibold text-ink-700 hover:border-ink-500">Tolak</button>
                                                </form>
                                            </div>
                                        @elseif ($masuk->status === 'terkirim')
                                            <span class="text-xs text-amber-700">Persetujuan Gudang tercatat; menunggu desa pengirim.</span>
                                        @elseif ($masuk->status === 'disetujui')
                                            <span class="text-xs text-amber-700">Persetujuan ulang kedua entitas diperlukan sebelum rekomendasi dapat dilempar.</span>
                                        @else
                                            <span class="text-xs text-ink-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="rounded-sm border border-paper-300 bg-white p-4 shadow-xs">
            <div class="border-b border-paper-200 pb-3">
                <h2 class="font-semibold text-ink-900">Permintaan Keluar</h2>
                <p class="mt-0.5 text-xs text-ink-500">Pantau tawaran atau permintaan yang dikirim Gudang ke desa mitra.</p>
            </div>

            @if ($permintaanKeluar->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500">Belum ada permintaan keluar yang dikirim.</p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[48rem] text-left text-sm">
                        <thead class="border-b border-paper-200 bg-paper-50 text-xs uppercase text-ink-600">
                            <tr>
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Desa Tujuan</th>
                                <th class="px-3 py-2">Jenis</th>
                                <th class="px-3 py-2">Komoditas &amp; Jumlah</th>
                                <th class="px-3 py-2">Persetujuan</th>
                                <th class="px-3 py-2 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-100">
                            @foreach ($permintaanKeluar as $keluar)
                                @php
                                    $persetujuanLengkap = $keluar->tgl_persetujuan_pengaju !== null
                                        && $keluar->tgl_persetujuan_penerima !== null;
                                    $siapDilempar = $persetujuanLengkap
                                        && in_array($keluar->status, ['terkirim', 'disetujui'], true);
                                @endphp
                                <tr>
                                    <td class="px-3 py-2.5 text-xs text-ink-600">{{ $keluar->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-3 py-2.5 font-medium text-ink-900">{{ $keluar->penerima?->nama_entitas ?? 'Desa Mitra' }}</td>
                                    <td class="px-3 py-2.5">
                                        @if ($keluar->jenis_permintaan === 'tawaran_jual')
                                            <span class="inline-flex rounded-sm bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800">Tawaran barang</span>
                                        @else
                                            <span class="inline-flex rounded-sm bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-800">Permintaan barang</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 font-semibold text-ink-900">
                                        {{ $keluar->nama_komoditas }}: {{ number_format((float) ($keluar->qty_dialokasikan ?? $keluar->qty_rekomendasi), 2, ',', '.') }} {{ $keluar->satuan }}
                                        @if ($keluar->qty_dialokasikan !== null)
                                            <span class="mt-0.5 block text-xs font-normal text-ink-500">
                                                Dialokasikan dari permintaan awal {{ number_format((float) $keluar->qty_rekomendasi, 2, ',', '.') }} {{ $keluar->satuan }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if ($keluar->status === 'dilempar')
                                            <span class="text-xs font-semibold text-emerald-700">Sudah dilempar</span>
                                        @elseif ($keluar->status === 'ditolak')
                                            <span class="text-xs font-semibold text-merah-700">Ditolak</span>
                                        @elseif ($keluar->status === 'kadaluarsa')
                                            <span class="text-xs font-semibold text-ink-500">Kedaluwarsa</span>
                                        @elseif ($siapDilempar)
                                            <span class="text-xs font-semibold text-emerald-700">Disetujui kedua entitas</span>
                                        @elseif ($keluar->status === 'disetujui')
                                            <span class="text-xs font-semibold text-amber-700">Persetujuan lama belum tercatat lengkap</span>
                                        @elseif ($keluar->status === 'terkirim')
                                            <span class="text-xs font-semibold text-amber-700">
                                                Persetujuan {{ ($keluar->tgl_persetujuan_pengaju ? 1 : 0) + ($keluar->tgl_persetujuan_penerima ? 1 : 0) }}/2
                                            </span>
                                        @else
                                            <span class="text-xs font-semibold text-ink-500">{{ $keluar->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right">
                                        @if ($siapDilempar)
                                            <form method="POST" action="{{ route('gudang.permintaan-antar-entitas.dispatch', $keluar->id_permintaan_gudang) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-sm bg-emerald-700 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-800">
                                                    Lempar ke Penjualan/Pembelian
                                                </button>
                                            </form>
                                        @elseif ($keluar->status === 'dilempar')
                                            <span class="text-xs text-emerald-700">Menunggu tindak lanjut transaksi.</span>
                                        @elseif ($keluar->status === 'terkirim' && $keluar->tgl_persetujuan_pengaju === null)
                                            <div class="flex items-center justify-end gap-1.5">
                                                <form method="POST" action="{{ route('gudang.permintaan-antar-entitas.respond', $keluar->id_permintaan_gudang) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="disetujui">
                                                    <button type="submit" class="rounded-sm bg-emerald-700 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-800">Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('gudang.permintaan-antar-entitas.respond', $keluar->id_permintaan_gudang) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="ditolak">
                                                    <button type="submit" class="rounded-sm border border-paper-300 bg-white px-3 py-1 text-xs font-semibold text-ink-700 hover:border-ink-500">Tolak</button>
                                                </form>
                                            </div>
                                        @elseif ($keluar->status === 'terkirim')
                                            <span class="text-xs text-amber-700">Persetujuan Gudang tercatat; menunggu desa tujuan.</span>
                                        @elseif ($keluar->status === 'disetujui')
                                            <span class="text-xs text-amber-700">Persetujuan ulang kedua entitas diperlukan sebelum rekomendasi dapat dilempar.</span>
                                        @else
                                            <span class="text-xs text-ink-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
