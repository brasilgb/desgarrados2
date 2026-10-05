<?php

use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function assignedRole(User $user, RoleCode $code): Role
{
    $role = Role::firstOrCreate(['code' => $code->value], ['name' => $code->label()]);
    $user->roles()->attach($role, ['created_at' => now()]);

    return $role;
}

test('role catalog creates no users and grants no memberships', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('roles', 5);
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('role_user', 0);
});

test('administrative gate respects each explicit role without a universal bypass', function (RoleCode $code) {
    $user = User::factory()->create();
    assignedRole($user, $code);
    Gate::define('private-boundary', fn (User $user): bool => false);

    expect($user->can('access-administration'))->toBeTrue();
    expect($user->can('viewAny', Role::class))->toBe($code === RoleCode::Administrator);
    expect($user->can('manage-territory'))->toBe($code === RoleCode::Administrator);
    expect($user->can('private-boundary'))->toBeFalse();
})->with(RoleCode::cases());

test('guests common users and unverified privileged users cannot enter administration', function () {
    $this->get(route('admin.index'))->assertRedirect(route('login'));
    $common = User::factory()->create();
    $this->actingAs($common)->get(route('admin.index'))->assertForbidden();
    $unverified = User::factory()->unverified()->create();
    assignedRole($unverified, RoleCode::Administrator);
    $this->actingAs($unverified)->get(route('admin.index'))->assertRedirect(route('verification.notice'));
});

test('editor sees protected navigation but cannot view or grant roles', function () {
    $editor = User::factory()->create();
    $role = assignedRole($editor, RoleCode::Editor);
    $target = User::factory()->create();

    $this->actingAs($editor)->get(route('admin.index'))->assertInertia(fn (Assert $page) => $page->where('canManageRoles', false));
    $this->get(route('admin.roles'))->assertForbidden();
    $this->post(route('admin.grant', [$target, $role]), ['reason' => 'Escalada indevida'])->assertForbidden();

    $this->assertDatabaseMissing('role_user', ['user_id' => $target->id]);
    $this->assertDatabaseCount('role_assignment_audits', 0);
});

test('administrator can grant and revoke roles with idempotent audit', function () {
    $actor = User::factory()->create();
    assignedRole($actor, RoleCode::Administrator);
    $target = User::factory()->create();
    $role = Role::factory()->create(['code' => RoleCode::Author->value, 'name' => 'Autor']);

    $this->actingAs($actor)->post(route('admin.grant', [$target, $role]), ['reason' => 'Equipe editorial', 'bootstrap' => true])->assertRedirect(route('admin.roles'));
    $this->post(route('admin.grant', [$target, $role]), ['reason' => 'Repetição'])->assertRedirect(route('admin.roles'));
    $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role_id' => $role->id, 'granted_by' => $actor->id]);
    $this->assertDatabaseCount('role_assignment_audits', 1);
    $this->assertDatabaseHas('role_assignment_audits', ['actor_id' => $actor->id, 'target_user_id' => $target->id, 'action' => 'granted', 'reason' => 'Equipe editorial']);

    $this->delete(route('admin.revoke', [$target, $role]), ['reason' => 'Fim da colaboração'])->assertRedirect(route('admin.roles'));
    $this->assertDatabaseMissing('role_user', ['user_id' => $target->id, 'role_id' => $role->id]);
    $this->assertDatabaseHas('role_assignment_audits', ['target_user_id' => $target->id, 'action' => 'revoked']);
});

test('role assignment requires a reason', function () {
    $actor = User::factory()->create();
    $role = assignedRole($actor, RoleCode::Administrator);
    $target = User::factory()->create();

    $this->actingAs($actor)->post(route('admin.grant', [$target, $role]))->assertSessionHasErrors('reason');

    $this->assertDatabaseMissing('role_user', ['user_id' => $target->id]);
});

test('last verified administrator cannot be revoked deleted or unverified', function () {
    $actor = User::factory()->create();
    $role = assignedRole($actor, RoleCode::Administrator);
    $unverified = User::factory()->unverified()->create();
    assignedRole($unverified, RoleCode::Administrator);

    $this->actingAs($actor)->delete(route('admin.revoke', [$actor, $role]), ['reason' => 'Tentativa'])->assertSessionHasErrors('role');
    expect(fn () => $actor->delete())->toThrow(ValidationException::class);
    $actor->email_verified_at = null;
    expect(fn () => $actor->save())->toThrow(ValidationException::class);

    $this->assertDatabaseHas('role_user', ['user_id' => $actor->id, 'role_id' => $role->id]);
    expect($actor->fresh()->email_verified_at)->not->toBeNull();
    $this->assertDatabaseCount('role_assignment_audits', 0);
});

test('an administrator can leave when another verified administrator remains', function () {
    $actor = User::factory()->create();
    $role = assignedRole($actor, RoleCode::Administrator);
    $other = User::factory()->create();
    assignedRole($other, RoleCode::Administrator);

    $this->actingAs($actor)->delete(route('admin.revoke', [$actor, $role]), ['reason' => 'Transição da equipe'])->assertRedirect(route('admin.roles'));

    expect($other->hasRole(RoleCode::Administrator))->toBeTrue();
    expect($actor->hasRole(RoleCode::Administrator))->toBeFalse();
});

test('initial administrator requires explicit bootstrap and an existing verified user', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create();

    $this->artisan('roles:grant', ['user' => $user->id, 'role' => 'administrator', '--bootstrap' => true, '--reason' => 'Concessão inicial explícita'])->assertSuccessful();
    $this->artisan('roles:grant', ['user' => $user->id, 'role' => 'administrator', '--bootstrap' => true, '--reason' => 'Não repetir bootstrap'])->assertFailed();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('role_assignment_audits', 1);
    expect($user->hasRole(RoleCode::Administrator))->toBeTrue();
});

test('unverified users cannot receive administrator membership', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->unverified()->create();

    $this->artisan('roles:grant', ['user' => $user->id, 'role' => 'administrator', '--bootstrap' => true, '--reason' => 'Inicial'])->assertFailed();

    $this->assertDatabaseCount('role_user', 0);
    $this->assertDatabaseCount('role_assignment_audits', 0);
});

test('last administrator keeps the session and account when profile deletion is refused', function () {
    $user = User::factory()->create();
    assignedRole($user, RoleCode::Administrator);

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('password');

    $this->assertAuthenticatedAs($user);
    $this->assertModelExists($user);
});

test('last administrator cannot lose verification by changing the profile email', function () {
    $user = User::factory()->create();
    assignedRole($user, RoleCode::Administrator);

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'email' => 'new@example.test'])->assertSessionHasErrors('email');

    expect($user->fresh()->email)->toBe($user->getRawOriginal('email'));
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('deleting an administrator with a verified successor audits revoked memberships', function () {
    $user = User::factory()->create();
    assignedRole($user, RoleCode::Administrator);
    $successor = User::factory()->create();
    assignedRole($successor, RoleCode::Administrator);

    $user->delete();

    $this->assertModelMissing($user);
    $this->assertDatabaseHas('role_assignment_audits', ['role_code' => 'administrator', 'action' => 'revoked', 'target_user_id' => null]);
    expect($successor->hasRole(RoleCode::Administrator))->toBeTrue();
});

test('CLI grants without bootstrap or an authorized actor are refused', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create();

    $this->artisan('roles:grant', ['user' => $user->id, 'role' => 'administrator', '--reason' => 'Sem autorização'])->assertFailed();

    $this->assertDatabaseCount('role_user', 0);
    $this->assertDatabaseCount('role_assignment_audits', 0);
});

test('administrator role page exposes assignments but excludes user emails', function () {
    $user = User::factory()->create();
    assignedRole($user, RoleCode::Administrator);

    $this->actingAs($user)->get(route('admin.roles'))->assertInertia(fn (Assert $page) => $page->component('admin/roles')->has('users.data', 1)->missing('users.data.0.email'));
});
