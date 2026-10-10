<?php

namespace App\Actions\Editorial;

use App\Enums\MediaRights;
use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaAsset;
use App\Media\ImageInspector;
use App\Media\MediaProcessor;
use App\Models\AuditEntry;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Upload and lifecycle of library images. Files are written before the row and removed if the row fails. */
class MediaLibrary
{
    public function __construct(private ImageInspector $inspector) {}

    /** @param array<string, mixed> $input */
    public function upload(User $actor, mixed $file, array $input = []): MediaAsset
    {
        Gate::forUser($actor)->authorize('create', MediaAsset::class);
        Validator::make(['file' => $file], ['file' => ['required', 'file', 'max:'.config('media.max_upload_kb')]],
            ['file.max' => 'A imagem pode ter no máximo 10 MB.', 'file.required' => 'Selecione uma imagem.',
                'file.file' => 'O envio da imagem falhou. Verifique o tamanho do arquivo.'])->validate();
        /** @var UploadedFile $file */
        $rights = $this->validateRights($input, false);
        $info = $this->inspector->inspect($file->getRealPath());
        $sha256 = (string) hash_file('sha256', $file->getRealPath());
        $existing = MediaAsset::where('owner_id', $actor->id)->where('sha256', $sha256)->first();
        if ($existing) {
            return $existing;
        }
        // v4: fully random (no embedded timestamp) and evenly spread across the two-character directories.
        $uuid = (string) Str::uuid();
        $path = MediaProcessor::originalPath($uuid, $info['extension']);
        $disk = Storage::disk(config('media.disk'));
        $disk->putFileAs(dirname($path), $file, basename($path));
        try {
            $asset = DB::transaction(function () use ($actor, $file, $info, $sha256, $uuid, $path, $rights): MediaAsset {
                $asset = new MediaAsset;
                $asset->forceFill([...$rights, 'uuid' => $uuid, 'owner_id' => $actor->id, 'sha256' => $sha256,
                    'original_path' => $path, 'original_name' => Str::limit($this->safeName($file->getClientOriginalName()), 200, ''),
                    'mime' => $info['mime'], 'width' => $info['width'], 'height' => $info['height'],
                    'size_bytes' => (int) $file->getSize(), 'processing_status' => 'pending', 'status' => 'active'])->save();
                $this->audit($actor, $asset, 'media_uploaded');
                ProcessMediaAsset::dispatch($asset->id);

                return $asset;
            });
        } catch (QueryException $e) {
            $disk->delete($path);
            if (($e->errorInfo[1] ?? null) === 1062) {
                return MediaAsset::where('owner_id', $actor->id)->where('sha256', $sha256)->firstOrFail();
            }
            throw $e;
        }

        return $asset->refresh();
    }

    /** @param array<string, mixed> $input */
    public function updateRights(User $actor, MediaAsset $asset, array $input): MediaAsset
    {
        Gate::forUser($actor)->authorize('update', $asset);
        $rights = $this->validateRights($input, true);

        return DB::transaction(function () use ($actor, $asset, $rights): MediaAsset {
            $asset = MediaAsset::query()->lockForUpdate()->findOrFail($asset->id);
            $asset->forceFill($rights)->save();
            $this->audit($actor, $asset, 'media_rights_updated', ['rights_type' => $asset->rights_type?->value]);

            return $asset;
        });
    }

    public function block(User $actor, MediaAsset $asset, ?string $reason): MediaAsset
    {
        Gate::forUser($actor)->authorize('block', $asset);
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:500']],
            ['reason.required' => 'Informe o motivo do bloqueio.'])->validate();
        $asset->forceFill(['status' => MediaStatus::Blocked, 'blocked_reason' => $reason])->save();
        $this->audit($actor, $asset, 'media_blocked');

        return $asset;
    }

    public function unblock(User $actor, MediaAsset $asset): MediaAsset
    {
        Gate::forUser($actor)->authorize('block', $asset);
        $asset->forceFill(['status' => MediaStatus::Active, 'blocked_reason' => null])->save();
        $this->audit($actor, $asset, 'media_unblocked');

        return $asset;
    }

    /** Only images never associated with a revision can be deleted; revision history keeps its files. */
    public function delete(User $actor, MediaAsset $asset): void
    {
        Gate::forUser($actor)->authorize('delete', $asset);
        $paths = DB::transaction(function () use ($actor, $asset): array {
            $asset = MediaAsset::query()->lockForUpdate()->findOrFail($asset->id);
            if ($asset->usages()->exists()) {
                throw ValidationException::withMessages(['media' => 'A imagem está associada a revisões e não pode ser excluída. Bloqueie-a, se necessário.']);
            }
            $this->audit($actor, $asset, 'media_deleted', ['uuid' => $asset->uuid]);
            $paths = [$asset->original_path, MediaProcessor::derivativeDirectory($asset->uuid)];
            $asset->delete();

            return $paths;
        });
        $disk = Storage::disk(config('media.disk'));
        $disk->delete($paths[0]);
        $disk->deleteDirectory($paths[1]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{rights_type: string|null, rights_holder: string|null, license: string|null, rights_notes: string|null}
     */
    private function validateRights(array $input, bool $required): array
    {
        $data = Validator::make($input, [
            'rights_type' => [$required ? 'required' : 'nullable', Rule::enum(MediaRights::class)],
            'rights_holder' => ['nullable', 'required_with:rights_type', 'string', 'max:200', 'not_regex:/<[^>]*>/u'],
            'license' => ['nullable', 'string', 'max:120', 'not_regex:/<[^>]*>/u'],
            'rights_notes' => ['nullable', 'string', 'max:1000', 'not_regex:/<[^>]*>/u'],
        ], ['rights_type.required' => 'Informe o tipo de direito de uso.',
            'rights_holder.required_with' => 'Informe o titular dos direitos.'])->validate();

        return ['rights_type' => $data['rights_type'] ?? null, 'rights_holder' => $data['rights_holder'] ?? null,
            'license' => $data['license'] ?? null, 'rights_notes' => $data['rights_notes'] ?? null];
    }

    private function safeName(string $name): string
    {
        return trim((string) preg_replace('/[^\pL\pN ._-]+/u', '', basename($name)));
    }

    /** @param array<string, mixed> $changes */
    private function audit(User $actor, MediaAsset $asset, string $action, array $changes = []): void
    {
        AuditEntry::create(['actor_id' => $actor->id, 'media_asset_id' => $asset->id, 'action' => $action,
            'changes' => ['uuid' => $asset->uuid, 'status' => $asset->status->value, ...$changes]]);
    }
}
