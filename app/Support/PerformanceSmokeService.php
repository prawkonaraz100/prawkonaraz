<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PerformanceSmokeService
{
    public function __construct(
        protected StudySessionManager $studySessionManager,
        protected DashboardMetricsService $dashboardMetricsService,
    ) {}

    /**
     * @param  array<string, float|int>  $thresholds
     * @return array<string, mixed>
     */
    public function run(array $thresholds = [], bool $assertThresholds = false): array
    {
        $thresholds = $this->resolvedThresholds($thresholds);
        $benchmarks = [];
        $meta = [];
        $assetCleanup = [];

        DB::beginTransaction();

        try {
            [$user, $category, $assetCleanup] = $this->provisionBenchmarkFixture();
            $meta = [
                'category_code' => $category->code,
                'question_count' => (int) $category->questions()->where('is_active', true)->readyForDelivery()->count(),
                'mode' => StudySessionManager::MODE_QUICK,
            ];

            $sessionStart = $this->measure(function () use ($category, $user) {
                return $this->studySessionManager->start(
                    $user,
                    $category,
                    StudySessionManager::MODE_QUICK,
                    10,
                );
            });

            $session = $sessionStart['result'];
            $benchmarks[] = $this->formatBenchmark(
                'session_start',
                'Tworzenie szybkiej sesji benchmarkowej.',
                $sessionStart,
                (float) $thresholds['session_start_ms'],
            );

            $currentQuestion = $this->studySessionManager->currentQuestion($session);

            if (! $currentQuestion instanceof Question) {
                throw new RuntimeException('Benchmark nie mogl pobrac aktualnego pytania sesji.');
            }

            $firstAnswer = $this->measure(function () use ($currentQuestion, $session): void {
                $this->studySessionManager->recordAnswer(
                    $session,
                    $currentQuestion,
                    (string) $currentQuestion->correct_answer,
                    900,
                );
            });

            $benchmarks[] = $this->formatBenchmark(
                'first_answer',
                'Zapis pierwszej odpowiedzi i aktualizacja progresu.',
                $firstAnswer,
                (float) $thresholds['first_answer_ms'],
            );

            $complete = $this->measure(function () use ($session) {
                return $this->studySessionManager->complete($session->fresh() ?? $session);
            });

            $session = $complete['result'];
            $benchmarks[] = $this->formatBenchmark(
                'session_complete',
                'Finalizacja sesji i przeliczenie wyniku.',
                $complete,
                (float) $thresholds['session_complete_ms'],
            );

            $dashboard = $this->measure(function () use ($user) {
                return $this->dashboardMetricsService->build($user->fresh() ?? $user);
            });

            $benchmarks[] = $this->formatBenchmark(
                'dashboard_metrics',
                'Budowa danych kokpitu po zakonczonej sesji.',
                $dashboard,
                (float) $thresholds['dashboard_ms'],
            );

            $status = collect($benchmarks)->contains(
                fn (array $benchmark): bool => $benchmark['status'] === 'failed',
            ) ? 'failed' : 'ok';

            if ($assertThresholds && $status === 'failed') {
                return [
                    'status' => 'failed',
                    'checked_at' => now()->utc()->toIso8601String(),
                    'thresholds' => $thresholds,
                    'meta' => $meta,
                    'benchmarks' => $benchmarks,
                ];
            }

            return [
                'status' => $status,
                'checked_at' => now()->utc()->toIso8601String(),
                'thresholds' => $thresholds,
                'meta' => $meta,
                'benchmarks' => $benchmarks,
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'failed',
                'checked_at' => now()->utc()->toIso8601String(),
                'thresholds' => $thresholds,
                'meta' => $meta,
                'benchmarks' => [
                    [
                        'name' => 'perf_smoke',
                        'status' => 'failed',
                        'message' => $exception->getMessage(),
                    ],
                ],
            ];
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->cleanupAssets($assetCleanup);
        }
    }

    /**
     * @return array{0: User, 1: LicenseCategory, 2: array{disk: string, paths: array<int, string>}}
     */
    protected function provisionBenchmarkFixture(): array
    {
        $benchmarkKey = Str::lower(Str::random(8));

        $category = LicenseCategory::factory()->create([
            'code' => 'PB-'.$benchmarkKey,
            'slug' => 'performance-benchmark-'.$benchmarkKey,
            'name' => 'Performance Benchmark',
            'sort_order' => 1,
        ]);

        $questions = Question::factory()
            ->count(12)
            ->for($category, 'licenseCategory')
            ->sequence(
                fn (Sequence $sequence) => [
                    'external_id' => sprintf('PB-%03d', $sequence->index + 1),
                    'difficulty' => min(($sequence->index % 5) + 1, 5),
                    'published_at' => now()->subDays(12 - $sequence->index),
                    'correct_answer' => $sequence->index % 4 === 0
                        ? ['a', 'b'][$sequence->index % 2]
                        : ['a', 'b', 'c'][$sequence->index % 3],
                    'question_type' => $sequence->index % 4 === 0 ? 'boolean' : 'single_choice',
                    'option_c' => $sequence->index % 4 === 0 ? null : "Opcja C {$sequence->index}",
                ],
            )
            ->create();

        $assetCleanup = $this->attachBenchmarkMedia($questions[0], $questions[1]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        return [$user, $category, $assetCleanup];
    }

    /**
     * @return array{disk: string, paths: array<int, string>}
     */
    protected function attachBenchmarkMedia(Question $imageQuestion, Question $videoQuestion): array
    {
        $disk = (string) config('media.public_disk', config('media.default_disk', 'public'));
        $paths = [
            'perf/pb/image.webp',
            'perf/pb/video.mp4',
            'perf/pb/poster.webp',
        ];

        Storage::disk($disk)->put($paths[0], 'perf-image');
        Storage::disk($disk)->put($paths[1], 'perf-video');
        Storage::disk($disk)->put($paths[2], 'perf-poster');

        QuestionMedia::query()->create([
            'question_id' => $imageQuestion->getKey(),
            'kind' => 'image',
            'disk' => $disk,
            'path' => $paths[0],
            'poster_path' => null,
            'mime_type' => 'image/webp',
            'bytes' => strlen('perf-image'),
            'duration_seconds' => null,
            'width' => null,
            'height' => null,
            'variant' => 'full',
            'sort_order' => 0,
            'metadata' => [
                'purpose' => 'perf-smoke',
            ],
        ]);

        QuestionMedia::query()->create([
            'question_id' => $videoQuestion->getKey(),
            'kind' => 'video',
            'disk' => $disk,
            'path' => $paths[1],
            'poster_path' => $paths[2],
            'mime_type' => 'video/mp4',
            'bytes' => strlen('perf-video'),
            'duration_seconds' => 8,
            'width' => null,
            'height' => null,
            'variant' => 'full',
            'sort_order' => 0,
            'metadata' => [
                'purpose' => 'perf-smoke',
            ],
        ]);

        return [
            'disk' => $disk,
            'paths' => $paths,
        ];
    }

    /**
     * @return array{result:mixed,duration_ms:float,query_count:int,memory_delta_kb:float}
     */
    protected function measure(callable $callback): array
    {
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $memoryBefore = memory_get_usage(true);
        $startedAt = hrtime(true);

        try {
            $result = $callback();
        } finally {
            $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
            $memoryAfter = memory_get_usage(true);
            $queries = count($connection->getQueryLog());
            $connection->disableQueryLog();
        }

        return [
            'result' => $result,
            'duration_ms' => round($durationMs, 2),
            'query_count' => $queries,
            'memory_delta_kb' => round(($memoryAfter - $memoryBefore) / 1024, 2),
        ];
    }

    /**
     * @param  array{result:mixed,duration_ms:float,query_count:int,memory_delta_kb:float}  $measurement
     * @return array<string, mixed>
     */
    protected function formatBenchmark(
        string $name,
        string $message,
        array $measurement,
        float $thresholdMs,
    ): array {
        $status = $measurement['duration_ms'] <= $thresholdMs ? 'ok' : 'failed';

        return [
            'name' => $name,
            'status' => $status,
            'message' => $status === 'ok'
                ? $message
                : sprintf('%s Przekroczono prog %.2f ms.', $message, $thresholdMs),
            'duration_ms' => $measurement['duration_ms'],
            'query_count' => $measurement['query_count'],
            'memory_delta_kb' => $measurement['memory_delta_kb'],
            'threshold_ms' => $thresholdMs,
        ];
    }

    /**
     * @param  array<string, float|int>  $thresholds
     * @return array<string, float>
     */
    protected function resolvedThresholds(array $thresholds): array
    {
        return [
            'session_start_ms' => (float) ($thresholds['session_start_ms'] ?? config('performance.perf_smoke.session_start_ms', 150)),
            'first_answer_ms' => (float) ($thresholds['first_answer_ms'] ?? config('performance.perf_smoke.first_answer_ms', 150)),
            'session_complete_ms' => (float) ($thresholds['session_complete_ms'] ?? config('performance.perf_smoke.session_complete_ms', 150)),
            'dashboard_ms' => (float) ($thresholds['dashboard_ms'] ?? config('performance.perf_smoke.dashboard_ms', 150)),
        ];
    }

    /**
     * @param  array{disk?: string, paths?: array<int, string>}  $assetCleanup
     */
    protected function cleanupAssets(array $assetCleanup): void
    {
        $disk = $assetCleanup['disk'] ?? null;
        $paths = $assetCleanup['paths'] ?? [];

        if (! is_string($disk) || $paths === []) {
            return;
        }

        try {
            Storage::disk($disk)->delete($paths);
        } catch (Throwable) {
            // Best-effort cleanup for benchmark placeholders.
        }
    }
}
