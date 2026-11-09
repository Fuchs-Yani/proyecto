<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Notificaciones</h2>
                <p class="text-sm text-slate-500">Novedades sobre tus postulaciones, ideas y avances.</p>
            </div>

            @if (auth()->user()->unreadNotifications()->count() > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Marcar todas como leídas
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 space-y-3">
        <x-flash-toast />

        @forelse ($notifications as $notification)
            @php($data = $notification->data)
            <a href="{{ route('notifications.read', $notification->id) }}"
                class="block rounded-lg border p-4 transition hover:border-slate-400 {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-blue-200 bg-blue-50' }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0 space-y-1">
                        <p class="font-semibold text-slate-900">{{ $data['title'] ?? 'Notificación' }}</p>
                        <p class="text-sm text-slate-700">{{ $data['message'] ?? '' }}</p>
                    </div>
                    @if (! $notification->read_at)
                        <span class="shrink-0 rounded-full border border-blue-200 bg-white px-2 py-0.5 text-xs font-semibold text-blue-700">
                            Nueva
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    {{ $notification->created_at?->diffForHumans() }}
                </p>
            </a>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                Todavía no tenés notificaciones.
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <div>{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
