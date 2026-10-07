<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Models\Assignment;
use App\Models\ProjectIdea;

class AssignmentController extends Controller
{
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
