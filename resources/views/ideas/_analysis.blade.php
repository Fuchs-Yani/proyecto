@php
    $user = auth()->user();
    $isOwner = $user !== null && $projectIdea->organizer_id === $user->id;
    $isStaff = $user?->hasAnyRole(['admin', 'profesor']) ?? false;
    $isTeamMember = $user?->hasRole('student') && $projectIdea->isTeamMember($user);

    $questions = $projectIdea->questions->sortBy('created_at');
    $answeredQuestions = $questions->filter(fn ($question) => $question->isAnswered());
    $requirement = $projectIdea->requirement;

    $requirementLabel = match ($requirement?->status) {
        \App\Models\ProjectRequirement::STATUS_DRAFT => 'Borrador',
        \App\Models\ProjectRequirement::STATUS_PENDING => 'Pendiente de aprobación',
        \App\Models\ProjectRequirement::STATUS_APPROVED => 'Aprobada',
        default => 'Sin redactar',
    };

    $requirementBadge = match ($requirement?->status) {
        \App\Models\ProjectRequirement::STATUS_DRAFT => 'bg-amber-50 border-amber-200 text-amber-700',
        \App\Models\ProjectRequirement::STATUS_PENDING => 'bg-blue-50 border-blue-200 text-blue-700',
        \App\Models\ProjectRequirement::STATUS_APPROVED => 'bg-emerald-50 border-emerald-200 text-emerald-700',
        default => 'bg-slate-50 border-slate-200 text-slate-600',
    };
@endphp

<section id="analisis" class="bg-white border border-slate-200 rounded-lg p-4 space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h3 class="font-bold text-slate-900 text-sm">Análisis y requerimientos</h3>
            <p class="text-xs text-slate-500">
                El equipo aceptado pregunta al beneficiario, redacta la lista de requerimientos y la envía.
                El beneficiario y el profesor deben aprobarla antes de iniciar el proyecto.
            </p>
        </div>
        <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $requirementBadge }}">
            {{ $requirementLabel }}
        </span>
    </div>

    <div class="grid gap-2 text-xs sm:grid-cols-4">
        <div class="rounded-md border border-slate-200 bg-slate-50 p-2.5">
            <p class="font-semibold text-slate-700">1. Preguntas</p>
            <p class="text-slate-500">{{ $answeredQuestions->count() }} de {{ $questions->count() }} consultas respondidas</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-slate-50 p-2.5">
            <p class="font-semibold text-slate-700">2. Requerimientos</p>
            <p class="text-slate-500">{{ $requirement ? $requirementLabel : 'Sin redactar' }}</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-slate-50 p-2.5">
            <p class="font-semibold text-slate-700">3. Aprobaciones</p>
            <p class="text-slate-500">
                {{ $requirement?->hasBeneficiaryApproval() ? 'Beneficiario ✓' : 'Beneficiario pendiente' }}
                ·
                {{ $requirement?->hasProfessorApproval() ? 'Profesor ✓' : 'Profesor pendiente' }}
            </p>
        </div>
        <div class="rounded-md border border-slate-200 bg-slate-50 p-2.5">
            <p class="font-semibold text-slate-700">4. Inicio</p>
            <p class="text-slate-500">
                {{ $projectIdea->requirementsApproved()
                    ? ($projectIdea->status === 'available' ? 'Habilitado para iniciar' : 'Proyecto iniciado')
                    : 'Bloqueado hasta aprobar' }}
            </p>
        </div>
    </div>

    {{-- Preguntas del equipo --}}
    <div class="border-t border-slate-100 pt-3 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h4 class="text-sm font-semibold text-slate-800">Preguntas al beneficiario</h4>
                <p class="text-xs text-slate-500">
                    Solo los alumnos aceptados en el equipo pueden preguntar. Podés escribir varias preguntas
                    en un mismo mensaje y el beneficiario las responde todas juntas.
                </p>
            </div>
            <span class="text-xs text-slate-500">{{ $questions->count() }} {{ $questions->count() === 1 ? 'consulta' : 'consultas' }}</span>
        </div>

        @forelse ($questions as $question)
            <article class="rounded-md border border-slate-200 p-3 space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                    <span>
                        <span class="font-semibold text-slate-800">{{ $question->asker->name ?? 'Alumno del equipo' }}</span>
                        · {{ $question->created_at?->format('d/m/Y H:i') }}
                    </span>
                    @if ($question->isAnswered())
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">Respondida</span>
                    @else
                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 font-semibold text-amber-700">Pendiente de respuesta</span>
                    @endif
                </div>

                <p class="text-sm text-slate-800 whitespace-pre-line">{{ $question->question }}</p>

                @if ($question->isAnswered())
                    <div class="rounded-md border border-emerald-100 bg-emerald-50/60 p-2.5">
                        <p class="text-xs font-semibold text-emerald-800">
                            Respuesta de {{ $question->answerer->name ?? 'el beneficiario' }}
                            @if ($question->answered_at) · {{ $question->answered_at->format('d/m/Y H:i') }} @endif
                        </p>
                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ $question->answer }}</p>
                    </div>
                @else
                    @can('answerQuestion', $projectIdea)
                        <form method="POST" action="{{ route('questions.answer', $question) }}" class="space-y-2">
                            @csrf
                            @method('PATCH')
                            <label for="answer_{{ $question->id }}" class="block text-xs font-semibold text-slate-600">
                                Respuesta del beneficiario
                            </label>
                            <textarea id="answer_{{ $question->id }}" name="answer" rows="4" required maxlength="8000"
                                placeholder="Respondé las preguntas en el mismo orden…"
                                class="block w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900">{{ old('answer') }}</textarea>
                            <button class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Enviar respuesta</button>
                        </form>
                    @endcan
                @endif

                @can('deleteQuestion', [$projectIdea, $question])
                    @if (! $question->isAnswered())
                        <form method="POST" action="{{ route('questions.destroy', $question) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs text-red-700 underline">Eliminar consulta</button>
                        </form>
                    @endif
                @endcan
            </article>
        @empty
            <p class="rounded-md border border-dashed border-slate-200 px-3 py-4 text-center text-sm text-slate-500">
                Todavía no hay preguntas para el beneficiario.
            </p>
        @endforelse

        @can('askQuestion', $projectIdea)
            <form method="POST" action="{{ route('ideas.questions.store', $projectIdea) }}" class="space-y-2 rounded-md border border-slate-200 bg-slate-50 p-3">
                @csrf
                <label for="question" class="block text-xs font-semibold text-slate-600">Nuevas preguntas para el beneficiario</label>
                <textarea id="question" name="question" rows="5" required maxlength="5000"
                    placeholder="Podés escribir varias preguntas en un solo texto. Por ejemplo:&#10;1. ¿Qué datos debe mostrar la ficha de cada animal?&#10;2. ¿Quién actualiza el estado cuando un animal es adoptado?&#10;3. ¿Qué información necesita el formulario de adopción?"
                    class="block w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900">{{ old('question') }}</textarea>
                <p class="text-xs text-slate-500">Escribí todas las preguntas que necesites en un mismo mensaje, preferentemente numeradas.</p>
                @error('question')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <button class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Enviar preguntas</button>
            </form>
        @endcan
    </div>

    {{-- Lista de requerimientos --}}
    <div class="border-t border-slate-100 pt-3 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h4 class="text-sm font-semibold text-slate-800">Lista de requerimientos del sistema</h4>
                <p class="text-xs text-slate-500">
                    La redacta el equipo aceptado a partir de las respuestas. Debe aprobarla el beneficiario y el profesor.
                </p>
            </div>
            @if ($requirement)
                <span class="text-xs text-slate-500">
                    Enviada {{ $requirement->submitted_at?->format('d/m/Y H:i') ?? 'sin enviar' }}
                </span>
            @endif
        </div>

        @if ($requirement && in_array($requirement->status, [\App\Models\ProjectRequirement::STATUS_PENDING, \App\Models\ProjectRequirement::STATUS_APPROVED], true))
            <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                <p class="text-sm text-slate-800 whitespace-pre-line">{{ $requirement->content }}</p>
            </div>
        @endif

        @can('manageRequirements', $projectIdea)
            @if (! $requirement || $requirement->isDraft())
                <form method="POST" action="{{ route('ideas.requirements.save', $projectIdea) }}" class="space-y-2">
                    @csrf
                    @method('PATCH')
                    <textarea name="content" rows="8" required maxlength="20000"
                        placeholder="Ej. 1. Registrar animales con ficha y foto. 2. Recibir solicitudes de adopción…"
                        class="block w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900">{{ old('content', $requirement->content ?? '') }}</textarea>
                    @error('content')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <div class="flex flex-wrap items-center gap-2">
                        <button class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">
                            {{ $requirement ? 'Guardar borrador' : 'Crear borrador' }}
                        </button>
                    </div>
                </form>

                @if ($requirement && filled($requirement->content))
                    <form method="POST" action="{{ route('ideas.requirements.submit', $projectIdea) }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-md bg-emerald-600 px-3 py-1.5 text-sm text-white hover:bg-emerald-700">Enviar a aprobación</button>
                    </form>
                @endif
            @endif
        @endcan

        @if ($requirement)
            <div class="grid gap-2 sm:grid-cols-2">
                <div class="rounded-md border {{ $requirement->hasBeneficiaryApproval() ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white' }} p-2.5 text-xs">
                    <p class="font-semibold text-slate-700">Aprobación del beneficiario</p>
                    @if ($requirement->hasBeneficiaryApproval())
                        <p class="text-emerald-700">
                            Aprobada por {{ $requirement->beneficiaryApprover->name ?? 'el beneficiario' }}
                            · {{ $requirement->beneficiary_approved_at?->format('d/m/Y H:i') }}
                        </p>
                    @else
                        <p class="text-slate-500">Pendiente.</p>
                    @endif
                </div>
                <div class="rounded-md border {{ $requirement->hasProfessorApproval() ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white' }} p-2.5 text-xs">
                    <p class="font-semibold text-slate-700">Aprobación del profesor</p>
                    @if ($requirement->hasProfessorApproval())
                        <p class="text-emerald-700">
                            Aprobada por {{ $requirement->professorApprover->name ?? 'el profesor' }}
                            · {{ $requirement->professor_approved_at?->format('d/m/Y H:i') }}
                        </p>
                    @else
                        <p class="text-slate-500">Pendiente.</p>
                    @endif
                </div>
            </div>

            @can('approveRequirements', $projectIdea)
                @php
                    $approvalLabel = $isOwner ? 'Aprobar como beneficiario' : 'Aprobar como profesor';
                @endphp
                <form method="POST" action="{{ route('ideas.requirements.approve', $projectIdea) }}">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-md bg-emerald-600 px-3 py-1.5 text-sm text-white hover:bg-emerald-700">
                        {{ $approvalLabel }}
                    </button>
                </form>
            @endcan

            @if ($requirement->isApproved())
                <p class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                    Requerimientos aprobados por el beneficiario y el profesor.
                    @if ($projectIdea->status === 'available')
                        El profesor ya puede iniciar el proyecto.
                    @endif
                </p>
            @endif
        @endif
    </div>
</section>
