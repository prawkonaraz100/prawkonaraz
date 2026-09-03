<?php

namespace App\Http\Controllers;

use App\Filament\Pages\ProfessionalCourses;
use App\Http\Requests\StudySessionStoreRequest;
use App\Models\Question;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Support\PjmCoverageService;
use App\Support\PjmQuestionProgressService;
use App\Support\PjmSignLanguageAssetPayloadBuilder;
use App\Support\ProductAccessResolver;
use App\Support\PublicQuestionExplanationLinkResolver;
use App\Support\QuestionAudioPayloadBuilder;
use App\Support\QuestionExplanationAnnotationPayloadBuilder;
use App\Support\QuestionExplanationAssetPayloadBuilder;
use App\Support\QuestionExplanationSignReferencePayloadBuilder;
use App\Support\QuestionMediaPayloadBuilder;
use App\Support\ReviewTrainerCompletionSummaryService;
use App\Support\SharedQuestionExplanationAssetResolver;
use App\Support\SharedQuestionScopeService;
use App\Support\StudyContextService;
use App\Support\StudySessionManager;
use App\Support\StudyTopicCompletionRecordService;
use App\Support\StudyTopicGroupsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudySessionController extends Controller
{
    protected const LEARN_FULL_POOL_THRESHOLD = 24;

    protected const LEARN_WINDOW_SIZE = 12;

    protected ?QuestionExplanationSignReferencePayloadBuilder $questionExplanationSignReferencePayloadBuilder = null;

    public function store(
        StudySessionStoreRequest $request,
        StudyContextService $studyContextService,
        StudySessionManager $studySessionManager,
    ): JsonResponse|RedirectResponse {
        $category = $studyContextService
            ->visibleCategoriesQuery()
            ->findOrFail($request->integer('license_category_id'));

        $studyContextService->assertUserCanUseCategory($request->user(), $category);

        $studySession = $studySessionManager->start(
            $request->user(),
            $category,
            (string) $request->string('mode'),
            $request->integer('question_count'),
            [
                'question_topic_id' => $request->integer('question_topic_id') ?: null,
                'question_scope' => (string) ($request->string('question_scope')->value() ?: 'all'),
                'question_status' => (string) ($request->string('question_status')->value() ?: 'all'),
                'randomize_order' => $request->boolean('randomize_order'),
                'ui_shell' => $request->string('ui_shell')->value(),
            ],
        );

        if (in_array($request->header('X-Study-Session-Switch'), ['topic', 'follow-up'], true)) {
            return response()->json([
                'session' => [
                    'id' => $studySession->getKey(),
                    'status' => $studySession->status,
                ],
                'redirect' => route('study-sessions.current'),
            ]);
        }

        return to_route('study-sessions.current');
    }

    public function current(
        Request $request,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        StudyTopicGroupsService $studyTopicGroupsService,
        SharedQuestionScopeService $sharedQuestionScopeService,
    ): Response|RedirectResponse {
        $studySession = $studySessionManager->activeSessionForUser($request->user());

        if (! $studySession) {
            return to_route('session.index');
        }

        return $this->renderSessionPage(
            $request,
            $studySession,
            $studySessionManager,
            $questionMediaPayloadBuilder,
            $questionExplanationAnnotationPayloadBuilder,
            $questionExplanationAssetPayloadBuilder,
            $studyTopicGroupsService,
            $sharedQuestionScopeService,
        );
    }

    public function currentExamState(
        Request $request,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
    ): JsonResponse {
        $studySession = $studySessionManager->activeSessionForUser($request->user());

        abort_if(! $studySession, 404);
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);
        abort_unless($studySession->mode === StudySessionManager::MODE_EXAM, 404);

        $studySession = $studySessionManager->syncExamState($studySession);

        if ($studySession->status === 'in_progress' && $request->input('action') === 'start-answer') {
            $studySession = $studySessionManager->startCurrentExamQuestionAnswerPhase($studySession);
        }

        $studySession->load('licenseCategory');
        $questionIds = $studySessionManager->questionIds($studySession);
        $answeredCount = $studySessionManager->answeredCount($studySession);
        $currentQuestion = $studySession->status === 'in_progress'
            ? $studySessionManager->currentQuestion($studySession)
            : null;
        if ($currentQuestion) {
            $this->preloadSharedExplanationAssets([$currentQuestion]);
        }
        $sectionSummary = $this->sectionSummary(
            Question::query()
                ->whereIn('id', $questionIds)
                ->get(['id', 'metadata']),
            $studySession->answers()->get()->keyBy('question_id'),
        );
        $examState = $studySessionManager->currentExamState($studySession, $currentQuestion);
        $examStartedAt = $this->examTimestamp(
            data_get($studySession->payload, 'exam_started_at'),
            $studySession->started_at,
        );
        $examDeadlineAt = $this->examTimestamp(
            data_get($studySession->payload, 'exam_deadline_at'),
            $examStartedAt?->copy()->addSeconds(StudySessionManager::EXAM_DURATION_SECONDS),
        );

        return response()->json([
            'session' => [
                'id' => $studySession->getKey(),
                'mode' => $studySession->mode,
                'ui_shell' => data_get($studySession->payload, 'ui_shell'),
                'status' => $studySession->status,
                'license_category_id' => $studySession->licenseCategory?->getKey(),
                'license_category_code' => $studySession->licenseCategory?->code,
                'license_category_name' => $studySession->licenseCategory?->name,
                'started_at' => $studySession->started_at?->toIso8601String(),
                'completed_at' => $studySession->completed_at?->toIso8601String(),
                'correct_answers_count' => $studySession->correct_answers_count,
                'total_questions_count' => $studySession->total_questions_count,
                'score_percent' => $studySession->score_percent !== null ? (float) $studySession->score_percent : null,
            ],
            'progress' => [
                'answered' => $answeredCount,
                'remaining' => max($studySession->total_questions_count - $answeredCount, 0),
                'total' => $studySession->total_questions_count,
            ],
            'examUi' => [
                'duration_seconds' => StudySessionManager::EXAM_DURATION_SECONDS,
                'remaining_seconds' => $examDeadlineAt
                    ? max(now()->diffInSeconds($examDeadlineAt, false), 0)
                    : StudySessionManager::EXAM_DURATION_SECONDS,
                'started_at' => $examStartedAt?->toIso8601String(),
                'deadline_at' => $examDeadlineAt?->toIso8601String(),
                'pass_threshold' => 68,
                'max_points' => 74,
                'current_scope' => $currentQuestion
                    ? strtoupper((string) ($currentQuestion->metadata['structure_scope'] ?? 'PODSTAWOWY'))
                    : null,
                'current_points' => $currentQuestion?->points,
                'basic' => $sectionSummary['basic'],
                'specialist' => $sectionSummary['specialist'],
                ...$examState,
            ],
            'currentQuestionNumber' => $studySession->status === 'in_progress'
                ? $studySessionManager->currentQuestionPosition($studySession)
                : null,
            'currentQuestion' => $currentQuestion
                ? $this->transformQuestion($currentQuestion, $questionMediaPayloadBuilder, $questionExplanationAnnotationPayloadBuilder, $questionExplanationAssetPayloadBuilder, false, false)
                : null,
            'completed' => $studySession->status === 'completed',
            'redirect' => $studySession->status === 'completed'
                ? route('study-sessions.show', $studySession)
                : route('study-sessions.current'),
        ]);
    }

    public function show(
        Request $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        StudyTopicGroupsService $studyTopicGroupsService,
        SharedQuestionScopeService $sharedQuestionScopeService,
    ): Response|RedirectResponse {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);

        if ($studySession->status === 'in_progress') {
            return to_route('study-sessions.current');
        }

        return $this->renderSessionPage(
            $request,
            $studySession,
            $studySessionManager,
            $questionMediaPayloadBuilder,
            $questionExplanationAnnotationPayloadBuilder,
            $questionExplanationAssetPayloadBuilder,
            $studyTopicGroupsService,
            $sharedQuestionScopeService,
        );
    }

    public function currentQuestionData(
        Request $request,
        Question $question,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        SharedQuestionScopeService $sharedQuestionScopeService,
    ): JsonResponse {
        $studySession = $studySessionManager->activeSessionForUser($request->user());

        abort_if(! $studySession, 404);
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);
        abort_unless(in_array($question->getKey(), $studySessionManager->questionIds($studySession), true), 404);

        $includeSignLanguageAssets = $this->shouldIncludeSignLanguageAssets($studySession);
        $question->loadMissing($this->questionRelations($studySession));
        $this->preloadSharedExplanationAssets([$question], $this->shouldShowQuestionExplanations($studySession));
        $sharedExplanationConflictMap = $sharedQuestionScopeService->explanationConflictMapForQuestions([$question]);
        $includeCorrectAnswer = $this->shouldRevealQuestionAnswer($studySession);
        $includeVisualExplanations = $this->shouldShowQuestionExplanations($studySession);
        $includeExplanationText = $this->shouldIncludeQuestionExplanationText($studySession);
        $includeQuestionAudio = $this->shouldIncludeQuestionAudio($studySession);
        $publicExplanationUrl = $this->publicExplanationUrlsFor([$question])->get((int) $question->getKey());

        return response()->json([
            'question' => $this->transformQuestion(
                $question,
                $questionMediaPayloadBuilder,
                $questionExplanationAnnotationPayloadBuilder,
                $questionExplanationAssetPayloadBuilder,
                $includeCorrectAnswer,
                $includeVisualExplanations,
                $sharedExplanationConflictMap,
                $includeExplanationText,
                $includeSignLanguageAssets,
                $includeQuestionAudio,
                $publicExplanationUrl,
            ),
        ]);
    }

    public function questionData(
        Request $request,
        StudySession $studySession,
        Question $question,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        SharedQuestionScopeService $sharedQuestionScopeService,
    ): JsonResponse {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);
        abort_unless(in_array($question->getKey(), $studySessionManager->questionIds($studySession), true), 404);

        $includeSignLanguageAssets = $this->shouldIncludeSignLanguageAssets($studySession);
        $question->loadMissing($this->questionRelations($studySession));
        $this->preloadSharedExplanationAssets([$question], $this->shouldShowQuestionExplanations($studySession));
        $sharedExplanationConflictMap = $sharedQuestionScopeService->explanationConflictMapForQuestions([$question]);
        $includeCorrectAnswer = $this->shouldRevealQuestionAnswer($studySession);
        $includeVisualExplanations = $this->shouldShowQuestionExplanations($studySession);
        $includeExplanationText = $this->shouldIncludeQuestionExplanationText($studySession);
        $includeQuestionAudio = $this->shouldIncludeQuestionAudio($studySession);
        $publicExplanationUrl = $this->publicExplanationUrlsFor([$question])->get((int) $question->getKey());

        return response()->json([
            'question' => $this->transformQuestion(
                $question,
                $questionMediaPayloadBuilder,
                $questionExplanationAnnotationPayloadBuilder,
                $questionExplanationAssetPayloadBuilder,
                $includeCorrectAnswer,
                $includeVisualExplanations,
                $sharedExplanationConflictMap,
                $includeExplanationText,
                $includeSignLanguageAssets,
                $includeQuestionAudio,
                $publicExplanationUrl,
            ),
        ]);
    }

    public function currentQuestionBatchData(
        Request $request,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        SharedQuestionScopeService $sharedQuestionScopeService,
    ): JsonResponse {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:20'],
            'ids.*' => ['required', 'integer'],
        ]);

        $studySession = $studySessionManager->activeSessionForUser($request->user());

        abort_if(! $studySession, 404);
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);

        $sessionQuestionIds = $studySessionManager->questionIds($studySession);
        $requestedIds = collect($data['ids'])
            ->map(fn (mixed $questionId) => (int) $questionId)
            ->unique()
            ->values();

        abort_unless(
            $requestedIds->every(fn (int $questionId) => in_array($questionId, $sessionQuestionIds, true)),
            404,
        );

        $includeSignLanguageAssets = $this->shouldIncludeSignLanguageAssets($studySession);
        $questions = Question::query()
            ->with($this->questionRelations($studySession))
            ->whereIn('id', $requestedIds->all())
            ->get()
            ->keyBy('id');
        $this->preloadSharedExplanationAssets($questions->values(), $this->shouldShowQuestionExplanations($studySession));
        $sharedExplanationConflictMap = $sharedQuestionScopeService->explanationConflictMapForQuestions($questions->values());
        $includeCorrectAnswer = $this->shouldRevealQuestionAnswer($studySession);
        $includeVisualExplanations = $this->shouldShowQuestionExplanations($studySession);
        $includeExplanationText = $this->shouldIncludeQuestionExplanationText($studySession);
        $includeQuestionAudio = $this->shouldIncludeQuestionAudio($studySession);
        $publicExplanationUrls = $this->publicExplanationUrlsFor($questions->values());

        return response()->json([
            'questions' => $requestedIds
                ->map(function (int $questionId) use ($includeCorrectAnswer, $includeExplanationText, $includeQuestionAudio, $includeSignLanguageAssets, $includeVisualExplanations, $publicExplanationUrls, $questions, $questionMediaPayloadBuilder, $questionExplanationAnnotationPayloadBuilder, $questionExplanationAssetPayloadBuilder, $sharedExplanationConflictMap): ?array {
                    $question = $questions->get($questionId);

                    return $question
                        ? $this->transformQuestion(
                            $question,
                            $questionMediaPayloadBuilder,
                            $questionExplanationAnnotationPayloadBuilder,
                            $questionExplanationAssetPayloadBuilder,
                            $includeCorrectAnswer,
                            $includeVisualExplanations,
                            $sharedExplanationConflictMap,
                            $includeExplanationText,
                            $includeSignLanguageAssets,
                            $includeQuestionAudio,
                            $publicExplanationUrls->get($questionId),
                        )
                        : null;
                })
                ->filter()
                ->values(),
        ]);
    }

    public function questionBatchData(
        Request $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        SharedQuestionScopeService $sharedQuestionScopeService,
    ): JsonResponse {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:20'],
            'ids.*' => ['required', 'integer'],
        ]);

        abort_unless($studySession->user_id === $request->user()->getKey(), 403);

        $sessionQuestionIds = $studySessionManager->questionIds($studySession);
        $requestedIds = collect($data['ids'])
            ->map(fn (mixed $questionId) => (int) $questionId)
            ->unique()
            ->values();

        abort_unless(
            $requestedIds->every(fn (int $questionId) => in_array($questionId, $sessionQuestionIds, true)),
            404,
        );

        $includeSignLanguageAssets = $this->shouldIncludeSignLanguageAssets($studySession);
        $questions = Question::query()
            ->with($this->questionRelations($studySession))
            ->whereIn('id', $requestedIds->all())
            ->get()
            ->keyBy('id');
        $this->preloadSharedExplanationAssets($questions->values(), $this->shouldShowQuestionExplanations($studySession));
        $sharedExplanationConflictMap = $sharedQuestionScopeService->explanationConflictMapForQuestions($questions->values());
        $includeCorrectAnswer = $this->shouldRevealQuestionAnswer($studySession);
        $includeVisualExplanations = $this->shouldShowQuestionExplanations($studySession);
        $includeExplanationText = $this->shouldIncludeQuestionExplanationText($studySession);
        $includeQuestionAudio = $this->shouldIncludeQuestionAudio($studySession);
        $publicExplanationUrls = $this->publicExplanationUrlsFor($questions->values());

        return response()->json([
            'questions' => $requestedIds
                ->map(function (int $questionId) use ($includeCorrectAnswer, $includeExplanationText, $includeQuestionAudio, $includeSignLanguageAssets, $includeVisualExplanations, $publicExplanationUrls, $questions, $questionMediaPayloadBuilder, $questionExplanationAnnotationPayloadBuilder, $questionExplanationAssetPayloadBuilder, $sharedExplanationConflictMap): ?array {
                    $question = $questions->get($questionId);

                    return $question
                        ? $this->transformQuestion(
                            $question,
                            $questionMediaPayloadBuilder,
                            $questionExplanationAnnotationPayloadBuilder,
                            $questionExplanationAssetPayloadBuilder,
                            $includeCorrectAnswer,
                            $includeVisualExplanations,
                            $sharedExplanationConflictMap,
                            $includeExplanationText,
                            $includeSignLanguageAssets,
                            $includeQuestionAudio,
                            $publicExplanationUrls->get($questionId),
                        )
                        : null;
                })
                ->filter()
                ->values(),
        ]);
    }

    protected function renderSessionPage(
        Request $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        StudyTopicGroupsService $studyTopicGroupsService,
        SharedQuestionScopeService $sharedQuestionScopeService,
    ): Response {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);

        if ($studySession->mode === StudySessionManager::MODE_EXAM && $studySession->status === 'in_progress') {
            $studySession = $studySessionManager->syncExamState($studySession);
        }

        $studySession->load('licenseCategory');

        if (
            $studySession->status === 'completed'
            && $studySession->mode === StudySessionManager::MODE_LEARN
            && ! $this->isQuestionModuleSession($studySession)
            && ! is_array(data_get($studySession->payload, 'topic_completion_record'))
        ) {
            app(StudyTopicCompletionRecordService::class)->syncFromCompletedSession(
                $studySession->loadMissing('answers'),
            );
            $studySession = $studySession->fresh(['licenseCategory']) ?? $studySession;
        }

        $sessionPayload = is_array($studySession->payload) ? $studySession->payload : [];
        $sessionFilters = is_array($sessionPayload['filters'] ?? null) ? $sessionPayload['filters'] : [];
        $questionIds = $studySessionManager->questionIds($studySession);
        $answeredCount = $studySessionManager->answeredCount($studySession);
        $currentQuestion = $studySessionManager->currentQuestion($studySession);
        $currentQuestionNumber = $studySessionManager->currentQuestionPosition($studySession);
        $isCompleted = $studySession->status === 'completed' || $currentQuestion === null;
        $sharedExplanationConflictMap = $sharedQuestionScopeService->explanationConflictMapForQuestions(
            Question::query()
                ->whereIn('id', $questionIds)
                ->get(['id', 'external_id', 'source']),
        );

        if ($studySession->status === 'completed') {
            $currentQuestion = null;
            $currentQuestionNumber = null;
        }
        $isLearnInProgress = $this->isInteractiveLearningMode($studySession) && ! $isCompleted;
        $includeCorrectAnswer = $this->shouldRevealQuestionAnswer($studySession);
        $includeVisualExplanations = $this->shouldShowQuestionExplanations($studySession);
        $includeExplanationText = $this->shouldIncludeQuestionExplanationText($studySession);
        $includeSignLanguageAssets = $this->shouldIncludeSignLanguageAssets($studySession);
        $includeQuestionAudio = $this->shouldIncludeQuestionAudio($studySession);

        if ($currentQuestion) {
            $this->preloadSharedExplanationAssets([$currentQuestion], $includeVisualExplanations);
        }
        $answerMap = ($isCompleted || $studySession->mode === 'exam')
            ? $studySession->answers()->get()->keyBy('question_id')
            : $studySession->answers()
                ->select(['question_id', 'selected_answer', 'answer_kind', 'is_correct', 'response_time_ms'])
                ->get()
                ->keyBy('question_id');
        $resultQuestionIds = collect($questionIds);

        if ($isCompleted && $studySession->mode === StudySessionManager::MODE_SR_REVIEW) {
            $resultQuestionIds = $resultQuestionIds
                ->filter(fn (int $questionId): bool => $answerMap->get($questionId)?->is_correct === false)
                ->values();
        }

        $completionTiming = $this->completionTimingInsights($studySession);

        $questionSummaries = ($isCompleted || ! $isLearnInProgress)
            ? Question::query()
                ->with($this->questionRelations($studySession))
                ->whereIn('id', $resultQuestionIds->all())
                ->get(['id', 'external_id', 'source', 'prompt', 'explanation', 'option_a', 'option_b', 'option_c', 'correct_answer', 'metadata', 'points', 'question_topic_id'])
                ->keyBy('id')
            : Question::query()
                ->with($this->questionRelations($studySession))
                ->whereIn('id', $answerMap->keys())
                ->get(['id', 'external_id', 'source', 'prompt', 'explanation', 'option_a', 'option_b', 'option_c', 'correct_answer', 'metadata', 'points', 'question_topic_id'])
                ->keyBy('id');
        $this->preloadSharedExplanationAssets($questionSummaries->values(), $includeVisualExplanations);

        $sectionSummary = $this->sectionSummary(
            Question::query()
                ->whereIn('id', $questionIds)
                ->get(['id', 'metadata']),
            $answerMap,
        );
        $examStartedAt = $this->examTimestamp(
            $sessionPayload['exam_started_at'] ?? null,
            $studySession->started_at,
        );
        $examDeadlineAt = $this->examTimestamp(
            $sessionPayload['exam_deadline_at'] ?? null,
            $examStartedAt?->copy()->addSeconds(StudySessionManager::EXAM_DURATION_SECONDS),
        );
        $examRemainingSeconds = $examDeadlineAt
            ? max(now()->diffInSeconds($examDeadlineAt, false), 0)
            : StudySessionManager::EXAM_DURATION_SECONDS;
        $currentExamState = $studySessionManager->currentExamState($studySession, $currentQuestion);
        $examResult = $this->examResultSummary(
            $studySession,
            $questionSummaries,
            $answerMap,
            $sectionSummary,
        );
        $questionPool = collect();
        $prefetchedQuestions = collect();
        $questionPoolMode = 'full';
        $publicExplanationUrls = collect();
        $topicGroups = $this->shouldLoadTopicGroups($studySession) && $studySession->licenseCategory
            ? $studyTopicGroupsService->topicGroups($studySession->licenseCategory, $request->user())
            : collect();
        $topicCompletionOverview = app(StudyTopicCompletionRecordService::class)
            ->overviewForSession($studySession, $request->user(), $topicGroups);
        $sessionQuestionScope = in_array(($sessionFilters['question_scope'] ?? 'all'), ['all', 'basic', 'specialist'], true)
            ? (string) ($sessionFilters['question_scope'] ?? 'all')
            : 'all';

        if ($isLearnInProgress) {
            if ($studySession->mode !== StudySessionManager::MODE_SR_REVIEW && count($questionIds) <= self::LEARN_FULL_POOL_THRESHOLD) {
                $orderedQuestions = $studySessionManager->orderedQuestions($studySession);
                $this->preloadSharedExplanationAssets($orderedQuestions, $includeVisualExplanations);
                $publicExplanationUrls = $this->publicExplanationUrlsFor($orderedQuestions);

                $questionPool = $orderedQuestions
                    ->map(fn (Question $question) => $this->transformQuestion($question, $questionMediaPayloadBuilder, $questionExplanationAnnotationPayloadBuilder, $questionExplanationAssetPayloadBuilder, $includeCorrectAnswer, $includeVisualExplanations, $sharedExplanationConflictMap, $includeExplanationText, $includeSignLanguageAssets, $includeQuestionAudio, $publicExplanationUrls->get((int) $question->getKey())))
                    ->values();

                $prefetchedQuestions = $questionPool
                    ->slice(max(($currentQuestionNumber ?? 1) - 1, 0) + 1, min(self::LEARN_WINDOW_SIZE - 1, 4))
                    ->values();
            } else {
                $questionPoolMode = 'windowed';
                $prefetchLimit = $studySession->mode === StudySessionManager::MODE_SR_REVIEW
                    ? 0
                    : self::LEARN_WINDOW_SIZE - 1;
                $publicExplanationUrls = $currentQuestion
                    ? $this->publicExplanationUrlsFor([$currentQuestion])
                    : collect();
                $windowQuestionIds = collect($questionIds)
                    ->slice(max(($currentQuestionNumber ?? 1) - 1, 0) + 1, $prefetchLimit)
                    ->values();

                if ($windowQuestionIds->isNotEmpty()) {
                    $windowQuestions = Question::query()
                        ->with($this->questionRelations($studySession))
                        ->whereIn('id', $windowQuestionIds->all())
                        ->get()
                        ->keyBy('id');
                    $this->preloadSharedExplanationAssets($windowQuestions->values(), $includeVisualExplanations);
                    $publicExplanationUrls = $this->publicExplanationUrlsFor(
                        $windowQuestions->values()->prepend($currentQuestion),
                    );

                    $prefetchedQuestions = $windowQuestionIds
                        ->map(function (int $questionId) use ($includeCorrectAnswer, $includeExplanationText, $includeQuestionAudio, $includeSignLanguageAssets, $includeVisualExplanations, $publicExplanationUrls, $windowQuestions, $questionExplanationAnnotationPayloadBuilder, $questionExplanationAssetPayloadBuilder, $questionMediaPayloadBuilder, $sharedExplanationConflictMap): ?array {
                            $question = $windowQuestions->get($questionId);

                            return $question
                                ? $this->transformQuestion($question, $questionMediaPayloadBuilder, $questionExplanationAnnotationPayloadBuilder, $questionExplanationAssetPayloadBuilder, $includeCorrectAnswer, $includeVisualExplanations, $sharedExplanationConflictMap, $includeExplanationText, $includeSignLanguageAssets, $includeQuestionAudio, $publicExplanationUrls->get($questionId))
                                : null;
                        })
                        ->filter()
                        ->values();
                }
            }
        }

        $component = $studySession->mode === StudySessionManager::MODE_EXAM
            ? ($studySession->status === 'in_progress'
                ? 'StudySessions/Exam'
                : 'StudySessions/ExamResult')
            : 'StudySessions/Show';

        return Inertia::render($component, [
            'session' => [
                'id' => $studySession->getKey(),
                'mode' => $studySession->mode,
                'ui_shell' => data_get($studySession->payload, 'ui_shell'),
                'status' => $studySession->status,
                'license_category_id' => $studySession->licenseCategory?->getKey(),
                'license_category_code' => $studySession->licenseCategory?->code,
                'license_category_name' => $studySession->licenseCategory?->name,
                'context' => $this->sessionContext($studySession),
                'started_at' => $studySession->started_at?->toIso8601String(),
                'completed_at' => $studySession->completed_at?->toIso8601String(),
                'correct_answers_count' => $studySession->correct_answers_count,
                'total_questions_count' => $studySession->total_questions_count,
                'score_percent' => $studySession->score_percent !== null ? (float) $studySession->score_percent : null,
            ],
            'questionIds' => $questionIds,
            'progress' => [
                'answered' => $answeredCount,
                'remaining' => max($studySession->total_questions_count - $answeredCount, 0),
                'total' => $studySession->total_questions_count,
            ],
            'examUi' => [
                'duration_seconds' => StudySessionManager::EXAM_DURATION_SECONDS,
                'remaining_seconds' => $examRemainingSeconds,
                'started_at' => $examStartedAt?->toIso8601String(),
                'deadline_at' => $examDeadlineAt?->toIso8601String(),
                'pass_threshold' => 68,
                'max_points' => 74,
                'current_scope' => $currentQuestion
                    ? strtoupper((string) ($currentQuestion->metadata['structure_scope'] ?? 'PODSTAWOWY'))
                    : null,
                'current_points' => $currentQuestion?->points,
                'basic' => $sectionSummary['basic'],
                'specialist' => $sectionSummary['specialist'],
                ...$currentExamState,
            ],
            'currentQuestionNumber' => $currentQuestionNumber,
            'currentQuestion' => $currentQuestion
                ? $this->transformQuestion(
                    $currentQuestion,
                    $questionMediaPayloadBuilder,
                    $questionExplanationAnnotationPayloadBuilder,
                    $questionExplanationAssetPayloadBuilder,
                    $includeCorrectAnswer,
                    $includeVisualExplanations,
                    $sharedExplanationConflictMap,
                    $includeExplanationText,
                    $includeSignLanguageAssets,
                    $includeQuestionAudio,
                    $publicExplanationUrls->get((int) $currentQuestion->getKey()),
                )
                : null,
            'questionPool' => $questionPool,
            'questionPoolMode' => $questionPoolMode,
            'questionBatchSize' => self::LEARN_WINDOW_SIZE,
            'prefetchedQuestions' => $prefetchedQuestions->values(),
            'sessionFilters' => [
                'question_topic_id' => isset($sessionFilters['question_topic_id']) && $sessionFilters['question_topic_id']
                    ? (int) $sessionFilters['question_topic_id']
                    : null,
                'question_scope' => $sessionQuestionScope,
                'question_status' => isset($sessionFilters['question_status']) && $sessionFilters['question_status'] !== ''
                    ? (string) $sessionFilters['question_status']
                    : 'all',
                'randomize_order' => (bool) ($sessionFilters['randomize_order'] ?? false),
                'question_count' => isset($sessionFilters['question_count']) && (int) $sessionFilters['question_count'] > 0
                    ? (int) $sessionFilters['question_count']
                    : max(count($questionIds), 1),
            ],
            'topicGroups' => $topicGroups->values(),
            'topicCompletionOverview' => $topicCompletionOverview,
            'statusOptions' => [
                ['value' => 'all', 'label' => 'Wszystkie z tego działu'],
                ['value' => 'unanswered', 'label' => 'Jeszcze nieprzerobione'],
                ['value' => 'incorrect', 'label' => 'Z błędami'],
                ['value' => 'correct', 'label' => 'Dobrze rozwiązane'],
                ['value' => 'memorized', 'label' => 'Utrwalone'],
            ],
            'results' => $resultQuestionIds
                ->when(
                    $isLearnInProgress,
                    fn (Collection $questionIdCollection) => $questionIdCollection->filter(
                        fn (int $questionId) => $answerMap->has($questionId),
                    ),
                )
                ->map(function (int $questionId, int $position) use ($answerMap, $includeSignLanguageAssets, $includeVisualExplanations, $isCompleted, $questionSummaries, $questionExplanationAnnotationPayloadBuilder, $questionExplanationAssetPayloadBuilder, $questionMediaPayloadBuilder, $sharedExplanationConflictMap, $sharedQuestionScopeService, $studySession): array {
                    /** @var StudySessionAnswer|null $answer */
                    $answer = $answerMap->get($questionId);
                    /** @var Question|null $question */
                    $question = $questionSummaries->get($questionId);
                    $selectedAnswer = $answer?->selected_answer ? Str::upper($answer->selected_answer) : null;
                    $correctAnswer = $isCompleted && $question?->correct_answer
                        ? Str::upper($question->correct_answer)
                        : null;

                    return [
                        'id' => $questionId,
                        'external_id' => $question?->external_id,
                        'shared_explanation_has_conflict' => $question
                            ? $sharedQuestionScopeService->hasSharedExplanationConflict($question, $sharedExplanationConflictMap)
                            : false,
                        'sequence_number' => $position + 1,
                        'prompt' => $question?->prompt,
                        'structure_scope' => $question
                            ? strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY'))
                            : null,
                        'points' => $question?->points,
                        'explanation' => $isCompleted ? $question?->explanation : null,
                        'correct_answer' => $correctAnswer,
                        'correct_answer_text' => $isCompleted
                            ? $this->resolveQuestionOptionText($question, $correctAnswer)
                            : null,
                        'selected_answer' => $selectedAnswer,
                        'answer_kind' => $answer?->answer_kind,
                        'selected_answer_text' => $this->resolveQuestionOptionText($question, $selectedAnswer),
                        'is_correct' => $answer?->is_correct,
                        'response_time_ms' => $answer?->response_time_ms,
                        'topic' => $question?->questionTopic ? [
                            'id' => $question->questionTopic->getKey(),
                            'key' => $question->questionTopic->key,
                            'name' => $question->questionTopic->name,
                        ] : null,
                        'media' => $question
                            ? $questionMediaPayloadBuilder->forQuestion($question->media)
                            : [],
                        'sign_language_assets' => $question && $includeSignLanguageAssets
                            ? $this->signLanguageAssetsPayload($question)
                            : [],
                        'explanation_asset' => $question && ($studySession->mode !== StudySessionManager::MODE_EXAM || $isCompleted)
                            ? $questionExplanationAssetPayloadBuilder->forAsset($this->resolvedExplanationAsset($question))
                            : null,
                        'explanation_sign_references' => $question && $includeVisualExplanations
                            ? $this->questionExplanationSignReferencePayloadBuilder()->forQuestion($question)
                            : [],
                        'explanation_annotations' => $question && ($studySession->mode !== StudySessionManager::MODE_EXAM || $isCompleted)
                            ? $questionExplanationAnnotationPayloadBuilder->forRuntime($question->explanationAnnotations)
                            : [],
                    ];
                })
                ->values(),
            'examResult' => $examResult,
            'completionTiming' => $completionTiming,
            'pjmCompletion' => $this->pjmCompletionSummary($request, $studySession),
            'reviewCompletion' => app(ReviewTrainerCompletionSummaryService::class)->summary($studySession),
        ]);
    }

    protected function isInteractiveLearningMode(StudySession $studySession): bool
    {
        return in_array($studySession->mode, [
            StudySessionManager::MODE_LEARN,
            StudySessionManager::MODE_PJM,
            StudySessionManager::MODE_SR_REVIEW,
        ], true);
    }

    protected function shouldLoadTopicGroups(StudySession $studySession): bool
    {
        return $studySession->mode !== StudySessionManager::MODE_SR_REVIEW
            && ! $this->isQuestionModuleSession($studySession);
    }

    protected function isQuestionModuleSession(StudySession $studySession): bool
    {
        return $studySession->question_collection_id !== null
            || $studySession->question_module_id !== null
            || data_get($studySession->payload, 'context.type') === 'question_module';
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function sessionContext(StudySession $studySession): ?array
    {
        if (! $this->isQuestionModuleSession($studySession)) {
            return null;
        }

        $context = data_get($studySession->payload, 'context');

        if (! is_array($context)) {
            return null;
        }

        $isCollectionReview = ($context['type'] ?? null) === 'question_collection_review';

        return [
            'type' => $isCollectionReview ? 'question_collection_review' : 'question_module',
            'label' => $isCollectionReview ? 'Powtórka kursu' : 'Moduł '.($context['module_code'] ?? ''),
            'title' => $isCollectionReview ? 'Pytania do poprawy' : ($context['module_name'] ?? null),
            'collection_name' => $context['collection_name'] ?? null,
            'return_url' => is_string($context['return_url'] ?? null) && str_starts_with($context['return_url'], '/nauka/kursy/')
                ? $context['return_url']
                : ProfessionalCourses::getUrl(panel: 'admin'),
        ];
    }

    protected function shouldIncludeSignLanguageAssets(StudySession $studySession): bool
    {
        return $studySession->mode === StudySessionManager::MODE_PJM;
    }

    protected function shouldIncludeQuestionAudio(StudySession $studySession): bool
    {
        return $studySession->mode === StudySessionManager::MODE_LEARN;
    }

    /**
     * @return array<int, string>
     */
    protected function questionRelations(StudySession $studySession): array
    {
        return $this->questionRelationsForPayload($this->shouldIncludeSignLanguageAssets($studySession));
    }

    /**
     * @return array<int, string>
     */
    protected function questionRelationsForPayload(bool $includeSignLanguageAssets): array
    {
        $relations = [
            'media',
            'questionTopic',
            'referenceExplanationAsset',
            'explanationAnnotations',
        ];

        if ($includeSignLanguageAssets) {
            $relations[] = 'activeSignLanguageAssets';
        }

        return $relations;
    }

    protected function shouldRevealQuestionAnswer(StudySession $studySession): bool
    {
        if ($studySession->status === 'completed') {
            return true;
        }

        return $this->isInteractiveLearningMode($studySession)
            && $studySession->mode !== StudySessionManager::MODE_SR_REVIEW;
    }

    protected function shouldShowQuestionExplanations(StudySession $studySession): bool
    {
        if ($studySession->status === 'completed') {
            return true;
        }

        return $studySession->mode !== StudySessionManager::MODE_EXAM
            && $studySession->mode !== StudySessionManager::MODE_SR_REVIEW;
    }

    protected function shouldIncludeQuestionExplanationText(StudySession $studySession): bool
    {
        return $studySession->status === 'completed'
            || $studySession->mode !== StudySessionManager::MODE_SR_REVIEW;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function pjmCompletionSummary(Request $request, StudySession $studySession): ?array
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

    /**
     * @return array<string, mixed>
     */
    protected function completionTimingInsights(StudySession $studySession): array
    {
        $durationSeconds = null;

        if ($studySession->started_at && $studySession->completed_at) {
            $durationSeconds = max($studySession->started_at->diffInSeconds($studySession->completed_at), 0);
        }

        $answers = $studySession->relationLoaded('answers')
            ? $studySession->answers
            : $studySession->answers()
                ->get(['study_session_id', 'question_id', 'is_correct', 'response_time_ms']);

        $answeredCount = $answers->count();
        $completedFullSession = $studySession->total_questions_count > 0
            && $answeredCount >= $studySession->total_questions_count;
        $hasCorrectAnswer = $answers->contains(
            fn (StudySessionAnswer $answer) => $answer->is_correct === true,
        );
        $averageCorrectResponseTimeMs = $this->averageCorrectResponseTimeMs($answers);

        $comparison = [
            'previous_duration_seconds' => null,
            'previous_average_correct_response_time_ms' => null,
            'same_question_count' => false,
            'score_not_worse' => false,
            'saved_duration_seconds' => null,
            'saved_average_correct_response_time_ms' => null,
            'show_reward' => false,
        ];

        if (
            $studySession->status === 'completed'
            && $studySession->mode === StudySessionManager::MODE_LEARN
            && ! $this->isQuestionModuleSession($studySession)
        ) {
            $previousSession = $this->previousComparableLearnSession($studySession);

            if ($previousSession) {
                $previousDurationSeconds = null;

                if ($previousSession->started_at && $previousSession->completed_at) {
                    $previousDurationSeconds = max(
                        $previousSession->started_at->diffInSeconds($previousSession->completed_at),
                        0,
                    );
                }

                $previousAverageCorrectResponseTimeMs = $this->averageCorrectResponseTimeMs(
                    $previousSession->answers,
                );
                $previousAnsweredCount = $previousSession->answers->count();
                $previousCompletedFullSession = $previousSession->total_questions_count > 0
                    && $previousAnsweredCount >= $previousSession->total_questions_count;
                $previousHasCorrectAnswer = $previousSession->answers->contains(
                    fn (StudySessionAnswer $answer) => $answer->is_correct === true,
                );

                $sameQuestionCount = $previousSession->total_questions_count === $studySession->total_questions_count;
                $scoreNotWorse = $studySession->score_percent !== null
                    && $previousSession->score_percent !== null
                    ? (float) $studySession->score_percent >= (float) $previousSession->score_percent
                    : $studySession->correct_answers_count >= $previousSession->correct_answers_count;
                $hasMeaningfulRewardComparison = $completedFullSession
                    && $previousCompletedFullSession
                    && $hasCorrectAnswer
                    && $previousHasCorrectAnswer;

                $savedDurationSeconds = $hasMeaningfulRewardComparison
                    && $sameQuestionCount
                    && $durationSeconds !== null
                    && $previousDurationSeconds !== null
                    && $durationSeconds < $previousDurationSeconds
                        ? $previousDurationSeconds - $durationSeconds
                        : null;

                $savedAverageCorrectResponseTimeMs = $hasMeaningfulRewardComparison
                    && $averageCorrectResponseTimeMs !== null
                    && $previousAverageCorrectResponseTimeMs !== null
                    && $averageCorrectResponseTimeMs < $previousAverageCorrectResponseTimeMs
                        ? $previousAverageCorrectResponseTimeMs - $averageCorrectResponseTimeMs
                        : null;

                $comparison = [
                    'previous_duration_seconds' => $previousDurationSeconds,
                    'previous_average_correct_response_time_ms' => $previousAverageCorrectResponseTimeMs,
                    'same_question_count' => $sameQuestionCount,
                    'score_not_worse' => $scoreNotWorse,
                    'saved_duration_seconds' => $savedDurationSeconds,
                    'saved_average_correct_response_time_ms' => $savedAverageCorrectResponseTimeMs,
                    'show_reward' => $hasMeaningfulRewardComparison
                        && $scoreNotWorse
                        && ($savedDurationSeconds !== null || $savedAverageCorrectResponseTimeMs !== null),
                ];
            }
        }

        return [
            'duration_seconds' => $durationSeconds,
            'average_correct_response_time_ms' => $averageCorrectResponseTimeMs,
            'comparison' => $comparison,
        ];
    }

    /**
     * @param  Collection<int, Question>  $questionSummaries
     * @param  Collection<int|string, StudySessionAnswer>  $answerMap
     * @param  array{basic: array{answered:int,total:int}, specialist: array{answered:int,total:int}}  $sectionSummary
     * @return array<string, mixed>|null
     */
    protected function examResultSummary(
        StudySession $studySession,
        Collection $questionSummaries,
        Collection $answerMap,
        array $sectionSummary,
    ): ?array {
        if ($studySession->mode !== StudySessionManager::MODE_EXAM || $studySession->status !== 'completed') {
            return null;
        }

        $passThreshold = 68;
        $officialMaxPoints = 74;
        $availablePoints = 0;
        $earnedPoints = 0;
        $correctAnswersCount = 0;
        $incorrectAnswersCount = 0;
        $unansweredCount = 0;
        $breakdown = [
            'basic' => [
                'answered' => $sectionSummary['basic']['answered'],
                'total' => $sectionSummary['basic']['total'],
                'correct' => 0,
                'incorrect' => 0,
                'unanswered' => 0,
                'earned_points' => 0,
                'available_points' => 0,
            ],
            'specialist' => [
                'answered' => $sectionSummary['specialist']['answered'],
                'total' => $sectionSummary['specialist']['total'],
                'correct' => 0,
                'incorrect' => 0,
                'unanswered' => 0,
                'earned_points' => 0,
                'available_points' => 0,
            ],
        ];

        foreach ($questionSummaries as $question) {
            $bucket = strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY')) === 'SPECJALISTYCZNY'
                ? 'specialist'
                : 'basic';
            $points = max((int) ($question->points ?? 0), 0);
            /** @var StudySessionAnswer|null $answer */
            $answer = $answerMap->get($question->getKey());

            $availablePoints += $points;
            $breakdown[$bucket]['available_points'] += $points;

            if (! $answer || $answer->selected_answer === null) {
                $unansweredCount++;
                $breakdown[$bucket]['unanswered']++;

                continue;
            }

            if ($answer->is_correct === true) {
                $correctAnswersCount++;
                $earnedPoints += $points;
                $breakdown[$bucket]['correct']++;
                $breakdown[$bucket]['earned_points'] += $points;

                continue;
            }

            $incorrectAnswersCount++;
            $breakdown[$bucket]['incorrect']++;
        }

        return [
            'passed' => $earnedPoints >= $passThreshold,
            'earned_points' => $earnedPoints,
            'available_points' => $availablePoints,
            'pass_threshold' => $passThreshold,
            'official_max_points' => $officialMaxPoints,
            'correct_answers_count' => $correctAnswersCount,
            'incorrect_answers_count' => $incorrectAnswersCount,
            'unanswered_count' => $unansweredCount,
            'basic' => $breakdown['basic'],
            'specialist' => $breakdown['specialist'],
        ];
    }

    protected function examTimestamp(mixed $value, ?Carbon $fallback = null): ?Carbon
    {
        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return $fallback?->copy();
            }
        }

        return $fallback?->copy();
    }

    protected function averageCorrectResponseTimeMs(Collection $answers): ?int
    {
        $correctAnswerTimes = $answers
            ->filter(
                fn (StudySessionAnswer $answer) => $answer->is_correct === true
                    && $answer->response_time_ms !== null
                    && $answer->response_time_ms > 0,
            )
            ->pluck('response_time_ms');

        if ($correctAnswerTimes->isEmpty()) {
            return null;
        }

        return (int) round($correctAnswerTimes->avg());
    }

    protected function resolveQuestionOptionText(?Question $question, ?string $answer): ?string
    {
        if (! $question || ! $answer) {
            return null;
        }

        return match (Str::lower($answer)) {
            'a' => $question->option_a,
            'b' => $question->option_b,
            'c' => $question->option_c,
            default => null,
        };
    }

    protected function previousComparableLearnSession(StudySession $studySession): ?StudySession
    {
        $currentFilters = is_array($studySession->payload['filters'] ?? null)
            ? $studySession->payload['filters']
            : [];

        return StudySession::query()
            ->regularCategory()
            ->with(['answers:id,study_session_id,is_correct,response_time_ms'])
            ->where('user_id', $studySession->user_id)
            ->where('license_category_id', $studySession->license_category_id)
            ->where('mode', StudySessionManager::MODE_LEARN)
            ->where('status', 'completed')
            ->whereKeyNot($studySession->getKey())
            ->orderByDesc('completed_at')
            ->get([
                'id',
                'license_category_id',
                'mode',
                'status',
                'started_at',
                'completed_at',
                'correct_answers_count',
                'total_questions_count',
                'score_percent',
                'payload',
                'user_id',
            ])
            ->first(function (StudySession $candidate) use ($currentFilters) {
                $candidateFilters = is_array($candidate->payload['filters'] ?? null)
                    ? $candidate->payload['filters']
                    : [];

                return (int) ($candidateFilters['question_topic_id'] ?? 0) === (int) ($currentFilters['question_topic_id'] ?? 0)
                    && (string) ($candidateFilters['question_scope'] ?? 'all') === (string) ($currentFilters['question_scope'] ?? 'all')
                    && (string) ($candidateFilters['question_status'] ?? 'all') === (string) ($currentFilters['question_status'] ?? 'all');
            });
    }

    public function complete(
        Request $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
    ): JsonResponse|RedirectResponse {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);

        return $this->completeStudySession($request, $studySession, $studySessionManager);
    }

    public function completeCurrent(
        Request $request,
        StudySessionManager $studySessionManager,
    ): JsonResponse|RedirectResponse {
        $studySession = $studySessionManager->activeSessionForUser($request->user());

        abort_if(! $studySession, 404);

        return $this->completeStudySession($request, $studySession, $studySessionManager);
    }

    protected function completeStudySession(
        Request $request,
        StudySession $studySession,
        StudySessionManager $studySessionManager,
    ): JsonResponse|RedirectResponse {
        abort_unless($studySession->user_id === $request->user()->getKey(), 403);

        $studySession = $studySessionManager->complete($studySession);
        if (! $this->isQuestionModuleSession($studySession)) {
            app(StudyTopicCompletionRecordService::class)->syncFromCompletedSession($studySession);
        }
        app(ReviewTrainerCompletionSummaryService::class)->persistSnapshot($studySession);

        if ($request->routeIs('study-sessions.current.complete')) {
            $studySession->refresh();
            $studySession->load('licenseCategory');
            $topicCompletionOverview = null;

            if (
                $studySession->mode === StudySessionManager::MODE_LEARN
                && ! $this->isQuestionModuleSession($studySession)
                && $studySession->licenseCategory
            ) {
                $topicGroups = app(StudyTopicGroupsService::class)
                    ->topicGroups($studySession->licenseCategory, $request->user());
                $topicCompletionOverview = app(StudyTopicCompletionRecordService::class)
                    ->overviewForSession($studySession, $request->user(), $topicGroups);
            }

            return response()->json([
                'session' => [
                    'id' => $studySession->getKey(),
                    'status' => $studySession->status,
                    'correct_answers_count' => $studySession->correct_answers_count,
                    'total_questions_count' => $studySession->total_questions_count,
                    'score_percent' => $studySession->score_percent !== null ? (float) $studySession->score_percent : null,
                    'completed_at' => $studySession->completed_at?->toIso8601String(),
                ],
                'redirect' => route('study-sessions.show', $studySession),
                'topicCompletionOverview' => $topicCompletionOverview,
                'reviewCompletion' => app(ReviewTrainerCompletionSummaryService::class)->summary($studySession),
            ]);
        }

        return to_route('study-sessions.show', $studySession);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transformQuestion(
        Question $question,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        bool $includeCorrectAnswer = false,
        bool $includeVisualExplanations = true,
        array $sharedExplanationConflictMap = [],
        bool $includeExplanationText = true,
        bool $includeSignLanguageAssets = false,
        bool $includeQuestionAudio = false,
        ?string $publicExplanationUrl = null,
    ): array {
        $sharedQuestionScopeService = app(SharedQuestionScopeService::class);

        return [
            'id' => $question->getKey(),
            'external_id' => $question->external_id,
            'public_explanation_url' => $publicExplanationUrl,
            'source' => $question->source,
            'shared_explanation_has_conflict' => $sharedQuestionScopeService->hasSharedExplanationConflict($question, $sharedExplanationConflictMap),
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
            'sign_language_assets' => $includeSignLanguageAssets
                ? $this->signLanguageAssetsPayload($question)
                : [],
            'audio' => $includeQuestionAudio
                ? app(QuestionAudioPayloadBuilder::class)->forQuestion($question)
                : [],
            'explanation_asset' => $includeVisualExplanations
                ? $questionExplanationAssetPayloadBuilder->forAsset($this->resolvedExplanationAsset($question))
                : null,
            'explanation_sign_references' => $includeVisualExplanations
                ? $this->questionExplanationSignReferencePayloadBuilder()->forQuestion($question)
                : [],
            'explanation_annotations' => $includeVisualExplanations
                ? $questionExplanationAnnotationPayloadBuilder->forRuntime($question->explanationAnnotations)
                : [],
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
        QuestionExplanationAnnotationPayloadBuilder $questionExplanationAnnotationPayloadBuilder,
        QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        bool $includeCorrectAnswer = false,
        bool $includeVisualExplanations = true,
        bool $includeExplanationText = true,
        bool $includeSignLanguageAssets = false,
        bool $includeQuestionAudio = false,
    ): Collection {
        $currentIndex = max(($currentQuestionNumber ?? 1) - 1, 0);
        $prefetchIds = collect($questionIds)
            ->slice($currentIndex + 1, $limit)
            ->values();

        if ($prefetchIds->isEmpty()) {
            return collect();
        }

        $questions = Question::query()
            ->with($this->questionRelationsForPayload($includeSignLanguageAssets))
            ->whereIn('id', $prefetchIds->all())
            ->get()
            ->keyBy('id');
        $this->preloadSharedExplanationAssets($questions->values(), $includeVisualExplanations);

        return $prefetchIds
            ->map(function (int $questionId) use ($includeCorrectAnswer, $includeExplanationText, $includeQuestionAudio, $includeSignLanguageAssets, $includeVisualExplanations, $questions, $questionExplanationAnnotationPayloadBuilder, $questionExplanationAssetPayloadBuilder, $questionMediaPayloadBuilder): ?array {
                $question = $questions->get($questionId);

                return $question
                    ? $this->transformQuestion($question, $questionMediaPayloadBuilder, $questionExplanationAnnotationPayloadBuilder, $questionExplanationAssetPayloadBuilder, $includeCorrectAnswer, $includeVisualExplanations, [], $includeExplanationText, $includeSignLanguageAssets, $includeQuestionAudio)
                    : null;
            })
            ->filter()
            ->values();
    }

    protected function preloadSharedExplanationAssets(iterable $questions, bool $includeSignReferences = false): void
    {
        $questions = collect($questions);

        app(SharedQuestionExplanationAssetResolver::class)->preloadForQuestions($questions);

        if ($includeSignReferences) {
            $this->questionExplanationSignReferencePayloadBuilder()->preloadForQuestions($questions);
        }
    }

    /**
     * @param  iterable<mixed>  $questions
     * @return Collection<int, string>
     */
    protected function publicExplanationUrlsFor(iterable $questions): Collection
    {
        return app(PublicQuestionExplanationLinkResolver::class)->urlsForQuestions($questions);
    }

    protected function questionExplanationSignReferencePayloadBuilder(): QuestionExplanationSignReferencePayloadBuilder
    {
        return $this->questionExplanationSignReferencePayloadBuilder
            ??= app(QuestionExplanationSignReferencePayloadBuilder::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function signLanguageAssetsPayload(Question $question): array
    {
        $question->loadMissing('activeSignLanguageAssets');

        return app(PjmSignLanguageAssetPayloadBuilder::class)
            ->forQuestion($question->activeSignLanguageAssets);
    }

    protected function resolvedExplanationAsset(Question $question)
    {
        return app(SharedQuestionExplanationAssetResolver::class)->resolveReferenceAsset($question);
    }

    /**
     * @param  Collection<int, Question>  $orderedQuestions
     * @param  Collection<int|string, mixed>  $answerMap
     * @return array{basic: array{answered:int,total:int}, specialist: array{answered:int,total:int}}
     */
    protected function sectionSummary(Collection $orderedQuestions, Collection $answerMap): array
    {
        $summary = [
            'basic' => ['answered' => 0, 'total' => 0],
            'specialist' => ['answered' => 0, 'total' => 0],
        ];

        foreach ($orderedQuestions as $question) {
            $scope = strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY'));
            $bucket = $scope === 'SPECJALISTYCZNY' ? 'specialist' : 'basic';

            $summary[$bucket]['total']++;

            if ($answerMap->has($question->getKey())) {
                $summary[$bucket]['answered']++;
            }
        }

        return $summary;
    }
}
