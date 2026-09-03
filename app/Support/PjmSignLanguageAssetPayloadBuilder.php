<?php

namespace App\Support;

use App\Models\QuestionSignLanguageAsset;
use Illuminate\Support\Collection;

class PjmSignLanguageAssetPayloadBuilder
{
    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @param  iterable<QuestionSignLanguageAsset>  $assets
     * @return array<int, array<string, mixed>>
     */
    public function forQuestion(iterable $assets): array
    {
        return Collection::make($assets)
            ->filter(fn (QuestionSignLanguageAsset $asset): bool => $asset->is_active)
            ->sortBy([
                fn (QuestionSignLanguageAsset $asset): int => $this->rolePosition($asset->asset_role),
                fn (QuestionSignLanguageAsset $asset): int => (int) $asset->getKey(),
            ])
            ->map(fn (QuestionSignLanguageAsset $asset): array => [
                'id' => $asset->getKey(),
                'external_id' => $asset->external_id,
                'role' => $asset->asset_role,
                'url' => $this->mediaUrlResolver->resolve($asset->path, $asset->disk),
                'mime_type' => $asset->mime_type,
                'duration_seconds' => $asset->duration_seconds,
                'bytes' => $asset->bytes,
                'width' => $asset->width,
                'height' => $asset->height,
                'variant' => $asset->variant,
                'processing_status' => $asset->processing_status,
                'review_required' => $asset->review_required,
            ])
            ->values()
            ->all();
    }

    private function rolePosition(string $role): int
    {
        return match ($role) {
            QuestionSignLanguageAsset::ROLE_QUESTION => 0,
            QuestionSignLanguageAsset::ROLE_ANSWER_A => 1,
            QuestionSignLanguageAsset::ROLE_ANSWER_B => 2,
            QuestionSignLanguageAsset::ROLE_ANSWER_C => 3,
            default => 99,
        };
    }
}
