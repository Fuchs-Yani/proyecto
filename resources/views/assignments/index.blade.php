<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-bold text-slate-900">
        @role('student') Mis postulaciones @elserole('beneficiario') Postulaciones recibidas @else Todas las postulaciones @endrole
    </h2></x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        @if(session('status'))<div class="bg-white border rounded-lg p-3 text-sm">{{ session('status') }}</div>@endif
        @forelse($assignments as $a)
            <div class="bg-white border border-slate-200 rounded-lg p-3 flex justify-between text-sm">
                <span><a href="{{ route('ideas.show', $a->projectIdea) }}" class="font-bold text-slate-900">{{ $a->projectIdea->title ?? '-' }}</a>
                @role('beneficiario|admin') · {{ $a->student->name ?? '' }} @endrole · {{ $a->status }}</span>
                @can('delete', $a)<form method="POST" action="{{ route('assignments.destroy', $a) }}">@csrf @method('DELETE')<button class="text-red-700">Eliminar</button></form>@endcan
            </div>
        @empty<div class="bg-white border rounded-lg p-6 text-center text-sm text-slate-500">No hay postulaciones.</div>@endforelse
        {{ $assignments->links() }}
    </div>
</x-app-layout>
