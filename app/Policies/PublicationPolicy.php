<?php

namespace App\Policies;

use App\Models\Publication;
use App\Models\User;
use App\RoleCode;

class PublicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->create($user);
    }

    public function create(User $user): bool
    {
        return $user->email_verified_at !== null && ($user->hasRole(RoleCode::Collaborator)
            || $user->hasRole(RoleCode::Author) || $user->hasRole(RoleCode::Editor));
    }

    public function view(User $user, Publication $publication): bool
    {
        return $this->create($user) && ($publication->author_id === $user->id || $this->manage($user));
    }

    public function update(User $user, Publication $publication): bool
    {
        return $this->view($user, $publication) && ! $publication->trashed();
    }

    public function manage(User $user): bool
    {
        // Administrators need the editor role; there is no universal bypass.
        return $user->email_verified_at !== null && $user->hasRole(RoleCode::Editor);
    }

    public function publish(User $user, Publication $publication): bool
    {
        return $this->manage($user) && ! $publication->trashed();
    }

    public function delete(User $user, Publication $publication): bool
    {
        return $this->publish($user, $publication);
    }
}
