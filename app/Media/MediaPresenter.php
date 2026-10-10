<?php

namespace App\Media;

use App\Models\MediaAsset;
use App\Models\RevisionMedia;

/** Shapes derivative URLs for the pages. URLs carry the random uuid; access is decided per request. */
class MediaPresenter
{
    /** @return array{src: string, width: int, height: int, srcset: string}|null */
    public static function image(MediaAsset $asset, string $variant): ?array
    {
        $variants = $asset->variants ?? [];
        if (! isset($variants[$variant])) {
            return null;
        }
        $srcset = collect($variants)->sortBy('width')->unique('width')
            ->map(fn (array $v, string $name) => self::url($asset, $name).' '.$v['width'].'w')->implode(', ');

        return ['src' => self::url($asset, $variant), 'width' => $variants[$variant]['width'],
            'height' => $variants[$variant]['height'], 'srcset' => $srcset];
    }

    /** @return array<string, mixed> */
    public static function usage(RevisionMedia $row, string $variant): array
    {
        return ['purpose' => $row->purpose->value, 'alt' => $row->alt_text, 'caption' => $row->caption,
            'credit' => $row->credit, 'image' => self::image($row->asset, $variant)];
    }

    public static function url(MediaAsset $asset, string $variant): string
    {
        return route('media.show', ['uuid' => $asset->uuid, 'variant' => $variant], false);
    }
}
