<?php

namespace App\Actions\Editorial;

use App\Models\AuditEntry;
use App\Models\Publication;
use App\Models\PublicationFeature;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManagePublicationFeatures
{
    /** @param array<string, mixed> $input */
    public function save(User $actor, array $input, ?PublicationFeature $feature = null): PublicationFeature
    {
        Gate::forUser($actor)->authorize('manage', Publication::class);
        $data = Validator::make($input, [
            'publication_id' => ['required', 'integer', 'exists:publications,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'position' => ['required', 'integer', 'between:1,12'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ])->validate();

        return DB::transaction(function () use ($actor, $data, $feature): PublicationFeature {
            if ($feature) {
                $feature = PublicationFeature::query()->lockForUpdate()->findOrFail($feature->id);
            }
            $publication = Publication::query()->with('publishedRevision.media.asset')->whereKey($data['publication_id'])->firstOrFail();
            $this->assertEligible($publication, isset($data['region_id']) ? (int) $data['region_id'] : null);
            $scope = isset($data['region_id']) ? 'region:'.$data['region_id'] : 'global';
            DB::table('publication_feature_locks')->insertOrIgnore(['scope' => $scope, 'position' => $data['position']]);
            DB::table('publication_feature_locks')->where(['scope' => $scope, 'position' => $data['position']])->lockForUpdate()->first();

            $start = CarbonImmutable::parse($data['starts_at'])->utc();
            $end = isset($data['ends_at']) ? CarbonImmutable::parse($data['ends_at'])->utc() : null;
            $conflict = PublicationFeature::query()
                ->where('position', $data['position'])
                ->where(fn ($query) => isset($data['region_id']) ? $query->where('region_id', $data['region_id']) : $query->whereNull('region_id'))
                ->when($feature, fn ($query) => $query->where('id', '!=', $feature->id))
                ->when($end, fn ($query) => $query->where('starts_at', '<', $end))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $start))
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages(['position' => 'Já existe um destaque nesta posição durante parte do período informado.']);
            }

            $before = $feature?->only(['publication_id', 'region_id', 'position', 'starts_at', 'ends_at']);
            $feature ??= new PublicationFeature;
            $feature->forceFill([...$data, 'starts_at' => $start, 'ends_at' => $end, 'curator_id' => $actor->id])->save();
            AuditEntry::create(['actor_id' => $actor->id, 'publication_id' => $publication->id,
                'publication_revision_id' => $publication->published_revision_id, 'action' => $before ? 'feature_updated' : 'feature_created',
                'changes' => ['feature_id' => $feature->id, 'before' => $before, 'after' => $feature->only(['publication_id', 'region_id', 'position', 'starts_at', 'ends_at'])]]);

            return $feature;
        }, 3);
    }

    public function delete(User $actor, PublicationFeature $feature): void
    {
        Gate::forUser($actor)->authorize('manage', Publication::class);
        DB::transaction(function () use ($actor, $feature): void {
            $feature = PublicationFeature::query()->lockForUpdate()->findOrFail($feature->id);
            AuditEntry::create(['actor_id' => $actor->id, 'publication_id' => $feature->publication_id,
                'publication_revision_id' => $feature->publication->published_revision_id, 'action' => 'feature_deleted',
                'changes' => ['feature_id' => $feature->id, 'before' => $feature->only(['publication_id', 'region_id', 'position', 'starts_at', 'ends_at'])]]);
            $feature->delete();
        }, 3);
    }

    private function assertEligible(Publication $publication, ?int $regionId): void
    {
        if (! Publication::query()->publiclyVisible()->whereKey($publication->id)->exists()) {
            throw ValidationException::withMessages(['publication_id' => 'A publicação deve estar aprovada, publicada e visível.']);
        }
        if ($regionId && ! $publication->regions()->whereKey($regionId)->exists()) {
            throw ValidationException::withMessages(['region_id' => 'A publicação precisa estar relacionada à região escolhida.']);
        }
        if ($regionId && ! Region::query()->whereKey($regionId)->where('active', true)->exists()) {
            throw ValidationException::withMessages(['region_id' => 'A região escolhida precisa estar ativa.']);
        }
        if ($publication->publishedRevision?->media->contains(fn ($usage) => ! $usage->asset->isPublishable())) {
            throw ValidationException::withMessages(['publication_id' => 'A publicação possui imagem bloqueada, sem direitos ou ainda não processada.']);
        }
    }
}
