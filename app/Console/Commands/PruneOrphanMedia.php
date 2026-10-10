<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Removes files with no media_assets row (for example, a crash between writing the file and the row). */
class PruneOrphanMedia extends Command
{
    protected $signature = 'media:prune-orphans {--force : Apaga de fato; sem a opção, apenas lista}';

    protected $description = 'Lista ou remove arquivos de mídia sem registro no banco.';

    public function handle(): int
    {
        $disk = Storage::disk(config('media.disk'));
        $orphans = [];
        foreach ($disk->allFiles('originals') as $file) {
            $uuid = pathinfo($file, PATHINFO_FILENAME);
            if (! MediaAsset::where('uuid', $uuid)->where('original_path', $file)->exists()) {
                $orphans[] = $file;
            }
        }
        foreach ($disk->directories('derivatives') as $prefix) {
            foreach ($disk->directories($prefix) as $directory) {
                if (! MediaAsset::where('uuid', basename($directory))->exists()) {
                    $orphans[] = $directory.'/';
                }
            }
        }
        foreach ($orphans as $orphan) {
            $this->line(($this->option('force') ? 'Removido: ' : 'Órfão: ').$orphan);
            if ($this->option('force')) {
                str_ends_with($orphan, '/') ? $disk->deleteDirectory(rtrim($orphan, '/')) : $disk->delete($orphan);
            }
        }
        $this->info(count($orphans).' item(ns) órfão(s)'.($this->option('force') ? ' removido(s).' : '. Use --force para remover.'));

        return self::SUCCESS;
    }
}
