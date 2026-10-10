<?php

namespace App\Contracts;

use App\Models\PublicationRevision;

/** Future raster pipeline: assets belong to immutable revisions, private until approval.
 * Implementations must enforce reprocessing, EXIF removal and documented usage rights.
 */
interface RevisionMediaProvider
{
    /** @return list<array{url: string, alt: string, credit: string, width: int, height: int}> */
    public function approvedFor(PublicationRevision $revision): array;
}
