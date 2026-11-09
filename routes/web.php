<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectIdeaController;
use App\Http\Controllers\ProjectUpdateController;
use App\Http\Controllers\StudentController;
use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $finished = ProjectIdea::where('status', 'completed')->with('organizer')->latest()->take(3)->get();

    return view('welcome', compact('finished'));
});

Route::get('/dashboard', function () {
    $ideas = null;
    $stats = null;
    $projectStatuses = collect();
    $studentStatuses = collect();
    $studentSummary = ['total' => 0, 'applied' => 0];
    $pendingApplications = collect();
    $pendingUpdates = collect();
    $pendingApplicationsCount = 0;
    $pendingUpdatesCount = 0;

    if (auth()->user()->hasRole('student')) {
        $ideas = ProjectIdea::query()
            ->with(['organizer', 'attachments', 'tags'])
            ->withCount(['assignments as active_assignments_count' => fn ($query) => $query->whereNotIn('status', Assignment::INACTIVE_STATUSES)])
            ->where('status', 'available')
            ->latest()
            ->paginate(12);
    }

    if (auth()->user()->hasRole('profesor')) {
        $stats = [
            'students' => User::role('student')->count(),
            'applications' => Assignment::count(),
            'ideas' => ProjectIdea::count(),
            'available' => ProjectIdea::where('status', 'available')->count(),
        ];

        $students = User::role('student')
            ->with(['assignments:id,student_id,status'])
            ->get();

        $studentGroups = [
            'not_applied' => 0,
            'pending' => 0,
            'accepted' => 0,
            'completed' => 0,
            'rejected' => 0,
        ];

        foreach ($students as $student) {
            $statuses = $student->assignments->pluck('status');

            if ($statuses->isEmpty()) {
                $studentGroups['not_applied']++;

                continue;
            }

            if ($statuses->contains(Assignment::STATUS_COMPLETED)) {
                $studentGroups['completed']++;

                continue;
            }

            if ($statuses->contains(fn (string $status): bool => in_array($status, Assignment::ACCEPTED_STATUSES, true))) {
                $studentGroups['accepted']++;

                continue;
            }

            if ($statuses->contains(Assignment::STATUS_APPLIED)) {
                $studentGroups['pending']++;

                continue;
            }

            $studentGroups['rejected']++;
        }

        $studentStatuses = collect([
            [
                'key' => 'not_applied',
                'label' => 'Sin postularse',
                'value' => $studentGroups['not_applied'],
                'color' => '#94a3b8',
            ],
            [
                'key' => 'pending',
                'label' => 'Esperando aprobación',
                'value' => $studentGroups['pending'],
                'color' => '#f59e0b',
            ],
            [
                'key' => 'accepted',
                'label' => 'Aceptados / en curso',
                'value' => $studentGroups['accepted'],
                'color' => '#2563eb',
            ],
            [
                'key' => 'completed',
                'label' => 'Finalizados',
                'value' => $studentGroups['completed'],
                'color' => '#059669',
            ],
            [
                'key' => 'rejected',
                'label' => 'Rechazados / retirados',
                'value' => $studentGroups['rejected'],
                'color' => '#dc2626',
            ],
        ]);

        $studentSummary = [
            'total' => $students->count(),
            'applied' => $students->count() - $studentGroups['not_applied'],
        ];

        $projectStatuses = collect([
            [
                'key' => 'available',
                'label' => 'Disponibles / sin iniciar',
                'value' => ProjectIdea::where('status', 'available')->count(),
                'color' => '#0ea5e9',
            ],
            [
                'key' => 'in_development',
                'label' => 'En desarrollo',
                'value' => ProjectIdea::where('status', 'in_development')->count(),
                'color' => '#2563eb',
            ],
            [
                'key' => 'completed',
                'label' => 'Finalizados',
                'value' => ProjectIdea::where('status', 'completed')->count(),
                'color' => '#059669',
            ],
            [
                'key' => 'cancelled',
                'label' => 'Cancelados',
                'value' => ProjectIdea::where('status', 'cancelled')->count(),
                'color' => '#64748b',
            ],
        ]);

        $pendingApplications = Assignment::query()
            ->with(['student', 'projectIdea'])
            ->where('status', 'applied')
            ->latest('applied_at')
            ->take(5)
            ->get();

        $pendingApplicationsCount = Assignment::where('status', 'applied')->count();

        $pendingUpdates = ProjectUpdate::query()
            ->with(['student', 'projectIdea'])
            ->where('status', ProjectUpdate::STATUS_PENDING)
            ->latest()
            ->take(5)
            ->get();

        $pendingUpdatesCount = ProjectUpdate::where('status', ProjectUpdate::STATUS_PENDING)->count();
    }

    return view('dashboard', compact(
        'ideas',
        'stats',
        'projectStatuses',
        'studentStatuses',
        'studentSummary',
        'pendingApplications',
        'pendingUpdates',
        'pendingApplicationsCount',
        'pendingUpdatesCount',
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

// Crear idea: solo usuarios autenticados con permiso (beneficiario/admin)
Route::middleware('auth')->group(function () {
    Route::get('/alumnos', [StudentController::class, 'index'])->name('professor.students');
    Route::get('/mis-ideas', [ProjectIdeaController::class, 'mine'])->name('ideas.mine');
    Route::get('/ideas/crear', [ProjectIdeaController::class, 'create'])->name('ideas.create');
    Route::post('/ideas', [ProjectIdeaController::class, 'store'])->name('ideas.store');
});

// Catálogo público: invitados solo ven proyectos terminados
Route::get('/ideas', [ProjectIdeaController::class, 'index'])->name('ideas.index');
Route::get('/ideas/{projectIdea}', [ProjectIdeaController::class, 'show'])->name('ideas.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notificaciones/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notificaciones/leer-todas', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::delete('/ideas/{projectIdea}', [ProjectIdeaController::class, 'destroy'])->name('ideas.destroy');
    Route::patch('/ideas/{projectIdea}/cancelar', [ProjectIdeaController::class, 'cancel'])->name('ideas.cancel');
    Route::patch('/ideas/{projectIdea}', [ProjectIdeaController::class, 'update'])->name('ideas.update');
    Route::patch('/ideas/{projectIdea}/etiquetas', [ProjectIdeaController::class, 'updateTags'])->name('ideas.tags.update');
    Route::patch('/ideas/{projectIdea}/cupo', [ProjectIdeaController::class, 'updateQuota'])->name('ideas.quota.update');
    Route::patch('/ideas/{projectIdea}/iniciar', [ProjectIdeaController::class, 'startDevelopment'])->name('ideas.start');

    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    // STUDENT (+admin): postularse / retirar
    Route::post('/ideas/{projectIdea}/apply', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::patch('/assignments/{assignment}/estado', [AssignmentController::class, 'updateStatus'])->name('assignments.status.update');
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::patch('/assignments/{assignment}/abandonar', [AssignmentController::class, 'withdraw'])->name('assignments.withdraw');
    // AVANCES: el alumno postulado envía avances y el profesor los aprueba
    Route::post('/assignments/{assignment}/avances', [ProjectUpdateController::class, 'store'])->name('progress.store');
    Route::get('/avances', [ProjectUpdateController::class, 'index'])->name('progress.index');
    Route::patch('/avances/{projectUpdate}/aprobar', [ProjectUpdateController::class, 'approve'])->name('progress.approve');
    Route::patch('/avances/{projectUpdate}/rechazar', [ProjectUpdateController::class, 'reject'])->name('progress.reject');
    // ORGANIZATION (+admin): subir / borrar adjuntos propios
    Route::post('/ideas/{projectIdea}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
});

require __DIR__.'/auth.php';
