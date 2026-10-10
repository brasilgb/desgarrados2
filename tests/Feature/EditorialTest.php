<?php

use App\Actions\Editorial\EditorialWorkflow;
use App\Enums\PublicationStatus;
use App\Enums\RevisionStatus;
use App\Models\AuditEntry;
use App\Models\Category;
use App\Models\Municipality;
use App\Models\Publication;
use App\Models\PublicationRevision;
use App\Models\PublicationSlug;
use App\Models\Region;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function editorialUser(RoleCode $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('code', $role->value)->firstOrFail());

    return $user;
}
function editorialText(string $title = 'Memórias da nossa terra'): array
{
    return ['type' => 'memory', 'title' => $title, 'summary' => 'Uma lembrança da infância.',
        'body' => "Primeiro parágrafo.\n\nSegundo parágrafo.", 'public_byline' => 'Assinatura editorial',
        'sources' => [['title' => 'Acervo local', 'url' => 'https://example.org/acervo', 'attribution' => 'Arquivo da cidade', 'accessed_at' => '2026-10-08']]];
}
function editorialApproved(EditorialWorkflow $workflow, User $author, User $editor, string $title = 'Memórias da nossa terra'): array
{
    $p = $workflow->create($author, editorialText($title));
    $r = $p->revisions()->firstOrFail();
    $workflow->submit($author, $r);
    $workflow->review($editor, $r, true, 'Conferido');

    return [$p, $r->refresh()];
}

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->workflow = new EditorialWorkflow;
    $this->author = editorialUser(RoleCode::Author);
    $this->editor = editorialUser(RoleCode::Editor);
});

it('creates private publications and revision sources without accepting sensitive fields', function () {
    $p = $this->workflow->create($this->author, [...editorialText(), 'status' => 'published', 'visibility' => 'public', 'author_id' => $this->editor->id]);
    expect($p->status)->toBe(PublicationStatus::Draft)->and($p->author_id)->toBe($this->author->id)
        ->and($p->visibility->value)->toBe('private')->and($p->published_revision_id)->toBeNull()
        ->and($p->revisions()->first()->sources()->count())->toBe(1);
});
it('increments versions without modifying submitted snapshots', function () {
    $p = $this->workflow->create($this->author, editorialText());
    $first = $p->revisions()->firstOrFail();
    $this->workflow->submit($this->author, $first);
    $second = $this->workflow->createRevision($this->author, $p, editorialText('Texto revisto'));
    expect($first->refresh()->title)->toBe('Memórias da nossa terra')->and($first->status)->toBe(RevisionStatus::InReview)
        ->and($second->version)->toBe(2)->and($second->status)->toBe(RevisionStatus::Draft);
});
it('submits approves and publishes a revision atomically with audit', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->publish($this->editor, $p, $r);
    expect($p->refresh()->published_revision_id)->toBe($r->id)->and(Publication::publiclyVisible()->count())->toBe(1)
        ->and(AuditEntry::where('publication_id', $p->id)->pluck('action')->all())->toContain('submitted', 'approved', 'published');
    expect(AuditEntry::where('publication_id', $p->id)->first()->changes)->not->toHaveKey('body');
});
it('rejects submitted revisions with a required review note', function () {
    $p = $this->workflow->create($this->author, editorialText());
    $r = $p->revisions()->firstOrFail();
    $this->workflow->submit($this->author, $r);
    expect(fn () => $this->workflow->review($this->editor, $r, false, null))->toThrow(ValidationException::class);
    $this->workflow->review($this->editor, $r, false, 'Conferir a fonte');
    expect($r->refresh()->status)->toBe(RevisionStatus::Rejected);
    expect(fn () => $this->workflow->publish($this->editor, $p, $r))->toThrow(ValidationException::class);
});
it('rejects publication of drafts', function () {
    $p = $this->workflow->create($this->author, editorialText());
    expect(fn () => $this->workflow->publish($this->editor, $p, $p->revisions()->firstOrFail()))->toThrow(ValidationException::class);
    expect(Publication::publiclyVisible()->count())->toBe(0);
});
it('blocks self approval and requires another editor', function () {
    $p = $this->workflow->create($this->editor, editorialText());
    $r = $p->revisions()->firstOrFail();
    $this->workflow->submit($this->editor, $r);
    expect(fn () => $this->workflow->review($this->editor, $r, true, null))->toThrow(AuthorizationException::class);
    $this->workflow->review(editorialUser(RoleCode::Editor), $r, true, null);
    expect($r->refresh()->status)->toBe(RevisionStatus::Approved);
});
it('enforces the role matrix without administrator bypass', function (RoleCode $role, bool $create, bool $manage) {
    $user = editorialUser($role);
    expect(Gate::forUser($user)->allows('create', Publication::class))->toBe($create)
        ->and(Gate::forUser($user)->allows('manage', Publication::class))->toBe($manage);
})->with([
    [RoleCode::Collaborator, true, false], [RoleCode::Author, true, false], [RoleCode::Editor, true, true],
    [RoleCode::Moderator, false, false], [RoleCode::Administrator, false, false],
]);
it('denies unverified users and users without editorial roles', function () {
    $unverified = User::factory()->unverified()->create();
    $unverified->roles()->attach(Role::where('code', 'editor')->firstOrFail());
    expect(Gate::forUser($unverified)->allows('create', Publication::class))->toBeFalse()
        ->and(Gate::forUser(User::factory()->create())->allows('create', Publication::class))->toBeFalse();
});
it('protects ownership and restricts editorial metadata and publication to editors', function () {
    $p = $this->workflow->create($this->author, editorialText());
    expect(fn () => $this->workflow->createRevision(editorialUser(RoleCode::Author), $p, editorialText()))->toThrow(AuthorizationException::class);
    expect(fn () => $this->workflow->metadata($this->author, $p, ['slug' => 'novo']))->toThrow(AuthorizationException::class);
    expect(fn () => $this->workflow->publish($this->author, $p, $p->revisions()->firstOrFail()))->toThrow(AuthorizationException::class);
});
it('keeps the public revision while a new edition is under review then swaps it atomically', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->publish($this->editor, $p, $r);
    $next = $this->workflow->createRevision($this->author, $p, editorialText('Nova edição'));
    $this->workflow->submit($this->author, $next);
    $this->get(route('stories.show', $p->currentSlug->slug))->assertInertia(fn (Assert $page) => $page->where('publication.title', $r->title));
    $this->workflow->review($this->editor, $next, true, null);
    $this->workflow->publish($this->editor, $p, $next);
    $this->get(route('stories.show', $p->currentSlug->slug))->assertInertia(fn (Assert $page) => $page->where('publication.title', 'Nova edição'));
});
it('schedules approved revisions and publishes only when due', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->schedule($this->editor, $p, $r, now()->addHour()->toIso8601String());
    expect($this->workflow->publishDue($p))->toBeFalse()->and(Publication::publiclyVisible()->count())->toBe(0);
    $this->travel(2)->hours();
    $this->artisan('editorial:publish-due')->assertSuccessful();
    expect($p->refresh()->status)->toBe(PublicationStatus::Published)->and($p->scheduled_for)->toBeNull();
    expect($this->workflow->publishDue($p))->toBeFalse();
});
it('preserves an existing public edition when scheduling its replacement', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->publish($this->editor, $p, $r);
    $next = $this->workflow->createRevision($this->author, $p, editorialText('Depois'));
    $this->workflow->submit($this->author, $next);
    $this->workflow->review($this->editor, $next, true, null);
    $this->workflow->schedule($this->editor, $p, $next, now()->addHour()->toIso8601String());
    expect($p->refresh()->published_revision_id)->toBe($r->id)->and(Publication::publiclyVisible()->count())->toBe(1);
    $this->travel(2)->hours();
    expect($this->workflow->publishDue($p))->toBeTrue()->and($p->refresh()->published_revision_id)->toBe($next->id);
});
it('blocks expired permissions at scheduled execution', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->schedule($this->editor, $p, $r, now()->addHour()->toIso8601String());
    $this->editor->roles()->detach();
    $this->travel(2)->hours();
    expect($this->workflow->publishDue($p))->toBeFalse()->and($p->refresh()->scheduled_for)->toBeNull()
        ->and(AuditEntry::where('action', 'schedule_blocked')->exists())->toBeTrue();
});
it('rejects invalid scheduling and draft scheduling', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    expect(fn () => $this->workflow->schedule($this->editor, $p, $r, now()->subMinute()->toIso8601String()))->toThrow(ValidationException::class);
    $draft = $this->workflow->createRevision($this->author, $p, editorialText());
    expect(fn () => $this->workflow->schedule($this->editor, $p, $draft, now()->addHour()->toIso8601String()))->toThrow(ValidationException::class);
});
it('normalizes slugs and rolls back colliding creations', function () {
    $p = $this->workflow->create($this->author, editorialText());
    expect($p->currentSlug->slug)->toBe('memorias-da-nossa-terra');
    expect(fn () => $this->workflow->create($this->author, editorialText()))->toThrow(ValidationException::class);
    expect(Publication::count())->toBe(1)->and(PublicationRevision::count())->toBe(1);
});
it('preserves aliases and permanently redirects old public slugs', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->publish($this->editor, $p, $r);
    $old = $p->currentSlug->slug;
    $this->workflow->metadata($this->editor, $p, ['slug' => 'novo-endereco']);
    $this->get(route('stories.show', $old))->assertStatus(301)->assertRedirect(route('stories.show', 'novo-endereco'));
    expect(PublicationSlug::where('is_current', true)->count())->toBe(1);
    expect(fn () => $this->workflow->create($this->author, [...editorialText('Outra'), 'slug' => $old]))->toThrow(ValidationException::class);
});
it('does not redirect or leak hidden private archived deleted or future content', function (string $state) {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->publish($this->editor, $p, $r);
    $old = $p->currentSlug->slug;
    $this->workflow->metadata($this->editor, $p, ['slug' => 'endereco-secreto']);
    $p->refresh();
    match ($state) {
        'hidden' => $this->workflow->conceal($this->editor, $p),
        'archived' => $this->workflow->conceal($this->editor, $p, true),
        'deleted' => $this->workflow->delete($this->editor, $p),
        'private' => $p->forceFill(['visibility' => 'private'])->save(),
        'future' => $p->forceFill(['published_at' => now()->addHour()])->save(),
    };
    $this->get(route('stories.show', $old))->assertNotFound()->assertHeaderMissing('Location');
    $this->get(route('stories.show', 'endereco-secreto'))->assertNotFound();
    $this->get(route('stories.index'))->assertInertia(fn (Assert $page) => $page->has('publications.data', 0)->has('sections', 0));
})->with(['hidden', 'archived', 'deleted', 'private', 'future']);
it('associates taxonomy municipality and overlapping regions manually', function () {
    $p = $this->workflow->create($this->author, editorialText());
    $m = Municipality::factory()->create();
    $regions = Region::factory()->count(2)->create();
    $category = Category::create(['name' => 'Memórias', 'slug' => 'memorias']);
    $tag = Tag::create(['name' => 'Infância', 'slug' => 'infancia']);
    $this->workflow->metadata($this->editor, $p, ['municipality_id' => $m->id, 'category_id' => $category->id, 'tag_ids' => [$tag->id], 'region_ids' => $regions->modelKeys()]);
    expect($p->refresh()->municipality->id)->toBe($m->id)->and($p->category->id)->toBe($category->id)->and($p->regions()->count())->toBe(2)->and($p->tags()->count())->toBe(1);
});
it('filters paginates and scopes administrative publications', function () {
    $p = $this->workflow->create($this->author, editorialText());
    $m = Municipality::factory()->create();
    $region = Region::factory()->create();
    $category = Category::create(['name' => 'A', 'slug' => 'a']);
    $this->workflow->metadata($this->editor, $p, ['municipality_id' => $m->id, 'region_ids' => [$region->id], 'category_id' => $category->id]);
    $other = editorialUser(RoleCode::Author);
    $this->workflow->create($other, editorialText('Outra história'));
    $filters = ['status' => 'draft', 'type' => 'memory', 'author_id' => $this->author->id, 'category_id' => $category->id, 'municipality_id' => $m->id, 'region_id' => $region->id, 'from' => now()->toDateString(), 'to' => now()->toDateString()];
    $this->actingAs($this->editor)->get(route('editorial.index', $filters))->assertInertia(fn (Assert $page) => $page->has('publications.data', 1)->where('publications.data.0.id', $p->id));
    $this->actingAs($other)->get(route('editorial.index'))->assertInertia(fn (Assert $page) => $page->has('publications.data', 1));
    $this->actingAs($other)->get(route('editorial.show', $p))->assertForbidden();
});
it('exposes only approved public fields without private identities or notes', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->publish($this->editor, $p, $r);
    $this->get(route('stories.show', $p->currentSlug->slug))->assertInertia(fn (Assert $page) => $page->component('editorial/publication')
        ->where('publication.body', $r->body)->where('publication.byline', $r->public_byline)
        ->missing('publication.author')->missing('publication.editor_id')->missing('publication.reviewer_id')->missing('publication.review_note')
        ->missing('publication.revisions')->missing('publication.published_revision_id')->has('publication.sources', 1));
});
it('rejects arbitrary types HTML and unsafe source URLs', function (array $override) {
    expect(fn () => $this->workflow->create($this->author, [...editorialText(), ...$override]))->toThrow(ValidationException::class);
    expect(Publication::count())->toBe(0);
})->with([[['type' => 'arbitrary']], [['body' => '<img src="https://example.org/x">']], [['sources' => [['title' => 'Insegura', 'url' => 'javascript:alert(1)']]]]]);
it('enforces compound FK approval checks and revision version uniqueness in MariaDB', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    [$other, $foreign] = editorialApproved($this->workflow, $this->author, $this->editor, 'Outra');
    expect(fn () => DB::table('publications')->where('id', $p->id)->update(['published_revision_id' => $foreign->id, 'published_revision_status' => 'approved']))->toThrow(QueryException::class);
    $draft = $this->workflow->createRevision($this->author, $p, editorialText());
    expect(fn () => DB::table('publications')->where('id', $p->id)->update(['published_revision_id' => $draft->id, 'published_revision_status' => 'approved']))->toThrow(QueryException::class);
    expect(fn () => DB::table('publications')->where('id', $p->id)->update(['published_revision_id' => $draft->id]))->toThrow(QueryException::class);
    expect(fn () => DB::table('publications')->where('id', $p->id)->update(['status' => 'published']))->toThrow(QueryException::class);
    expect(fn () => DB::table('publication_revisions')->insert(['publication_id' => $p->id, 'version' => $r->version, 'title' => 'Duplicada', 'body' => 'Texto', 'status' => 'draft']))->toThrow(QueryException::class);
    $this->workflow->publish($this->editor, $p, $r);
    expect(fn () => DB::table('publication_revisions')->where('id', $r->id)->update(['status' => 'rejected']))->toThrow(QueryException::class);
});
it('enforces only one current slug with a database constraint', function () {
    $p = $this->workflow->create($this->author, editorialText());
    expect(fn () => PublicationSlug::create(['publication_id' => $p->id, 'slug' => 'duplicado', 'is_current' => true]))->toThrow(QueryException::class);
});
it('protects taxonomy and preserves stable slugs during rename', function () {
    $this->actingAs($this->author)->post(route('editorial.taxonomy.save', 'categories'), ['name' => 'Memórias'])->assertForbidden();
    $this->actingAs($this->editor)->post(route('editorial.taxonomy.save', 'categories'), ['name' => 'Memórias'])->assertRedirect();
    $category = Category::firstOrFail();
    $this->post(route('editorial.taxonomy.save', ['kind' => 'categories', 'id' => $category->id]), ['name' => 'Novas memórias'])->assertRedirect();
    expect($category->refresh()->slug)->toBe('memorias');
    $p = $this->workflow->create($this->author, editorialText());
    $this->workflow->metadata($this->editor, $p, ['category_id' => $category->id]);
    $this->delete(route('editorial.taxonomy.delete', ['kind' => 'categories', 'id' => $category->id]))->assertSessionHasErrors('taxonomy');
});
it('protects revision routes and marks editorial pages noindex', function () {
    $p = $this->workflow->create($this->author, editorialText());
    $this->actingAs($this->author)->get(route('editorial.show', $p))->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->post(route('editorial.transition', $p), ['action' => 'publish', 'revision_id' => $p->revisions()->first()->id])->assertForbidden();
    $this->post(route('editorial.transition', $p), ['action' => 'submit', 'revision_id' => $p->revisions()->first()->id])->assertRedirect();
    $this->actingAs($this->editor)->get(route('editorial.lookup', ['kind' => 'authors']))->assertOk()->assertJsonMissingPath('0.email');
});
it('serves only the matching public municipal regional and section feeds', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->publish($this->editor, $p, $r);
    $m = Municipality::factory()->create();
    $region = Region::factory()->create();
    $this->workflow->metadata($this->editor, $p, ['municipality_id' => $m->id, 'region_ids' => [$region->id]]);
    $this->get(route('stories.index', ['municipality' => $m->id, 'region' => $region->id]))->assertInertia(fn (Assert $page) => $page->has('publications.data', 1));
    $this->get(route('stories.section', 'causos'))->assertInertia(fn (Assert $page) => $page->has('publications.data', 0));
    $this->get(route('stories.section', 'inexistente'))->assertNotFound();
});
it('soft deletes while preserving revisions aliases and audit', function () {
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor);
    $this->workflow->publish($this->editor, $p, $r);
    $this->workflow->delete($this->editor, $p);
    expect(Publication::find($p->id))->toBeNull()->and(Publication::withTrashed()->find($p->id))->not->toBeNull()
        ->and(PublicationRevision::find($r->id))->not->toBeNull()->and(PublicationSlug::count())->toBe(1)
        ->and(AuditEntry::where('action', 'deleted')->exists())->toBeTrue();
});

it('renders indexable public HTML with SSR metadata and escaped paragraphs without JavaScript', function () {
    if (! getenv('EDITORIAL_TEST_SSR')) {
        $this->markTestSkipped('Run with EDITORIAL_TEST_SSR=1 and the built Inertia SSR server.');
    }
    [$p, $r] = editorialApproved($this->workflow, $this->author, $this->editor, 'Lembranças & raízes');
    $this->workflow->publish($this->editor, $p, $r);
    $response = $this->get(route('stories.show', $p->currentSlug->slug))->assertOk();
    $dom = new DOMDocument;
    @$dom->loadHTML($response->getContent());
    $xpath = new DOMXPath($dom);
    expect($xpath->query('//h1')->item(0)?->textContent)->toBe('Lembranças & raízes')
        ->and($xpath->query('//title')->item(0)?->textContent)->toContain('Lembranças & raízes')
        ->and($xpath->query('//meta[@name="description"]/@content')->item(0)?->nodeValue)->toBe($r->summary)
        ->and($xpath->query('//link[@rel="canonical"]/@href')->item(0)?->nodeValue)->toBe(route('stories.show', $p->currentSlug->slug))
        ->and($xpath->query('//meta[@property="og:title"]/@content')->item(0)?->nodeValue)->toBe($r->title)
        ->and($xpath->query('//main//article//p[contains(text(), "Primeiro parágrafo.")]')->length)->toBe(1)
        ->and($xpath->query('//main//article//p[contains(text(), "Segundo parágrafo.")]')->length)->toBe(1);
});

it('renders the editorial workspace taxonomy and permission states', function () {
    $p = $this->workflow->create($this->author, editorialText());
    $this->actingAs($this->editor)->get(route('editorial.create'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('editorial/create'));
    $this->get(route('editorial.taxonomy', 'categories'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('editorial/taxonomy'));
    $this->actingAs(editorialUser(RoleCode::Author))->get(route('editorial.show', $p))->assertForbidden()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertInertia(fn (Assert $page) => $page->component('editorial/denied'));
});
