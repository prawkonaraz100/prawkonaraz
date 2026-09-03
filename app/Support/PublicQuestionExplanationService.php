<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PublicQuestionExplanationService
{
    public function __construct(
        protected QuestionTextFormatter $questionTextFormatter,
    ) {}

    /**
     * @param  Collection<int, Question>  $questions
     * @return array<string, mixed>|null
     */
    public function forQuestionGroup(Collection $questions): ?array
    {
        $explanation = $this->publishedModelForQuestionGroup($questions);

        if (! $explanation instanceof QuestionPublicExplanation) {
            return null;
        }

        $bodyHtml = $this->questionTextFormatter->richHtml($explanation->body);
        $bodyPlain = $this->questionTextFormatter->plainText($explanation->body);
        $dontConfuseWithHtml = $this->questionTextFormatter->richHtml($explanation->dont_confuse_with);
        $dontConfuseWithPlain = $this->questionTextFormatter->plainText($explanation->dont_confuse_with);
        $examTrapHtml = $this->questionTextFormatter->richHtml($explanation->exam_trap);
        $examTrapPlain = $this->questionTextFormatter->plainText($explanation->exam_trap);

        if ($bodyPlain === '') {
            return null;
        }

        $commonMistakes = collect($explanation->common_mistakes)
            ->map(function (mixed $mistake): ?array {
                if (! is_array($mistake)) {
                    return null;
                }

                $title = trim((string) ($mistake['title'] ?? ''));
                $explanation = trim((string) ($mistake['explanation'] ?? ''));

                if ($title === '' || $explanation === '') {
                    return null;
                }

                return [
                    'title' => $title,
                    'explanation' => $explanation,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $relatedQuestions = collect($explanation->related_questions)
            ->map(function (mixed $relatedQuestion): ?array {
                if (! is_array($relatedQuestion)) {
                    return null;
                }

                $externalId = trim((string) ($relatedQuestion['external_id'] ?? ''));
                $description = trim((string) ($relatedQuestion['description'] ?? ''));

                if ($externalId === '' || $description === '') {
                    return null;
                }

                return [
                    'external_id' => $externalId,
                    'description' => $description,
                ];
            })
            ->filter()
            ->unique('external_id')
            ->values()
            ->all();

        return [
            'title' => $explanation->title ?: 'Omówienie sytuacji',
            'body_raw' => $explanation->body,
            'body_html' => $bodyHtml,
            'body_plain' => $bodyPlain,
            'dont_confuse_with_raw' => $explanation->dont_confuse_with,
            'dont_confuse_with_html' => $dontConfuseWithHtml,
            'dont_confuse_with_plain' => $dontConfuseWithPlain,
            'exam_trap_raw' => $explanation->exam_trap,
            'exam_trap_html' => $examTrapHtml,
            'exam_trap_plain' => $examTrapPlain,
            'common_mistakes' => $commonMistakes,
            'related_questions' => $relatedQuestions,
            'last_reviewed_at' => $explanation->last_reviewed_at,
            'updated_at' => $explanation->updated_at,
            'author' => $this->authorPayload($explanation->displayAuthor()),
        ];
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array{source: string, body_raw: string, body_html: string, body_plain: string, dont_confuse_with_raw: string, dont_confuse_with_html: string, dont_confuse_with_plain: string, exam_trap_raw: string, exam_trap_html: string, exam_trap_plain: string, common_mistakes: list<array{title: string, explanation: string}>, related_questions: list<array{external_id: string, description: string}>, last_reviewed_at: Carbon|null, author: array{name: string, slug: string, is_public: bool}|null}
     */
    public function publicOrSystemFallbackForQuestionGroup(Collection $questions, Question $fallbackQuestion): array
    {
        $publicExplanation = $this->forQuestionGroup($questions);

        if ($publicExplanation !== null) {
            return [
                'source' => 'public',
                'body_raw' => (string) ($publicExplanation['body_raw'] ?? ''),
                'body_html' => (string) ($publicExplanation['body_html'] ?? ''),
                'body_plain' => (string) ($publicExplanation['body_plain'] ?? ''),
                'dont_confuse_with_raw' => (string) ($publicExplanation['dont_confuse_with_raw'] ?? ''),
                'dont_confuse_with_html' => (string) ($publicExplanation['dont_confuse_with_html'] ?? ''),
                'dont_confuse_with_plain' => (string) ($publicExplanation['dont_confuse_with_plain'] ?? ''),
                'exam_trap_raw' => (string) ($publicExplanation['exam_trap_raw'] ?? ''),
                'exam_trap_html' => (string) ($publicExplanation['exam_trap_html'] ?? ''),
                'exam_trap_plain' => (string) ($publicExplanation['exam_trap_plain'] ?? ''),
                'common_mistakes' => $publicExplanation['common_mistakes'] ?? [],
                'related_questions' => $publicExplanation['related_questions'] ?? [],
                'last_reviewed_at' => $publicExplanation['last_reviewed_at'] ?? null,
                'author' => $publicExplanation['author'] ?? null,
            ];
        }

        $bodyPlain = $this->questionTextFormatter->plainText($fallbackQuestion->explanation);

        return [
            'source' => 'system_fallback',
            'body_raw' => $bodyPlain,
            'body_html' => $bodyPlain !== ''
                ? $this->questionTextFormatter->richHtml($fallbackQuestion->explanation)
                : '',
            'body_plain' => $bodyPlain,
            'dont_confuse_with_raw' => '',
            'dont_confuse_with_html' => '',
            'dont_confuse_with_plain' => '',
            'exam_trap_raw' => '',
            'exam_trap_html' => '',
            'exam_trap_plain' => '',
            'common_mistakes' => [],
            'related_questions' => [],
            'last_reviewed_at' => null,
            'author' => null,
        ];
    }

    /**
     * @param  array<int, string>  $externalIds
     * @return Collection<string, Carbon>
     */
    public function lastModifiedByExternalIds(array $externalIds): Collection
    {
        $externalIds = collect($externalIds)
            ->map(fn (mixed $externalId): string => trim((string) $externalId))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($externalIds === []) {
            return collect();
        }

        return QuestionPublicExplanation::query()
            ->published()
            ->whereIn('external_id', $externalIds)
            ->get(['external_id', 'updated_at'])
            ->filter(fn (QuestionPublicExplanation $explanation): bool => $explanation->updated_at instanceof Carbon)
            ->mapWithKeys(fn (QuestionPublicExplanation $explanation): array => [
                (string) $explanation->external_id => $explanation->updated_at,
            ]);
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    protected function publishedModelForQuestionGroup(Collection $questions): ?QuestionPublicExplanation
    {
        $questionIds = $questions
            ->pluck('id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
        $externalIds = $questions
            ->pluck('external_id')
            ->map(fn (mixed $externalId): string => trim((string) $externalId))
            ->filter()
            ->unique()
            ->values();

        if ($questionIds->isEmpty() && $externalIds->isEmpty()) {
            return null;
        }

        $candidates = QuestionPublicExplanation::query()
            ->published()
            ->with([
                'author:id,name,slug,is_published,published_at',
                'reviewer:id,name,slug,is_published,published_at',
            ])
            ->where(function ($query) use ($questionIds, $externalIds): void {
                if ($questionIds->isNotEmpty()) {
                    $query->whereIn('question_id', $questionIds->all());
                }

                if ($externalIds->isNotEmpty()) {
                    $method = $questionIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('external_id', $externalIds->all());
                }
            })
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sortBy(function (QuestionPublicExplanation $explanation) use ($questionIds, $externalIds): int {
                if ($explanation->question_id !== null) {
                    $index = $questionIds->search((int) $explanation->question_id);

                    return $index === false ? 50 : (int) $index;
                }

                $index = $externalIds->search((string) $explanation->external_id);

                return $index === false ? 150 : 100 + (int) $index;
            })
            ->first();
    }

    /**
     * @return array{name: string, slug: string, is_public: bool}|null
     */
    protected function authorPayload(?ContentAuthor $author): ?array
    {
        if (! $author instanceof ContentAuthor) {
            return null;
        }

        return [
            'name' => $author->name,
            'slug' => $author->slug,
            'is_public' => $author->isPubliclyVisible(),
        ];
    }
}
