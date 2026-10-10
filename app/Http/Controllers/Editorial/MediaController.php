<?php

namespace App\Http\Controllers\Editorial;

use App\Actions\Editorial\MediaLibrary;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function store(Request $request, MediaLibrary $library): RedirectResponse
    {
        $asset = $library->upload($this->actor($request), $request->file('file'), $request->only(['rights_type', 'rights_holder', 'license', 'rights_notes']));

        return back()->with('media_uploaded', $asset->uuid);
    }

    public function rights(Request $request, MediaAsset $media, MediaLibrary $library): RedirectResponse
    {
        $library->updateRights($this->actor($request), $media, $request->only(['rights_type', 'rights_holder', 'license', 'rights_notes']));

        return back();
    }

    public function block(Request $request, MediaAsset $media, MediaLibrary $library): RedirectResponse
    {
        $library->block($this->actor($request), $media, $request->string('reason')->toString() ?: null);

        return back();
    }

    public function unblock(Request $request, MediaAsset $media, MediaLibrary $library): RedirectResponse
    {
        $library->unblock($this->actor($request), $media);

        return back();
    }

    public function destroy(Request $request, MediaAsset $media, MediaLibrary $library): RedirectResponse
    {
        $library->delete($this->actor($request), $media);

        return back();
    }

    private function actor(Request $request): User
    {
        return $request->user() ?? abort(403);
    }
}
