<?php

namespace App\Support;

use App\Models\QuestionAudioAsset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class QuestionAudioCoverageService
{
    public function __construct(
        private readonly QuestionAudioExportManifestBuilder $manifestBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function summary(array $options = []): array
    {
        $sampleLimit = max(0, (int) ($options['sample_limit'] ?? 20));
        $manifest = $this->manifestBuilder->build([
            ...$options,
            'types' => $options['type'] ?? $options['types'] ?? QuestionAudioAsset::TYPE_QUESTION,
        ]);
        $audioType = (string) collect($manifest['audio_types'] ?? [QuestionAudioAsset::TYPE_QUESTION])->first();
        $items = collect($manifest['items'] ?? []);
        $reviewItems = collect($manifest['review_items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values();
        $externalIds = $items
            ->pluck('external_id')
            ->map(fn (mixed $externalId): string => (string) $externalId)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $assets = $externalIds === []
            ? collect()
            : QuestionAudioAsset::query()
                ->whereIn('external_id', $externalIds)
                ->where('content_scope', QuestionAudioAsset::CONTENT_SCOPE_QUESTION)
                ->where('audio_type', $audioType)
                ->where('locale', (string) ($manifest['locale'] ?? config('media.question_audio_locale', 'pl-PL')))
                ->get();
        $assetsByKey = $assets->keyBy('asset_key');
        $assetsByExternalId = $assets->groupBy('external_id');
        $stats = [
            'total' => $items->count() + $reviewItems->count(),
            'ready' => 0,
            'missing' => 0,
            'review_required' => $reviewItems->count(),
            'failed' => 0,
            'outdated_hash' => 0,
            'missing_files' => 0,
            'disabled' => 0,
        ];
        $samples = [
            'missing' => [],
            'failed' => [],
            'outdated_hash' => [],
            'missing_files' => [],
            'disabled' => [],
            'review_required' => $reviewItems
                ->take($sampleLimit)
                ->map(fn (mixed $item): array => $this->reviewSample(is_array($item) ? $item : []))
                ->all(),
        ];

        foreach ($items as $item) {
            $item = is_array($item) ? $item : [];
            $asset = $assetsByKey->get((string) ($item['asset_key'] ?? ''));
            $externalId = (string) ($item['external_id'] ?? '');
            $groupAssets = $assetsByExternalId->get($externalId, collect());

            if ($asset instanceof QuestionAudioAsset) {
                if ($asset->status === QuestionAudioAsset::STATUS_DISABLED) {
                    $stats['disabled']++;
                    $this->pushSample($samples['disabled'], $this->sample($item, $asset), $sampleLimit);
                    continue;
                }

                if ($asset->status === QuestionAudioAsset::STATUS_FAILED) {
                    $stats['failed']++;
                    $this->pushSample($samples['failed'], $this->sample($item, $asset), $sampleLimit);
                    continue;
                }

                if ($asset->status === QuestionAudioAsset::STATUS_GENERATED) {
                    if ($this->assetFileExists($asset)) {
                        $stats['ready']++;
                    } else {
                        $stats['missing_files']++;
                        $this->pushSample($samples['missing_files'], $this->sample($item, $asset), $sampleLimit);
                    }

                    continue;
                }
            }

            if ($this->hasGeneratedDifferentHash($groupAssets, (string) ($item['source_text_hash'] ?? ''))) {
                $stats['outdated_hash']++;
                $this->pushSample($samples['outdated_hash'], $this->sample($item), $sampleLimit);
                continue;
            }

            $stats['missing']++;
            $this->pushSample($samples['missing'], $this->sample($item), $sampleLimit);
        }

        $stats['coverage_percent'] = $this->percent($stats['ready'], $stats['total']);

        return [
            'generated_at' => now()->toIso8601String(),
            'type' => $audioType,
            'locale' => (string) ($manifest['locale'] ?? config('media.question_audio_locale', 'pl-PL')),
            'voice_provider' => (string) ($manifest['voice_provider'] ?? config('media.question_audio_voice_provider', 'elevenlabs')),
            'voice_id' => (string) ($manifest['voice_id'] ?? config('media.question_audio_voice_id', '')),
            'model_id' => (string) ($manifest['model_id'] ?? config('media.question_audio_model_id', '')),
            'generation_version' => (string) ($manifest['generation_version'] ?? config('media.question_audio_generation_version', 'question-v1')),
            'canonical_questions' => $stats,
            'database_assets' => [
                'total' => QuestionAudioAsset::query()->count(),
                'generated' => QuestionAudioAsset::query()->generated()->count(),
                'failed' => QuestionAudioAsset::query()->where('status', QuestionAudioAsset::STATUS_FAILED)->count(),
                'bytes' => (int) QuestionAudioAsset::query()->sum('bytes'),
            ],
            'samples' => $samples,
        ];
    }

    private function hasGeneratedDifferentHash(Collection $assets, string $currentHash): bool
    {
        return $assets->contains(fn (QuestionAudioAsset $asset): bool => $asset->status === QuestionAudioAsset::STATUS_GENERATED
            && $asset->source_text_hash !== $currentHash);
    }

    private function assetFileExists(QuestionAudioAsset $asset): bool
    {
        if (! filled($asset->storage_disk) || ! filled($asset->storage_path)) {
            return false;
        }

        try {
            return Storage::disk((string) $asset->storage_disk)->exists((string) $asset->storage_path);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function sample(array $item, ?QuestionAudioAsset $asset = null): array
    {
        return array_filter([
            'external_id' => $item['external_id'] ?? null,
            'asset_key' => $item['asset_key'] ?? $asset?->asset_key,
            'source_text_hash' => $item['source_text_hash'] ?? null,
            'storage_disk' => $asset?->storage_disk,
            'storage_path' => $asset?->storage_path,
            'status' => $asset?->status,
            'error_message' => $asset?->error_message,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function reviewSample(array $item): array
    {
        return array_filter([
            'external_id' => $item['external_id'] ?? null,
            'reason' => $item['reason'] ?? null,
            'message' => $item['message'] ?? null,
            'variant_count' => $item['variant_count'] ?? null,
            'category_codes' => $item['category_codes'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  array<int, mixed>  $samples
     */
    private function pushSample(array &$samples, mixed $sample, int $limit): void
    {
        if ($limit <= 0 || count($samples) >= $limit) {
            return;
        }

        $samples[] = $sample;
    }

    private function percent(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 1);
    }
}
