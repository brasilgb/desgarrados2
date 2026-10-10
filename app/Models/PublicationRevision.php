<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $publication_id
 * @property int $version
 * @property int|null $editor_id
 * @property RevisionStatus $status
 * @property string $title
 * @property string|null $summary
 * @property string $body
 * @property string|null $public_byline
 * @property-read Publication $publication
 */
class PublicationRevision extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['status' => RevisionStatus::class, 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    /** @return BelongsTo<Publication, $this> */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    /** @return HasMany<RevisionSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(RevisionSource::class);
    }

    /** @return HasMany<RevisionMedia, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(RevisionMedia::class)->orderByRaw("purpose = 'cover' desc")->orderBy('position')->orderBy('id');
    }
}
