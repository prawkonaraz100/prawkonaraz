<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationSignOverride;
use App\Models\SharedQuestionExplanationSignOverride;
use App\Models\TrafficSign;

class QuestionExplanationSignReferencePayloadBuilder
{
    /**
     * @var array<int, list<array{code:string,name:string,title:string,image_url:string,alt_text:string,url:string|null,source:string,match_text:string,placement:string,reference_key:string}>>
     */
    protected array $referencesByQuestionId = [];

    public function __construct(
        protected PublicQuestionSignReferenceService $publicQuestionSignReferenceService,
        protected QuestionExplanationSignOverrideResolver $questionExplanationSignOverrideResolver,
    ) {}

    public function preloadForQuestions(iterable $questions): void
    {
        if (! $this->enabled()) {
            return;
        }

        $questions = collect($questions)
            ->filter(fn (mixed $question): bool => $question instanceof Question)
            ->values();

        $questionsToResolve = $questions
            ->filter(fn (Question $question): bool => ! array_key_exists($question->getKey(), $this->referencesByQuestionId))
            ->values();

        if ($questionsToResolve->isEmpty()) {
            return;
        }

        $this->publicQuestionSignReferenceService->preloadForTexts(
            $questionsToResolve
                ->map(fn (Question $question): ?string => $question->explanation)
                ->all(),
        );
        $this->questionExplanationSignOverrideResolver->preloadForQuestions($questionsToResolve);

        $questionsToResolve->each(function (Question $question): void {
            $this->referencesByQuestionId[$question->getKey()] = $this->referencesForQuestion($question);
        });
    }

    /**
     * @return list<array{code:string,name:string,title:string,image_url:string,alt_text:string,url:string|null,source:string,match_text:string,placement:string,reference_key:string}>
     */
    public function forQuestion(Question $question): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $this->preloadForQuestions([$question]);

        return $this->referencesByQuestionId[$question->getKey()] ?? [];
    }

    public function enabled(): bool
    {
        return (bool) config('study.explanation_sign_references_enabled', false);
    }

    /**
     * @return list<array{code:string,name:string,title:string,image_url:string,alt_text:string,url:string|null,source:string,match_text:string,placement:string,reference_key:string}>
     */
    protected function referencesForQuestion(Question $question): array
    {
        $automaticReferences = collect($this->publicQuestionSignReferenceService->cardsByCodesForText($question->explanation))
            ->filter(fn (array $card): bool => filled($card['image_url'] ?? null))
            ->map(fn (array $card, string $detectedCode): array => [
                'code' => (string) $card['code'],
                'name' => (string) ($card['name'] ?? ''),
                'title' => (string) ($card['title'] ?? $card['code']),
                'image_url' => (string) $card['image_url'],
                'alt_text' => (string) ($card['image_alt'] ?? $card['title'] ?? $card['code']),
                'url' => filled($card['url'] ?? null) ? (string) $card['url'] : null,
                'source' => 'automatic',
                'match_text' => $detectedCode,
                'placement' => 'replace',
                'reference_key' => 'automatic:'.mb_strtoupper($detectedCode),
            ]);

        $manualAdditions = collect();

        $this->questionExplanationSignOverrideResolver
            ->forQuestion($question)
            ->each(function (QuestionExplanationSignOverride|SharedQuestionExplanationSignOverride $override) use ($automaticReferences, $manualAdditions): void {
                $source = $override instanceof QuestionExplanationSignOverride
                    ? 'local_override'
                    : 'shared_override';

                if ($override->action === QuestionExplanationSignOverride::ACTION_HIDE) {
                    $automaticReferences->forget($this->signCodeKey((string) $override->detected_code));

                    return;
                }

                $signReference = $this->referenceForTrafficSign($override->trafficSign, $source, $override);
                if ($signReference === null) {
                    return;
                }

                if ($override->action === QuestionExplanationSignOverride::ACTION_REPLACE) {
                    $signReference['match_text'] = (string) $override->detected_code;
                    $signReference['placement'] = 'replace';
                    $automaticReferences->put($this->signCodeKey((string) $override->detected_code), $signReference);

                    return;
                }

                if ($override->action === QuestionExplanationSignOverride::ACTION_ADD) {
                    $signReference['match_text'] = (string) $override->anchor_text;
                    $signReference['placement'] = 'after';
                    $manualAdditions->push($signReference);
                }
            });

        $automaticOrder = $this->publicQuestionSignReferenceService->codesFromText($question->explanation);

        return collect($automaticOrder)
            ->map(function (string $code) use ($automaticReferences): ?array {
                $reference = $automaticReferences->get($this->signCodeKey($code));

                if (! is_array($reference)) {
                    return null;
                }

                $reference['match_text'] = $code;

                return $reference;
            })
            ->filter()
            ->concat($manualAdditions)
            ->values()
            ->all();
    }

    /**
     * @return array{code:string,name:string,title:string,image_url:string,alt_text:string,url:string|null,source:string,match_text:string,placement:string,reference_key:string}|null
     */
    protected function referenceForTrafficSign(
        ?TrafficSign $sign,
        string $source,
        QuestionExplanationSignOverride|SharedQuestionExplanationSignOverride $override,
    ): ?array {
        if (! $sign || ! $sign->isPubliclyVisible()) {
            return null;
        }

        $card = $this->publicQuestionSignReferenceService->cardForTrafficSign($sign);
        if (! filled($card['image_url'] ?? null)) {
            return null;
        }

        return [
            'code' => (string) $card['code'],
            'name' => (string) ($card['name'] ?? ''),
            'title' => (string) ($card['title'] ?? $card['code']),
            'image_url' => (string) $card['image_url'],
            'alt_text' => (string) ($card['image_alt'] ?? $card['title'] ?? $card['code']),
            'url' => filled($card['url'] ?? null) ? (string) $card['url'] : null,
            'source' => $source,
            'match_text' => '',
            'placement' => 'replace',
            'reference_key' => $source.':'.$override->getKey(),
        ];
    }

    protected function signCodeKey(string $code): string
    {
        return mb_strtoupper(trim($code));
    }
}
