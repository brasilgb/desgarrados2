<?php

namespace App\Media;

use App\Enums\MediaProcessingStatus;
use App\Models\MediaAsset;
use GdImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Generates WebP derivatives with GD. Re-encoding drops every original metadata block (EXIF, GPS, XMP);
 * the JPEG orientation is applied to the pixels first. Output paths are deterministic, so a retry overwrites.
 */
class MediaProcessor
{
    /**
     * Claims the asset atomically; returns false when another run already owns it or it is done.
     * With $force, stuck or finished assets are reprocessed.
     */
    public function process(MediaAsset $asset, bool $force = false): bool
    {
        $claimable = $force
            ? [MediaProcessingStatus::Pending, MediaProcessingStatus::Failed, MediaProcessingStatus::Processing, MediaProcessingStatus::Ready]
            : [MediaProcessingStatus::Pending, MediaProcessingStatus::Failed];
        // Row lock instead of affected-row counting: MariaDB reports 0 when a stuck row already holds the same values.
        $claimed = DB::transaction(function () use ($asset, $claimable): ?MediaAsset {
            $row = MediaAsset::query()->lockForUpdate()->find($asset->id);
            if (! $row || ! in_array($row->processing_status, $claimable, true)) {
                return null;
            }
            $row->forceFill(['processing_status' => MediaProcessingStatus::Processing, 'processing_error' => null])->save();

            return $row;
        });
        if (! $claimed) {
            return false;
        }
        $asset = $claimed;
        try {
            $variants = $this->derivatives($asset);
            $asset->forceFill(['variants' => $variants, 'processed_at' => now(),
                'processing_status' => MediaProcessingStatus::Ready])->save();

            return true;
        } catch (Throwable $exception) {
            Log::warning('Falha no processamento de mídia.', ['media' => $asset->uuid, 'exception' => $exception::class]);
            $asset->forceFill(['processing_status' => MediaProcessingStatus::Failed,
                'processing_error' => 'Não foi possível processar a imagem.'])->save();

            return false;
        }
    }

    /** @return array<string, array{path: string, width: int, height: int, size: int}> */
    private function derivatives(MediaAsset $asset): array
    {
        $disk = Storage::disk(config('media.disk'));
        $source = imagecreatefromstring($disk->get($asset->original_path));
        if (! $source instanceof GdImage) {
            throw new RuntimeException('Imagem não decodificável.');
        }
        $source = $this->orient($source, $disk->path($asset->original_path), $asset->mime);
        [$width, $height] = [imagesx($source), imagesy($source)];
        $variants = [];
        foreach (config('media.variants') as $name => $maxWidth) {
            $targetWidth = max(1, min($width, (int) $maxWidth));
            $targetHeight = max(1, (int) round($height * $targetWidth / $width));
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            ob_start();
            $encoded = imagewebp($canvas, null, (int) config('media.webp_quality'));
            $bytes = (string) ob_get_clean();
            if (! $encoded || $bytes === '') {
                throw new RuntimeException('Falha ao codificar WebP.');
            }
            $path = self::derivativeDirectory($asset->uuid)."/{$name}.webp";
            $disk->put($path, $bytes);
            $variants[$name] = ['path' => $path, 'width' => $targetWidth, 'height' => $targetHeight, 'size' => strlen($bytes)];
        }

        return $variants;
    }

    private function orient(GdImage $image, string $path, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }
        $orientation = (int) ((@exif_read_data($path) ?: [])['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            // Mirrored orientations are rare in cameras; flip and rotate to match the intended view.
            imageflip($image, IMG_FLIP_HORIZONTAL);
            $rotated = match ($orientation) {
                4 => imagerotate($image, 180, 0),
                5 => imagerotate($image, 90, 0),
                7 => imagerotate($image, -90, 0),
                default => $image,
            };
        }

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    public static function originalPath(string $uuid, string $extension): string
    {
        return 'originals/'.substr($uuid, 0, 2)."/{$uuid}.{$extension}";
    }

    public static function derivativeDirectory(string $uuid): string
    {
        return 'derivatives/'.substr($uuid, 0, 2)."/{$uuid}";
    }
}
