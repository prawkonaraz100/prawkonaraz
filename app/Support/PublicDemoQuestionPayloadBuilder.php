<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicDemoQuestionPayloadBuilder
{
    public function __construct(
        protected QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        protected QuestionAudioPayloadBuilder $questionAudioPayloadBuilder,
        protected QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        protected QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        protected PublicQuestionExplanationLinkResolver $publicQuestionExplanationLinkResolver,
        protected SharedQuestionExplanationAssetResolver $sharedQuestionExplanationAssetResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forQuestion(
        Question $question,
        bool $includeReveal = false,
        ?string $publicExplanationUrl = null,
    ): array {
        $question->loadMissing(['media', 'questionTopic', 'referenceExplanationAsset', 'explanationAnnotations']);

        if ($includeReveal) {
            $this->sharedQuestionExplanationAssetResolver->preloadForQuestions([$question]);
        }

        return [
            'id' => $question->getKey(),
            'external_id' => $question->external_id,
            'public_explanation_url' => $publicExplanationUrl,
            'source' => $question->source,
            'shared_explanation_has_conflict' => false,
            'prompt' => $question->prompt,
            'explanation' => $includeReveal ? $question->explanation : null,
            'correct_answer' => $includeReveal ? Str::upper((string) $question->correct_answer) : null,
            'question_type' => $question->question_type,
            'structure_scope' => strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY')),
            'difficulty' => $question->difficulty,
            'points' => $question->points,
            'topic' => $question->questionTopic ? [
                'id' => $question->questionTopic->getKey(),
                'key' => $question->questionTopic->key,
                'name' => $question->questionTopic->name,
            ] : null,
            'options' => collect([
                'a' => $question->option_a,
                'b' => $question->option_b,
                'c' => $question->option_c,
            ])
                ->filter()
                ->map(fn (string $label, string $key): array => [
                    'key' => $key,
                    'label' => Str::upper($key),
                    'text' => $label,
                ])
                ->values()
                ->all(),
            'media' => $this->questionMediaPayloadBuilder->forQuestion($question->media),
            'audio' => $this->questionAudioPayloadBuilder->forQuestion($question),
            'sign_language_assets' => [],
            'explanation_asset' => $includeReveal
                ? $this->questionExplanationAssetPayloadBuilder->forAsset(
                    $this->sharedQuestionExplanationAssetResolver->resolveReferenceAsset($question),
                )
                : null,
            'explanation_annotations' => $includeReveal
                ? $this->questionExplanationAnnotationPayloadBuilder->forRuntime($question->explanationAnnotations)
                : [],
        ];
    }

    /**
     * @param  iterable<mixed>  $questions
     * @return Collection<int, string>
     */
    public function publicExplanationUrlsFor(iterable $questions): Collection
    {
        return $this->publicQuestionExplanationLinkResolver->urlsForQuestions($questions);
    }

    public function publicExplanationUrlFor(Question $question): ?string
    {
        return $this->publicQuestionExplanationLinkResolver->urlForQuestion($question);
    }

    public function optionText(Question $question, ?string $answer): ?string
    {
        if (! $answer) {
            return null;
        }

        return match (Str::lower($answer)) {
            'a' => $question->option_a,
            'b' => $question->option_b,
            'c' => $question->option_c,
            default => null,
        };
    }
}
