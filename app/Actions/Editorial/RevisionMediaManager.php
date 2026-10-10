<?php

namespace App\Actions\Editorial;

use App\Enums\MediaProcessingStatus;
use App\Enums\MediaPurpose;
use App\Enums\MediaStatus;
use App\Enums\RevisionStatus;
use App\Models\AuditEntry;
use App\Models\MediaAsset;
use App\Models\Publication;
use App\Models\PublicationRevision;
use App\Models\RevisionMedia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Each revision owns its image set. Only drafts change; submitted, approved and published revisions are frozen,
 * and a new revision receives a copy of the rows (never of the files).
 */
class RevisionMediaManager
{
    /** @param array<string, mixed> $input */
    public function attach(User $actor, PublicationRevision $revision, MediaAsset $asset, array $input): RevisionMedia
    {
        return $this->inDraft($actor, $revision, function (PublicationRevision $revision) use ($actor, $asset, $input): RevisionMedia {
            Gate::forUser($actor)->authorize('use', $asset);
            if ($asset->processing_status === MediaProcessingStatus::Failed) {
                throw ValidationException::withMessages(['media' => 'A imagem falhou no processamento e não pode ser usada.']);
            }
            $data = $this->validate($input, true);
            $purpose = MediaPurpose::from($data['purpose']);
            if ($purpose === MediaPurpose::Cover) {
                $revision->media()->where('purpose', MediaPurpose::Cover->value)->delete();
            } elseif ($revision->media()->where('media_asset_id', $asset->id)->where('purpose', 'content')->exists()) {
                throw ValidationException::withMessages(['media' => 'Esta imagem já está no conteúdo desta revisão.']);
            }
            $item = new RevisionMedia;
            $item->forceFill(['publication_revision_id' => $revision->id, 'media_asset_id' => $asset->id, 'purpose' => $purpose,
                'position' => $purpose === MediaPurpose::Cover ? 0 : ((int) $revision->media()->where('purpose', 'content')->max('position')) + 1,
                'alt_text' => $data['alt_text'], 'caption' => $data['caption'] ?? null, 'credit' => $data['credit']])->save();
            $this->audit($actor, $revision, $asset, 'media_attached', ['purpose' => $purpose->value]);

            return $item;
        });
    }

    /** @param array<string, mixed> $input */
    public function update(User $actor, RevisionMedia $item, array $input): RevisionMedia
    {
        return $this->inDraft($actor, $item->revision, function () use ($actor, $item, $input): RevisionMedia {
            $item = RevisionMedia::query()->lockForUpdate()->findOrFail($item->id);
            $data = $this->validate($input, false);
            $item->forceFill(['alt_text' => $data['alt_text'], 'caption' => $data['caption'] ?? null, 'credit' => $data['credit']])->save();
            $this->audit($actor, $item->revision, $item->asset, 'media_updated');

            return $item;
        });
    }

    public function detach(User $actor, RevisionMedia $item): void
    {
        $this->inDraft($actor, $item->revision, function () use ($actor, $item): void {
            $item = RevisionMedia::query()->lockForUpdate()->findOrFail($item->id);
            $item->delete();
            $this->audit($actor, $item->revision, $item->asset, 'media_detached', ['purpose' => $item->purpose->value]);
        });
    }

    public function move(User $actor, RevisionMedia $item, string $direction): void
    {
        $this->inDraft($actor, $item->revision, function (PublicationRevision $revision) use ($item, $direction): void {
            $rows = $revision->media()->where('purpose', 'content')->lockForUpdate()->get()->values();
            $index = $rows->search(fn (RevisionMedia $row) => $row->id === $item->id);
            $target = $direction === 'up' ? $index - 1 : $index + 1;
            if ($index === false || ! $rows->has($target)) {
                return;
            }
            $ordered = $rows->all();
            [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];
            foreach ($ordered as $position => $row) {
                $row->forceFill(['position' => $position + 1])->save();
            }
        });
    }

    /** Copies the image set of the base revision into a newly created draft (same transaction and lock). */
    public function copy(PublicationRevision $from, PublicationRevision $to): void
    {
        foreach ($from->media()->with('asset')->get() as $row) {
            if ($row->asset->status !== MediaStatus::Active) {
                continue;
            }
            (new RevisionMedia)->forceFill(['publication_revision_id' => $to->id, 'media_asset_id' => $row->media_asset_id,
                'purpose' => $row->purpose, 'position' => $row->position, 'alt_text' => $row->alt_text,
                'caption' => $row->caption, 'credit' => $row->credit])->save();
        }
    }

    /** @throws ValidationException when an image is not processed, is blocked or lacks usage rights */
    public function assertPublishable(PublicationRevision $revision): void
    {
        $problems = [];
        foreach ($revision->media()->with('asset')->get() as $row) {
            $asset = $row->asset;
            $label = $row->alt_text;
            if ($asset->processing_status !== MediaProcessingStatus::Ready) {
                $problems[] = "A imagem “{$label}” ainda não foi processada.";
            } elseif ($asset->status !== MediaStatus::Active) {
                $problems[] = "A imagem “{$label}” está bloqueada.";
            } elseif (! $asset->hasRights()) {
                $problems[] = "A imagem “{$label}” não tem direitos de uso registrados.";
            }
        }
        if ($problems !== []) {
            throw ValidationException::withMessages(['media' => $problems]);
        }
    }

    /**
     * @template T
     *
     * @param  \Closure(PublicationRevision): T  $callback
     * @return T
     */
    private function inDraft(User $actor, PublicationRevision $revision, \Closure $callback): mixed
    {
        return DB::transaction(function () use ($actor, $revision, $callback): mixed {
            $publication = Publication::query()->lockForUpdate()->findOrFail($revision->publication_id);
            Gate::forUser($actor)->authorize('update', $publication);
            $revision = $revision->fresh() ?? abort(404);
            if ($revision->status !== RevisionStatus::Draft) {
                throw ValidationException::withMessages(['media' => 'Somente revisões em rascunho podem ter imagens alteradas. Crie uma nova revisão.']);
            }

            return $callback($revision);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{purpose: string, alt_text: string, caption?: string|null, credit: string}
     */
    private function validate(array $input, bool $withPurpose): array
    {
        /** @var array{purpose: string, alt_text: string, caption?: string|null, credit: string} */
        return Validator::make($input, [
            'purpose' => [$withPurpose ? 'required' : 'exclude', Rule::enum(MediaPurpose::class)],
            'alt_text' => ['required', 'string', 'max:250', 'not_regex:/<[^>]*>/u'],
            'caption' => ['nullable', 'string', 'max:300', 'not_regex:/<[^>]*>/u'],
            'credit' => ['required', 'string', 'max:200', 'not_regex:/<[^>]*>/u'],
        ], ['alt_text.required' => 'Descreva a imagem no texto alternativo.', 'credit.required' => 'Informe o crédito da imagem.'])->validate();
    }

    /** @param array<string, mixed> $changes */
    private function audit(User $actor, PublicationRevision $revision, MediaAsset $asset, string $action, array $changes = []): void
    {
        AuditEntry::create(['actor_id' => $actor->id, 'publication_id' => $revision->publication_id,
            'publication_revision_id' => $revision->id, 'media_asset_id' => $asset->id, 'action' => $action,
            'changes' => ['uuid' => $asset->uuid, 'version' => $revision->version, ...$changes]]);
    }
}
