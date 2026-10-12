<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['publication_revision_id', 'uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'sha256', 'status', 'superseded_at'])]
class RevisionRightsEvidence extends Model
{
    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'superseded_at' => 'datetime'];
    }

    /** @return BelongsTo<PublicationRevision, $this> */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(PublicationRevision::class, 'publication_revision_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
