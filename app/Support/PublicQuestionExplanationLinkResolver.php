<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Support\Collection;

class PublicQuestionExplanationLinkResolver
{
    public function __construct(
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
    ) {}

    /**
     * Return public explanation URLs keyed by the local question ID.
     *
     * The lookup is deliberately batched because a learning session can preload
     * many questions at once. The frontend only renders the link after an
     * explanation is visible; this resolver verifies that its public question
     * card has a canonical URL.
     *
     * @param  iterable<mixed>  $questions
     * @return Collection<int, string>
     */
    public function urlsForQuestions(iterable $questions): Collection
    {
        $questions = collect($questions)
            ->filter(fn (mixed $question): bool => $question instanceof Question)
            ->unique(fn (Question $question): int => (int) $question->getKey())
            ->values();

        if ($questions->isEmpty()) {
            return collect();
        }

        $externalIds = $questions
            ->pluck('external_id')
            ->map(fn (mixed $externalId): string => trim((string) $externalId))
            ->filter()
            ->unique()
            ->values();

        if ($externalIds->isEmpty()) {
            return collect();
        }

        $canonicalQuestions = $this->publicQuestionCatalogService
            ->canonicalQuestionsByExternalIds($externalIds);

        return $questions
            ->mapWithKeys(function (Question $question) use ($canonicalQuestions): array {
                $externalId = trim((string) $question->external_id);
                $canonicalQuestion = $canonicalQuestions->get($externalId);

                if (! $canonicalQuestion instanceof Question) {
                    return [];
                }

                return [
                    (int) $question->getKey() => $this->publicQuestionCatalogService->questionUrl($canonicalQuestion),
                ];
            });
    }

    public function urlForQuestion(Question $question): ?string
    {
        return $this->urlsForQuestions([$question])->get((int) $question->getKey());
    }
}
