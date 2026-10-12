<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Administration\ManageRoleAssignments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleAssignmentRequest;
use App\Models\AdministrationAudit;
use App\Models\Publication;
use App\Models\Role;
use App\Models\RoleAssignmentAudit;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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

    public function users(Request $request): Response
    {
        Gate::authorize('viewAny', Role::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $query = User::with('roles:id,code,name')->orderBy('name')->orderBy('id');
        if (filled($filters['q'] ?? null)) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%'));
        }

        return Inertia::render('admin/users', [
            'users' => $query->paginate(20)->withQueryString()->through(fn (User $user) => [
                'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(), 'is_active' => $user->is_active,
                'roles' => $user->roles->map->only(['id', 'name']),
            ]),
            'filters' => $filters,
            'audits' => AdministrationAudit::with('actor:id,name')->latest('id')->limit(30)->get(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        Gate::authorize('viewAny', Role::class);
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ])->validate();
        $user = User::create(['name' => $data['name'], 'email' => mb_strtolower($data['email']), 'password' => $data['password'], 'is_active' => true]);
        AdministrationAudit::create(['actor_id' => $request->user()->id, 'target_user_id' => $user->id,
            'action' => 'user_created', 'changes' => ['email_verification_required' => true], 'created_at' => now()]);
        Event::dispatch(new Registered($user));

        return to_route('admin.users')->with('status', 'Conta criada. O titular deverá verificar o e-mail antes de entrar.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('viewAny', Role::class);
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ])->validate();
        $emailChanged = mb_strtolower($data['email']) !== mb_strtolower($user->email);
        $user->forceFill(['name' => $data['name'], 'email' => mb_strtolower($data['email'])]);
        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null]);
        }
        if (filled($data['password'] ?? null)) {
            $user->forceFill(['password' => $data['password']]);
        }
        $passwordChanged = filled($data['password'] ?? null);
        DB::transaction(function () use ($user, $request, $emailChanged, $passwordChanged): void {
            $user->save();
            AdministrationAudit::create(['actor_id' => $request->user()->id, 'target_user_id' => $user->id,
                'action' => 'user_updated', 'changes' => ['email_changed' => $emailChanged, 'password_changed' => $passwordChanged], 'created_at' => now()]);
        }, 3);
        if ($emailChanged) {
            Event::dispatch(new Registered($user));
        }

        return to_route('admin.users')->with('status', 'Dados da conta atualizados.');
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('viewAny', Role::class);
        $reason = (string) $request->validate(['reason' => ['required', 'string', 'max:500']])['reason'];
        $activating = ! $user->is_active;
        DB::transaction(function () use ($request, $user, $activating, $reason): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $user->forceFill(['is_active' => $activating])->save();
            AdministrationAudit::create(['actor_id' => $request->user()->id, 'target_user_id' => $user->id,
                'action' => $activating ? 'user_activated' : 'user_deactivated', 'changes' => ['is_active' => $activating, 'reason' => $reason], 'created_at' => now()]);
        }, 3);

        return to_route('admin.users')->with('status', $activating ? 'Conta ativada.' : 'Conta desativada.');
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
