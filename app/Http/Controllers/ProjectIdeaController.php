<?php

namespace App\Http\Controllers;

use App\Models\ProjectIdea;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectIdeaController extends Controller
{
    // GET /ideas/crear - formulario para publicar una idea
    public function create()
    {
        if ($this->reachedBeneficiaryIdeaLimit()) {
            return $this->redirectToOwnIdeas();
        }

        $this->authorize('create', ProjectIdea::class);

        return view('ideas.create');
    }

    // POST /ideas - guarda la idea del beneficiario autenticado
    public function store(Request $request)
    {
        if ($this->reachedBeneficiaryIdeaLimit()) {
            return $this->redirectToOwnIdeas();
        }

        $this->authorize('create', ProjectIdea::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'tags' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:'.ProjectIdea::MAX_ATTACHMENTS],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,gif', 'max:10240'],
        ], [
            'tags.max' => 'Las etiquetas no pueden superar los 255 caracteres.',
            'attachments.array' => 'Los archivos no se enviaron correctamente.',
            'attachments.max' => 'Solo podés adjuntar hasta '.ProjectIdea::MAX_ATTACHMENTS.' archivos por idea.',
            'attachments.*.file' => 'Uno de los archivos no es válido.',
            'attachments.*.mimes' => 'Solo se permiten archivos PDF o imágenes (JPG, PNG, WEBP o GIF).',
            'attachments.*.max' => 'Cada archivo no puede superar los 10 MB.',
            'attachments.*.uploaded' => 'No se pudo subir uno de los archivos. Verificá el tamaño permitido.',
        ]);

        $storedPaths = [];
        $tagIds = $this->tagIdsFrom($data['tags'] ?? null);

        try {
            $idea = DB::transaction(function () use ($data, $request, $tagIds, &$storedPaths) {
                $idea = ProjectIdea::create([
                    'organizer_id' => auth()->id(),
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'status' => 'available',
                ]);

                $idea->tags()->sync($tagIds);

                foreach ((array) $request->file('attachments', []) as $file) {
                    $path = $file->store('attachments', 'public');
                    $storedPaths[] = $path;

                    $idea->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => strtolower($file->getClientOriginalExtension()),
                        'uploaded_at' => now(),
                    ]);
                }

                return $idea;
            });
        } catch (\Throwable $exception) {
            // Si la transacción falla, borra los archivos ya guardados en el disco.
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        return redirect()
            ->route('ideas.show', $idea)
            ->with('status', 'Idea publicada correctamente.');
    }

    // DELETE /ideas/{projectIdea} - el beneficiario dueño (o admin) elimina la idea
    public function destroy(ProjectIdea $projectIdea)
    {
        $this->authorize('delete', $projectIdea);

        DB::transaction(fn () => $projectIdea->delete());

        return redirect()
            ->route('ideas.index')
            ->with('status', 'Idea eliminada correctamente.');
    }

    // PATCH /ideas/{projectIdea} - el dueño (o admin) edita la descripción
    public function update(Request $request, ProjectIdea $projectIdea)
    {
        $this->authorize('update', $projectIdea);

        $data = $request->validate([
            'description' => ['required', 'string'],
        ], [
            'description.required' => 'La descripción de la idea es obligatoria.',
        ]);

        $projectIdea->update([
            'description' => $data['description'],
        ]);

        return back()->with('status', 'Descripción actualizada.');
    }

    // PATCH /ideas/{projectIdea}/etiquetas - el dueño (o admin) actualiza las etiquetas
    public function updateTags(Request $request, ProjectIdea $projectIdea)
    {
        $this->authorize('update', $projectIdea);

        $data = $request->validate([
            'tags' => ['nullable', 'string', 'max:255'],
        ], [
            'tags.max' => 'Las etiquetas no pueden superar los 255 caracteres.',
        ]);

        $projectIdea->tags()->sync($this->tagIdsFrom($data['tags'] ?? null));

        return back()->with('status', 'Etiquetas actualizadas.');
    }

    // GET /mis-ideas - el beneficiario ve únicamente las ideas que publicó
    public function mine(Request $request)
    {
        abort_unless(auth()->user()->hasRole('beneficiario'), 403);

        $ideas = ProjectIdea::query()
            ->with(['organizer', 'attachments', 'tags'])
            ->withCount(['assignments as active_assignments_count' => fn ($query) => $query->where('status', '!=', 'rejected')])
            ->where('organizer_id', auth()->id())
            ->when($request->filled('tag'), fn ($query) => $query->whereHas('tags', fn ($tag) => $tag->whereKey($request->query('tag'))))
            ->search($request->query('search'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $tags = Tag::orderBy('name')->get();

        return view('ideas.mine', compact('ideas', 'tags'));
    }

    // GET /ideas - invitados ven terminados; usuarios con permiso ven disponibles
    public function index(Request $request)
    {
        $user = auth()->user();

        $ideas = ProjectIdea::query()
            ->with(['organizer', 'attachments', 'tags'])
            ->withCount(['assignments as active_assignments_count' => fn ($query) => $query->where('status', '!=', 'rejected')])
            ->when(
                $user?->can('ViewAny:ProjectIdea'),
                fn ($query) => $query->where('status', 'available'),
                fn ($query) => $query->where('status', 'completed'),
            )
            ->when($request->filled('tag'), fn ($query) => $query->whereHas('tags', fn ($tag) => $tag->whereKey($request->query('tag'))))
            ->search($request->query('search'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $tags = Tag::orderBy('name')->get();

        return view('ideas.index', compact('ideas', 'tags'));
    }

    // GET /ideas/{projectIdea} - invitados solo pueden ver proyectos terminados
    public function show(ProjectIdea $projectIdea)
    {
        if (auth()->check()) {
            $this->authorize('view', $projectIdea);
        } else {
            abort_unless($projectIdea->status === 'completed', 404);
        }

        $projectIdea->load(['organizer', 'attachments', 'tags', 'assignments.student']);
        $projectIdea->setAttribute(
            'active_assignments_count',
            $projectIdea->assignments->where('status', '!=', 'rejected')->count(),
        );

        $alreadyApplied = auth()->check()
            ? $projectIdea->assignments()->where('student_id', auth()->id())->exists()
            : false;

        return view('ideas.show', compact('projectIdea', 'alreadyApplied'));
    }

    /**
     * Indica si el beneficiario autenticado ya publicó su única idea permitida.
     */
    private function reachedBeneficiaryIdeaLimit(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasRole('beneficiario')
            && $user->projectIdeas()->exists();
    }

    private function redirectToOwnIdeas(): RedirectResponse
    {
        return redirect()
            ->route('ideas.mine')
            ->with('status', 'Ya publicaste tu idea. Solo se permite una idea por beneficiario.');
    }

    /**
     * Convierte el texto de etiquetas separadas por coma en IDs de etiquetas.
     *
     * @return array<int, int>
     */
    private function tagIdsFrom(?string $tags): array
    {
        return collect(explode(',', (string) $tags))
            ->map(fn (string $tag): string => trim($tag))
            ->filter()
            ->unique(fn (string $tag): string => mb_strtolower($tag))
            ->take(ProjectIdea::MAX_TAGS)
            ->map(fn (string $tag): int => Tag::firstOrCreate(['name' => $tag])->id)
            ->values()
            ->all();
    }
}
