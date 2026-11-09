<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\Tag;
use App\Notifications\ApplicationReviewed;
use App\Notifications\ProjectIdeaReviewed;
use Illuminate\Database\Eloquent\Collection;
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
            'tags' => $this->tagRules(),
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
        $tagNames = $this->tagNamesFrom($data['tags'] ?? null);

        try {
            $idea = DB::transaction(function () use ($data, $request, $tagNames, &$storedPaths) {
                $idea = ProjectIdea::create([
                    'organizer_id' => auth()->id(),
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'status' => 'available',
                ]);

                $idea->tags()->sync($this->tagIdsFrom($tagNames));

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

    // PATCH /ideas/{projectIdea}/cancelar - cancela la idea conservando el historial
    public function cancel(Request $request, ProjectIdea $projectIdea)
    {
        $this->authorize('cancel', $projectIdea);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ], [
            'cancellation_reason.required' => 'Indicá el motivo de la cancelación.',
            'cancellation_reason.string' => 'El motivo de la cancelación no es válido.',
            'cancellation_reason.max' => 'El motivo no puede superar los 1000 caracteres.',
        ]);

        $pendingApplications = $projectIdea->assignments()
            ->where('status', Assignment::STATUS_APPLIED)
            ->get();

        if (! $projectIdea->cancel($data['cancellation_reason'])) {
            return back()->withErrors([
                'cancellation_reason' => 'La idea no se puede cancelar en su estado actual.',
            ]);
        }

        $this->notifyRejectedApplications($pendingApplications);

        return back()->with('status', 'Idea cancelada. Se conservaron las postulaciones y los avances.');
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
            'tags' => $this->tagRules(),
        ], [
            'tags.max' => 'Las etiquetas no pueden superar los 255 caracteres.',
        ]);

        $tagNames = $this->tagNamesFrom($data['tags'] ?? null);

        DB::transaction(function () use ($projectIdea, $tagNames) {
            $projectIdea->tags()->sync($this->tagIdsFrom($tagNames));
        });

        return back()->with('status', 'Etiquetas actualizadas.');
    }

    // PATCH /ideas/{projectIdea}/cupo - el profesor define cuántos alumnos participan
    public function updateQuota(Request $request, ProjectIdea $projectIdea)
    {
        $this->authorize('analyze', $projectIdea);

        $data = $request->validate([
            'max_students' => [
                'required',
                'integer',
                'min:'.ProjectIdea::MIN_STUDENTS,
                'max:'.ProjectIdea::MAX_STUDENTS,
            ],
        ], [
            'max_students.required' => 'Indicá cuántos alumnos pueden participar.',
            'max_students.integer' => 'El cupo debe ser un número entero.',
            'max_students.min' => 'El cupo mínimo es de '.ProjectIdea::MIN_STUDENTS.' '.(ProjectIdea::MIN_STUDENTS === 1 ? 'alumno.' : 'alumnos.'),
            'max_students.max' => 'El cupo máximo es de '.ProjectIdea::MAX_STUDENTS.' alumnos.',
        ]);

        if ($projectIdea->status !== 'available') {
            return back()->with('status', 'Solo se puede definir el cupo de una idea disponible.');
        }

        if ($projectIdea->activeAssignmentsCount() > $data['max_students']) {
            return back()->withErrors([
                'max_students' => 'Ya hay '.$projectIdea->activeAssignmentsCount().' postulantes; el cupo no puede ser menor.',
            ]);
        }

        $projectIdea->update(['max_students' => $data['max_students']]);
        $projectIdea->syncDevelopmentStatus();

        $projectIdea->organizer?->notify(new ProjectIdeaReviewed($projectIdea->fresh(), $data['max_students']));

        return back()->with(
            'status',
            'Cupo definido: '.$data['max_students'].' '.($data['max_students'] === 1 ? 'alumno.' : 'alumnos.'),
        );
    }

    // PATCH /ideas/{projectIdea}/iniciar - el profesor inicia el proyecto con los aceptados
    public function startDevelopment(ProjectIdea $projectIdea)
    {
        $this->authorize('startDevelopment', $projectIdea);

        $accepted = $projectIdea->acceptedAssignmentsCount();
        $pendingApplications = $projectIdea->assignments()
            ->where('status', Assignment::STATUS_APPLIED)
            ->get();

        if ($accepted < 1) {
            return back()->withErrors([
                'start' => 'Aceptá al menos un postulante para iniciar el proyecto.',
            ]);
        }

        if (! $projectIdea->startDevelopment()) {
            return back()->with('status', 'El proyecto no se puede iniciar en su estado actual.');
        }

        $this->notifyRejectedApplications($pendingApplications);

        return back()->with(
            'status',
            'Proyecto iniciado con '.$accepted.' '.($accepted === 1 ? 'integrante.' : 'integrantes.'),
        );
    }

    // GET /mis-ideas - el beneficiario ve únicamente las ideas que publicó
    public function mine(Request $request)
    {
        abort_unless(auth()->user()->hasRole('beneficiario'), 403);

        $ideas = ProjectIdea::query()
            ->with(['organizer', 'attachments', 'tags'])
            ->withCount([
                'assignments as active_assignments_count' => fn ($query) => $query->whereNotIn('status', Assignment::INACTIVE_STATUSES),
                'assignments as accepted_assignments_count' => fn ($query) => $query->whereIn('status', Assignment::ACCEPTED_STATUSES),
            ])
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
            ->withCount([
                'assignments as active_assignments_count' => fn ($query) => $query->whereNotIn('status', Assignment::INACTIVE_STATUSES),
                'assignments as accepted_assignments_count' => fn ($query) => $query->whereIn('status', Assignment::ACCEPTED_STATUSES),
            ])
            ->when(
                $user?->hasAnyRole(['profesor', 'admin']),
                fn ($query) => $query->whereIn('status', ['available', 'in_development', 'completed']),
                fn ($query) => $query->when(
                    $user?->can('ViewAny:ProjectIdea'),
                    fn ($query) => $query->where('status', 'available'),
                    fn ($query) => $query->where('status', 'completed'),
                ),
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

        $projectIdea->load([
            'organizer',
            'attachments',
            'tags',
            'assignments.student',
            'assignments.updates.images',
            'updates.student',
            'updates.approver',
            'updates.images',
        ]);
        $projectIdea->setAttribute(
            'active_assignments_count',
            $projectIdea->assignments->whereNotIn('status', Assignment::INACTIVE_STATUSES)->count(),
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
     * Avisa a los alumnos que sus postulaciones pendientes fueron rechazadas
     * al iniciar o cancelar un proyecto.
     *
     * @param  Collection<int, Assignment>  $assignments
     */
    private function notifyRejectedApplications($assignments): void
    {
        foreach ($assignments as $assignment) {
            $assignment->loadMissing('projectIdea');

            $assignment->student?->notify(new ApplicationReviewed($assignment, Assignment::STATUS_REJECTED));
        }
    }

    /**
     * Reglas de validación para el campo de etiquetas.
     *
     * @return array<int, mixed>
     */
    private function tagRules(): array
    {
        return [
            'nullable',
            'string',
            'max:255',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (count($this->tagNamesFrom($value)) > ProjectIdea::MAX_TAGS) {
                    $fail('Solo podés agregar hasta '.ProjectIdea::MAX_TAGS.' etiquetas por idea.');
                }
            },
        ];
    }

    /**
     * Convierte el texto de etiquetas separadas por coma en nombres únicos.
     *
     * @return array<int, string>
     */
    private function tagNamesFrom(?string $tags): array
    {
        return collect(explode(',', (string) $tags))
            ->map(fn (string $tag): string => trim($tag))
            ->filter()
            ->unique(fn (string $tag): string => mb_strtolower($tag))
            ->values()
            ->all();
    }

    /**
     * Crea (si hace falta) las etiquetas indicadas y devuelve sus IDs.
     *
     * @param  array<int, string>  $tagNames
     * @return array<int, int>
     */
    private function tagIdsFrom(array $tagNames): array
    {
        return collect($tagNames)
            ->map(fn (string $tag): int => Tag::firstOrCreate(['name' => $tag])->id)
            ->values()
            ->all();
    }
}
