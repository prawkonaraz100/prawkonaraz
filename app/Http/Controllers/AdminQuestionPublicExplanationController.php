<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminQuestionPublicExplanationUpdateRequest;
use App\Models\ContentAuthor;
use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use App\Support\AuditLogService;
use App\Support\QuestionTextFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AdminQuestionPublicExplanationController extends Controller
{
    public function update(
        AdminQuestionPublicExplanationUpdateRequest $request,
        Question $question,
        AuditLogService $auditLogService,
        QuestionTextFormatter $questionTextFormatter,
    ): JsonResponse {
        $externalId = trim((string) $question->external_id);

        abort_if($externalId === '', 422, 'Pytanie nie ma external_id potrzebnego do publicznego wyjaśnienia.');

        $body = (string) $request->validated('body');
        $existingExplanation = QuestionPublicExplanation::query()
            ->where('external_id', $externalId)
            ->first();
        $examTrap = $request->has('exam_trap')
            ? trim((string) $request->validated('exam_trap'))
            : (string) ($existingExplanation?->exam_trap ?? '');
        $dontConfuseWith = $request->has('dont_confuse_with')
            ? trim((string) $request->validated('dont_confuse_with'))
            : (string) ($existingExplanation?->dont_confuse_with ?? '');
        $commonMistakes = $request->has('common_mistakes')
            ? $this->normalizedCommonMistakes($request->validated('common_mistakes') ?? [])
            : $this->normalizedCommonMistakes($existingExplanation?->common_mistakes ?? []);
        $publishedAt = $existingExplanation?->published_at ?? now();
        $lastReviewedAt = today();
        $defaultAuthor = $this->defaultQuestionExplanationAuthor();
        $authorId = $existingExplanation?->author_id ?: $defaultAuthor?->getKey();
        $reviewerId = $existingExplanation?->reviewer_id ?: $defaultAuthor?->getKey();

        $explanation = QuestionPublicExplanation::query()->updateOrCreate(
            ['external_id' => $externalId],
            [
                'question_id' => null,
                'title' => 'Omówienie sytuacji',
                'body' => $body,
                'dont_confuse_with' => $dontConfuseWith !== '' ? $dontConfuseWith : null,
                'exam_trap' => $examTrap !== '' ? $examTrap : null,
                'common_mistakes' => $commonMistakes !== [] ? $commonMistakes : null,
                'status' => QuestionPublicExplanation::STATUS_PUBLISHED,
                'author_id' => $authorId,
                'reviewer_id' => $reviewerId,
                'published_at' => $publishedAt,
                'last_reviewed_at' => $lastReviewedAt,
                'source_note' => $existingExplanation?->source_note
                    ?: 'Ręczna edycja publicznego wyjaśnienia z publicznej strony pytania.',
                'internal_note' => $existingExplanation?->internal_note
                    ?: 'Utworzone lub zaktualizowane ręcznie z publicznej strony pytania. Nie wgrywać do questions.explanation.',
            ],
        );

        $auditLogService->record(
            'admin.question_public_explanation.updated',
            'question_public_explanation',
            $explanation->getKey(),
            $request->user(),
            [
                'source' => 'public_question_inline',
                'question_id' => $question->getKey(),
                'external_id' => $externalId,
                'had_public_explanation_before' => $existingExplanation instanceof QuestionPublicExplanation,
                'previous_status' => $existingExplanation?->status,
                'word_count' => $this->wordCount($body),
                'dont_confuse_with_word_count' => $this->wordCount($dontConfuseWith),
                'exam_trap_word_count' => $this->wordCount($examTrap),
                'common_mistakes_count' => count($commonMistakes),
            ],
            $request,
        );

        $bodyHtml = $questionTextFormatter->richHtml($explanation->body);
        $bodyPlain = $questionTextFormatter->plainText($explanation->body);
        $dontConfuseWithHtml = $questionTextFormatter->richHtml($explanation->dont_confuse_with);
        $dontConfuseWithPlain = $questionTextFormatter->plainText($explanation->dont_confuse_with);
        $examTrapHtml = $questionTextFormatter->richHtml($explanation->exam_trap);
        $examTrapPlain = $questionTextFormatter->plainText($explanation->exam_trap);
        $explanation->loadMissing([
            'author:id,name,slug,is_published,published_at',
            'reviewer:id,name,slug,is_published,published_at',
        ]);

        return response()->json([
            'data' => [
                'question' => [
                    'id' => $question->getKey(),
                    'external_id' => $externalId,
                    'system_explanation' => $question->explanation,
                ],
                'explanation' => [
                    'id' => $explanation->getKey(),
                    'external_id' => $explanation->external_id,
                    'body' => $explanation->body,
                    'body_html' => $bodyHtml,
                    'body_plain' => $bodyPlain,
                    'dont_confuse_with' => $explanation->dont_confuse_with,
                    'dont_confuse_with_html' => $dontConfuseWithHtml,
                    'dont_confuse_with_plain' => $dontConfuseWithPlain,
                    'exam_trap' => $explanation->exam_trap,
                    'exam_trap_html' => $examTrapHtml,
                    'exam_trap_plain' => $examTrapPlain,
                    'common_mistakes' => $this->normalizedCommonMistakes($explanation->common_mistakes ?? []),
                    'status' => $explanation->status,
                    'published_at' => $explanation->published_at?->toIso8601String(),
                    'last_reviewed_at' => $explanation->last_reviewed_at?->toDateString(),
                    'last_reviewed_label' => $explanation->last_reviewed_at?->format('d.m.Y'),
                    'author' => $this->authorPayload($explanation->displayAuthor()),
                ],
            ],
        ]);
    }

    /**
     * @return list<array{title: string, explanation: string}>
     */
    protected function normalizedCommonMistakes(mixed $commonMistakes): array
    {
        return collect(is_array($commonMistakes) ? $commonMistakes : [])
            ->filter(fn (mixed $mistake): bool => is_array($mistake))
            ->map(fn (array $mistake): array => [
                'title' => trim((string) ($mistake['title'] ?? '')),
                'explanation' => trim((string) ($mistake['explanation'] ?? '')),
            ])
            ->filter(fn (array $mistake): bool => $mistake['title'] !== '' && $mistake['explanation'] !== '')
            ->values()
            ->all();
    }

    protected function wordCount(string $body): int
    {
        return Str::of($body)->matchAll('/[\p{L}\p{N}]+(?:[-\/][\p{L}\p{N}]+)*/u')->count();
    }

    protected function defaultQuestionExplanationAuthor(): ?ContentAuthor
    {
        return ContentAuthor::query()
            ->published()
            ->where('slug', 'jakub-wisniewski')
            ->first();
    }

    /**
     * @return array{name: string, slug: string, is_public: bool, url: string|null}|null
     */
    protected function authorPayload(?ContentAuthor $author): ?array
    {
        if (! $author instanceof ContentAuthor) {
            return null;
        }

        $isPublic = $author->isPubliclyVisible();

        return [
            'name' => $author->name,
            'slug' => $author->slug,
            'is_public' => $isPublic,
            'url' => $isPublic ? route('content-authors.show', $author->slug) : null,
        ];
    }
}
