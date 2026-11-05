<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProjectUpdate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjectUpdatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProjectUpdate');
    }

    public function view(AuthUser $authUser, ProjectUpdate $projectUpdate): bool
    {
        if (! $authUser->can('View:ProjectUpdate')) {
            return false;
        }

        if (! method_exists($authUser, 'hasRole')) {
            return false;
        }

        if ($authUser->hasAnyRole(['admin', 'profesor'])) {
            return true;
        }

        if ($authUser->hasRole('student')) {
            return $projectUpdate->student_id === $authUser->id;
        }

        if ($authUser->hasRole('beneficiario')) {
            return $projectUpdate->isApproved()
                && $projectUpdate->projectIdea?->organizer_id === $authUser->id;
        }

        return false;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProjectUpdate');
    }

    /**
     * Solo el profesor (o un administrador) puede aprobar avances.
     */
    public function approve(AuthUser $authUser, ProjectUpdate $projectUpdate): bool
    {
        return $authUser->can('Update:ProjectUpdate')
            && method_exists($authUser, 'hasRole')
            && $authUser->hasAnyRole(['admin', 'profesor']);
    }

    public function delete(AuthUser $authUser, ProjectUpdate $projectUpdate): bool
    {
        if (! $authUser->can('Delete:ProjectUpdate')) {
            return false;
        }

        return method_exists($authUser, 'hasRole') && $authUser->hasRole('admin');
    }
}
