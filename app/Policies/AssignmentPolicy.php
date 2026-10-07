<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Assignment;
use Illuminate\Foundation\Auth\User as AuthUser;

class AssignmentPolicy
{
    use \Illuminate\Auth\Access\HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Assignment');
    }

    public function view(AuthUser $authUser, Assignment $assignment): bool
    {
        if (! $authUser->can('View:Assignment')) {
            return false;
        }
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('admin')) {
            return true;
        }
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('student')) {
            return $assignment->student_id === $authUser->id;
        }
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('beneficiario')) {
            return $assignment->projectIdea?->organizer_id === $authUser->id;
        }

        return true;
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

        return method_exists($authUser, 'hasRole') && ($authUser->hasRole('admin') || $assignment->projectIdea?->organizer_id === $authUser->id);
    }

    public function delete(AuthUser $authUser, Assignment $assignment): bool
    {
        if (! $authUser->can('Delete:Assignment')) {
            return false;
        }
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('admin')) {
            return true;
        }

        return $assignment->student_id === $authUser->id;
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
