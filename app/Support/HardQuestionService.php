<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\User;
use App\Models\UserQuestionProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HardQuestionService
{
    /**
     * @return Collection<int, array{id: int, code: string, name: string, hard_count: int}>
     */
    public function categories(User $user): Collection
    {
        $hardCountsByCategory = $this->countsByCategory($user);

        if ($hardCountsByCategory->isEmpty()) {
            return collect();
        }

        return LicenseCategory::query()
            ->where('is_active', true)
            ->whereIn('id', $hardCountsByCategory->keys())
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LicenseCategory $category) => [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
                'hard_count' => (int) ($hardCountsByCategory[$category->getKey()] ?? 0),
            ])
            ->values();
    }

    /**
     * @return Collection<int, UserQuestionProgress>
     */
    public function entries(User $user, ?int $categoryId = null): Collection
    {
        return $this->baseQuery($user, $categoryId)
            ->get()
            ->filter(fn (UserQuestionProgress $progress) => $this->isHard($progress))
            ->map(function (UserQuestionProgress $progress): UserQuestionProgress {
                $progress->setAttribute('hardness_score', $this->hardnessScore($progress));

                return $progress;
            })
            ->sort(function (UserQuestionProgress $left, UserQuestionProgress $right): int {
                $comparisons = [
                    $right->getAttribute('hardness_score') <=> $left->getAttribute('hardness_score'),
                    $right->incorrect_count <=> $left->incorrect_count,
                    ($right->question?->difficulty ?? 0) <=> ($left->question?->difficulty ?? 0),
                    ($right->last_answered_at?->getTimestamp() ?? 0) <=> ($left->last_answered_at?->getTimestamp() ?? 0),
                ];

                foreach ($comparisons as $comparison) {
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return $left->question_id <=> $right->question_id;
            })
            ->values();
    }

    /**
     * @return array<int, int>
     */
    public function questionIds(User $user, LicenseCategory $category, int $limit): array
    {
        return $this->entries($user, $category->getKey())
            ->take($limit)
            ->pluck('question_id')
            ->map(fn (mixed $questionId) => (int) $questionId)
            ->all();
    }

    /**
     * @return Collection<int|string, int>
     */
    public function countsByCategory(User $user): Collection
    {
        return $this->entries($user)
            ->groupBy(fn (UserQuestionProgress $progress) => $progress->question?->license_category_id)
            ->map(fn (Collection $entries) => $entries->count())
            ->filter(fn (int $count, int|string|null $categoryId) => $count > 0 && $categoryId !== null);
    }

    protected function baseQuery(User $user, ?int $categoryId = null): Builder
    {
        return UserQuestionProgress::query()
            ->with(['question.media', 'question.licenseCategory', 'question.questionTopic'])
            ->where('user_id', $user->getKey())
            ->where('total_attempts', '>', 0)
            ->whereHas('question', function (Builder $query) use ($categoryId): void {
                $query
                    ->where('is_active', true)
                    ->readyForDelivery()
                    ->when($categoryId, fn (Builder $categoryQuery) => $categoryQuery->where('license_category_id', $categoryId));
            });
    }

    public function isHard(UserQuestionProgress $progress): bool
    {
        $attempts = max($progress->total_attempts, 1);
        $accuracy = $progress->correct_count / $attempts;

        return ($progress->last_quality !== null && $progress->last_quality <= 2)
            || $progress->incorrect_count > $progress->correct_count
            || ($progress->total_attempts >= 2 && $accuracy < 0.6);
    }

    protected function hardnessScore(UserQuestionProgress $progress): float
    {
        $attempts = max($progress->total_attempts, 1);
        $accuracy = $progress->correct_count / $attempts;
        $accuracyPenalty = (1 - $accuracy) * 50;
        $mistakePressure = min($progress->incorrect_count, 5) * 8;
        $qualityPenalty = $progress->last_quality !== null
            ? max(0, 3 - $progress->last_quality) * 12
            : 0;
        $difficultyBonus = ($progress->question?->difficulty ?? 0) * 3;
        $reviewPressure = $progress->next_review_at?->lessThanOrEqualTo(today()) ? 8 : 0;

        return round($accuracyPenalty + $mistakePressure + $qualityPenalty + $difficultyBonus + $reviewPressure, 1);
    }
}
