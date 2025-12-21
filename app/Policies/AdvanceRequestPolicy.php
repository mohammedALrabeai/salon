<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AdvanceRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdvanceRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AdvanceRequest');
    }

    public function view(AuthUser $authUser, AdvanceRequest $advanceRequest): bool
    {
        return $authUser->can('View:AdvanceRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AdvanceRequest');
    }

    public function update(AuthUser $authUser, AdvanceRequest $advanceRequest): bool
    {
        return $authUser->can('Update:AdvanceRequest');
    }

    public function delete(AuthUser $authUser, AdvanceRequest $advanceRequest): bool
    {
        return $authUser->can('Delete:AdvanceRequest');
    }

    public function restore(AuthUser $authUser, AdvanceRequest $advanceRequest): bool
    {
        return $authUser->can('Restore:AdvanceRequest');
    }

    public function forceDelete(AuthUser $authUser, AdvanceRequest $advanceRequest): bool
    {
        return $authUser->can('ForceDelete:AdvanceRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AdvanceRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AdvanceRequest');
    }

    public function replicate(AuthUser $authUser, AdvanceRequest $advanceRequest): bool
    {
        return $authUser->can('Replicate:AdvanceRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AdvanceRequest');
    }

}