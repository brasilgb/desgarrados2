<?php

namespace App\Actions\Editorial;

use App\Models\AdministrationAudit;
use App\Models\PublicationRevision;
use App\Models\RevisionRightsEvidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ManageRightsEvidence
{
    public function store(User $actor, PublicationRevision $revision, UploadedFile $file): RevisionRightsEvidence
    {
        Gate::forUser($actor)->authorize('update', $revision->publication);
        $path = $file->storeAs('revisions/'.$revision->id, Str::uuid().'.'.$file->extension(), 'rights');
        if (! is_string($path)) {
            throw new \RuntimeException('Não foi possível armazenar o comprovante no disco privado.');
        }

        try {
            $checksum = hash_file('sha256', $file->getRealPath());
            if (! is_string($checksum)) {
                throw new \RuntimeException('Não foi possível calcular o hash do comprovante.');
            }

            return DB::transaction(function () use ($actor, $revision, $file, $path, $checksum): RevisionRightsEvidence {
                $revision = PublicationRevision::query()->lockForUpdate()->findOrFail($revision->id);
                Gate::forUser($actor)->authorize('update', $revision->publication);
                $previousIds = $revision->rightsEvidence()->where('status', 'current')->pluck('id');
                $revision->rightsEvidence()->where('status', 'current')->update(['status' => 'superseded', 'superseded_at' => now()]);
                $evidence = RevisionRightsEvidence::create([
                    'publication_revision_id' => $revision->id,
                    'uploaded_by' => $actor->id,
                    'disk' => 'rights',
                    'path' => $path,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                    'mime_type' => (string) $file->getMimeType(),
                    'size_bytes' => (int) $file->getSize(),
                    'sha256' => $checksum,
                    'status' => 'current',
                ]);
                AdministrationAudit::create(['actor_id' => $actor->id, 'action' => 'rights_evidence_uploaded',
                    'changes' => ['revision_id' => $revision->id, 'evidence_id' => $evidence->id, 'superseded_ids' => $previousIds->all(), 'sha256' => $checksum],
                    'created_at' => now()]);

                return $evidence;
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('rights')->delete($path);
            throw $exception;
        }
    }
}
