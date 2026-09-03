<?php

namespace App\Support;

use App\Models\QuestionExplanationAsset;
use App\Models\SharedQuestionExplanationAsset;

class QuestionExplanationAssetPayloadBuilder
{
    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function forAsset(QuestionExplanationAsset|SharedQuestionExplanationAsset|null $asset): ?array
    {
        if (! $asset || ! $asset->is_active) {
            return null;
        }

        if (blank($asset->file_path) && blank($asset->traffic_sign_id)) {
            return null;
        }

        if ($asset->traffic_sign_id && $asset->trafficSign) {
            return [
                'id' => $asset->getKey(),
                'kind' => $asset->kind,
                'title' => 'Znak '.$asset->trafficSign->name,
                'body' => null, // Intentionally null per user request
                'caption' => null,
                'alt_text' => $asset->trafficSign->image_alt ?? 'Znak drogowy',
                'image_url' => $this->mediaUrlResolver->resolve($asset->trafficSign->image_path, 'public'),
            ];
        }

        return [
            'id' => $asset->getKey(),
            'kind' => $asset->kind,
            'title' => $asset->title,
            'body' => $asset->body,
            'caption' => $asset->caption,
            'alt_text' => $asset->alt_text,
            'image_url' => $this->mediaUrlResolver->resolve($asset->file_path, $asset->disk),
        ];
    }
}
