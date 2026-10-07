<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProjectIdea;
use Illuminate\Auth\Access\HandlesAuthorization;

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
        return $authUser->can('Create:ProjectIdea');
    }

    public function update(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('Update:ProjectIdea');
    }

    public function delete(AuthUser $authUser, ProjectIdea $projectIdea): bool
    {
        return $authUser->can('Delete:ProjectIdea');
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