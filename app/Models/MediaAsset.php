<?php

namespace App\Models;

use App\Enums\MediaProcessingStatus;
use App\Enums\MediaRights;
use App\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $owner_id
 * @property string $sha256
 * @property string $original_path
 * @property string|null $original_name
 * @property string $mime
 * @property int $width
 * @property int $height
 * @property int $size_bytes
 * @property MediaProcessingStatus $processing_status
 * @property string|null $processing_error
 * @property array<string, array{path: string, width: int, height: int, size: int}>|null $variants
 * @property Carbon|null $processed_at
 * @property MediaStatus $status
 * @property string|null $blocked_reason
 * @property MediaRights|null $rights_type
 * @property string|null $rights_holder
 * @property string|null $license
 * @property string|null $rights_notes
 * @property Carbon $created_at
 * @property-read User|null $owner
 */
class MediaAsset extends Model
{
    // Paths, status and rights are assigned only by the media Actions.
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['processing_status' => MediaProcessingStatus::class, 'status' => MediaStatus::class,
            'rights_type' => MediaRights::class, 'variants' => 'array', 'processed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<RevisionMedia, $this> */
    public function usages(): HasMany
    {
        return $this->hasMany(RevisionMedia::class);
    }

    public function isPublishable(): bool
    {
        return $this->processing_status === MediaProcessingStatus::Ready && $this->status === MediaStatus::Active
            && $this->hasRights();
    }

    public function hasRights(): bool
    {
        return $this->rights_type !== null && trim((string) $this->rights_holder) !== '';
    }
}
