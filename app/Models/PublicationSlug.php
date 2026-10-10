<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property int $id
 * @property string $slug
 * @property bool $is_current
 * @property-read Publication $publication
 */
#[Fillable(['publication_id', 'slug', 'is_current', 'retired_at'])]
class PublicationSlug extends Model
{
    protected function casts(): array
    {
        return ['is_current' => 'boolean', 'retired_at' => 'datetime'];
    }

    /** @return BelongsTo<Publication, $this> */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }
}
