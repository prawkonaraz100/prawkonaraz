<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionSignLanguageAsset;
use App\Models\QuestionTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PjmQuestionProgressService
{
    /**
     * @return array<string, mixed>
     */
    public function progressForCategory(LicenseCategory $category, User $user): array
    {
        $topicGroups = $this->topicGroups($category, $user);
        $topicOptions = $topicGroups
            ->flatMap(fn (array $group): array => $group['options'])
            ->values();

        $totalQuestions = (int) $topicOptions->sum(fn (array $option): int => (int) $option['questions_count']);
        $unansweredQuestions = (int) $topicOptions->sum(fn (array $option): int => (int) $option['counts']['unanswered']);
        $incorrectQuestions = (int) $topicOptions->sum(fn (array $option): int => (int) $option['counts']['incorrect']);
        $correctQuestions = (int) $topicOptions->sum(fn (array $option): int => (int) $option['counts']['correct']);
        $memorizedQuestions = (int) $topicOptions->sum(fn (array $option): int => (int) $option['counts']['memorized']);
        $answeredQuestions = max(0, $totalQuestions - $unansweredQuestions);
        $currentTopic = $topicOptions->first(fn (array $option): bool => (int) $option['counts']['unanswered'] > 0);
        $reviewTopic = $topicOptions->first(fn (array $option): bool => (int) $option['counts']['incorrect'] > 0);

        return [
            'total_questions' => $totalQuestions,
            'answered_questions' => $answeredQuestions,
            'unanswered_questions' => $unansweredQuestions,
            'incorrect_questions' => $incorrectQuestions,
            'correct_questions' => $correctQuestions,
            'memorized_questions' => $memorizedQuestions,
            'progress_percent' => $this->percent($answeredQuestions, $totalQuestions),
            'current_topic_id' => $currentTopic['id'] ?? null,
            'review_topic_id' => $reviewTopic['id'] ?? null,
            'topic_groups' => $topicGroups->values()->all(),
        ];
    }

    /**
     * @return Collection<int, array{label:string,options:array<int, array{id:int,key:string,label:string,questions_count:int,counts:array<string,int>,answered_count:int,progress_percent:float}>}>
     */
    public function topicGroups(LicenseCategory $category, User $user): Collection
    {
        $classifier = app(QuestionTopicClassifier::class);
        $labelResolver = app(QuestionTopicLabelResolver::class);
        $topicProgressRows = $this->topicProgressRows($category, $user);

        $topicCounts = $topicProgressRows
            ->groupBy('question_topic_id')
            ->map(function (Collection $rows, int|string $topicId): object {
                $counts = [
                    'all' => $rows->count(),
                    'unanswered' => 0,
                    'incorrect' => 0,
                    'correct' => 0,
                    'memorized' => 0,
                ];

                foreach ($rows as $row) {
                    $bucket = QuestionProgressManager::progressBucketFromSnapshot(
                        $row->total_attempts,
                        $row->correct_count,
                        $row->incorrect_count,
                        $row->repetitions,
                        $row->correct_streak,
                        $row->last_quality,
                        $row->next_review_at,
                    );

                    $counts[$bucket]++;
                }

                return (object) [
                    'question_topic_id' => (int) $topicId,
                    'questions_count' => $counts['all'],
                    'unanswered_count' => $counts['unanswered'],
                    'incorrect_count' => $counts['incorrect'],
                    'correct_count' => $counts['correct'],
                    'memorized_count' => $counts['memorized'],
                ];
            })
            ->values();

        if ($topicCounts->isEmpty()) {
            return collect();
        }

        $topics = QuestionTopic::query()
            ->whereIn('id', $topicCounts->pluck('question_topic_id')->all())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'sort_order'])
            ->keyBy('id');
        $topicLabels = $labelResolver->labelsForCategory($category, $topics->values());

        $options = $topicCounts
            ->map(function ($row) use ($topics, $topicLabels, $classifier): ?array {
                $topic = $topics->get((int) $row->question_topic_id);

                if (! $topic) {
                    return null;
                }

                $questionsCount = (int) $row->questions_count;
                $unansweredCount = (int) $row->unanswered_count;
                $answeredCount = max(0, $questionsCount - $unansweredCount);

                return [
                    'id' => $topic->getKey(),
                    'key' => $topic->key,
                    'label' => (string) $topicLabels->get((int) $topic->getKey()),
                    'questions_count' => $questionsCount,
                    'counts' => [
                        'all' => $questionsCount,
                        'unanswered' => $unansweredCount,
                        'incorrect' => (int) $row->incorrect_count,
                        'correct' => (int) $row->correct_count,
                        'memorized' => (int) $row->memorized_count,
                    ],
                    'answered_count' => $answeredCount,
                    'progress_percent' => $this->percent($answeredCount, $questionsCount),
                    'sort_order' => $classifier->learningSortOrderForKey($topic->key, (int) $topic->sort_order),
                ];
            })
            ->filter()
            ->sortBy([
                ['sort_order', 'asc'],
                ['label', 'asc'],
            ])
            ->map(function (array $option): array {
                unset($option['sort_order']);

                return $option;
            })
            ->values();

        if ($options->isEmpty()) {
            return collect();
        }

        return $options
            ->groupBy(fn (array $option): string => $classifier->bucketLabelForKey($option['key']))
            ->map(fn (Collection $groupOptions, string $label): array => [
                'label' => $label,
                'options' => $groupOptions->values()->all(),
            ])
            ->values();
    }

    private function topicProgressRows(LicenseCategory $category, User $user): Collection
    {
        $query = Question::query()
            ->leftJoin('user_question_progress as progress', function ($join) use ($user): void {
                $join->on('progress.question_id', '=', 'questions.id')
                    ->where('progress.user_id', '=', $user->getKey());
            })
            ->select([
                'questions.question_topic_id',
                'progress.total_attempts',
                'progress.correct_count',
                'progress.incorrect_count',
                'progress.repetitions',
                'progress.correct_streak',
                'progress.last_quality',
                'progress.next_review_at',
            ])
            ->where('license_category_id', $category->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('question_topic_id');

        $this->applyPjmQuestionAssetFilter($query);

        return $query->get();
    }

    private function applyPjmQuestionAssetFilter(Builder $query): void
    {
        $query
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->whereExists(function ($assetQuery): void {
                $assetQuery
                    ->selectRaw('1')
                    ->from('question_sign_language_assets as pjm_assets')
                    ->whereColumn('pjm_assets.external_id', 'questions.external_id')
                    ->where('pjm_assets.asset_role', QuestionSignLanguageAsset::ROLE_QUESTION)
                    ->where('pjm_assets.is_active', true)
                    ->whereIn('pjm_assets.processing_status', [
                        QuestionSignLanguageAsset::STATUS_READY,
                        QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
                    ]);
            });
    }

    private function percent(int $covered, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($covered / $total) * 100, 2);
    }
}
