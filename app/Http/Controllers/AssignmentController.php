<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Models\Assignment;
use App\Models\ProjectIdea;

class AssignmentController extends Controller
{
    // GET /assignments - ver según rol
    public function index()
    {
        $this->authorize('viewAny', Assignment::class);
        $user = auth()->user();

        if ($user->hasRole('student')) {
            $assignments = Assignment::with('projectIdea')->where('student_id', $user->id)->latest()->paginate(10);
        } elseif ($user->hasRole('beneficiario')) {
            $assignments = Assignment::with(['projectIdea', 'student'])
                ->whereHas('projectIdea', fn ($q) => $q->where('organizer_id', $user->id))
                ->latest()->paginate(10);
        } else {
            $assignments = Assignment::with(['projectIdea', 'student'])->latest()->paginate(10);
        }

        return view('assignments.index', compact('assignments'));
    }

    // POST /ideas/{projectIdea}/apply - postularse
    public function store(StoreAssignmentRequest $request, ProjectIdea $projectIdea)
    {
        $this->authorize('create', Assignment::class);

        // Evita duplicados
        $exists = Assignment::where('project_idea_id', $projectIdea->id)
            ->where('student_id', auth()->id())
            ->exists();

        if ($exists) {
            return back()->with('status', 'Ya te postulaste a esta idea.');
        }

        Assignment::create([
            'project_idea_id' => $projectIdea->id,
            'student_id' => auth()->id(),
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        return back()->with('status', 'Postulación creada.');
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
