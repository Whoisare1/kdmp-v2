<x-layouts.app :title="$title" eyebrow="Perencanaan Pengadaan">
    <div class="mx-auto max-w-6xl space-y-6">
        <div><p class="font-mono text-[11px] uppercase tracking-wide text-ink-600/60">Perencanaan Pengadaan</p><h1 class="mt-1 font-display text-2xl font-semibold text-ink-900">{{ $title }}</h1></div>
        <form method="GET" class="grid gap-4 rounded-sm border border-paper-300 bg-paper-50 p-4 md:grid-cols-4">
            <label class="text-sm font-medium text-ink-700">Komoditas
                <select name="id_komoditas" required class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-normal"><option value="">Pilih komoditas</option>@foreach ($komoditas as $item)<option value="{{ $item->id_komoditas }}" @selected($selected == $item->id_komoditas)>{{ $item->nama }}</option>@endforeach</select>
            </label>
            <label class="text-sm font-medium text-ink-700">Tahun<input type="number" name="tahun" value="{{ $tahun }}" min="2000" max="2100" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-normal"></label>
            <label class="text-sm font-medium text-ink-700">Periode<select name="periode" class="mt-1 block w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 font-normal"><option value="ganjil">Ganjil (Januari–Juni)</option><option value="genap">Genap (Juli–Desember)</option></select></label>
            <button class="self-end rounded-sm bg-merah-500 px-4 py-2 font-medium text-paper-50">Bandingkan Harga</button>
        </form>
        @if ($selected)
            @if ($items->isEmpty())
                <div class="rounded-sm border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Belum ada data harga untuk komoditas dan periode tersebut.</div>
            @else
                <div class="grid gap-4 md:grid-cols-3">@foreach ([['Terendah', $lowest], ['Tertinggi', $highest], ['Selisih', $difference]] as [$label, $value])<div class="rounded-sm border border-paper-300 bg-paper-50 p-4"><p class="text-xs text-ink-600/60">{{ $label }}</p><p class="mt-1 text-xl font-semibold">Rp {{ number_format($value, 2, ',', '.') }}</p></div>@endforeach</div>
                <div class="overflow-hidden rounded-sm border border-paper-300 bg-paper-50"><table class="w-full text-left text-sm"><thead class="border-b border-paper-300 bg-paper-200/60"><tr><th class="px-4 py-3">Peringkat</th><th class="px-4 py-3">Desa sumber</th><th class="px-4 py-3">Harga</th><th class="px-4 py-3">Satuan</th></tr></thead><tbody>@foreach ($items as $item)<tr class="border-b border-paper-200 last:border-0"><td class="px-4 py-3">{{ $item->rank_harga }}</td><td class="px-4 py-3">{{ $item->wilayahSumber?->nama ?? '—' }}</td><td class="px-4 py-3">Rp {{ number_format((float) $item->harga_efektif, 2, ',', '.') }}</td><td class="px-4 py-3">{{ $satuan ?: '—' }}</td></tr>@endforeach</tbody></table></div>
            @endif
        @endif
    </div>
</x-layouts.app>
