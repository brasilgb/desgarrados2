<?php

namespace App\Http\Controllers\Editorial;

use App\Enums\MediaPurpose;
use App\Http\Controllers\Controller;
use App\Media\MediaPresenter;
use App\Models\Publication;
use App\Models\PublicationSlug;
use App\Models\RevisionMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPublicationController extends Controller
{
    public function index(Request $request, ?string $section = null): Response
    {
        abort_unless($section === null || in_array($section, ['historias', 'causos', 'cultura-e-tradicoes'], true), 404);
        $filters = $request->validate(['municipality' => ['nullable', 'integer', 'exists:municipalities,id'], 'region' => ['nullable', 'integer', 'exists:regions,id']]);
        $query = Publication::query()->publiclyVisible()->with(['publishedRevision.media.asset', 'currentSlug', 'category', 'municipality', 'tags', 'regions']);
        if ($section) {
            $query->whereIn('type', match ($section) {
                'causos' => ['causo'], 'cultura-e-tradicoes' => ['cultural_history'],
                'historias' => ['article', 'news', 'chronicle', 'personal_story', 'migration_story', 'memory'],
            });
        }
        if (! empty($filters['municipality'])) {
            $query->where('municipality_id', $filters['municipality']);
        }
        if (! empty($filters['region'])) {
            $query->whereHas('regions', fn ($q) => $q->where('regions.id', $filters['region']));
        }
        $sections = [];
        foreach (['historias' => ['article', 'news', 'chronicle', 'personal_story', 'migration_story', 'memory'], 'causos' => ['causo'], 'cultura-e-tradicoes' => ['cultural_history']] as $slug => $types) {
            if (Publication::publiclyVisible()->whereIn('type', $types)->exists()) {
                $sections[] = $slug;
            }
        }

        return Inertia::render('editorial/feed', ['publications' => $query->orderByDesc('published_at')->orderByDesc('id')->paginate(12)->withQueryString()
            ->through(fn (Publication $p) => $this->project($p, false)), 'sections' => $sections, 'section' => $section, 'filters' => $filters]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        $alias = PublicationSlug::where('slug', $slug)->firstOrFail();
        // Check visibility before returning even a redirect or canonical URL.
        $p = Publication::publiclyVisible()->with(['publishedRevision.sources', 'publishedRevision.media.asset', 'currentSlug', 'category', 'municipality', 'tags', 'regions'])->where('id', $alias->getAttribute('publication_id'))->firstOrFail();
        abort_unless($p->currentSlug !== null, 404);
        if (! $alias->is_current) {
            return to_route('stories.show', ['slug' => $p->currentSlug->slug], 301);
        }

        $publication = $this->project($p, true);
        $image = $publication['cover']['image'] ?? null;

        return Inertia::render('editorial/publication', ['publication' => $publication, 'canonical' => route('stories.show', ['slug' => $p->currentSlug->slug]),
            'ogImage' => $image ? ['url' => url($image['src']), 'width' => $image['width'], 'height' => $image['height']] : null]);
    }

    /** Explicit allowlist: no author account, reviewer, note or administrative data.
     * @return array<string, mixed>
     */
    private function project(Publication $p, bool $body): array
    {
        $revision = $p->publishedRevision ?? abort(404);

        // Only processed, active images with usage rights from the published revision itself.
        $media = $revision->media->filter(fn (RevisionMedia $row) => $row->asset->isPublishable());
        $cover = $media->first(fn (RevisionMedia $row) => $row->purpose === MediaPurpose::Cover);

        return ['slug' => $p->currentSlug?->slug, 'type' => $p->type->value, 'title' => $revision->title, 'summary' => $revision->summary,
            'cover' => $cover ? MediaPresenter::usage($cover, $body ? 'cover' : 'card') : null,
            'byline' => $revision->public_byline, 'published_at' => $p->published_at?->toIso8601String(),
            'category' => $p->category?->only(['name', 'slug']), 'municipality' => $p->municipality?->only(['id', 'name', 'slug']),
            'tags' => $p->tags->map->only(['name', 'slug']), 'regions' => $p->regions->map->only(['id', 'name', 'slug']),
            ...($body ? ['body' => $revision->body, 'sources' => $revision->sources->map->only(['title', 'url', 'attribution', 'accessed_at']),
                'images' => $media->filter(fn (RevisionMedia $row) => $row->purpose === MediaPurpose::Content)->map(fn (RevisionMedia $row) => MediaPresenter::usage($row, 'content'))->values()] : [])];
    }
}
