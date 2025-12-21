<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DailyEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class DailyEntryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DailyEntry');
    }

    public function view(AuthUser $authUser, DailyEntry $dailyEntry): bool
    {
        return $authUser->can('View:DailyEntry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DailyEntry');
    }

    public function update(AuthUser $authUser, DailyEntry $dailyEntry): bool
    {
        return $authUser->can('Update:DailyEntry');
    }

    public function delete(AuthUser $authUser, DailyEntry $dailyEntry): bool
    {
        return $authUser->can('Delete:DailyEntry');
    }

    public function restore(AuthUser $authUser, DailyEntry $dailyEntry): bool
    {
        return $authUser->can('Restore:DailyEntry');
    }

    public function forceDelete(AuthUser $authUser, DailyEntry $dailyEntry): bool
    {
        return $authUser->can('ForceDelete:DailyEntry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DailyEntry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DailyEntry');
    }

    public function replicate(AuthUser $authUser, DailyEntry $dailyEntry): bool
    {
        return $authUser->can('Replicate:DailyEntry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DailyEntry');
    }

}