<?php

namespace App\Http\Controllers\Editorial;

use App\Actions\Editorial\EditorialWorkflow;
use App\Enums\MediaRights;
use App\Enums\PublicationStatus;
use App\Enums\PublicationType;
use App\Enums\RevisionStatus;
use App\Http\Controllers\Controller;
use App\Media\MediaPresenter;
use App\Models\MediaAsset;
use App\Models\Publication;
use App\Models\PublicationRevision;
use App\Models\RevisionMedia;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PublicationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Publication::class);
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(PublicationStatus::class)], 'type' => ['nullable', Rule::enum(PublicationType::class)],
            'author_id' => ['nullable', 'integer'], 'category_id' => ['nullable', 'integer'],
            'municipality_id' => ['nullable', 'integer'], 'region_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $query = Publication::query()->with(['author:id,name', 'currentSlug'])->withMax('revisions', 'version')
            ->addSelect(['latest_title' => PublicationRevision::select('title')->whereColumn('publication_id', 'publications.id')->orderByDesc('version')->limit(1)]);
        if (! Gate::allows('manage', Publication::class)) {
            $query->where('author_id', $request->user()?->id);
        }
        foreach (['status', 'type', 'author_id', 'category_id', 'municipality_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['region_id'])) {
            $query->whereHas('regions', fn ($q) => $q->where('regions.id', $filters['region_id']));
        }
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to'].' 23:59:59');
        }

        return Inertia::render('editorial/index', ['publications' => $query->orderByDesc('id')->paginate(20)->withQueryString()->through(fn (Publication $p) => [
            'id' => $p->id, 'title' => $p->getAttribute('latest_title'), 'slug' => $p->currentSlug?->slug, 'status' => $p->status->value, 'type' => $p->type->value,
            'author' => $p->author?->name, 'version' => $p->getAttribute('revisions_max_version'), 'scheduled_for' => $p->scheduled_for?->toIso8601String(),
        ]), 'filters' => $filters, 'types' => array_column(PublicationType::cases(), 'value'),
            'statuses' => array_column(PublicationStatus::cases(), 'value'), 'canManage' => Gate::allows('manage', Publication::class)]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Publication::class);

        return Inertia::render('editorial/create', ['types' => array_column(PublicationType::cases(), 'value')]);
    }

    public function store(Request $request, EditorialWorkflow $workflow): RedirectResponse
    {
        $publication = $workflow->create($this->actor($request), $request->all());

        return to_route('editorial.show', $publication);
    }

    public function show(Request $request, Publication $publication): Response
    {
        Gate::authorize('view', $publication);
        $publication->load(['currentSlug', 'category', 'municipality', 'tags', 'regions']);
        $revision = $request->filled('revision') ? $publication->revisions()->findOrFail($request->integer('revision')) : $publication->revisions()->latest('version')->firstOrFail();
        $revision->load('sources');

        return Inertia::render('editorial/show', [
            'publication' => ['id' => $publication->id, 'status' => $publication->status->value,
                'type' => $publication->type->value, 'slug' => $publication->currentSlug?->slug,
                'scheduled_for' => $publication->scheduled_for?->toIso8601String(), 'published_revision_id' => $publication->published_revision_id,
                'municipality' => $publication->municipality?->only(['id', 'name']), 'category' => $publication->category?->only(['id', 'name']),
                'tags' => $publication->tags->map->only(['id', 'name']), 'regions' => $publication->regions->map->only(['id', 'name'])],
            'revision' => $revision->only(['id', 'version', 'status', 'title', 'summary', 'body', 'public_byline', 'review_note', 'submitted_at', 'reviewed_at', 'sources']),
            'revisions' => $publication->revisions()->latest('version')->paginate(15)->withQueryString()->through(fn (PublicationRevision $r) => $r->only(['id', 'version', 'status', 'title'])),
            'permissions' => ['manage' => Gate::allows('publish', $publication), 'submit' => Gate::allows('submit', $revision),
                'review' => Gate::allows('review', $revision), 'update' => Gate::allows('update', $publication),
                'editMedia' => $revision->status === RevisionStatus::Draft && Gate::allows('update', $publication)],
            'media' => $revision->media()->with(['asset' => fn ($q) => $q->withExists('usages')])->get()->map(fn (RevisionMedia $row) => [
                'id' => $row->id, ...MediaPresenter::usage($row, 'card'), 'asset' => $this->asset($row->asset)]),
            'library' => fn () => $this->library($request),
            'rightsTypes' => array_column(MediaRights::cases(), 'value'),
            'uploadLimitMb' => (int) floor(min(config('media.max_upload_kb') * 1024, $this->phpUploadLimit()) / 1024 / 1024),
        ]);
    }

    /** @return array<string, mixed> */
    private function asset(MediaAsset $asset): array
    {
        return ['uuid' => $asset->uuid, 'name' => $asset->original_name, 'width' => $asset->width, 'height' => $asset->height,
            'processing' => $asset->processing_status->value, 'status' => $asset->status->value, 'blocked_reason' => $asset->blocked_reason,
            'rights_type' => $asset->rights_type?->value, 'rights_holder' => $asset->rights_holder, 'license' => $asset->license,
            'rights_notes' => $asset->rights_notes, 'has_rights' => $asset->hasRights(), 'preview' => MediaPresenter::image($asset, 'card'),
            'can' => ['update' => Gate::allows('update', $asset), 'block' => Gate::allows('block', $asset), 'delete' => Gate::allows('delete', $asset)
                && ! ($asset->getAttribute('usages_exists') ?? $asset->usages()->exists())]];
    }

    /** @return list<array<string, mixed>> Library limited to what the user may use: own uploads, or everything for editors. */
    private function library(Request $request): array
    {
        $query = MediaAsset::query()->withExists('usages')->latest('id')->limit(48);
        if (! Gate::allows('manage', Publication::class)) {
            $query->where('owner_id', $request->user()?->id);
        }

        return array_values($query->get()->map(fn (MediaAsset $asset) => $this->asset($asset))->all());
    }

    private function phpUploadLimit(): int
    {
        $bytes = fn (string $value): int => (int) $value * match (strtolower(substr(trim($value), -1))) {
            'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1
        };

        return min($bytes((string) ini_get('upload_max_filesize')), $bytes((string) ini_get('post_max_size')));
    }

    public function revision(Request $request, Publication $publication, EditorialWorkflow $workflow): RedirectResponse
    {
        $revision = $workflow->createRevision($this->actor($request), $publication, $request->all());

        return to_route('editorial.show', ['publication' => $publication, 'revision' => $revision->id]);
    }

    public function transition(Request $request, Publication $publication, EditorialWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['submit', 'approve', 'reject', 'publish', 'schedule', 'hide', 'archive', 'delete'])],
            'revision_id' => ['nullable', 'integer'], 'note' => ['nullable', 'string', 'max:2000'], 'scheduled_for' => ['nullable', 'string']]);
        $actor = $this->actor($request);
        if (in_array($data['action'], ['hide', 'archive', 'delete'], true)) {
            if ($data['action'] === 'delete') {
                $workflow->delete($actor, $publication);

                return to_route('editorial.index');
            }
            $workflow->conceal($actor, $publication, $data['action'] === 'archive');
        } else {
            $revision = $publication->revisions()->where('id', $request->integer('revision_id'))->firstOrFail();
            match ($data['action']) {
                'submit' => $workflow->submit($actor, $revision),
                'approve' => $workflow->review($actor, $revision, true, $data['note'] ?? null),
                'reject' => $workflow->review($actor, $revision, false, $data['note'] ?? null),
                'publish' => $workflow->publish($actor, $publication, $revision),
                'schedule' => $workflow->schedule($actor, $publication, $revision, $data['scheduled_for'] ?? ''),
                default => abort(422),
            };
        }

        return back();
    }

    public function metadata(Request $request, Publication $publication, EditorialWorkflow $workflow): RedirectResponse
    {
        $workflow->metadata($this->actor($request), $publication, $request->all());

        return back();
    }

    private function actor(Request $request): User
    {
        return $request->user() ?? abort(403);
    }
}
