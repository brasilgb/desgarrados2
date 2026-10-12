<?php

use App\Actions\Editorial\EditorialWorkflow;
use App\Actions\Editorial\ManagePublicationFeatures;
use App\Models\AuditEntry;
use App\Models\Municipality;
use App\Models\Publication;
use App\Models\PublicationFeature;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function curationUser(RoleCode $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('code', $role->value)->firstOrFail());

    return $user;
}

function publishedForCuration(User $author, User $editor, string $title = 'Raízes do interior'): Publication
{
    $workflow = new EditorialWorkflow;
    $publication = $workflow->create($author, ['type' => 'memory', 'title' => $title, 'summary' => 'Memória aprovada.', 'body' => 'Uma história verdadeira.', 'public_byline' => 'Acervo', 'rights_status' => 'original', 'rights_author' => 'Autor do acervo']);
    $revision = $publication->revisions()->firstOrFail();
    $workflow->submit($author, $revision);
    $workflow->review($editor, $revision, true, null);
    $workflow->publish($editor, $publication, $revision);

    return $publication->refresh();
}

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->author = curationUser(RoleCode::Author);
    $this->editor = curationUser(RoleCode::Editor);
    $this->manager = new ManagePublicationFeatures;
});

it('lets only editors curate regions without changing official municipality data', function () {
    $municipality = Municipality::factory()->create();
    $original = $municipality->only(['name', 'slug', 'ibge_code', 'state_id']);
    $this->actingAs($this->author)->post(route('editorial.regions.save'), ['name' => 'Campanha', 'kind' => 'cultural', 'active' => true])->assertForbidden();
    $this->actingAs($this->editor)->post(route('editorial.regions.save'), ['name' => 'Campanha', 'kind' => 'cultural', 'description' => 'Campos e fronteiras.', 'active' => true, 'municipality_ids' => [$municipality->id]])->assertRedirect();
    $region = Region::firstOrFail();
    expect($region->slug)->toBe('campanha')->and($region->municipalities()->pluck('municipalities.id')->all())->toBe([$municipality->id])
        ->and($municipality->refresh()->only(['name', 'slug', 'ibge_code', 'state_id']))->toBe($original);
});

it('creates global and regional features and audits every mutation', function () {
    $publication = publishedForCuration($this->author, $this->editor);
    $region = Region::factory()->create();
    $publication->regions()->attach($region);
    $feature = $this->manager->save($this->editor, ['publication_id' => $publication->id, 'region_id' => $region->id,
        'position' => 1, 'starts_at' => now()->subMinute(), 'ends_at' => now()->addDay()]);
    $this->manager->save($this->editor, ['publication_id' => $publication->id, 'region_id' => $region->id,
        'position' => 2, 'starts_at' => now(), 'ends_at' => null], $feature);
    $this->manager->delete($this->editor, $feature);
    expect(AuditEntry::where('publication_id', $publication->id)->pluck('action')->all())
        ->toContain('feature_created', 'feature_updated', 'feature_deleted');
});

it('rejects overlapping positions but permits adjacent periods and separate scopes', function () {
    $publication = publishedForCuration($this->author, $this->editor);
    $other = publishedForCuration($this->author, $this->editor, 'Outra memória');
    $region = Region::factory()->create();
    $publication->regions()->attach($region);
    $other->regions()->attach($region);
    $end = now()->addHour();
    $this->manager->save($this->editor, ['publication_id' => $publication->id, 'position' => 1, 'starts_at' => now(), 'ends_at' => $end]);
    expect(fn () => $this->manager->save($this->editor, ['publication_id' => $other->id, 'position' => 1, 'starts_at' => now()->addMinutes(30), 'ends_at' => now()->addHours(2)]))->toThrow(ValidationException::class);
    $this->manager->save($this->editor, ['publication_id' => $other->id, 'position' => 1, 'starts_at' => $end, 'ends_at' => now()->addHours(2)]);
    $this->manager->save($this->editor, ['publication_id' => $other->id, 'region_id' => $region->id, 'position' => 1, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
    expect(PublicationFeature::count())->toBe(3);
});

it('rejects drafts hidden publications unrelated regions and unauthorized curators', function () {
    $draft = (new EditorialWorkflow)->create($this->author, ['type' => 'memory', 'title' => 'Rascunho', 'body' => 'Ainda privado.']);
    $region = Region::factory()->create();
    expect(fn () => $this->manager->save($this->editor, ['publication_id' => $draft->id, 'position' => 1, 'starts_at' => now()]))->toThrow(ValidationException::class);
    $published = publishedForCuration($this->author, $this->editor);
    expect(fn () => $this->manager->save($this->editor, ['publication_id' => $published->id, 'region_id' => $region->id, 'position' => 1, 'starts_at' => now()]))->toThrow(ValidationException::class);
    expect(fn () => $this->manager->save($this->author, ['publication_id' => $published->id, 'position' => 1, 'starts_at' => now()]))->toThrow(AuthorizationException::class);
});

it('renders active region identity municipalities features and paginated recent stories', function () {
    $publication = publishedForCuration($this->author, $this->editor);
    $region = Region::factory()->create(['name' => 'Serra', 'slug' => 'serra', 'description' => 'Paisagens e memórias da serra.']);
    $municipality = Municipality::factory()->create();
    $region->municipalities()->attach($municipality);
    $publication->regions()->attach($region);
    $this->manager->save($this->editor, ['publication_id' => $publication->id, 'region_id' => $region->id, 'position' => 1, 'starts_at' => now()->subMinute()]);
    $this->get(route('regions.show', $region))->assertOk()->assertInertia(fn (Assert $page) => $page->component('territory/region')
        ->where('region.name', 'Serra')->has('municipalities.data', 1)->has('featured', 1)->has('recent.data', 1)
        ->where('canonical', route('regions.show', $region)));
    $region->update(['active' => false]);
    $this->get(route('regions.show', $region))->assertNotFound();
});

it('builds the home only from approved real content and removes concealed features immediately', function () {
    $publication = publishedForCuration($this->author, $this->editor);
    $this->manager->save($this->editor, ['publication_id' => $publication->id, 'position' => 1, 'starts_at' => now()->subMinute()]);
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->has('featured', 1)->has('recent', 1)->where('featured.0.title', 'Raízes do interior'));
    (new EditorialWorkflow)->conceal($this->editor, $publication);
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->has('featured', 0)->has('recent', 0));
});
