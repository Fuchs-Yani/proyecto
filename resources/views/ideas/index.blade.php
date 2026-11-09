<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                @auth
                    @if (auth()->user()->hasAnyRole(['profesor', 'admin']))
                        <h2 class="text-xl font-bold text-slate-900">Proyectos</h2>
                        <p class="text-sm text-slate-500">Analizá cada idea y definí cuántos alumnos pueden participar.</p>
                    @else
                        <h2 class="text-xl font-bold text-slate-900">Ideas disponibles</h2>
                        <p class="text-sm text-slate-500">Rol: {{ auth()->user()->roleLabel() }}</p>
                    @endif
                @else
                    <h2 class="text-xl font-bold text-slate-900">Proyectos terminados</h2>
                    <p class="text-sm text-slate-500">Explora proyectos desarrollados por estudiantes</p>
                @endauth
            </div>
            @can('create', App\Models\ProjectIdea::class)
                <a href="{{ route('ideas.create') }}" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm whitespace-nowrap">Nueva idea</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        @include('ideas._search', ['action' => route('ideas.index'), 'ideas' => $ideas, 'tags' => $tags])
        @include('ideas._list', ['ideas' => $ideas, 'emptyMessage' => request()->filled('search') ? 'No se encontraron ideas con ese nombre.' : null])
    </div>
</x-app-layout>
