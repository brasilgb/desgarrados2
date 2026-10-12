<?php

namespace App\Http\Controllers\Editorial;

use App\Actions\Editorial\ManagePublicationFeatures;
use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Models\PublicationFeature;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PublicationFeatureController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage', Publication::class);
        $features = PublicationFeature::query()->with(['publication.publishedRevision:id,title', 'region:id,name', 'curator:id,name'])
            ->orderByDesc('starts_at')->paginate(20);
        $publications = Publication::query()->publiclyVisible()->with('publishedRevision:id,title')
            ->orderByDesc('published_at')->limit(100)->get()->map(fn (Publication $publication) => [
                'id' => $publication->id, 'name' => $publication->publishedRevision?->title,
            ]);

        return Inertia::render('editorial/features', ['features' => $features, 'publications' => $publications,
            'regions' => Region::query()->where('active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    public function save(Request $request, ManagePublicationFeatures $manager, ?PublicationFeature $feature = null): RedirectResponse
    {
        $manager->save($request->user() ?? abort(403), $request->all(), $feature);

        return back();
    }

    public function delete(Request $request, PublicationFeature $feature, ManagePublicationFeatures $manager): RedirectResponse
    {
        $manager->delete($request->user() ?? abort(403), $feature);

        return back();
    }
}
