@php
    $acceptedCount = $idea->acceptedAssignmentsCount();
    $pendingCount = $idea->pendingAssignmentsCount();
    $requirementsApproved = $idea->requirementsApproved();

    $message = '¿Iniciar el proyecto con '.($acceptedCount === 1
        ? '1 integrante aceptado'
        : $acceptedCount.' integrantes aceptados').'?';

    if ($pendingCount === 1) {
        $message .= ' Se rechazará 1 postulación pendiente automáticamente.';
    } elseif ($pendingCount > 1) {
        $message .= ' Se rechazarán '.$pendingCount.' postulaciones pendientes automáticamente.';
    } elseif ($idea->max_students !== null && $acceptedCount < $idea->max_students) {
        $message .= ' El cupo definido es de '.$idea->max_students.' '.($idea->max_students === 1 ? 'alumno' : 'alumnos').'.';
    }
@endphp

@if (! $requirementsApproved)
    <div class="flex flex-col items-start gap-1">
        <button type="button" disabled
            class="cursor-not-allowed rounded-md bg-slate-200 px-3 py-1.5 text-sm text-slate-500">
            Iniciar proyecto
        </button>
        <p class="max-w-xs text-xs text-amber-700">
            Faltan aprobar los requerimientos. El beneficiario y el profesor deben aprobarlos
            en «Análisis y requerimientos».
        </p>
    </div>
@else
    <form method="POST" action="{{ route('ideas.start', $idea) }}" data-confirm="{{ $message }}">
        @csrf
        @method('PATCH')
        <button class="px-3 py-1.5 bg-amber-500 text-white rounded-md text-sm hover:bg-amber-600">Iniciar proyecto</button>
    </form>
@endif

@once
    <script>
        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form[data-confirm]');

            if (!form || confirm(form.dataset.confirm)) {
                return;
            }

            event.preventDefault();
        }, true);
    </script>
@endonce
