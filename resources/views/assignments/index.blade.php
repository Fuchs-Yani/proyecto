<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-bold text-slate-900">
        @role('student') Mis postulaciones @elserole('beneficiario') Postulaciones recibidas @else Todas las postulaciones @endrole
    </h2></x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        <x-flash-toast />

        <form method="GET" action="{{ route('assignments.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" spellcheck="false"
                placeholder="Buscar por proyecto, alumno o email…"
                class="w-full sm:w-80 rounded-md border border-slate-300 p-2 text-sm" />
            <button class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Buscar</button>
            @if (request()->filled('search'))
                <a href="{{ route('assignments.index') }}" class="px-4 py-2 border border-slate-300 rounded-md text-sm">Limpiar</a>
            @endif
        </form>

        @if (request()->filled('search'))
            <p class="text-sm text-slate-500">Resultados para «{{ request('search') }}»: {{ $assignments->total() }}.</p>
        @endif

        @forelse($assignments as $a)
            <div class="bg-white border border-slate-200 rounded-lg p-3 text-sm space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>
                        <a href="{{ route('ideas.show', $a->projectIdea) }}" class="font-bold text-slate-900">{{ $a->projectIdea->title ?? '-' }}</a>
                        @role('beneficiario|admin|profesor') · {{ $a->student->name ?? '' }} @endrole
                        · {{ ['applied' => 'Postulado', 'accepted' => 'Aceptado', 'rejected' => 'Rechazado', 'in_progress' => 'En progreso', 'completed' => 'Completado'][$a->status] ?? $a->status }}
                    </span>
                    @can('delete', $a)
                        <form method="POST" action="{{ route('assignments.destroy', $a) }}">@csrf @method('DELETE')<button class="text-red-700">Eliminar</button></form>
                    @endcan
                </div>

                @role('student')
                    @if ($a->updates->isNotEmpty())
                        <div class="space-y-2 border-t border-slate-100 pt-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mis avances</p>
                            @foreach ($a->updates->sortByDesc('created_at') as $update)
                                <x-progress-card :update="$update" :show-student="false" />
                            @endforeach
                        </div>
                    @endif

                    @if ($a->status !== 'rejected')
                        <div class="border-t border-slate-100 pt-3">
                            <x-progress-form :assignment="$a" />
                        </div>
                    @endif
                @endrole
            </div>
        @empty
            <div class="bg-white border rounded-lg p-6 text-center text-sm text-slate-500">{{ request()->filled('search') ? 'No se encontraron postulaciones con ese criterio.' : 'No hay postulaciones.' }}</div>
        @endforelse
        {{ $assignments->links() }}
    </div>
</x-app-layout>
