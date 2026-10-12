<?php

namespace App\Http\Controllers;

use App\Editorial\PublicEditorialPresenter;
use App\Models\Municipality;
use App\Models\Publication;
use App\Models\PublicationFeature;
use App\Models\Region;
use App\Models\State;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TerritoryController extends Controller
{
    public function home(PublicEditorialPresenter $presenter): Response
    {
        $relations = PublicEditorialPresenter::relations();
        $globalFeatureIds = PublicationFeature::query()->active()->whereNull('region_id')->orderBy('position')->pluck('publication_id');
        $features = Publication::query()->publiclyVisible()->whereIn('id', $globalFeatureIds)->with($relations)->get()
            ->filter(fn (Publication $publication) => $presenter->eligibleForFeature($publication))
            ->sortBy(fn (Publication $publication) => $globalFeatureIds->search($publication->id))->values();
        $featureStories = $features->map(fn (Publication $publication) => $presenter->story($publication));
        $recent = fn (array $types, int $limit = 3) => Publication::query()->publiclyVisible()->whereIn('type', $types)->with($relations)
            ->orderByDesc('published_at')->orderByDesc('id')->limit($limit)->get()->map(fn (Publication $publication) => $presenter->story($publication));

        return Inertia::render('welcome', [
            'featuredState' => State::where('abbreviation', 'RS')->where('active', true)->first(['id', 'name', 'slug', 'abbreviation']),
            'stateCount' => State::where('active', true)->count(),
            'municipalityCount' => Municipality::where('active', true)->count(),
            'canonical' => route('home'),
            'featured' => $featureStories,
            'ogImage' => $featureStories->first()['cover']['image'] ?? null,
            'recent' => $recent(['article', 'news', 'chronicle', 'personal_story', 'migration_story', 'memory'], 6),
            'causos' => $recent(['causo']),
            'culture' => $recent(['cultural_history']),
            'regions' => Region::query()->where('active', true)->whereHas('publications', fn ($query) => $query->publiclyVisible())
                ->withCount(['municipalities' => fn ($query) => $query->where('municipalities.active', true),
                    'publications' => fn ($query) => $query->publiclyVisible()])
                ->orderByRaw("CASE WHEN slug LIKE '%rio-grande%' THEN 0 ELSE 1 END")->orderByDesc('publications_count')->limit(6)
                ->get(['id', 'name', 'slug', 'kind', 'description']),
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('territory/states', ['states' => $this->states()]);
    }

    public function state(Request $request, State $state): Response
    {
        abort_unless($state->active, 404);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return Inertia::render('territory/state', [
            'state' => $state->only(['id', 'name', 'slug', 'abbreviation']),
            'filters' => ['q' => $filters['q'] ?? ''],
            'municipalities' => $state->municipalities()->where('active', true)
                ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
                ->orderBy('name')->orderBy('id')->paginate(24, ['id', 'name', 'slug', 'ibge_code'])->withQueryString(),
        ]);
    }

    public function municipality(State $state, Municipality $municipality): Response
    {
        abort_unless($state->active && $municipality->active && $municipality->state_id === $state->id, 404);

        return Inertia::render('territory/municipality', [
            'state' => $state->only(['id', 'name', 'slug', 'abbreviation']),
            'municipality' => $municipality->only(['id', 'name', 'slug', 'ibge_code']),
            'regions' => $municipality->regions()->where('active', true)->orderBy('name')->get(['regions.id', 'name', 'slug', 'kind', 'description']),
        ]);
    }

    public function myLand(Request $request): Response
    {
        $filters = $request->validate(['state' => ['nullable', 'string', 'max:100']]);

        return Inertia::render('territory/my-land', [
            'states' => $this->states(),
            'selectedState' => $filters['state'] ?? null,
        ]);
    }

    /** @return Collection<int, State> */
    private function states(): Collection
    {
        return State::where('active', true)->withCount(['municipalities' => fn ($query) => $query->where('active', true)])
            ->orderByRaw('CASE WHEN abbreviation = ? THEN 0 ELSE 1 END', ['RS'])->orderBy('name')->get(['id', 'name', 'slug', 'abbreviation']);
    }
}
