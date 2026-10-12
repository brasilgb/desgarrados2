<?php

use App\Models\AdministrationAudit;
use App\Models\Role;
use App\Models\RoleAssignmentAudit;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function administrationTestUser(RoleCode ...$roles): User
{
    $user = User::factory()->create();
    foreach ($roles as $role) {
        $user->roles()->attach(Role::where('code', $role->value)->firstOrFail());
    }

    return $user;
}

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('refuses first-admin bootstrapping outside the explicit maintenance window', function () {
    config(['administration.admin_bootstrap_enabled' => false]);
    $this->artisan('desgarrados:admin:create')->assertFailed();
    expect(User::count())->toBe(0);
});

it('creates a verified first administrator interactively without exposing the secret', function () {
    config(['administration.admin_bootstrap_enabled' => true]);
    $this->artisan('desgarrados:admin:create')
        ->expectsQuestion('Nome completo', 'Administradora Teste')
        ->expectsQuestion('E-mail', 'admin-bootstrap@example.test')
        ->expectsQuestion('Senha forte (mínimo de 8 caracteres)', 'Aa1!bcD2')
        ->expectsQuestion('Repita a senha', 'Aa1!bcD2')
        ->expectsQuestion('Responsável e justificativa para o bootstrap', 'Designação de teste')
        ->expectsConfirmation('Confirmo que o titular controla este e-mail e foi designado como primeiro administrador', 'yes')
        ->expectsOutputToContain('Nenhuma senha foi exibida.')
        ->assertSuccessful();

    $user = User::sole();
    expect($user->is_active)->toBeTrue()->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Aa1!bcD2', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('Aa1!bcD2')
        ->and($user->hasRole(RoleCode::Administrator))->toBeTrue()
        ->and($user->hasRole(RoleCode::Editor))->toBeFalse();
    expect(RoleAssignmentAudit::sole()->action)->toBe('granted');
    expect(RoleAssignmentAudit::sole()->reason)->not->toContain('Strong-Secret-924!');

    $this->artisan('desgarrados:admin:create')
        ->expectsQuestion('Nome completo', 'Segunda administradora')
        ->expectsQuestion('E-mail', 'admin-bootstrap-second@example.test')
        ->expectsQuestion('Senha forte (mínimo de 8 caracteres)', 'Another-Strong-924!')
        ->expectsQuestion('Repita a senha', 'Another-Strong-924!')
        ->expectsQuestion('Responsável e justificativa para o bootstrap', 'Tentativa de duplicação')
        ->expectsConfirmation('Confirmo que o titular controla este e-mail e foi designado como primeiro administrador', 'yes')
        ->expectsOutputToContain('A concessão inicial exige ausência de administradores')
        ->assertFailed();
    expect(User::count())->toBe(1);
});

it('allows admins to search, create and update accounts and audits the changes', function () {
    Event::fake([Registered::class]);
    $admin = administrationTestUser(RoleCode::Administrator);
    $this->actingAs($admin)->get(route('admin.users', ['q' => 'new-user']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/users')->has('users.data', 0));
    $this->post(route('admin.users.store'), ['name' => 'Nova pessoa', 'email' => 'new-user@example.test',
        'password' => 'Strong-Secret-924!', 'password_confirmation' => 'Strong-Secret-924!'])->assertRedirect(route('admin.users'));
    $target = User::where('email', 'new-user@example.test')->sole();
    expect($target->is_active)->toBeTrue()->and($target->email_verified_at)->toBeNull()->and($target->roles)->toHaveCount(0);
    Event::assertDispatched(Registered::class);

    $this->patch(route('admin.users.update', $target), ['name' => 'Nome corrigido', 'email' => 'new-user@example.test',
        'password' => '', 'password_confirmation' => ''])->assertRedirect(route('admin.users'));
    expect($target->fresh()->name)->toBe('Nome corrigido')
        ->and(AdministrationAudit::where('target_user_id', $target->id)->pluck('action')->all())->toContain('user_created', 'user_updated');
});

it('deactivates user access and protects the last active administrator', function () {
    $admin = administrationTestUser(RoleCode::Administrator);
    $this->actingAs($admin)->patch(route('admin.users.status', $admin), ['reason' => 'Tentativa de bloqueio'])->assertSessionHasErrors('status');
    expect($admin->fresh()->is_active)->toBeTrue();

    $otherAdmin = administrationTestUser(RoleCode::Administrator);
    $this->actingAs($admin)->patch(route('admin.users.status', $admin), ['reason' => 'Transição de responsabilidade'])->assertRedirect(route('admin.users'));
    expect($admin->fresh()->is_active)->toBeFalse()->and($otherAdmin->fresh()->is_active)->toBeTrue();
    $this->actingAs($admin->fresh())->get(route('admin.users'))->assertRedirect(route('login'));
    expect(AdministrationAudit::where('target_user_id', $admin->id)->where('action', 'user_deactivated')->exists())->toBeTrue();
});
