<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** @property int $id
 * @property string $name
 * @property string $slug
 */
#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    /** @return BelongsToMany<Publication, $this> */
    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class);
    }
}
