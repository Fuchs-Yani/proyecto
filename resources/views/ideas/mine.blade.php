<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Mis ideas</h2>
                <p class="text-sm text-slate-500">Acá están las ideas que publicaste. Podés revisar su estado, adjuntos y postulaciones.</p>
            </div>
            @can('create', App\Models\ProjectIdea::class)
                <a href="{{ route('ideas.create') }}" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm whitespace-nowrap">Nueva idea</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        @include('ideas._search', ['action' => route('ideas.mine'), 'ideas' => $ideas, 'tags' => $tags])
        @include('ideas._list', ['ideas' => $ideas, 'emptyMessage' => request()->filled('search') ? 'No se encontraron ideas tuyas con ese nombre.' : 'Todavía no publicaste ninguna idea.'])
    </div>
</x-app-layout>
