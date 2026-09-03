<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionTopic;
use Illuminate\Support\Collection;

class QuestionTopicAssigner
{
    protected ?Collection $topics = null;

    public function __construct(
        protected QuestionTopicClassifier $questionTopicClassifier,
        protected QuestionTopicOverrideResolver $questionTopicOverrideResolver,
    ) {}

    /**
     * @return Collection<int, QuestionTopic>
     */
    public function seedTopics(): Collection
    {
        $definitions = $this->questionTopicClassifier->persistedDefinitions();

        QuestionTopic::query()->upsert(
            $definitions,
            ['key'],
            ['name', 'description', 'sort_order', 'is_active', 'updated_at'],
        );

        QuestionTopic::query()
            ->whereNotIn('key', collect($definitions)->pluck('key')->all())
            ->update(['is_active' => false]);

        return $this->topics = QuestionTopic::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function assign(Question $question): ?QuestionTopic
    {
        $overrideClassification = $this->questionTopicOverrideResolver->resolve($question);
        $classification = $overrideClassification ?? $this->questionTopicClassifier->classify($question);
        $topic = $this->topicsByKey()->get($classification['key']);

        if (! $topic) {
            return null;
        }

        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $structureScope = $this->structureScopeForTopicKey($topic->key);
        $shouldSyncStructureScope = $overrideClassification !== null
            || ($metadata['source_site'] ?? null) === 'zdamyto.com'
            || ($question->source ?? null) === 'zdamyto'
            || blank($metadata['structure_scope'] ?? null);

        $attributes = [];

        if ($question->question_topic_id !== $topic->getKey()) {
            $attributes['question_topic_id'] = $topic->getKey();
        }

        if ($shouldSyncStructureScope && ($metadata['structure_scope'] ?? null) !== $structureScope) {
            $metadata['structure_scope'] = $structureScope;
            $attributes['metadata'] = $metadata;
        }

        if ($attributes !== []) {
            $question->forceFill($attributes)->save();
        }

        return $topic;
    }

    /**
     * @return Collection<string, QuestionTopic>
     */
    protected function topicsByKey(): Collection
    {
        return ($this->topics ?? $this->seedTopics())
            ->keyBy('key');
    }

    protected function structureScopeForTopicKey(string $topicKey): string
    {
        return $this->questionTopicClassifier->bucketLabelForKey($topicKey) === 'Pytania specjalistyczne'
            ? 'SPECJALISTYCZNY'
            : 'PODSTAWOWY';
    }
}
