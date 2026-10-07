<?php

namespace App\Http\Controllers;

use App\Models\ProjectIdea;

class ProjectIdeaController extends Controller
{
    // GET /ideas - listar ideas disponibles
    public function index()
    {
        $this->authorize('viewAny', ProjectIdea::class);

        $ideas = ProjectIdea::where('status', 'available')
            ->with(['organizer', 'attachments'])
            ->latest()
            ->paginate(12);

        return view('ideas.index', compact('ideas'));
    }

    // GET /ideas/{projectIdea} - detalle
    public function show(ProjectIdea $projectIdea)
    {
        $this->authorize('view', $projectIdea);

        $projectIdea->load(['organizer', 'attachments', 'assignments.student']);

        $alreadyApplied = auth()->check()
            ? $projectIdea->assignments()->where('student_id', auth()->id())->exists()
            : false;

        return view('ideas.show', compact('projectIdea', 'alreadyApplied'));
    }
}
