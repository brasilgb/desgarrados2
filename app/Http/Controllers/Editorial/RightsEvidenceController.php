<?php

namespace App\Http\Controllers\Editorial;

use App\Actions\Editorial\ManageRightsEvidence;
use App\Http\Controllers\Controller;
use App\Models\PublicationRevision;
use App\Models\RevisionRightsEvidence;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RightsEvidenceController extends Controller
{
    public function store(Request $request, PublicationRevision $revision, ManageRightsEvidence $evidence): RedirectResponse
    {
        $data = $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        $evidence->store($this->actor($request), $revision, $data['file']);

        return back()->with('status', 'Comprovante anexado. Os anteriores permanecem no histórico.');
    }

    public function download(Request $request, RevisionRightsEvidence $evidence): StreamedResponse
    {
        Gate::authorize('view', $evidence->revision->publication);
        $filename = basename($evidence->original_name);

        return Storage::disk($evidence->disk)->download($evidence->path, $filename, [
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    private function actor(Request $request): User
    {
        return $request->user() ?? abort(403);
    }
}
