<x-layouts.app :title="$title" eyebrow="Master Data">
    <div class="mb-5">
        <a href="{{ route('master.barang.index') }}" class="inline-flex items-center gap-1 text-sm text-ink-600 hover:text-ink-900">
            &larr; Kembali ke Daftar Barang
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

    <form method="POST" action="{{ route('master.barang.hubungkan.store') }}"
          class="mx-auto max-w-2xl rounded-sm border border-paper-300 bg-paper-50 p-6 shadow-2xs">
        @csrf

        <div class="mb-5 border-b border-paper-200 pb-3">
            <h2 class="font-display text-lg font-semibold text-ink-900">{{ $title }}</h2>
            <p class="text-xs text-ink-600">
                Entitas: <strong>{{ $entitas?->nama_entitas }}</strong>
            </p>
        </div>

        <div class="mb-5 rounded-sm border border-padi-500 bg-padi-100 px-4 py-3 text-sm text-ink-800">
            Masukkan kode barang yang diberikan oleh desa mitra aktif. Sistem akan menghubungkan barang yang sama ke entitas Anda tanpa mengubah data master, stok, atau pengaturan milik mitra.
        </div>

        <div>
            <label for="kode_barang" class="mb-1 block text-xs font-medium text-ink-800">
                Kode Barang Mitra
            </label>
            <input id="kode_barang" type="text" name="kode_barang" value="{{ old('kode_barang') }}"
                   required maxlength="30" autocomplete="off" placeholder="Contoh: BRG-TELUR-01"
                   class="w-full rounded-sm border border-paper-300 bg-white px-3 py-2 font-mono text-sm uppercase text-ink-900 focus:border-merah-400 focus:outline-none">
        </div>

        <p class="mt-2 text-xs text-ink-600">
            Barang yang berhasil dihubungkan muncul pada daftar entitas Anda. Harga jual dan batas stok entitas Anda dapat diatur sesudahnya.
        </p>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-paper-200 pt-4">
            <a href="{{ route('master.barang.index') }}"
               class="rounded-sm border border-paper-300 px-4 py-2 text-xs font-medium text-ink-700 hover:bg-paper-100">
                Batal
            </a>
            <button type="submit"
                    class="rounded-sm bg-merah-500 px-5 py-2 text-xs font-semibold text-paper-50 hover:bg-merah-600">
                Hubungkan Barang
            </button>
        </div>
    </form>
</x-layouts.app>
