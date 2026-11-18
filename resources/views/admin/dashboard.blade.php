<x-layouts.app title="Super Admin Dashboard">
    <div class="space-y-6" x-data="{
        showModal: false,
        actionType: 'approve',
        entitasId: null,
        entitasNama: '',
        targetUrl: '',
        openApprove(id, nama, url) {
            this.actionType = 'approve';
            this.entitasId = id;
            this.entitasNama = nama;
            this.targetUrl = url;
            this.showModal = true;
        },
        openReject(id, nama, url) {
            this.actionType = 'reject';
            this.entitasId = id;
            this.entitasNama = nama;
            this.targetUrl = url;
            this.showModal = true;
        }
    }">
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
                                        <button type="button"
                                                @click="openApprove('{{ $entitas->id_entitas }}', '{{ addslashes($entitas->nama_entitas) }}', '{{ route('admin.approve', $entitas->id_entitas) }}')"
                                                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors shadow-xs">
                                            Setujui
                                        </button>
                                        <button type="button"
                                                @click="openReject('{{ $entitas->id_entitas }}', '{{ addslashes($entitas->nama_entitas) }}', '{{ route('admin.approve', $entitas->id_entitas) }}')"
                                                class="px-3 py-1.5 bg-white border border-paper-300 hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-paper-700 text-xs font-medium rounded-lg transition-colors ml-1 shadow-xs">
                                            Tolak
                                        </button>
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

        {{-- Modal Dialog Konfirmasi Approve / Reject Super Admin --}}
        <div x-show="showModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink-950/50 backdrop-blur-xs transition-opacity"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl border border-paper-200"
                 @click.away="showModal = false"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                <div class="flex items-center gap-3 mb-4">
                    <template x-if="actionType === 'approve'">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="actionType === 'reject'">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </template>
                    <div>
                        <h3 class="text-base font-semibold text-paper-900"
                            x-text="actionType === 'approve' ? 'Konfirmasi Persetujuan Entitas' : 'Konfirmasi Penolakan Entitas'">
                        </h3>
                        <p class="text-xs text-paper-500">Tindakan Super Administrator</p>
                    </div>
                </div>

                <div class="mb-5 text-sm text-paper-700 leading-relaxed">
                    <template x-if="actionType === 'approve'">
                        <p>
                            Apakah Anda yakin ingin <strong>menyetujui</strong> pendaftaran <span class="font-bold text-paper-900" x-text="entitasNama"></span>?
                            Setelah disetujui, entitas ini akan aktif di jaringan dan dapat mulai melakukan operasional serta pencatatan transaksi.
                        </p>
                    </template>
                    <template x-if="actionType === 'reject'">
                        <p>
                            Apakah Anda yakin ingin <strong>menolak</strong> pendaftaran <span class="font-bold text-paper-900" x-text="entitasNama"></span>?
                        </p>
                    </template>
                </div>

                <form :action="targetUrl" method="POST" class="flex justify-end gap-2.5">
                    @csrf
                    <input type="hidden" name="action" :value="actionType">
                    <button type="button" @click="showModal = false"
                            class="rounded-lg border border-paper-300 px-4 py-2 text-xs font-medium text-paper-700 hover:bg-paper-100 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            :class="actionType === 'approve' ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'"
                            class="rounded-lg px-4 py-2 text-xs font-medium transition-colors shadow-xs"
                            x-text="actionType === 'approve' ? 'Ya, Setujui Entitas' : 'Ya, Tolak Entitas'">
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
