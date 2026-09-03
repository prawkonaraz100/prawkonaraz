<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationSignOverride;
use App\Models\SharedQuestionExplanationSignOverride;
use Illuminate\Support\Collection;

class QuestionExplanationSignOverrideResolver
{
    /**
     * @var array<int, Collection<int, QuestionExplanationSignOverride>>
     */
    protected array $localOverridesByQuestionId = [];

    /**
     * @var array<string, Collection<int, SharedQuestionExplanationSignOverride>>
     */
    protected array $sharedOverridesByScope = [];

    public function preloadForQuestions(iterable $questions): void
    {
        /** @var Collection<int, Question> $questions */
        $questions = collect($questions)
            ->filter(fn (mixed $question): bool => $question instanceof Question)
            ->values();

        $questionIds = $questions
            ->map(fn (Question $question): int => (int) $question->getKey())
            ->filter(fn (int $questionId): bool => ! array_key_exists($questionId, $this->localOverridesByQuestionId))
            ->values();

        if ($questionIds->isNotEmpty()) {
            $localOverrides = QuestionExplanationSignOverride::query()
                ->with('trafficSign')
                ->whereIn('question_id', $questionIds->all())
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->groupBy('question_id');

            $questionIds->each(function (int $questionId) use ($localOverrides): void {
                $this->localOverridesByQuestionId[$questionId] = $localOverrides->get($questionId, collect())->values();
            });
        }

        $questionsNeedingSharedLookup = $questions
            ->filter(fn (Question $question): bool => filled($question->external_id))
            ->filter(fn (Question $question): bool => ! array_key_exists($this->sharedScopeKey($question), $this->sharedOverridesByScope))
            ->values();

        if ($questionsNeedingSharedLookup->isEmpty()) {
            return;
        }

        $externalIds = $questionsNeedingSharedLookup
            ->map(fn (Question $question): string => (string) $question->external_id)
            ->unique()
            ->values();

        $sharedOverrides = SharedQuestionExplanationSignOverride::query()
            ->with('trafficSign')
            ->whereIn('external_id', $externalIds->all())
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (SharedQuestionExplanationSignOverride $override): string => $this->sharedScopeKeyFromParts(
                (string) $override->external_id,
                (string) $override->source_scope,
            ));

        $questionsNeedingSharedLookup->each(function (Question $question) use ($sharedOverrides): void {
            $scopeKey = $this->sharedScopeKey($question);
            $this->sharedOverridesByScope[$scopeKey] = $sharedOverrides->get($scopeKey, collect())->values();
        });
    }

    /**
     * @return Collection<int, QuestionExplanationSignOverride|SharedQuestionExplanationSignOverride>
     */
    public function forQuestion(Question $question): Collection
    {
        $this->preloadForQuestions([$question]);

        $sharedOverrides = $this->sharedOverridesForQuestion($question)
            ->filter(fn (SharedQuestionExplanationSignOverride $override): bool => $override->is_active)
            ->keyBy(fn (SharedQuestionExplanationSignOverride $override): string => $override->resolutionKey());

        $localOverrides = $this->localOverridesForQuestion($question)
            ->filter(fn (QuestionExplanationSignOverride $override): bool => $override->is_active);

        $localOverrides->each(function (QuestionExplanationSignOverride $override) use ($sharedOverrides): void {
            $sharedOverrides->put($override->resolutionKey(), $override);
        });

        return $sharedOverrides->values();
    }

    /**
     * @return array{scope:string,overrides:Collection<int, QuestionExplanationSignOverride|SharedQuestionExplanationSignOverride>}
     */
    public function editableForQuestion(Question $question): array
    {
        $this->preloadForQuestions([$question]);

        $localOverrides = $this->localOverridesForQuestion($question);
        if ($localOverrides->isNotEmpty()) {
            return [
                'scope' => 'single',
                'overrides' => $localOverrides,
            ];
        }

        $sharedOverrides = $this->sharedOverridesForQuestion($question);
        if ($sharedOverrides->isNotEmpty()) {
            return [
                'scope' => 'shared_external_id',
                'overrides' => $sharedOverrides,
            ];
        }

        return [
            'scope' => filled($question->external_id) ? 'shared_external_id' : 'single',
            'overrides' => collect(),
        ];
    }

    /**
     * @return Collection<int, QuestionExplanationSignOverride>
     */
    protected function localOverridesForQuestion(Question $question): Collection
    {
        $questionId = (int) $question->getKey();

        return $this->localOverridesByQuestionId[$questionId] ?? collect();
    }

    /**
     * @return Collection<int, SharedQuestionExplanationSignOverride>
     */
    protected function sharedOverridesForQuestion(Question $question): Collection
    {
        if (! filled($question->external_id)) {
            return collect();
        }

        return $this->sharedOverridesByScope[$this->sharedScopeKey($question)] ?? collect();
    }

    protected function sharedScopeKey(Question $question): string
    {
        return $this->sharedScopeKeyFromParts(
            (string) $question->external_id,
            SharedQuestionExplanationSignOverride::sourceScopeFor($question->source),
        );
    }

    protected function sharedScopeKeyFromParts(string $externalId, string $sourceScope): string
    {
        return "{$externalId}::{$sourceScope}";
    }
}
