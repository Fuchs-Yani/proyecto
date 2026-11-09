<x-flash-toast />

@if ($errors->any())
    <div class="rounded-lg border border-red-300 bg-white p-3 text-sm text-red-700">{{ $errors->first() }}</div>
@endif

@forelse($ideas as $idea)
    <div class="bg-white border border-slate-200 rounded-lg p-4 flex justify-between items-center">
        <div>
            <a href="{{ route('ideas.show', $idea) }}" class="font-bold text-slate-900">{{ $idea->title }}</a>
            <p class="text-sm text-slate-500">{{ $idea->organizer->name ?? '' }} · {{ ['available' => 'Disponible', 'in_development' => 'En desarrollo', 'completed' => 'Terminado', 'cancelled' => 'Cancelado'][$idea->status] ?? $idea->status }} · 📎 {{ $idea->attachments->count() }}@if ($idea->max_students !== null) · 👥 {{ $idea->activeAssignmentsCount() }}/{{ $idea->max_students }} postulantes @elseif ($idea->isPendingReview()) · ⏳ Pendiente de revisión @endif</p>
            @if ($idea->tags->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($idea->tags as $tag)
                        <a href="{{ route('ideas.index', ['tag' => $tag->id]) }}" class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded-full text-xs text-slate-600 hover:bg-slate-200">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="flex gap-2 items-center">
            <a href="{{ route('ideas.show', $idea) }}" class="px-3 py-1.5 border border-slate-300 rounded-md text-sm">Ver</a>

            @hasanyrole('profesor|admin')
                @can('analyze', $idea)
                    <details class="relative">
                        <summary class="cursor-pointer list-none px-3 py-1.5 border border-slate-300 rounded-md text-sm">
                            {{ $idea->max_students === null ? 'Analizar' : 'Cambiar cupo' }}
                        </summary>
                        <div class="absolute right-0 z-20 mt-1 w-64 rounded-md border border-slate-200 bg-white p-3 shadow-lg">
                            <form method="POST" action="{{ route('ideas.quota.update', $idea) }}" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <label for="max_students_{{ $idea->id }}" class="block text-xs font-semibold text-slate-600">
                                    Cantidad de alumnos ({{ \App\Models\ProjectIdea::MIN_STUDENTS }} a {{ \App\Models\ProjectIdea::MAX_STUDENTS }})
                                </label>
                                <input id="max_students_{{ $idea->id }}" type="number" name="max_students"
                                    min="{{ \App\Models\ProjectIdea::MIN_STUDENTS }}" max="{{ \App\Models\ProjectIdea::MAX_STUDENTS }}"
                                    value="{{ $idea->max_students }}" required
                                    class="w-full rounded-md border border-slate-300 p-2 text-sm">
                                <p class="text-xs text-slate-500">Postulantes actuales: {{ $idea->activeAssignmentsCount() }}</p>
                                <button class="w-full rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Guardar cupo</button>
                            </form>
                        </div>
                    </details>
                @endcan

                @if ($idea->status === 'available' && $idea->acceptedAssignmentsCount() > 0)
                    @can('startDevelopment', $idea)
                        <form method="POST" action="{{ route('ideas.start', $idea) }}"
                            onsubmit="return confirm('¿Iniciar el proyecto con {{ $idea->acceptedAssignmentsCount() }} integrante(s)? Las postulaciones pendientes serán rechazadas.');">
                            @csrf
                            @method('PATCH')
                            <button class="px-3 py-1.5 bg-amber-500 text-white rounded-md text-sm hover:bg-amber-600">Iniciar proyecto</button>
                        </form>
                    @endcan
                @endif
            @endhasanyrole

            @role('student')
                @if ($idea->isOpenForApplications() && $idea->hasAvailableSlots())
                    @can('create', App\Models\Assignment::class)
                        <form method="POST" action="{{ route('assignments.store', $idea) }}">@csrf<button class="px-3 py-1.5 bg-slate-900 text-white rounded-md text-sm">Postularme</button></form>
                    @endcan
                @endif
            @endrole
            @role('beneficiario')
                @if($idea->organizer_id === auth()->id())<span class="px-2 py-1 bg-slate-100 border text-xs rounded">Mi idea</span>@endif
            @endrole
            @role('admin')
                <a href="/admin/project-ideas/{{ $idea->id }}/edit" class="px-3 py-1.5 border border-slate-300 rounded-md text-sm">Admin</a>
            @endrole
        </div>
    </div>
@empty
    <div class="bg-white border rounded-lg p-6 text-center text-sm text-slate-500">{{ $emptyMessage ?? 'No hay ideas disponibles.' }}</div>
@endforelse

{{ $ideas->links() }}
