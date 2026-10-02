<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use Illuminate\Support\Collection;

class PublicQuestionCategoryContentBuilder
{
    public function __construct(
        protected PublicQuestionCatalogService $catalog,
        protected QuestionTextFormatter $formatter,
        protected QuestionTopicLabelResolver $topicLabels,
        protected QuestionTopicClassifier $classifier,
    ) {}

    /**
     * Use the same delivery-ready, deduplicated questions as the public category.
     *
     * @param  Collection<int, Question>  $questions
     * @return array<string, mixed>
     */
    public function build(LicenseCategory $category, Collection $questions, bool $includeExamples = true): array
    {
        $grouped = $questions->groupBy('question_topic_id');
        $topics = QuestionTopic::query()
            ->whereIn('id', $questions->pluck('question_topic_id')->filter()->unique()->all())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $labels = $this->topicLabels->labelsForCategory($category, $topics);
        // Show both common road rules and category-specific specialist topics.
        $specialistTopics = $topics->filter(fn (QuestionTopic $topic): bool => $this->classifier->bucketLabelForKey($topic->key) === 'Pytania specjalistyczne');
        $featured = $topics->whereNotIn('id', $specialistTopics->modelKeys())->take(3)
            ->concat($specialistTopics->sortByDesc(fn (QuestionTopic $topic): int => $grouped->get($topic->id, collect())->count())->take(3));
        $remaining = $topics->whereNotIn('id', $featured->pluck('id')->all());
        $topics = $featured->concat($remaining)->values();
        $topicItems = $topics->map(function (QuestionTopic $topic, int $index) use ($grouped, $labels, $category, $includeExamples): array {
            $topicQuestions = $grouped->get($topic->getKey(), collect());

            return [
                'label' => $labels->get($topic->getKey()),
                'count' => $topicQuestions->count(),
                'examples' => $includeExamples && $index < 6
                    ? $topicQuestions->sortBy('id')->take(2)->map(fn (Question $question): array => [
                        'number' => $this->catalog->displayExternalId($question->external_id),
                        'prompt' => $this->formatter->plainText($question->prompt),
                        'url' => $this->catalog->questionUrl($question, $category),
                    ])->values()->all()
                    : [],
            ];
        })->values();

        return [
            'total' => $questions->count(),
            'boolean' => $questions->where('question_type', 'boolean')->count(),
            'single_choice' => $questions->where('question_type', 'single_choice')->count(),
            'topics' => $topicItems->all(),
            'topic_count' => $topicItems->count(),
        ];
    }
}
