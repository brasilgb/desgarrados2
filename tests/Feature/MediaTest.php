<?php

use App\Actions\Editorial\EditorialWorkflow;
use App\Actions\Editorial\MediaLibrary;
use App\Actions\Editorial\RevisionMediaManager;
use App\Enums\MediaProcessingStatus;
use App\Enums\PublicationStatus;
use App\Media\MediaProcessor;
use App\Models\AuditEntry;
use App\Models\MediaAsset;
use App\Models\Publication;
use App\Models\PublicationRevision;
use App\Models\RevisionMedia;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

/** JPEG with a real APP1/EXIF block: orientation 6 (rotate 90° clockwise) and a marker that must never reach derivatives. */
function mediaJpeg(int $width = 800, int $height = 400, bool $exif = true): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, (int) ($width / 2), $height, (int) imagecolorallocate($image, 200, 60, 40));
    ob_start();
    imagejpeg($image, null, 90);
    $jpeg = (string) ob_get_clean();
    if (! $exif) {
        return $jpeg;
    }
    $text = "SEGREDO-GPS-HOMOLOG\0";
    $ifd = pack('v', 2)
        .pack('vvVvv', 0x0112, 3, 1, 6, 0)
        .pack('vvVV', 0x010E, 2, strlen($text), 8 + 2 + 2 * 12 + 4)
        .pack('V', 0);
    $tiff = 'II'.pack('v', 42).pack('V', 8).$ifd.$text;
    $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8).'Exif'."\0\0".$tiff;

    return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
}

function mediaPng(int $width = 600, int $height = 600): string
{
    $image = imagecreatetruecolor($width, $height);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));
    ob_start();
    imagepng($image);

    return (string) ob_get_clean();
}

function mediaWebp(int $width = 500, int $height = 300): string
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    imagewebp($image);

    return (string) ob_get_clean();
}

function mediaFile(string $bytes, string $name = 'foto.jpg', ?string $mime = null): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'media');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, $mime, null, true);
}

function mediaUser(RoleCode ...$roles): User
{
    $user = User::factory()->create();
    foreach ($roles as $role) {
        $user->roles()->attach(Role::where('code', $role->value)->firstOrFail(), ['created_at' => now()]);
    }

    return $user;
}

/** @return array{0: Publication, 1: PublicationRevision} */
function mediaDraft(User $author, string $title = 'Fotografias da estação'): array
{
    $p = (new EditorialWorkflow)->create($author, ['type' => 'memory', 'title' => $title, 'summary' => 'Resumo.',
        'body' => "Primeiro parágrafo.\n\nSegundo parágrafo.", 'public_byline' => 'Assinatura', 'sources' => []]);

    return [$p, $p->revisions()->sole()];
}

function mediaRights(): array
{
    return ['rights_type' => 'own', 'rights_holder' => 'Família Homologação', 'license' => null, 'rights_notes' => null];
}

beforeEach(function () {
    Storage::fake('media');
    $this->seed(RoleSeeder::class);
    $this->author = mediaUser(RoleCode::Author);
    $this->editor = mediaUser(RoleCode::Editor);
    $this->library = app(MediaLibrary::class);
    $this->manager = new RevisionMediaManager;
    $this->workflow = new EditorialWorkflow;
});

/** Publishes a revision with a cover and one content image; returns publication, cover asset and content asset. */
function mediaPublished(object $t, bool $rights = true): array
{
    [$p, $r] = mediaDraft($t->author);
    $cover = $t->library->upload($t->author, mediaFile(mediaJpeg(1800, 900, false)), $rights ? mediaRights() : []);
    $content = $t->library->upload($t->author, mediaFile(mediaPng(), 'detalhe.png'), $rights ? mediaRights() : []);
    $t->manager->attach($t->author, $r, $cover, ['purpose' => 'cover', 'alt_text' => 'Estação ao entardecer', 'caption' => 'Plataforma antiga', 'credit' => 'Acervo da família']);
    $t->manager->attach($t->author, $r, $content, ['purpose' => 'content', 'alt_text' => 'Detalhe do relógio', 'credit' => 'Acervo da família']);
    $t->workflow->submit($t->author, $r);
    $t->workflow->review($t->editor, $r, true, null);
    if ($rights) {
        $t->workflow->publish($t->editor, $p, $r->refresh());
    }

    return [$p->refresh(), $cover->refresh(), $content->refresh(), $r->refresh()];
}

it('stores the original privately under a random name and generates WebP variants without upscaling', function () {
    $asset = $this->library->upload($this->author, mediaFile(mediaJpeg(800, 400), 'Minha Foto <script>.jpg'), mediaRights());

    expect($asset->processing_status)->toBe(MediaProcessingStatus::Ready)
        ->and($asset->original_path)->toStartWith('originals/')->not->toContain('Minha')
        ->and($asset->original_name)->not->toContain('<')
        ->and(array_keys($asset->variants))->toEqualCanonicalizing(['cover', 'content', 'card']);
    Storage::disk('media')->assertExists($asset->original_path);
    // Orientation 6 turns 800×400 into 400×800; no variant is wider than the source.
    foreach (['cover', 'content', 'card'] as $variant) {
        $bytes = Storage::disk('media')->get($asset->variants[$variant]['path']);
        $size = getimagesizefromstring($bytes);
        expect($size['mime'])->toBe('image/webp')->and($size[0])->toBe(400)->and($size[1])->toBe(800)
            ->and($bytes)->not->toContain('SEGREDO-GPS')->not->toContain('Exif');
    }
    expect(AuditEntry::where('media_asset_id', $asset->id)->where('action', 'media_uploaded')->exists())->toBeTrue();
});

it('downscales large images to the variant widths preserving the aspect ratio', function () {
    $asset = $this->library->upload($this->author, mediaFile(mediaJpeg(3000, 1500, false)), []);

    expect($asset->variants['cover'])->toMatchArray(['width' => 1600, 'height' => 800])
        ->and($asset->variants['content'])->toMatchArray(['width' => 1200, 'height' => 600])
        ->and($asset->variants['card'])->toMatchArray(['width' => 640, 'height' => 320]);
});

it('accepts PNG with transparency and WebP', function () {
    $png = $this->library->upload($this->author, mediaFile(mediaPng(), 'a.png'));
    $webp = $this->library->upload($this->author, mediaFile(mediaWebp(), 'b.webp'));

    expect($png->mime)->toBe('image/png')->and($png->processing_status)->toBe(MediaProcessingStatus::Ready)
        ->and($webp->mime)->toBe('image/webp')->and($webp->processing_status)->toBe(MediaProcessingStatus::Ready);
});

it('rejects files whose real content is not an allowed image', function (string $bytes, string $name) {
    expect(fn () => $this->library->upload($this->author, mediaFile($bytes, $name, 'image/jpeg')))->toThrow(ValidationException::class);
    expect(MediaAsset::count())->toBe(0)->and(Storage::disk('media')->allFiles())->toBe([]);
})->with([
    'SVG' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'x.svg'],
    'HTML disfarçado de JPEG' => ['<!doctype html><html><body>oi</body></html>', 'foto.jpg'],
    'PHP disfarçado de imagem' => ['<?php echo 1;', 'foto.jpg'],
    'GIF' => ["GIF89a\x01\x00\x01\x00\x00\x00\x00;", 'a.gif'],
    'JPEG truncado' => ["\xFF\xD8\xFF\xE0\x00\x10JFIF", 'a.jpg'],
]);

it('enforces the size and dimension limits', function () {
    expect(fn () => $this->library->upload($this->author, mediaFile(mediaJpeg(150, 150, false))))->toThrow(ValidationException::class, 'pelo menos 200');
    expect(fn () => $this->library->upload($this->author, mediaFile(mediaJpeg(8100, 300, false))))->toThrow(ValidationException::class, '8000');
    expect(fn () => $this->library->upload($this->author, UploadedFile::fake()->create('grande.jpg', 10 * 1024 + 1, 'image/jpeg')))->toThrow(ValidationException::class, '10 MB');
    expect(MediaAsset::count())->toBe(0);
});

it('reuses the same file uploaded twice by the same owner', function () {
    $bytes = mediaJpeg(500, 500, false);
    $first = $this->library->upload($this->author, mediaFile($bytes));
    $second = $this->library->upload($this->author, mediaFile($bytes, 'copia.jpg'));

    expect($second->id)->toBe($first->id)->and(MediaAsset::count())->toBe(1)
        ->and(Storage::disk('media')->allFiles('originals'))->toHaveCount(1);
});

it('authorizes upload and library access by role and ownership', function () {
    $common = mediaUser();
    $administrator = mediaUser(RoleCode::Administrator);
    $otherAuthor = mediaUser(RoleCode::Author);
    expect(fn () => $this->library->upload($common, mediaFile(mediaJpeg(400, 400, false))))->toThrow(AuthorizationException::class);
    expect(fn () => $this->library->upload($administrator, mediaFile(mediaJpeg(400, 400, false))))->toThrow(AuthorizationException::class);

    $asset = $this->library->upload($this->author, mediaFile(mediaJpeg(400, 400, false)));
    expect($otherAuthor->can('view', $asset))->toBeFalse()->and($this->editor->can('view', $asset))->toBeTrue()
        ->and($otherAuthor->can('block', $asset))->toBeFalse()->and($this->author->can('block', $asset))->toBeFalse()
        ->and($this->editor->can('block', $asset))->toBeTrue();

    $this->actingAs($otherAuthor)->get(route('editorial.media.original', $asset))->assertForbidden();
    $this->actingAs($this->author)->get(route('editorial.media.original', $asset))->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertDownload("original-{$asset->uuid}.jpg");
});

it('uploads through HTTP and lists only authorized images in the newsroom', function () {
    [$p] = mediaDraft($this->author);
    $this->actingAs($this->author)->post(route('editorial.media.store'), ['file' => mediaFile(mediaJpeg(600, 400, false)), ...mediaRights()])
        ->assertRedirect()->assertSessionHasNoErrors();
    $mine = MediaAsset::sole();
    $foreign = $this->library->upload(mediaUser(RoleCode::Author), mediaFile(mediaJpeg(450, 450, false)));

    $this->actingAs($this->author)->get(route('editorial.show', $p))->assertInertia(fn (Assert $page) => $page
        ->where('library', fn ($library) => collect($library)->pluck('uuid')->all() === [$mine->uuid])
        ->where('permissions.editMedia', true)->has('rightsTypes', 5));
    $this->actingAs($this->editor)->get(route('editorial.show', $p))->assertInertia(fn (Assert $page) => $page
        ->where('library', fn ($library) => collect($library)->pluck('uuid')->sort()->values()->all() === collect([$mine->uuid, $foreign->uuid])->sort()->values()->all()));
    $this->actingAs(mediaUser())->post(route('editorial.media.store'), ['file' => mediaFile(mediaJpeg(400, 400, false))])->assertForbidden();
});

it('associates images to a draft revision with required alt text and credit and a single cover', function () {
    [$p, $r] = mediaDraft($this->author);
    $a = $this->library->upload($this->author, mediaFile(mediaJpeg(600, 400, false)));
    $b = $this->library->upload($this->author, mediaFile(mediaPng()));

    expect(fn () => $this->manager->attach($this->author, $r, $a, ['purpose' => 'cover', 'alt_text' => '', 'credit' => 'X']))->toThrow(ValidationException::class);
    expect(fn () => $this->manager->attach($this->author, $r, $a, ['purpose' => 'cover', 'alt_text' => 'Texto', 'credit' => '']))->toThrow(ValidationException::class);
    $this->actingAs($this->author)->post(route('editorial.revision-media.store', $p), ['revision_id' => $r->id, 'media' => $a->uuid,
        'purpose' => 'cover', 'alt_text' => 'Capa', 'credit' => 'Autor'])->assertRedirect()->assertSessionHasNoErrors();
    $this->manager->attach($this->author, $r, $b, ['purpose' => 'cover', 'alt_text' => 'Nova capa', 'credit' => 'Autor']);

    expect(RevisionMedia::where('purpose', 'cover')->count())->toBe(1)->and(RevisionMedia::where('purpose', 'cover')->value('media_asset_id'))->toBe($b->id);
    expect(fn () => RevisionMedia::query()->insert(['publication_revision_id' => $r->id, 'media_asset_id' => $a->id, 'purpose' => 'cover',
        'position' => 0, 'alt_text' => 'x', 'credit' => 'y', 'created_at' => now(), 'updated_at' => now()]))->toThrow(QueryException::class);
});

it('orders content images and detaches them only from the draft', function () {
    [$p, $r] = mediaDraft($this->author);
    $assets = collect([mediaJpeg(600, 401, false), mediaJpeg(600, 402, false), mediaJpeg(600, 403, false)])
        ->map(fn ($bytes) => $this->library->upload($this->author, mediaFile($bytes)));
    $rows = $assets->map(fn ($asset, $i) => $this->manager->attach($this->author, $r, $asset, ['purpose' => 'content', 'alt_text' => "Imagem {$i}", 'credit' => 'C']));

    $this->actingAs($this->author)->post(route('editorial.revision-media.move', $rows[2]), ['direction' => 'up'])->assertRedirect();
    expect($r->media()->pluck('alt_text')->all())->toBe(['Imagem 0', 'Imagem 2', 'Imagem 1']);
    $this->actingAs($this->author)->delete(route('editorial.revision-media.destroy', $rows[0]))->assertRedirect();
    expect($r->media()->count())->toBe(2)->and(AuditEntry::where('action', 'media_detached')->count())->toBe(1);
    $this->actingAs(mediaUser(RoleCode::Author))->delete(route('editorial.revision-media.destroy', $rows[1]))->assertForbidden();
});

it('freezes the image set once the revision leaves draft', function () {
    [$p, $r] = mediaDraft($this->author);
    $asset = $this->library->upload($this->author, mediaFile(mediaJpeg(600, 400, false)));
    $row = $this->manager->attach($this->author, $r, $asset, ['purpose' => 'content', 'alt_text' => 'Texto', 'credit' => 'C']);
    $this->workflow->submit($this->author, $r);

    expect(fn () => $this->manager->attach($this->author, $r->refresh(), $asset, ['purpose' => 'cover', 'alt_text' => 'X', 'credit' => 'C']))->toThrow(ValidationException::class, 'rascunho');
    expect(fn () => $this->manager->detach($this->author, $row))->toThrow(ValidationException::class);
    expect(fn () => $this->manager->update($this->editor, $row, ['alt_text' => 'Outro', 'credit' => 'C']))->toThrow(ValidationException::class);
});

it('copies images into a new revision without changing the published set', function () {
    [$p, $cover, $content, $published] = mediaPublished($this);
    $this->actingAs($this->author)->post(route('editorial.revision', $p), ['title' => 'Nova edição', 'body' => 'Texto novo.', 'base_revision_id' => $published->id])->assertRedirect();
    $draft = $p->revisions()->where('version', 2)->sole();

    expect($draft->media()->pluck('media_asset_id')->all())->toBe([$cover->id, $content->id]);
    $this->manager->detach($this->author, $draft->media()->where('purpose', 'cover')->sole());
    $this->manager->attach($this->author, $draft, $this->library->upload($this->author, mediaFile(mediaJpeg(700, 500, false)), mediaRights()),
        ['purpose' => 'cover', 'alt_text' => 'Capa nova ainda privada', 'credit' => 'C']);

    expect($published->media()->count())->toBe(2)->and($published->media()->where('purpose', 'cover')->value('media_asset_id'))->toBe($cover->id);
    $this->get(route('stories.show', $p->currentSlug->slug))->assertInertia(fn (Assert $page) => $page
        ->where('publication.cover.alt', 'Estação ao entardecer'))->assertDontSee('Capa nova ainda privada');
});

it('requires processed, active images with usage rights before publishing', function () {
    [$p, $cover, , $r] = mediaPublished($this, rights: false);

    expect(fn () => $this->workflow->publish($this->editor, $p, $r))->toThrow(ValidationException::class, 'direitos de uso');
    expect(fn () => $this->workflow->schedule($this->editor, $p, $r, now()->addHour()->toIso8601String()))->toThrow(ValidationException::class);
    $this->actingAs($this->author)->patch(route('editorial.media.rights', $cover), mediaRights())->assertForbidden();
    foreach (MediaAsset::all() as $asset) {
        $this->library->updateRights($this->editor, $asset, mediaRights());
    }
    $this->library->block($this->editor, $cover, 'Direitos contestados pela família');
    expect(fn () => $this->workflow->publish($this->editor, $p, $r))->toThrow(ValidationException::class, 'bloqueada');
    $this->library->unblock($this->editor, $cover);
    $this->workflow->publish($this->editor, $p, $r);

    expect($p->refresh()->status)->toBe(PublicationStatus::Published)
        ->and(AuditEntry::whereIn('action', ['media_rights_updated', 'media_blocked', 'media_unblocked'])->count())->toBe(4);
});

it('serves derivatives publicly only for visible published revisions', function () {
    [$p, $cover, $content] = mediaPublished($this);
    $url = route('media.show', ['uuid' => $cover->uuid, 'variant' => 'cover']);

    $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/webp')
        ->assertHeader('Cache-Control', 'max-age=300, public')->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->get(route('media.show', ['uuid' => $cover->uuid, 'variant' => 'original']))->assertNotFound();
    $this->get('/midia/'.Str::uuid().'/card.webp')->assertNotFound();
    expect($this->get('/storage/'.$cover->original_path)->status())->toBeIn([403, 404]);
    $this->get(route('editorial.media.original', $cover))->assertRedirect(route('login'));

    $this->workflow->conceal($this->editor, $p);
    $this->get($url)->assertNotFound();
    $this->actingAs($this->author)->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->actingAs(mediaUser(RoleCode::Author))->get($url)->assertNotFound();
});

it('withdraws blocked images from the public page and URLs immediately', function () {
    [$p, $cover, $content] = mediaPublished($this);
    $slug = $p->currentSlug->slug;
    $this->get(route('stories.show', $slug))->assertInertia(fn (Assert $page) => $page->has('publication.images', 1)
        ->where('publication.cover.credit', 'Acervo da família')->where('publication.cover.caption', 'Plataforma antiga')
        ->where('publication.cover.image.width', 1600)->where('publication.cover.image.height', 800)
        ->where('ogImage.url', url(route('media.show', ['uuid' => $cover->uuid, 'variant' => 'cover'], false))));

    $this->library->block($this->editor, $content, 'Imagem com pessoa não autorizada');
    $this->app->forgetScopedInstances();
    $this->get(route('stories.show', $slug))->assertInertia(fn (Assert $page) => $page->has('publication.images', 0));
    $this->get(route('media.show', ['uuid' => $content->uuid, 'variant' => 'content']))->assertNotFound();
    $this->get(route('stories.index'))->assertInertia(fn (Assert $page) => $page->where('publications.data.0.cover.image.width', 640));
});

it('cancels a scheduled publication when an image is blocked before the date', function () {
    [$p, $cover, , $r] = mediaPublished($this, rights: false);
    foreach (MediaAsset::all() as $asset) {
        $this->library->updateRights($this->editor, $asset, mediaRights());
    }
    $this->workflow->schedule($this->editor, $p, $r, now()->addHour()->toIso8601String());
    $this->library->block($this->editor, $cover, 'Retirada a pedido');
    $this->travel(2)->hours();

    $this->artisan('editorial:publish-due')->assertSuccessful();
    expect($p->refresh()->status)->not->toBe(PublicationStatus::Published)
        ->and(AuditEntry::where('publication_id', $p->id)->where('action', 'schedule_blocked')->exists())->toBeTrue();
});

it('processes idempotently and records failures without leaking details', function () {
    $asset = $this->library->upload($this->author, mediaFile(mediaJpeg(600, 400, false)));
    $processor = new MediaProcessor;

    expect($processor->process($asset))->toBeFalse();
    MediaAsset::whereKey($asset->id)->update(['processing_status' => 'processing']);
    expect($processor->process($asset->refresh()))->toBeFalse();
    expect($processor->process($asset, force: true))->toBeTrue()->and($asset->refresh()->processing_status)->toBe(MediaProcessingStatus::Ready);

    Storage::disk('media')->put($asset->original_path, 'corrompido');
    MediaAsset::whereKey($asset->id)->update(['processing_status' => 'pending']);
    expect($processor->process($asset->refresh()))->toBeFalse()
        ->and($asset->refresh()->processing_status)->toBe(MediaProcessingStatus::Failed)
        ->and($asset->processing_error)->toBe('Não foi possível processar a imagem.');
    [, $r] = mediaDraft($this->author);
    expect(fn () => $this->manager->attach($this->author, $r, $asset, ['purpose' => 'cover', 'alt_text' => 'X', 'credit' => 'C']))->toThrow(ValidationException::class);
    $this->artisan('media:process', ['uuid' => $asset->uuid])->assertFailed();
});

it('deletes only unattached images and prunes orphan files on request', function () {
    $loose = $this->library->upload($this->author, mediaFile(mediaJpeg(600, 400, false)));
    $used = $this->library->upload($this->author, mediaFile(mediaPng()));
    [, $r] = mediaDraft($this->author);
    $this->manager->attach($this->author, $r, $used, ['purpose' => 'cover', 'alt_text' => 'X', 'credit' => 'C']);

    expect(fn () => $this->library->delete($this->author, $used))->toThrow(ValidationException::class);
    $this->actingAs($this->author)->delete(route('editorial.media.destroy', $loose))->assertRedirect();
    expect(MediaAsset::find($loose->id))->toBeNull()
        ->and(Storage::disk('media')->exists($loose->original_path))->toBeFalse()
        ->and(Storage::disk('media')->exists($loose->variants['card']['path']))->toBeFalse()
        ->and(AuditEntry::where('action', 'media_deleted')->whereNull('media_asset_id')->exists())->toBeTrue();

    Storage::disk('media')->put('originals/ab/orfao.jpg', 'x');
    $this->artisan('media:prune-orphans')->expectsOutputToContain('Órfão: originals/ab/orfao.jpg')->assertSuccessful();
    Storage::disk('media')->assertExists('originals/ab/orfao.jpg');
    $this->artisan('media:prune-orphans', ['--force' => true])->assertSuccessful();
    Storage::disk('media')->assertMissing('originals/ab/orfao.jpg');
    Storage::disk('media')->assertExists($used->original_path);
});

it('renders the cover with explicit dimensions and alternative text in SSR HTML', function () {
    if (! getenv('EDITORIAL_TEST_SSR')) {
        $this->markTestSkipped('Run with EDITORIAL_TEST_SSR=1 and the built Inertia SSR server.');
    }
    [$p, $cover] = mediaPublished($this);
    $html = $this->get(route('stories.show', $p->currentSlug->slug))->assertOk()->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $img = (new DOMXPath($dom))->query('//main//img[@alt="Estação ao entardecer"]')->item(0);

    expect($img)->not->toBeNull()
        ->and($img->getAttribute('width'))->toBe('1600')->and($img->getAttribute('height'))->toBe('800')
        ->and($img->getAttribute('src'))->toContain($cover->uuid)
        ->and((new DOMXPath($dom))->query('//meta[@property="og:image"]/@content')->item(0)?->nodeValue)->toContain($cover->uuid)
        ->and($html)->not->toContain($cover->original_path);
});
