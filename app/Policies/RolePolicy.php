<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\RoleCode;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->email_verified_at !== null && $user->hasRole(RoleCode::Administrator);
    }

    public function assign(User $user, Role $role): bool
    {
        return $this->viewAny($user);
    }
}
