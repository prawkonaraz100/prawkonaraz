<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionLearningOrderSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class QuestionLearningOrderService
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_FALLBACK = 'fallback';

    public function __construct(
        protected IncorrectQuestionListService $incorrectQuestionListService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function orderedQuestionIds(User $user, LicenseCategory $category, array $filters): QuestionLearningOrderResult
    {
        $topicId = $this->topicId($filters);
        $questionScope = $this->questionScope($filters);

        if ($topicId === null) {
            return $this->fallbackResult($this->fallbackQuestionIds(
                $this->baseQuestionQuery($category, null, $questionScope, $user, $this->questionStatus($filters)),
            ));
        }

        $eligibleIds = $this->fallbackQuestionIds(
            $this->baseQuestionQuery($category, $topicId, $questionScope),
        );

        $currentIds = $this->fallbackQuestionIds(
            $this->baseQuestionQuery($category, $topicId, $questionScope, $user, $this->questionStatus($filters)),
        );

        $orderSet = QuestionLearningOrderSet::query()
            ->where('license_category_id', $category->getKey())
            ->where('question_topic_id', $topicId)
            ->where('question_scope', $questionScope)
            ->where('status', QuestionLearningOrderSet::STATUS_ACTIVE)
            ->where('active_marker', QuestionLearningOrderSet::ACTIVE_MARKER)
            ->with('items')
            ->first();

        if (! $orderSet) {
            return $this->fallbackResult($currentIds);
        }

        $eligibleLookup = array_fill_keys($eligibleIds, true);
        $currentLookup = array_fill_keys($currentIds, true);
        $manualQuestionIds = [];
        $staleCount = 0;
        $filteredOutCount = 0;

        foreach ($orderSet->items as $item) {
            $questionId = (int) $item->question_id;

            if (isset($currentLookup[$questionId])) {
                $manualQuestionIds[] = $questionId;

                continue;
            }

            if (isset($eligibleLookup[$questionId])) {
                $filteredOutCount++;

                continue;
            }

            $staleCount++;
        }

        $manualLookup = array_fill_keys($manualQuestionIds, true);
        $missingQuestionIds = array_values(array_filter(
            $currentIds,
            fn (int $questionId): bool => ! isset($manualLookup[$questionId]),
        ));

        return new QuestionLearningOrderResult(
            questionIds: array_values(array_merge($manualQuestionIds, $missingQuestionIds)),
            source: self::SOURCE_MANUAL,
            orderSetId: (int) $orderSet->getKey(),
            orderSetVersion: (int) $orderSet->version,
            manualCount: count($manualQuestionIds),
            fallbackCount: count($missingQuestionIds),
            staleCount: $staleCount,
            missingCount: count($missingQuestionIds),
            filteredOutCount: $filteredOutCount,
        );
    }

    /**
     * @param  array<int, int>  $questionIds
     */
    protected function fallbackResult(array $questionIds): QuestionLearningOrderResult
    {
        return new QuestionLearningOrderResult(
            questionIds: $questionIds,
            source: self::SOURCE_FALLBACK,
            fallbackCount: count($questionIds),
        );
    }

    /**
     * @return array<int, int>
     */
    public function fallbackQuestionIds(Builder $query): array
    {
        return $query
            ->orderBy('difficulty')
            ->orderByDesc('published_at')
            ->orderBy('questions.id')
            ->pluck('questions.id')
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->values()
            ->all();
    }

    public function baseQuestionQuery(
        LicenseCategory $category,
        ?int $topicId,
        string $questionScope = 'all',
        ?User $user = null,
        ?string $questionStatus = null,
    ): Builder {
        $query = Question::query()
            ->where('license_category_id', $category->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->when($topicId, fn (Builder $topicQuery) => $topicQuery->where('question_topic_id', $topicId));

        $this->applyQuestionScopeFilter($query, $questionScope);

        if ($user) {
            $this->applyQuestionStatusFilter($query, $user, $questionStatus);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function topicId(array $filters): ?int
    {
        return isset($filters['question_topic_id']) && $filters['question_topic_id']
            ? (int) $filters['question_topic_id']
            : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function questionScope(array $filters): string
    {
        return in_array(($filters['question_scope'] ?? 'all'), ['all', 'basic', 'specialist'], true)
            ? (string) ($filters['question_scope'] ?? 'all')
            : 'all';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function questionStatus(array $filters): ?string
    {
        return isset($filters['question_status']) && $filters['question_status'] !== ''
            ? (string) $filters['question_status']
            : null;
    }

    protected function applyQuestionScopeFilter(Builder $query, string $questionScope): void
    {
        if ($questionScope === 'specialist') {
            $query->where('metadata->structure_scope', 'SPECJALISTYCZNY');

            return;
        }

        if ($questionScope === 'basic') {
            $query->where(function (Builder $scopeQuery): void {
                $scopeQuery
                    ->whereNull('metadata->structure_scope')
                    ->orWhere('metadata->structure_scope', '!=', 'SPECJALISTYCZNY');
            });
        }
    }

    protected function applyQuestionStatusFilter(Builder $query, User $user, ?string $questionStatus): void
    {
        if ($questionStatus === null || $questionStatus === '' || $questionStatus === 'all') {
            return;
        }

        if ($questionStatus === 'mistake_list') {
            $this->incorrectQuestionListService->applyActiveQuestionFilter($query, (int) $user->getKey());

            return;
        }

        $progressAlias = 'status_progress';

        $query
            ->leftJoin("user_question_progress as {$progressAlias}", function ($join) use ($progressAlias, $user): void {
                $join->on("{$progressAlias}.question_id", '=', 'questions.id')
                    ->where("{$progressAlias}.user_id", '=', $user->getKey());
            })
            ->select('questions.*');

        $masteredSql = $this->masteredProgressSql($progressAlias);

        if ($questionStatus === 'unanswered') {
            $query->where(function (Builder $statusQuery) use ($progressAlias): void {
                $statusQuery
                    ->whereNull("{$progressAlias}.id")
                    ->orWhere("{$progressAlias}.total_attempts", '<=', 0);
            });

            return;
        }

        if ($questionStatus === 'memorized') {
            $query
                ->where("{$progressAlias}.total_attempts", '>', 0)
                ->whereRaw($masteredSql, [today()->toDateString()]);

            return;
        }

        if ($questionStatus === 'incorrect') {
            $query
                ->where("{$progressAlias}.total_attempts", '>', 0)
                ->where("{$progressAlias}.incorrect_count", '>', 0)
                ->whereRaw("not {$masteredSql}", [today()->toDateString()]);

            return;
        }

        if ($questionStatus === 'correct') {
            $query
                ->where("{$progressAlias}.total_attempts", '>', 0)
                ->where("{$progressAlias}.correct_count", '>', 0)
                ->where("{$progressAlias}.incorrect_count", '=', 0)
                ->whereRaw("not {$masteredSql}", [today()->toDateString()]);
        }
    }

    protected function masteredProgressSql(string $alias): string
    {
        return sprintf(
            '(coalesce(%1$s.repetitions, 0) >= %2$d and coalesce(%1$s.correct_streak, 0) >= %3$d and coalesce(%1$s.last_quality, 0) >= %4$d and %1$s.next_review_at > ?)',
            $alias,
            QuestionProgressManager::MASTERED_MIN_REPETITIONS,
            QuestionProgressManager::MASTERED_MIN_CORRECT_STREAK,
            QuestionProgressManager::MASTERED_MIN_LAST_QUALITY,
        );
    }
}
