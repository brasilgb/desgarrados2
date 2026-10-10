<?php

// Standalone integration check: committed fixtures are visible to independent workers.

use App\Actions\Editorial\EditorialWorkflow;
use App\Models\Publication;
use App\Models\PublicationSlug;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = config('database.connections.'.config('database.default'));
if (config('database.default') !== 'mariadb' || $connection['database'] !== 'desgarrados2_test'
    || $connection['host'] !== '127.0.0.1' || $connection['username'] !== 'desgarrados'
    || ! empty($connection['url']) || ! empty($connection['unix_socket']) || ! empty($connection['read']) || ! empty($connection['write'])) {
    throw new RuntimeException('Concurrency checks require the dedicated local test database.');
}
if (! extension_loaded('pcntl')) {
    throw new RuntimeException('pcntl is required.');
}
(new RoleSeeder)->run();
$author = User::factory()->create();
$editor = User::factory()->create();
$author->roles()->attach(Role::where('code', 'author')->firstOrFail());
$editor->roles()->attach(Role::where('code', 'editor')->firstOrFail());
$workflow = new EditorialWorkflow;
$text = ['type' => 'memory', 'title' => 'Ensaio concorrente '.bin2hex(random_bytes(6)), 'body' => 'Texto de teste.'];
$publication = $workflow->create($author, $text);
$slug = 'concorrencia-'.bin2hex(random_bytes(8));

/** @return list<int> */
function workers(int $count, Closure $job): array
{
    DB::purge();
    $start = microtime(true) + 0.3;
    $pids = [];
    for ($i = 0; $i < $count; $i++) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Cannot fork');
        }
        if ($pid === 0) {
            DB::purge();
            while (microtime(true) < $start) {
                usleep(1000);
            }
            try {
                $status = $job($i);
            } catch (Throwable $e) {
                fwrite(STDERR, get_class($e).': '.$e->getMessage().PHP_EOL);
                $status = 1;
            }
            DB::disconnect();
            exit($status);
        }
        $pids[] = $pid;
    }
    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $statuses[] = pcntl_wexitstatus($status);
    }
    DB::purge();
    sort($statuses);

    return $statuses;
}
function ensure(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS: '.$message.PHP_EOL;
}

try {
    $statuses = workers(4, function () use ($workflow, $author, $publication, $text): int {
        $workflow->createRevision($author, $publication, $text);

        return 0;
    });
    ensure($statuses === [0, 0, 0, 0], 'four independent concurrent revision writers');
    ensure($publication->revisions()->orderBy('version')->pluck('version')->all() === [1, 2, 3, 4, 5], 'unique sequential versions under publication lock');
    $statuses = workers(2, function () use ($workflow, $author, $text, $slug): int {
        try {
            $workflow->create($author, [...$text, 'slug' => $slug]);

            return 0;
        } catch (ValidationException) {
            return 2;
        }
    });
    ensure($statuses === [0, 2], 'global slug collision: one committed winner and one validation failure');
    ensure(PublicationSlug::where('slug', $slug)->count() === 1, 'only one slug reserved after concurrent creation');
    $statuses = workers(2, function (int $i) use ($workflow, $editor, $publication, $slug): int {
        $workflow->metadata($editor, $publication, ['slug' => $slug.'-'.$i]);

        return 0;
    });
    ensure($statuses === [0, 0], 'concurrent slug changes serialize successfully');
    ensure($publication->slugs()->where('is_current', true)->count() === 1 && $publication->slugs()->count() === 3, 'single current slug and preserved aliases');
    $revisions = $publication->revisions()->orderBy('version')->limit(2)->get();
    foreach ($revisions as $revision) {
        $workflow->submit($author, $revision);
        $workflow->review($editor, $revision, true, null);
    }
    $statuses = workers(2, function (int $i) use ($workflow, $editor, $publication, $revisions): int {
        $workflow->publish($editor, $publication, $revisions[$i]);

        return 0;
    });
    ensure($statuses === [0, 0], 'concurrent authorized publication swaps');
    ensure(in_array($publication->refresh()->published_revision_id, $revisions->modelKeys(), true)
        && $publication->publishedRevision->status->value === 'approved', 'final public pointer is an approved owned revision');
} finally {
    DB::purge();
    $ids = Publication::withTrashed()->where('author_id', $author->id)->pluck('id');
    DB::transaction(function () use ($ids, $author, $editor): void {
        DB::table('publications')->whereIn('id', $ids)->update(['status' => 'draft', 'published_revision_id' => null, 'published_revision_status' => null,
            'scheduled_revision_id' => null, 'scheduled_revision_status' => null, 'scheduled_for' => null]);
        $revisionIds = DB::table('publication_revisions')->whereIn('publication_id', $ids)->pluck('id');
        DB::table('revision_sources')->whereIn('publication_revision_id', $revisionIds)->delete();
        foreach (['audit_entries', 'publication_slugs', 'publication_tag', 'publication_region', 'publication_revisions'] as $table) {
            DB::table($table)->whereIn('publication_id', $ids)->delete();
        }
        DB::table('publications')->whereIn('id', $ids)->delete();
        DB::table('role_user')->whereIn('user_id', [$author->id, $editor->id])->delete();
        DB::table('users')->whereIn('id', [$author->id, $editor->id])->delete();
    });
}
