<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property int $id
 * @property string $name
 * @property string $slug
 */
#[Fillable(['name', 'slug'])]
class Category extends Model
{
    /** @return HasMany<Publication, $this> */
    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }
}
