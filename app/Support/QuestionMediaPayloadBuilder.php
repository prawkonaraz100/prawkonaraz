<?php

namespace App\Support;

use App\Models\QuestionMedia;
use Illuminate\Support\Collection;

class QuestionMediaPayloadBuilder
{
    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @param  iterable<QuestionMedia>  $mediaItems
     * @return array<int, array<string, mixed>>
     */
    public function forCatalog(iterable $mediaItems): array
    {
        return $this->build($mediaItems, true);
    }

    /**
     * @param  iterable<QuestionMedia>  $mediaItems
     * @return array<int, array<string, mixed>>
     */
    public function forQuestion(iterable $mediaItems): array
    {
        return $this->build($mediaItems, false);
    }

    /**
     * @param  iterable<QuestionMedia>  $mediaItems
     * @return array<int, array<string, mixed>>
     */
    protected function build(iterable $mediaItems, bool $preferThumbForImages): array
    {
        return Collection::make($mediaItems)
            ->groupBy(fn (QuestionMedia $media): string => $this->groupKey($media))
            ->map(function (Collection $group) use ($preferThumbForImages): ?array {
                /** @var QuestionMedia $first */
                $first = $group->first();

                return $first->kind === 'video'
                    ? $this->videoPayload($group)
                    : $this->imagePayload($group, $preferThumbForImages);
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function groupKey(QuestionMedia $media): string
    {
        $metadata = is_array($media->metadata) ? $media->metadata : [];

        if (filled($metadata['asset_group'] ?? null)) {
            return (string) $metadata['asset_group'];
        }

        return 'media:'.$media->getKey();
    }

    protected function imagePayload(Collection $group, bool $preferThumbForImages): ?array
    {
        /** @var QuestionMedia|null $full */
        $full = $group->firstWhere('variant', 'full');
        /** @var QuestionMedia|null $thumb */
        $thumb = $group->firstWhere('variant', 'thumb');
        /** @var QuestionMedia|null $poster */
        $poster = $group->firstWhere('variant', 'poster');
        /** @var QuestionMedia|null $fallback */
        $fallback = $group->first();
        /** @var QuestionMedia|null $primary */
        $primary = $preferThumbForImages
            ? ($thumb ?? $full ?? $poster ?? $fallback)
            : ($full ?? $thumb ?? $poster ?? $fallback);

        if (! $primary) {
            return null;
        }

        return [
            'kind' => 'image',
            'url' => $this->mediaUrlResolver->resolve($primary->path, $primary->disk),
            'full_url' => $full ? $this->mediaUrlResolver->resolve($full->path, $full->disk) : null,
            'thumb_url' => $thumb ? $this->mediaUrlResolver->resolve($thumb->path, $thumb->disk) : null,
            'poster_url' => $poster ? $this->mediaUrlResolver->resolve($poster->path, $poster->disk) : null,
            'mime_type' => $primary->mime_type,
            'bytes' => $primary->bytes,
            'width' => $full?->width ?? $primary->width,
            'height' => $full?->height ?? $primary->height,
            'variant' => $primary->variant,
        ];
    }

    protected function videoPayload(Collection $group): ?array
    {
        /** @var QuestionMedia|null $full */
        $full = $group->firstWhere('variant', 'full');
        /** @var QuestionMedia|null $fallback */
        $fallback = $group->first();
        /** @var QuestionMedia|null $primary */
        $primary = $full ?? $fallback;

        if (! $primary) {
            return null;
        }

        return [
            'kind' => 'video',
            'url' => $this->mediaUrlResolver->resolve($primary->path, $primary->disk),
            'full_url' => $this->mediaUrlResolver->resolve($primary->path, $primary->disk),
            'thumb_url' => null,
            'poster_url' => $this->mediaUrlResolver->resolve($primary->poster_path, $primary->disk),
            'mime_type' => $primary->mime_type,
            'duration_seconds' => $primary->duration_seconds,
            'bytes' => $primary->bytes,
            'width' => $primary->width,
            'height' => $primary->height,
            'variant' => $primary->variant,
        ];
    }
}
