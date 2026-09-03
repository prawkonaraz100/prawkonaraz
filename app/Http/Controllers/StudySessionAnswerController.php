<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudySessionAnswerRequest;
use App\Models\Question;
use App\Models\StudySession;
use App\Support\PjmCoverageService;
use App\Support\PjmQuestionProgressService;
use App\Support\ProductAccessResolver;
use App\Support\QuestionExplanationAnnotationPayloadBuilder;
use App\Support\QuestionExplanationAssetPayloadBuilder;
use App\Support\QuestionExplanationSignReferencePayloadBuilder;
use App\Support\QuestionMediaPayloadBuilder;
use App\Support\ReviewTrainerCompletionSummaryService;
use App\Support\SharedQuestionExplanationAssetResolver;
use App\Support\StudySessionAnswerKind;
use App\Support\StudySessionManager;
use App\Support\StudyTopicCompletionRecordService;
use App\Support\StudyTopicGroupsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudySessionAnswerController extends Controller
{
    public function store(
        StoreStudySessionAnswerRequest $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        StudyTopicCompletionRecordService $topicCompletionRecordService,
    ): JsonResponse|RedirectResponse {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);

        return $this->storeForSession(
            $request,
            $studySession,
            $studySessionManager,
            $questionMediaPayloadBuilder,
            $topicCompletionRecordService,
        );
    }

    public function storeCurrent(
        StoreStudySessionAnswerRequest $request,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        StudyTopicCompletionRecordService $topicCompletionRecordService,
    ): JsonResponse|RedirectResponse {
        $studySession = $studySessionManager->activeSessionForUser($request->user());

        abort_if(! $studySession, 404);

        return $this->storeForSession(
            $request,
            $studySession,
            $studySessionManager,
            $questionMediaPayloadBuilder,
            $topicCompletionRecordService,
        );
    }

    protected function storeForSession(
        StoreStudySessionAnswerRequest $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        StudyTopicCompletionRecordService $topicCompletionRecordService,
    ): JsonResponse|RedirectResponse {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);

        if ($studySession->mode === StudySessionManager::MODE_EXAM && $studySession->status === 'in_progress') {
            $studySession = $studySessionManager->syncExamState($studySession);
        }

        $question = Question::query()->findOrFail($request->integer('question_id'));
        $answerKind = (string) $request->string('answer_kind', StudySessionAnswerKind::CHOICE);
        $selectedAnswer = $request->input('selected_answer');
        $selectedAnswer = is_string($selectedAnswer) ? $selectedAnswer : null;
        $responseTimeMs = $request->filled('response_time_ms') ? $request->integer('response_time_ms') : null;

        try {
            $answer = $studySessionManager->recordAnswer(
                $studySession,
                $question,
                $selectedAnswer,
                $responseTimeMs,
                $answerKind,
            );
        } catch (ValidationException $exception) {
            logger()->warning('study_session_answer_validation_failed', [
                'study_session_id' => $studySession->getKey(),
                'route_name' => $request->route()?->getName(),
                'mode' => $studySession->mode,
                'question_id' => $question->getKey(),
                'selected_answer' => $selectedAnswer,
                'answer_kind' => $answerKind,
                'response_time_ms' => $responseTimeMs,
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        }

        $studySession = $studySession->fresh(['answers', 'licenseCategory']) ?? $studySession;
        $topicCompletionOverview = null;

        if ($studySession->status === 'completed' && ! $this->isQuestionModuleSession($studySession)) {
            $topicCompletionRecordService->syncFromCompletedSession($studySession);
            $studySession = $studySession->fresh(['answers', 'licenseCategory']) ?? $studySession;

            if ($studySession->mode === StudySessionManager::MODE_LEARN && $studySession->licenseCategory) {
                $topicGroups = app(StudyTopicGroupsService::class)
                    ->topicGroups($studySession->licenseCategory, $request->user());
                $topicCompletionOverview = $topicCompletionRecordService
                    ->overviewForSession($studySession, $request->user(), $topicGroups);
            }
        }

        if ($request->routeIs('study-sessions.current.answers.store')) {
            $answeredCount = $studySessionManager->answeredCount($studySession);
            $nextQuestion = null;
            $nextQuestionNumber = null;

            if ($studySession->mode === StudySessionManager::MODE_SR_REVIEW && $studySession->status === 'in_progress') {
                $nextQuestion = $studySessionManager->currentQuestion($studySession);
                $nextQuestionNumber = $studySessionManager->currentQuestionPosition($studySession);
            }

            $pjmCompletion = $studySession->status === 'completed'
                ? $this->pjmCompletionSummary($request, $studySession)
                : null;
            $reviewCompletion = $studySession->status === 'completed'
                ? app(ReviewTrainerCompletionSummaryService::class)->persistSnapshot($studySession)
                : null;
            $answerPayload = [
                'question_id' => $answer->question_id,
                'selected_answer' => $answer->selected_answer,
                'answer_kind' => $answer->answer_kind,
                'is_correct' => $answer->is_correct,
                'response_time_ms' => $answer->response_time_ms,
            ];

            if ($this->shouldReturnAnswerReveal($studySession)) {
                $answerPayload = [
                    ...$answerPayload,
                    ...$this->answerRevealPayload($question),
                ];
            }

            return response()->json([
                'answer' => $answerPayload,
                'session' => [
                    'status' => $studySession->status,
                    'correct_answers_count' => $studySession->correct_answers_count,
                    'total_questions_count' => $studySession->total_questions_count,
                    'score_percent' => $studySession->score_percent !== null ? (float) $studySession->score_percent : null,
                    'completed_at' => $studySession->completed_at?->toIso8601String(),
                ],
                'progress' => [
                    'answered' => $answeredCount,
                    'remaining' => max($studySession->total_questions_count - $answeredCount, 0),
                    'total' => $studySession->total_questions_count,
                ],
                'completed' => $studySession->status === 'completed',
                'topicCompletionOverview' => $topicCompletionOverview,
                'nextQuestion' => $nextQuestion
                    ? $this->transformQuestion(
                        $nextQuestion,
                        $questionMediaPayloadBuilder,
                        includeCorrectAnswer: false,
                        includeExplanationText: false,
                    )
                    : null,
                'nextQuestionNumber' => $nextQuestionNumber,
                'pjmCompletion' => $pjmCompletion,
                'reviewCompletion' => $reviewCompletion,
            ]);
        }

        return $studySession->status === 'in_progress'
            ? to_route('study-sessions.current')
            : to_route('study-sessions.show', $studySession);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function pjmCompletionSummary(StoreStudySessionAnswerRequest $request, StudySession $studySession): ?array
    {
        if ($studySession->mode !== StudySessionManager::MODE_PJM || ! $studySession->licenseCategory) {
            return null;
        }

        $coverage = collect(app(PjmCoverageService::class)->categoryCoverage([
            $studySession->licenseCategory->code,
        ]))->first();
        $progress = app(PjmQuestionProgressService::class)
            ->progressForCategory($studySession->licenseCategory, $request->user());
        $productDecision = app(ProductAccessResolver::class)->forUser($request->user());

        return [
            'coverage' => $coverage,
            'progress' => $progress,
            'has_full_product_access' => $productDecision->allowed,
            'pricing_url' => route('public.pricing', absolute: false),
            'session_index_url' => route('session.index', absolute: false),
            'pjm_index_url' => route('session.pjm', absolute: false),
        ];
    }

    protected function shouldReturnAnswerReveal(StudySession $studySession): bool
    {
        return in_array($studySession->mode, [
            StudySessionManager::MODE_LEARN,
            StudySessionManager::MODE_PJM,
            StudySessionManager::MODE_SR_REVIEW,
        ], true);
    }

    protected function isQuestionModuleSession(StudySession $studySession): bool
    {
        return $studySession->question_collection_id !== null
            || $studySession->question_module_id !== null
            || data_get($studySession->payload, 'context.type') === 'question_module';
    }

    /**
     * @return array<string, mixed>
     */
    protected function answerRevealPayload(Question $question): array
    {
        $question->loadMissing('referenceExplanationAsset', 'explanationAnnotations');

        $sharedExplanationAssetResolver = app(SharedQuestionExplanationAssetResolver::class);
        $sharedExplanationAssetResolver->preloadForQuestions([$question]);
        $questionExplanationSignReferencePayloadBuilder = app(QuestionExplanationSignReferencePayloadBuilder::class);
        $questionExplanationSignReferencePayloadBuilder->preloadForQuestions([$question]);

        $correctAnswer = Str::upper((string) $question->correct_answer);

        return [
            'correct_answer' => $correctAnswer,
            'correct_answer_text' => $this->resolveQuestionOptionText($question, $correctAnswer),
            'explanation' => $question->explanation,
            'explanation_asset' => app(QuestionExplanationAssetPayloadBuilder::class)
                ->forAsset($sharedExplanationAssetResolver->resolveReferenceAsset($question)),
            'explanation_sign_references' => $questionExplanationSignReferencePayloadBuilder->forQuestion($question),
            'explanation_annotations' => app(QuestionExplanationAnnotationPayloadBuilder::class)
                ->forRuntime($question->explanationAnnotations),
        ];
    }

    protected function resolveQuestionOptionText(Question $question, ?string $answer): ?string
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

    /**
     * @return array<string, mixed>
     */
    protected function transformQuestion(
        Question $question,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        bool $includeCorrectAnswer = true,
        bool $includeExplanationText = true,
    ): array {
        return [
            'id' => $question->getKey(),
            'external_id' => $question->external_id,
            'source' => $question->source,
            'shared_explanation_has_conflict' => false,
            'prompt' => $question->prompt,
            'explanation' => $includeExplanationText ? $question->explanation : null,
            'correct_answer' => $includeCorrectAnswer ? Str::upper($question->correct_answer) : null,
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
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => Str::upper($key),
                    'text' => $label,
                ])
                ->values(),
            'media' => $questionMediaPayloadBuilder->forQuestion($question->media),
            'sign_language_assets' => [],
            'explanation_asset' => null,
            'explanation_sign_references' => [],
            'explanation_annotations' => [],
        ];
    }

    /**
     * @param  array<int, int>  $questionIds
     * @return Collection<int, array<string, mixed>>
     */
    protected function prefetchedQuestions(
        array $questionIds,
        ?int $currentQuestionNumber,
        int $limit,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
    ) {
        $currentIndex = max(($currentQuestionNumber ?? 1) - 1, 0);
        $prefetchIds = collect($questionIds)
            ->slice($currentIndex + 1, $limit)
            ->values();

        if ($prefetchIds->isEmpty()) {
            return collect();
        }

        $questions = Question::query()
            ->with('media', 'questionTopic')
            ->whereIn('id', $prefetchIds->all())
            ->get()
            ->keyBy('id');

        return $prefetchIds
            ->map(function (int $questionId) use ($questions, $questionMediaPayloadBuilder): ?array {
                $question = $questions->get($questionId);

                return $question
                    ? $this->transformQuestion($question, $questionMediaPayloadBuilder)
                    : null;
            })
            ->filter()
            ->values();
    }
}
