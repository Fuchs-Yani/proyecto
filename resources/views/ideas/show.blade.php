<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">{{ $projectIdea->title }}</h2>
        <p class="text-sm text-slate-500">Estado: {{ ['available' => 'Disponible', 'in_development' => 'En desarrollo', 'completed' => 'Terminado', 'cancelled' => 'Cancelado', 'pendiente' => 'Pendiente'][$projectIdea->status] ?? $projectIdea->status }} · Beneficiario: {{ $projectIdea->organizer->name ?? '' }}</p>
    </x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        @if (session('status'))<div class="bg-white border rounded-lg p-3 text-sm">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="bg-white border border-red-300 rounded-lg p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif

        <div class="bg-white border border-slate-200 rounded-lg p-4"><p class="text-sm">{{ $projectIdea->description }}</p></div>

        @role('student')
            @if (! $alreadyApplied)
                @can('create', App\Models\Assignment::class)
                    <form method="POST" action="{{ route('assignments.store', $projectIdea) }}">@csrf<button class="px-5 py-2.5 bg-slate-900 text-white rounded-md text-sm">Postularme</button></form>
                @endcan
            @else
                <p class="bg-white border rounded-lg p-3 text-sm">Ya postulado.</p>
                @php($mine = $projectIdea->assignments->firstWhere('student_id', auth()->id()))
                @if ($mine)
                    @can('delete', $mine)
                        <form method="POST" action="{{ route('assignments.destroy', $mine) }}">@csrf @method('DELETE')<button class="px-3 py-1.5 border border-slate-300 rounded-md text-sm text-red-700">Retirar postulación</button></form>
                    @endcan
                @endif
            @endif
        @endrole

        @if (auth()->check() && (auth()->user()->hasRole('admin') || $projectIdea->organizer_id === auth()->id()))
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <h3 class="font-bold text-slate-900 text-sm">Postulaciones ({{ $projectIdea->assignments->count() }})</h3>
                @forelse($projectIdea->assignments as $a)
                    <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                        <span>
                            {{ $a->student->name ?? '' }}
                            @if ($a->student?->email) · {{ $a->student->email }} @endif
                            · {{ ['applied' => 'Postulado', 'accepted' => 'Aceptado', 'rejected' => 'Rechazado', 'in_progress' => 'En progreso', 'completed' => 'Completado'][$a->status] ?? $a->status }}
                            · {{ $a->applied_at }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sin postulaciones.</p>
                @endforelse
            </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <h3 class="font-bold text-slate-900 text-sm">Adjuntos</h3>
            @forelse($projectIdea->attachments as $a)
                <div class="flex items-center justify-between gap-3 py-2 text-sm border-b border-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        @if (in_array(strtolower($a->file_type), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true))
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($a->file_path) }}"
                                alt="{{ $a->file_name }}"
                                class="h-14 w-14 object-cover rounded border border-slate-200">
                        @else
                            <span class="h-14 w-14 flex items-center justify-center rounded border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-600">{{ strtoupper($a->file_type ?: 'ARCHIVO') }}</span>
                        @endif
                        <span class="truncate">{{ $a->file_name }} ({{ strtoupper($a->file_type) }})</span>
                    </div>
                    <span class="flex gap-3 shrink-0">
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
                    <form method="POST" action="{{ route('attachments.store', $projectIdea) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-2">@csrf
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif" class="border border-slate-300 rounded p-1 text-sm" required>
                        <button class="px-4 py-1.5 bg-slate-900 text-white rounded-md text-sm">Subir archivo</button>
                    </form>
                    <p class="text-xs text-slate-500 mt-1">PDF o imágenes (JPG, PNG, WEBP, GIF) · máx. 10 MB</p>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>
