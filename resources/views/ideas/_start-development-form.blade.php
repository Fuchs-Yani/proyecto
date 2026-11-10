@php
    $acceptedCount = $idea->acceptedAssignmentsCount();
    $pendingCount = $idea->pendingAssignmentsCount();

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

<form method="POST" action="{{ route('ideas.start', $idea) }}" data-confirm="{{ $message }}">
    @csrf
    @method('PATCH')
    <button class="px-3 py-1.5 bg-amber-500 text-white rounded-md text-sm hover:bg-amber-600">Iniciar proyecto</button>
</form>

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
