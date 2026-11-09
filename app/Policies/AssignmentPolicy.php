<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Assignment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AssignmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Assignment');
    }

    public function view(AuthUser $authUser, Assignment $assignment): bool
    {
        if (! $authUser->can('View:Assignment')) {
            return false;
        }
        if (! method_exists($authUser, 'hasRole')) {
            return false;
        }

        if ($authUser->hasAnyRole(['admin', 'profesor'])) {
            return true;
        }

        if ($authUser->hasRole('student')) {
            return $assignment->student_id === $authUser->id;
        }

        if ($authUser->hasRole('beneficiario')) {
            return $assignment->projectIdea?->organizer_id === $authUser->id;
        }

        return false;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Assignment');
    }

    public function update(AuthUser $authUser, Assignment $assignment): bool
    {
        if (! $authUser->can('Update:Assignment')) {
            return false;
        }

        if (! method_exists($authUser, 'hasRole')) {
            return false;
        }

        if ($authUser->hasAnyRole(['admin', 'profesor'])) {
            return true;
        }

        return $assignment->projectIdea?->organizer_id === $authUser->id;
    }

    /**
     * Solo el profesor (o un administrador) puede aceptar o rechazar postulaciones.
     * El beneficiario, aunque sea dueño de la idea, no evalúa postulantes.
     */
    public function review(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('Update:Assignment')
            && method_exists($authUser, 'hasRole')
            && $authUser->hasAnyRole(['admin', 'profesor']);
    }

    /**
     * El alumno solo puede borrar su postulación mientras siga pendiente y el
     * proyecto continúe disponible. Una vez aceptada debe usar "abandonar
     * proyecto" para conservar el historial y recomputar el cupo.
     */
    public function delete(AuthUser $authUser, Assignment $assignment): bool
    {
        if (! $authUser->can('Delete:Assignment')) {
            return false;
        }
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('admin')) {
            return true;
        }

        return $assignment->student_id === $authUser->id
            && $assignment->status === Assignment::STATUS_APPLIED
            && $assignment->projectIdea?->status === 'available';
    }

    /**
     * Permite al alumno abandonar un proyecto ya aceptado sin borrar la
     * postulación ni sus avances.
     */
    public function withdraw(AuthUser $authUser, Assignment $assignment): bool
    {
        if (! $authUser->can('Delete:Assignment')) {
            return false;
        }
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('admin')) {
            return true;
        }

        return $assignment->student_id === $authUser->id
            && in_array($assignment->status, Assignment::WITHDRAWABLE_STATUSES, true);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Assignment');
    }

    public function restore(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('Restore:Assignment');
    }

    public function forceDelete(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('ForceDelete:Assignment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Assignment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Assignment');
    }

    public function replicate(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('Replicate:Assignment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Assignment');
    }
}
