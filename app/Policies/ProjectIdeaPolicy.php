<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProjectIdea;
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
