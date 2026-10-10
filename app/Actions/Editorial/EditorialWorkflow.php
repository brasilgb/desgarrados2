<?php

namespace App\Actions\Editorial;

use App\Enums\PublicationStatus;
use App\Enums\PublicationType;
use App\Enums\RevisionStatus;
use App\Models\AuditEntry;
use App\Models\Publication;
use App\Models\PublicationRevision;
use App\Models\PublicationSlug;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** All editorial writes lock the publication first, including revision allocation and slug changes. */
class EditorialWorkflow
{
    public function __construct(private RevisionMediaManager $media = new RevisionMediaManager) {}

    /** @param array<string, mixed> $input */
    public function create(User $actor, array $input): Publication
    {
        Gate::forUser($actor)->authorize('create', Publication::class);
        $data = Validator::make($input, ['type' => ['required', Rule::enum(PublicationType::class)],
            'slug' => ['nullable', 'string', 'max:180']])->validate();

        return $this->slugTransaction(function () use ($actor, $data, $input): Publication {
            $publication = new Publication;
            $publication->forceFill(['author_id' => $actor->id, 'type' => $data['type'], 'status' => 'draft', 'visibility' => 'private'])->save();
            $revision = $this->createRevision($actor, $publication, $input);
            $this->changeSlug($publication, (string) ($data['slug'] ?? $revision->title));
            $this->audit($actor, $publication, 'created', $revision);

            return $publication->refresh();
        });
    }

    /** Every content save creates a new version; submitted snapshots never change.
     * @param  array<string, mixed>  $input
     */
    public function createRevision(User $actor, Publication $publication, array $input): PublicationRevision
    {
        return DB::transaction(function () use ($actor, $publication, $input): PublicationRevision {
            $publication = $this->lock($publication);
            Gate::forUser($actor)->authorize('update', $publication);
            $data = Validator::make($input, [
                'title' => ['required', 'string', 'max:200', 'not_regex:/<[^>]*>/u'],
                'summary' => ['nullable', 'string', 'max:500', 'not_regex:/<[^>]*>/u'],
                'body' => ['required', 'string', 'max:200000', 'not_regex:/<[^>]*>/u'],
                'public_byline' => ['nullable', 'string', 'max:120', 'not_regex:/<[^>]*>/u'],
                'sources' => ['sometimes', 'array', 'max:30'],
                'sources.*.title' => ['required', 'string', 'max:200'],
                'sources.*.url' => ['nullable', 'url:http,https', 'max:2048'],
                'sources.*.attribution' => ['nullable', 'string', 'max:250'],
                'sources.*.accessed_at' => ['nullable', 'date_format:Y-m-d'],
                'base_revision_id' => ['nullable', 'integer'],
            ])->validate();
            $sources = $data['sources'] ?? [];
            // Images follow the revision being edited (default: the latest one); the published set is never touched.
            $base = isset($data['base_revision_id'])
                ? $publication->revisions()->whereKey((int) $data['base_revision_id'])->firstOrFail()
                : $publication->revisions()->latest('version')->first();
            unset($data['sources'], $data['base_revision_id']);
            $revision = new PublicationRevision;
            $revision->forceFill([...$data, 'publication_id' => $publication->id, 'editor_id' => $actor->id,
                'version' => ((int) $publication->revisions()->max('version')) + 1, 'status' => 'draft'])->save();
            foreach ($sources as $source) {
                $revision->sources()->create($source);
            }
            if ($base) {
                $this->media->copy($base, $revision);
            }
            $this->audit($actor, $publication, 'revision_created', $revision);

            return $revision;
        }, 3);
    }

    public function submit(User $actor, PublicationRevision $revision): PublicationRevision
    {
        return DB::transaction(function () use ($actor, $revision): PublicationRevision {
            $publication = $this->lock($revision->publication);
            $revision = $revision->fresh() ?? abort(404);
            Gate::forUser($actor)->authorize('submit', $revision);
            $revision->forceFill(['status' => 'in_review', 'submitted_at' => now()])->save();
            if ($publication->status === PublicationStatus::Draft) {
                $publication->forceFill(['status' => 'in_review'])->save();
            }
            $this->audit($actor, $publication, 'submitted', $revision);

            return $revision;
        }, 3);
    }

    public function review(User $actor, PublicationRevision $revision, bool $approve, ?string $note): PublicationRevision
    {
        return DB::transaction(function () use ($actor, $revision, $approve, $note): PublicationRevision {
            $publication = $this->lock($revision->publication);
            $revision = $revision->fresh() ?? abort(404);
            Gate::forUser($actor)->authorize('review', $revision);
            Validator::make(['note' => $note], ['note' => [$approve ? 'nullable' : 'required', 'string', 'max:2000']])->validate();
            $revision->forceFill(['status' => $approve ? 'approved' : 'rejected', 'reviewer_id' => $actor->id,
                'reviewed_at' => now(), 'review_note' => $note])->save();
            $this->audit($actor, $publication, $approve ? 'approved' : 'rejected', $revision);

            return $revision;
        }, 3);
    }

    public function publish(User $actor, Publication $publication, PublicationRevision $revision): Publication
    {
        return DB::transaction(function () use ($actor, $publication, $revision): Publication {
            $publication = $this->lock($publication);
            Gate::forUser($actor)->authorize('publish', $publication);
            $this->assertApproved($publication, $revision);
            $this->media->assertPublishable($revision);
            $publication->forceFill(['status' => 'published', 'visibility' => 'public',
                'published_revision_id' => $revision->id, 'published_revision_status' => 'approved', 'published_at' => now(),
                'scheduled_revision_id' => null, 'scheduled_revision_status' => null, 'scheduled_for' => null,
                'scheduled_by' => null, 'archived_at' => null])->save();
            $this->audit($actor, $publication, 'published', $revision);

            return $publication;
        }, 3);
    }

    public function schedule(User $actor, Publication $publication, PublicationRevision $revision, string $date): Publication
    {
        return DB::transaction(function () use ($actor, $publication, $revision, $date): Publication {
            $publication = $this->lock($publication);
            Gate::forUser($actor)->authorize('publish', $publication);
            $this->assertApproved($publication, $revision);
            $this->media->assertPublishable($revision);
            Validator::make(['scheduled_for' => $date], ['scheduled_for' => ['required', 'date', 'after:now']])->validate();
            // An already public edition remains online while its replacement is scheduled.
            $publication->forceFill(['status' => $publication->status === PublicationStatus::Published ? 'published' : 'scheduled',
                'scheduled_revision_id' => $revision->id, 'scheduled_revision_status' => 'approved',
                'scheduled_for' => CarbonImmutable::parse($date)->utc(), 'scheduled_by' => $actor->id])->save();
            $this->audit($actor, $publication, 'scheduled', $revision);

            return $publication;
        }, 3);
    }

    /** Scheduler rechecks permissions and the deadline under the same lock. */
    public function publishDue(Publication $publication): bool
    {
        return DB::transaction(function () use ($publication): bool {
            $publication = $this->lock($publication);
            if (! $publication->scheduled_for || $publication->scheduled_for->isFuture() || ! $publication->scheduled_revision_id) {
                return false;
            }
            $actor = User::find($publication->scheduled_by);
            $revision = PublicationRevision::findOrFail($publication->scheduled_revision_id);
            $mediaReady = true;
            try {
                $this->media->assertPublishable($revision);
            } catch (ValidationException) {
                // An image blocked or stripped of rights after scheduling cancels the publication.
                $mediaReady = false;
            }
            if (! $actor || Gate::forUser($actor)->denies('publish', $publication) || ! $mediaReady) {
                $this->audit(null, $publication, 'schedule_blocked');
                $publication->forceFill(['scheduled_for' => null, 'scheduled_revision_id' => null,
                    'scheduled_revision_status' => null, 'scheduled_by' => null,
                    'status' => $publication->status === PublicationStatus::Scheduled ? 'draft' : $publication->status->value])->save();

                return false;
            }
            $this->publish($actor, $publication, $revision);

            return true;
        }, 3);
    }

    public function conceal(User $actor, Publication $publication, bool $archive = false): void
    {
        DB::transaction(function () use ($actor, $publication, $archive): void {
            $publication = $this->lock($publication);
            Gate::forUser($actor)->authorize('publish', $publication);
            $publication->forceFill(['status' => $archive ? 'archived' : 'hidden', 'visibility' => 'private',
                'archived_at' => $archive ? now() : null, 'scheduled_for' => null, 'scheduled_revision_id' => null,
                'scheduled_revision_status' => null, 'scheduled_by' => null])->save();
            $this->audit($actor, $publication, $archive ? 'archived' : 'hidden');
        }, 3);
    }

    public function delete(User $actor, Publication $publication): void
    {
        DB::transaction(function () use ($actor, $publication): void {
            $publication = $this->lock($publication);
            Gate::forUser($actor)->authorize('delete', $publication);
            $this->conceal($actor, $publication);
            $publication->delete();
            $this->audit($actor, $publication, 'deleted');
        }, 3);
    }

    /** @param array<string, mixed> $input */
    public function metadata(User $actor, Publication $publication, array $input): Publication
    {
        return $this->slugTransaction(function () use ($actor, $publication, $input): Publication {
            $publication = $this->lock($publication);
            Gate::forUser($actor)->authorize('publish', $publication);
            $data = Validator::make($input, [
                'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'tag_ids' => ['sometimes', 'array', 'max:30'], 'tag_ids.*' => ['integer', 'distinct', 'exists:tags,id'],
                'region_ids' => ['sometimes', 'array', 'max:30'], 'region_ids.*' => ['integer', 'distinct', 'exists:regions,id'],
                'slug' => ['sometimes', 'required', 'string', 'max:180'],
            ])->validate();
            foreach (['municipality_id', 'category_id'] as $field) {
                if (array_key_exists($field, $data)) {
                    $publication->setAttribute($field, $data[$field]);
                }
            }
            $publication->save();
            if (isset($data['tag_ids'])) {
                $publication->tags()->sync($data['tag_ids']);
            }
            if (isset($data['region_ids'])) {
                $publication->regions()->sync($data['region_ids']);
            }
            if (isset($data['slug'])) {
                $this->changeSlug($publication, $data['slug']);
            }
            $this->audit($actor, $publication, 'metadata_updated');

            return $publication;
        });
    }

    private function lock(Publication $publication): Publication
    {
        return Publication::query()->lockForUpdate()->findOrFail($publication->id);
    }

    private function assertApproved(Publication $publication, PublicationRevision $revision): void
    {
        $revision = $revision->fresh() ?? abort(404);
        if ($revision->publication_id !== $publication->id || $revision->status !== RevisionStatus::Approved) {
            throw ValidationException::withMessages(['revision' => 'A revisão deve pertencer à publicação e estar aprovada.']);
        }
    }

    private function changeSlug(Publication $publication, string $input): void
    {
        $slug = Str::slug($input);
        if ($slug === '' || strlen($slug) > 180) {
            throw ValidationException::withMessages(['slug' => 'Informe um slug válido com até 180 caracteres.']);
        }
        $existing = PublicationSlug::where('slug', $slug)->first();
        if ($existing) {
            if ($existing->is_current && $existing->getAttribute('publication_id') === $publication->id) {
                return;
            }
            throw ValidationException::withMessages(['slug' => 'Este slug já foi reservado, inclusive como alias.']);
        }
        $publication->slugs()->where('is_current', true)->update(['is_current' => false, 'retired_at' => now()]);
        $publication->slugs()->create(['slug' => $slug, 'is_current' => true]);
    }

    /** @template T
     * @param  \Closure(): T  $callback
     * @return T
     */
    private function slugTransaction(\Closure $callback): mixed
    {
        try {
            return DB::transaction($callback, 3);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['slug' => 'Este slug já foi reservado.']);
            }
            throw $e;
        }
    }

    private function audit(?User $actor, Publication $publication, string $action, ?PublicationRevision $revision = null): void
    {
        AuditEntry::create(['actor_id' => $actor?->id, 'publication_id' => $publication->id,
            'publication_revision_id' => $revision?->id, 'action' => $action,
            'changes' => ['status' => $publication->status->value, 'version' => $revision?->version]]);
    }
}
