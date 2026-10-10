<?php

namespace App\Console\Commands;

use App\Actions\Editorial\EditorialWorkflow;
use App\Models\AuditEntry;
use App\Models\Publication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishScheduledPublications extends Command
{
    public const HEARTBEAT_KEY = 'editorial:publish-due:last-run';

    protected $signature = 'editorial:publish-due {--batch=100 : Publicações carregadas por lote}';

    protected $description = 'Publica revisões aprovadas cujo agendamento venceu.';

    public function handle(EditorialWorkflow $workflow): int
    {
        $published = 0;
        $failed = 0;
        $batch = max(1, min(500, (int) $this->option('batch')));
        foreach (Publication::where('scheduled_for', '<=', now())->lazyById($batch) as $publication) {
            try {
                if ($workflow->publishDue($publication)) {
                    $published++;
                }
            } catch (Throwable $exception) {
                // Uma publicação com defeito não pode bloquear o restante do lote; a transação dela já foi desfeita.
                $failed++;
                Log::error('Falha ao publicar agendamento editorial.', ['publication_id' => $publication->id, 'exception' => $exception::class]);
                AuditEntry::create(['publication_id' => $publication->id, 'action' => 'schedule_failed',
                    'changes' => ['status' => $publication->status->value, 'exception' => class_basename($exception)]]);
            }
        }
        Cache::forever(self::HEARTBEAT_KEY, ['at' => now()->toIso8601String(), 'published' => $published, 'failed' => $failed]);
        $this->info("Publicadas: {$published}. Falhas: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
