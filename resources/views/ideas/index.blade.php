<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Ideas disponibles</h2>
        <p class="text-sm text-slate-500">Rol: {{ auth()->user()->getRoleNames()->join(', ') }}</p>
    </x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        @if (session('status'))<div class="bg-white border border-slate-200 rounded-lg p-3 text-sm">{{ session('status') }}</div>@endif
        @forelse($ideas as $idea)
            <div class="bg-white border border-slate-200 rounded-lg p-4 flex justify-between items-center">
                <div>
                    <a href="{{ route('ideas.show', $idea) }}" class="font-bold text-slate-900">{{ $idea->title }}</a>
                    <p class="text-sm text-slate-500">{{ $idea->organizer->name ?? '' }} · {{ $idea->status }} · 📎 {{ $idea->attachments->count() }}</p>
                </div>
                <div class="flex gap-2 items-center">
                    <a href="{{ route('ideas.show', $idea) }}" class="px-3 py-1.5 border border-slate-300 rounded-md text-sm">Ver</a>
                    @role('student')
                        @can('create', App\Models\Assignment::class)
                            <form method="POST" action="{{ route('assignments.store', $idea) }}">@csrf<button class="px-3 py-1.5 bg-slate-900 text-white rounded-md text-sm">Postularme</button></form>
                        @endcan
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
            <div class="bg-white border rounded-lg p-6 text-center text-sm text-slate-500">No hay ideas disponibles.</div>
        @endforelse
        {{ $ideas->links() }}
    </div>
</x-app-layout>
