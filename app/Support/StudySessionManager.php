<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use App\Models\QuestionSignLanguageAsset;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudySessionManager
{
    public const EXAM_BASIC_COUNT = 20;

    public const EXAM_DURATION_SECONDS = 25 * 60;

    public const EXAM_PREVIEW_DURATION_SECONDS = 20;

    public const EXAM_BASIC_ANSWER_DURATION_SECONDS = 15;

    public const EXAM_SPECIALIST_ANSWER_DURATION_SECONDS = 50;

    public const EXAM_SPECIALIST_COUNT = 12;

    public const EXAM_TOTAL_COUNT = self::EXAM_BASIC_COUNT + self::EXAM_SPECIALIST_COUNT;

    public const MODE_EXAM = 'exam';

    public const MODE_LEARN = 'learn';

    public const MODE_SR_REVIEW = 'sr_review';

    public const MODE_HARD = 'hard';

    public const MODE_QUICK = 'quick';

    public const MODE_PJM = 'pjm';

    public const UI_SHELL_EXAM = 'exam';

    public const UI_SHELL_EXAM_LIKE = 'exam_like';

    public const UI_SHELL_ZEN = 'zen';

    public const QUESTION_COUNT_FIXED = 'fixed';

    public const QUESTION_COUNT_TOPIC_REMAINING = 'topic_remaining';

    public function __construct(
        protected QuestionProgressManager $questionProgressManager,
        protected HardQuestionService $hardQuestionService,
        protected ReviewPlannerService $reviewPlannerService,
        protected ReviewTrainerEventLogger $reviewTrainerEventLogger,
        protected ReviewMemoryProgressService $reviewMemoryProgressService,
        protected ReviewTrainerDailyAnswerLedgerService $reviewTrainerDailyAnswerLedgerService,
        protected UserProfileService $userProfileService,
        protected StudyContextService $studyContextService,
        protected QuestionLearningOrderService $questionLearningOrderService,
        protected IncorrectQuestionListService $incorrectQuestionListService,
        protected QuestionCollectionIncorrectQuestionService $questionCollectionIncorrectQuestionService,
        protected QuestionCollectionAccessService $questionCollectionAccessService,
    ) {}

    public function start(User $user, LicenseCategory $category, string $mode, int $questionCount, array $filters = []): StudySession
    {
        $mode = $this->canonicalMode($mode);
        $uiShell = $this->normalizeUiShell($mode, $filters);
        $filters = $this->normalizeFilters($mode, $filters);
        $usesTopicRemainingQuestionCount = $this->usesTopicRemainingQuestionCount($mode, $filters);
        $learningOrderResult = null;
        $reviewPlan = null;

        if ($usesTopicRemainingQuestionCount && $filters['question_topic_id'] === null) {
            throw ValidationException::withMessages([
                'question_topic_id' => 'Wybierz dzial PJM, zeby uruchomic caly pozostaly dzial.',
            ]);
        }

        $questionCount = $this->normalizeQuestionCount($mode, $questionCount, $filters);

        if ($mode === self::MODE_EXAM) {
            $questionIds = $this->buildExamQuestionIds($category);
        } elseif ($mode === self::MODE_HARD) {
            $questionIds = $this->hardQuestionService->questionIds($user, $category, $questionCount);
        } elseif ($mode === self::MODE_SR_REVIEW) {
            $reviewPlan = $this->reviewPlannerService->plan($user, (int) $category->getKey());
            $questionIds = $this->reviewPlannerService->recommendedQuestionIdsFromPlan($reviewPlan, $questionCount);
        } elseif ($mode === self::MODE_LEARN) {
            if (! $filters['randomize_order'] && $filters['question_topic_id'] !== null) {
                $learningOrderResult = $this->questionLearningOrderService->orderedQuestionIds($user, $category, $filters);
                $questionIds = $learningOrderResult->questionIds;
            } else {
                $questionIds = $this->questionQuery($user, $category, $mode, $filters)
                    ->pluck('id')
                    ->all();
            }
        } elseif ($usesTopicRemainingQuestionCount) {
            $questionIds = $this->questionQuery($user, $category, $mode, $filters)
                ->pluck('id')
                ->all();
        } else {
            $questionIds = $this->questionQuery($user, $category, $mode, $filters)
                ->limit($questionCount)
                ->pluck('id')
                ->all();
        }

        if ($questionIds === []) {
            throw ValidationException::withMessages([
                'license_category_id' => match ($mode) {
                    self::MODE_SR_REVIEW => 'Brak pytań do treningu pamięci dla wybranej kategorii.',
                    self::MODE_HARD => 'Brak trudnych pytan dla wybranej kategorii.',
                    self::MODE_PJM => 'Brak pytan z tlumaczeniem PJM dla wybranej kategorii i filtrow.',
                    default => 'Brak aktywnych pytan dla wybranej kategorii.',
                },
            ]);
        }

        $this->userProfileService->markStudyActivity($user);

        $startedAt = now();
        $storedQuestionCount = $usesTopicRemainingQuestionCount || $mode === self::MODE_SR_REVIEW
            ? count($questionIds)
            : $questionCount;

        return DB::transaction(function () use ($category, $filters, $learningOrderResult, $mode, $questionIds, $reviewPlan, $startedAt, $storedQuestionCount, $uiShell, $user): StudySession {
            User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            if ($mode === self::MODE_SR_REVIEW && is_array($reviewPlan)) {
                $existingReviewSession = $this->activeReviewSessionForPlan($user, $category, $reviewPlan);

                if ($existingReviewSession) {
                    StudySession::query()
                        ->where('user_id', $user->getKey())
                        ->where('status', 'in_progress')
                        ->whereKeyNot($existingReviewSession->getKey())
                        ->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                            'updated_at' => now(),
                        ]);

                    return $existingReviewSession->fresh() ?? $existingReviewSession;
                }
            }

            $reviewSessionsToReplace = StudySession::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'in_progress')
                ->where('mode', self::MODE_SR_REVIEW)
                ->get();

            StudySession::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'in_progress')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            $reviewSessionsToReplace->each(fn (StudySession $replacedSession) => $this->reviewTrainerEventLogger->sessionReplaced(
                $replacedSession,
                $mode,
                (int) $category->getKey(),
                $startedAt,
            ));

            $payload = [
                'question_ids' => $questionIds,
                'current_index' => 0,
                'answered_count' => 0,
                'ui_shell' => $uiShell,
                'exam_started_at' => $mode === self::MODE_EXAM ? $startedAt->toIso8601String() : null,
                'exam_deadline_at' => $mode === self::MODE_EXAM
                    ? $startedAt->copy()->addSeconds(self::EXAM_DURATION_SECONDS)->toIso8601String()
                    : null,
                'question_phase' => $mode === self::MODE_EXAM
                    ? $this->initialExamPhaseForQuestionId($questionIds[0] ?? null)
                    : null,
                'question_started_at' => $mode === self::MODE_EXAM
                    ? $startedAt->toIso8601String()
                    : null,
                'filters' => [
                    'question_topic_id' => $filters['question_topic_id'],
                    'question_scope' => $filters['question_scope'],
                    'question_status' => $filters['question_status'],
                    'randomize_order' => $filters['randomize_order'],
                    'question_count' => $storedQuestionCount,
                    'question_count_strategy' => $filters['question_count_strategy'],
                ],
            ];

            if ($learningOrderResult instanceof QuestionLearningOrderResult) {
                $payload['filters']['learning_order'] = $learningOrderResult->metadata();
            }

            if ($mode === self::MODE_SR_REVIEW && is_array($reviewPlan)) {
                $payload['review_plan'] = $this->compactReviewPlanPayload($reviewPlan, count($questionIds));
            }

            $session = StudySession::create([
                'user_id' => $user->getKey(),
                'license_category_id' => $category->getKey(),
                'mode' => $mode,
                'status' => 'in_progress',
                'started_at' => $startedAt,
                'correct_answers_count' => 0,
                'total_questions_count' => count($questionIds),
                'payload' => $payload,
            ]);

            if ($mode === self::MODE_SR_REVIEW && is_array($reviewPlan)) {
                $this->reviewTrainerEventLogger->sessionStarted($session, $reviewPlan, $questionIds);
            }

            return $session;
        });
    }

    public function startQuestionModule(
        User $user,
        QuestionCollection $collection,
        QuestionModule $module,
        bool $replaceActiveSession = true,
        ?string $returnUrl = null,
    ): StudySession {
        if ($module->question_collection_id !== $collection->getKey()) {
            throw ValidationException::withMessages([
                'question_module_id' => 'Wybrany moduł nie należy do tej kolekcji.',
            ]);
        }

        $questionIds = $module->questions()
            ->pluck('questions.id')
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->values()
            ->all();

        if ($questionIds === []) {
            throw ValidationException::withMessages([
                'question_module_id' => 'Ten moduł nie zawiera pytań.',
            ]);
        }

        return $this->startQuestionCollectionSession(
            $user,
            $collection,
            $module,
            $questionIds,
            'question_module',
            $replaceActiveSession,
            $returnUrl,
        );
    }

    public function startQuestionCollectionReview(
        User $user,
        QuestionCollection $collection,
        bool $replaceActiveSession = true,
        ?string $returnUrl = null,
    ): StudySession {
        $questionIds = $this->questionCollectionIncorrectQuestionService
            ->activeQuestionIdsFor($user, $collection)
            ->all();

        if ($questionIds === []) {
            throw ValidationException::withMessages([
                'question_collection_id' => 'Nie masz jeszcze pytań do poprawy w tym kursie.',
            ]);
        }

        return $this->startQuestionCollectionSession(
            $user,
            $collection,
            null,
            $questionIds,
            'question_collection_review',
            $replaceActiveSession,
            $returnUrl,
        );
    }

    /**
     * @param  array<int, int>  $questionIds
     */
    protected function startQuestionCollectionSession(
        User $user,
        QuestionCollection $collection,
        ?QuestionModule $module,
        array $questionIds,
        string $contextType,
        bool $replaceActiveSession,
        ?string $returnUrl,
    ): StudySession {
        $category = $collection->licenseCategory()->firstOrFail();
        $questionIds = collect($questionIds)
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($questionIds === []) {
            throw ValidationException::withMessages([
                'question_collection_id' => 'Sesja nie zawiera pytań.',
            ]);
        }

        $this->userProfileService->markStudyActivity($user);
        $startedAt = now();

        return DB::transaction(function () use ($category, $collection, $contextType, $module, $questionIds, $replaceActiveSession, $returnUrl, $startedAt, $user): StudySession {
            User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            $activeSession = StudySession::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'in_progress')
                ->latest('started_at')
                ->latest('id')
                ->first();

            if ($activeSession && ! $replaceActiveSession) {
                throw ValidationException::withMessages([
                    'replace_active_session' => 'Najpierw potwierdź zakończenie bieżącej sesji.',
                ]);
            }

            $reviewSessionsToReplace = StudySession::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'in_progress')
                ->where('mode', self::MODE_SR_REVIEW)
                ->get();

            StudySession::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'in_progress')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            $reviewSessionsToReplace->each(fn (StudySession $replacedSession) => $this->reviewTrainerEventLogger->sessionReplaced(
                $replacedSession,
                $contextType,
                (int) $category->getKey(),
                $startedAt,
            ));

            $context = [
                'type' => $contextType,
                'question_collection_id' => (int) $collection->getKey(),
                'collection_code' => $collection->code,
                'collection_name' => $collection->name,
                'return_url' => $returnUrl,
            ];

            if ($module) {
                $context = [
                    ...$context,
                    'question_module_id' => (int) $module->getKey(),
                    'module_code' => $module->code,
                    'module_name' => $module->name,
                ];
            }

            return StudySession::query()->create([
                'user_id' => $user->getKey(),
                'license_category_id' => $category->getKey(),
                'question_collection_id' => $collection->getKey(),
                'question_module_id' => $module?->getKey(),
                'mode' => self::MODE_LEARN,
                'status' => 'in_progress',
                'started_at' => $startedAt,
                'correct_answers_count' => 0,
                'total_questions_count' => count($questionIds),
                'payload' => [
                    'question_ids' => $questionIds,
                    'current_index' => 0,
                    'answered_count' => 0,
                    'ui_shell' => self::UI_SHELL_ZEN,
                    'filters' => [
                        'question_topic_id' => null,
                        'question_scope' => 'all',
                        'question_status' => $contextType === 'question_collection_review'
                            ? 'mistake_list'
                            : 'all',
                        'randomize_order' => false,
                        'question_count' => count($questionIds),
                        'question_count_strategy' => self::QUESTION_COUNT_FIXED,
                    ],
                    'context' => $context,
                ],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $reviewPlan
     */
    protected function activeReviewSessionForPlan(User $user, LicenseCategory $category, array $reviewPlan): ?StudySession
    {
        $reviewDay = (string) ($reviewPlan['review_day'] ?? '');
        $policyVersion = (string) ($reviewPlan['daily_plan_policy_version'] ?? '');
        $plannerVersion = (string) ($reviewPlan['planner_version'] ?? '');

        if ($reviewDay === '' || $policyVersion === '' || $plannerVersion === '') {
            return null;
        }

        return StudySession::query()
            ->where('user_id', $user->getKey())
            ->where('license_category_id', $category->getKey())
            ->where('mode', self::MODE_SR_REVIEW)
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->latest('id')
            ->get()
            ->first(function (StudySession $session) use ($plannerVersion, $policyVersion, $reviewDay): bool {
                $payload = is_array($session->payload) ? $session->payload : [];
                $storedPlan = is_array($payload['review_plan'] ?? null) ? $payload['review_plan'] : [];

                return (string) ($storedPlan['review_day'] ?? '') === $reviewDay
                    && (string) ($storedPlan['daily_plan_policy_version'] ?? '') === $policyVersion
                    && (string) ($storedPlan['planner_version'] ?? '') === $plannerVersion;
            });
    }

    /**
     * @param  array<string, mixed>  $reviewPlan
     * @return array<string, mixed>
     */
    protected function compactReviewPlanPayload(array $reviewPlan, int $selectedQuestionCount): array
    {
        $candidateSourceCounts = is_array($reviewPlan['candidate_source_counts'] ?? null)
            ? $reviewPlan['candidate_source_counts']
            : [];

        return [
            'planner_version' => (string) ($reviewPlan['planner_version'] ?? ReviewPlannerService::VERSION),
            'daily_plan_policy_version' => (string) ($reviewPlan['daily_plan_policy_version'] ?? ReviewTrainerDailyPlanService::VERSION),
            'review_day' => (string) ($reviewPlan['review_day'] ?? today()->toDateString()),
            'daily_target_count' => (int) ($reviewPlan['daily_target_count'] ?? ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT),
            'minimum_session_question_count' => (int) ($reviewPlan['minimum_session_question_count'] ?? ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT),
            'completed_today_count' => (int) ($reviewPlan['completed_today_count'] ?? 0),
            'daily_remaining_count' => (int) ($reviewPlan['daily_remaining_count'] ?? ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT),
            'due_count' => (int) ($reviewPlan['due_count'] ?? 0),
            'candidate_count' => (int) ($reviewPlan['candidate_count'] ?? $reviewPlan['due_count'] ?? 0),
            'booster_count' => (int) ($reviewPlan['booster_count'] ?? 0),
            'new_candidate_count' => (int) ($reviewPlan['new_candidate_count'] ?? 0),
            'candidate_source_counts' => [
                'primary' => (int) ($candidateSourceCounts['primary'] ?? $reviewPlan['due_count'] ?? 0),
                'seen_booster' => (int) ($candidateSourceCounts['seen_booster'] ?? $reviewPlan['booster_count'] ?? 0),
                'new_candidate' => (int) ($candidateSourceCounts['new_candidate'] ?? $reviewPlan['new_candidate_count'] ?? 0),
            ],
            'recommended_question_count' => (int) ($reviewPlan['recommended_question_count'] ?? 0),
            'selected_question_count' => $selectedQuestionCount,
            'estimated_duration_seconds' => (int) ($reviewPlan['estimated_duration_seconds'] ?? 0),
        ];
    }

    /**
     * @return Collection<int, Question>
     */
    public function orderedQuestions(StudySession $studySession): Collection
    {
        $questionIds = $this->questionIds($studySession);

        $questions = Question::query()
            ->with($this->questionRelations($studySession, includeLicenseCategory: true))
            ->whereIn('id', $questionIds)
            ->get()
            ->keyBy('id');

        return collect($questionIds)
            ->map(fn (int $questionId) => $questions->get($questionId))
            ->filter()
            ->values();
    }

    public function currentQuestion(StudySession $studySession): ?Question
    {
        $currentQuestionId = $this->currentQuestionId($studySession);

        if ($currentQuestionId === null) {
            return null;
        }

        return Question::query()
            ->with($this->questionRelations($studySession))
            ->find($currentQuestionId);
    }

    /**
     * @return array<int, string>
     */
    protected function questionRelations(StudySession $studySession, bool $includeLicenseCategory = false): array
    {
        $relations = [
            'media',
            'questionTopic',
            'referenceExplanationAsset',
            'explanationAnnotations',
        ];

        if ($includeLicenseCategory) {
            $relations[] = 'licenseCategory';
        }

        if ($studySession->mode === self::MODE_PJM) {
            $relations[] = 'activeSignLanguageAssets';
        }

        return $relations;
    }

    public function currentQuestionPosition(StudySession $studySession): ?int
    {
        if ($this->usesNextUnansweredQuestion($studySession)) {
            $nextUnansweredIndex = $this->nextUnansweredQuestionIndex(
                $studySession,
                $this->questionIds($studySession),
            );

            return $nextUnansweredIndex === null ? null : $nextUnansweredIndex + 1;
        }

        if ($this->currentQuestionId($studySession) === null) {
            return null;
        }

        return $this->currentQuestionIndex($studySession) + 1;
    }

    public function activeSessionForUser(User $user): ?StudySession
    {
        $studySession = $this->inProgressSessionForUser($user);

        if (! $studySession) {
            return null;
        }

        $collectionDecision = $this->questionCollectionAccessService->forStudySession($user, $studySession);

        return $collectionDecision?->allowed === false ? null : $studySession;
    }

    public function inProgressSessionForUser(User $user): ?StudySession
    {
        $this->retireOutOfScopeReviewSessionsForUser($user);

        return StudySession::query()
            ->with(['questionCollection', 'questionModule'])
            ->where('user_id', $user->getKey())
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->latest('id')
            ->first();
    }

    public function retireOutOfScopeReviewSessionsForUser(User $user): int
    {
        $user->unsetRelation('profile');

        $allowedCategoryIds = $this->studyContextService->activeCategories($user)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        $sessions = StudySession::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'in_progress')
            ->where('mode', self::MODE_SR_REVIEW)
            ->when(
                $allowedCategoryIds !== [],
                fn (Builder $query) => $query->whereNotIn('license_category_id', $allowedCategoryIds),
            )
            ->get();

        $sessions->each(fn (StudySession $session) => $this->retireReviewSession(
            $session,
            'category_scope_changed',
        ));

        return $sessions->count();
    }

    public function retireReviewSessionIfOutsideActiveScope(StudySession $studySession, User $user): bool
    {
        $user->unsetRelation('profile');

        if (
            $studySession->mode !== self::MODE_SR_REVIEW
            || $studySession->status !== 'in_progress'
            || $this->studyContextService->canUseCategoryId($user, $studySession->license_category_id)
        ) {
            return false;
        }

        $this->retireReviewSession($studySession, 'category_scope_changed');

        return true;
    }

    public function retireReviewSession(StudySession $studySession, string $reason): StudySession
    {
        if ($studySession->mode !== self::MODE_SR_REVIEW || $studySession->status !== 'in_progress') {
            return $studySession;
        }

        $resolvedAt = now();

        $studySession->forceFill([
            'status' => 'completed',
            'completed_at' => $resolvedAt,
        ])->save();

        $retiredSession = $studySession->fresh(['answers', 'user', 'licenseCategory']) ?? $studySession;

        $this->reviewTrainerEventLogger->sessionReplaced(
            $retiredSession,
            $reason,
            null,
            $resolvedAt,
            $reason,
        );

        return $retiredSession;
    }

    public function answeredCount(StudySession $studySession): int
    {
        $answerCount = $studySession->relationLoaded('answers')
            ? $studySession->answers->count()
            : (int) $studySession->answers()->count();

        if ($studySession->mode === self::MODE_SR_REVIEW) {
            return $answerCount;
        }

        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $payloadAnsweredCount = isset($payload['answered_count'])
            ? (int) $payload['answered_count']
            : $answerCount;

        return max($answerCount, $payloadAnsweredCount);
    }

    public function syncExamState(StudySession $studySession): StudySession
    {
        if ($studySession->mode !== self::MODE_EXAM || $studySession->status !== 'in_progress') {
            return $studySession;
        }

        [$resolvedSession, $timedOutQuestions] = $this->runWithSqliteBusyRetry(function () use ($studySession): array {
            /** @var StudySession $lockedSession */
            $lockedSession = StudySession::query()
                ->with(['answers', 'user', 'licenseCategory'])
                ->whereKey($studySession->getKey())
                ->firstOrFail();

            if ($lockedSession->mode !== self::MODE_EXAM || $lockedSession->status !== 'in_progress') {
                return [$lockedSession, []];
            }

            $payload = is_array($lockedSession->payload) ? $lockedSession->payload : [];
            $questionIds = $this->questionIds($lockedSession);
            $questionCount = count($questionIds);
            $answersByQuestionId = $lockedSession->answers->keyBy('question_id');
            $answeredCount = max(
                isset($payload['answered_count']) ? (int) $payload['answered_count'] : 0,
                $answersByQuestionId->count(),
            );
            $correctAnswersCount = (int) $lockedSession->correct_answers_count;
            $examStartedAt = $this->examTimestamp(
                $payload['exam_started_at'] ?? null,
                $lockedSession->started_at ?? now(),
            ) ?? now();
            $examDeadlineAt = $this->examTimestamp(
                $payload['exam_deadline_at'] ?? null,
                $examStartedAt->copy()->addSeconds(self::EXAM_DURATION_SECONDS),
            ) ?? $examStartedAt->copy()->addSeconds(self::EXAM_DURATION_SECONDS);
            $now = now();
            $timedOutQuestions = [];

            $payload['exam_started_at'] = $examStartedAt->toIso8601String();
            $payload['exam_deadline_at'] = $examDeadlineAt->toIso8601String();

            while (true) {
                $currentIndex = max(
                    0,
                    min(
                        isset($payload['current_index']) ? (int) $payload['current_index'] : $answersByQuestionId->count(),
                        $questionCount,
                    ),
                );

                $payload['current_index'] = $currentIndex;
                $payload['answered_count'] = min(max($answeredCount, $currentIndex > 0 ? $currentIndex : 0), $questionCount);

                if ($currentIndex >= $questionCount) {
                    $payload['answered_count'] = $questionCount;
                    $this->completeExamSession(
                        $lockedSession,
                        $payload,
                        $questionCount,
                        $correctAnswersCount,
                        $examDeadlineAt,
                    );

                    break;
                }

                $questionId = $questionIds[$currentIndex] ?? null;
                $question = $questionId
                    ? Question::query()->with('media')->find($questionId)
                    : null;

                if (! $question) {
                    $payload['current_index'] = min($currentIndex + 1, $questionCount);

                    continue;
                }

                $phase = in_array((string) ($payload['question_phase'] ?? ''), ['preview', 'media', 'answer'], true)
                    ? (string) $payload['question_phase']
                    : $this->initialExamPhase($question);
                $questionStartedAt = $this->examTimestamp(
                    $payload['question_started_at'] ?? null,
                    $currentIndex === 0 ? $examStartedAt : $now,
                ) ?? ($currentIndex === 0 ? $examStartedAt->copy() : $now->copy());
                $stageDurationSeconds = $this->examStageDurationSeconds($question, $phase);
                $stageDeadlineAt = $questionStartedAt->copy()->addSeconds($stageDurationSeconds);

                if ($stageDeadlineAt->greaterThan($examDeadlineAt)) {
                    $stageDeadlineAt = $examDeadlineAt->copy();
                }

                if ($now->lt($stageDeadlineAt)) {
                    $payload['question_phase'] = $phase;
                    $payload['question_started_at'] = $questionStartedAt->toIso8601String();
                    break;
                }

                if ($phase === 'preview' && $this->initialExamPhase($question) === 'preview' && $stageDeadlineAt->lt($examDeadlineAt)) {
                    $payload['question_phase'] = $this->phaseAfterPreview($question);
                    $payload['question_started_at'] = $stageDeadlineAt->toIso8601String();

                    continue;
                }

                if ($phase === 'media' && $stageDeadlineAt->lt($examDeadlineAt)) {
                    $payload['question_phase'] = 'answer';
                    $payload['question_started_at'] = $stageDeadlineAt->toIso8601String();

                    continue;
                }

                if (! $answersByQuestionId->has($question->getKey())) {
                    $responseTimeMs = $phase === 'answer'
                        ? $this->examStageDurationSeconds($question, 'answer') * 1000
                        : null;

                    $timeoutAnswer = $lockedSession->answers()->create([
                        'question_id' => $question->getKey(),
                        'selected_answer' => null,
                        'answer_kind' => StudySessionAnswerKind::TIMEOUT,
                        'is_correct' => false,
                        'response_time_ms' => $responseTimeMs,
                        'answered_at' => $stageDeadlineAt,
                    ]);

                    $this->incorrectQuestionListService->recordAnswer(
                        (int) $lockedSession->user_id,
                        $question,
                        $lockedSession,
                        $timeoutAnswer,
                        false,
                        $stageDeadlineAt,
                    );

                    $answersByQuestionId->put($question->getKey(), $timeoutAnswer);
                    $answeredCount = min($answeredCount + 1, $questionCount);
                    $timedOutQuestions[] = [
                        'question' => $question,
                        'answered_at' => $stageDeadlineAt->copy(),
                        'response_time_ms' => $responseTimeMs,
                    ];
                } else {
                    $answeredCount = min(max($answeredCount, $currentIndex + 1), $questionCount);
                }

                $payload['current_index'] = min($currentIndex + 1, $questionCount);
                $payload['answered_count'] = min(max($answeredCount, $payload['current_index']), $questionCount);
                unset($payload['question_phase'], $payload['question_started_at']);

                if ($stageDeadlineAt->greaterThanOrEqualTo($examDeadlineAt) || $payload['current_index'] >= $questionCount) {
                    $payload['answered_count'] = $questionCount;
                    $this->completeExamSession(
                        $lockedSession,
                        $payload,
                        $questionCount,
                        $correctAnswersCount,
                        $stageDeadlineAt->greaterThan($examDeadlineAt) ? $examDeadlineAt : $stageDeadlineAt,
                    );

                    break;
                }

                $nextQuestionId = $questionIds[$payload['current_index']] ?? null;
                $nextQuestion = $nextQuestionId
                    ? Question::query()->with('media')->find($nextQuestionId)
                    : null;

                if (! $nextQuestion) {
                    $payload['current_index'] = min($payload['current_index'] + 1, $questionCount);

                    continue;
                }

                $payload['question_phase'] = $this->initialExamPhase($nextQuestion);
                $payload['question_started_at'] = $stageDeadlineAt->toIso8601String();
            }

            $lockedSession->forceFill([
                'payload' => $payload,
            ])->save();

            return [$lockedSession->fresh(['answers', 'user', 'licenseCategory']) ?? $lockedSession, $timedOutQuestions];
        });

        foreach ($timedOutQuestions as $timedOutQuestion) {
            /** @var Question $question */
            $question = $timedOutQuestion['question'];
            $this->questionProgressManager->recordAnswer(
                $resolvedSession->user,
                $question,
                false,
                $timedOutQuestion['response_time_ms'],
                $timedOutQuestion['answered_at'],
            );
        }

        return $studySession->fresh(['answers', 'user', 'licenseCategory']) ?? $studySession;
    }

    public function startCurrentExamQuestionAnswerPhase(StudySession $studySession): StudySession
    {
        if ($studySession->mode !== self::MODE_EXAM || $studySession->status !== 'in_progress') {
            return $studySession;
        }

        $resolvedSession = $this->runWithSqliteBusyRetry(function () use ($studySession): StudySession {
            /** @var StudySession $lockedSession */
            $lockedSession = StudySession::query()
                ->whereKey($studySession->getKey())
                ->firstOrFail();

            if ($lockedSession->mode !== self::MODE_EXAM || $lockedSession->status !== 'in_progress') {
                return $lockedSession;
            }

            $payload = is_array($lockedSession->payload) ? $lockedSession->payload : [];
            $questionId = $this->currentQuestionId($lockedSession);
            $question = $questionId
                ? Question::query()->with('media')->find($questionId)
                : null;
            $currentPhase = in_array((string) ($payload['question_phase'] ?? ''), ['preview', 'media', 'answer'], true)
                ? (string) $payload['question_phase']
                : ($question ? $this->initialExamPhase($question) : null);

            if (! $question || ! in_array($currentPhase, ['preview', 'media'], true)) {
                return $lockedSession;
            }

            $payload['question_phase'] = $currentPhase === 'preview'
                ? $this->phaseAfterPreview($question)
                : 'answer';
            $payload['question_started_at'] = now()->toIso8601String();

            $lockedSession->forceFill([
                'payload' => $payload,
            ])->save();

            return $lockedSession->fresh(['answers', 'user', 'licenseCategory']) ?? $lockedSession;
        });

        return $studySession->fresh(['answers', 'user', 'licenseCategory']) ?? $studySession;
    }

    /**
     * @return array{
     *     phase:'preview'|'media'|'answer'|null,
     *     question_remaining_seconds:int,
     *     question_started_at:string|null,
     *     question_deadline_at:string|null,
     *     preview_duration_seconds:int,
     *     basic_answer_duration_seconds:int,
     *     specialist_answer_duration_seconds:int
     * }
     */
    public function currentExamState(StudySession $studySession, ?Question $currentQuestion = null): array
    {
        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $examStartedAt = $this->examTimestamp(
            $payload['exam_started_at'] ?? null,
            $studySession->started_at,
        );
        $examDeadlineAt = $this->examTimestamp(
            $payload['exam_deadline_at'] ?? null,
            $examStartedAt?->copy()->addSeconds(self::EXAM_DURATION_SECONDS),
        );
        $phase = null;
        $questionStartedAt = null;
        $questionDeadlineAt = null;
        $questionRemainingSeconds = 0;

        if ($studySession->mode === self::MODE_EXAM && $studySession->status === 'in_progress' && $currentQuestion) {
            $phase = in_array((string) ($payload['question_phase'] ?? ''), ['preview', 'media', 'answer'], true)
                ? (string) $payload['question_phase']
                : $this->initialExamPhase($currentQuestion);
            $questionStartedAt = $this->examTimestamp(
                $payload['question_started_at'] ?? null,
                $examStartedAt,
            );

            if ($questionStartedAt) {
                $questionDeadlineAt = $questionStartedAt
                    ->copy()
                    ->addSeconds($this->examStageDurationSeconds($currentQuestion, $phase));

                if ($examDeadlineAt && $questionDeadlineAt->greaterThan($examDeadlineAt)) {
                    $questionDeadlineAt = $examDeadlineAt->copy();
                }

                $questionRemainingSeconds = max(now()->diffInSeconds($questionDeadlineAt, false), 0);
            }
        }

        return [
            'phase' => $phase,
            'question_remaining_seconds' => $questionRemainingSeconds,
            'question_started_at' => $questionStartedAt?->toIso8601String(),
            'question_deadline_at' => $questionDeadlineAt?->toIso8601String(),
            'preview_duration_seconds' => self::EXAM_PREVIEW_DURATION_SECONDS,
            'basic_answer_duration_seconds' => self::EXAM_BASIC_ANSWER_DURATION_SECONDS,
            'specialist_answer_duration_seconds' => self::EXAM_SPECIALIST_ANSWER_DURATION_SECONDS,
        ];
    }

    public function recordAnswer(
        StudySession $studySession,
        Question $question,
        ?string $selectedAnswer,
        ?int $responseTimeMs = null,
        string $answerKind = StudySessionAnswerKind::CHOICE,
    ): StudySessionAnswer {
        if (! StudySessionAnswerKind::isValid($answerKind)) {
            throw ValidationException::withMessages([
                'answer_kind' => 'Nieprawidlowy typ odpowiedzi.',
            ]);
        }

        if (! in_array($answerKind, [StudySessionAnswerKind::CHOICE, StudySessionAnswerKind::UNKNOWN], true)) {
            throw ValidationException::withMessages([
                'answer_kind' => 'Ten typ odpowiedzi nie moze byc zapisany recznie.',
            ]);
        }

        [$answer, $resolvedSession, $isCorrect, $answeredAt] = $this->runWithSqliteBusyRetry(function () use (
            $answerKind,
            $question,
            $responseTimeMs,
            $selectedAnswer,
            $studySession,
        ): array {
            /** @var StudySession $lockedSession */
            $lockedSession = StudySession::query()
                ->whereKey($studySession->getKey())
                ->firstOrFail();

            $questionIds = $this->questionIds($lockedSession);
            $questionIndex = array_search($question->getKey(), $questionIds, true);

            if ($questionIndex === false) {
                throw ValidationException::withMessages([
                    'question_id' => 'To pytanie nie nalezy do tej sesji.',
                ]);
            }

            $existingAnswer = $lockedSession->answers()
                ->where('question_id', $question->getKey())
                ->first();

            if ($existingAnswer) {
                return [$existingAnswer, $lockedSession, (bool) $existingAnswer->is_correct, $existingAnswer->answered_at];
            }

            if ($lockedSession->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'session' => 'Sesja jest juz zakonczona.',
                ]);
            }

            $currentQuestionId = $this->currentQuestionId($lockedSession);
            $payload = is_array($lockedSession->payload) ? $lockedSession->payload : [];

            if (! $this->allowsFlexibleAnswerOrder($lockedSession) && $currentQuestionId !== null && $question->getKey() !== $currentQuestionId) {
                throw ValidationException::withMessages([
                    'question_id' => 'To nie jest aktualne pytanie tej sesji.',
                ]);
            }

            if ($lockedSession->mode === self::MODE_EXAM) {
                $phase = in_array((string) ($payload['question_phase'] ?? ''), ['preview', 'media', 'answer'], true)
                    ? (string) $payload['question_phase']
                    : $this->initialExamPhase($question);

                if ($phase !== 'answer') {
                    throw ValidationException::withMessages([
                        'question_id' => 'Na to pytanie nie mozna jeszcze odpowiedziec.',
                    ]);
                }
            }

            if ($answerKind === StudySessionAnswerKind::UNKNOWN && $lockedSession->mode !== self::MODE_SR_REVIEW) {
                throw ValidationException::withMessages([
                    'answer_kind' => 'Odpowiedz "Nie wiem" jest dostepna tylko w Trenerze pamieci.',
                ]);
            }

            if ($answerKind === StudySessionAnswerKind::UNKNOWN) {
                $selectedAnswer = null;
            }

            if ($answerKind === StudySessionAnswerKind::CHOICE && ! in_array($selectedAnswer, ['a', 'b', 'c'], true)) {
                throw ValidationException::withMessages([
                    'selected_answer' => 'Wybierz odpowiedz A, B albo C.',
                ]);
            }

            $isCorrect = $answerKind === StudySessionAnswerKind::CHOICE
                && $selectedAnswer === $question->correct_answer;
            $answeredAt = now();
            $questionCount = count($questionIds);
            $currentIndex = $this->currentQuestionIndex($lockedSession);

            $answer = $lockedSession->answers()->create([
                'question_id' => $question->getKey(),
                'selected_answer' => $selectedAnswer,
                'answer_kind' => $answerKind,
                'is_correct' => $isCorrect,
                'response_time_ms' => $responseTimeMs,
                'answered_at' => $answeredAt,
            ]);

            if (
                $answerKind === StudySessionAnswerKind::CHOICE
                && $lockedSession->mode !== self::MODE_SR_REVIEW
                && ! $this->questionCollectionAccessService->isCourseSession($lockedSession)
            ) {
                $this->incorrectQuestionListService->recordAnswer(
                    (int) $lockedSession->user_id,
                    $question,
                    $lockedSession,
                    $answer,
                    $isCorrect,
                    $answeredAt,
                );
            }

            $answeredCount = min((int) $lockedSession->answers()->count(), $questionCount);
            $correctAnswersCount = (int) $lockedSession->answers()->where('is_correct', true)->count();
            $payload['question_ids'] = $questionIds;
            $payload['answered_count'] = $answeredCount;
            $payload['current_index'] = $this->allowsFlexibleAnswerOrder($lockedSession)
                ? min(max($currentIndex, $questionIndex + 1), $questionCount)
                : min($currentIndex + 1, $questionCount);

            if ($this->usesNextUnansweredQuestion($lockedSession)) {
                $answeredQuestionIds = $lockedSession->answers()
                    ->pluck('question_id')
                    ->map(fn (mixed $questionId): int => (int) $questionId)
                    ->all();
                $nextUnansweredQuestionIndex = $this->nextUnansweredQuestionIndexForIds(
                    $questionIds,
                    $answeredQuestionIds,
                );
                $nextUnansweredQuestionId = $nextUnansweredQuestionIndex === null
                    ? null
                    : $questionIds[$nextUnansweredQuestionIndex];

                if ($nextUnansweredQuestionId === null) {
                    unset($payload['next_unanswered_question_id']);
                } else {
                    $payload['next_unanswered_question_id'] = $nextUnansweredQuestionId;
                }
            }

            if ($lockedSession->mode === self::MODE_EXAM) {
                $nextQuestionId = $questionIds[$payload['current_index']] ?? null;
                $nextQuestion = $nextQuestionId
                    ? Question::query()->with('media')->find($nextQuestionId)
                    : null;

                if ($nextQuestion && $answeredCount < $questionCount) {
                    $payload['question_phase'] = $this->initialExamPhase($nextQuestion);
                    $payload['question_started_at'] = $answeredAt->toIso8601String();
                } else {
                    unset($payload['question_phase'], $payload['question_started_at']);
                }
            }

            $lockedSession->forceFill([
                'payload' => $payload,
                'correct_answers_count' => $correctAnswersCount,
                'total_questions_count' => $questionCount,
                'score_percent' => round(($correctAnswersCount / max($questionCount, 1)) * 100, 2),
                'status' => $answeredCount >= $questionCount ? 'completed' : $lockedSession->status,
                'completed_at' => $answeredCount >= $questionCount ? now() : $lockedSession->completed_at,
            ])->save();

            return [$answer, $lockedSession->fresh() ?? $lockedSession, $isCorrect, $answeredAt];
        });

        $resolvedSession->loadMissing('user');
        $isCourseSession = $this->questionCollectionAccessService->isCourseSession($resolvedSession);

        if (
            $answer->wasRecentlyCreated
            && $answer->answer_kind === StudySessionAnswerKind::CHOICE
            && $isCourseSession
        ) {
            $this->questionCollectionIncorrectQuestionService->recordAnswer(
                $resolvedSession,
                $question,
                $answer,
                $isCorrect,
                $answeredAt,
            );
        }

        if (
            $answer->wasRecentlyCreated
            && $answer->answer_kind === StudySessionAnswerKind::CHOICE
            && $resolvedSession->mode !== self::MODE_SR_REVIEW
            && ! $isCourseSession
        ) {
            $this->questionProgressManager->recordAnswer(
                $resolvedSession->user,
                $question,
                $isCorrect,
                $responseTimeMs,
                $answeredAt,
            );
        }

        if (! $isCourseSession) {
            $this->reviewMemoryProgressService->recordAnswer(
                $resolvedSession,
                $answer,
                $question,
            );

            if ($answer->wasRecentlyCreated) {
                $this->reviewTrainerDailyAnswerLedgerService->recordAnswer(
                    $resolvedSession,
                    $answer,
                    $question,
                );
            }
        }

        $this->logReviewSessionCompleted($resolvedSession);

        return $answer;
    }

    protected function runWithSqliteBusyRetry(callable $callback): mixed
    {
        $attempts = 5;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (QueryException $exception) {
                $message = strtolower($exception->getMessage());

                if (! str_contains($message, 'database is locked') || $attempt === $attempts) {
                    throw $exception;
                }

                usleep(50000 * $attempt);
            }
        }

        throw new \RuntimeException('Unexpected SQLite retry flow exit.');
    }

    /**
     * @return array<int, int>
     */
    public function questionIds(StudySession $studySession): array
    {
        return collect($studySession->payload['question_ids'] ?? [])
            ->map(fn (mixed $questionId) => (int) $questionId)
            ->filter()
            ->values()
            ->all();
    }

    public function complete(StudySession $studySession): StudySession
    {
        if ($studySession->status === 'completed') {
            $completedSession = $studySession->fresh(['answers', 'licenseCategory']) ?? $studySession->load('answers', 'licenseCategory');
            $this->logReviewSessionCompleted($completedSession);

            return $completedSession;
        }

        $studySession->load('answers');
        $this->syncProgress($studySession, true);

        $completedSession = $studySession->fresh(['answers', 'licenseCategory']) ?? $studySession->load('answers', 'licenseCategory');
        $this->logReviewSessionCompleted($completedSession);

        return $completedSession;
    }

    protected function logReviewSessionCompleted(StudySession $studySession): void
    {
        if ($studySession->mode !== self::MODE_SR_REVIEW || $studySession->status !== 'completed') {
            return;
        }

        $this->reviewTrainerEventLogger->sessionCompleted($studySession);
    }

    protected function syncProgress(StudySession $studySession, bool $forceComplete = false): void
    {
        $answeredCount = $studySession->answers()->count();
        $correctCount = $studySession->answers()->where('is_correct', true)->count();
        $totalCount = max($studySession->total_questions_count, 1);

        $studySession->forceFill([
            'correct_answers_count' => $correctCount,
            'total_questions_count' => count($this->questionIds($studySession)),
            'score_percent' => round(($correctCount / $totalCount) * 100, 2),
        ]);

        if ($forceComplete || $answeredCount >= $studySession->total_questions_count) {
            $studySession->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        $studySession->save();
    }

    protected function currentQuestionId(StudySession $studySession): ?int
    {
        $questionIds = $this->questionIds($studySession);

        if ($this->usesNextUnansweredQuestion($studySession)) {
            $nextUnansweredIndex = $this->nextUnansweredQuestionIndex($studySession, $questionIds);

            return $nextUnansweredIndex === null ? null : $questionIds[$nextUnansweredIndex];
        }

        $currentIndex = $this->currentQuestionIndex($studySession);

        return $questionIds[$currentIndex] ?? null;
    }

    protected function currentQuestionIndex(StudySession $studySession): int
    {
        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $questionIds = $this->questionIds($studySession);
        $storedIndex = isset($payload['current_index'])
            ? (int) $payload['current_index']
            : (int) $studySession->answers()->count();

        return max(0, min($storedIndex, count($questionIds)));
    }

    protected function usesNextUnansweredQuestion(StudySession $studySession): bool
    {
        return $studySession->mode === self::MODE_SR_REVIEW
            && $studySession->status === 'in_progress';
    }

    /**
     * @param  array<int, int>  $questionIds
     */
    protected function nextUnansweredQuestionIndex(StudySession $studySession, array $questionIds): ?int
    {
        $answeredQuestionIds = $studySession->relationLoaded('answers')
            ? $studySession->answers
                ->pluck('question_id')
                ->map(fn (mixed $questionId): int => (int) $questionId)
                ->all()
            : $studySession->answers()
                ->pluck('question_id')
                ->map(fn (mixed $questionId): int => (int) $questionId)
                ->all();

        return $this->nextUnansweredQuestionIndexForIds($questionIds, $answeredQuestionIds);
    }

    /**
     * @param  array<int, int>  $questionIds
     * @param  array<int, int>  $answeredQuestionIds
     */
    protected function nextUnansweredQuestionIndexForIds(array $questionIds, array $answeredQuestionIds): ?int
    {
        $answeredQuestionLookup = array_fill_keys($answeredQuestionIds, true);

        foreach ($questionIds as $index => $questionId) {
            if (! isset($answeredQuestionLookup[$questionId])) {
                return $index;
            }
        }

        return null;
    }

    protected function completeExamSession(
        StudySession $studySession,
        array &$payload,
        int $questionCount,
        int $correctAnswersCount,
        Carbon $completedAt,
    ): void {
        unset($payload['question_phase'], $payload['question_started_at']);

        $studySession->forceFill([
            'payload' => $payload,
            'correct_answers_count' => $correctAnswersCount,
            'total_questions_count' => $questionCount,
            'score_percent' => round(($correctAnswersCount / max($questionCount, 1)) * 100, 2),
            'status' => 'completed',
            'completed_at' => $completedAt,
        ])->save();
    }

    protected function initialExamPhaseForQuestionId(?int $questionId): ?string
    {
        if (! $questionId) {
            return null;
        }

        $question = Question::query()->find($questionId);

        return $question ? $this->initialExamPhase($question) : null;
    }

    protected function initialExamPhase(Question $question): string
    {
        return strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY')) === 'SPECJALISTYCZNY'
            ? 'answer'
            : 'preview';
    }

    protected function phaseAfterPreview(Question $question): string
    {
        return $this->questionHasVideoMedia($question) ? 'media' : 'answer';
    }

    protected function examStageDurationSeconds(Question $question, string $phase): int
    {
        if ($phase === 'preview') {
            return self::EXAM_PREVIEW_DURATION_SECONDS;
        }

        if ($phase === 'media') {
            $videoDurationSeconds = $this->questionVideoDurationSeconds($question);

            return $videoDurationSeconds !== null
                ? max($videoDurationSeconds, 1)
                : self::EXAM_DURATION_SECONDS;
        }

        return strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY')) === 'SPECJALISTYCZNY'
            ? self::EXAM_SPECIALIST_ANSWER_DURATION_SECONDS
            : self::EXAM_BASIC_ANSWER_DURATION_SECONDS;
    }

    protected function questionHasVideoMedia(Question $question): bool
    {
        if ($question->relationLoaded('media')) {
            return $question->media->contains(fn ($media) => $media->kind === 'video');
        }

        return $question->media()->where('kind', 'video')->exists();
    }

    protected function questionVideoDurationSeconds(Question $question): ?int
    {
        if ($question->relationLoaded('media')) {
            $duration = $question->media
                ->where('kind', 'video')
                ->max('duration_seconds');

            return $duration !== null ? max((int) $duration, 0) : null;
        }

        $duration = $question->media()->where('kind', 'video')->max('duration_seconds');

        return $duration !== null ? max((int) $duration, 0) : null;
    }

    protected function examTimestamp(mixed $value, Carbon|string|null $fallback = null): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return $fallback instanceof Carbon ? $fallback->copy() : (is_string($fallback) && $fallback !== '' ? Carbon::parse($fallback) : null);
            }
        }

        if ($fallback instanceof Carbon) {
            return $fallback->copy();
        }

        if (is_string($fallback) && $fallback !== '') {
            try {
                return Carbon::parse($fallback);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * @return array{question_topic_id:int|null,question_scope:string,question_status:string,randomize_order:bool,question_count_strategy:string}
     */
    protected function normalizeFilters(string $mode, array $filters): array
    {
        if ($mode === self::MODE_EXAM) {
            return [
                'question_topic_id' => null,
                'question_scope' => 'all',
                'question_status' => 'all',
                'randomize_order' => false,
                'question_count_strategy' => self::QUESTION_COUNT_FIXED,
            ];
        }

        $questionStatus = isset($filters['question_status']) && $filters['question_status'] !== ''
            ? (string) $filters['question_status']
            : ($mode === self::MODE_PJM ? 'unanswered' : 'all');
        $questionCountStrategy = $mode === self::MODE_PJM
            && ($filters['question_count_strategy'] ?? self::QUESTION_COUNT_FIXED) === self::QUESTION_COUNT_TOPIC_REMAINING
                ? self::QUESTION_COUNT_TOPIC_REMAINING
                : self::QUESTION_COUNT_FIXED;

        return [
            'question_topic_id' => isset($filters['question_topic_id']) && $filters['question_topic_id']
                ? (int) $filters['question_topic_id']
                : null,
            'question_scope' => in_array(($filters['question_scope'] ?? 'all'), ['all', 'basic', 'specialist'], true)
                ? (string) ($filters['question_scope'] ?? 'all')
                : 'all',
            'question_status' => $questionStatus,
            'randomize_order' => (bool) ($filters['randomize_order'] ?? false),
            'question_count_strategy' => $questionCountStrategy,
        ];
    }

    protected function normalizeUiShell(string $mode, array $filters): string
    {
        if ($mode === self::MODE_EXAM) {
            return self::UI_SHELL_EXAM;
        }

        $requestedShell = strtolower(trim((string) ($filters['ui_shell'] ?? '')));

        return match ($requestedShell) {
            self::UI_SHELL_EXAM_LIKE => self::UI_SHELL_EXAM_LIKE,
            self::UI_SHELL_ZEN => self::UI_SHELL_ZEN,
            default => self::UI_SHELL_ZEN,
        };
    }

    /**
     * @return array<int, int>
     */
    protected function buildExamQuestionIds(LicenseCategory $category): array
    {
        $questions = Question::query()
            ->where('license_category_id', $category->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->get(['id', 'metadata']);

        $basicQuestions = $questions
            ->filter(fn (Question $question) => strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY')) !== 'SPECJALISTYCZNY')
            ->shuffle()
            ->values();

        $specialistQuestions = $questions
            ->filter(fn (Question $question) => strtoupper((string) ($question->metadata['structure_scope'] ?? 'PODSTAWOWY')) === 'SPECJALISTYCZNY')
            ->shuffle()
            ->values();

        if (
            $basicQuestions->count() < self::EXAM_BASIC_COUNT
            || $specialistQuestions->count() < self::EXAM_SPECIALIST_COUNT
        ) {
            throw ValidationException::withMessages([
                'license_category_id' => 'Brak pelnego zestawu egzaminacyjnego 20 pytan podstawowych i 12 specjalistycznych dla wybranej kategorii.',
            ]);
        }

        return $basicQuestions
            ->take(self::EXAM_BASIC_COUNT)
            ->pluck('id')
            ->merge(
                $specialistQuestions
                    ->take(self::EXAM_SPECIALIST_COUNT)
                    ->pluck('id'),
            )
            ->map(fn (mixed $questionId) => (int) $questionId)
            ->values()
            ->all();
    }

    protected function questionQuery(User $user, LicenseCategory $category, string $mode, array $filters = []): Builder
    {
        $questionTopicId = isset($filters['question_topic_id']) && $filters['question_topic_id']
            ? (int) $filters['question_topic_id']
            : null;
        $questionScope = in_array(($filters['question_scope'] ?? 'all'), ['all', 'basic', 'specialist'], true)
            ? (string) ($filters['question_scope'] ?? 'all')
            : 'all';
        $questionStatus = isset($filters['question_status']) && $filters['question_status'] !== ''
            ? (string) $filters['question_status']
            : null;
        $randomizeOrder = (bool) ($filters['randomize_order'] ?? false);

        $query = Question::query()
            ->where('license_category_id', $category->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->when($questionTopicId, fn (Builder $topicQuery) => $topicQuery->where('question_topic_id', $questionTopicId));

        $this->applyQuestionScopeFilter($query, $questionScope);
        if ($mode === self::MODE_PJM) {
            $this->applyPjmAssetFilter($query);
        }
        $this->applyQuestionStatusFilter($query, $user, $questionStatus);
        $shouldRandomizeLearnSession = $mode === self::MODE_LEARN && $randomizeOrder;

        return match ($mode) {
            self::MODE_LEARN => $shouldRandomizeLearnSession
                ? $query->inRandomOrder()
                : $query
                    ->orderBy('difficulty')
                    ->orderByDesc('published_at')
                    ->orderBy('questions.id'),
            self::MODE_SR_REVIEW => $query
                ->dueForReview($user)
                ->orderBy('progress.next_review_at')
                ->orderByDesc('progress.incorrect_count')
                ->orderByDesc('questions.difficulty'),
            self::MODE_PJM => $randomizeOrder
                ? $query->inRandomOrder()
                : $query
                    ->orderBy('difficulty')
                    ->orderByDesc('published_at')
                    ->orderBy('questions.id'),
            default => $query->inRandomOrder(),
        };
    }

    protected function applyPjmAssetFilter(Builder $query): void
    {
        $query
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->whereExists(function ($assetQuery): void {
                $assetQuery
                    ->selectRaw('1')
                    ->from('question_sign_language_assets as pjm_assets')
                    ->whereColumn('pjm_assets.external_id', 'questions.external_id')
                    ->where('pjm_assets.asset_role', QuestionSignLanguageAsset::ROLE_QUESTION)
                    ->where('pjm_assets.is_active', true)
                    ->whereIn('pjm_assets.processing_status', [
                        QuestionSignLanguageAsset::STATUS_READY,
                        QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
                    ]);
            });
    }

    protected function applyQuestionScopeFilter(Builder $query, string $questionScope): void
    {
        if ($questionScope === 'specialist') {
            $query->where('metadata->structure_scope', 'SPECJALISTYCZNY');

            return;
        }

        if ($questionScope === 'basic') {
            $query->where(function (Builder $scopeQuery): void {
                $scopeQuery
                    ->whereNull('metadata->structure_scope')
                    ->orWhere('metadata->structure_scope', '!=', 'SPECJALISTYCZNY');
            });
        }
    }

    protected function applyQuestionStatusFilter(Builder $query, User $user, ?string $questionStatus): void
    {
        if ($questionStatus === null || $questionStatus === '' || $questionStatus === 'all') {
            return;
        }

        if ($questionStatus === 'mistake_list') {
            $this->incorrectQuestionListService->applyActiveQuestionFilter($query, (int) $user->getKey());

            return;
        }

        $progressAlias = 'status_progress';

        $query
            ->leftJoin("user_question_progress as {$progressAlias}", function ($join) use ($progressAlias, $user): void {
                $join->on("{$progressAlias}.question_id", '=', 'questions.id')
                    ->where("{$progressAlias}.user_id", '=', $user->getKey());
            })
            ->select('questions.*');

        $masteredSql = $this->masteredProgressSql($progressAlias);

        if ($questionStatus === 'unanswered') {
            $query->where(function (Builder $statusQuery) use ($progressAlias): void {
                $statusQuery
                    ->whereNull("{$progressAlias}.id")
                    ->orWhere("{$progressAlias}.total_attempts", '<=', 0);
            });

            return;
        }

        if ($questionStatus === 'memorized') {
            $query
                ->where("{$progressAlias}.total_attempts", '>', 0)
                ->whereRaw($masteredSql, [today()->toDateString()]);

            return;
        }

        if ($questionStatus === 'incorrect') {
            $query
                ->where("{$progressAlias}.total_attempts", '>', 0)
                ->where("{$progressAlias}.incorrect_count", '>', 0)
                ->whereRaw("not {$masteredSql}", [today()->toDateString()]);

            return;
        }

        if ($questionStatus === 'correct') {
            $query
                ->where("{$progressAlias}.total_attempts", '>', 0)
                ->where("{$progressAlias}.correct_count", '>', 0)
                ->where("{$progressAlias}.incorrect_count", '=', 0)
                ->whereRaw("not {$masteredSql}", [today()->toDateString()]);
        }
    }

    protected function masteredProgressSql(string $alias): string
    {
        return sprintf(
            '(coalesce(%1$s.repetitions, 0) >= %2$d and coalesce(%1$s.correct_streak, 0) >= %3$d and coalesce(%1$s.last_quality, 0) >= %4$d and %1$s.next_review_at > ?)',
            $alias,
            QuestionProgressManager::MASTERED_MIN_REPETITIONS,
            QuestionProgressManager::MASTERED_MIN_CORRECT_STREAK,
            QuestionProgressManager::MASTERED_MIN_LAST_QUALITY,
        );
    }

    protected function canonicalMode(string $mode): string
    {
        return match (strtolower(trim($mode))) {
            'review', self::MODE_SR_REVIEW => self::MODE_SR_REVIEW,
            self::MODE_EXAM,
            self::MODE_LEARN,
            self::MODE_HARD,
            self::MODE_QUICK,
            self::MODE_PJM => strtolower(trim($mode)),
            default => strtolower(trim($mode)),
        };
    }

    protected function normalizeQuestionCount(string $mode, int $questionCount, array $filters = []): int
    {
        if ($mode === self::MODE_EXAM) {
            return self::EXAM_TOTAL_COUNT;
        }

        if ($mode === self::MODE_LEARN) {
            return max($questionCount, 1);
        }

        if ($mode === self::MODE_SR_REVIEW) {
            return min(max($questionCount, 1), ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT);
        }

        if ($mode === self::MODE_PJM) {
            if ($this->usesTopicRemainingQuestionCount($mode, $filters)) {
                return max($questionCount, 1);
            }

            return min(max($questionCount, 1), 40);
        }

        $normalized = min(max($questionCount, 3), 40);

        if ($mode === self::MODE_QUICK) {
            return min($normalized, 10);
        }

        return $normalized;
    }

    protected function usesTopicRemainingQuestionCount(string $mode, array $filters): bool
    {
        return $mode === self::MODE_PJM
            && ($filters['question_count_strategy'] ?? self::QUESTION_COUNT_FIXED) === self::QUESTION_COUNT_TOPIC_REMAINING;
    }

    protected function allowsFlexibleAnswerOrder(StudySession $studySession): bool
    {
        return in_array($studySession->mode, [self::MODE_LEARN, self::MODE_PJM, self::MODE_SR_REVIEW], true);
    }
}
