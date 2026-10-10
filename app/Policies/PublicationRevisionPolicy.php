<?php

namespace App\Policies;

use App\Enums\RevisionStatus;
use App\Models\PublicationRevision;
use App\Models\User;

class PublicationRevisionPolicy
{
    public function submit(User $user, PublicationRevision $revision): bool
    {
        return $revision->status === RevisionStatus::Draft
            && (new PublicationPolicy)->update($user, $revision->publication);
    }

    public function review(User $user, PublicationRevision $revision): bool
    {
        return $revision->status === RevisionStatus::InReview
            && $revision->editor_id !== $user->id
            && (new PublicationPolicy)->publish($user, $revision->publication);
    }
}
