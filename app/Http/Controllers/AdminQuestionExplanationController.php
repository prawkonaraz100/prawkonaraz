<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminQuestionExplanationUpdateRequest;
use App\Models\Question;
use App\Support\AuditLogService;
use App\Support\SharedQuestionScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class AdminQuestionExplanationController extends Controller
{
    public function update(
        AdminQuestionExplanationUpdateRequest $request,
        Question $question,
        AuditLogService $auditLogService,
        SharedQuestionScopeService $sharedQuestionScopeService,
    ): JsonResponse {
        $previousExplanation = $question->explanation;
        $explanation = $request->validated('explanation');
        $applyScope = (string) ($request->validated('apply_scope') ?? 'single');

        /** @var Collection<int, Question> $questionsToUpdate */
        $questionsToUpdate = $applyScope === 'shared_external_id' && filled($question->external_id)
            ? $sharedQuestionScopeService->questionsForSharedExternalId($question)
            : collect([$question]);

        $hadSharedConflictBeforeUpdate = $sharedQuestionScopeService->hasSharedExplanationConflict(
            $question,
            $sharedQuestionScopeService->explanationConflictMapForQuestions($questionsToUpdate),
        );

        $questionsToUpdate->each(function (Question $questionToUpdate) use ($explanation): void {
            $questionToUpdate->forceFill([
                'explanation' => $explanation,
            ])->save();
        });

        $updatedQuestionIds = $questionsToUpdate
            ->pluck('id')
            ->map(fn (mixed $id) => (int) $id)
            ->values()
            ->all();

        $auditLogService->record(
            'admin.question.explanation_updated',
            'question',
            $question->getKey(),
            $request->user(),
            [
                'source' => 'study_session_inline',
                'question_id' => $question->getKey(),
                'apply_scope' => $applyScope,
                'shared_external_id' => $question->external_id,
                'affected_question_ids' => $updatedQuestionIds,
                'affected_questions_count' => count($updatedQuestionIds),
                'shared_group_had_conflicts_before_update' => $hadSharedConflictBeforeUpdate,
                'had_explanation_before' => filled($previousExplanation),
                'has_explanation_after' => filled($explanation),
            ],
            $request,
        );

        return response()->json([
            'data' => [
                'question' => [
                    'id' => $question->getKey(),
                    'explanation' => $explanation,
                    'external_id' => $question->external_id,
                ],
                'affected_questions' => $questionsToUpdate
                    ->map(fn (Question $questionToUpdate) => [
                        'id' => $questionToUpdate->getKey(),
                        'external_id' => $questionToUpdate->external_id,
                        'explanation' => $explanation,
                    ])
                    ->values(),
                'apply_scope' => $applyScope,
                'shared_group_had_conflicts_before_update' => $hadSharedConflictBeforeUpdate,
            ],
        ]);
    }
}
