<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-500/30 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-rose-300 bg-rose-50 p-4 text-sm text-rose-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-ink-900 tracking-tight">Daftar Staf Koperasi</h2>
            <p class="text-xs text-paper-500 mt-1">Kelola staf operasional desa (Kasir, Admin Gudang, Surveyor) yang bertugas di koperasi ini.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route($routeBase . '.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tambah Staf Baru</span>
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-paper-300 bg-white shadow-sm">
        <div class="p-4 border-b border-paper-200 bg-paper-50/60">
            <form method="GET" class="flex items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email staf..."
                           class="w-full rounded-lg border border-paper-300 bg-white px-3.5 py-2 text-xs text-ink-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                </div>
                <button type="submit" class="rounded-lg border border-paper-300 bg-white px-3.5 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100 transition shadow-sm">
                    Cari
                </button>
                @if(request('q'))
                    <a href="{{ route($routeBase . '.index') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-paper-200 bg-paper-100/70 font-mono text-[11px] uppercase tracking-wider text-ink-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Nama Staf</th>
                        <th class="px-5 py-3 font-semibold">Email Login</th>
                        <th class="px-5 py-3 font-semibold">Peran / Tugas</th>
                        <th class="px-5 py-3 text-center font-semibold">Status</th>
                        <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper-200">
                    @forelse($items as $user)
                        <tr class="hover:bg-paper-50/70 transition">
                            <td class="px-5 py-3.5 font-medium text-ink-900">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-paper-200 font-bold text-ink-700 uppercase text-[11px]">
                                        {{ substr($user->nama, 0, 1) }}
                                    </span>
                                    <span>{{ $user->nama }}</span>
                                    @if($user->id === auth()->id())
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">Akun Anda</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-paper-500">{{ $user->email }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($user->peran as $p)
                                        @php
                                            $badgeColor = match($p->kode) {
                                                'manajer'      => 'bg-purple-100 text-purple-800 border-purple-200',
                                                'kasir'        => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'admin_gudang' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                'surveyor'     => 'bg-blue-100 text-blue-800 border-blue-200',
                                                default        => 'bg-slate-100 text-slate-800 border-slate-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-semibold {{ $badgeColor }}">
                                            {{ $p->nama }}
                                        </span>
                                    @empty
                                        <span class="text-paper-400 italic">Belum ada peran</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if($user->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 border border-emerald-200">
                                        ● Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700 border border-rose-200">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route($routeBase . '.edit', $user->id) }}"
                                       class="rounded border border-paper-300 bg-white px-2.5 py-1 text-[11px] font-medium text-ink-700 hover:border-emerald-500 hover:text-emerald-600 transition shadow-sm">
                                        Ubah
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route($routeBase . '.destroy', $user->id) }}"
                                              onsubmit="return confirm('Hapus staf {{ $user->nama }}? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded border border-paper-300 bg-white px-2.5 py-1 text-[11px] font-medium text-rose-600 hover:bg-rose-50 hover:border-rose-300 transition shadow-sm">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-paper-500 italic">
                                Belum ada staf terdaftar di koperasi ini. Klik tombol "+ Tambah Staf Baru" di atas untuk menambahkan staf.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="border-t border-paper-200 p-4">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>

