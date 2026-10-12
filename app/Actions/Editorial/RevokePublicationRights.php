<?php

namespace App\Actions\Editorial;

use App\Models\AuditEntry;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RevokePublicationRights
{
    public function handle(User $actor, Publication $publication, string $reason): void
    {
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:2000']])->validate();
        DB::transaction(function () use ($actor, $publication, $reason): void {
            $publication = Publication::query()->lockForUpdate()->findOrFail($publication->id);
            Gate::forUser($actor)->authorize('publish', $publication);
            $revisionId = $publication->published_revision_id ?? $publication->scheduled_revision_id;
            if ($revisionId === null) {
                throw ValidationException::withMessages(['publication' => 'A publicação não tem versão publicada ou agendada para bloquear.']);
            }
            $publication->forceFill(['status' => 'hidden', 'visibility' => 'private', 'scheduled_for' => null,
                'scheduled_revision_id' => null, 'scheduled_revision_status' => null, 'scheduled_by' => null])->save();
            AuditEntry::create(['actor_id' => $actor->id, 'publication_id' => $publication->id,
                'publication_revision_id' => $revisionId, 'action' => 'rights_revoked',
                'changes' => ['reason' => $reason, 'publication_hidden' => true]]);
        }, 3);
    }
}
