<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\User;
use App\Notifications\ApplicationReviewed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    // GET /assignments - ver según rol
    public function index(Request $request)
    {
        $this->authorize('viewAny', Assignment::class);
        $user = auth()->user();

        $status = $request->query('status');
        $status = in_array($status, ['applied', 'accepted', 'rejected', 'in_progress', 'completed', 'withdrawn'], true)
            ? $status
            : null;

        if ($user->hasRole('student')) {
            $assignments = Assignment::with(['projectIdea', 'updates.images'])
                ->where('student_id', $user->id)
                ->when($status, fn ($query) => $query->where('status', $status))
                ->search($request->query('search'))
                ->latest()->paginate(10)->withQueryString();
        } elseif ($user->hasRole('beneficiario')) {
            $assignments = Assignment::with(['projectIdea', 'student'])
                ->whereHas('projectIdea', fn ($q) => $q->where('organizer_id', $user->id))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->search($request->query('search'))
                ->latest()->paginate(10)->withQueryString();
        } else {
            $assignments = Assignment::with(['projectIdea', 'student'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->search($request->query('search'))
                ->latest()->paginate(10)->withQueryString();
        }

        return view('assignments.index', compact('assignments', 'status'));
    }

    // POST /ideas/{projectIdea}/apply - postularse
    public function store(StoreAssignmentRequest $request, ProjectIdea $projectIdea)
    {
        $this->authorize('create', Assignment::class);

        // Hasta que el profesor analice la idea y defina el cupo no se puede postular.
        if ($projectIdea->isPendingReview()) {
            return back()->with('status', 'El profesor todavía no definió el cupo para esta idea.');
        }

        // Una idea cancelada (o ya cerrada) no acepta nuevas postulaciones.
        if (! $projectIdea->isOpenForApplications()) {
            return back()->with('status', 'Esta idea ya no acepta postulaciones.');
        }

        // Bloquea la fila de la idea para controlar el cupo ante postulaciones simultáneas.
        $result = DB::transaction(function () use ($projectIdea): string {
            $idea = ProjectIdea::whereKey($projectIdea->getKey())->lockForUpdate()->firstOrFail();
            $studentId = auth()->id();

            // Serializa las postulaciones del alumno para que no pueda enviar
            // dos solicitudes a ideas distintas al mismo tiempo.
            User::whereKey($studentId)->lockForUpdate()->firstOrFail();

            $exists = Assignment::where('project_idea_id', $idea->id)
                ->where('student_id', $studentId)
                ->exists();

            if ($exists) {
                return 'duplicate';
            }

            $hasOtherApplication = Assignment::where('student_id', $studentId)
                ->active()
                ->exists();

            if ($hasOtherApplication) {
                return 'other_application';
            }

            if (! $idea->hasAvailableSlots()) {
                return 'full';
            }

            Assignment::create([
                'project_idea_id' => $idea->id,
                'student_id' => $studentId,
                'status' => 'applied',
                'applied_at' => now(),
            ]);

            return 'created';
        });

        return match ($result) {
            'duplicate' => back()->with('status', 'Ya te postulaste a esta idea.'),
            'other_application' => back()->with('status', 'Ya estás postulado a otra idea. Solo podés postularte a una.'),
            'full' => back()->with('status', 'Esta idea ya alcanzó el cupo máximo de postulantes.'),
            default => back()->with('status', 'Postulación creada.'),
        };
    }

    // PATCH /assignments/{assignment}/estado - el profesor acepta o rechaza
    public function updateStatus(Request $request, Assignment $assignment)
    {
        $this->authorize('review', $assignment);

        $data = $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
        ], [
            'status.required' => 'Elegí si querés aceptar o rechazar la postulación.',
            'status.in' => 'El estado elegido no es válido.',
        ]);

        if ($assignment->status !== 'applied') {
            return back()->with('status', 'Esta postulación ya fue evaluada.');
        }

        if ($data['status'] === 'rejected') {
            $assignment->reject();
            $this->notifyStudent($assignment, Assignment::STATUS_REJECTED);

            return back()->with('status', 'Postulación rechazada.');
        }

        if (! $assignment->accept()) {
            return back()->with(
                'status',
                'No se puede aceptar: el profesor todavía no definió el cupo o ya se completó el cupo de aceptados.',
            );
        }

        $this->notifyStudent($assignment, Assignment::STATUS_ACCEPTED);

        $projectIsInDevelopment = $assignment->fresh()?->projectIdea?->status === 'in_development';

        return back()->with(
            'status',
            $projectIsInDevelopment
                ? 'Postulación aceptada. El proyecto pasó a desarrollo.'
                : 'Postulación aceptada.',
        );
    }

    // DELETE /assignments/{assignment} - retirar postulación pendiente
    public function destroy(Assignment $assignment)
    {
        $this->authorize('delete', $assignment);

        // Solo el dueño o admin
        if (auth()->user()->hasRole('student') && $assignment->student_id !== auth()->id()) {
            abort(403);
        }

        $assignment->delete();

        return back()->with('status', 'Postulación eliminada.');
    }

    // PATCH /assignments/{assignment}/abandonar - abandonar un proyecto aceptado
    public function withdraw(Assignment $assignment)
    {
        $this->authorize('withdraw', $assignment);

        if (! $assignment->withdraw()) {
            return back()->with('status', 'No se puede abandonar esta postulación.');
        }

        return back()->with('status', 'Abandonaste el proyecto. Se liberó tu cupo.');
    }

    /**
     * Avisa al alumno que su postulación fue aceptada o rechazada.
     */
    private function notifyStudent(Assignment $assignment, string $status): void
    {
        $assignment->loadMissing('projectIdea');

        $assignment->student?->notify(new ApplicationReviewed($assignment, $status));
    }
}
