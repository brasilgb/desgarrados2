<?php

namespace App\Models;

use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property int $id */
#[Fillable(['name', 'slug', 'kind', 'description', 'active'])]
class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    /** @return BelongsToMany<Municipality, $this> */
    public function municipalities(): BelongsToMany
    {
        return $this->belongsToMany(Municipality::class);
    }

    /** @return BelongsToMany<Publication, $this> */
    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class);
    }

    /** @return HasMany<PublicationFeature, $this> */
    public function features(): HasMany
    {
        return $this->hasMany(PublicationFeature::class);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
