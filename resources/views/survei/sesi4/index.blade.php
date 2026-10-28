<x-layouts.app title="Sesi 4: Pemenuhan & Harga Komoditas — KDMP" eyebrow="Manajemen Survei">
<div class="min-h-screen bg-paper-50 pb-20">
    <!-- Header Minimalis -->
    <div class="bg-white border-b border-paper-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div>
                    <a href="{{ route('survei.sesi.show', $sesi->id) }}" class="text-xs font-medium text-ink-500 hover:text-merah-500 transition-colors flex items-center gap-1 mb-1">
                        &larr; Kembali ke Detail Sesi
                    </a>
                    <h1 class="text-lg font-display font-semibold text-ink-900 tracking-tight">Sesi 4 — Standar Pemenuhan & Harga</h1>
                </div>
                
                <div class="flex items-center gap-3">
                    @if(in_array($sesi->status_sesi4, ['selesai', 'terverifikasi']))
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-hijau-50 border border-hijau-200 text-xs font-semibold text-hijau-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Selesai
                        </span>
                    @else
                        <form method="POST" action="{{ route('survei.sesi4.draft', $sesi->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-xs font-semibold text-ink-700 bg-paper-100 hover:bg-paper-200 border border-paper-300 rounded-sm shadow-sm transition-colors">
                                Simpan Draft
                            </button>
                        </form>
                        <form method="POST" action="{{ route('survei.sesi4.selesaikan', $sesi->id) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menyelesaikan Sesi 4? Data harga dan pemenuhan komoditas sudah benar?')">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-hijau-500 hover:bg-hijau-600 rounded-sm shadow-sm transition-colors">
                                Selesaikan Sesi 4
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-6 space-y-6">
        
        <!-- Alerts -->
        @if (session('success'))
            <div class="p-4 bg-hijau-50 border border-hijau-200 text-hijau-700 rounded-sm text-sm flex items-start gap-2 shadow-sm">
                <svg class="w-5 h-5 text-hijau-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="p-4 bg-merah-50 border border-merah-200 text-merah-700 rounded-sm text-sm flex items-start gap-2 shadow-sm">
                <svg class="w-5 h-5 text-merah-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Card Informasi Sesi -->
        <div class="bg-white border border-paper-200 rounded-sm shadow-sm p-5 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-[10px] font-bold tracking-wider text-ink-400 uppercase mb-1">Desa</p>
                <p class="text-sm font-semibold text-ink-900">{{ $sesi->wilayah->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold tracking-wider text-ink-400 uppercase mb-1">Periode</p>
                <p class="text-sm font-semibold text-ink-900">{{ $sesi->tahun }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold tracking-wider text-ink-400 uppercase mb-1">Kecamatan</p>
                <p class="text-sm font-semibold text-ink-900">{{ $sesi->wilayah->parent->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold tracking-wider text-ink-400 uppercase mb-1">Status Sesi 4</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium 
                    @if($sesi->status_sesi4 == 'belum_diisi') bg-paper-100 text-ink-600
                    @elseif($sesi->status_sesi4 == 'sedang_diisi') bg-kuning-100 text-kuning-800
                    @else bg-hijau-100 text-hijau-800 @endif">
                    {{ \Survei\Models\SesiSurvei::LABEL_STATUS_SESI4[$sesi->status_sesi4 ?? 'belum_diisi'] }}
                </span>
            </div>
        </div>

        <!-- Section: Tabel Pemenuhan -->
        <div class="bg-white border border-paper-200 rounded-sm shadow-sm overflow-hidden">
            <div class="p-5 border-b border-paper-200 bg-paper-50 flex items-center justify-between">
                <div>
                    <h2 class="font-display text-base font-semibold text-ink-900">Sumber Pemenuhan & Harga Komoditas</h2>
                    <p class="mt-0.5 text-xs text-ink-500">{{ $pemenuhanList->count() }} data pemenuhan tercatat.</p>
                </div>
                <button type="button"
                        onclick="bukaModalTambah()"
                        class="flex items-center gap-1.5 rounded-sm bg-merah-500 px-4 py-2 text-xs font-semibold text-white hover:bg-merah-600 shadow-sm whitespace-nowrap transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Data Pemenuhan
                </button>
            </div>

            @if($pemenuhanList->isEmpty())
                <div class="px-5 py-16 text-center">
                    <svg class="mx-auto mb-4 h-12 w-12 text-ink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <p class="text-sm font-medium text-ink-800">Belum ada data harga/pemenuhan.</p>
                    <p class="text-xs text-ink-500 mt-1 mb-5">Tambahkan data sumber pembelian, tempat, dan harga komoditas yang berlaku di desa ini.</p>
                    <button type="button" onclick="bukaModalTambah()"
                            class="inline-flex items-center gap-1.5 rounded-sm bg-merah-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-merah-600 shadow-sm transition-colors">
                        + Tambah Data Pertama
                    </button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink-600 whitespace-nowrap">
                        <thead class="bg-white text-[11px] font-bold uppercase tracking-wider text-ink-400 border-b border-paper-200">
                            <tr>
                                <th class="px-5 py-3">Komoditas</th>
                                <th class="px-5 py-3">Sumber Pemenuhan</th>
                                <th class="px-5 py-3">Nama Tempat</th>
                                <th class="px-5 py-3 text-right">Harga</th>
                                <th class="px-5 py-3">Tgl Survei</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-paper-100">
                            @foreach($pemenuhanList as $p)
                                <tr class="hover:bg-paper-50/50 transition-colors">
                                    <td class="px-5 py-3 font-medium text-ink-900">
                                        {{ $p->komoditas->nama }}
                                        <span class="block text-[10px] text-ink-400 font-normal mt-0.5">{{ $p->komoditas->kategori }}</span>
                                    </td>
                                    <td class="px-5 py-3">{{ $p->sumber_pemenuhan }}</td>
                                    <td class="px-5 py-3">{{ $p->nama_tempat }}</td>
                                    <td class="px-5 py-3 text-right font-medium text-ink-900">
                                        Rp {{ number_format($p->harga, 0, ',', '.') }} <span class="text-ink-400 text-xs font-normal">/ {{ $p->satuan_harga }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-xs">{{ $p->tanggal_survei->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <form method="POST" action="{{ route('survei.sesi4.pemenuhan.destroy', [$sesi->id, $p->id]) }}" onsubmit="return confirm('Hapus data pemenuhan komoditas ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-merah-500 hover:text-merah-700 p-1.5 rounded-sm hover:bg-merah-50 transition-colors" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Data Pemenuhan -->
<div id="modalPemenuhan" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-ink-900/60 backdrop-blur-sm transition-opacity" onclick="tutupModal()"></div>
    
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-md bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-paper-200">
                <form id="formPemenuhan" method="POST" action="{{ route('survei.sesi4.pemenuhan.store', $sesi->id) }}">
                    @csrf
                    
                    <div class="bg-paper-50 px-6 py-4 border-b border-paper-200 flex justify-between items-center">
                        <div>
                            <h3 class="text-base font-semibold leading-6 text-ink-900" id="modal-title">Tambah Data Pemenuhan & Harga</h3>
                            <p class="text-xs text-ink-500 mt-0.5">Catat sumber pemenuhan komoditas dari masyarakat setempat.</p>
                        </div>
                        <button type="button" onclick="tutupModal()" class="text-ink-400 hover:text-ink-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-5">
                        
                        <!-- Baris 1: Komoditas & Tgl -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-ink-700 mb-1.5">Komoditas <span class="text-merah-500">*</span></label>
                                <select name="id_komoditas" required class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500 text-sm py-2 px-3 bg-white">
                                    <option value="">-- Pilih Komoditas --</option>
                                    @php $currentKat = ''; @endphp
                                    @foreach($komoditasList as $k)
                                        @if($currentKat != $k->kategori)
                                            @if($currentKat != '') </optgroup> @endif
                                            <optgroup label="{{ $k->kategori }}">
                                            @php $currentKat = $k->kategori; @endphp
                                        @endif
                                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                                    @endforeach
                                    @if($currentKat != '') </optgroup> @endif
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-ink-700 mb-1.5">Tanggal Survei <span class="text-merah-500">*</span></label>
                                <input type="date" name="tanggal_survei" required value="{{ date('Y-m-d') }}" 
                                       class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500 text-sm py-2 px-3">
                            </div>
                        </div>

                        <hr class="border-paper-100">

                        <!-- Baris 2: Sumber & Nama Tempat -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-ink-700 mb-1.5">Jenis Sumber <span class="text-merah-500">*</span></label>
                                <select name="sumber_pemenuhan" id="sumberPemenuhan" required onchange="toggleLainnya()" 
                                        class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500 text-sm py-2 px-3 bg-white">
                                    <option value="">-- Pilih Sumber --</option>
                                    @foreach($SUMBER_PEMENUHAN as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </select>
                                
                                <div id="divSumberLainnya" class="mt-2 hidden">
                                    <input type="text" name="sumber_lainnya" id="sumberLainnya" placeholder="Sebutkan jenis sumber..." 
                                           class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500 text-sm py-2 px-3">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-ink-700 mb-1.5">Nama Tempat/Lokasi Spesifik <span class="text-merah-500">*</span></label>
                                <input type="text" name="nama_tempat" id="nama_tempat" required placeholder="Contoh: Pasar Dawe, Toko Sumber Rejeki" 
                                       class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500 text-sm py-2 px-3">
                                <p class="text-[10px] text-ink-400 mt-1">Nama spesifik tempat pembelian.</p>
                            </div>
                        </div>

                        <!-- Baris 3: Harga, Satuan, Ketersediaan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-ink-700 mb-1.5">Harga Beli <span class="text-merah-500">*</span></label>
                                <div class="relative rounded-sm shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-ink-500 sm:text-sm font-medium">Rp</span>
                                    </div>
                                    <input type="number" name="harga" required min="0" step="0.01" placeholder="0" 
                                           class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500 text-sm py-2 pl-10 pr-3">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-ink-700 mb-1.5">Satuan Harga <span class="text-merah-500">*</span></label>
                                <input type="text" name="satuan_harga" required placeholder="Contoh: kg, liter, butir" value="kg"
                                       class="w-full rounded-sm border-paper-300 shadow-sm focus:border-merah-500 focus:ring-merah-500 text-sm py-2 px-3">
                            </div>
                        </div>

                    </div>

                    <div class="bg-paper-50 px-6 py-4 border-t border-paper-200 flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="keepOpen" class="rounded-sm border-paper-300 text-merah-500 focus:ring-merah-500 h-4 w-4">
                            <span class="text-xs font-medium text-ink-700">Simpan & Tambah komoditas di tempat yang sama</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="tutupModal()" class="px-4 py-2 text-sm font-medium text-ink-700 hover:text-ink-900 transition-colors">
                                Batal
                            </button>
                            <button type="submit" class="inline-flex items-center justify-center rounded-sm bg-merah-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-merah-600 transition-colors">
                                Simpan Data
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function bukaModalTambah() {
        document.getElementById('modalPemenuhan').classList.remove('hidden');
        if (!document.getElementById('keepOpen').checked) {
            document.getElementById('formPemenuhan').reset();
            document.querySelector('[name="tanggal_survei"]').value = new Date().toISOString().split('T')[0];
            toggleLainnya();
        }
    }

    function tutupModal() {
        document.getElementById('modalPemenuhan').classList.add('hidden');
    }

    function toggleLainnya() {
        const select = document.getElementById('sumberPemenuhan');
        const div = document.getElementById('divSumberLainnya');
        const input = document.getElementById('sumberLainnya');
        
        if (select.value === 'Lainnya') {
            div.classList.remove('hidden');
            input.setAttribute('required', 'required');
        } else {
            div.classList.add('hidden');
            input.removeAttribute('required');
        }
    }

    // Handle "Simpan & Tambah komoditas di tempat yang sama" logic
    document.getElementById('formPemenuhan').addEventListener('submit', function(e) {
        if (document.getElementById('keepOpen').checked) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = 'Menyimpan...';
            submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    // Berhasil, kosongkan hanya komoditas dan harga
                    form.querySelector('[name="id_komoditas"]').value = '';
                    form.querySelector('[name="harga"]').value = '';
                    form.querySelector('[name="id_komoditas"]').focus();
                    
                    // Tampilkan notifikasi sukses kecil (opsional)
                    alert('Data berhasil disimpan! Silakan pilih komoditas berikutnya.');
                } else {
                    alert('Terjadi kesalahan saat menyimpan data.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan koneksi.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
    });
</script>
</x-layouts.app>
