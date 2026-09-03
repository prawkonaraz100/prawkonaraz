<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class SmokeTestService
{
    public function __construct(
        protected Kernel $kernel,
        protected DashboardMetricsService $dashboardMetricsService,
        protected MediaUrlResolver $mediaUrlResolver,
        protected StudySessionManager $studySessionManager,
    ) {}

    /**
     * @return array{status:string,checked_at:string,checks:list<array<string,mixed>>}
     */
    public function run(bool $requireMedia = false): array
    {
        $checks = [
            $this->checkHealthApi(),
            $this->checkCategoriesApi(),
        ];

        foreach ($this->checkLearningFlow() as $check) {
            $checks[] = $check;
        }

        $checks[] = $this->checkMediaAssets($requireMedia);

        $status = collect($checks)->contains(
            fn (array $check): bool => $check['status'] === 'failed',
        ) ? 'failed' : 'ok';

        return [
            'status' => $status,
            'checked_at' => now()->utc()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function checkLearningFlow(): array
    {
        $category = $this->smokeCategory();

        if (! $category) {
            return [
                $this->failedCheck(
                    'session_flow',
                    'Brak aktywnej kategorii z co najmniej jednym aktywnym pytaniem.',
                ),
                $this->skippedCheck(
                    'dashboard_metrics',
                    'Pominieto, bo sesja smoke nie mogla zostac uruchomiona.',
                ),
            ];
        }

        DB::beginTransaction();

        try {
            $availableQuestions = $category->questions()
                ->where('is_active', true)
                ->readyForDelivery()
                ->count();
            $questionCount = max(1, min($availableQuestions, 3));
            $user = User::query()->create([
                'name' => 'Smoke Test User',
                'email' => sprintf('smoke+%s@example.test', Str::lower((string) Str::ulid())),
                'password' => Str::password(32),
                'password_login_enabled' => false,
                'is_test_account' => true,
                'email_verified_at' => now(),
            ]);

            $session = $this->studySessionManager->start($user, $category, 'learn', $questionCount);
            $currentQuestion = $this->studySessionManager->currentQuestion($session);

            if (! $currentQuestion instanceof Question) {
                throw new RuntimeException('Nie udalo sie pobrac aktualnego pytania dla sesji smoke.');
            }

            $this->studySessionManager->recordAnswer(
                $session,
                $currentQuestion,
                (string) $currentQuestion->correct_answer,
                900,
            );

            $session = $this->studySessionManager->complete($session->fresh() ?? $session);
            $dashboard = $this->dashboardMetricsService->build($user->fresh() ?? $user);

            if (($dashboard['stats']['sessions_today'] ?? 0) < 1) {
                throw new RuntimeException('Dashboard nie pokazuje aktywnej sesji z dzisiejszego dnia.');
            }

            if (($dashboard['recentSessions']->count() ?? 0) < 1) {
                throw new RuntimeException('Dashboard nie zwraca listy ostatnich sesji.');
            }

            DB::rollBack();

            return [
                $this->passedCheck(
                    'session_flow',
                    sprintf(
                        'Utworzono i zakonczono sesje learn dla kategorii %s.',
                        (string) $category->code,
                    ),
                    [
                        'category_id' => $category->getKey(),
                        'category_code' => $category->code,
                        'question_count' => $questionCount,
                        'score_percent' => (float) ($session->score_percent ?? 0),
                    ],
                ),
                $this->passedCheck(
                    'dashboard_metrics',
                    'Dashboard zwraca dane po wykonaniu sesji smoke.',
                    [
                        'sessions_today' => (int) ($dashboard['stats']['sessions_today'] ?? 0),
                        'answered_today' => (int) ($dashboard['stats']['answered_today'] ?? 0),
                        'readiness_score' => (int) ($dashboard['stats']['readiness_score'] ?? 0),
                    ],
                ),
            ];
        } catch (\Throwable $exception) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return [
                $this->failedCheck('session_flow', $exception->getMessage()),
                $this->skippedCheck(
                    'dashboard_metrics',
                    'Pominieto, bo krytyczny flow sesji zakonczyl sie bledem.',
                ),
            ];
        }
    }

    protected function checkHealthApi(): array
    {
        try {
            $response = $this->dispatch('/api/v1/health', 'application/json');
            $payload = json_decode($response->getContent(), true);
            $status = $payload['data']['status'] ?? null;

            if ($response->getStatusCode() !== 200 || $status !== 'ok') {
                return $this->failedCheck(
                    'health_api',
                    sprintf(
                        'Health endpoint zwrocil HTTP %d i status aplikacji %s.',
                        $response->getStatusCode(),
                        (string) ($status ?? 'unknown'),
                    ),
                );
            }

            return $this->passedCheck(
                'health_api',
                'Health endpoint odpowiada poprawnie.',
                [
                    'http_status' => $response->getStatusCode(),
                    'application_status' => $status,
                ],
            );
        } catch (\Throwable $exception) {
            return $this->failedCheck('health_api', $exception->getMessage());
        }
    }

    protected function checkCategoriesApi(): array
    {
        try {
            $response = $this->dispatch('/api/v1/categories', 'application/json');
            $payload = json_decode($response->getContent(), true);
            $categories = is_array($payload['data'] ?? null) ? $payload['data'] : [];

            if ($response->getStatusCode() !== 200) {
                return $this->failedCheck(
                    'categories_api',
                    sprintf('Endpoint kategorii zwrocil HTTP %d.', $response->getStatusCode()),
                );
            }

            if ($categories === []) {
                return $this->failedCheck(
                    'categories_api',
                    'Publiczny endpoint kategorii nie zwrocil zadnej aktywnej kategorii.',
                );
            }

            return $this->passedCheck(
                'categories_api',
                'Publiczny endpoint kategorii zwraca aktywne dane.',
                [
                    'count' => count($categories),
                    'first_code' => $categories[0]['code'] ?? null,
                ],
            );
        } catch (\Throwable $exception) {
            return $this->failedCheck('categories_api', $exception->getMessage());
        }
    }

    protected function checkMediaAssets(bool $requireMedia): array
    {
        $assets = QuestionMedia::query()
            ->whereIn('kind', ['image', 'video'])
            ->whereHas('question', function ($query): void {
                $query->where('is_active', true)
                    ->readyForDelivery()
                    ->whereHas('licenseCategory', fn ($categoryQuery) => $categoryQuery->where('is_active', true));
            })
            ->orderBy('id')
            ->get();

        $smokeAssets = $assets->filter(
            fn (QuestionMedia $media): bool => data_get($media->metadata, 'purpose') === 'smoke',
        );

        $preferredAssets = $smokeAssets->isNotEmpty() ? $smokeAssets : $assets;
        $groupedAssets = $preferredAssets->groupBy('kind');

        $image = $groupedAssets->get('image')?->first();
        $video = $groupedAssets->get('video')?->first();

        if (! $image && ! $video) {
            return $requireMedia
                ? $this->failedCheck(
                    'media_assets',
                    'Brak image/video assetow do sprawdzenia smoke.',
                )
                : $this->skippedCheck(
                    'media_assets',
                    'Pominieto, bo nie znaleziono image/video assetow.',
                );
        }

        if ($requireMedia && (! $image || ! $video)) {
            return $this->failedCheck(
                'media_assets',
                'Smoke media wymaga co najmniej jednego obrazu i jednego wideo.',
            );
        }

        try {
            $validated = collect([$image, $video])
                ->filter()
                ->map(fn (QuestionMedia $media) => $this->validateMediaAsset($media));

            return $this->passedCheck(
                'media_assets',
                'Dostepne assety maja poprawne URL-e i istnieja na storage.',
                [
                    'checked_kinds' => $validated->pluck('kind')->values()->all(),
                    'assets' => $validated->values()->all(),
                ],
            );
        } catch (\Throwable $exception) {
            return $this->failedCheck('media_assets', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateMediaAsset(QuestionMedia $media): array
    {
        $url = $this->mediaUrlResolver->resolve($media->path, $media->disk);

        if (blank($url)) {
            throw new RuntimeException(sprintf(
                'Nie udalo sie rozwiazac URL dla assetu %s (%s).',
                $media->getKey(),
                $media->kind,
            ));
        }

        $diskConfig = config("filesystems.disks.{$media->disk}");

        if (is_array($diskConfig) && ! Storage::disk($media->disk)->exists($media->path)) {
            throw new RuntimeException(sprintf(
                'Asset %s (%s) nie istnieje na dysku %s.',
                $media->getKey(),
                $media->kind,
                $media->disk,
            ));
        }

        return [
            'id' => $media->getKey(),
            'kind' => $media->kind,
            'disk' => $media->disk,
            'path' => $media->path,
            'url' => $url,
        ];
    }

    protected function smokeCategory(): ?LicenseCategory
    {
        return LicenseCategory::query()
            ->where('is_active', true)
            ->withCount([
                'questions' => fn ($query) => $query->where('is_active', true)->readyForDelivery(),
            ])
            ->orderBy('sort_order')
            ->get()
            ->first(fn (LicenseCategory $category): bool => $category->questions_count > 0);
    }

    protected function dispatch(string $uri, string $accept): Response
    {
        $request = Request::create(
            $uri,
            'GET',
            [],
            [],
            [],
            $this->serverVariables($accept),
        );

        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serverVariables(string $accept): array
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
        $port = parse_url($appUrl, PHP_URL_PORT);

        return [
            'HTTP_HOST' => $host,
            'SERVER_NAME' => $host,
            'SERVER_PORT' => $port ?: ($scheme === 'https' ? 443 : 80),
            'REQUEST_SCHEME' => $scheme,
            'HTTPS' => $scheme === 'https' ? 'on' : 'off',
            'HTTP_ACCEPT' => $accept,
            'HTTP_X_REQUEST_ID' => 'smoke-'.Str::lower((string) Str::ulid()),
        ];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    protected function passedCheck(string $name, string $message, array $details = []): array
    {
        return [
            'name' => $name,
            'status' => 'ok',
            'message' => $message,
            'details' => $details,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function failedCheck(string $name, string $message): array
    {
        return [
            'name' => $name,
            'status' => 'failed',
            'message' => $message,
            'details' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function skippedCheck(string $name, string $message): array
    {
        return [
            'name' => $name,
            'status' => 'skipped',
            'message' => $message,
            'details' => [],
        ];
    }
}
