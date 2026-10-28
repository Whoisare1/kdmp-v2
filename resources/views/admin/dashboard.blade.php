<x-layouts.app title="Super Admin Dashboard">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-paper-900">Dashboard Super Admin</h1>
            <div class="text-sm text-paper-500">Pusat Persetujuan & Manajemen Jaringan Ngobar</div>
        </div>

        @if(session('success'))
            <div class="bg-sawah-50 border border-sawah-200 text-sawah-700 px-4 py-3 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white border border-paper-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-paper-100 bg-paper-50/50 flex justify-between items-center">
                    <h2 class="font-medium text-paper-900">Menunggu Persetujuan</h2>
                    <span class="bg-merah-100 text-merah-700 py-1 px-2.5 rounded-full text-xs font-medium">{{ $pendingEntitas->count() }} Pendaftar</span>
                </div>
                <div class="p-0">
                    @if($pendingEntitas->isEmpty())
                        <div class="px-6 py-8 text-center text-paper-500 text-sm">
                            Tidak ada entitas yang menunggu persetujuan.
                        </div>
                    @else
                        <ul class="divide-y divide-paper-100">
                            @foreach($pendingEntitas as $entitas)
                                <li class="px-6 py-4 flex items-center justify-between hover:bg-paper-50 transition-colors">
                                    <div>
                                        <p class="text-sm font-medium text-paper-900">{{ $entitas->nama_entitas }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-paper-500">{{ $entitas->jenis_entitas }}</span>
                                            <span class="text-paper-300 text-xs">&bull;</span>
                                            <span class="text-xs text-paper-500">{{ $entitas->wilayah->nama ?? 'Wilayah Tidak Diketahui' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <form action="{{ route('admin.approve', $entitas->id_entitas) }}" method="POST">
                                            @csrf
                                            <button type="submit" name="action" value="approve" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors">
                                                Setujui
                                            </button>
                                            <button type="submit" name="action" value="reject" class="px-3 py-1.5 bg-white border border-paper-300 hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-paper-700 text-xs font-medium rounded-lg transition-colors ml-1">
                                                Tolak
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="bg-white border border-paper-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-paper-100 bg-paper-50/50 flex justify-between items-center">
                    <h2 class="font-medium text-paper-900">Entitas Jaringan Aktif</h2>
                    <span class="bg-sawah-100 text-sawah-700 py-1 px-2.5 rounded-full text-xs font-medium">{{ $approvedEntitas->count() }} Aktif</span>
                </div>
                <div class="p-0">
                    @if($approvedEntitas->isEmpty())
                        <div class="px-6 py-8 text-center text-paper-500 text-sm">
                            Belum ada entitas yang disetujui.
                        </div>
                    @else
                        <ul class="divide-y divide-paper-100">
                            @foreach($approvedEntitas as $entitas)
                                <li class="px-6 py-4 hover:bg-paper-50 transition-colors">
                                    <p class="text-sm font-medium text-paper-900">{{ $entitas->nama_entitas }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-paper-500">{{ $entitas->jenis_entitas }}</span>
                                        <span class="text-paper-300 text-xs">&bull;</span>
                                        <span class="text-xs text-padi-600 font-medium">{{ $entitas->kode_entitas }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
