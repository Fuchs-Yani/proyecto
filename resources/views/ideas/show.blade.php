<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">{{ $projectIdea->title }}</h2>
        <p class="text-sm text-slate-500">Estado: {{ $projectIdea->status }} · Beneficiario: {{ $projectIdea->organizer->name ?? '' }}</p>
    </x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        @if (session('status'))<div class="bg-white border rounded-lg p-3 text-sm">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="bg-white border border-red-300 rounded-lg p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif

        <div class="bg-white border border-slate-200 rounded-lg p-4"><p class="text-sm">{{ $projectIdea->description }}</p></div>

        @role('student')
            @if(!$alreadyApplied)
                @can('create', App\Models\Assignment::class)
                    <form method="POST" action="{{ route('assignments.store', $projectIdea) }}">@csrf<button class="px-5 py-2.5 bg-slate-900 text-white rounded-md text-sm">Postularme</button></form>
                @endcan
            @else
                <p class="bg-white border rounded-lg p-3 text-sm">Ya postulado.</p>
                @php($mine = $projectIdea->assignments->firstWhere('student_id', auth()->id()))
                @if($mine)@can('delete', $mine)
                    <form method="POST" action="{{ route('assignments.destroy', $mine) }}">@csrf @method('DELETE')<button class="px-3 py-1.5 border border-slate-300 rounded-md text-sm text-red-700">Retirar postulación</button></form>
                @endcan@endif
            @endif
        @endrole

        @role('beneficiario|admin')
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <h3 class="font-bold text-slate-900 text-sm">Postulaciones ({{ $projectIdea->assignments->count() }})</h3>
                @forelse($projectIdea->assignments as $a)
                    <div class="flex justify-between border-b border-slate-100 py-2 text-sm"><span>{{ $a->student->name ?? '' }} · {{ $a->status }} · {{ $a->applied_at }}</span></div>
                @empty<p class="text-sm text-slate-500">Sin postulaciones.</p>@endforelse
            </div>
        @endrole

        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <h3 class="font-bold text-slate-900 text-sm">Adjuntos</h3>
            @forelse($projectIdea->attachments as $a)
                <div class="flex justify-between py-2 text-sm border-b border-slate-100">
                    <span>{{ $a->file_name }} ({{ $a->file_type }})</span>
                    <span class="flex gap-3">
                        @can('view', $a)<a href="{{ route('attachments.download', $a) }}" class="underline">Descargar</a>@endcan
                        @can('delete', $a)
                            @if(auth()->user()->hasRole('admin') || $projectIdea->organizer_id === auth()->id())
                                <form method="POST" action="{{ route('attachments.destroy', $a) }}">@csrf @method('DELETE')<button class="text-red-700">Eliminar</button></form>
                            @endif
                        @endcan
                    </span>
                </div>
            @empty<p class="text-sm text-slate-500">Sin archivos.</p>@endforelse
            @can('create', App\Models\Attachment::class)
                @if(auth()->user()->hasRole('admin') || $projectIdea->organizer_id === auth()->id())
                    <form method="POST" action="{{ route('attachments.store', $projectIdea) }}" enctype="multipart/form-data" class="mt-3 flex gap-2">@csrf
                        <input type="file" name="file" class="border border-slate-300 rounded p-1 text-sm" required>
                        <button class="px-4 py-1.5 bg-slate-900 text-white rounded-md text-sm">Subir</button>
                    </form>
                    <p class="text-xs text-slate-500 mt-1">pdf, png, jpg, zip · máx 10MB</p>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>
