<x-layouts.app :title="$title" eyebrow="Master Data (Super Admin)">
    <div class="mb-5">
        <a href="{{ route('master.entitas.index') }}" class="inline-flex items-center gap-1 text-sm text-ink-600 hover:text-ink-900">
            &larr; Kembali ke Daftar Entitas
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $mode === 'edit' ? route('master.entitas.update', $item->id_entitas) : route('master.entitas.store') }}"
          class="mx-auto max-w-2xl rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="mb-5 border-b border-paper-200 pb-3">
            <h2 class="font-display text-lg font-semibold text-ink-900">{{ $title }}</h2>
            <p class="text-xs text-ink-600">Setiap entitas memiliki isolasi data master dan pembukuan tersendiri.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Kode Entitas <span class="text-merah-600">*</span></label>
                <input type="text" name="kode_entitas" value="{{ old('kode_entitas', $item->kode_entitas) }}" required maxlength="30"
                       placeholder="Contoh: KDMP-CIBIRU"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm uppercase text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Jenis Organisasi <span class="text-merah-600">*</span></label>
                <select name="jenis_entitas" required
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
                    @foreach($jenisList as $key => $label)
                        <option value="{{ $key }}" @selected(old('jenis_entitas', $item->jenis_entitas ?? 'Koperasi Desa') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-ink-800 mb-1">Nama Lengkap Entitas / Koperasi <span class="text-merah-600">*</span></label>
                <input type="text" name="nama_entitas" value="{{ old('nama_entitas', $item->nama_entitas) }}" required maxlength="150"
                       placeholder="Contoh: Koperasi Desa Merah Putih Tani Makmur"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Pilih Wilayah Desa Terdaftar</label>
                <select name="id_wilayah"
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
                    <option value="">-- Pilih Wilayah --</option>
                    @foreach($wilayahList as $w)
                        <option value="{{ $w->id }}" @selected(old('id_wilayah', $item->id_wilayah) == $w->id)>
                            {{ $w->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Atau Ketik Nama Desa Baru</label>
                <input type="text" name="nama_wilayah_baru" value="{{ old('nama_wilayah_baru') }}" maxlength="150"
                       placeholder="Contoh: Desa Cibiru"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Nomor Badan Hukum / SK</label>
                <input type="text" name="badan_hukum_no" value="{{ old('badan_hukum_no', $item->badan_hukum_no) }}" maxlength="100"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Tanggal Berdiri</label>
                <input type="date" name="tgl_berdiri" value="{{ old('tgl_berdiri', $item->tgl_berdiri?->format('Y-m-d')) }}"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Tahun Buku Awal <span class="text-merah-600">*</span></label>
                <input type="number" name="tahun_buku_awal" value="{{ old('tahun_buku_awal', $item->tahun_buku_awal ?? date('Y')) }}" required min="2020" max="2100"
                       class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900">
            </div>

            <div>
                <label class="block text-xs font-medium text-ink-800 mb-1">Status Verifikasi <span class="text-merah-600">*</span></label>
                <select name="status" required
                        class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm text-ink-900">
                    <option value="APPROVED" @selected(old('status', $item->status) === 'APPROVED')>APPROVED</option>
                    <option value="PENDING" @selected(old('status', $item->status) === 'PENDING')>PENDING</option>
                    <option value="REJECTED" @selected(old('status', $item->status) === 'REJECTED')>REJECTED</option>
                </select>
            </div>

            <div class="sm:col-span-2 pt-2">
                <label class="inline-flex items-center gap-2 text-sm text-ink-800">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $item->is_active ?? true))
                           class="rounded border-paper-300 text-merah-600 focus:ring-merah-500">
                    <span>Entitas Aktif</span>
                </label>
            </div>
        </div>

        @if($mode === 'create')
            {{-- Section Akun Administrator Desa (Manajer Pertama) --}}
            <div class="mt-8 border-t-2 border-dashed border-paper-300 pt-6">
                <div class="mb-5 flex items-start gap-3 rounded-lg border border-padi-500/50 bg-padi-50/80 p-4">
                    <div class="rounded-md bg-padi-500 p-2 text-ink-950 shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-ink-950">Akun Administrator Desa / Manajer Pertama</h3>
                        <p class="mt-0.5 text-xs text-ink-700 leading-relaxed">
                            Setiap entitas baru membutuhkan akun login pengelola awal. Akun ini akan otomatis dibuatkan wewenang <strong>Manajer Koperasi</strong> (akses administrator desa) dan langsung aktif, sehingga dapat langsung digunakan untuk masuk (login) ke sistem dan mendaftarkan staf operasional desa lainnya (Kasir, Admin Gudang, Surveyor).
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-ink-800 mb-1">
                            Nama Lengkap Administrator Desa <span class="text-merah-600">*</span>
                        </label>
                        <input type="text" name="admin_nama" value="{{ old('admin_nama') }}" required maxlength="100"
                               placeholder="Contoh: Ahmad Fauzi (Ketua / Manajer Koperasi)"
                               class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-800 mb-1">
                            Email Login Administrator <span class="text-merah-600">*</span>
                        </label>
                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" required maxlength="150"
                               placeholder="Contoh: manajer@cibiru.kdmp.id"
                               class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                        <p class="mt-1 text-[11px] text-ink-500">Digunakan sebagai username saat login.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-800 mb-1">
                            Kata Sandi (Password) <span class="text-merah-600">*</span>
                        </label>
                        <input type="password" name="admin_password" required minlength="8"
                               placeholder="Minimal 8 karakter"
                               class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2 sm:max-w-xs">
                        <label class="block text-xs font-semibold text-ink-800 mb-1">
                            Konfirmasi Kata Sandi <span class="text-merah-600">*</span>
                        </label>
                        <input type="password" name="admin_password_confirmation" required minlength="8"
                               placeholder="Ulangi kata sandi di atas"
                               class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-merah-400 focus:outline-none">
                    </div>
                </div>
            </div>
        @else
            {{-- Section Daftar Akun Pengguna / Administrator Terdaftar pada Entitas Ini --}}
            <div class="mt-8 border-t-2 border-dashed border-paper-300 pt-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-ink-950">Daftar Pengguna / Staf Entitas Ini</h3>
                        <p class="text-xs text-ink-600">Akun terdaftar yang memiliki akses ke operasional entitas ini.</p>
                    </div>
                    <a href="{{ route('master.pengguna.create', ['id_entitas' => $item->id_entitas]) }}"
                       class="inline-flex items-center gap-1 rounded bg-sawah-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-sawah-700">
                        + Tambah Akun / Staf
                    </a>
                </div>

                @if($item->pengguna && $item->pengguna->isNotEmpty())
                    <div class="overflow-hidden rounded border border-paper-200 bg-white">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-paper-100 font-mono text-[11px] text-ink-600">
                                <tr>
                                    <th class="px-3 py-2">Nama Pengguna</th>
                                    <th class="px-3 py-2">Email Login</th>
                                    <th class="px-3 py-2">Peran</th>
                                    <th class="px-3 py-2 text-center">Status</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-paper-200">
                                @foreach($item->pengguna as $staf)
                                    <tr class="hover:bg-paper-50">
                                        <td class="px-3 py-2 font-medium text-ink-900">{{ $staf->nama }}</td>
                                        <td class="px-3 py-2 font-mono text-ink-600">{{ $staf->email }}</td>
                                        <td class="px-3 py-2">
                                            @foreach($staf->peran as $p)
                                                <span class="inline-block rounded bg-paper-200 px-1.5 py-0.5 text-[10px] font-semibold text-ink-800">
                                                    {{ $p->nama }}
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $staf->is_active ? 'bg-sawah-100 text-sawah-700' : 'bg-merah-100 text-merah-700' }}">
                                                {{ $staf->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="{{ route('master.pengguna.edit', $staf->id) }}" class="text-merah-600 hover:underline">Edit Akun</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-xs text-amber-900">
                        <p class="font-bold mb-1">⚠️ Entitas ini belum memiliki akun Administrator / Staf.</p>
                        <p class="mb-3">Buatkan akun Manajer Koperasi pertama untuk entitas ini sekarang agar siap digunakan login:</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 bg-white p-3.5 rounded border border-amber-200">
                            <div>
                                <label class="block text-[11px] font-semibold text-ink-800 mb-1">Nama Administrator</label>
                                <input type="text" name="admin_nama" value="{{ old('admin_nama') }}" placeholder="Nama Manajer"
                                       class="w-full rounded border border-paper-300 px-2.5 py-1.5 text-xs text-ink-900">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-ink-800 mb-1">Email Login</label>
                                <input type="email" name="admin_email" value="{{ old('admin_email') }}" placeholder="admin@desa.kdmp.id"
                                       class="w-full rounded border border-paper-300 px-2.5 py-1.5 text-xs text-ink-900">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-ink-800 mb-1">Kata Sandi Baru</label>
                                <input type="password" name="admin_password" placeholder="Minimal 8 karakter"
                                       class="w-full rounded border border-paper-300 px-2.5 py-1.5 text-xs text-ink-900">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-ink-800 mb-1">Ulangi Kata Sandi</label>
                                <input type="password" name="admin_password_confirmation" placeholder="Ulangi kata sandi"
                                       class="w-full rounded border border-paper-300 px-2.5 py-1.5 text-xs text-ink-900">
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
            <a href="{{ route('master.entitas.index') }}"
               class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                Batal
            </a>
            <button type="submit"
                    class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                {{ $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Entitas' }}
            </button>
        </div>
    </form>
</x-layouts.app>

