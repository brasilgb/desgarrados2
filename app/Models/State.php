<?php

namespace App\Models;

use Database\Factories\StateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $ibge_code
 * @property string $abbreviation
 * @property string $name
 * @property string $slug
 * @property bool $active
 */
#[Fillable(['ibge_code', 'abbreviation', 'name', 'slug', 'active', 'synced_at'])]
class State extends Model
{
    /** @use HasFactory<StateFactory> */
    use HasFactory;

    /** @return HasMany<Municipality, $this> */
    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'synced_at' => 'immutable_datetime'];
    }
}
