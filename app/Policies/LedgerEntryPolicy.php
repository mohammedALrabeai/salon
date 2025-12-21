<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LedgerEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class LedgerEntryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LedgerEntry');
    }

    public function view(AuthUser $authUser, LedgerEntry $ledgerEntry): bool
    {
        return $authUser->can('View:LedgerEntry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LedgerEntry');
    }

    public function update(AuthUser $authUser, LedgerEntry $ledgerEntry): bool
    {
        return $authUser->can('Update:LedgerEntry');
    }

    public function delete(AuthUser $authUser, LedgerEntry $ledgerEntry): bool
    {
        return $authUser->can('Delete:LedgerEntry');
    }

    public function restore(AuthUser $authUser, LedgerEntry $ledgerEntry): bool
    {
        return $authUser->can('Restore:LedgerEntry');
    }

    public function forceDelete(AuthUser $authUser, LedgerEntry $ledgerEntry): bool
    {
        return $authUser->can('ForceDelete:LedgerEntry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LedgerEntry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LedgerEntry');
    }

    public function replicate(AuthUser $authUser, LedgerEntry $ledgerEntry): bool
    {
        return $authUser->can('Replicate:LedgerEntry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LedgerEntry');
    }

}