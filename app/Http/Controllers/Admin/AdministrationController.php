<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Administration\ManageRoleAssignments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleAssignmentRequest;
use App\Models\Publication;
use App\Models\Role;
use App\Models\RoleAssignmentAudit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdministrationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/index', ['canEdit' => Gate::allows('viewAny', Publication::class),
            'canManageRoles' => Gate::allows('viewAny', Role::class)]);
    }

    public function roles(): Response
    {
        Gate::authorize('viewAny', Role::class);

        return Inertia::render('admin/roles', [
            'roles' => Role::orderBy('name')->get(['id', 'code', 'name']),
            'users' => User::with('roles:id,code,name')->orderBy('id')->paginate(20, ['id', 'name', 'email_verified_at']),
            'audits' => RoleAssignmentAudit::latest('id')->limit(20)->get(['id', 'target_user_id', 'actor_id', 'role_code', 'action', 'reason', 'created_at']),
        ]);
    }

    public function grant(RoleAssignmentRequest $request, User $user, Role $role, ManageRoleAssignments $assignments): RedirectResponse
    {
        $assignments->handle($request->user(), $user, $role, true, $request->validated('reason'));

        return to_route('admin.roles')->with('status', 'Papel concedido.');
    }

    public function revoke(RoleAssignmentRequest $request, User $user, Role $role, ManageRoleAssignments $assignments): RedirectResponse
    {
        $assignments->handle($request->user(), $user, $role, false, $request->validated('reason'));

        return to_route('admin.roles')->with('status', 'Papel revogado.');
    }
}
