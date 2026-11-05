<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Avances</h2>
            <p class="text-sm text-slate-500">Revisá los avances enviados por los alumnos y aprobá los que estén listos.</p>
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto px-4 space-y-4">
        <x-flash-toast />

        @forelse ($ideas as $idea)
            <section class="bg-white border border-slate-200 rounded-lg overflow-hidden">
                <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3">
                    <div class="min-w-0">
                        <a href="{{ route('ideas.show', $idea) }}" class="font-bold text-slate-900 hover:underline">{{ $idea->title }}</a>
                        <p class="text-sm text-slate-500">
                            Beneficiario: {{ $idea->organizer->name ?? '—' }}
                            · {{ $idea->total_updates_count }} {{ $idea->total_updates_count === 1 ? 'avance' : 'avances' }}
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $idea->pending_updates_count > 0 ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
                        {{ $idea->pending_updates_count }} {{ $idea->pending_updates_count === 1 ? 'pendiente' : 'pendientes' }}
                    </span>
                </header>

                <div class="space-y-3 p-4">
                    @foreach ($idea->updates->sortBy(fn ($update) => $update->isPending() ? 0 : 1) as $update)
                        <x-progress-card :update="$update" :show-approve="true" />
                    @endforeach
                </div>
            </section>
        @empty
            <div class="bg-white border border-slate-200 rounded-lg px-4 py-8 text-center text-sm text-slate-500">
                Todavía no hay avances enviados por los alumnos.
            </div>
        @endforelse

        @if ($ideas->hasPages())
            <div>{{ $ideas->links() }}</div>
        @endif
    </div>
</x-app-layout>
