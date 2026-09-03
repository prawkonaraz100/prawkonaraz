<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationAsset;
use App\Models\SharedQuestionExplanationAsset;
use Illuminate\Support\Collection;

class SharedQuestionExplanationAssetResolver
{
    /**
     * @var array<string, SharedQuestionExplanationAsset|null>
     */
    protected array $sharedAssetCache = [];

    public function preloadForQuestions(iterable $questions): void
    {
        $questionsCollection = collect($questions)
            ->filter(fn (mixed $question): bool => $question instanceof Question);

        $missingKeys = $questionsCollection
            ->filter(function (Question $question): bool {
                if ($this->hasLocalReferenceAssetOverride($question)) {
                    return false;
                }

                return filled($question->external_id);
            })
            ->map(fn (Question $question): string => $this->cacheKey($question))
            ->filter(fn (string $cacheKey): bool => ! array_key_exists($cacheKey, $this->sharedAssetCache))
            ->values();

        if ($missingKeys->isEmpty()) {
            return;
        }

        /** @var Collection<int, Question> $questionsNeedingSharedLookup */
        $questionsNeedingSharedLookup = $questionsCollection
            ->filter(function (Question $question) use ($missingKeys): bool {
                if (! filled($question->external_id) || $this->hasLocalReferenceAssetOverride($question)) {
                    return false;
                }

                return $missingKeys->contains($this->cacheKey($question));
            })
            ->values();

        if ($questionsNeedingSharedLookup->isEmpty()) {
            return;
        }

        $externalIds = $questionsNeedingSharedLookup
            ->map(fn (Question $question): string => (string) $question->external_id)
            ->unique()
            ->values();

        $sharedAssets = SharedQuestionExplanationAsset::query()
            ->with('trafficSign')
            ->where('kind', SharedQuestionExplanationAsset::KIND_REFERENCE_SIGN)
            ->whereIn('external_id', $externalIds->all())
            ->get()
            ->keyBy(fn (SharedQuestionExplanationAsset $asset): string => $this->cacheKeyFromParts(
                (string) $asset->external_id,
                (string) $asset->source_scope,
            ));

        foreach ($missingKeys as $cacheKey) {
            $this->sharedAssetCache[$cacheKey] = $sharedAssets->get($cacheKey);
        }
    }

    public function resolveReferenceAsset(Question $question): QuestionExplanationAsset|SharedQuestionExplanationAsset|null
    {
        $localAsset = $question->referenceExplanationAsset;

        if ($localAsset !== null) {
            return $localAsset;
        }

        if (! filled($question->external_id)) {
            return null;
        }

        $cacheKey = $this->cacheKey($question);

        if (! array_key_exists($cacheKey, $this->sharedAssetCache)) {
            $this->preloadForQuestions([$question]);
        }

        return $this->sharedAssetCache[$cacheKey] ?? null;
    }

    protected function hasLocalReferenceAssetOverride(Question $question): bool
    {
        return $question->referenceExplanationAsset !== null;
    }

    protected function cacheKey(Question $question): string
    {
        return $this->cacheKeyFromParts(
            (string) $question->external_id,
            SharedQuestionExplanationAsset::sourceScopeFor($question->source),
        );
    }

    protected function cacheKeyFromParts(string $externalId, string $sourceScope): string
    {
        return "{$externalId}::{$sourceScope}";
    }
}
