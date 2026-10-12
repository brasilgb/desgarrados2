<?php

use App\Actions\Editorial\EditorialWorkflow;
use App\Enums\PublicationStatus;
use App\Models\AdministrationAudit;
use App\Models\AuditEntry;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function rightsEvidenceUser(RoleCode $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('code', $role->value)->firstOrFail());

    return $user;
}

function rightsEvidencePdf(string $name = 'autorizacao.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\nComprovante fictício de teste.\n%%EOF");
}

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('rights');
    $this->author = rightsEvidenceUser(RoleCode::Author);
    $this->editor = rightsEvidenceUser(RoleCode::Editor);
    $this->workflow = new EditorialWorkflow;
    $this->publication = $this->workflow->create($this->author, ['type' => 'memory', 'title' => 'Obra com autorização',
        'body' => 'Texto de homologação.', 'rights_status' => 'authorized', 'rights_holder' => 'Titular DEMO',
        'rights_basis' => 'Autorização escrita', 'rights_reference' => 'termo-2026-demo']);
    $this->revision = $this->publication->revisions()->sole();
});

it('stores private rights evidence by revision and preserves superseded document history', function () {
    $this->actingAs($this->author)->post(route('editorial.rights-evidence.store', $this->revision), ['file' => rightsEvidencePdf()])->assertRedirect();
    $first = $this->revision->rightsEvidence()->sole();
    expect($first->status)->toBe('current')->and($first->uploaded_by)->toBe($this->author->id)
        ->and(strlen($first->sha256))->toBe(64);
    Storage::disk('rights')->assertExists($first->path);

    $this->post(route('editorial.rights-evidence.store', $this->revision), ['file' => rightsEvidencePdf('nova-autorizacao.pdf')])->assertRedirect();
    expect($this->revision->rightsEvidence()->count())->toBe(2)
        ->and($first->fresh()->status)->toBe('superseded')
        ->and($this->revision->rightsEvidence()->where('status', 'current')->count())->toBe(1)
        ->and(AdministrationAudit::where('action', 'rights_evidence_uploaded')->count())->toBe(2);

    $current = $this->revision->rightsEvidence()->where('status', 'current')->sole();
    $this->get(route('editorial.rights-evidence.download', $current))->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    Storage::disk('public')->assertMissing($current->path);
});

it('denies proof access to unrelated users and rejects unsupported document types', function () {
    $other = rightsEvidenceUser(RoleCode::Author);
    $this->actingAs($this->author)->post(route('editorial.rights-evidence.store', $this->revision), ['file' => rightsEvidencePdf()])->assertRedirect();
    $evidence = $this->revision->rightsEvidence()->sole();
    $this->actingAs($other)->get(route('editorial.rights-evidence.download', $evidence))->assertForbidden();
    $this->actingAs($other)->post(route('editorial.rights-evidence.store', $this->revision), ['file' => rightsEvidencePdf()])->assertForbidden();
    $this->actingAs($this->author)->post(route('editorial.rights-evidence.store', $this->revision), [
        'file' => UploadedFile::fake()->createWithContent('script.html', '<script>alert(1)</script>'),
    ])->assertSessionHasErrors('file');
    $this->assertDatabaseCount('revision_rights_evidence', 1);
    expect($evidence->fresh()->status)->toBe('current');
});

it('immediately withdraws revoked publication rights and leaves an audit record', function () {
    $this->workflow->submit($this->author, $this->revision);
    $this->workflow->review($this->editor, $this->revision, true, 'Comprovante conferido.');
    $this->workflow->publish($this->editor, $this->publication, $this->revision->refresh());
    $this->assertDatabaseHas('publications', ['id' => $this->publication->id, 'status' => PublicationStatus::Published->value]);
    $this->actingAs($this->editor)->post(route('editorial.rights.revoke', $this->publication), ['reason' => 'Titular revogou a licença.'])->assertRedirect();
    expect($this->publication->fresh()->status->value)->toBe('hidden')
        ->and(AuditEntry::where('publication_id', $this->publication->id)->where('action', 'rights_revoked')->exists())->toBeTrue();
    $this->get(route('stories.show', $this->publication->currentSlug->slug))->assertNotFound();
});

it('cancels a scheduled revision when its rights are revoked before publication', function () {
    $this->workflow->submit($this->author, $this->revision);
    $this->workflow->review($this->editor, $this->revision, true, 'Aguardando confirmação final.');
    $this->workflow->schedule($this->editor, $this->publication, $this->revision->refresh(), now()->addDay()->toIso8601String());

    $this->actingAs($this->editor)->post(route('editorial.rights.revoke', $this->publication), ['reason' => 'Autorização retirada antes da publicação.'])->assertRedirect();
    expect($this->publication->fresh()->status->value)->toBe('hidden')
        ->and($this->publication->fresh()->scheduled_for)->toBeNull()
        ->and(AuditEntry::where('publication_id', $this->publication->id)->where('action', 'rights_revoked')->exists())->toBeTrue();
});
