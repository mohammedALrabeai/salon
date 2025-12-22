<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class PermissionDeniedException extends AuthorizationException
{
    public function __construct(
        private readonly string $permission,
        private readonly ?string $role = null
    ) {
        parent::__construct('PERMISSION_DENIED');
    }

    public function permission(): string
    {
        return $this->permission;
    }

    public function role(): ?string
    {
        return $this->role;
    }
}
