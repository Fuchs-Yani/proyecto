<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-bold text-slate-900">
        @role('student') Mis postulaciones @elserole('beneficiario') Postulaciones recibidas @else Todas las postulaciones @endrole
    </h2></x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        <x-flash-toast />

        @php
            $statusLabels = ['applied' => 'Postulados', 'accepted' => 'Aceptados', 'rejected' => 'Rechazados', 'in_progress' => 'En progreso', 'completed' => 'Completados', 'withdrawn' => 'Abandonados'];
        @endphp

        <form method="GET" action="{{ route('assignments.index') }}" class="flex flex-wrap items-center gap-2">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <input type="search" name="search" value="{{ request('search') }}" spellcheck="false"
                placeholder="Buscar por proyecto, alumno o email…"
                class="w-full sm:w-80 rounded-md border border-slate-300 p-2 text-sm" />
            <button class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Buscar</button>
            @if (request()->filled('search'))
                <a href="{{ route('assignments.index', $status ? ['status' => $status] : []) }}" class="px-4 py-2 border border-slate-300 rounded-md text-sm">Limpiar</a>
            @endif
        </form>

        @if ($status)
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                <span>Mostrando postulaciones: <b>{{ $statusLabels[$status] ?? $status }}</b>.</span>
                <a href="{{ route('assignments.index') }}" class="text-slate-600 underline">Quitar filtro</a>
            </div>
        @endif

        @if (request()->filled('search'))
            <p class="text-sm text-slate-500">Resultados para «{{ request('search') }}»: {{ $assignments->total() }}.</p>
        @endif

        @forelse($assignments as $a)
            <div id="postulacion-{{ $a->id }}" class="bg-white border border-slate-200 rounded-lg p-3 text-sm space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>
                        <a href="{{ route('ideas.show', $a->projectIdea) }}" class="font-bold text-slate-900">{{ $a->projectIdea->title ?? '-' }}</a>
                        @role('beneficiario|admin|profesor') · {{ $a->student->name ?? '' }} @endrole
                        · {{ ['applied' => 'Postulado', 'accepted' => 'Aceptado', 'rejected' => 'Rechazado', 'in_progress' => 'En progreso', 'completed' => 'Completado', 'withdrawn' => 'Abandonado'][$a->status] ?? $a->status }}
                    </span>
                    <span class="flex flex-wrap items-center gap-2">
                        @hasanyrole('profesor|admin')
                            @if ($a->status === 'applied')
                                @can('review', $a)
                                    <form method="POST" action="{{ route('assignments.status.update', $a) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="accepted">
                                        <button class="rounded-md bg-slate-900 px-3 py-1.5 text-white hover:bg-slate-800">Aceptar</button>
                                    </form>
                                    <form method="POST" action="{{ route('assignments.status.update', $a) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="rounded-md border border-red-300 px-3 py-1.5 text-red-700 hover:bg-red-50">Rechazar</button>
                                    </form>
                                @endcan
                            @endif
                        @endhasanyrole
                        @can('delete', $a)
                            <form method="POST" action="{{ route('assignments.destroy', $a) }}">@csrf @method('DELETE')<button class="text-red-700">Eliminar</button></form>
                        @endcan
                        @can('withdraw', $a)
                            <form method="POST" action="{{ route('assignments.withdraw', $a) }}"
                                onsubmit="return confirm('¿Abandonar este proyecto? Se liberará tu cupo.');">
                                @csrf
                                @method('PATCH')
                                <button class="text-red-700">Abandonar proyecto</button>
                            </form>
                        @endcan
                    </span>
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

                    @if ($a->canReceiveUpdates())
                        <div class="border-t border-slate-100 pt-3">
                            <x-progress-form :assignment="$a" />
                        </div>
                    @elseif (in_array($a->status, ['accepted', 'in_progress'], true))
                        <p class="border-t border-slate-100 pt-3 text-xs text-slate-500">
                            Los avances se habilitan cuando el proyecto está en desarrollo.
                        </p>
                    @elseif ($a->status === 'applied')
                        <p class="border-t border-slate-100 pt-3 text-xs text-slate-500">
                            Podrás subir avances cuando el profesor acepte tu postulación.
                        </p>
                    @endif
                @endrole
            </div>
        @empty
            <div class="bg-white border rounded-lg p-6 text-center text-sm text-slate-500">{{ request()->filled('search') ? 'No se encontraron postulaciones con ese criterio.' : 'No hay postulaciones.' }}</div>
        @endforelse
        {{ $assignments->links() }}
    </div>
</x-app-layout>
