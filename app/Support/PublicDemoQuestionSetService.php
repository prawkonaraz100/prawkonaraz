<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PublicDemoQuestionSetService
{
    /**
     * @return list<int>
     */
    public function questionIds(): array
    {
        $limit = $this->questionLimit();
        $configuredIds = $this->configuredQuestionIds($limit);

        if (count($configuredIds) >= $limit) {
            return array_slice($configuredIds, 0, $limit);
        }

        return collect($configuredIds)
            ->merge($this->fallbackQuestionIds($limit, $configuredIds))
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    public function questionLimit(): int
    {
        return max((int) config('public_demo.player_demo.question_limit', 20), 1);
    }

    public function categoryCode(): string
    {
        return strtoupper((string) config('public_demo.player_demo.category_code', 'B'));
    }

    /**
     * @return Collection<int, Question>
     */
    public function questions(): Collection
    {
        $questionIds = $this->questionIds();

        if ($questionIds === []) {
            return collect();
        }

        $questions = Question::query()
            ->with(['media', 'questionTopic', 'referenceExplanationAsset', 'explanationAnnotations'])
            ->whereIn('id', $questionIds)
            ->get()
            ->keyBy('id');

        return collect($questionIds)
            ->map(fn (int $questionId): ?Question => $questions->get($questionId))
            ->filter()
            ->values();
    }

    /**
     * @return list<int>
     */
    protected function configuredQuestionIds(int $limit): array
    {
        $externalIds = collect(config('public_demo.player_demo.question_external_ids', []))
            ->filter(fn (mixed $externalId): bool => filled($externalId))
            ->map(fn (mixed $externalId): string => (string) $externalId)
            ->unique()
            ->values();

        if ($externalIds->isEmpty()) {
            return [];
        }

        $questions = $this->baseQuery()
            ->whereIn('external_id', $externalIds->all())
            ->get(['id', 'external_id'])
            ->keyBy('external_id');

        return $externalIds
            ->map(fn (string $externalId): ?int => $questions->get($externalId)?->getKey())
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param list<int> $excludeQuestionIds
     * @return list<int>
     */
    protected function fallbackQuestionIds(int $limit, array $excludeQuestionIds): array
    {
        return $this->baseQuery()
            ->when($excludeQuestionIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludeQuestionIds))
            ->withCount('media')
            ->orderByDesc('media_count')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    protected function baseQuery(): Builder
    {
        $category = LicenseCategory::query()
            ->where('code', $this->categoryCode())
            ->first();

        return Question::query()
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('prompt')
            ->whereNotNull('correct_answer')
            ->whereNotNull('explanation')
            ->where('explanation', '<>', '')
            ->when(
                $category instanceof LicenseCategory,
                fn (Builder $query) => $query->where('license_category_id', $category->getKey()),
            );
    }
}
