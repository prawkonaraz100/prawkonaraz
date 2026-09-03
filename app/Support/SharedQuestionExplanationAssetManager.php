<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationAsset;
use App\Models\SharedQuestionExplanationAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToCheckFileExistence;

class SharedQuestionExplanationAssetManager
{
    /**
     * @param  array<string, mixed>|null  $attributes
     */
    public function syncReferenceSign(Question $question, ?array $attributes, ?int $actorId = null): ?SharedQuestionExplanationAsset
    {
        $normalized = $this->normalize($attributes);
        $existingAsset = $this->referenceSignForQuestion($question);

        if ($normalized === null) {
            if ($existingAsset) {
                $this->deleteAssetFile($existingAsset->disk, $existingAsset->file_path, $existingAsset->getKey());
                $existingAsset->delete();
            }

            return null;
        }

        if ($normalized['file_path'] !== null) {
            $normalized['file_path'] = $this->ensureSharedScopedFilePath(
                $question,
                $normalized['disk'],
                $normalized['file_path'],
                $existingAsset,
            );
        }

        $attributesToPersist = [
            'disk' => $normalized['disk'],
            'file_path' => $normalized['file_path'],
            'title' => $normalized['title'],
            'body' => $normalized['body'],
            'caption' => $normalized['caption'],
            'alt_text' => $normalized['alt_text'],
            'traffic_sign_id' => $normalized['traffic_sign_id'],
            'position' => $normalized['position'],
            'is_active' => $normalized['is_active'],
            'updated_by' => $actorId,
        ];

        if ($existingAsset) {
            $this->deleteReplacedFile($existingAsset, $normalized['disk'], $normalized['file_path']);

            $existingAsset->fill($attributesToPersist);
            $existingAsset->save();

            return $existingAsset->fresh();
        }

        return SharedQuestionExplanationAsset::query()->create([
            ...$attributesToPersist,
            'external_id' => (string) $question->external_id,
            'source_scope' => SharedQuestionExplanationAsset::sourceScopeFor($question->source),
            'kind' => SharedQuestionExplanationAsset::KIND_REFERENCE_SIGN,
            'created_by' => $actorId,
        ]);
    }

    public function referenceSignForQuestion(Question $question): ?SharedQuestionExplanationAsset
    {
        if (! filled($question->external_id)) {
            return null;
        }

        return SharedQuestionExplanationAsset::query()
            ->where('external_id', $question->external_id)
            ->where('source_scope', SharedQuestionExplanationAsset::sourceScopeFor($question->source))
            ->where('kind', SharedQuestionExplanationAsset::KIND_REFERENCE_SIGN)
            ->first();
    }

    protected function deleteReplacedFile(
        SharedQuestionExplanationAsset $existingAsset,
        ?string $nextDisk,
        ?string $nextFilePath,
    ): void {
        if ($existingAsset->file_path === $nextFilePath && $existingAsset->disk === $nextDisk) {
            return;
        }

        $this->deleteAssetFile($existingAsset->disk, $existingAsset->file_path, $existingAsset->getKey());
    }

    protected function deleteAssetFile(?string $disk, ?string $filePath, ?int $excludingAssetId = null): void
    {
        if (blank($disk) || blank($filePath)) {
            return;
        }

        if (! is_array(config("filesystems.disks.{$disk}"))) {
            return;
        }

        if ($this->isFileReferencedByOtherAssets($disk, $filePath, $excludingAssetId)) {
            return;
        }

        Storage::disk($disk)->delete($filePath);
    }

    protected function ensureSharedScopedFilePath(
        Question $question,
        string $disk,
        string $filePath,
        ?SharedQuestionExplanationAsset $existingAsset = null,
    ): string {
        if (! is_array(config("filesystems.disks.{$disk}"))) {
            return $filePath;
        }

        $targetPath = $this->sharedScopedFilePath($question, $filePath);

        if ($targetPath === $filePath) {
            return $filePath;
        }

        $storage = Storage::disk($disk);

        try {
            if (! $storage->exists($filePath)) {
                return $filePath;
            }
        } catch (UnableToCheckFileExistence) {
            return $filePath;
        }

        rescue(
            callback: static fn () => $storage->makeDirectory(dirname($targetPath)),
            report: false,
        );

        if ($storage->exists($targetPath)) {
            return $targetPath;
        }

        $isShared = $this->isFileReferencedByOtherAssets($disk, $filePath, $existingAsset?->getKey());

        $didStoreScopedFile = $isShared
            ? $storage->copy($filePath, $targetPath)
            : $storage->move($filePath, $targetPath);

        if (! $didStoreScopedFile && ! $storage->exists($targetPath)) {
            return $filePath;
        }

        rescue(
            callback: static fn () => $storage->setVisibility($targetPath, 'public'),
            report: false,
        );

        return $targetPath;
    }

    protected function sharedScopedFilePath(Question $question, string $filePath): string
    {
        $fileName = pathinfo($filePath, PATHINFO_BASENAME);
        $sourceScope = Str::slug(SharedQuestionExplanationAsset::sourceScopeFor($question->source), '-');
        $externalId = Str::slug((string) $question->external_id, '-');

        $sourceScope = $sourceScope !== '' ? $sourceScope : 'null-source';
        $externalId = $externalId !== '' ? $externalId : 'unknown-external-id';

        return trim("question-explanations/shared/{$sourceScope}/{$externalId}/{$fileName}", '/');
    }

    protected function isFileReferencedByOtherAssets(
        string $disk,
        string $filePath,
        ?int $excludingAssetId = null,
    ): bool {
        $referencedBySharedAsset = SharedQuestionExplanationAsset::query()
            ->where('disk', $disk)
            ->where('file_path', $filePath)
            ->when($excludingAssetId !== null, fn ($query) => $query->whereKeyNot($excludingAssetId))
            ->exists();

        if ($referencedBySharedAsset) {
            return true;
        }

        return QuestionExplanationAsset::query()
            ->where('disk', $disk)
            ->where('file_path', $filePath)
            ->exists();
    }

    /**
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>|null
     */
    protected function normalize(?array $attributes): ?array
    {
        if (! is_array($attributes)) {
            return null;
        }

        $filePath = $this->normalizeString($attributes['file_path'] ?? null);
        $title = $this->normalizeString($attributes['title'] ?? null);
        $body = $this->normalizeText($attributes['body'] ?? null);
        $caption = $this->normalizeString($attributes['caption'] ?? null);
        $altText = $this->normalizeString($attributes['alt_text'] ?? null);
        $trafficSignId = isset($attributes['traffic_sign_id']) && $attributes['traffic_sign_id'] ? (int) $attributes['traffic_sign_id'] : null;
        $isActive = (bool) ($attributes['is_active'] ?? false);
        $position = max((int) ($attributes['position'] ?? 1), 1);

        if ($filePath === null && $title === null && $body === null && $caption === null && $altText === null && ! $isActive && $trafficSignId === null) {
            return null;
        }

        return [
            'disk' => $this->normalizeString($attributes['disk'] ?? null) ?? (string) config('media.public_disk', 'public'),
            'file_path' => $filePath,
            'traffic_sign_id' => $trafficSignId,
            'title' => $title,
            'body' => $body,
            'caption' => $caption,
            'alt_text' => $altText,
            'position' => $position,
            'is_active' => $isActive,
        ];
    }

    protected function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    protected function normalizeText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim(preg_replace("/\r\n?/", "\n", $value) ?? $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
