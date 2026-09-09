<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use App\Models\RankedPlayerRating;
use App\Models\StudySession;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LearningHomePayloadBuilder
{
    protected const CACHE_VERSION = 'v3';

    public function __construct(
        protected StudyContextService $studyContextService,
        protected StudyTopicGroupsService $studyTopicGroupsService,
        protected ProductAccessResolver $productAccessResolver,
        protected PjmFreeAccessResolver $pjmFreeAccessResolver,
        protected PjmCoverageService $pjmCoverageService,
        protected FriendInvitationProfilePresenter $friendInvitationProfilePresenter,
        protected LearningDashboardPresenter $learningDashboardPresenter,
        protected IncorrectQuestionListService $incorrectQuestionListService,
        protected QuestionCollectionAccessService $questionCollectionAccessService,
        protected QuestionCollectionIncorrectQuestionService $questionCollectionIncorrectQuestionService,
        protected QuestionCollectionProgressService $questionCollectionProgressService,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function validationRules(): array
    {
        return [
            'question_topic_id' => ['nullable', 'integer', 'exists:question_topics,id'],
            'question_scope' => ['nullable', 'string', 'in:all,basic,specialist'],
            'question_status' => ['nullable', 'string', 'in:all,unanswered,memorized,incorrect,correct,mistake_list'],
            'question_count' => ['nullable', 'integer', 'min:1'],
            'randomize_order' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array{product: ProductAccessDecision, pjm: PjmFreeAccessDecision}
     */
    public function accessDecisions(User $user): array
    {
        return [
            'product' => $this->productAccessResolver->forUser($user),
            'pjm' => $this->pjmFreeAccessResolver->forUser($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildForSessionPage(
        User $user,
        array $input = [],
        ?ProductAccessDecision $productDecision = null,
        ?PjmFreeAccessDecision $pjmDecision = null,
        bool $includeCourseSessions = true,
        bool $includeProfessionalCourses = true,
    ): array {
        $productDecision ??= $this->productAccessResolver->forUser($user);
        $pjmDecision ??= $this->pjmFreeAccessResolver->forUser($user);

        $category = LicenseCategory::query()->find($this->studyContextService->preferredCategoryId($user));
        $questionsCount = $category ? $this->categoryQuestionsCount($category) : 0;
        $topicGroups = $category ? $this->studyTopicGroupsService->topicGroups($category, $user) : collect();
        $coverage = $category
            ? collect($this->pjmCoverageService->categoryCoverage([$category->code]))->first()
            : null;

        $user->load('profile:user_id,preferred_learning_track,auto_remove_incorrect_questions_on_correct');
        $isPjmPreferred = $user->profile?->preferred_learning_track === UserProfile::LEARNING_TRACK_PJM;
        $showPjmEntryTile = $pjmDecision->allowed && $isPjmPreferred;
        $friendInvitationState = $this->friendInvitationProfilePresenter->forOwner($user);
        $dueReviewCount = $this->studyContextService->dueReviewCount($user);
        $rankingPreview = $this->rankingPreview($user);
        $questionScope = $this->stringInput($input, 'question_scope', 'all');
        $filteredTopicGroups = $this->filterTopicGroupsByScope($topicGroups, $questionScope);
        $filteredTopicOptions = $filteredTopicGroups
            ->flatMap(fn (array $group) => $group['options'])
            ->values();
        $lastLearningState = $this->lastLearningState($user, $category, $filteredTopicOptions);
        $requestedTopicId = $this->integerInput($input, 'question_topic_id');
        $selectedTopicId = $filteredTopicOptions->contains(fn (array $option) => $option['id'] === $requestedTopicId)
            ? $requestedTopicId
            : ($lastLearningState['question_topic_id'] ?? ($filteredTopicOptions->first()['id'] ?? null));
        $selectedUiShell = $lastLearningState['ui_shell'] ?? 'exam_like';
        $incorrectListCount = $category
            ? $this->incorrectQuestionListService->activeCountForCategory($user->getKey(), $category->getKey())
            : 0;
        $incorrectListReadMode = (string) config('study.incorrect_question_list.read_mode', 'legacy');
        $incorrectListUiEnabled = (bool) config('study.incorrect_question_list.ui_enabled', false);

        return [
            'category' => $category ? [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
                'short_name' => $this->studyContextService->shortCategoryName($category),
                'questions_count' => $questionsCount,
            ] : null,
            'filters' => [
                'question_topic_id' => $selectedTopicId,
                'ui_shell' => $selectedUiShell,
                'question_scope' => $questionScope,
                'question_status' => $this->stringInput($input, 'question_status', 'all'),
                'randomize_order' => $this->booleanInput($input, 'randomize_order'),
                'question_count' => $this->integerInput($input, 'question_count')
                    ?: $this->defaultQuestionCount($questionsCount, $filteredTopicGroups, $selectedTopicId),
            ],
            'group_options' => $topicGroups->values(),
            'status_options' => $this->statusOptions(),
            'access' => [
                'full_product' => [
                    'allowed' => $productDecision->allowed,
                    'reason' => $productDecision->reason,
                    'activation_url' => route('access.activate', absolute: false),
                ],
            ],
            'learning_overview' => [
                'due_review_count' => $dueReviewCount,
                'incorrect_list_count' => $incorrectListCount,
            ],
            'incorrect_question_list' => [
                'enabled' => $incorrectListUiEnabled,
                'read_mode' => $incorrectListReadMode,
                'index_url' => route('incorrect-questions.index', absolute: false),
                'auto_remove_on_correct' => (bool) ($user->profile?->auto_remove_incorrect_questions_on_correct ?? false),
            ],
            'learning_dashboard' => $this->learningDashboardPresenter->forSessionPage(
                $user,
                $category,
                $topicGroups,
                $productDecision,
                $pjmDecision,
                $dueReviewCount,
                $rankingPreview,
                $incorrectListCount,
                $includeCourseSessions,
            ),
            'ranking_preview' => $rankingPreview,
            'pjm_module' => [
                'available' => $pjmDecision->allowed,
                'reason' => $pjmDecision->reason,
                'preferred' => $isPjmPreferred,
                'show_entry_tile' => $showPjmEntryTile,
                'starter_mode' => $showPjmEntryTile && ! $productDecision->allowed,
                'href' => route('session.pjm', absolute: false),
                'coverage' => $coverage,
            ],
            'professional_courses' => $includeProfessionalCourses
                ? $this->professionalCoursesForUser($user)
                : [],
            'friend_invitation_cta' => $this->friendInvitationCta($friendInvitationState),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildForApi(
        User $user,
        array $input = [],
        ?ProductAccessDecision $productDecision = null,
        ?PjmFreeAccessDecision $pjmDecision = null,
    ): array {
        $productDecision ??= $this->productAccessResolver->forUser($user);
        $pjmDecision ??= $this->pjmFreeAccessResolver->forUser($user);
        $payload = $this->buildForSessionPage(
            $user,
            $input,
            $productDecision,
            $pjmDecision,
            includeCourseSessions: false,
            includeProfessionalCourses: false,
        );

        return [
            'schema_version' => 1,
            ...$payload,
            'modes' => $this->mobileModes($productDecision, (int) $payload['learning_overview']['due_review_count']),
            'actions' => $this->mobileActions(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function statusOptions(): array
    {
        $options = [
            ['value' => 'all', 'label' => 'Wszystkie z tego działu'],
            ['value' => 'unanswered', 'label' => 'Jeszcze nieprzerobione'],
            ['value' => 'incorrect', 'label' => 'Z błędami'],
            ['value' => 'correct', 'label' => 'Dobrze rozwiązane'],
            ['value' => 'memorized', 'label' => 'Utrwalone'],
        ];

        if (
            config('study.incorrect_question_list.ui_enabled', false)
            && config('study.incorrect_question_list.read_mode', 'legacy') === 'list'
        ) {
            $options[] = ['value' => 'mistake_list', 'label' => 'Na mojej liście'];
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected function friendInvitationCta(array $state): array
    {
        return [
            'visible' => (bool) ($state['enabled'] ?? false)
                && (bool) ($state['eligible'] ?? false)
                && ($state['active_guest'] ?? null) === null,
            'can_issue' => (bool) ($state['can_issue'] ?? false),
            'pending_count' => (int) ($state['pending_count'] ?? 0),
            'pending_limit' => (int) ($state['pending_limit'] ?? 0),
            'profile_url' => route('profile.edit', absolute: false).'#zapros-znajomego',
            'reason' => $state['reason'] ?? null,
            'reason_label' => $state['reason_label'] ?? null,
        ];
    }

    /**
     * @param  Collection<int, array{id:int}>  $filteredTopicOptions
     * @return array{question_topic_id:int|null,ui_shell:string|null}|null
     */
    protected function lastLearningState(User $user, ?LicenseCategory $category, Collection $filteredTopicOptions): ?array
    {
        if (! $category || $filteredTopicOptions->isEmpty()) {
            return null;
        }

        $visibleTopicIds = $filteredTopicOptions
            ->pluck('id')
            ->map(fn (mixed $topicId): int => (int) $topicId)
            ->values()
            ->all();
        $resolvedTopicId = null;
        $resolvedUiShell = null;

        $recentSessions = StudySession::query()
            ->regularCategory()
            ->where('user_id', $user->getKey())
            ->where('license_category_id', $category->getKey())
            ->where('mode', 'learn')
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'payload']);

        foreach ($recentSessions as $studySession) {
            $payload = is_array($studySession->payload) ? $studySession->payload : [];

            if ($resolvedUiShell === null) {
                $resolvedUiShell = $this->normalizeLearningUiShell($payload['ui_shell'] ?? null);
            }

            $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
            $topicId = isset($filters['question_topic_id']) && $filters['question_topic_id']
                ? (int) $filters['question_topic_id']
                : null;

            if ($resolvedTopicId === null && $topicId !== null && in_array($topicId, $visibleTopicIds, true)) {
                $resolvedTopicId = $topicId;
            }

            if ($resolvedTopicId !== null && $resolvedUiShell !== null) {
                break;
            }
        }

        if ($resolvedTopicId === null && $resolvedUiShell === null) {
            return null;
        }

        return [
            'question_topic_id' => $resolvedTopicId,
            'ui_shell' => $resolvedUiShell,
        ];
    }

    protected function normalizeLearningUiShell(mixed $uiShell): ?string
    {
        $uiShell = strtolower(trim((string) $uiShell));

        return in_array($uiShell, ['exam_like', 'zen'], true) ? $uiShell : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function professionalCoursesForUser(User $user): array
    {
        $collections = QuestionCollection::query()
            ->where('kind', 'professional_qualification')
            ->where('is_active', true)
            ->where('is_available_to_learners', true)
            ->with([
                'licenseCategory',
                'modules' => fn ($query) => $query
                    ->where('is_active', true)
                    ->withCount('questions'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (QuestionCollection $collection): bool => $this->questionCollectionAccessService
                ->forUser($user, $collection)
                ->allowed)
            ->values();
        $progressByModule = $this->questionCollectionProgressService->moduleProgressFor($user, $collections);

        return $collections
            ->map(fn (QuestionCollection $collection): array => [
                'id' => $collection->getKey(),
                'code' => $collection->code,
                'slug' => $collection->slug,
                'name' => $collection->name,
                'description' => $collection->description,
                'category_name' => $collection->licenseCategory?->name,
                'progress' => $this->questionCollectionProgressService->collectionProgressFor(
                    $collection,
                    $progressByModule,
                ),
                'incorrect_questions' => [
                    'count' => $this->questionCollectionIncorrectQuestionService->activeCountFor($user, $collection),
                    'url' => route('learning.question-collections.incorrect-questions.index', $collection, absolute: false),
                ],
                'modules' => $collection->modules
                    ->map(fn (QuestionModule $module): array => [
                        'id' => $module->getKey(),
                        'code' => $module->code,
                        'name' => $module->name,
                        'description' => $module->description,
                        'questions_count' => $module->questions_count,
                        'progress' => $progressByModule->get($module->getKey()),
                        'start_url' => route('learning.question-collections.modules.start', [
                            'questionCollection' => $collection,
                            'module' => $module,
                        ], absolute: false),
                    ])
                    ->values(),
            ])
            ->values()
            ->all();
    }

    protected function categoryQuestionsCount(LicenseCategory $category): int
    {
        return (int) Cache::remember(
            'session-page:'.self::CACHE_VERSION.":category-questions-count:{$category->getKey()}",
            now()->addMinutes(10),
            fn () => Question::query()
                ->where('license_category_id', $category->getKey())
                ->where('is_active', true)
                ->readyForDelivery()
                ->count(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function rankingPreview(User $user): array
    {
        $rating = RankedPlayerRating::query()
            ->where('user_id', $user->getKey())
            ->first();

        if (! $rating) {
            return [
                'position' => null,
                'rating' => null,
                'matches_played' => 0,
                'message' => 'Pierwszy pojedynek ustawi Twoją pozycję.',
            ];
        }

        $betterPlayers = RankedPlayerRating::query()
            ->whereHas('user')
            ->where(function ($query) use ($rating): void {
                $query
                    ->where('rating', '>', $rating->rating)
                    ->orWhere(function ($query) use ($rating): void {
                        $query
                            ->where('rating', $rating->rating)
                            ->where('peak_rating', '>', $rating->peak_rating);
                    })
                    ->orWhere(function ($query) use ($rating): void {
                        $query
                            ->where('rating', $rating->rating)
                            ->where('peak_rating', $rating->peak_rating)
                            ->where('wins', '>', $rating->wins);
                    })
                    ->orWhere(function ($query) use ($rating): void {
                        $query
                            ->where('rating', $rating->rating)
                            ->where('peak_rating', $rating->peak_rating)
                            ->where('wins', $rating->wins)
                            ->where('matches_played', '>', $rating->matches_played);
                    })
                    ->orWhere(function ($query) use ($rating): void {
                        $query
                            ->where('rating', $rating->rating)
                            ->where('peak_rating', $rating->peak_rating)
                            ->where('wins', $rating->wins)
                            ->where('matches_played', $rating->matches_played)
                            ->where('user_id', '<', $rating->user_id);
                    });
            })
            ->count();

        return [
            'position' => $betterPlayers + 1,
            'rating' => $rating->rating,
            'matches_played' => $rating->matches_played,
            'message' => $rating->matches_played > 0
                ? 'Świetna robota! Trzymaj tak dalej.'
                : 'Pierwszy pojedynek ustawi Twoją pozycję.',
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $topicGroups
     */
    protected function defaultQuestionCount(int $categoryQuestionsCount, Collection $topicGroups, ?int $selectedTopicId): int
    {
        if ($selectedTopicId !== null) {
            $selectedTopicCount = $topicGroups
                ->flatMap(fn (array $group) => $group['options'])
                ->firstWhere('id', $selectedTopicId)['questions_count'] ?? null;

            if (is_int($selectedTopicCount) && $selectedTopicCount > 0) {
                return $selectedTopicCount;
            }
        }

        return max($categoryQuestionsCount, 1);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $topicGroups
     * @return Collection<int, array<string, mixed>>
     */
    protected function filterTopicGroupsByScope(Collection $topicGroups, string $questionScope): Collection
    {
        return match ($questionScope) {
            'basic' => $topicGroups
                ->filter(fn (array $group) => $group['label'] === 'Pytania podstawowe')
                ->values(),
            'specialist' => $topicGroups
                ->filter(fn (array $group) => $group['label'] === 'Pytania specjalistyczne')
                ->values(),
            default => $topicGroups->values(),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mobileModes(ProductAccessDecision $productDecision, int $dueReviewCount): array
    {
        return [
            [
                'key' => 'classic',
                'label' => 'Nauka klasyczna',
                'session_mode' => StudySessionManager::MODE_LEARN,
                'ui_shell' => StudySessionManager::UI_SHELL_EXAM_LIKE,
                'enabled' => $productDecision->allowed,
                'api_supported' => true,
            ],
            [
                'key' => 'zen',
                'label' => 'Zen mode',
                'session_mode' => StudySessionManager::MODE_LEARN,
                'ui_shell' => StudySessionManager::UI_SHELL_ZEN,
                'enabled' => $productDecision->allowed,
                'api_supported' => true,
            ],
            [
                'key' => 'exam',
                'label' => 'Egzamin próbny',
                'session_mode' => StudySessionManager::MODE_EXAM,
                'ui_shell' => StudySessionManager::UI_SHELL_EXAM,
                'enabled' => $productDecision->allowed,
                'api_supported' => true,
                'question_count' => 32,
            ],
            [
                'key' => 'memory',
                'label' => 'Trener pamięci',
                'session_mode' => StudySessionManager::MODE_SR_REVIEW,
                'ui_shell' => null,
                'enabled' => $productDecision->allowed,
                'api_supported' => true,
                'due_count' => $dueReviewCount,
            ],
            [
                'key' => 'traffic-signs',
                'label' => 'Znaki drogowe',
                'session_mode' => null,
                'ui_shell' => null,
                'enabled' => $productDecision->allowed,
                'api_supported' => false,
            ],
            [
                'key' => 'ranking',
                'label' => 'Ranking',
                'session_mode' => null,
                'ui_shell' => null,
                'enabled' => $productDecision->allowed,
                'api_supported' => true,
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function mobileActions(): array
    {
        return [
            'start_session' => [
                'method' => 'POST',
                'api_url' => route('api.v1.sessions.store', absolute: false),
                'web_url' => route('study-sessions.store', absolute: false),
            ],
            'resume_session' => [
                'method' => 'GET',
                'api_url' => route('api.v1.sessions.current', absolute: false),
                'web_url' => route('study-sessions.current', absolute: false),
            ],
            'answer_current_session' => [
                'method' => 'POST',
                'api_url' => route('api.v1.sessions.current.answers.store', absolute: false),
                'web_url' => route('study-sessions.current.answers.store', absolute: false),
            ],
            'complete_current_session' => [
                'method' => 'POST',
                'api_url' => route('api.v1.sessions.current.complete', absolute: false),
                'web_url' => route('study-sessions.current.complete', absolute: false),
            ],
            'review_queue' => [
                'method' => 'GET',
                'api_url' => route('api.v1.me.review-queue', absolute: false),
                'web_url' => route('review-queue.index', absolute: false),
            ],
            'ranking_overview' => [
                'method' => 'GET',
                'api_url' => route('api.v1.ranked.overview', absolute: false),
                'web_url' => route('session.ranking', absolute: false),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function stringInput(array $input, string $key, string $default): string
    {
        $value = $input[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function integerInput(array $input, string $key): ?int
    {
        $value = $input[$key] ?? null;

        return $value !== null && $value !== '' ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function booleanInput(array $input, string $key): bool
    {
        if (! array_key_exists($key, $input)) {
            return false;
        }

        return filter_var($input[$key], FILTER_VALIDATE_BOOLEAN);
    }
}
