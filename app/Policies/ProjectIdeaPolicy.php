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

    public function delete(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        if (! $authUser->can('Delete:ProjectIdea')) {
            return false;
        }

        return method_exists($authUser, 'hasRole') && ($authUser->hasRole('admin') || $projectIdea->organizer_id === $authUser->id);
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
