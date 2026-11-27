<x-layouts.app title="Notifikasi" eyebrow="Pemberitahuan">
    <div class="mx-auto max-w-4xl space-y-4">
        <div>
            <h1 class="font-display text-2xl font-semibold text-ink-900">Notifikasi</h1>
            <p class="mt-1 text-sm text-ink-600">Peringatan stok minimum dan pembaruan rekomendasi untuk akun Anda.</p>
        </div>
        @forelse ($notifications as $notification)
            <article class="rounded-sm border border-paper-300 {{ $notification->read_at ? 'bg-paper-50' : 'bg-amber-50' }} p-4">
                <h2 class="font-semibold text-ink-900">{{ $notification->data['title'] ?? 'Pemberitahuan' }}</h2>
                <p class="mt-1 text-sm text-ink-700">{{ $notification->data['message'] ?? '' }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                    <span class="text-ink-600">{{ $notification->created_at?->format('d M Y H:i') }}</span>
                    @if (! empty($notification->data['url']))
                        <a href="{{ $notification->data['url'] }}" class="font-semibold text-sawah-800 underline">Buka</a>
                    @endif
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="font-semibold text-ink-700 underline">Tandai sudah dibaca</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-sm border border-dashed border-paper-300 bg-paper-50 px-5 py-10 text-center text-sm text-ink-600">
                Belum ada notifikasi.
            </div>
        @endforelse
        {{ $notifications->links() }}
    </div>
</x-layouts.app>
