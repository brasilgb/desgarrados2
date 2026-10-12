<?php

namespace App\Http\Controllers;

use App\Editorial\PublicEditorialPresenter;
use App\Models\Publication;
use App\Models\PublicationFeature;
use App\Models\Region;
use Inertia\Inertia;
use Inertia\Response;

class RegionalPageController extends Controller
{
    public function show(Region $region, PublicEditorialPresenter $presenter): Response
    {
        abort_unless($region->active, 404);
        $base = Publication::query()->publiclyVisible()->whereHas('regions', fn ($query) => $query->whereKey($region->id));
        $featuredIds = PublicationFeature::query()->active()->where('region_id', $region->id)->orderBy('position')->pluck('publication_id');
        $featured = (clone $base)->whereIn('id', $featuredIds)->with(PublicEditorialPresenter::relations())->get()
            ->filter(fn (Publication $publication) => $presenter->eligibleForFeature($publication))
            ->sortBy(fn (Publication $publication) => $featuredIds->search($publication->id))->values()->map(fn (Publication $publication) => $presenter->story($publication));

        return Inertia::render('territory/region', [
            'region' => $region->only(['id', 'name', 'slug', 'kind', 'description']),
            'municipalities' => $region->municipalities()->where('municipalities.active', true)->with('state:id,name,slug,abbreviation')
                ->orderBy('municipalities.name')->paginate(24, ['municipalities.id', 'municipalities.state_id', 'municipalities.name', 'municipalities.slug'], 'municipios'),
            'featured' => $featured,
            'ogImage' => $featured->first()['cover']['image'] ?? null,
            'recent' => $base->with(PublicEditorialPresenter::relations())->orderByDesc('published_at')->orderByDesc('id')->paginate(12)
                ->through(fn (Publication $publication) => $presenter->story($publication)),
            'canonical' => route('regions.show', $region),
        ]);
    }
}
