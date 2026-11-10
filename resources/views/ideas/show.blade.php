<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">{{ $projectIdea->title }}</h2>
        <p class="text-sm text-slate-500">Estado: {{ ['available' => 'Disponible', 'in_development' => 'En desarrollo', 'completed' => 'Terminado', 'cancelled' => 'Cancelado'][$projectIdea->status] ?? $projectIdea->status }} · Beneficiario: {{ $projectIdea->organizer->name ?? '' }}</p>
        @if ($projectIdea->max_students !== null)
            <p class="text-sm text-slate-500">
                Cupo de postulantes: {{ $projectIdea->activeAssignmentsCount() }} / {{ $projectIdea->max_students }}
                @if (! $projectIdea->hasAvailableSlots()) · Cupo completo @endif
            </p>
        @elseif ($projectIdea->isPendingReview())
            <p class="text-sm text-slate-500">⏳ Pendiente de revisión del profesor.</p>
        @endif
    </x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        <x-flash-toast />
        @if ($errors->any())<div class="bg-white border border-red-300 rounded-lg p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif

        <div class="bg-white border border-slate-200 rounded-lg p-4"><p class="text-sm">{{ $projectIdea->description }}</p></div>

        @if ($projectIdea->status === 'cancelled' && $projectIdea->cancellation_reason)
            <div class="bg-white border border-slate-200 rounded-lg p-4 space-y-1">
                <p class="text-sm text-slate-700"><span class="font-semibold">Motivo de cancelación:</span> {{ $projectIdea->cancellation_reason }}</p>
                @if ($projectIdea->cancelled_at)
                    <p class="text-xs text-slate-500">Cancelada el {{ $projectIdea->cancelled_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        @endif

        @php
            $viewer = auth()->user();
            $canReviewUpdates = $viewer?->hasAnyRole(['admin', 'profesor']) ?? false;
            $isBeneficiaryOwner = $viewer !== null && $projectIdea->organizer_id === $viewer->id;
            $projectUpdates = $projectIdea->updates;
            $approvedProjectUpdates = $projectUpdates
                ->where('status', \App\Models\ProjectUpdate::STATUS_APPROVED)
                ->sortBy('approved_at');
            $visibleUpdates = ($canReviewUpdates
                ? $projectUpdates
                : $approvedProjectUpdates)
                ->sortByDesc('created_at');
        @endphp

        @if ($canReviewUpdates || $isBeneficiaryOwner)
            <x-project-progress
                :updates="$canReviewUpdates ? $projectUpdates : $approvedProjectUpdates"
                title="Progreso del proyecto"
                subtitle="Basado en las etapas aprobadas por el profesor." />

            <section class="bg-white border border-slate-200 rounded-lg p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Avances del proyecto</h3>
                        <p class="text-xs text-slate-500">
                            {{ $canReviewUpdates
                                ? 'Todos los avances enviados por los alumnos.'
                                : 'Solo se muestran las descripciones e imágenes aprobadas por el profesor.' }}
                        </p>
                    </div>
                    <span class="text-xs text-slate-500">
                        {{ $visibleUpdates->count() }} {{ $visibleUpdates->count() === 1 ? 'avance' : 'avances' }}
                    </span>
                </div>

                @if ($visibleUpdates->isNotEmpty())
                    <x-progress-table
                        :updates="$visibleUpdates"
                        :subtitle="$canReviewUpdates
                            ? 'Todos los avances enviados por los alumnos.'
                            : 'Solo avances aprobados por el profesor.'" />

                    <div class="space-y-3">
                        @foreach ($visibleUpdates as $update)
                            <x-progress-card
                                :update="$update"
                                :show-student="$canReviewUpdates"
                                :show-repository="$canReviewUpdates"
                                :show-approve="$canReviewUpdates" />
                        @endforeach
                    </div>
                @else
                    <p class="py-4 text-center text-sm text-slate-500">
                        Todavía no hay avances aprobados por el profesor.
                    </p>
                @endif
            </section>
        @endif

        @can('update', $projectIdea)
            @php
                $openPanel = match (session('status')) {
                    'Descripción actualizada.' => 'descripcion',
                    'Etiquetas actualizadas.' => 'etiquetas',
                    'Archivo subido.', 'Archivo eliminado.' => 'adjuntos',
                    default => $errors->has('file') ? 'adjuntos' : null,
                };
            @endphp
            <div x-data="{ menu: false, panel: @js($openPanel) }" class="space-y-3">
                <div class="relative inline-block">
                    <button type="button" @click="menu = !menu"
                        class="flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-md text-sm bg-white hover:bg-slate-50">
                        Editar idea <span class="text-[10px]">▼</span>
                    </button>
                    <div x-show="menu" x-cloak @click.outside="menu = false"
                        class="absolute z-20 mt-1 w-64 overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg">
                        <button type="button" @click="panel = 'descripcion'; menu = false"
                            class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50">Descripción de la idea</button>
                        <button type="button" @click="panel = 'adjuntos'; menu = false"
                            class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50">Adjuntos ({{ $projectIdea->attachments->count() }}/{{ \App\Models\ProjectIdea::MAX_ATTACHMENTS }})</button>
                        <button type="button" @click="panel = 'etiquetas'; menu = false"
                            class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50">Etiquetas</button>
                    </div>
                </div>

                <div x-show="panel === 'descripcion'" x-cloak class="bg-white border border-slate-200 rounded-lg p-4 space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm">Descripción de la idea</h3>
                    <form method="POST" action="{{ route('ideas.update', $projectIdea) }}" class="space-y-2">
                        @csrf
                        @method('PATCH')
                        <textarea name="description" rows="6" required spellcheck="false"
                            class="block w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900 rounded-md shadow-sm text-sm">{{ old('description', $projectIdea->description) }}</textarea>
                        @error('description')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        <div class="flex items-center gap-2">
                            <button class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Guardar descripción</button>
                            <button type="button" @click="panel = null" class="px-4 py-2 border border-slate-300 rounded-md text-sm">Cancelar</button>
                        </div>
                    </form>
                </div>

                <div x-show="panel === 'adjuntos'" x-cloak class="bg-white border border-slate-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm">Adjuntos ({{ $projectIdea->attachments->count() }}/{{ \App\Models\ProjectIdea::MAX_ATTACHMENTS }})</h3>
                        <button type="button" @click="panel = null" class="text-sm text-slate-500 underline">Cerrar</button>
                    </div>
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
                                    <form method="POST" action="{{ route('attachments.destroy', $a) }}">@csrf @method('DELETE')<button class="text-red-700">Eliminar</button></form>
                                @endcan
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 py-2">Sin archivos.</p>
                    @endforelse
                    @if ($projectIdea->hasReachedAttachmentLimit())
                        <p class="text-xs text-slate-500 mt-3">Alcanzaste el máximo de {{ \App\Models\ProjectIdea::MAX_ATTACHMENTS }} archivos por idea. Eliminá uno para poder subir otro.</p>
                    @else
                        @can('create', App\Models\Attachment::class)
                            <form method="POST" action="{{ route('attachments.store', $projectIdea) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-2">@csrf
                                <input id="attachment_file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif" class="peer sr-only" required>
                                <label for="attachment_file" class="inline-flex cursor-pointer items-center rounded-md border border-slate-300 bg-white px-4 py-1.5 text-sm transition hover:bg-slate-50 peer-focus-visible:ring-2 peer-focus-visible:ring-slate-400 peer-focus-visible:ring-offset-2">
                                    Elegir archivo
                                </label>
                                <span id="attachment_file_name" class="text-sm text-slate-600">Ningún archivo seleccionado</span>
                                <button class="px-4 py-1.5 bg-slate-900 text-white rounded-md text-sm">Subir archivo</button>
                            </form>
                            <p class="text-xs text-slate-500 mt-1">PDF o imágenes (JPG, PNG, WEBP, GIF) · máx. 10 MB · hasta {{ \App\Models\ProjectIdea::MAX_ATTACHMENTS }} archivos por idea</p>
                        @endcan
                    @endif
                </div>

                <div x-show="panel === 'etiquetas'" x-cloak class="bg-white border border-slate-200 rounded-lg p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm">Etiquetas</h3>
                        <button type="button" @click="panel = null" class="text-sm text-slate-500 underline">Cerrar</button>
                    </div>
                    @if ($projectIdea->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($projectIdea->tags as $tag)
                                <a href="{{ route('ideas.index', ['tag' => $tag->id]) }}" class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded-full text-xs text-slate-600 hover:bg-slate-200">#{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Esta idea todavía no tiene etiquetas.</p>
                    @endif

                    <form method="POST" action="{{ route('ideas.tags.update', $projectIdea) }}" class="flex flex-wrap items-center gap-2 pt-1">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="tags" value="{{ $projectIdea->tags->pluck('name')->implode(', ') }}"
                            placeholder="Ej. educación, gestión, web" spellcheck="false"
                            class="w-full sm:w-80 rounded-md border border-slate-300 p-2 text-sm" />
                        <button class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Guardar etiquetas</button>
                    </form>
                    <p class="text-xs text-slate-500">Creá hasta {{ \App\Models\ProjectIdea::MAX_TAGS }} etiquetas para tu idea. Separá las etiquetas con comas.</p>
                    @error('tags')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        @endcan

        @cannot('update', $projectIdea)
            @if ($projectIdea->tags->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-lg p-4">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($projectIdea->tags as $tag)
                            <a href="{{ route('ideas.index', ['tag' => $tag->id]) }}" class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded-full text-xs text-slate-600 hover:bg-slate-200">#{{ $tag->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <h3 class="font-bold text-slate-900 text-sm">Adjuntos ({{ $projectIdea->attachments->count() }}/{{ \App\Models\ProjectIdea::MAX_ATTACHMENTS }})</h3>
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
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 py-2">Sin archivos.</p>
                @endforelse
            </div>
        @endcannot

        @can('delete', $projectIdea)
            <form method="POST" action="{{ route('ideas.destroy', $projectIdea) }}"
                onsubmit="return confirm('¿Eliminar esta idea? Solo se puede eliminar mientras no tenga postulaciones.');">
                @csrf
                @method('DELETE')
                <button class="px-4 py-2 border border-red-300 text-red-700 rounded-md text-sm">Eliminar idea</button>
            </form>
        @elsecan('cancel', $projectIdea)
            <div class="bg-white border border-amber-200 rounded-lg p-4 space-y-2">
                <h3 class="font-bold text-slate-900 text-sm">Cancelar idea</h3>
                <p class="text-sm text-slate-600">
                    Esta idea ya tiene postulaciones o trabajo asociado, por lo que no se puede eliminar.
                    Si necesitás detenerla, cancelala indicando un motivo. Se conservarán las postulaciones y los avances.
                </p>
                <form method="POST" action="{{ route('ideas.cancel', $projectIdea) }}" class="space-y-2"
                    onsubmit="return confirm('¿Cancelar esta idea? Las postulaciones pendientes serán rechazadas.');">
                    @csrf
                    @method('PATCH')
                    <label for="cancellation_reason" class="block text-xs font-semibold text-slate-600">Motivo de la cancelación</label>
                    <textarea id="cancellation_reason" name="cancellation_reason" rows="3" required maxlength="1000"
                        class="block w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900 rounded-md shadow-sm text-sm">{{ old('cancellation_reason') }}</textarea>
                    @error('cancellation_reason')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <button class="px-4 py-2 border border-amber-300 text-amber-800 rounded-md text-sm">Cancelar idea</button>
                </form>
            </div>
        @endcan

        @role('student')
            @if ($alreadyApplied)
                @php
                    $mine = $projectIdea->assignments->firstWhere('student_id', auth()->id());
                @endphp
                <div class="bg-white border border-slate-200 rounded-lg p-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm">
                            Ya postulado.
                            @if ($mine)
                                · {{ ['applied' => 'Postulado', 'accepted' => 'Aceptado', 'rejected' => 'Rechazado', 'in_progress' => 'En progreso', 'completed' => 'Completado', 'withdrawn' => 'Abandonado'][$mine->status] ?? $mine->status }}
                            @endif
                        </p>
                        @if ($mine)
                            @can('delete', $mine)
                                <form method="POST" action="{{ route('assignments.destroy', $mine) }}">@csrf @method('DELETE')<button class="px-3 py-1.5 border border-slate-300 rounded-md text-sm text-red-700">Retirar postulación</button></form>
                            @endcan
                            @can('withdraw', $mine)
                                <form method="POST" action="{{ route('assignments.withdraw', $mine) }}"
                                    onsubmit="return confirm('¿Abandonar este proyecto? Se liberará tu cupo.');">
                                    @csrf
                                    @method('PATCH')
                                    <button class="px-3 py-1.5 border border-red-300 rounded-md text-sm text-red-700">Abandonar proyecto</button>
                                </form>
                            @endcan
                        @endif
                    </div>

                    @if ($mine)
                        @php
                            $studentUpdates = $mine->updates->sortByDesc('created_at');
                        @endphp

                        <div class="space-y-3 border-t border-slate-100 pt-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Seguimiento de entregas</p>
                                    <p class="text-xs text-slate-500">Todos los avances que enviaste, con su estado actual.</p>
                                </div>
                                <span class="text-xs text-slate-500">
                                    {{ $studentUpdates->count() }} {{ $studentUpdates->count() === 1 ? 'avance enviado' : 'avances enviados' }}
                                </span>
                            </div>

                            <x-project-progress
                                :updates="$studentUpdates"
                                title="Progreso de tus entregas"
                                subtitle="Según las etapas aprobadas por el profesor." />

                            <x-progress-table
                                :updates="$studentUpdates"
                                subtitle="Estado de cada avance que enviaste." />

                            @if ($studentUpdates->isNotEmpty())
                                <div class="space-y-2">
                                    @foreach ($studentUpdates as $update)
                                        <x-progress-card :update="$update" :show-student="false" />
                                    @endforeach
                                </div>
                            @else
                                <p class="rounded-md border border-dashed border-slate-200 px-3 py-4 text-center text-sm text-slate-500">
                                    Todavía no enviaste avances.
                                </p>
                            @endif
                        </div>

                        @if ($mine->canReceiveUpdates())
                            <div class="border-t border-slate-100 pt-3">
                                <x-progress-form :assignment="$mine" />
                            </div>
                        @elseif (in_array($mine->status, ['accepted', 'in_progress'], true))
                            <p class="border-t border-slate-100 pt-3 text-xs text-slate-500">
                                Los avances se habilitan cuando el proyecto está en desarrollo.
                            </p>
                        @elseif ($mine->status === 'applied')
                            <p class="border-t border-slate-100 pt-3 text-xs text-slate-500">
                                Podrás subir avances cuando el profesor acepte tu postulación.
                            </p>
                        @endif
                    @endif
                </div>
            @elseif ($activeAssignment)
                <p class="bg-white border border-slate-200 rounded-lg p-3 text-sm">
                    Ya estás postulado a
                    <a href="{{ route('ideas.show', $activeAssignment->projectIdea) }}" class="font-semibold underline">
                        {{ $activeAssignment->projectIdea?->title ?? 'otra idea' }}
                    </a>.
                    Solo podés postularte a una idea, pero podés seguir viendo las demás.
                </p>
            @elseif ($projectIdea->isPendingReview())
                <p class="bg-white border rounded-lg p-3 text-sm">El profesor todavía no definió el cupo para esta idea.</p>
            @elseif (! $projectIdea->isOpenForApplications())
                <p class="bg-white border rounded-lg p-3 text-sm">Esta idea ya no acepta postulaciones.</p>
            @elseif (! $projectIdea->hasAvailableSlots())
                <p class="bg-white border rounded-lg p-3 text-sm">Esta idea ya alcanzó el cupo máximo de postulantes.</p>
            @else
                @can('create', App\Models\Assignment::class)
                    <form method="POST" action="{{ route('assignments.store', $projectIdea) }}">@csrf<button class="px-5 py-2.5 bg-slate-900 text-white rounded-md text-sm">Postularme</button></form>
                @endcan
            @endif
        @endrole

        @if (auth()->check() && (auth()->user()->hasAnyRole(['admin', 'profesor']) || $projectIdea->organizer_id === auth()->id()))
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-bold text-slate-900 text-sm">
                        Postulaciones ({{ $projectIdea->assignments->count() }})
                        · Aceptados: {{ $projectIdea->acceptedAssignmentsCount() }}
                    </h3>
                    @hasanyrole('profesor|admin')
                        @if ($projectIdea->status === 'available' && $projectIdea->acceptedAssignmentsCount() > 0)
                            @can('startDevelopment', $projectIdea)
                                @include('ideas._start-development-form', ['idea' => $projectIdea])
                            @endcan
                        @endif
                    @endhasanyrole
                </div>
                @forelse($projectIdea->assignments as $a)
                    <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                        <span>
                            {{ $a->student->name ?? '' }}
                            @if ($a->student?->email) · {{ $a->student->email }} @endif
                            · {{ ['applied' => 'Postulado', 'accepted' => 'Aceptado', 'rejected' => 'Rechazado', 'in_progress' => 'En progreso', 'completed' => 'Completado', 'withdrawn' => 'Abandonado'][$a->status] ?? $a->status }}
                            · {{ $a->applied_at?->format('d/m/Y') }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sin postulaciones.</p>
                @endforelse
            </div>
        @endif
    </div>

    <script>
        document.getElementById('attachment_file')?.addEventListener('change', function () {
            const output = document.getElementById('attachment_file_name');

            if (! output) {
                return;
            }

            output.textContent = this.files.length ? this.files[0].name : 'Ningún archivo seleccionado';
        });
    </script>
</x-app-layout>
