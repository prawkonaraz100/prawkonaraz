<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\ReviewMemoryProgress;
use App\Models\UserQuestionProgress;
use App\Support\MediaUrlResolver;
use App\Support\ReviewMemoryLegacySignalMapper;
use App\Support\ReviewMemorySignalService;
use App\Support\ReviewMemoryVerifiedSignalService;
use App\Support\ReviewPlannerService;
use App\Support\ReviewTrainerAnalyticsService;
use App\Support\StudyContextService;
use App\Support\StudySessionManager;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ReviewQueueController extends Controller
{
    public function index(
        Request $request,
        MediaUrlResolver $mediaUrlResolver,
        ReviewMemorySignalService $reviewMemorySignalService,
        ReviewMemoryVerifiedSignalService $reviewMemoryVerifiedSignalService,
        ReviewMemoryLegacySignalMapper $reviewMemoryLegacySignalMapper,
        ReviewPlannerService $reviewPlannerService,
        ReviewTrainerAnalyticsService $reviewTrainerAnalyticsService,
        StudyContextService $studyContextService,
    ): Response {
        [$categories, $questions, $categoryId, $plan, $telemetry] = $this->payload(
            $request,
            $mediaUrlResolver,
            $reviewMemorySignalService,
            $reviewMemoryVerifiedSignalService,
            $reviewMemoryLegacySignalMapper,
            $reviewPlannerService,
            $reviewTrainerAnalyticsService,
            $studyContextService,
            includeQuestions: false,
        );

        return Inertia::render('ReviewQueue/Index', [
            'categories' => $categories,
            'filters' => [
                'category' => $categoryId,
            ],
            'stats' => [
                'ready_for_review_count' => $categories->sum('due_count'),
            ],
            'plan' => $plan,
            'telemetry' => $telemetry,
            'questions' => $questions,
        ]);
    }

    public function apiIndex(
        Request $request,
        MediaUrlResolver $mediaUrlResolver,
        ReviewMemorySignalService $reviewMemorySignalService,
        ReviewMemoryVerifiedSignalService $reviewMemoryVerifiedSignalService,
        ReviewMemoryLegacySignalMapper $reviewMemoryLegacySignalMapper,
        ReviewPlannerService $reviewPlannerService,
        ReviewTrainerAnalyticsService $reviewTrainerAnalyticsService,
        StudyContextService $studyContextService,
    ): JsonResponse {
        $includeQuestions = $this->validateIncludeQuestions($request);

        [$categories, $questions, $categoryId, $plan, $telemetry] = $this->payload(
            $request,
            $mediaUrlResolver,
            $reviewMemorySignalService,
            $reviewMemoryVerifiedSignalService,
            $reviewMemoryLegacySignalMapper,
            $reviewPlannerService,
            $reviewTrainerAnalyticsService,
            $studyContextService,
            includeQuestions: $includeQuestions,
        );

        return response()->json([
            'data' => [
                'questions' => $questions->values(),
            ],
            'meta' => [
                'category' => $categoryId,
                'ready_for_review_count' => $categories->sum('due_count'),
                'categories' => $categories->values(),
                'plan' => $plan,
                'telemetry' => $telemetry,
            ],
        ]);
    }

    /**
     * @return array{Collection<int, array<string, mixed>>, Collection<int, array<string, mixed>>, int|null, array<string, mixed>, array<string, mixed>}
     */
    protected function payload(
        Request $request,
        MediaUrlResolver $mediaUrlResolver,
        ReviewMemorySignalService $reviewMemorySignalService,
        ReviewMemoryVerifiedSignalService $reviewMemoryVerifiedSignalService,
        ReviewMemoryLegacySignalMapper $reviewMemoryLegacySignalMapper,
        ReviewPlannerService $reviewPlannerService,
        ReviewTrainerAnalyticsService $reviewTrainerAnalyticsService,
        StudyContextService $studyContextService,
        bool $includeQuestions = true,
    ): array {
        $request->validate([
            'category' => ['nullable', 'integer', 'exists:license_categories,id'],
        ]);

        $user = $request->user();
        $categoryId = $studyContextService->requestedOrPreferredCategoryId($request);
        $activeCategories = $studyContextService->activeCategories($user);
        $allowedCategoryIds = $activeCategories
            ->pluck('id')
            ->map(fn ($categoryId) => (int) $categoryId)
            ->all();
        $today = today();

        $categories = UserQuestionProgress::query()
            ->selectRaw('questions.license_category_id as category_id, license_categories.code, license_categories.name, license_categories.sort_order, COUNT(DISTINCT user_question_progress.question_id) as due_count')
            ->join('questions', 'questions.id', '=', 'user_question_progress.question_id')
            ->join('license_categories', 'license_categories.id', '=', 'questions.license_category_id')
            ->leftJoin('review_memory_progress', function ($join) use ($user): void {
                $join
                    ->on('review_memory_progress.question_id', '=', 'user_question_progress.question_id')
                    ->where('review_memory_progress.user_id', '=', $user->getKey());
            })
            ->where('user_question_progress.user_id', $user->getKey())
            ->where(function ($query) use ($today): void {
                $query
                    ->where(function ($memoryQuery) use ($today): void {
                        $memoryQuery
                            ->whereNotNull('review_memory_progress.id')
                            ->where(function ($stateQuery) use ($today): void {
                                $stateQuery
                                    ->where('review_memory_progress.verified_memory_state', ReviewMemoryProgress::STATE_NEEDS_RECOVERY)
                                    ->orWhereDate('review_memory_progress.next_verified_review_at', '<=', $today);
                            });
                    })
                    ->orWhere(function ($classicQuery) use ($today): void {
                        $classicQuery
                            ->whereNull('review_memory_progress.id')
                            ->where(function ($classicStateQuery) use ($today): void {
                                $classicStateQuery
                                    ->whereDate('user_question_progress.next_review_at', '<=', $today)
                                    ->orWhere('user_question_progress.total_attempts', '>', 0);
                            });
                    });
            })
            ->where('questions.is_active', true)
            ->whereNull('questions.delivery_issue')
            ->whereNotExists(function ($query) use ($today, $user): void {
                $query
                    ->selectRaw('1')
                    ->from('study_session_answers as today_review_answers')
                    ->join('study_sessions as today_review_sessions', 'today_review_sessions.id', '=', 'today_review_answers.study_session_id')
                    ->whereColumn('today_review_answers.question_id', 'user_question_progress.question_id')
                    ->where('today_review_sessions.user_id', $user->getKey())
                    ->where('today_review_sessions.mode', StudySessionManager::MODE_SR_REVIEW)
                    ->whereDate('today_review_answers.answered_at', $today);
            })
            ->when($allowedCategoryIds !== [], function ($query) use ($allowedCategoryIds): void {
                $query->whereIn('questions.license_category_id', $allowedCategoryIds);
            })
            ->groupBy(
                'questions.license_category_id',
                'license_categories.code',
                'license_categories.name',
                'license_categories.sort_order',
            )
            ->orderBy('license_categories.sort_order')
            ->get()
            ->map(fn (UserQuestionProgress $progress) => [
                'id' => (int) $progress->category_id,
                'code' => (string) $progress->code,
                'name' => (string) $progress->name,
                'short_name' => (string) $progress->code,
                'due_count' => (int) $progress->due_count,
            ])
            ->values();

        $plan = $this->planForResponse(
            $reviewPlannerService->plan($user, $categoryId, $allowedCategoryIds),
            $includeQuestions,
        );
        $categories = $this->categoriesWithSelectedPlan($categories, $activeCategories, $categoryId, $plan);
        $telemetry = $reviewTrainerAnalyticsService->summary($user, $categoryId, $allowedCategoryIds);

        if (! $includeQuestions) {
            return [$categories, collect(), $categoryId, $plan, $telemetry];
        }

        $previewQuestionIds = (array) ($plan['preview_question_ids'] ?? []);
        $orderByQuestionId = array_flip($previewQuestionIds);

        $progressEntries = $previewQuestionIds === []
            ? collect()
            : UserQuestionProgress::query()
                ->with(['question.media', 'question.licenseCategory', 'question.questionTopic'])
                ->where('user_id', $user->getKey())
                ->whereIn('question_id', $previewQuestionIds)
                ->get()
                ->sortBy(fn (UserQuestionProgress $progress): int => $orderByQuestionId[$progress->question_id] ?? PHP_INT_MAX)
                ->values();
        $progressByQuestionId = $progressEntries->keyBy('question_id');
        $missingQuestionIds = array_values(array_diff(
            array_map('intval', $previewQuestionIds),
            $progressEntries
                ->pluck('question_id')
                ->map(fn (mixed $questionId): int => (int) $questionId)
                ->all(),
        ));
        $newCandidateQuestionsById = $missingQuestionIds === []
            ? collect()
            : Question::query()
                ->with(['media', 'licenseCategory', 'questionTopic'])
                ->whereIn('id', $missingQuestionIds)
                ->get()
                ->keyBy('id');
        $verifiedProgressByQuestionId = $previewQuestionIds === []
            ? collect()
            : ReviewMemoryProgress::query()
                ->where('user_id', $user->getKey())
                ->whereIn('question_id', $previewQuestionIds)
                ->get()
                ->keyBy('question_id');

        $questions = collect($previewQuestionIds)
            ->map(function (mixed $questionId) use ($mediaUrlResolver, $newCandidateQuestionsById, $progressByQuestionId, $reviewMemorySignalService, $reviewMemoryVerifiedSignalService, $reviewMemoryLegacySignalMapper, $verifiedProgressByQuestionId): ?array {
                $questionId = (int) $questionId;
                $progress = $progressByQuestionId->get($questionId);
                $verifiedProgress = $verifiedProgressByQuestionId->get($questionId);

                if ($progress instanceof UserQuestionProgress) {
                    return $this->questionPreviewFromProgress(
                        $progress,
                        $verifiedProgress,
                        $mediaUrlResolver,
                        $reviewMemorySignalService,
                        $reviewMemoryVerifiedSignalService,
                        $reviewMemoryLegacySignalMapper,
                    );
                }

                $question = $newCandidateQuestionsById->get($questionId);

                if ($question instanceof Question && $verifiedProgress instanceof ReviewMemoryProgress) {
                    return $this->questionPreviewFromVerifiedProgress(
                        $question,
                        $verifiedProgress,
                        $mediaUrlResolver,
                        $reviewMemoryVerifiedSignalService,
                        $reviewMemoryLegacySignalMapper,
                    );
                }

                return $question instanceof Question
                    ? $this->questionPreviewFromNewCandidate($question, $mediaUrlResolver)
                    : null;
            })
            ->filter()
            ->values();

        return [$categories, $questions, $categoryId, $plan, $telemetry];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $categories
     * @param  Collection<int, mixed>  $activeCategories
     * @param  array<string, mixed>  $plan
     * @return Collection<int, array<string, mixed>>
     */
    protected function categoriesWithSelectedPlan(
        Collection $categories,
        Collection $activeCategories,
        ?int $categoryId,
        array $plan,
    ): Collection {
        $candidateCount = (int) ($plan['candidate_count'] ?? 0);

        if (! $categoryId || $candidateCount <= 0) {
            return $categories;
        }

        if ($categories->contains(fn (array $category): bool => (int) $category['id'] === $categoryId)) {
            return $categories
                ->map(function (array $category) use ($candidateCount, $categoryId): array {
                    if ((int) $category['id'] !== $categoryId) {
                        return $category;
                    }

                    $category['due_count'] = $candidateCount;

                    return $category;
                })
                ->values();
        }

        $activeCategory = $activeCategories->firstWhere('id', $categoryId);

        if (! $activeCategory) {
            return $categories;
        }

        return $categories
            ->push([
                'id' => (int) $activeCategory->getKey(),
                'code' => (string) $activeCategory->code,
                'name' => (string) $activeCategory->name,
                'short_name' => (string) $activeCategory->code,
                'due_count' => $candidateCount,
            ])
            ->values();
    }

    protected function validateIncludeQuestions(Request $request): bool
    {
        $request->validate([
            'include_questions' => [
                'nullable',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) === null) {
                        $fail('Pole include_questions musi byc wartoscia logiczna.');
                    }
                },
            ],
        ]);

        $value = $request->query('include_questions');

        if ($value === null || $value === '') {
            return true;
        }

        return (bool) filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    protected function planForResponse(array $plan, bool $includeQuestions): array
    {
        if (! $includeQuestions) {
            unset($plan['preview_question_ids']);
        }

        return $plan;
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionPreviewFromProgress(
        UserQuestionProgress $progress,
        ?ReviewMemoryProgress $verifiedProgress,
        MediaUrlResolver $mediaUrlResolver,
        ReviewMemorySignalService $reviewMemorySignalService,
        ReviewMemoryVerifiedSignalService $reviewMemoryVerifiedSignalService,
        ReviewMemoryLegacySignalMapper $reviewMemoryLegacySignalMapper,
    ): array {
        $question = $progress->question;
        $memorySignal = $this->memorySignal(
            $progress,
            $verifiedProgress,
            $reviewMemorySignalService,
            $reviewMemoryVerifiedSignalService,
            $reviewMemoryLegacySignalMapper,
        );
        $nextReviewAt = $verifiedProgress?->next_verified_review_at ?? $progress->next_review_at;

        return [
            'question_id' => $question->getKey(),
            'external_id' => $question->external_id,
            'prompt' => $question->prompt,
            'difficulty' => $question->difficulty,
            'points' => $question->points,
            'next_review_at' => $nextReviewAt?->toDateString(),
            'total_attempts' => $progress->total_attempts,
            'correct_count' => $progress->correct_count,
            'incorrect_count' => $progress->incorrect_count,
            'correct_streak' => $progress->correct_streak,
            'last_quality' => $progress->last_quality,
            'memory_signal' => $memorySignal,
            'license_category' => [
                'id' => $question->licenseCategory?->getKey(),
                'code' => $question->licenseCategory?->code,
                'name' => $question->licenseCategory?->name,
                'short_name' => $question->licenseCategory?->code,
            ],
            'topic' => $question->questionTopic ? [
                'id' => $question->questionTopic->getKey(),
                'key' => $question->questionTopic->key,
                'name' => $question->questionTopic->name,
            ] : null,
            'media' => $question->media
                ->map(fn (QuestionMedia $media) => $this->transformMedia($media, $mediaUrlResolver))
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionPreviewFromNewCandidate(Question $question, MediaUrlResolver $mediaUrlResolver): array
    {
        return [
            'question_id' => $question->getKey(),
            'external_id' => $question->external_id,
            'prompt' => $question->prompt,
            'difficulty' => $question->difficulty,
            'points' => $question->points,
            'next_review_at' => null,
            'total_attempts' => 0,
            'correct_count' => 0,
            'incorrect_count' => 0,
            'correct_streak' => 0,
            'last_quality' => 0,
            'memory_signal' => $this->newCandidateMemorySignal($question),
            'license_category' => [
                'id' => $question->licenseCategory?->getKey(),
                'code' => $question->licenseCategory?->code,
                'name' => $question->licenseCategory?->name,
                'short_name' => $question->licenseCategory?->code,
            ],
            'topic' => $question->questionTopic ? [
                'id' => $question->questionTopic->getKey(),
                'key' => $question->questionTopic->key,
                'name' => $question->questionTopic->name,
            ] : null,
            'media' => $question->media
                ->map(fn (QuestionMedia $media) => $this->transformMedia($media, $mediaUrlResolver))
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionPreviewFromVerifiedProgress(
        Question $question,
        ReviewMemoryProgress $verifiedProgress,
        MediaUrlResolver $mediaUrlResolver,
        ReviewMemoryVerifiedSignalService $reviewMemoryVerifiedSignalService,
        ReviewMemoryLegacySignalMapper $reviewMemoryLegacySignalMapper,
    ): array {
        $signal = $reviewMemoryVerifiedSignalService->signal($verifiedProgress);

        return [
            'question_id' => $question->getKey(),
            'external_id' => $question->external_id,
            'prompt' => $question->prompt,
            'difficulty' => $question->difficulty,
            'points' => $question->points,
            'next_review_at' => $verifiedProgress->next_verified_review_at?->toDateString(),
            'total_attempts' => $verifiedProgress->verified_attempts_count,
            'correct_count' => $verifiedProgress->verified_correct_count,
            'incorrect_count' => (int) ($verifiedProgress->verified_unknown_count ?? 0)
                + (int) ($verifiedProgress->verified_incorrect_count ?? 0),
            'correct_streak' => $verifiedProgress->verified_correct_streak,
            'last_quality' => $verifiedProgress->last_verified_result === ReviewMemoryProgress::RESULT_CORRECT ? 5 : 1,
            'memory_signal' => $reviewMemoryLegacySignalMapper->verifiedPreviewSignal(
                $signal,
                max(1, min(5, (int) $question->difficulty)),
            ),
            'license_category' => [
                'id' => $question->licenseCategory?->getKey(),
                'code' => $question->licenseCategory?->code,
                'name' => $question->licenseCategory?->name,
                'short_name' => $question->licenseCategory?->code,
            ],
            'topic' => $question->questionTopic ? [
                'id' => $question->questionTopic->getKey(),
                'key' => $question->questionTopic->key,
                'name' => $question->questionTopic->name,
            ] : null,
            'media' => $question->media
                ->map(fn (QuestionMedia $media) => $this->transformMedia($media, $mediaUrlResolver))
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function transformMedia(QuestionMedia $media, MediaUrlResolver $mediaUrlResolver): array
    {
        return [
            'kind' => $media->kind,
            'url' => $mediaUrlResolver->resolve($media->path, $media->disk),
            'poster_url' => $mediaUrlResolver->resolve($media->poster_path, $media->disk),
            'mime_type' => $media->mime_type,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function memorySignal(
        UserQuestionProgress $progress,
        ?ReviewMemoryProgress $verifiedProgress,
        ReviewMemorySignalService $reviewMemorySignalService,
        ReviewMemoryVerifiedSignalService $reviewMemoryVerifiedSignalService,
        ReviewMemoryLegacySignalMapper $reviewMemoryLegacySignalMapper,
    ): array {
        if (! $verifiedProgress) {
            return $reviewMemoryLegacySignalMapper->classicPreviewSignal(
                $reviewMemorySignalService->signal($progress),
            );
        }

        $signal = $reviewMemoryVerifiedSignalService->signal($verifiedProgress);
        $difficulty = max(1, min(5, (int) ($progress->question?->difficulty ?? 1)));

        return $reviewMemoryLegacySignalMapper->verifiedPreviewSignal($signal, $difficulty);
    }

    /**
     * @return array<string, int|string>
     */
    protected function newCandidateMemorySignal(Question $question): array
    {
        return [
            'version' => ReviewMemorySignalService::VERSION,
            'source' => 'new_candidate',
            'memory_state' => ReviewMemorySignalService::STATE_NEW,
            'plan_segment' => ReviewMemorySignalService::SEGMENT_REINFORCE,
            'leech_score' => 0,
            'stability_score' => 0,
            'difficulty_score' => max(1, min(5, (int) $question->difficulty)),
            'overdue_days' => 0,
            'recovery_score' => 0,
        ];
    }
}
