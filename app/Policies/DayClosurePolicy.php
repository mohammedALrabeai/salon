<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DayClosure;
use Illuminate\Auth\Access\HandlesAuthorization;

class DayClosurePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DayClosure');
    }

    public function view(AuthUser $authUser, DayClosure $dayClosure): bool
    {
        return $authUser->can('View:DayClosure');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DayClosure');
    }

    public function update(AuthUser $authUser, DayClosure $dayClosure): bool
    {
        return $authUser->can('Update:DayClosure');
    }

    public function delete(AuthUser $authUser, DayClosure $dayClosure): bool
    {
        return $authUser->can('Delete:DayClosure');
    }

    public function restore(AuthUser $authUser, DayClosure $dayClosure): bool
    {
        return $authUser->can('Restore:DayClosure');
    }

    public function forceDelete(AuthUser $authUser, DayClosure $dayClosure): bool
    {
        return $authUser->can('ForceDelete:DayClosure');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DayClosure');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DayClosure');
    }

    public function replicate(AuthUser $authUser, DayClosure $dayClosure): bool
    {
        return $authUser->can('Replicate:DayClosure');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DayClosure');
    }

}