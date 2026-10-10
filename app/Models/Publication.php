<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\PublicationType;
use App\Enums\PublicationVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $author_id
 * @property PublicationStatus $status
 * @property PublicationType $type
 * @property PublicationVisibility $visibility
 * @property int|null $published_revision_id
 * @property int|null $scheduled_revision_id
 * @property int|null $scheduled_by
 * @property Carbon|null $published_at
 * @property Carbon|null $scheduled_for
 * @property-read PublicationRevision|null $publishedRevision
 * @property-read PublicationSlug|null $currentSlug
 * @property-read Municipality|null $municipality
 * @property-read Category|null $category
 * @property-read User|null $author
 */
class Publication extends Model
{
    use SoftDeletes;

    // Sensitive editorial fields are assigned only by authorized Actions.
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['type' => PublicationType::class, 'status' => PublicationStatus::class, 'visibility' => PublicationVisibility::class,
            'published_at' => 'datetime', 'scheduled_for' => 'datetime', 'archived_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<PublicationRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(PublicationRevision::class);
    }

    /** @return BelongsTo<PublicationRevision, $this> */
    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(PublicationRevision::class, 'published_revision_id');
    }

    /** @return HasOne<PublicationSlug, $this> */
    public function currentSlug(): HasOne
    {
        return $this->hasOne(PublicationSlug::class)->where('is_current', true);
    }

    /** @return HasMany<PublicationSlug, $this> */
    public function slugs(): HasMany
    {
        return $this->hasMany(PublicationSlug::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return BelongsToMany<Region, $this> */
    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class);
    }

    /** @param Builder<Publication> $query */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('status', 'published')->where('visibility', 'public')->where('published_at', '<=', now())
            ->whereHas('publishedRevision', fn (Builder $revision) => $revision->where('status', 'approved')->whereColumn('publication_id', 'publications.id'));
    }
}
