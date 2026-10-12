<?php

namespace App\Editorial;

use App\Enums\MediaPurpose;
use App\Media\MediaPresenter;
use App\Models\Publication;
use App\Models\RevisionMedia;

class PublicEditorialPresenter
{
    public function eligibleForFeature(Publication $publication): bool
    {
        return $publication->publishedRevision !== null
            && $publication->publishedRevision->media->every(fn (RevisionMedia $usage) => $usage->asset->isPublishable());
    }

    /** @return array<string, mixed> */
    public function story(Publication $publication, bool $body = false): array
    {
        $revision = $publication->publishedRevision ?? abort(404);
        $media = $revision->media->filter(fn (RevisionMedia $row) => $row->asset->isPublishable());
        $cover = $media->first(fn (RevisionMedia $row) => $row->purpose === MediaPurpose::Cover);

        return ['slug' => $publication->currentSlug?->slug, 'type' => $publication->type->value,
            'title' => $revision->title, 'summary' => $revision->summary,
            'cover' => $cover ? MediaPresenter::usage($cover, $body ? 'cover' : 'card') : null,
            'byline' => $revision->public_byline, 'published_at' => $publication->published_at?->toIso8601String(),
            'category' => $publication->category?->only(['name', 'slug']),
            'municipality' => $publication->municipality?->only(['id', 'name', 'slug']),
            'tags' => $publication->tags->map->only(['name', 'slug']),
            'regions' => $publication->regions->map->only(['id', 'name', 'slug']),
            ...($body ? ['body' => $revision->body,
                'sources' => $revision->sources->map->only(['title', 'url', 'attribution', 'accessed_at']),
                'images' => $media->filter(fn (RevisionMedia $row) => $row->purpose === MediaPurpose::Content)
                    ->map(fn (RevisionMedia $row) => MediaPresenter::usage($row, 'content'))->values()] : [])];
    }

    /** @return list<string> */
    public static function relations(bool $body = false): array
    {
        return [$body ? 'publishedRevision.sources' : 'publishedRevision', 'publishedRevision.media.asset',
            'currentSlug', 'category', 'municipality', 'tags', 'regions'];
    }
}
