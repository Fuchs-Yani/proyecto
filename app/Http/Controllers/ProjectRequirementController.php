<?php

namespace App\Http\Controllers;

use App\Models\ProjectIdea;
use App\Models\User;
use App\Notifications\ProjectRequirementApproved;
use App\Notifications\ProjectRequirementSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectRequirementController extends Controller
{
    /**
     * PATCH /ideas/{projectIdea}/requerimientos
     * El equipo guarda o actualiza el borrador de la lista de requerimientos.
     */
    public function upsert(Request $request, ProjectIdea $projectIdea): RedirectResponse
    {
        $this->authorize('manageRequirements', $projectIdea);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:20000'],
        ], [
            'content.required' => 'Escribí la lista de requerimientos del sistema.',
            'content.max' => 'La lista de requerimientos no puede superar los 20000 caracteres.',
        ]);

        $requirement = $projectIdea->requirement()->firstOrNew();

        if ($requirement->exists && $requirement->isApproved()) {
            return back()->with('status', 'Los requerimientos ya fueron aprobados y no se pueden modificar.');
        }

        $requirement->saveDraft($data['content']);

        return back()->with('status', 'Borrador de requerimientos guardado.');
    }

    /**
     * PATCH /ideas/{projectIdea}/requerimientos/enviar
     * El equipo envía la lista para la aprobación del beneficiario y del profesor.
     */
    public function submit(ProjectIdea $projectIdea): RedirectResponse
    {
        $this->authorize('manageRequirements', $projectIdea);

        $requirement = $projectIdea->requirement()->first();

        if ($requirement === null) {
            return back()->withErrors([
                'content' => 'Primero escribí la lista de requerimientos.',
            ]);
        }

        if ($requirement->isApproved()) {
            return back()->with('status', 'Los requerimientos ya están aprobados.');
        }

        if ($requirement->isPending()) {
            return back()->with('status', 'Los requerimientos ya fueron enviados a revisión.');
        }

        if (! $requirement->submitForReview()) {
            return back()->withErrors([
                'content' => 'Escribí la lista de requerimientos antes de enviarla.',
            ]);
        }

        $projectIdea->organizer?->notify(new ProjectRequirementSubmitted($projectIdea));

        User::whereHas('roles', fn ($query) => $query->where('name', 'profesor'))
            ->get()
            ->each(fn (User $professor) => $professor->notify(new ProjectRequirementSubmitted($projectIdea)));

        return back()->with('status', 'Requerimientos enviados. Deben aprobarlos el beneficiario y el profesor.');
    }

    /**
     * PATCH /ideas/{projectIdea}/requerimientos/aprobar
     * El beneficiario dueño y el profesor aprueban la lista. Se requieren
     * ambas aprobaciones para habilitar el inicio del proyecto.
     */
    public function approve(ProjectIdea $projectIdea): RedirectResponse
    {
        $this->authorize('approveRequirements', $projectIdea);

        $requirement = $projectIdea->requirement()->firstOrFail();
        $user = auth()->user();

        $isBeneficiary = $user->hasRole('beneficiario') && $projectIdea->organizer_id === $user->id;
        $isProfessor = $user->hasAnyRole(['admin', 'profesor']);

        if ($isBeneficiary && $requirement->hasBeneficiaryApproval()) {
            return back()->with('status', 'Ya aprobaste los requerimientos como beneficiario.');
        }

        if ($isProfessor && $requirement->hasProfessorApproval()) {
            return back()->with('status', 'Ya aprobaste los requerimientos como profesor.');
        }

        $requirement->approveBy($user);
        $requirement = $requirement->fresh();

        if (! $requirement->isApproved()) {
            $missing = $requirement->hasBeneficiaryApproval()
                ? 'Falta la aprobación del profesor.'
                : 'Falta la aprobación del beneficiario.';

            return back()->with('status', 'Aprobación registrada. '.$missing);
        }

        $projectIdea->refresh();
        $projectIdea->syncDevelopmentStatus();
        $this->notifyApproved($projectIdea, $user);

        $status = 'Requerimientos aprobados por el beneficiario y el profesor.';

        if ($projectIdea->fresh()->status === 'in_development') {
            $status .= ' El proyecto pasó a desarrollo.';
        } else {
            $status .= ' Ya se puede iniciar el proyecto.';
        }

        return back()->with('status', $status);
    }

    /**
     * Avisa al equipo y al beneficiario que la lista quedó aprobada.
     */
    private function notifyApproved(ProjectIdea $projectIdea, User $actor): void
    {
        $recipients = $projectIdea->teamMembers()
            ->push($projectIdea->organizer)
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $user->is($actor));

        $recipients->each(
            fn (User $user) => $user->notify(new ProjectRequirementApproved($projectIdea)),
        );
    }
}
