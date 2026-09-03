<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\StudySession;
use App\Models\User;
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
    ): array {
        $purchaseMode = (string) config('pwa.purchase_mode', 'web');

        return [
            'schema_version' => 1,
            'active_session' => $this->activeSession($user, $includeCourseSessions),
            'course_progress' => $this->courseProgress($category, $topicGroups, $incorrectListCount),
            'review' => [
                'due_count' => $dueReviewCount,
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
        $percent = $totals['all'] > 0 ? min((int) round(($answered / $totals['all']) * 100), 100) : 0;

        return [
            'category_id' => $category?->getKey(),
            'answered_questions' => $answered,
            'unanswered_questions' => $totals['unanswered'],
            'incorrect_questions' => $totals['incorrect'],
            'incorrect_list_count' => $incorrectListCount,
            'correct_questions' => $correct,
            'total_questions' => $totals['all'],
            'percent' => $percent,
        ];
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
