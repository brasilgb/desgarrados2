<?php

namespace App\Http\Controllers\Editorial;

use App\Actions\Editorial\RevisionMediaManager;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\Publication;
use App\Models\RevisionMedia;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RevisionMediaController extends Controller
{
    public function store(Request $request, Publication $publication, RevisionMediaManager $manager): RedirectResponse
    {
        $data = $request->validate(['revision_id' => ['required', 'integer'], 'media' => ['required', 'string', 'max:36']]);
        $revision = $publication->revisions()->whereKey((int) $data['revision_id'])->firstOrFail();
        $asset = MediaAsset::where('uuid', $data['media'])->firstOrFail();
        $manager->attach($this->actor($request), $revision, $asset, $request->only(['purpose', 'alt_text', 'caption', 'credit']));

        return back();
    }

    public function update(Request $request, RevisionMedia $item, RevisionMediaManager $manager): RedirectResponse
    {
        $manager->update($this->actor($request), $item, $request->only(['alt_text', 'caption', 'credit']));

        return back();
    }

    public function destroy(Request $request, RevisionMedia $item, RevisionMediaManager $manager): RedirectResponse
    {
        $manager->detach($this->actor($request), $item);

        return back();
    }

    public function move(Request $request, RevisionMedia $item, RevisionMediaManager $manager): RedirectResponse
    {
        $data = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);
        $manager->move($this->actor($request), $item, $data['direction']);

        return back();
    }

    private function actor(Request $request): User
    {
        return $request->user() ?? abort(403);
    }
}
