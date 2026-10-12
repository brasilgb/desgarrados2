<?php

namespace App\Http\Controllers\Editorial;

use App\Editorial\PublicEditorialPresenter;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Municipality;
use App\Models\Publication;
use App\Models\PublicationSlug;
use App\Models\Region;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPublicationController extends Controller
{
    public function index(Request $request, PublicEditorialPresenter $presenter, ?string $section = null): Response
    {
        abort_unless($section === null || in_array($section, ['historias', 'causos', 'cultura-e-tradicoes'], true), 404);
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:100', 'exists:categories,slug'],
            'tag' => ['nullable', 'string', 'max:100', 'exists:tags,slug'],
            'municipality' => ['nullable', 'integer', 'exists:municipalities,id'],
            'region' => ['nullable', 'integer', 'exists:regions,id'],
        ]);
        $query = Publication::query()->publiclyVisible()->with(PublicEditorialPresenter::relations());
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
        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('categories.slug', $filters['category']));
        }
        if (! empty($filters['tag'])) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.slug', $filters['tag']));
        }
        $sections = [];
        foreach (['historias' => ['article', 'news', 'chronicle', 'personal_story', 'migration_story', 'memory'], 'causos' => ['causo'], 'cultura-e-tradicoes' => ['cultural_history']] as $slug => $types) {
            if (Publication::publiclyVisible()->whereIn('type', $types)->exists()) {
                $sections[] = $slug;
            }
        }

        $publications = $query->orderByDesc('published_at')->orderByDesc('id')->paginate(12)->withQueryString()
            ->through(fn (Publication $p) => $presenter->story($p));

        return Inertia::render('editorial/feed', [
            'publications' => $publications,
            'sections' => $sections,
            'section' => $section,
            'filters' => $filters,
            'categories' => Category::query()->whereHas('publications', fn ($q) => $q->publiclyVisible())
                ->orderBy('name')->get(['id', 'name', 'slug']),
            'tags' => Tag::query()->whereHas('publications', fn ($q) => $q->publiclyVisible())
                ->orderBy('name')->get(['id', 'name', 'slug']),
            'municipalities' => Municipality::query()->where('active', true)
                ->whereHas('publications', fn ($q) => $q->publiclyVisible())
                ->with('state:id,abbreviation')->orderBy('name')->get(['id', 'state_id', 'name']),
            'regions' => Region::query()->where('active', true)
                ->whereHas('publications', fn ($q) => $q->publiclyVisible())
                ->orderBy('name')->get(['id', 'name']),
            'canonical' => $section ? route('stories.section', $section) : route('stories.index'),
            'ogImage' => $publications->first()['cover']['image'] ?? null]);
    }

    public function show(string $slug, PublicEditorialPresenter $presenter): Response|RedirectResponse
    {
        $alias = PublicationSlug::where('slug', $slug)->firstOrFail();
        // Check visibility before returning even a redirect or canonical URL.
        $p = Publication::publiclyVisible()->with(PublicEditorialPresenter::relations(true))->where('id', $alias->getAttribute('publication_id'))->firstOrFail();
        abort_unless($p->currentSlug !== null, 404);
        if (! $alias->is_current) {
            return to_route('stories.show', ['slug' => $p->currentSlug->slug], 301);
        }

        $publication = $presenter->story($p, true);
        $image = $publication['cover']['image'] ?? null;

        return Inertia::render('editorial/publication', ['publication' => $publication, 'canonical' => route('stories.show', ['slug' => $p->currentSlug->slug]),
            'ogImage' => $image ? ['url' => url($image['src']), 'width' => $image['width'], 'height' => $image['height']] : null]);
    }
}
