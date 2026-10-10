<?php

namespace App\Models;

use App\Enums\MediaPurpose;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $publication_revision_id
 * @property int $media_asset_id
 * @property MediaPurpose $purpose
 * @property int $position
 * @property string $alt_text
 * @property string|null $caption
 * @property string $credit
 * @property-read MediaAsset $asset
 * @property-read PublicationRevision $revision
 */
class RevisionMedia extends Model
{
    protected $table = 'revision_media';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['purpose' => MediaPurpose::class];
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    /** @return BelongsTo<PublicationRevision, $this> */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(PublicationRevision::class, 'publication_revision_id');
    }
}
