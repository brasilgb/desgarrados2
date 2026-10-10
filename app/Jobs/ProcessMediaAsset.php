<?php

namespace App\Jobs;

use App\Media\MediaProcessor;
use App\Models\MediaAsset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMediaAsset implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public int $mediaAssetId)
    {
        $this->afterCommit();
    }

    public function handle(MediaProcessor $processor): void
    {
        // A 40-megapixel image needs ~160 MB decoded, plus the resampled canvases.
        ini_set('memory_limit', '768M');
        $asset = MediaAsset::find($this->mediaAssetId);
        if ($asset) {
            $processor->process($asset);
        }
    }
}
