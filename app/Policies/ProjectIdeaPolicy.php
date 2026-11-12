<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProjectIdea;
use App\Models\ProjectQuestion;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjectIdeaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProjectIdea');
    }

    public function view(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('View:ProjectIdea');
    }

    public function create(AuthUser $authUser): bool
    {
        if (! $authUser->can('Create:ProjectIdea')) {
            return false;
        }

        return ! $this->hasReachedIdeaLimit($authUser);
    }

    /**
     * Un beneficiario puede publicar una única idea.
     */
    private function hasReachedIdeaLimit(AuthUser $authUser): bool
    {
        return method_exists($authUser, 'hasRole')
            && method_exists($authUser, 'projectIdeas')
            && $authUser->hasRole('beneficiario')
            && $authUser->projectIdeas()->exists();
    }

    public function update(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        if (! $authUser->can('Update:ProjectIdea')) {
            return false;
        }

        return method_exists($authUser, 'hasRole') && ($authUser->hasRole('admin') || $projectIdea->organizer_id === $authUser->id);
    }

    /**
     * Solo el profesor (o un administrador) analiza la idea y define
     * cuántos alumnos pueden participar. La idea debe seguir disponible.
     */
    public function analyze(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('View:ProjectIdea')
            && method_exists($authUser, 'hasRole')
            && $authUser->hasAnyRole(['admin', 'profesor'])
            && $projectIdea->status === 'available';
    }

    /**
     * El profesor (o un administrador) puede iniciar el proyecto con los
     * postulantes aceptados aunque el cupo máximo no esté completo.
     */
    public function startDevelopment(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('View:ProjectIdea')
            && method_exists($authUser, 'hasRole')
            && $authUser->hasAnyRole(['admin', 'profesor'])
            && $projectIdea->status === 'available';
    }

    /**
     * Los alumnos del equipo aceptado pueden preguntar al beneficiario
     * mientras el proyecto está en análisis o en desarrollo.
     */
    public function askQuestion(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('View:ProjectIdea')
            && method_exists($authUser, 'hasRole')
            && $authUser->hasRole('student')
            && in_array($projectIdea->status, ['available', 'in_development'], true)
            && $projectIdea->isTeamMember($authUser);
    }

    /**
     * El beneficiario dueño (o un administrador) responde las preguntas.
     */
    public function answerQuestion(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        if (! $authUser->can('View:ProjectIdea') || ! method_exists($authUser, 'hasRole')) {
            return false;
        }

        return $authUser->hasRole('admin') || $projectIdea->organizer_id === $authUser->id;
    }

    /**
     * El equipo redacta y edita la lista de requerimientos mientras el
     * proyecto sigue en la fase de análisis (disponible).
     */
    public function manageRequirements(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('View:ProjectIdea')
            && method_exists($authUser, 'hasRole')
            && $authUser->hasRole('student')
            && $projectIdea->status === 'available'
            && $projectIdea->isTeamMember($authUser);
    }

    /**
     * El beneficiario dueño y el profesor (o admin) aprueban la lista. Se
     * necesitan ambas aprobaciones para habilitar el inicio del proyecto.
     */
    public function approveRequirements(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        if (! $authUser->can('View:ProjectIdea') || ! method_exists($authUser, 'hasRole')) {
            return false;
        }

        $requirement = $projectIdea->requirement()->first();

        if ($requirement === null || ! $requirement->isPending()) {
            return false;
        }

        if ($authUser->hasAnyRole(['admin', 'profesor'])) {
            return ! $requirement->hasProfessorApproval();
        }

        return $authUser->hasRole('beneficiario')
            && $projectIdea->organizer_id === $authUser->id
            && ! $requirement->hasBeneficiaryApproval();
    }

    /**
     * El alumno que hizo la pregunta puede eliminarla mientras no tenga
     * respuesta. El admin mantiene el control total.
     */
    public function deleteQuestion(AuthUser $authUser, ProjectIdea $projectIdea, ProjectQuestion $projectQuestion): bool
    {
        if (! $authUser->can('View:ProjectIdea') || ! method_exists($authUser, 'hasRole')) {
            return false;
        }

        if ($projectQuestion->project_idea_id !== $projectIdea->id) {
            return false;
        }

        if ($authUser->hasRole('admin')) {
            return true;
        }

        return $authUser->hasRole('student')
            && $projectQuestion->asked_by === $authUser->id
            && ! $projectQuestion->isAnswered();
    }

    /**
     * El beneficiario solo puede eliminar su idea mientras siga disponible y
     * nadie se haya postulado. Una vez que hay postulaciones (o el proyecto ya
     * está en desarrollo/terminado) debe conservarse el historial y usar la
     * cancelación con motivo. El admin mantiene el control total.
     */
    public function delete(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        if (! $authUser->can('Delete:ProjectIdea')) {
            return false;
        }

        if (! method_exists($authUser, 'hasRole')) {
            return false;
        }

        if ($authUser->hasRole('admin')) {
            return true;
        }

        return $projectIdea->organizer_id === $authUser->id
            && $projectIdea->status === 'available'
            && ! $projectIdea->hasApplications();
    }

    /**
     * Cancelar reemplaza al borrado cuando ya hay trabajo asociado. El motivo
     * se exige en el controlador y queda guardado junto con la fecha.
     */
    public function cancel(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        if (! $authUser->can('Update:ProjectIdea')) {
            return false;
        }

        if (! method_exists($authUser, 'hasRole')) {
            return false;
        }

        if (! $projectIdea->canBeCancelled()) {
            return false;
        }

        return $authUser->hasRole('admin') || $projectIdea->organizer_id === $authUser->id;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProjectIdea');
    }

    public function restore(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('Restore:ProjectIdea');
    }

    public function forceDelete(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('ForceDelete:ProjectIdea');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProjectIdea');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProjectIdea');
    }

    public function replicate(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('Replicate:ProjectIdea');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProjectIdea');
    }
}
