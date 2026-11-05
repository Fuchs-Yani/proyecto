<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Models\Assignment;
use App\Models\ProjectIdea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    // GET /assignments - ver según rol
    public function index(Request $request)
    {
        $this->authorize('viewAny', Assignment::class);
        $user = auth()->user();

        if ($user->hasRole('student')) {
            $assignments = Assignment::with(['projectIdea', 'updates.images'])
                ->where('student_id', $user->id)
                ->search($request->query('search'))
                ->latest()->paginate(10)->withQueryString();
        } elseif ($user->hasRole('beneficiario')) {
            $assignments = Assignment::with(['projectIdea', 'student'])
                ->whereHas('projectIdea', fn ($q) => $q->where('organizer_id', $user->id))
                ->search($request->query('search'))
                ->latest()->paginate(10)->withQueryString();
        } else {
            $assignments = Assignment::with(['projectIdea', 'student'])
                ->search($request->query('search'))
                ->latest()->paginate(10)->withQueryString();
        }

        return view('assignments.index', compact('assignments'));
    }

    // POST /ideas/{projectIdea}/apply - postularse
    public function store(StoreAssignmentRequest $request, ProjectIdea $projectIdea)
    {
        $this->authorize('create', Assignment::class);

        // Una idea cancelada (o ya cerrada) no acepta nuevas postulaciones.
        if (! $projectIdea->isOpenForApplications()) {
            return back()->with('status', 'Esta idea ya no acepta postulaciones.');
        }

        // Bloquea la fila de la idea para controlar el cupo ante postulaciones simultáneas.
        $result = DB::transaction(function () use ($projectIdea): string {
            $idea = ProjectIdea::whereKey($projectIdea->getKey())->lockForUpdate()->firstOrFail();

            $exists = Assignment::where('project_idea_id', $idea->id)
                ->where('student_id', auth()->id())
                ->exists();

            if ($exists) {
                return 'duplicate';
            }

            if (! $idea->hasAvailableSlots()) {
                return 'full';
            }

            Assignment::create([
                'project_idea_id' => $idea->id,
                'student_id' => auth()->id(),
                'status' => 'applied',
                'applied_at' => now(),
            ]);

            return 'created';
        });

        return match ($result) {
            'duplicate' => back()->with('status', 'Ya te postulaste a esta idea.'),
            'full' => back()->with('status', 'Esta idea ya alcanzó el cupo máximo de postulantes.'),
            default => back()->with('status', 'Postulación creada.'),
        };
    }

    // DELETE /assignments/{assignment} - retirar postulación
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
}
