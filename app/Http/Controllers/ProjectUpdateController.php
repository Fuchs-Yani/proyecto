<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectUpdateRequest;
use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\ProjectUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProjectUpdateController extends Controller
{
    // GET /avances - el profesor revisa los avances agrupados por proyecto
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProjectUpdate::class);

        $ideas = ProjectIdea::query()
            ->whereHas('updates')
            ->with([
                'organizer',
                'updates' => fn ($query) => $query->with(['student', 'approver', 'images'])->latest(),
            ])
            ->withCount([
                'updates as total_updates_count',
                'updates as pending_updates_count' => fn ($query) => $query->where('status', ProjectUpdate::STATUS_PENDING),
            ])
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        return view('professor.progress', compact('ideas'));
    }

    // POST /assignments/{assignment}/avances - el alumno postulado envía un avance
    public function store(StoreProjectUpdateRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('create', ProjectUpdate::class);

        abort_unless(
            auth()->user()->hasRole('student') && $assignment->student_id === auth()->id(),
            403,
            'Solo podés enviar avances de tus propias postulaciones.',
        );

        abort_if(
            $assignment->status === 'rejected',
            403,
            'No podés enviar avances de una postulación rechazada.',
        );

        $data = $request->validated();
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $assignment, $data, &$storedPaths): void {
                $update = ProjectUpdate::create([
                    'assignment_id' => $assignment->id,
                    'project_idea_id' => $assignment->project_idea_id,
                    'student_id' => auth()->id(),
                    'description' => $data['description'],
                    'repository_url' => $data['repository_url'] ?? null,
                    'status' => ProjectUpdate::STATUS_PENDING,
                ]);

                foreach ((array) $request->file('images', []) as $file) {
                    $path = $file->store('progress-images', 'public');
                    $storedPaths[] = $path;

                    $update->images()->create([
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            // Si falla el guardado en la base, no deja imágenes huérfanas en el disco.
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        return back()->with('status', 'Avance enviado. Queda pendiente de aprobación del profesor.');
    }

    // PATCH /avances/{projectUpdate}/aprobar - el profesor aprueba el avance
    public function approve(ProjectUpdate $projectUpdate): RedirectResponse
    {
        $this->authorize('approve', $projectUpdate);

        if ($projectUpdate->isApproved()) {
            return back()->with('status', 'Este avance ya estaba aprobado.');
        }

        $projectUpdate->update([
            'status' => ProjectUpdate::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('status', 'Avance aprobado. El beneficiario ya puede verlo.');
    }
}
