<?php

use App\Actions\Editorial\EditorialWorkflow;
use App\Console\Commands\PublishScheduledPublications;
use App\Enums\PublicationStatus;
use App\Models\AuditEntry;
use App\Models\Publication;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Inertia\Testing\AssertableInertia as Assert;

function operationsUser(RoleCode $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('code', $role->value)->firstOrFail(), ['created_at' => now()]);

    return $user;
}

/** @return list<Publication> */
function operationsScheduled(int $count, string $prefix = 'Agendada'): array
{
    $workflow = new EditorialWorkflow;
    $author = operationsUser(RoleCode::Author);
    $editor = operationsUser(RoleCode::Editor);
    $publications = [];
    for ($i = 1; $i <= $count; $i++) {
        $p = $workflow->create($author, ['type' => 'memory', 'title' => "{$prefix} {$i}", 'summary' => 'Resumo.', 'body' => 'Corpo.', 'public_byline' => 'Assinatura', 'sources' => []]);
        $r = $p->revisions()->sole();
        $workflow->submit($author, $r);
        $workflow->review($editor, $r, true, null);
        $workflow->schedule($editor, $p, $r->refresh(), now()->addMinutes(10)->toIso8601String());
        $publications[] = $p->refresh();
    }

    return $publications;
}

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('starts the scheduler alongside the local dev processes', function () {
    expect(collect(DevCommands::commands())->pluck('command', 'name')->all())
        ->toHaveKey('scheduler', 'php artisan schedule:work')
        ->toHaveKey('queue');
});

it('registers the publisher every minute with a short overlap lock', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) $e->command, 'editorial:publish-due'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(10);
});

it('publishes due schedules in batches idempotently and records a heartbeat', function () {
    $publications = operationsScheduled(3);
    $this->travel(11)->minutes();

    $this->artisan('editorial:publish-due', ['--batch' => 1])->expectsOutputToContain('Publicadas: 3. Falhas: 0.')->assertSuccessful();
    $this->artisan('editorial:publish-due', ['--batch' => 1])->expectsOutputToContain('Publicadas: 0. Falhas: 0.')->assertSuccessful();

    foreach ($publications as $p) {
        expect($p->refresh()->status)->toBe(PublicationStatus::Published);
    }
    expect(AuditEntry::where('action', 'published')->count())->toBe(3)
        ->and(Cache::get(PublishScheduledPublications::HEARTBEAT_KEY))->toMatchArray(['published' => 0, 'failed' => 0]);
});

it('isolates a failing publication without blocking the rest of the batch', function () {
    [$broken, $healthy] = operationsScheduled(2);
    $this->travel(11)->minutes();
    $this->app->instance(EditorialWorkflow::class, new class($broken->id) extends EditorialWorkflow
    {
        public function __construct(private int $brokenId)
        {
            parent::__construct();
        }

        public function publishDue(Publication $publication): bool
        {
            if ($publication->id === $this->brokenId) {
                throw new RuntimeException('falha simulada com conteúdo que não deve ir ao log');
            }

            return parent::publishDue($publication);
        }
    });
    Log::spy();

    $this->artisan('editorial:publish-due')->expectsOutputToContain('Publicadas: 1. Falhas: 1.')->assertFailed();

    expect($healthy->refresh()->status)->toBe(PublicationStatus::Published)
        ->and($broken->refresh()->status)->toBe(PublicationStatus::Scheduled)
        ->and(AuditEntry::where('publication_id', $broken->id)->where('action', 'schedule_failed')->exists())->toBeTrue();
    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message, array $context) => $context === ['publication_id' => $broken->id, 'exception' => RuntimeException::class]);
});

it('reports operational health without exposing configuration values', function () {
    config(['inertia.ssr.enabled' => false]);

    $this->artisan('ops:health', ['--json' => true])->assertSuccessful()
        ->doesntExpectOutputToContain((string) config('database.connections.mariadb.password'))
        ->doesntExpectOutputToContain((string) config('app.key'))
        ->expectsOutputToContain('"healthy":true');
    $this->artisan('ops:health', ['--strict' => true])->assertFailed();
});

it('fails health checks for a stale scheduler and overdue schedules', function () {
    config(['inertia.ssr.enabled' => false]);
    operationsScheduled(1);
    $this->artisan('editorial:publish-due')->assertSuccessful();
    $this->travel(20)->minutes();

    $this->artisan('ops:health', ['--json' => true])->assertFailed()
        ->expectsOutputToContain('Agendamentos vencidos há mais de 5 min: 1.');
});

it('warns about insecure production configuration by name only', function () {
    config(['inertia.ssr.enabled' => false, 'app.debug' => true, 'app.url' => 'http://desgarrados.test', 'session.secure' => false]);
    $this->app['env'] = 'production';
    Cache::forever(PublishScheduledPublications::HEARTBEAT_KEY, ['at' => now()->toIso8601String(), 'published' => 0, 'failed' => 0]);

    $this->artisan('ops:health', ['--json' => true])->assertSuccessful()
        ->expectsOutputToContain('APP_DEBUG ativo; APP_URL sem HTTPS; SESSION_SECURE_COOKIE desativado')
        ->doesntExpectOutputToContain('desgarrados.test');
});

it('falls back to client rendering without leaking private data when the renderer is down', function () {
    Vite::useHotFile(storage_path('framework/testing/no-hot-file'));
    config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false, 'inertia.ssr.url' => 'http://127.0.0.1:9']);
    [$p] = operationsScheduled(1, 'Pública com renderer fora');
    $this->travel(11)->minutes();
    $this->artisan('editorial:publish-due')->assertSuccessful();
    Log::spy();

    $response = $this->get(route('stories.show', $p->currentSlug->slug))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('editorial/publication'));

    expect($response->getContent())->toContain('<script data-page="app"')->not->toContain('scheduled_by')->not->toContain('review_note');
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context) => $context['type'] === 'connection'
        && $context['component'] === 'editorial/publication' && ! array_key_exists('props', $context));
    expect(config('inertia.ssr.timeout'))->toBe(3.0);
});

it('keeps administrative pages private and non-indexable while SSR is down', function () {
    Vite::useHotFile(storage_path('framework/testing/no-hot-file'));
    config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false, 'inertia.ssr.url' => 'http://127.0.0.1:9']);

    $this->get(route('editorial.index'))->assertRedirect(route('login'));
    $this->actingAs(operationsUser(RoleCode::Editor))->get(route('editorial.index'))->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeader('Cache-Control', 'no-store, private');
});
