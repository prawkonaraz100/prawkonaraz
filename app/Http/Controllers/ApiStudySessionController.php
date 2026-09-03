<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiStudySessionStoreRequest;
use App\Models\Question;
use App\Models\StudySession;
use App\Support\ReviewTrainerCompletionSummaryService;
use App\Support\StudyContextService;
use App\Support\StudySessionApiPayloadBuilder;
use App\Support\StudySessionManager;
use App\Support\StudyTopicCompletionRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiStudySessionController extends Controller
{
    public function store(
        ApiStudySessionStoreRequest $request,
        StudyContextService $studyContextService,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $category = $studyContextService
            ->visibleCategoriesQuery()
            ->findOrFail($request->integer('category_id'));

        $studyContextService->assertUserCanUseCategory($request->user(), $category);

        $activeSession = $studySessionManager->activeSessionForUser($request->user());
        if ($activeSession && ! $request->boolean('replace_active_session', true)) {
            $activeSession->loadMissing('answers', 'licenseCategory');

            return response()->json([
                'message' => 'Masz aktywna sesje. Potwierdz zastapienie, zeby rozpoczac nowa.',
                'error_code' => 'ACTIVE_SESSION_EXISTS',
                'data' => [
                    'active_session' => $payloadBuilder->session($activeSession),
                    'category' => $payloadBuilder->category($activeSession->licenseCategory),
                ],
            ], 409);
        }

        $studySession = $studySessionManager->start(
            $request->user(),
            $category,
            (string) $request->string('mode'),
            $request->integer('question_count', 20),
            [
                'question_topic_id' => $request->integer('question_topic_id') ?: null,
                'question_scope' => (string) ($request->string('question_scope')->value() ?: 'all'),
                'question_status' => (string) ($request->string('question_status')->value() ?: 'all'),
                'randomize_order' => $request->boolean('randomize_order'),
                'ui_shell' => $request->string('ui_shell')->value(),
                'question_count_strategy' => $request->string('question_count_strategy')->value(),
            ],
        );

        $studySession->load('answers', 'licenseCategory');
        $includeVisualExplanations = $studySession->mode !== StudySessionManager::MODE_SR_REVIEW
            || $studySession->status === 'completed';

        return response()->json([
            'data' => [
                'session' => $payloadBuilder->session($studySession),
                'category' => $payloadBuilder->category($studySession->licenseCategory),
                'questions' => $studySessionManager->orderedQuestions($studySession)
                    ->map(fn ($question) => $payloadBuilder->question(
                        $question,
                        includeVisualExplanations: $includeVisualExplanations,
                    ))
                    ->values(),
            ],
        ], 201);
    }

    public function current(
        Request $request,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $studySession = $studySessionManager->activeSessionForUser($request->user());

        if (! $studySession) {
            return response()->json([
                'data' => [
                    'session' => null,
                    'category' => null,
                    'progress' => null,
                    'current_question' => null,
                    'review_completion' => null,
                    'questions' => [],
                ],
            ]);
        }

        return response()->json([
            'data' => $payloadBuilder->sessionDetails($studySession),
        ]);
    }

    public function currentQuestions(
        Request $request,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:20'],
            'ids.*' => ['required', 'integer'],
        ]);
        $studySession = $this->activeSessionOrAbort($request, $studySessionManager);
        $requestedIds = collect($data['ids'])
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'questions' => $this->questionsForSession($studySession, $requestedIds, $studySessionManager, $payloadBuilder),
            ],
        ]);
    }

    public function currentQuestion(
        Request $request,
        Question $question,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $studySession = $this->activeSessionOrAbort($request, $studySessionManager);

        return response()->json([
            'data' => [
                'question' => $this->questionsForSession(
                    $studySession,
                    [(int) $question->getKey()],
                    $studySessionManager,
                    $payloadBuilder,
                )[0] ?? null,
            ],
        ]);
    }

    public function show(
        Request $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);
        abort_if(
            $studySessionManager->retireReviewSessionIfOutsideActiveScope($studySession, $request->user()),
            409,
            'Ten trening pamięci jest poza aktualną kategorią. Uruchom nowy plan.',
        );

        return response()->json([
            'data' => $payloadBuilder->sessionDetails($studySession),
        ]);
    }

    public function complete(
        Request $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
        ReviewTrainerCompletionSummaryService $reviewCompletionSummaryService,
        StudyTopicCompletionRecordService $topicCompletionRecordService,
    ): JsonResponse {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);
        abort_if(
            $studySessionManager->retireReviewSessionIfOutsideActiveScope($studySession, $request->user()),
            409,
            'Ten trening pamięci jest poza aktualną kategorią. Uruchom nowy plan.',
        );

        return $this->completeSession(
            $studySession,
            $studySessionManager,
            $payloadBuilder,
            $reviewCompletionSummaryService,
            $topicCompletionRecordService,
        );
    }

    public function completeCurrent(
        Request $request,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
        ReviewTrainerCompletionSummaryService $reviewCompletionSummaryService,
        StudyTopicCompletionRecordService $topicCompletionRecordService,
    ): JsonResponse {
        $studySession = $this->activeSessionOrAbort($request, $studySessionManager);

        return $this->completeSession(
            $studySession,
            $studySessionManager,
            $payloadBuilder,
            $reviewCompletionSummaryService,
            $topicCompletionRecordService,
        );
    }

    public function currentExamState(
        Request $request,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $data = $request->validate([
            'action' => ['nullable', 'string', 'in:start-answer'],
        ]);
        $studySession = $this->activeSessionOrAbort($request, $studySessionManager);

        abort_unless($studySession->user_id === $request->user()->getKey(), 403);
        abort_unless($studySession->mode === StudySessionManager::MODE_EXAM, 404);

        $studySession = $studySessionManager->syncExamState($studySession);

        if ($studySession->status === 'in_progress' && ($data['action'] ?? null) === 'start-answer') {
            $studySession = $studySessionManager->startCurrentExamQuestionAnswerPhase($studySession);
        }

        return response()->json([
            'data' => $payloadBuilder->examState($studySession),
        ]);
    }

    protected function completeSession(
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
        ReviewTrainerCompletionSummaryService $reviewCompletionSummaryService,
        StudyTopicCompletionRecordService $topicCompletionRecordService,
    ): JsonResponse {
        $studySession = $studySessionManager->complete($studySession);
        $topicCompletionRecordService->syncFromCompletedSession($studySession);
        $reviewCompletionSummaryService->persistSnapshot($studySession);

        return response()->json([
            'data' => $payloadBuilder->sessionDetails($studySession),
        ]);
    }

    protected function activeSessionOrAbort(Request $request, StudySessionManager $studySessionManager): StudySession
    {
        $studySession = $studySessionManager->activeSessionForUser($request->user());

        abort_if(! $studySession, 404);

        return $studySession;
    }

    /**
     * @param  array<int, int>  $requestedIds
     * @return array<int, array<string, mixed>>
     */
    protected function questionsForSession(
        StudySession $studySession,
        array $requestedIds,
        StudySessionManager $studySessionManager,
        StudySessionApiPayloadBuilder $payloadBuilder,
    ): array {
        $sessionQuestionIds = $studySessionManager->questionIds($studySession);

        abort_unless(
            collect($requestedIds)->every(fn (int $questionId): bool => in_array($questionId, $sessionQuestionIds, true)),
            404,
        );

        $studySession->loadMissing('answers');
        $questions = $studySessionManager->orderedQuestions($studySession)
            ->keyBy(fn (Question $question): int => (int) $question->getKey());
        $answerMap = $studySession->answers->keyBy('question_id');
        $revealsOutcomes = $studySession->status === 'completed';
        $includeVisualExplanations = $studySession->mode !== StudySessionManager::MODE_SR_REVIEW
            || $studySession->status === 'completed';
        $publicExplanationUrls = $payloadBuilder->publicExplanationUrlsFor(
            collect($requestedIds)
                ->map(fn (int $questionId): ?Question => $questions->get($questionId))
                ->filter(),
        );

        return collect($requestedIds)
            ->map(function (int $questionId) use ($answerMap, $includeVisualExplanations, $payloadBuilder, $publicExplanationUrls, $questions, $revealsOutcomes): ?array {
                $question = $questions->get($questionId);

                return $question
                    ? $payloadBuilder->question(
                        $question,
                        $answerMap->get($questionId),
                        $revealsOutcomes,
                        $includeVisualExplanations,
                        $publicExplanationUrls->get($questionId),
                    )
                    : null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
