<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property int $id @property int $publication_id @property int|null $region_id @property int $position */
class PublicationFeature extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Publication, $this> */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /** @return BelongsTo<User, $this> */
    public function curator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'curator_id');
    }

    /** @param Builder<PublicationFeature> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('starts_at', '<=', now())->where(fn (Builder $period) => $period->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
