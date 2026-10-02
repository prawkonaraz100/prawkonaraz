<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\StudySession;
use App\Models\User;
use App\Models\UserTopicCompletionRecord;
use Illuminate\Support\Collection;

class LearningDashboardPresenter
{
    public function __construct(
        protected StudySessionManager $studySessionManager,
        protected StudyContextService $studyContextService,
        protected QuestionCollectionAccessService $questionCollectionAccessService,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $topicGroups
     * @param  array<string, mixed>  $rankingPreview
     * @return array<string, mixed>
     */
    public function forSessionPage(
        User $user,
        ?LicenseCategory $category,
        Collection $topicGroups,
        ProductAccessDecision $productDecision,
        PjmFreeAccessDecision $pjmDecision,
        int $dueReviewCount,
        array $rankingPreview,
        int $incorrectListCount,
        bool $includeCourseSessions = true,
        int $pendingReviewCount = 0,
    ): array {
        $purchaseMode = (string) config('pwa.purchase_mode', 'web');
        $progress = $this->courseProgress($user, $category, $topicGroups, $incorrectListCount);

        return [
            'schema_version' => 1,
            'active_session' => $this->activeSession($user, $includeCourseSessions),
            'course_progress' => $progress,
            'progress_message' => app(LearningProgressMessageService::class)->forCategory(
                $progress,
                $pendingReviewCount,
                $this->recentTopicCompletion($user, $category, $topicGroups, $progress['completed_topic_ids']),
            ),
            'review' => [
                'due_count' => $dueReviewCount,
                'recommended_count' => $dueReviewCount,
                'pending_count' => $pendingReviewCount,
            ],
            'ranking' => $rankingPreview,
            'recent_learning_activity' => null,
            'weekly_activity' => null,
            'study_time' => null,
            'access_ui' => [
                'can_show_pricing_link' => $purchaseMode === 'web',
                'purchase_mode' => $purchaseMode,
                'full_product_allowed' => $productDecision->allowed,
                'full_product_reason' => $productDecision->reason,
                'pjm_allowed' => $pjmDecision->allowed,
                'pjm_reason' => $pjmDecision->reason,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function activeSession(User $user, bool $includeCourseSessions): ?array
    {
        $studySession = $this->studySessionManager->activeSessionForUser($user);

        if (! $studySession) {
            return null;
        }

        if (! $includeCourseSessions && $this->questionCollectionAccessService->isCourseSession($studySession)) {
            return null;
        }

        $studySession->loadMissing('licenseCategory', 'answers');
        $answered = $this->studySessionManager->answeredCount($studySession);
        $total = max((int) $studySession->total_questions_count, count($this->studySessionManager->questionIds($studySession)), 0);
        $remaining = max($total - $answered, 0);
        $progressPercent = $total > 0 ? min((int) round(($answered / $total) * 100), 100) : 0;
        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        $uiShell = $this->sessionUiShell($studySession);
        $isCourseSession = $this->questionCollectionAccessService->isCourseSession($studySession);

        return [
            'id' => $studySession->getKey(),
            'mode' => $studySession->mode,
            'ui_shell' => $uiShell,
            'status' => $studySession->status,
            'title' => $isCourseSession
                ? (($context['type'] ?? null) === 'question_collection_review'
                    ? 'Pytania do poprawy'
                    : (is_string($context['module_name'] ?? null) && $context['module_name'] !== ''
                        ? $context['module_name']
                        : 'Kurs zawodowy'))
                : $this->sessionTitle($studySession, $uiShell),
            'subtitle' => $isCourseSession
                ? (is_string($context['collection_name'] ?? null) ? $context['collection_name'] : null)
                : ($studySession->licenseCategory
                ? 'Kategoria '.$this->studyContextService->shortCategoryName($studySession->licenseCategory)
                : null),
            'category' => $this->sessionCategory($studySession),
            'progress' => [
                'answered' => $answered,
                'remaining' => $remaining,
                'total' => $total,
                'percent' => $progressPercent,
                'current_question_number' => $this->studySessionManager->currentQuestionPosition($studySession),
            ],
            'filters' => [
                'question_topic_id' => isset($filters['question_topic_id']) ? (int) $filters['question_topic_id'] : null,
                'question_scope' => (string) ($filters['question_scope'] ?? 'all'),
                'question_status' => (string) ($filters['question_status'] ?? 'all'),
                'randomize_order' => (bool) ($filters['randomize_order'] ?? false),
                'question_count' => isset($filters['question_count']) ? (int) $filters['question_count'] : $total,
                'question_count_strategy' => (string) ($filters['question_count_strategy'] ?? StudySessionManager::QUESTION_COUNT_FIXED),
            ],
            'started_at' => $studySession->started_at?->toIso8601String(),
            'updated_at' => $studySession->updated_at?->toIso8601String(),
            'resume_url' => route('study-sessions.current', absolute: false),
            'can_replace' => true,
            'replace_warning' => 'Masz aktywna sesje. Rozpoczecie nowej zakonczy obecna.',
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $topicGroups
     * @return array<string, mixed>
     */
    protected function courseProgress(
        User $user,
        ?LicenseCategory $category,
        Collection $topicGroups,
        int $incorrectListCount,
    ): array {
        $totals = [
            'all' => 0,
            'unanswered' => 0,
            'incorrect' => 0,
            'mistake_list' => 0,
        ];

        $topicGroups
            ->flatMap(fn (array $group): array => is_array($group['options'] ?? null) ? $group['options'] : [])
            ->each(function (array $option) use (&$totals): void {
                $counts = is_array($option['counts'] ?? null) ? $option['counts'] : [];

                $totals['all'] += (int) ($counts['all'] ?? $option['questions_count'] ?? 0);
                $totals['unanswered'] += (int) ($counts['unanswered'] ?? 0);
                $totals['incorrect'] += (int) ($counts['incorrect'] ?? 0);
                $totals['mistake_list'] += (int) ($counts['mistake_list'] ?? 0);
            });

        $answered = max($totals['all'] - $totals['unanswered'], 0);
        $correct = max($answered - $totals['incorrect'], 0);
        $percent = $totals['all'] > 0 ? min((int) round(($answered / $totals['all']) * 100), $totals['unanswered'] > 0 ? 99 : 100) : 0;
        $topicIds = $topicGroups->flatMap(fn (array $group): array => $group['options'] ?? [])
            ->pluck('id')->map(fn (mixed $id): int => (int) $id)->unique()->values();
        // Full, perfect sessions are achievements, independent of later
        // mistakes and scheduled question reviews.
        $completedTopicIds = $category && $topicIds->isNotEmpty()
            ? UserTopicCompletionRecord::query()
                ->where('user_id', $user->getKey())
                ->where('license_category_id', $category->getKey())
                ->where('question_scope', 'all')
                ->where('perfect_completion_count', '>', 0)
                ->whereIn('question_topic_id', $topicIds->all())
                ->orderBy('question_topic_id')
                ->pluck('question_topic_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->values()->all()
            : [];

        return [
            'category_id' => $category?->getKey(),
            'answered_questions' => $answered,
            'unanswered_questions' => $totals['unanswered'],
            'incorrect_questions' => $totals['incorrect'],
            'incorrect_list_count' => $incorrectListCount,
            'correct_questions' => $correct,
            'total_questions' => $totals['all'],
            'percent' => $percent,
            'completed_topic_ids' => $completedTopicIds,
            'completed_topics' => count($completedTopicIds),
            'total_topics' => $topicIds->count(),
        ];
    }

    /** @return array{name:string}|null */
    protected function recentTopicCompletion(User $user, ?LicenseCategory $category, Collection $topicGroups, array $completedTopicIds): ?array
    {
        if (! $category || $completedTopicIds === []) {
            return null;
        }

        // Inspect the latest session, not the latest successful learning session:
        // starting another session must stop showing the old congratulations.
        $session = StudySession::query()->regularCategory()
            ->where('user_id', $user->getKey())
            ->where('license_category_id', $category->getKey())
            ->orderByDesc('id')
            ->first(['id', 'mode', 'status', 'total_questions_count', 'correct_answers_count', 'payload']);
        $result = data_get($session?->payload, 'topic_completion_record');

        if (! $session || $session->mode !== StudySessionManager::MODE_LEARN || $session->status !== 'completed'
            || $session->total_questions_count <= 0
            || $session->correct_answers_count !== $session->total_questions_count
            || ! is_array($result) || ! ($result['qualified'] ?? false)
            || (int) ($result['study_session_id'] ?? 0) !== (int) $session->getKey()
            || ($result['question_scope'] ?? null) !== 'all'
            || ! in_array((int) ($result['question_topic_id'] ?? 0), $completedTopicIds, true)) {
            return null;
        }

        $topic = $topicGroups->flatMap(fn (array $group): array => $group['options'] ?? [])
            ->first(fn (array $option): bool => (int) $option['id'] === (int) $result['question_topic_id']);

        return $topic ? ['name' => (string) $topic['label']] : null;
    }

    protected function sessionUiShell(StudySession $studySession): ?string
    {
        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $uiShell = $payload['ui_shell'] ?? null;

        return is_string($uiShell) && $uiShell !== '' ? $uiShell : null;
    }

    protected function sessionTitle(StudySession $studySession, ?string $uiShell): string
    {
        return match ($studySession->mode) {
            StudySessionManager::MODE_EXAM => 'Egzamin probny',
            StudySessionManager::MODE_SR_REVIEW => 'Trener pamieci',
            StudySessionManager::MODE_PJM => 'PJM',
            StudySessionManager::MODE_QUICK => 'Szybka seria',
            StudySessionManager::MODE_HARD => 'Trudne pytania',
            StudySessionManager::MODE_LEARN => $uiShell === StudySessionManager::UI_SHELL_ZEN
                ? 'Zen mode'
                : 'Nauka klasyczna',
            default => 'Aktywna sesja',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function sessionCategory(StudySession $studySession): ?array
    {
        if (! $studySession->licenseCategory) {
            return null;
        }

        return [
            'id' => $studySession->licenseCategory->getKey(),
            'code' => $studySession->licenseCategory->code,
            'short_name' => $this->studyContextService->shortCategoryName($studySession->licenseCategory),
        ];
    }
}
