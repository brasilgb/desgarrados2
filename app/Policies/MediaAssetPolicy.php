<?php

namespace App\Policies;

use App\Enums\MediaStatus;
use App\Enums\RevisionStatus;
use App\Models\MediaAsset;
use App\Models\User;

class MediaAssetPolicy
{
    public function create(User $user): bool
    {
        return (new PublicationPolicy)->create($user);
    }

    /** Owners see their own uploads; editors see the whole library. */
    public function view(User $user, MediaAsset $asset): bool
    {
        return $this->create($user) && ($asset->owner_id === $user->id || (new PublicationPolicy)->manage($user));
    }

    public function use(User $user, MediaAsset $asset): bool
    {
        return $this->view($user, $asset) && $asset->status === MediaStatus::Active;
    }

    /** Rights of an image already under review or published are changed only by editors (audited). */
    public function update(User $user, MediaAsset $asset): bool
    {
        if (! $this->view($user, $asset)) {
            return false;
        }

        return (new PublicationPolicy)->manage($user)
            || ! $asset->usages()->whereHas('revision', fn ($q) => $q->where('status', '!=', RevisionStatus::Draft->value))->exists();
    }

    public function block(User $user, MediaAsset $asset): bool
    {
        return (new PublicationPolicy)->manage($user);
    }

    public function delete(User $user, MediaAsset $asset): bool
    {
        return $this->view($user, $asset);
    }
}
