<?php

namespace App\Actions\Administration;

use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Illuminate\Validation\ValidationException;

class ProtectLastAdministrator
{
    /** The caller must hold a database transaction through the mutation. */
    public function handle(User $user, string $errorKey = 'role'): void
    {
        $role = Role::where('code', RoleCode::Administrator->value)->lockForUpdate()->first();
        if ($role === null || ! $role->users()->whereKey($user->id)->exists() || $user->getRawOriginal('email_verified_at') === null) {
            return;
        }
        if ($role->users()->whereNotNull('email_verified_at')->where('users.id', '!=', $user->id)->count() === 0) {
            throw ValidationException::withMessages([$errorKey => 'É necessário manter pelo menos um administrador com e-mail verificado.']);
        }
    }
}
