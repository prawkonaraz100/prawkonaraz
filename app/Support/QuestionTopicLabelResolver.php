<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryLabel;
use Illuminate\Support\Collection;

class QuestionTopicLabelResolver
{
    public function __construct(
        protected QuestionTopicClassifier $classifier,
    ) {}

    public function labelFor(LicenseCategory $category, QuestionTopic $topic): string
    {
        return (string) $this->labelsForCategory($category, collect([$topic]))
            ->get((int) $topic->getKey(), $this->defaultLabelFor($topic));
    }

    /**
     * @param  Collection<int, QuestionTopic>  $topics
     * @return Collection<int, string>
     */
    public function labelsForCategory(LicenseCategory $category, Collection $topics): Collection
    {
        $topicsById = $topics
            ->filter(fn (QuestionTopic $topic): bool => $topic->getKey() !== null)
            ->keyBy(fn (QuestionTopic $topic): int => (int) $topic->getKey());

        if ($topicsById->isEmpty()) {
            return collect();
        }

        $customLabels = QuestionTopicCategoryLabel::query()
            ->where('license_category_id', $category->getKey())
            ->whereIn('question_topic_id', $topicsById->keys()->all())
            ->where('is_active', true)
            ->get(['question_topic_id', 'display_name'])
            ->mapWithKeys(fn (QuestionTopicCategoryLabel $label): array => [
                (int) $label->question_topic_id => trim((string) $label->display_name),
            ])
            ->filter(fn (string $displayName): bool => $displayName !== '');

        return $topicsById->mapWithKeys(function (QuestionTopic $topic, int $topicId) use ($customLabels): array {
            return [
                $topicId => (string) ($customLabels->get($topicId) ?: $this->defaultLabelFor($topic)),
            ];
        });
    }

    public function defaultLabelFor(QuestionTopic $topic): string
    {
        $classifierLabel = $this->classifier->displayLabelForKey((string) $topic->key);

        if ($classifierLabel !== '' && $classifierLabel !== (string) $topic->key) {
            return $classifierLabel;
        }

        if (filled($topic->name)) {
            return (string) $topic->name;
        }

        return (string) $topic->key;
    }
}
