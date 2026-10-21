<?php

namespace App\Http\Controllers;

use App\Models\ProjectIdea;
use Illuminate\Http\Request;

class ProjectIdeaController extends Controller
{
    // GET /ideas/crear - formulario para publicar una idea
    public function create()
    {
        $this->authorize('create', ProjectIdea::class);

        return view('ideas.create');
    }

    // POST /ideas - guarda la idea del beneficiario autenticado
    public function store(Request $request)
    {
        $this->authorize('create', ProjectIdea::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $idea = ProjectIdea::create([
            'organizer_id' => auth()->id(),
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'available',
        ]);

        return redirect()
            ->route('ideas.show', $idea)
            ->with('status', 'Idea publicada correctamente.');
    }
    // GET /ideas - invitados ven terminados; usuarios con permiso ven disponibles
    public function index()
    {
        $user = auth()->user();

        $ideas = ProjectIdea::query()
            ->with(['organizer', 'attachments'])
            ->when(
                $user?->can('ViewAny:ProjectIdea'),
                fn ($query) => $query->where('status', 'available'),
                fn ($query) => $query->where('status', 'completed'),
            )
            ->latest()
            ->paginate(12);

        return view('ideas.index', compact('ideas'));
    }

    // GET /ideas/{projectIdea} - invitados solo pueden ver proyectos terminados
    public function show(ProjectIdea $projectIdea)
    {
        if (auth()->check()) {
            $this->authorize('view', $projectIdea);
        } else {
            abort_unless($projectIdea->status === 'completed', 404);
        }

        $projectIdea->load(['organizer', 'attachments', 'assignments.student']);

        $alreadyApplied = auth()->check()
            ? $projectIdea->assignments()->where('student_id', auth()->id())->exists()
            : false;

        return view('ideas.show', compact('projectIdea', 'alreadyApplied'));
    }
}
