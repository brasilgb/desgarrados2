<?php

namespace App\Http\Controllers\Editorial;

use App\Enums\MediaProcessingStatus;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\Publication;
use App\Models\RevisionMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves derivatives. Public only while the image belongs to the published revision of a publicly visible
 * publication and is processed, active and with usage rights; otherwise only owners and editors (no cache).
 * Unknown, private and withdrawn images all answer 404, so ids and paths reveal nothing.
 */
class MediaFileController extends Controller
{
    public function show(Request $request, string $uuid, string $variant): BinaryFileResponse
    {
        abort_unless(in_array($variant, array_keys(config('media.variants')), true), 404);
        $asset = MediaAsset::where('uuid', $uuid)->first();
        $path = $asset?->variants[$variant]['path'] ?? null;
        abort_if(! $asset || $asset->processing_status !== MediaProcessingStatus::Ready || ! $path, 404);
        $public = $this->isPublic($asset);
        abort_unless($public || ($request->user() && Gate::forUser($request->user())->allows('view', $asset)), 404);
        $disk = Storage::disk(config('media.disk'));
        abort_unless($disk->exists($path), 404);
        // BinaryFileResponse defaults to "public"; private previews must never land in a shared cache.
        $response = new BinaryFileResponse($disk->path($path), 200, ['Content-Type' => 'image/webp', 'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="'.$variant.'.webp"'], $public, null, true);
        if ($public) {
            $response->setMaxAge((int) config('media.public_max_age'));
        } else {
            $response->setPrivate();
            $response->headers->addCacheControlDirective('no-store');
        }

        return $response;
    }

    /** Originals never leave through a public link: authenticated owners and editors only, as a download. */
    public function original(Request $request, MediaAsset $media): BinaryFileResponse
    {
        Gate::authorize('view', $media);
        $disk = Storage::disk(config('media.disk'));
        abort_unless($disk->exists($media->original_path), 404);
        $extension = config('media.mimes')[$media->mime];

        $response = new BinaryFileResponse($disk->path($media->original_path), 200, ['Content-Type' => $media->mime,
            'X-Content-Type-Options' => 'nosniff', 'X-Robots-Tag' => 'noindex, nofollow'], false, 'attachment');
        $response->setContentDisposition('attachment', "original-{$media->uuid}.{$extension}");
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    private function isPublic(MediaAsset $asset): bool
    {
        return $asset->isPublishable() && RevisionMedia::where('media_asset_id', $asset->id)
            ->whereIn('publication_revision_id', Publication::publiclyVisible()->select('published_revision_id'))->exists();
    }
}
