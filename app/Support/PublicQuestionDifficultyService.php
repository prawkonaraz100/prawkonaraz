<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicQuestionDifficultyService
{
    protected const CONFIDENCE_TARGET_USERS = 15;

    /**
     * @var array<string, array{label: string, description: string, field: string}>
     */
    protected const RANKINGS = [
        'overall' => [
            'label' => 'Najtrudniejsze ogółem',
            'description' => 'Łączny wynik błędów, powracających pomyłek, czasu odpowiedzi i drogi do utrwalenia.',
            'field' => 'difficulty_score',
        ],
        'first_try' => [
            'label' => 'Najczęściej mylone na starcie',
            'description' => 'Pytania, na których kursanci najczęściej mylą się przy pierwszym kontakcie.',
            'field' => 'first_try_error_pct',
        ],
        'repeat_fail' => [
            'label' => 'Najbardziej podchwytliwe',
            'description' => 'Pytania, które wracają z błędami nawet po wcześniejszych próbach.',
            'field' => 'repeat_fail_pct',
        ],
        'mastery_lag' => [
            'label' => 'Najdłużej utrwalane',
            'description' => 'Pytania, które najwolniej przechodzą do stanu utrwalenia.',
            'field' => 'mastery_lag_score',
        ],
    ];

    public function __construct(
        protected StudyContextService $studyContextService,
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?LicenseCategory $selectedCategory = null, string $ranking = 'overall'): array
    {
        $rankingKey = array_key_exists($ranking, self::RANKINGS) ? $ranking : 'overall';
        $windowDays = max((int) config('study.question_analytics_window_days', 365), 1);
        $cacheKey = sprintf(
            'public-question-difficulty:v1:%s:%s:%d',
            $rankingKey,
            $selectedCategory?->getKey() ?? 'all',
            $windowDays,
        );

        if (app()->environment('testing')) {
            return $this->buildPayload($selectedCategory, $rankingKey, $windowDays);
        }

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn (): array => $this->buildPayload($selectedCategory, $rankingKey, $windowDays),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(?LicenseCategory $selectedCategory, string $rankingKey, int $windowDays): array
    {
        $visibleCategories = $this->studyContextService->visibleCategoriesQuery()
            ->orderBy('sort_order')
            ->get(['id', 'code', 'slug', 'name', 'description', 'sort_order']);
        $visibleCategoryIds = $visibleCategories->pluck('id')->all();

        $answerRows = $this->answerRows($selectedCategory, $visibleCategoryIds, $windowDays);
        $progressRows = $this->progressRows($selectedCategory, $visibleCategoryIds, $windowDays)
            ->groupBy('question_id');

        $globalMedianResponseMs = $answerRows
            ->pluck('response_time_ms')
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->median();

        $questionStats = $this->buildQuestionStats($answerRows, $progressRows, $globalMedianResponseMs)
            ->values();

        $categoryCards = $this->buildCategoryCards($visibleCategories, $questionStats, $answerRows);
        $rankedQuestions = $this->rankQuestions($questionStats, $rankingKey);
        $topQuestionIds = $rankedQuestions
            ->take(12)
            ->pluck('question_id')
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->all();

        $questionAssets = $this->questionAssets($topQuestionIds);

        $topQuestions = $rankedQuestions
            ->take(12)
            ->map(function (array $question) use ($questionAssets): array {
                $asset = $questionAssets->get($question['question_id']);

                return [
                    ...$question,
                    'media' => $asset['media'] ?? null,
                    'practice_url' => '/nauka',
                ];
            })
            ->values();

        $questionsBaseQuery = Question::query()
            ->where('is_active', true)
            ->readyForDelivery()
            ->when(
                $selectedCategory,
                fn ($query) => $query->where('license_category_id', $selectedCategory->getKey()),
                fn ($query) => $query->whereIn('license_category_id', $visibleCategories->pluck('id')),
            );

        $distinctUserCount = $answerRows
            ->pluck('user_id')
            ->unique()
            ->count();

        $activeCategoryCount = $categoryCards
            ->filter(fn (array $category): bool => $category['answers_count'] > 0)
            ->count();

        return [
            'page' => [
                'title' => $selectedCategory
                    ? "Najtrudniejsze pytania na prawo jazdy kategorii {$selectedCategory->code}"
                    : 'Najtrudniejsze pytania na prawo jazdy',
                'description' => $selectedCategory
                    ? "Publiczny ranking pytań, które sprawiają największą trudność kursantom kategorii {$selectedCategory->code}."
                    : 'Publiczny ranking pytań, które realnie sprawiają kursantom największą trudność.',
                'canonical_path' => $selectedCategory
                    ? '/najtrudniejsze-pytania-na-prawo-jazdy/kategoria/'.$selectedCategory->slug
                    : '/najtrudniejsze-pytania-na-prawo-jazdy',
            ],
            'selected_category' => $selectedCategory ? [
                'id' => $selectedCategory->getKey(),
                'code' => $selectedCategory->code,
                'slug' => $selectedCategory->slug,
                'name' => $selectedCategory->name,
                'short_name' => $this->studyContextService->shortCategoryName($selectedCategory),
                'description' => $selectedCategory->description,
            ] : null,
            'summary' => [
                'questions_total' => $questionsBaseQuery->count(),
                'questions_analyzed' => $questionStats->count(),
                'answers_count' => $answerRows->count(),
                'users_count' => $distinctUserCount,
                'active_categories_count' => $activeCategoryCount,
                'window_label' => $this->windowLabel($windowDays),
            ],
            'ranking' => [
                'key' => $rankingKey,
                ...self::RANKINGS[$rankingKey],
            ],
            'ranking_options' => collect(self::RANKINGS)
                ->map(fn (array $definition, string $key) => [
                    'key' => $key,
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                ])
                ->values(),
            'categories' => $categoryCards->values(),
            'featured_question' => $topQuestions->first(),
            'top_questions' => $topQuestions,
            'top_topics' => $this->buildTopTopics($questionStats)->take(8)->values(),
            'methodology' => [
                [
                    'title' => 'Pierwsza pomylka',
                    'description' => 'Pokazuje, jak czesto kursanci myla sie juz przy pierwszym kontakcie z pytaniem.',
                ],
                [
                    'title' => 'Powracajace bledy',
                    'description' => 'Mierzy, czy pytanie nadal wraca z pomylkami po wczesniejszych probach.',
                ],
                [
                    'title' => 'Czas odpowiedzi',
                    'description' => 'Uwzgledniamy pytania, nad ktorymi kursanci zatrzymuja sie wyraznie dluzej.',
                ],
                [
                    'title' => 'Droga do utrwalenia',
                    'description' => 'Patrzymy, czy pytanie szybko przechodzi do stanu utrwalenia, czy zostaje problemem na dluzej.',
                ],
            ],
        ];
    }

    protected function windowLabel(int $days): string
    {
        return "Ostatnie {$days} dni";
    }

    /**
     * @return Collection<int, object>
     */
    protected function answerRows(?LicenseCategory $selectedCategory, array $visibleCategoryIds, int $windowDays): Collection
    {
        $windowStart = now()->subDays($windowDays);

        return DB::table('study_session_answers as answers')
            ->join('study_sessions as sessions', 'sessions.id', '=', 'answers.study_session_id')
            ->join('questions as questions', 'questions.id', '=', 'answers.question_id')
            ->join('license_categories as categories', 'categories.id', '=', 'questions.license_category_id')
            ->leftJoin('question_topics as topics', 'topics.id', '=', 'questions.question_topic_id')
            ->where('questions.is_active', true)
            ->whereNull('questions.delivery_issue')
            ->where(function ($query) use ($windowStart): void {
                $query
                    ->where('answers.answered_at', '>=', $windowStart)
                    ->orWhere(function ($innerQuery) use ($windowStart): void {
                        $innerQuery
                            ->whereNull('answers.answered_at')
                            ->where('answers.created_at', '>=', $windowStart);
                    });
            })
            ->when(
                $selectedCategory,
                fn ($query) => $query->where('questions.license_category_id', $selectedCategory->getKey()),
                fn ($query) => $query->whereIn('questions.license_category_id', $visibleCategoryIds),
            )
            ->orderBy('answers.question_id')
            ->orderBy('sessions.user_id')
            ->orderByRaw('COALESCE(answers.answered_at, answers.created_at)')
            ->orderBy('answers.id')
            ->get([
                'answers.id',
                'answers.question_id',
                'answers.is_correct',
                'answers.response_time_ms',
                DB::raw('COALESCE(answers.answered_at, answers.created_at) as answered_at_value'),
                'sessions.user_id',
                'questions.external_id',
                'questions.prompt',
                'questions.difficulty',
                'questions.points',
                'questions.license_category_id',
                'questions.question_topic_id',
                'categories.code as category_code',
                'categories.slug as category_slug',
                'categories.name as category_name',
                'topics.key as topic_key',
                'topics.name as topic_name',
            ]);
    }

    /**
     * @return Collection<int, object>
     */
    protected function progressRows(?LicenseCategory $selectedCategory, array $visibleCategoryIds, int $windowDays): Collection
    {
        $windowStart = now()->subDays($windowDays);

        return DB::table('user_question_progress as progress')
            ->join('questions', 'questions.id', '=', 'progress.question_id')
            ->where('questions.is_active', true)
            ->whereNull('questions.delivery_issue')
            ->where('progress.last_answered_at', '>=', $windowStart)
            ->when(
                $selectedCategory,
                fn ($query) => $query->where('questions.license_category_id', $selectedCategory->getKey()),
                fn ($query) => $query->whereIn('questions.license_category_id', $visibleCategoryIds),
            )
            ->get([
                'progress.user_id',
                'progress.question_id',
                'progress.total_attempts',
                'progress.repetitions',
                'progress.correct_streak',
                'progress.last_quality',
                'progress.next_review_at',
            ]);
    }

    /**
     * @param  Collection<int, object>  $answerRows
     * @param  Collection<int|string, Collection<int, object>>  $progressRows
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildQuestionStats(Collection $answerRows, Collection $progressRows, mixed $globalMedianResponseMs): Collection
    {
        return $answerRows
            ->groupBy('question_id')
            ->map(function (Collection $questionAnswers, int|string $questionId) use ($progressRows, $globalMedianResponseMs): array {
                $firstAnswer = $questionAnswers->first();
                $answersByUser = $questionAnswers
                    ->groupBy('user_id')
                    ->map(function (Collection $userAnswers): Collection {
                        return $userAnswers
                            ->sortBy(fn (object $answer) => sprintf(
                                '%s-%010d',
                                (string) $answer->answered_at_value,
                                (int) $answer->id,
                            ))
                            ->values();
                    });

                $firstTryWrongUsers = 0;
                $repeatEligibleUsers = 0;
                $repeatFailUsers = 0;

                foreach ($answersByUser as $userAnswers) {
                    $first = $userAnswers->first();

                    if (! $first?->is_correct) {
                        $firstTryWrongUsers++;
                    }

                    if ($userAnswers->count() > 1) {
                        $repeatEligibleUsers++;

                        if ($userAnswers->slice(1)->contains(fn (object $answer): bool => ! $answer->is_correct)) {
                            $repeatFailUsers++;
                        }
                    }
                }

                $responseTimes = $questionAnswers
                    ->pluck('response_time_ms')
                    ->filter(fn (mixed $value): bool => filled($value))
                    ->map(fn (mixed $value): int => (int) $value);

                $medianResponseMs = $responseTimes->median();
                $progressEntries = $progressRows->get($questionId, collect());
                $progressUserCount = $progressEntries->count();
                $masteredEntries = $progressEntries->filter(
                    fn (object $progress): bool => QuestionProgressManager::isMasteredSnapshot(
                        (int) $progress->total_attempts,
                        (int) $progress->repetitions,
                        (int) $progress->correct_streak,
                        $progress->last_quality !== null ? (int) $progress->last_quality : null,
                        $progress->next_review_at,
                    )
                );
                $masteredRatePct = $progressUserCount > 0
                    ? round(($masteredEntries->count() / $progressUserCount) * 100, 1)
                    : 0.0;
                $avgAttempts = $progressUserCount > 0
                    ? round((float) $progressEntries->avg('total_attempts'), 1)
                    : 0.0;

                $firstTryErrorPct = round(($firstTryWrongUsers / max($answersByUser->count(), 1)) * 100, 1);
                $repeatFailPct = $repeatEligibleUsers > 0
                    ? round(($repeatFailUsers / $repeatEligibleUsers) * 100, 1)
                    : 0.0;
                $timePressureScore = $this->timePressureScore($medianResponseMs, $globalMedianResponseMs);
                $masteryLagScore = $this->masteryLagScore($masteredRatePct, $avgAttempts);
                $baseDifficultyScore = (0.40 * $firstTryErrorPct)
                    + (0.25 * $repeatFailPct)
                    + (0.20 * $timePressureScore)
                    + (0.15 * $masteryLagScore);
                $confidenceScore = $this->confidenceScore($answersByUser->count());

                return [
                    'question_id' => (int) $questionId,
                    'external_id' => $firstAnswer->external_id,
                    'prompt' => $firstAnswer->prompt,
                    'difficulty' => (int) $firstAnswer->difficulty,
                    'points' => (int) $firstAnswer->points,
                    'category' => [
                        'id' => (int) $firstAnswer->license_category_id,
                        'code' => $firstAnswer->category_code,
                        'slug' => $firstAnswer->category_slug,
                        'name' => $firstAnswer->category_name,
                    ],
                    'topic' => [
                        'id' => $firstAnswer->question_topic_id ? (int) $firstAnswer->question_topic_id : null,
                        'key' => $firstAnswer->topic_key,
                        'name' => $firstAnswer->topic_name ?? 'Bez przypisanego dzialu',
                    ],
                    'users_count' => $answersByUser->count(),
                    'answers_count' => $questionAnswers->count(),
                    'first_try_error_pct' => $firstTryErrorPct,
                    'repeat_fail_pct' => $repeatFailPct,
                    'median_response_time_ms' => $medianResponseMs !== null ? (int) round((float) $medianResponseMs) : null,
                    'mastered_rate_pct' => $masteredRatePct,
                    'avg_attempts' => $avgAttempts,
                    'mastery_lag_score' => $masteryLagScore,
                    'confidence_score' => $confidenceScore,
                    'difficulty_score' => round($baseDifficultyScore * ($confidenceScore / 100), 1),
                ];
            })
            ->filter(fn (array $question): bool => $question['answers_count'] > 0);
    }

    protected function timePressureScore(mixed $medianResponseMs, mixed $globalMedianResponseMs): float
    {
        if (! $medianResponseMs || ! $globalMedianResponseMs || $globalMedianResponseMs <= 0) {
            return 0.0;
        }

        $ratio = ((float) $medianResponseMs / (float) $globalMedianResponseMs) - 1;

        return round((float) min(max($ratio * 60, 0), 100), 1);
    }

    protected function masteryLagScore(float $masteredRatePct, float $avgAttempts): float
    {
        $notMasteredPressure = (100 - $masteredRatePct) * 0.7;
        $attemptPressure = min(max($avgAttempts - 1, 0) * 12, 30);

        return round((float) min($notMasteredPressure + $attemptPressure, 100), 1);
    }

    protected function confidenceScore(int $usersCount): float
    {
        if ($usersCount <= 0) {
            return 0.0;
        }

        $ratio = min($usersCount / self::CONFIDENCE_TARGET_USERS, 1);

        return round((0.35 + (0.65 * $ratio)) * 100, 1);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $questionStats
     * @return Collection<int, array<string, mixed>>
     */
    protected function rankQuestions(Collection $questionStats, string $rankingKey): Collection
    {
        $field = self::RANKINGS[$rankingKey]['field'];

        return $questionStats
            ->sort(function (array $left, array $right) use ($field): int {
                $comparisons = [
                    ($right[$field] ?? 0) <=> ($left[$field] ?? 0),
                    $right['difficulty_score'] <=> $left['difficulty_score'],
                    $right['users_count'] <=> $left['users_count'],
                    $left['question_id'] <=> $right['question_id'],
                ];

                foreach ($comparisons as $comparison) {
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return 0;
            })
            ->values();
    }

    /**
     * @param  Collection<int, LicenseCategory>  $visibleCategories
     * @param  Collection<int, array<string, mixed>>  $questionStats
     * @param  Collection<int, object>  $answerRows
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildCategoryCards(Collection $visibleCategories, Collection $questionStats, Collection $answerRows): Collection
    {
        return $visibleCategories
            ->map(function (LicenseCategory $category) use ($questionStats, $answerRows): array {
                $items = $questionStats
                    ->filter(fn (array $question): bool => $question['category']['id'] === $category->getKey())
                    ->values();
                $categoryAnswers = $answerRows->filter(
                    fn (object $answer): bool => (int) $answer->license_category_id === $category->getKey()
                );

                return [
                    'id' => $category->getKey(),
                    'code' => $category->code,
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'short_name' => $this->studyContextService->shortCategoryName($category),
                    'description' => $category->description,
                    'path' => '/najtrudniejsze-pytania-na-prawo-jazdy/kategoria/'.$category->slug,
                    'questions_analyzed' => $items->count(),
                    'answers_count' => $items->sum('answers_count'),
                    'users_count' => $categoryAnswers->pluck('user_id')->unique()->count(),
                    'avg_difficulty_score' => $items->isNotEmpty()
                        ? round((float) $items->avg('difficulty_score'), 1)
                        : null,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $questionStats
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildTopTopics(Collection $questionStats): Collection
    {
        return $questionStats
            ->groupBy(fn (array $question): string => (string) ($question['topic']['key'] ?? 'bez-przypisania'))
            ->map(function (Collection $items): array {
                $first = $items->first();

                return [
                    'key' => $first['topic']['key'] ?? 'bez-przypisania',
                    'name' => $first['topic']['name'] ?? 'Bez przypisanego dzialu',
                    'questions_count' => $items->count(),
                    'answers_count' => $items->sum('answers_count'),
                    'avg_difficulty_score' => round((float) $items->avg('difficulty_score'), 1),
                ];
            })
            ->sort(function (array $left, array $right): int {
                $comparisons = [
                    $right['avg_difficulty_score'] <=> $left['avg_difficulty_score'],
                    $right['answers_count'] <=> $left['answers_count'],
                    $left['name'] <=> $right['name'],
                ];

                foreach ($comparisons as $comparison) {
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return 0;
            })
            ->values();
    }

    /**
     * @param  array<int, int>  $questionIds
     * @return Collection<int, array<string, mixed>>
     */
    protected function questionAssets(array $questionIds): Collection
    {
        if ($questionIds === []) {
            return collect();
        }

        return Question::query()
            ->select(['id'])
            ->with(['media' => fn ($query) => $query
                ->select(['id', 'question_id', 'kind', 'path', 'poster_path', 'disk', 'sort_order'])
                ->orderBy('sort_order')])
            ->whereIn('id', $questionIds)
            ->get()
            ->keyBy('id')
            ->map(function (Question $question): array {
                $media = $question->media->first();

                if (! $media) {
                    return ['media' => null];
                }

                return [
                    'media' => [
                        'kind' => $media->kind,
                        'url' => $this->mediaUrlResolver->resolve($media->path, $media->disk),
                        'poster_url' => $this->mediaUrlResolver->resolve($media->poster_path, $media->disk),
                    ],
                ];
            });
    }
}
