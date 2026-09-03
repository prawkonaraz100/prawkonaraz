<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminQuestionPromptUpdateRequest;
use App\Models\Question;
use App\Support\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class AdminQuestionPromptController extends Controller
{
    public function update(
        AdminQuestionPromptUpdateRequest $request,
        Question $question,
        AuditLogService $auditLogService,
    ): JsonResponse {
        $previousPrompt = $question->prompt;
        $prompt = $request->validated('prompt');
        $applyScope = (string) ($request->validated('apply_scope') ?? 'single');

        /** @var Collection<int, Question> $questionsToUpdate */
        $questionsToUpdate = $applyScope === 'shared_external_id' && filled($question->external_id)
            ? Question::query()
                ->where('external_id', $question->external_id)
                ->when(
                    filled($question->source),
                    fn ($query) => $query->where('source', $question->source),
                )
                ->get()
            : collect([$question]);

        $questionsToUpdate->each(function (Question $questionToUpdate) use ($prompt): void {
            $questionToUpdate->forceFill([
                'prompt' => $prompt,
            ])->save();
        });

        $updatedQuestionIds = $questionsToUpdate
            ->pluck('id')
            ->map(fn (mixed $id) => (int) $id)
            ->values()
            ->all();

        $auditLogService->record(
            'admin.question.prompt_updated',
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
                'prompt_was_changed' => $previousPrompt !== $prompt,
                'previous_prompt_length' => mb_strlen((string) $previousPrompt),
                'new_prompt_length' => mb_strlen((string) $prompt),
            ],
            $request,
        );

        return response()->json([
            'data' => [
                'question' => [
                    'id' => $question->getKey(),
                    'prompt' => $prompt,
                    'external_id' => $question->external_id,
                ],
                'affected_questions' => $questionsToUpdate
                    ->map(fn (Question $questionToUpdate) => [
                        'id' => $questionToUpdate->getKey(),
                        'external_id' => $questionToUpdate->external_id,
                        'prompt' => $prompt,
                    ])
                    ->values(),
                'apply_scope' => $applyScope,
            ],
        ]);
    }
}
