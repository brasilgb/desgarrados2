<?php

// Standalone integration check for media under real concurrency: independent processes, committed rows, MariaDB locks.

use App\Actions\Editorial\EditorialWorkflow;
use App\Actions\Editorial\MediaLibrary;
use App\Actions\Editorial\RevisionMediaManager;
use App\Media\MediaProcessor;
use App\Models\MediaAsset;
use App\Models\Publication;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = config('database.connections.'.config('database.default'));
if (config('database.default') !== 'mariadb' || $connection['database'] !== 'desgarrados2_test'
    || $connection['host'] !== '127.0.0.1' || $connection['username'] !== 'desgarrados'
    || ! empty($connection['url']) || ! empty($connection['unix_socket']) || ! empty($connection['read']) || ! empty($connection['write'])) {
    throw new RuntimeException('Media concurrency checks require the dedicated local test database.');
}
if (! extension_loaded('pcntl')) {
    throw new RuntimeException('pcntl is required.');
}
// Isolated storage and no queue: processing is exercised explicitly by the workers.
$root = sys_get_temp_dir().'/desgarrados-media-concurrency-'.bin2hex(random_bytes(6));
config(['filesystems.disks.media.root' => $root, 'queue.default' => 'null']);
Storage::forgetDisk('media');

(new RoleSeeder)->run();
$author = User::factory()->create();
$author->roles()->attach(Role::where('code', 'author')->firstOrFail());
$library = app(MediaLibrary::class);
$manager = new RevisionMediaManager;

function concurrencyJpeg(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, intdiv($width, 3), $height, (int) imagecolorallocate($image, random_int(0, 255), 80, 40));
    ob_start();
    imagejpeg($image, null, 90);

    return (string) ob_get_clean();
}
function concurrencyUpload(string $bytes): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'media');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, 'foto.jpg', 'image/jpeg', null, true);
}
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
    $bytes = concurrencyJpeg(900, 600);
    $statuses = workers(2, function () use ($library, $author, $bytes): int {
        $library->upload($author, concurrencyUpload($bytes));

        return 0;
    });
    ensure($statuses === [0, 0], 'two concurrent uploads of the same file both succeed');
    ensure(MediaAsset::where('owner_id', $author->id)->count() === 1, 'the duplicate upload reuses a single asset row');
    ensure(count(Storage::disk('media')->allFiles('originals')) === 1, 'the losing upload removed its original file');

    $asset = MediaAsset::where('owner_id', $author->id)->sole();
    $statuses = workers(3, fn (): int => (new MediaProcessor)->process(MediaAsset::findOrFail($asset->id)) ? 0 : 3);
    ensure($statuses === [0, 3, 3], 'exactly one of three concurrent workers processes a pending asset');
    ensure($asset->refresh()->processing_status->value === 'ready' && count($asset->variants) === 3, 'asset ends ready with three variants');

    $covers = [$asset];
    foreach ([concurrencyJpeg(800, 500)] as $other) {
        $covers[] = $library->upload($author, concurrencyUpload($other));
    }
    $publication = (new EditorialWorkflow)->create($author, ['type' => 'memory', 'title' => 'Concorrência de mídia '.bin2hex(random_bytes(4)), 'body' => 'Texto.']);
    $revision = $publication->revisions()->sole();
    $statuses = workers(2, function (int $i) use ($manager, $author, $revision, $covers): int {
        $manager->attach($author, $revision, $covers[$i], ['purpose' => 'cover', 'alt_text' => "Capa {$i}", 'credit' => 'Crédito']);

        return 0;
    });
    ensure($statuses === [0, 0], 'two concurrent cover choices serialize under the publication lock');
    ensure($revision->media()->where('purpose', 'cover')->count() === 1, 'the revision ends with exactly one cover');

    $statuses = workers(2, function () use ($manager, $author, $revision, $asset): int {
        try {
            $manager->attach($author, $revision, $asset, ['purpose' => 'content', 'alt_text' => 'Conteúdo', 'credit' => 'Crédito']);

            return 0;
        } catch (ValidationException) {
            return 2;
        }
    });
    ensure($statuses === [0, 2], 'concurrent duplicate content association: one winner and one validation failure');
    ensure($revision->media()->where('purpose', 'content')->count() === 1, 'no duplicate content row was created');
} finally {
    DB::purge();
    $ids = Publication::withTrashed()->where('author_id', $author->id)->pluck('id');
    $assetIds = MediaAsset::where('owner_id', $author->id)->pluck('id');
    DB::transaction(function () use ($ids, $assetIds, $author): void {
        $revisionIds = DB::table('publication_revisions')->whereIn('publication_id', $ids)->pluck('id');
        DB::table('revision_media')->whereIn('publication_revision_id', $revisionIds)->delete();
        DB::table('audit_entries')->whereIn('publication_id', $ids)->orWhereIn('media_asset_id', $assetIds)
            ->orWhere(fn ($q) => $q->where('actor_id', $author->id)->where('action', 'like', 'media\_%'))->delete();
        DB::table('revision_sources')->whereIn('publication_revision_id', $revisionIds)->delete();
        foreach (['publication_slugs', 'publication_tag', 'publication_region', 'publication_revisions'] as $table) {
            DB::table($table)->whereIn('publication_id', $ids)->delete();
        }
        DB::table('publications')->whereIn('id', $ids)->delete();
        DB::table('media_assets')->whereIn('id', $assetIds)->delete();
        DB::table('role_user')->where('user_id', $author->id)->delete();
        DB::table('users')->where('id', $author->id)->delete();
    });
    File::deleteDirectory($root);
}
