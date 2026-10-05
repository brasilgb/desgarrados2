<?php

namespace App\Actions\Administration;

use App\Models\Role;
use App\Models\RoleAssignmentAudit;
use App\Models\User;
use App\RoleCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ManageRoleAssignments
{
    public function handle(?User $actor, User $target, Role $role, bool $grant, string $reason, bool $bootstrap = false): void
    {
        if (trim($reason) === '' || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['reason' => 'Informe uma justificativa de até 500 caracteres.']);
        }
        DB::transaction(function () use ($actor, $target, $role, $grant, $reason, $bootstrap): void {
            $administrator = Role::where('code', RoleCode::Administrator->value)->lockForUpdate()->firstOrFail();
            if ($bootstrap) {
                if (! $grant || $role->code !== RoleCode::Administrator || $administrator->users()->exists()) {
                    throw ValidationException::withMessages(['role' => 'A concessão inicial exige ausência de administradores e o papel Administrador.']);
                }
            } else {
                Gate::forUser($actor)->authorize('assign', $role);
            }
            $target = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            if ($grant && $role->code === RoleCode::Administrator && $target->email_verified_at === null) {
                throw ValidationException::withMessages(['user' => 'O administrador precisa ter e-mail verificado.']);
            }
            $assigned = $target->roles()->whereKey($role->id)->exists();
            if ($assigned === $grant) {
                return;
            }
            if (! $grant && $role->code === RoleCode::Administrator) {
                (new ProtectLastAdministrator)->handle($target);
            }
            if ($grant) {
                $target->roles()->attach($role->id, ['granted_by' => $actor?->id, 'created_at' => now()]);
            } else {
                $target->roles()->detach($role->id);
            }
            RoleAssignmentAudit::create(['actor_id' => $actor?->id, 'target_user_id' => $target->id, 'role_code' => $role->code->value, 'action' => $grant ? 'granted' : 'revoked', 'reason' => $reason]);
        }, 3);
    }
}
