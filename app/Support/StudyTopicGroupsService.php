<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\User;
use Illuminate\Support\Collection;

class StudyTopicGroupsService
{
    /**
     * @return Collection<int, array{label:string,options:array<int, array{id:int,key:string,label:string,questions_count:int,counts:array<string,int>,hero_image_url:?string,hero_image_alt:?string,hero_image_position:?string,hero_image_source:?string}>}>
     */
    public function topicGroups(LicenseCategory $category, User $user): Collection
    {
        $classifier = app(QuestionTopicClassifier::class);
        $labelResolver = app(QuestionTopicLabelResolver::class);
        $heroResolver = app(QuestionTopicHeroResolver::class);
        $topicProgressRows = Question::query()
            ->leftJoin('user_question_progress as progress', function ($join) use ($user): void {
                $join->on('progress.question_id', '=', 'questions.id')
                    ->where('progress.user_id', '=', $user->getKey());
            })
            ->leftJoin('user_incorrect_questions as mistake_list', function ($join) use ($user): void {
                $join->on('mistake_list.question_id', '=', 'questions.id')
                    ->where('mistake_list.user_id', '=', $user->getKey())
                    ->whereNull('mistake_list.removed_at');
            })
            ->select([
                'questions.question_topic_id',
                'mistake_list.id as mistake_list_id',
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
            ->whereNotNull('question_topic_id')
            ->get();

        $topicCounts = $topicProgressRows
            ->groupBy('question_topic_id')
            ->map(function (Collection $rows, int|string $topicId): object {
                $counts = [
                    'all' => $rows->count(),
                    'unanswered' => 0,
                    'incorrect' => 0,
                    'correct' => 0,
                    'memorized' => 0,
                    'mistake_list' => 0,
                ];

                foreach ($rows as $row) {
                    if ($row->mistake_list_id !== null) {
                        $counts['mistake_list']++;
                    }

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
                    'mistake_list_count' => $counts['mistake_list'],
                ];
            })
            ->values();

        $topics = QuestionTopic::query()
            ->whereIn('id', $topicCounts->pluck('question_topic_id')->all())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'hero_image_path', 'hero_image_alt', 'hero_image_position', 'sort_order'])
            ->keyBy('id');
        $topicLabels = $labelResolver->labelsForCategory($category, $topics->values());
        $topicHeroes = $heroResolver->heroesForCategory($category, $topics->values());

        $options = $topicCounts
            ->map(function ($row) use ($topics, $topicLabels, $topicHeroes, $classifier): ?array {
                $topic = $topics->get((int) $row->question_topic_id);

                if (! $topic) {
                    return null;
                }

                $topicHero = $topicHeroes->get((int) $topic->getKey());

                return [
                    'id' => $topic->getKey(),
                    'key' => $topic->key,
                    'label' => (string) $topicLabels->get((int) $topic->getKey()),
                    'questions_count' => (int) $row->questions_count,
                    'counts' => [
                        'all' => (int) $row->questions_count,
                        'unanswered' => (int) $row->unanswered_count,
                        'incorrect' => (int) $row->incorrect_count,
                        'correct' => (int) $row->correct_count,
                        'memorized' => (int) $row->memorized_count,
                        'mistake_list' => (int) $row->mistake_list_count,
                    ],
                    'hero_image_url' => $topicHero['image_url'] ?? null,
                    'hero_image_alt' => $topicHero['image_alt'] ?? null,
                    'hero_image_position' => $topicHero['image_position'] ?? null,
                    'hero_image_source' => $topicHero['source'] ?? null,
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
}
