<?php

namespace App\Models;

use Database\Factories\MunicipalityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $state_id
 * @property string $ibge_code
 * @property string $name
 * @property string $slug
 * @property bool $active
 * @property State $state
 */
#[Fillable(['state_id', 'ibge_code', 'name', 'slug', 'active', 'synced_at'])]
class Municipality extends Model
{
    /** @use HasFactory<MunicipalityFactory> */
    use HasFactory;

    /** @return BelongsTo<State, $this> */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /** @return BelongsToMany<Region, $this> */
    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'synced_at' => 'immutable_datetime'];
    }
}
