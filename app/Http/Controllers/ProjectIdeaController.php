<?php

namespace App\Http\Controllers;

use App\Models\ProjectIdea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,gif', 'max:10240'],
        ], [
            'attachments.array' => 'Los archivos no se enviaron correctamente.',
            'attachments.*.file' => 'Uno de los archivos no es válido.',
            'attachments.*.mimes' => 'Solo se permiten archivos PDF o imágenes (JPG, PNG, WEBP o GIF).',
            'attachments.*.max' => 'Cada archivo no puede superar los 10 MB.',
            'attachments.*.uploaded' => 'No se pudo subir uno de los archivos. Verificá el tamaño permitido.',
        ]);

        $idea = DB::transaction(function () use ($data, $request) {
            $idea = ProjectIdea::create([
                'organizer_id' => auth()->id(),
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => 'available',
            ]);

            foreach ((array) $request->file('attachments', []) as $file) {
                $path = $file->store('attachments', 'public');

                $idea->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => strtolower($file->getClientOriginalExtension()),
                    'uploaded_at' => now(),
                ]);
            }

            return $idea;
        });

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
