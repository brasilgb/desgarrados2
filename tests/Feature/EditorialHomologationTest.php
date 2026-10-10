<?php

use App\Enums\PublicationStatus;
use App\Enums\RevisionStatus;
use App\Models\AuditEntry;
use App\Models\Publication;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Homologação DESG-V2-006: fluxo editorial completo pelas rotas HTTP, com usuários temporários
 * criados apenas no banco dedicado de testes.
 */
function homologationUser(RoleCode ...$roles): User
{
    $user = User::factory()->create();
    foreach ($roles as $role) {
        $user->roles()->attach(Role::where('code', $role->value)->firstOrFail(), ['created_at' => now()]);
    }

    return $user;
}

function homologationText(string $title, string $body = "Primeiro parágrafo.\n\nSegundo parágrafo."): array
{
    return ['type' => 'memory', 'title' => $title, 'summary' => 'Resumo de homologação.', 'body' => $body,
        'public_byline' => 'Assinatura pública', 'sources' => []];
}

function homologationTransition(User $actor, Publication $publication, string $action, array $data = []): TestResponse
{
    return test()->actingAs($actor)->post(route('editorial.transition', $publication), ['action' => $action, ...$data]);
}

function homologationGuest(): void
{
    // Visitante real: sem guard, sessão ou estado SSR (scoped) reaproveitados das requisições anteriores.
    // Em PHP-FPM cada requisição recria esse estado; no processo de teste ele precisa ser descartado.
    app()->forgetScopedInstances();
    app('auth')->forgetGuards();
    test()->flushSession();
}

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('homologates the complete editorial flow through HTTP routes', function () {
    $collaborator = homologationUser(RoleCode::Collaborator);
    $editor = homologationUser(RoleCode::Editor);
    $secondEditor = homologationUser(RoleCode::Editor);

    // 1–2. Colaborador cria publicação privada; a primeira revisão é salva com ela.
    $this->actingAs($collaborator)->post(route('editorial.store'), homologationText('Causo do passo do rio'))->assertRedirect();
    $publication = Publication::sole();
    $first = $publication->revisions()->sole();
    expect($publication->visibility->value)->toBe('private')->and($first->status)->toBe(RevisionStatus::Draft);
    $slug = $publication->currentSlug->slug;
    $this->get(route('stories.show', $slug))->assertNotFound();

    // 3. Submissão para análise.
    homologationTransition($collaborator, $publication, 'submit', ['revision_id' => $first->id])->assertRedirect();

    // 4–5. Editor diferente revisa e rejeita com justificativa obrigatória.
    homologationTransition($editor, $publication, 'reject', ['revision_id' => $first->id])->assertSessionHasErrors();
    homologationTransition($editor, $publication, 'reject', ['revision_id' => $first->id, 'note' => 'NOTA-INTERNA-SIGILOSA: conferir datas'])->assertRedirect();
    expect($first->refresh()->status)->toBe(RevisionStatus::Rejected);
    homologationTransition($editor, $publication, 'publish', ['revision_id' => $first->id])->assertSessionHasErrors();

    // 6. Autor da publicação cria nova versão.
    $this->actingAs($collaborator)->post(route('editorial.revision', $publication), homologationText('Causo do passo do rio', "Versão corrigida.\n\nCom datas conferidas."))->assertRedirect();
    $second = $publication->revisions()->where('version', 2)->sole();
    homologationTransition($collaborator, $publication, 'submit', ['revision_id' => $second->id])->assertRedirect();
    homologationTransition($collaborator, $publication, 'approve', ['revision_id' => $second->id])->assertForbidden();

    // 7–8. Editor aprova e publica.
    homologationTransition($editor, $publication, 'approve', ['revision_id' => $second->id, 'note' => 'NOTA-INTERNA-APROVACAO'])->assertRedirect();
    homologationTransition($editor, $publication, 'publish', ['revision_id' => $second->id])->assertRedirect();
    expect($publication->refresh()->status)->toBe(PublicationStatus::Published);

    // 9. Visitante acessa a publicação aprovada, sem notas de revisão nem identidades privadas.
    homologationGuest();
    $public = $this->get(route('stories.show', $slug))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('editorial/publication')->where('publication.title', 'Causo do passo do rio'));
    foreach (['NOTA-INTERNA', $collaborator->email, $editor->email, 'review_note', 'scheduled_by'] as $private) {
        expect($public->getContent())->not->toContain($private);
    }

    // 10. Nova revisão em andamento não altera a versão publicada.
    $this->actingAs($collaborator)->post(route('editorial.revision', $publication), homologationText('Rascunho que não deve vazar'))->assertRedirect();
    $third = $publication->revisions()->where('version', 3)->sole();
    homologationGuest();
    $this->get(route('stories.show', $slug))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('publication.title', 'Causo do passo do rio'))
        ->assertDontSee('Rascunho que não deve vazar');

    // 11. Agendamento substitui a versão pública somente na data autorizada.
    homologationTransition($collaborator, $publication, 'submit', ['revision_id' => $third->id])->assertRedirect();
    homologationTransition($secondEditor, $publication, 'approve', ['revision_id' => $third->id])->assertRedirect();
    homologationTransition($secondEditor, $publication, 'schedule', ['revision_id' => $third->id, 'scheduled_for' => now()->addHour()->toIso8601String()])->assertRedirect();
    homologationGuest();
    $this->artisan('editorial:publish-due')->assertSuccessful();
    $this->get(route('stories.show', $slug))->assertInertia(fn (Assert $page) => $page->where('publication.title', 'Causo do passo do rio'));
    $this->travel(61)->minutes();
    $this->artisan('editorial:publish-due')->assertSuccessful();
    $this->get(route('stories.show', $slug))->assertInertia(fn (Assert $page) => $page->where('publication.title', 'Rascunho que não deve vazar'));
    $this->artisan('editorial:publish-due')->assertSuccessful();
    expect(AuditEntry::where('publication_id', $publication->id)->where('action', 'published')->count())->toBe(2);

    // 12. Publicação ocultada deixa de ser pública, inclusive por slugs antigos.
    $this->actingAs($editor)->patch(route('editorial.metadata', $publication), ['slug' => 'causo-renomeado'])->assertRedirect();
    homologationGuest();
    $this->get(route('stories.show', $slug))->assertRedirect(route('stories.show', 'causo-renomeado'));
    homologationTransition($editor, $publication, 'hide')->assertRedirect();
    homologationGuest();
    $this->get(route('stories.show', 'causo-renomeado'))->assertNotFound();
    $this->get(route('stories.show', $slug))->assertNotFound();
    $this->get(route('stories.index'))->assertInertia(fn (Assert $page) => $page->has('publications.data', 0));
});

it('applies the DESG-V2-006 role matrix with independent administrator and editor roles', function (array $roles, bool $manageRoles, bool $review, bool $create) {
    $user = homologationUser(...$roles);
    $author = homologationUser(RoleCode::Author);
    $this->actingAs($author)->post(route('editorial.store'), homologationText('Matriz de papéis'));
    $publication = Publication::sole();
    $revision = $publication->revisions()->sole();
    homologationTransition($author, $publication, 'submit', ['revision_id' => $revision->id]);

    expect(Gate::forUser($user)->allows('viewAny', Role::class))->toBe($manageRoles)
        ->and(Gate::forUser($user)->allows('review', $revision->refresh()))->toBe($review)
        ->and(Gate::forUser($user)->allows('publish', $publication))->toBe($review)
        ->and(Gate::forUser($user)->allows('create', Publication::class))->toBe($create);
    homologationTransition($user, $publication, 'approve', ['revision_id' => $revision->id])->assertStatus($review ? 302 : 403);
})->with([
    'usuário comum' => [[], false, false, false],
    'colaborador' => [[RoleCode::Collaborator], false, false, true],
    'autor' => [[RoleCode::Author], false, false, true],
    'editor' => [[RoleCode::Editor], false, true, true],
    'administrador' => [[RoleCode::Administrator], true, false, false],
    'administrador + editor' => [[RoleCode::Administrator, RoleCode::Editor], true, true, true],
]);

it('keeps segregation for an administrator who is also editor', function () {
    $adminEditor = homologationUser(RoleCode::Administrator, RoleCode::Editor);
    $this->actingAs($adminEditor)->post(route('editorial.store'), homologationText('Texto do próprio editor'));
    $publication = Publication::sole();
    $revision = $publication->revisions()->sole();
    homologationTransition($adminEditor, $publication, 'submit', ['revision_id' => $revision->id])->assertRedirect();

    homologationTransition($adminEditor, $publication, 'approve', ['revision_id' => $revision->id])->assertForbidden();
    expect($revision->refresh()->status)->toBe(RevisionStatus::InReview);
});

it('blocks editors from managing roles and administrators without editor from the newsroom actions', function () {
    $editor = homologationUser(RoleCode::Editor);
    $administrator = homologationUser(RoleCode::Administrator);
    $target = User::factory()->create();
    $editorRole = Role::where('code', RoleCode::Editor->value)->firstOrFail();

    $this->actingAs($editor)->get(route('admin.roles'))->assertForbidden();
    $this->actingAs($editor)->post(route('admin.grant', [$target, $editorRole]), ['reason' => 'Tentativa indevida'])->assertForbidden();
    expect($target->roles()->count())->toBe(0);

    $this->actingAs($administrator)->post(route('editorial.store'), homologationText('Administrador sem papel editorial'))->assertForbidden();
    expect(Publication::count())->toBe(0);
});

it('documents the manual bootstrap procedure end to end without creating accounts', function () {
    $operator = User::factory()->create();
    $unverified = User::factory()->unverified()->create();
    $usersBefore = User::count();

    $this->artisan('roles:grant', ['user' => 999999, 'role' => 'administrator', '--bootstrap' => true, '--reason' => 'Conta inexistente'])->assertFailed();
    $this->artisan('roles:grant', ['user' => $unverified->id, 'role' => 'administrator', '--bootstrap' => true, '--reason' => 'Conta sem verificação'])->assertFailed();
    $this->artisan('roles:grant', ['user' => $operator->id, 'role' => 'administrator', '--bootstrap' => true])->assertFailed();
    $this->artisan('roles:grant', ['user' => $operator->id, 'role' => 'editor', '--bootstrap' => true, '--reason' => 'Bootstrap só concede Administrador'])->assertFailed();
    $this->artisan('roles:grant', ['user' => $operator->id, 'role' => 'administrator', '--bootstrap' => true, '--reason' => 'Primeiro administrador escolhido pelo operador'])->assertSuccessful();

    // Editor é concessão separada, feita pelo administrador com justificativa e auditoria.
    expect($operator->hasRole(RoleCode::Administrator))->toBeTrue()->and($operator->hasRole(RoleCode::Editor))->toBeFalse();
    $this->artisan('roles:grant', ['user' => $operator->id, 'role' => 'editor', '--actor' => $operator->id, '--reason' => 'Acúmulo explícito de Editor'])->assertSuccessful();

    expect($operator->fresh()->hasRole(RoleCode::Editor))->toBeTrue()->and(User::count())->toBe($usersBefore);
    $this->assertDatabaseCount('role_assignment_audits', 2);
});
