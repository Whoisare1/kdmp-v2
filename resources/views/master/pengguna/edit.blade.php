<x-layouts.app :title="$title" eyebrow="M0 Master Data">
    <div class="max-w-2xl mx-auto">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-ink-900 tracking-tight">Edit Data Staf</h2>
                <p class="text-xs text-paper-500 mt-0.5">Perbarui profil, kata sandi, status akun, dan wewenang peran staf.</p>
            </div>
            <a href="{{ route($routeBase . '.index') }}" class="text-xs text-paper-500 hover:text-ink-900 transition flex items-center gap-1">
                ← Kembali ke Daftar Staf
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-rose-300 bg-rose-50 p-4 text-xs text-rose-800">
                <p class="font-bold mb-1">Periksa isian berikut:</p>
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route($routeBase . '.update', $user->id) }}" class="space-y-5 rounded-xl border border-paper-300 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-ink-800 mb-1.5">Nama Lengkap Staf <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" value="{{ old('nama', $user->nama) }}" required
                       class="w-full rounded-lg border border-paper-300 px-3.5 py-2.5 text-xs text-ink-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-ink-800 mb-1.5">Alamat Email Login <span class="text-rose-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full rounded-lg border border-paper-300 px-3.5 py-2.5 text-xs text-ink-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-ink-800 mb-1.5">Ganti Kata Sandi (Opsional)</label>
                <input type="password" name="password" minlength="8" placeholder="Kosongkan jika tidak ingin mengubah kata sandi"
                       class="w-full rounded-lg border border-paper-300 px-3.5 py-2.5 text-xs text-ink-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-ink-800 mb-1.5">Status Akun</label>
                <select name="is_active" class="w-full rounded-lg border border-paper-300 bg-white px-3.5 py-2.5 text-xs text-ink-900 focus:border-emerald-500 outline-none">
                    <option value="1" {{ old('is_active', $user->is_active) ? 'selected' : '' }}>Aktif (Dapat Masuk Sistem)</option>
                    <option value="0" {{ !old('is_active', $user->is_active) ? 'selected' : '' }}>Nonaktif (Akses Diblokir)</option>
                </select>
            </div>

            <div class="border-t border-paper-200 pt-4">
                <label class="block text-xs font-semibold text-ink-800 mb-2">
                    Tugaskan Peran / Wewenang <span class="text-rose-500">*</span>
                    <span class="font-normal text-paper-500">(Boleh memilih lebih dari satu peran jika staf merangkap)</span>
                </label>

                @php
                    $userPeranIds = old('peran_ids', $user->peran->pluck('id_peran')->toArray());
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                    @foreach($perans as $peran)
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-paper-200 hover:border-emerald-400 hover:bg-emerald-50/20 cursor-pointer transition">
                            <input type="checkbox" name="peran_ids[]" value="{{ $peran->id_peran }}"
                                   {{ in_array($peran->id_peran, $userPeranIds) ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-paper-300 text-emerald-600 focus:ring-emerald-500 h-4 w-4">
                            <div>
                                <span class="block text-xs font-bold text-ink-900">{{ $peran->nama }}</span>
                                <span class="block text-[11px] text-paper-500 mt-0.5">
                                    @if($peran->kode === 'kasir')
                                        Melayani transaksi kasir sembako, cetak struk POS, dan penerimaan kas kasir.
                                    @elseif($peran->kode === 'admin_gudang')
                                        Mengelola posisi stok, penerimaan barang (GRN), expired batch, dan stock opname.
                                    @elseif($peran->kode === 'surveyor')
                                        Menginput kuesioner survei pangan, demografi RT, dan kalender panen produsen lokal.
                                    @elseif($peran->kode === 'manajer')
                                        Administrator desa: memiliki akses penuh ke seluruh modul koperasi.
                                    @else
                                        Mengelola modul {{ $peran->nama }} pada koperasi.
                                    @endif
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-paper-200">
                <a href="{{ route($routeBase . '.index') }}" class="rounded-lg border border-paper-300 bg-white px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100 transition shadow-sm">
                    Batal
                </a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 text-xs font-semibold text-white hover:bg-emerald-500 transition shadow-md">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>

