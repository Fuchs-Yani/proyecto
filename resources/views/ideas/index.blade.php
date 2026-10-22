<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ auth()->check() ? 'Ideas disponibles' : 'Proyectos terminados' }}</h2>
                <p class="text-sm text-slate-500">
                    @auth Rol: {{ auth()->user()->roleLabel() }}
                    @else Explora proyectos desarrollados por estudiantes
                    @endauth
                </p>
            </div>
            @can('create', App\Models\ProjectIdea::class)
                <a href="{{ route('ideas.create') }}" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm whitespace-nowrap">Nueva idea</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        @include('ideas._list', ['ideas' => $ideas])
    </div>
</x-app-layout>
