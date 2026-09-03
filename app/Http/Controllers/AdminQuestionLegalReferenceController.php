<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminQuestionLegalReferenceUpdateRequest;
use App\Models\ContentAuthor;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use App\Support\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class AdminQuestionLegalReferenceController extends Controller
{
    public function update(
        AdminQuestionLegalReferenceUpdateRequest $request,
        Question $question,
        AuditLogService $auditLogService,
    ): JsonResponse {
        $externalId = trim((string) $question->external_id);

        abort_if($externalId === '', 422, 'Pytanie nie ma external_id potrzebnego do podstawy prawnej.');

        $validated = $request->validated();
        $legalUnitId = (int) $validated['legal_unit_id'];
        $legalTopicId = isset($validated['legal_topic_id'])
            ? (int) $validated['legal_topic_id']
            : null;
        $publicNote = $validated['public_note'] ?? null;
        $questionGroup = $this->questionGroup($externalId);

        abort_if($questionGroup->isEmpty(), 422, 'Nie znaleziono aktywnych pytań dla tej podstawy prawnej.');

        $previousReference = null;

        if (! empty($validated['reference_id'])) {
            $previousReference = QuestionLegalReference::query()
                ->whereKey($validated['reference_id'])
                ->whereIn('question_id', $questionGroup->pluck('id'))
                ->first();
        }

        $verifiedAt = today();
        $verifiedBy = ContentAuthor::defaultLegalReferenceVerifier()?->getKey();
        $savedReferences = $questionGroup
            ->map(fn (Question $groupQuestion): QuestionLegalReference => QuestionLegalReference::query()->updateOrCreate(
                [
                    'question_id' => $groupQuestion->getKey(),
                    'legal_unit_id' => $legalUnitId,
                    'legal_topic_id' => $legalTopicId,
                ],
                [
                    'legal_content_page_id' => $validated['legal_content_page_id'] ?? null,
                    'relation_type' => 'direct_basis',
                    'public_note' => $publicNote,
                    'internal_note' => 'Ręczna edycja podstawy prawnej z publicznej strony pytania.',
                    'assignment_source' => QuestionLegalReference::SOURCE_MANUAL,
                    'confidence' => 85,
                    'status' => QuestionLegalReference::STATUS_VERIFIED,
                    'verified_by' => $verifiedBy,
                    'verified_at' => $verifiedAt,
                ],
            ));

        if (
            $previousReference instanceof QuestionLegalReference
            && (
                (int) $previousReference->legal_unit_id !== (int) $validated['legal_unit_id']
                || $this->nullableInt($previousReference->legal_topic_id) !== $legalTopicId
            )
        ) {
            $previousReferenceQuery = QuestionLegalReference::query()
                ->whereIn('question_id', $questionGroup->pluck('id'))
                ->where('legal_unit_id', $previousReference->legal_unit_id);

            if ($previousReference->legal_topic_id === null) {
                $previousReferenceQuery->whereNull('legal_topic_id');
            } else {
                $previousReferenceQuery->where('legal_topic_id', $previousReference->legal_topic_id);
            }

            $previousReferenceQuery->update([
                'assignment_source' => QuestionLegalReference::SOURCE_MANUAL,
                'status' => QuestionLegalReference::STATUS_REJECTED,
                'verified_by' => null,
                'verified_at' => null,
                'internal_note' => 'Ręcznie zastąpiono podstawę prawną z publicznej strony pytania.',
                'updated_at' => now(),
            ]);
        }

        $reference = $savedReferences->first();

        $auditLogService->record(
            'admin.question_legal_reference.updated',
            'question_legal_reference',
            $reference?->getKey(),
            $request->user(),
            [
                'source' => 'public_question_inline',
                'question_id' => $question->getKey(),
                'external_id' => $externalId,
                'group_question_count' => $questionGroup->count(),
                'legal_unit_id' => $legalUnitId,
                'legal_topic_id' => $legalTopicId,
                'legal_content_page_id' => $validated['legal_content_page_id'] ?? null,
                'had_legal_reference_before' => $previousReference instanceof QuestionLegalReference,
            ],
            $request,
        );

        return response()->json([
            'data' => [
                'reference' => [
                    'id' => $reference?->getKey(),
                    'public_note' => $reference?->public_note,
                    'legal_unit_id' => $reference?->legal_unit_id,
                    'legal_topic_id' => $reference?->legal_topic_id,
                    'legal_content_page_id' => $reference?->legal_content_page_id,
                    'status' => $reference?->status,
                    'verified_at' => $reference?->verified_at?->toDateString(),
                    'verified_label' => $reference?->verified_at?->format('d.m.Y'),
                ],
            ],
        ]);
    }

    /**
     * @return Collection<int, Question>
     */
    protected function questionGroup(string $externalId): Collection
    {
        $externalIds = str_starts_with($externalId, 'pj360:')
            ? [$externalId, substr($externalId, 6)]
            : [$externalId, 'pj360:'.$externalId];

        return Question::query()
            ->whereIn('external_id', array_values(array_unique($externalIds)))
            ->where('is_active', true)
            ->get();
    }

    protected function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
