<?php

namespace App\Console\Commands;

use App\Enums\MediaProcessingStatus;
use App\Media\MediaProcessor;
use App\Models\MediaAsset;
use Illuminate\Console\Command;

class ProcessMedia extends Command
{
    protected $signature = 'media:process {uuid? : Processa uma imagem específica} {--force : Reprocessa mesmo se pronta ou em andamento} {--stale : Inclui pendentes há mais de 10 min e processamentos travados há mais de 15 min}';

    protected $description = 'Gera os derivados das imagens editoriais pendentes (idempotente).';

    public function handle(MediaProcessor $processor): int
    {
        ini_set('memory_limit', '768M');
        if ($uuid = $this->argument('uuid')) {
            $asset = MediaAsset::where('uuid', $uuid)->first();
            if (! $asset) {
                $this->error('Imagem não encontrada.');

                return self::FAILURE;
            }
            $done = $processor->process($asset, (bool) $this->option('force'));
            $this->info($done ? 'Processada.' : 'Nada a fazer: imagem já processada ou em processamento.');

            return $asset->refresh()->processing_status === MediaProcessingStatus::Failed ? self::FAILURE : self::SUCCESS;
        }
        $query = MediaAsset::where(function ($q) {
            $q->where('processing_status', MediaProcessingStatus::Pending->value);
            if ($this->option('stale')) {
                $q->orWhere(fn ($s) => $s->where('processing_status', MediaProcessingStatus::Processing->value)->where('updated_at', '<', now()->subMinutes(15)));
            }
        });
        if ($this->option('stale')) {
            $query->where('updated_at', '<', now()->subMinutes(10));
        }
        $processed = 0;
        foreach ($query->lazyById(20) as $asset) {
            $stuck = $asset->processing_status === MediaProcessingStatus::Processing;
            $processed += $processor->process($asset, $stuck) ? 1 : 0;
        }
        $this->info("Processadas: {$processed}.");

        return self::SUCCESS;
    }
}
