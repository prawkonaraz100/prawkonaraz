<?php

namespace App\Support;

use App\Models\ContentImportRun;
use App\Models\MonitoringSnapshot;
use App\Models\QuestionDailyStat;
use App\Models\QuestionMonthlyStat;
use App\Models\ReviewMemoryProgress;
use App\Models\ReviewTrainerDailyAnswer;
use App\Models\ReviewTrainerEvent;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\UserIpHistory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AdminDataMonitoringService
{
    public function __construct(
        protected InfrastructureTelemetryService $infrastructureTelemetryService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $windows = [7, 30, 90, 365];
        $thresholds = $this->thresholds();
        $latestDailyStatDate = QuestionDailyStat::query()->max('stats_date');
        $latestMonthlyStatDate = QuestionMonthlyStat::query()->max('stats_month');
        $telemetry = $this->infrastructureTelemetryService->current();
        $currentMetrics = $this->currentMetricValues($telemetry);
        $snapshotSeries = MonitoringSnapshot::query()
            ->whereDate('snapshot_date', '>=', now()->subDays(29)->toDateString())
            ->orderBy('snapshot_date')
            ->get();

        $latestRuns = [
            [
                'label' => 'Agregacja dziennych statystyk',
                'run_at' => $this->formatRunTimestamp(Cache::get('ops.question_daily_stats.last_run_at')),
                'details' => $this->latestAggregateDetails($latestDailyStatDate),
                'status' => $this->runStatus(Cache::get('ops.question_daily_stats.last_run_at'), 30),
                'cache_key' => 'ops.question_daily_stats.last_run_at',
            ],
            [
                'label' => 'Rollup miesięcznego archiwum',
                'run_at' => $this->formatRunTimestamp(Cache::get('ops.question_monthly_rollup.last_run_at')),
                'details' => $this->latestMonthlyRollupDetails(),
                'status' => $this->runStatus(Cache::get('ops.question_monthly_rollup.last_run_at'), 36),
                'cache_key' => 'ops.question_monthly_rollup.last_run_at',
            ],
            [
                'label' => 'Czyszczenie historii nauki',
                'run_at' => $this->formatRunTimestamp(Cache::get('ops.study_history_prune.last_run_at')),
                'details' => $this->latestPruneDetails(),
                'status' => $this->runStatus(Cache::get('ops.study_history_prune.last_run_at'), 36),
                'cache_key' => 'ops.study_history_prune.last_run_at',
            ],
            $this->sqliteMaintenanceRunItem(),
        ];

        $warningCount = collect($latestRuns)->filter(fn (array $run): bool => $run['status']['tone'] === 'warning')->count();
        $dangerCount = collect($latestRuns)->filter(fn (array $run): bool => $run['status']['tone'] === 'danger')->count();

        $summary = $this->buildSummaryCards($currentMetrics, $snapshotSeries);
        $forecast = $this->buildForecastCards($currentMetrics, $snapshotSeries);
        $growthRanking = $this->buildGrowthRanking($currentMetrics, $snapshotSeries);
        $dataQuality = $this->buildDataQuality($snapshotSeries, $latestDailyStatDate, $latestMonthlyStatDate, $thresholds);
        $retentionImpact = $this->buildRetentionImpact($snapshotSeries);
        $trendComparisons = $this->buildTrendComparisons($currentMetrics, $snapshotSeries);
        $infrastructure = $this->buildInfrastructureStatus($currentMetrics, $snapshotSeries, $thresholds);
        $risks = $this->buildRiskItems($latestRuns, $snapshotSeries, $forecast, $growthRanking, $infrastructure);
        $healthScore = $this->buildHealthScore($latestRuns, $dataQuality, $forecast, $snapshotSeries, $thresholds, $infrastructure);
        $anomalies = $this->buildAnomalies($trendComparisons, $latestRuns, $dataQuality, $snapshotSeries, $infrastructure);
        $thresholdRows = $this->buildThresholdRows($thresholds);
        $recommendedActions = $this->buildRecommendedActions($latestRuns, $dataQuality, $forecast, $healthScore, $anomalies, $infrastructure);
        $eventTimeline = $this->buildEventTimeline($snapshotSeries, $latestRuns, $latestDailyStatDate, $latestMonthlyStatDate);
        $attentionItems = $this->buildAttentionItems($latestRuns);

        return [
            'retention' => [
                'raw_completed_days' => max((int) config('study.completed_session_retention_days', 365), 1),
                'raw_abandoned_days' => max((int) config('study.abandoned_session_retention_days', 7), 1),
                'admin_window_days' => max((int) config('study.admin_activity_window_days', 90), 1),
                'question_analytics_window_days' => max((int) config('study.question_analytics_window_days', 365), 1),
                'question_daily_stats_retention_days' => max((int) config('study.question_daily_stats_retention_days', 365), 1),
                'question_monthly_rollup_after_days' => max((int) config('study.question_daily_stats_retention_days', 365), 1),
            ],
            'retention_rows' => [
                [
                    'label' => 'Raw sesje i odpowiedzi',
                    'window' => max((int) config('study.completed_session_retention_days', 365), 1).' dni',
                    'details' => 'To pełna warstwa surowych danych, z której nadal liczymy roczną analitykę pytań.',
                ],
                [
                    'label' => 'Admin operacyjny',
                    'window' => max((int) config('study.admin_activity_window_days', 90), 1).' dni',
                    'details' => 'Panel pokazuje krótsze okno, żeby decyzje były oparte na aktualnym ruchu, a nie na całym archiwum.',
                ],
                [
                    'label' => 'Dzienne agregaty',
                    'window' => max((int) config('study.question_daily_stats_retention_days', 365), 1).' dni',
                    'details' => 'Pełny detal dzienny trzymamy tylko przez ostatni rok, bo to daje jeszcze sensowną dokładność i nie dusi bazy.',
                ],
                [
                    'label' => 'Archiwum miesięczne',
                    'window' => 'po '.max((int) config('study.question_daily_stats_retention_days', 365), 1).' dniach',
                    'details' => 'Starsze dni są zwijane do jednego rekordu na miesiąc, więc trend historyczny zostaje bez trzymania pełnego detalu.',
                ],
            ],
            'summary' => $summary,
            'infrastructure' => $infrastructure,
            'forecast' => [
                'horizon_days' => 30,
                'items' => $forecast,
            ],
            'growth_ranking' => $growthRanking,
            'health_score' => $healthScore,
            'data_quality' => $dataQuality,
            'retention_impact' => $retentionImpact,
            'trend_comparisons' => $trendComparisons,
            'threshold_rows' => $thresholdRows,
            'recommended_actions' => $recommendedActions,
            'event_timeline' => $eventTimeline,
            'risks' => $risks,
            'anomalies' => $anomalies,
            'job_health' => [
                'headline' => $dangerCount > 0
                    ? 'Część zadań wymaga pilnego sprawdzenia'
                    : ($warningCount > 0
                        ? 'Monitoring jest częściowo nieświeży'
                        : 'Zadania utrzymaniowe są aktualne'),
                'summary' => $dangerCount > 0
                    ? sprintf('%s zadań jest przestarzałych, %s wymaga sprawdzenia.', $this->formatNumber($dangerCount), $this->formatNumber($warningCount))
                    : ($warningCount > 0
                        ? sprintf('%s zadań wymaga sprawdzenia, ale nie ma jeszcze krytycznego przestoju.', $this->formatNumber($warningCount))
                        : 'Nie ma otwartych problemów z agregacją, cleanupem ani maintenance.'),
                'danger_count' => $dangerCount,
                'warning_count' => $warningCount,
                'attention_items' => $attentionItems,
            ],
            'charts' => [
                'history_growth' => $this->buildLineChart(
                    'Przyrost surowej historii',
                    'Jak rośnie warstwa operacyjna, która najmocniej obciąża bazę i cleanup.',
                    $snapshotSeries,
                    [
                        ['key' => 'study_sessions_count', 'label' => 'Sesje', 'color' => '#0f172a'],
                        ['key' => 'study_session_answers_count', 'label' => 'Odpowiedzi', 'color' => '#2563eb'],
                    ],
                ),
                'analytics_growth' => $this->buildLineChart(
                    'Przyrost warstwy analitycznej',
                    'Dzienne agregaty pokazują bieżący detal, miesięczne archiwum przejmuje starszy trend.',
                    $snapshotSeries,
                    [
                        ['key' => 'question_daily_stats_count', 'label' => 'Dzienne agregaty', 'color' => '#0f766e'],
                        ['key' => 'question_monthly_stats_count', 'label' => 'Archiwum miesięczne', 'color' => '#9333ea'],
                    ],
                ),
                'memory_trainer_growth' => $this->buildLineChart(
                    'Przyrost trenera pamięci',
                    'Osobna warstwa verified memory, dziennika odpowiedzi i eventów review.',
                    $snapshotSeries,
                    [
                        ['key' => 'review_trainer_daily_answers_count', 'label' => 'Odpowiedzi trenera', 'color' => '#0f766e'],
                        ['key' => 'review_memory_progress_count', 'label' => 'Stan pamięci', 'color' => '#2563eb'],
                        ['key' => 'review_trainer_events_count', 'label' => 'Eventy review', 'color' => '#9333ea'],
                    ],
                ),
            ],
            'database' => [
                'driver' => (string) config('database.default'),
                'path' => filled($telemetry['database_path'] ?? null)
                    ? (string) $telemetry['database_path']
                    : 'Brak dedykowanej ścieżki',
                'size' => $this->formatMetricValue('database_size_bytes', $currentMetrics['database_size_bytes'] ?? null),
                'volume_total' => isset($currentMetrics['database_volume_total_bytes']) ? $this->formatBytes((int) $currentMetrics['database_volume_total_bytes']) : 'brak danych',
                'volume_free' => isset($currentMetrics['database_volume_free_bytes']) ? $this->formatBytes((int) $currentMetrics['database_volume_free_bytes']) : 'brak danych',
                'volume_used_ratio' => $this->formatPercentValue($currentMetrics['database_volume_used_ratio'] ?? null),
                'database_share' => $this->formatPercentValue($currentMetrics['database_share_ratio'] ?? null),
                'latency' => $this->formatLatency($currentMetrics['database_latency_ms'] ?? null),
            ],
            'table_counts' => [
                ['label' => 'Sesje nauki', 'value' => $this->formatNumber(StudySession::query()->count())],
                ['label' => 'Odpowiedzi sesji', 'value' => $this->formatNumber(StudySessionAnswer::query()->count())],
                ['label' => 'Dzienne agregaty pytań', 'value' => $this->formatNumber(QuestionDailyStat::query()->count())],
                ['label' => 'Miesięczne archiwum pytań', 'value' => $this->formatNumber(QuestionMonthlyStat::query()->count())],
                ['label' => 'Logi IP', 'value' => $this->formatNumber(UserIpHistory::query()->count())],
                ['label' => 'Importy', 'value' => $this->formatNumber(ContentImportRun::query()->count())],
                ['label' => 'Eventy trenera pamięci', 'value' => $this->formatNumber(ReviewTrainerEvent::query()->count())],
                ['label' => 'Zweryfikowana pamięć', 'value' => $this->formatNumber(ReviewMemoryProgress::query()->count())],
                ['label' => 'Dzienne odpowiedzi trenera', 'value' => $this->formatNumber(ReviewTrainerDailyAnswer::query()->count())],
            ],
            'activity_windows' => collect($windows)->map(function (int $days): array {
                $start = now()->subDays($days);

                return [
                    'label' => "Ostatnie {$days} dni",
                    'sessions' => $this->formatNumber(StudySession::query()->where('created_at', '>=', $start)->count()),
                    'answers' => $this->formatNumber(
                        StudySessionAnswer::query()
                            ->where(function ($query) use ($start): void {
                                $query
                                    ->where('answered_at', '>=', $start)
                                    ->orWhere(function ($innerQuery) use ($start): void {
                                        $innerQuery
                                            ->whereNull('answered_at')
                                            ->where('created_at', '>=', $start);
                                    });
                            })
                            ->count()
                    ),
                    'daily_stats' => $this->formatNumber(
                        QuestionDailyStat::query()
                            ->whereDate('stats_date', '>=', $start->toDateString())
                            ->count()
                    ),
                    'monthly_stats' => $this->formatNumber(
                        QuestionMonthlyStat::query()
                            ->whereDate('stats_month', '>=', $start->copy()->startOfMonth()->toDateString())
                            ->count()
                    ),
                    'active_questions' => $this->formatNumber(
                        QuestionDailyStat::query()
                            ->whereDate('stats_date', '>=', $start->toDateString())
                            ->distinct()
                            ->count('question_id')
                    ),
                ];
            })->all(),
            'latest_runs' => $latestRuns,
        ];
    }

    /**
     * @param  array<string, int|float|string|null>  $telemetry
     * @return array<string, int|float|string|null>
     */
    protected function currentMetricValues(array $telemetry): array
    {
        return [
            'database_size_bytes' => isset($telemetry['database_size_bytes']) ? (int) $telemetry['database_size_bytes'] : null,
            'database_volume_total_bytes' => isset($telemetry['database_volume_total_bytes']) ? (int) $telemetry['database_volume_total_bytes'] : null,
            'database_volume_free_bytes' => isset($telemetry['database_volume_free_bytes']) ? (int) $telemetry['database_volume_free_bytes'] : null,
            'database_volume_used_bytes' => isset($telemetry['database_volume_used_bytes']) ? (int) $telemetry['database_volume_used_bytes'] : null,
            'database_volume_used_ratio' => isset($telemetry['database_volume_used_ratio']) ? (float) $telemetry['database_volume_used_ratio'] : null,
            'database_share_ratio' => isset($telemetry['database_share_ratio']) ? (float) $telemetry['database_share_ratio'] : null,
            'database_latency_ms' => isset($telemetry['database_latency_ms']) ? (int) $telemetry['database_latency_ms'] : null,
            'cpu_cores' => isset($telemetry['cpu_cores']) ? (int) $telemetry['cpu_cores'] : null,
            'cpu_load_1m' => isset($telemetry['cpu_load_1m']) ? (float) $telemetry['cpu_load_1m'] : null,
            'cpu_load_5m' => isset($telemetry['cpu_load_5m']) ? (float) $telemetry['cpu_load_5m'] : null,
            'cpu_load_ratio_1m' => isset($telemetry['cpu_load_ratio_1m']) ? (float) $telemetry['cpu_load_ratio_1m'] : null,
            'memory_total_bytes' => isset($telemetry['memory_total_bytes']) ? (int) $telemetry['memory_total_bytes'] : null,
            'memory_available_bytes' => isset($telemetry['memory_available_bytes']) ? (int) $telemetry['memory_available_bytes'] : null,
            'memory_used_bytes' => isset($telemetry['memory_used_bytes']) ? (int) $telemetry['memory_used_bytes'] : null,
            'memory_used_ratio' => isset($telemetry['memory_used_ratio']) ? (float) $telemetry['memory_used_ratio'] : null,
            'backup_status' => $telemetry['backup_status'] ?? null,
            'backup_last_at' => $telemetry['backup_last_at'] ?? null,
            'backup_age_hours' => isset($telemetry['backup_age_hours']) ? (float) $telemetry['backup_age_hours'] : null,
            'backup_latest_bytes' => isset($telemetry['backup_latest_bytes']) ? (int) $telemetry['backup_latest_bytes'] : null,
            'backup_total_bytes' => isset($telemetry['backup_total_bytes']) ? (int) $telemetry['backup_total_bytes'] : null,
            'backup_retained_count' => isset($telemetry['backup_retained_count']) ? (int) $telemetry['backup_retained_count'] : null,
            'backup_disk' => $telemetry['backup_disk'] ?? null,
            'backup_message' => $telemetry['backup_message'] ?? null,
            'study_sessions_count' => StudySession::query()->count(),
            'study_session_answers_count' => StudySessionAnswer::query()->count(),
            'question_daily_stats_count' => QuestionDailyStat::query()->count(),
            'question_monthly_stats_count' => QuestionMonthlyStat::query()->count(),
            'user_ip_histories_count' => UserIpHistory::query()->count(),
            'content_import_runs_count' => ContentImportRun::query()->count(),
            'review_trainer_events_count' => ReviewTrainerEvent::query()->count(),
            'review_memory_progress_count' => ReviewMemoryProgress::query()->count(),
            'review_trainer_daily_answers_count' => ReviewTrainerDailyAnswer::query()->count(),
        ];
    }

    protected function databasePath(): string
    {
        $driver = $this->currentDatabaseDriver();

        if ($driver !== 'sqlite') {
            $connection = config("database.connections.{$driver}");

            if (! is_array($connection)) {
                return strtoupper($driver);
            }

            $host = trim((string) ($connection['host'] ?? ''));
            $port = trim((string) ($connection['port'] ?? ''));
            $database = trim((string) ($connection['database'] ?? ''));
            $authority = $host !== ''
                ? ($port !== '' ? "{$host}:{$port}" : $host)
                : 'configured-host';
            $databaseLabel = $database !== '' ? $database : 'configured-database';

            return sprintf('%s://%s/%s', $driver, $authority, $databaseLabel);
        }

        $path = (string) config('database.connections.sqlite.database');

        return $path !== '' ? $path : 'Brak dedykowanej ścieżki';
    }

    protected function databaseSizeLabel(): string
    {
        $bytes = $this->databaseSizeBytes();

        if ($bytes === null) {
            return 'Brak danych';
        }

        return $this->formatBytes($bytes);
    }

    protected function databaseSizeBytes(): ?int
    {
        if ($this->currentDatabaseDriver() !== 'sqlite') {
            return null;
        }

        $path = (string) config('database.connections.sqlite.database');

        if ($path === '' || ! is_file($path)) {
            return null;
        }

        $bytes = filesize($path);

        return $bytes === false ? null : $bytes;
    }

    /**
     * @return array<string, int|float>
     */
    protected function thresholds(): array
    {
        return [
            'health_warning_score' => (int) config('study.monitoring.health_warning_score', 85),
            'health_danger_score' => (int) config('study.monitoring.health_danger_score', 65),
            'snapshot_completeness_warning_pct' => (int) config('study.monitoring.snapshot_completeness_warning_pct', 90),
            'snapshot_completeness_danger_pct' => (int) config('study.monitoring.snapshot_completeness_danger_pct', 75),
            'daily_stats_warning_lag_days' => (int) config('study.monitoring.daily_stats_warning_lag_days', 1),
            'daily_stats_danger_lag_days' => (int) config('study.monitoring.daily_stats_danger_lag_days', 3),
            'forecast_growth_warning_ratio' => (float) config('study.monitoring.forecast_growth_warning_ratio', 1.15),
            'forecast_growth_danger_ratio' => (float) config('study.monitoring.forecast_growth_danger_ratio', 1.30),
            'disk_usage_warning_pct' => (int) config('study.monitoring.disk_usage_warning_pct', 75),
            'disk_usage_danger_pct' => (int) config('study.monitoring.disk_usage_danger_pct', 85),
            'disk_free_warning_gb' => (int) config('study.monitoring.disk_free_warning_gb', 10),
            'disk_free_danger_gb' => (int) config('study.monitoring.disk_free_danger_gb', 4),
            'database_latency_warning_ms' => (int) config('study.monitoring.database_latency_warning_ms', 100),
            'database_latency_danger_ms' => (int) config('study.monitoring.database_latency_danger_ms', 250),
            'database_runway_warning_days' => (int) config('study.monitoring.database_runway_warning_days', 90),
            'database_runway_danger_days' => (int) config('study.monitoring.database_runway_danger_days', 30),
            'system_load_warning_ratio' => (float) config('study.monitoring.system_load_warning_ratio', 0.70),
            'system_load_danger_ratio' => (float) config('study.monitoring.system_load_danger_ratio', 1.00),
            'memory_usage_warning_pct' => (int) config('study.monitoring.memory_usage_warning_pct', 75),
            'memory_usage_danger_pct' => (int) config('study.monitoring.memory_usage_danger_pct', 90),
        ];
    }

    /**
     * @param  array<string, int|null>  $currentMetrics
     * @return array<int, array<string, mixed>>
     */
    protected function buildSummaryCards(array $currentMetrics, Collection $snapshots): array
    {
        $definitions = [
            ['key' => 'database_size_bytes', 'label' => 'Baza danych', 'details' => (string) config('database.default')],
            ['key' => 'study_sessions_count', 'label' => 'Surowe sesje', 'details' => 'pełna historia do 365 dni'],
            ['key' => 'question_daily_stats_count', 'label' => 'Dzienne agregaty', 'details' => 'szczegół do 365 dni'],
            ['key' => 'question_monthly_stats_count', 'label' => 'Archiwum miesięczne', 'details' => 'starsze trendy po rollupie'],
        ];

        return collect($definitions)->map(function (array $definition) use ($currentMetrics, $snapshots): array {
            $current = $currentMetrics[$definition['key']] ?? null;
            $baseline7 = $this->baselineMetricValue($snapshots, $definition['key'], 7);
            $baseline30 = $this->baselineMetricValue($snapshots, $definition['key'], 30);
            $dailyVelocity = $this->dailyVelocity($snapshots, $definition['key'], $current, 30);

            return [
                'label' => $definition['label'],
                'value' => $this->formatMetricValue($definition['key'], $current),
                'details' => $definition['details'],
                'deltas' => [
                    $this->buildDeltaBadge('7 dni', $definition['key'], $baseline7, $current),
                    $this->buildDeltaBadge('30 dni', $definition['key'], $baseline30, $current),
                ],
                'velocity' => $dailyVelocity === null
                    ? 'Czeka na pełniejszą serię snapshotów.'
                    : sprintf('Tempo z ostatnich 30 dni: %s / dzień', $this->formatMetricValue($definition['key'], $dailyVelocity, true)),
            ];
        })->all();
    }

    /**
     * @param  array<string, int|float|null>  $currentMetrics
     * @return array<string, mixed>
     */
    protected function buildInfrastructureStatus(array $currentMetrics, Collection $snapshots, array $thresholds): array
    {
        $volumeTotalBytes = isset($currentMetrics['database_volume_total_bytes']) ? (int) $currentMetrics['database_volume_total_bytes'] : null;
        $volumeFreeBytes = isset($currentMetrics['database_volume_free_bytes']) ? (int) $currentMetrics['database_volume_free_bytes'] : null;
        $databaseSizeBytes = isset($currentMetrics['database_size_bytes']) ? (int) $currentMetrics['database_size_bytes'] : null;
        $databaseLatencyMs = isset($currentMetrics['database_latency_ms']) ? (int) $currentMetrics['database_latency_ms'] : null;
        $usedRatio = isset($currentMetrics['database_volume_used_ratio']) ? (float) $currentMetrics['database_volume_used_ratio'] : null;
        $databaseShareRatio = isset($currentMetrics['database_share_ratio']) ? (float) $currentMetrics['database_share_ratio'] : null;
        $cpuCores = isset($currentMetrics['cpu_cores']) ? (int) $currentMetrics['cpu_cores'] : null;
        $cpuLoad1m = isset($currentMetrics['cpu_load_1m']) ? (float) $currentMetrics['cpu_load_1m'] : null;
        $cpuLoad5m = isset($currentMetrics['cpu_load_5m']) ? (float) $currentMetrics['cpu_load_5m'] : null;
        $cpuLoadRatio1m = isset($currentMetrics['cpu_load_ratio_1m']) ? (float) $currentMetrics['cpu_load_ratio_1m'] : null;
        $memoryTotalBytes = isset($currentMetrics['memory_total_bytes']) ? (int) $currentMetrics['memory_total_bytes'] : null;
        $memoryAvailableBytes = isset($currentMetrics['memory_available_bytes']) ? (int) $currentMetrics['memory_available_bytes'] : null;
        $memoryUsedBytes = isset($currentMetrics['memory_used_bytes']) ? (int) $currentMetrics['memory_used_bytes'] : null;
        $memoryUsedRatio = isset($currentMetrics['memory_used_ratio']) ? (float) $currentMetrics['memory_used_ratio'] : null;
        $backupStatus = is_string($currentMetrics['backup_status'] ?? null) ? $currentMetrics['backup_status'] : null;
        $backupLastAt = is_string($currentMetrics['backup_last_at'] ?? null) ? $currentMetrics['backup_last_at'] : null;
        $backupAgeHours = isset($currentMetrics['backup_age_hours']) ? (float) $currentMetrics['backup_age_hours'] : null;
        $backupLatestBytes = isset($currentMetrics['backup_latest_bytes']) ? (int) $currentMetrics['backup_latest_bytes'] : null;
        $backupTotalBytes = isset($currentMetrics['backup_total_bytes']) ? (int) $currentMetrics['backup_total_bytes'] : null;
        $backupRetainedCount = isset($currentMetrics['backup_retained_count']) ? (int) $currentMetrics['backup_retained_count'] : null;
        $backupDisk = is_string($currentMetrics['backup_disk'] ?? null) ? $currentMetrics['backup_disk'] : null;
        $backupMessage = is_string($currentMetrics['backup_message'] ?? null) ? trim($currentMetrics['backup_message']) : null;
        $databaseGrowthPerDay = $this->dailyVelocity($snapshots, 'database_size_bytes', $databaseSizeBytes, 30);
        $databaseBaseline30 = $this->baselineMetricValue($snapshots, 'database_size_bytes', 30);
        $forecastReadiness = $this->forecastReadiness($snapshots, $databaseBaseline30, $databaseSizeBytes);
        $databaseRunwayDays = $this->databaseRunwayDays($volumeFreeBytes, $databaseGrowthPerDay, $forecastReadiness['level']);

        $diskTone = $this->diskPressureTone($usedRatio, $volumeFreeBytes, $thresholds);
        $latencyTone = $this->databaseLatencyTone($databaseLatencyMs, $thresholds);
        $runwayTone = $this->databaseRunwayTone($databaseRunwayDays, $forecastReadiness['level'], $thresholds);
        $databaseShareTone = $databaseShareRatio === null
            ? 'neutral'
            : ($databaseShareRatio >= 0.10 ? 'warning' : 'success');
        $cpuTone = $this->systemLoadTone($cpuLoadRatio1m, $thresholds);
        $memoryTone = $this->memoryUsageTone($memoryUsedRatio, $thresholds);
        $backupTone = $this->backupTone($backupStatus);

        $items = [
            [
                'label' => 'Presja wolumenu',
                'value' => $this->formatPercentValue($usedRatio),
                'tone' => $diskTone,
                'details' => $volumeTotalBytes !== null && $volumeFreeBytes !== null
                    ? sprintf(
                        'Zajęte %s z %s na wolumenie, z którego korzysta aktywna baza danych.',
                        $this->formatBytes(max($volumeTotalBytes - $volumeFreeBytes, 0)),
                        $this->formatBytes($volumeTotalBytes),
                    )
                    : 'Brak metryk wolumenu z bazą.',
                'foot' => $volumeFreeBytes !== null
                    ? sprintf('Wolne miejsce: %s.', $this->formatBytes($volumeFreeBytes))
                    : 'Nie udało się odczytać wolnego miejsca.',
                'check' => 'Presja dyskowa',
            ],
            [
                'label' => 'Udział bazy',
                'value' => $this->formatPercentValue($databaseShareRatio),
                'tone' => $databaseShareTone,
                'details' => $databaseSizeBytes !== null
                    ? sprintf('Sam plik bazy waży teraz %s.', $this->formatBytes($databaseSizeBytes))
                    : 'Brak odczytu rozmiaru pliku aktywnej bazy.',
                'foot' => $volumeTotalBytes !== null && $databaseSizeBytes !== null
                    ? ($databaseShareRatio !== null && $databaseShareRatio < 0.05
                        ? 'Sama baza nie jest dziś głównym źródłem presji na wolumenie.'
                        : 'Baza zaczyna być zauważalną częścią całego wolumenu z danymi aplikacji.')
                    : 'Udział pojawi się, gdy będzie dostępny i rozmiar bazy, i wolumen.',
                'check' => 'Udział bazy',
            ],
            [
                'label' => 'Latencja DB',
                'value' => $this->formatLatency($databaseLatencyMs),
                'tone' => $latencyTone,
                'details' => $databaseLatencyMs !== null
                    ? 'Lekki ping `SELECT 1`, który pokazuje, czy baza zaczyna odpowiadać wolniej.'
                    : 'Brak bieżącego odczytu latencji bazy.',
                'foot' => $databaseLatencyMs !== null
                    ? sprintf('Pomiar wykonany podczas renderu panelu.')
                    : 'Jeśli to się utrzyma, warto sprawdzić połączenie i sam plik bazy.',
                'check' => 'Latencja bazy',
            ],
            [
                'label' => 'Runway bazy',
                'value' => $this->formatRunway($databaseRunwayDays),
                'tone' => $runwayTone,
                'details' => $databaseGrowthPerDay !== null && $databaseGrowthPerDay > 0
                    ? sprintf(
                        'Przy tempie %s / dzień baza wykorzysta bieżące wolne miejsce mniej więcej za %s.',
                        $this->formatMetricValue('database_size_bytes', $databaseGrowthPerDay, true),
                        $this->formatRunway($databaseRunwayDays),
                    )
                    : 'Runway pojawi się, gdy będzie dojrzały trend rozmiaru pliku bazy.',
                'foot' => sprintf(
                    'Dojrzałość forecastu: %s. %s',
                    $forecastReadiness['label'],
                    $forecastReadiness['note'],
                ),
                'check' => 'Runway bazy',
            ],
            [
                'label' => 'Load CPU',
                'value' => $this->formatPercentValue($cpuLoadRatio1m),
                'tone' => $cpuTone,
                'details' => $cpuLoad1m !== null && $cpuCores !== null
                    ? sprintf('Load 1m wynosi %s przy %s rdzeniach logicznych.', $this->formatLoad($cpuLoad1m), $this->formatNumber($cpuCores))
                    : 'Brak pełnego odczytu load average albo liczby rdzeni.',
                'foot' => $cpuLoad5m !== null
                    ? sprintf('Load 5m: %s.', $this->formatLoad($cpuLoad5m))
                    : 'Średnia 5m będzie widoczna, gdy system zwróci pełny load average.',
                'check' => 'Load CPU',
            ],
            [
                'label' => 'Pamięć',
                'value' => $this->formatPercentValue($memoryUsedRatio),
                'tone' => $memoryTone,
                'details' => $memoryUsedBytes !== null && $memoryTotalBytes !== null
                    ? sprintf('Zajęte %s z %s pamięci systemowej.', $this->formatBytes($memoryUsedBytes), $this->formatBytes($memoryTotalBytes))
                    : 'Brak odczytu pamięci systemowej.',
                'foot' => $memoryAvailableBytes !== null
                    ? sprintf('Dostępne teraz: %s.', $this->formatBytes($memoryAvailableBytes))
                    : 'Nie udało się odczytać wolnej pamięci.',
                'check' => 'Pamięć',
            ],
            [
                'label' => 'Backup',
                'value' => $this->formatBackupStatusValue($backupStatus, $backupAgeHours),
                'tone' => $backupTone,
                'details' => $backupStatus === 'error' && $backupMessage !== null
                    ? 'Monitoring nie mógł odczytać repozytorium backupów. Sam panel działa dalej, ale ten odczyt wymaga poprawienia storage.'
                    : ($backupLastAt !== null
                    ? sprintf(
                        'Ostatni backup zapisano %s%s.',
                        Carbon::parse($backupLastAt)->format('d.m.Y H:i'),
                        $backupLatestBytes !== null ? ' i waży '.strtolower($this->formatBytes($backupLatestBytes)) : '',
                    )
                    : 'Brak ostatniego backupu albo nie da się go odczytać.'),
                'foot' => $backupStatus === 'error' && $backupMessage !== null
                    ? sprintf(
                        'Dysk: %s. Szczegóły: %s',
                        $backupDisk ?? 'brak',
                        (string) str($backupMessage)->limit(140),
                    )
                    : sprintf(
                        'Dysk: %s%s%s',
                        $backupDisk ?? 'brak',
                        $backupRetainedCount !== null ? ' · kopii: '.$this->formatNumber($backupRetainedCount) : '',
                        $backupTotalBytes !== null ? ' · łącznie: '.strtolower($this->formatBytes($backupTotalBytes)) : '',
                    ),
                'check' => 'Backup',
            ],
        ];

        $overallTone = in_array('danger', [$diskTone, $latencyTone, $runwayTone, $cpuTone, $memoryTone, $backupTone], true)
            ? 'danger'
            : (in_array('warning', [$diskTone, $latencyTone, $runwayTone, $cpuTone, $memoryTone, $backupTone], true) ? 'warning' : 'success');

        $headline = match ($overallTone) {
            'danger' => 'Infrastruktura lub backupy wchodzą w strefę ryzyka',
            'warning' => 'System, baza albo backupy wymagają obserwacji',
            default => 'Wolumen, runtime i backupy wyglądają stabilnie',
        };

        $summary = match ($overallTone) {
            'danger' => 'Panel widzi już realne sygnały presji na wolumenie, runtime albo backupach, więc to nie jest już tylko trend danych.',
            'warning' => 'Są pierwsze sygnały, że system, baza lub backupy zaczynają wymagać regularnej obserwacji.',
            default => 'Mamy bieżący odczyt wolnego miejsca, loadu, pamięci i backupów. Nic nie wygląda dziś na przeciążenie ani lukę w odzyskiwaniu.',
        };

        return [
            'tone' => $overallTone,
            'headline' => $headline,
            'summary' => $summary,
            'items' => $items,
            'checks' => [
                ['label' => 'Presja dyskowa', 'tone' => $diskTone],
                ['label' => 'Latencja bazy', 'tone' => $latencyTone],
                ['label' => 'Runway bazy', 'tone' => $runwayTone],
                ['label' => 'Load CPU', 'tone' => $cpuTone],
                ['label' => 'Pamięć', 'tone' => $memoryTone],
                ['label' => 'Backup', 'tone' => $backupTone],
            ],
        ];
    }

    /**
     * @param  array<string, int|null>  $currentMetrics
     * @return array<int, array<string, mixed>>
     */
    protected function buildForecastCards(array $currentMetrics, Collection $snapshots): array
    {
        $definitions = [
            [
                'key' => 'study_sessions_count',
                'label' => 'Surowe sesje',
                'details' => 'Warstwa operacyjna i główny kandydat do wzrostu kosztu cleanupu.',
            ],
            [
                'key' => 'study_session_answers_count',
                'label' => 'Odpowiedzi',
                'details' => 'Największy wolumen w raw danych, zwykle rośnie szybciej niż same sesje.',
            ],
            [
                'key' => 'question_daily_stats_count',
                'label' => 'Dzienne agregaty',
                'details' => 'Detale analityczne, które powinny rosnąć liniowo razem z aktywnością.',
            ],
            [
                'key' => 'question_monthly_stats_count',
                'label' => 'Archiwum miesięczne',
                'details' => 'Warstwa długoterminowa. Jej wzrost powinien być spokojny i przewidywalny.',
            ],
            [
                'key' => 'review_trainer_daily_answers_count',
                'label' => 'Odpowiedzi trenera',
                'details' => 'Answer-level ledger trenera pamięci, czyli realny wolumen powtórek.',
            ],
            [
                'key' => 'review_memory_progress_count',
                'label' => 'Stan pamięci',
                'details' => 'Aktualny stan zweryfikowanej pamięci per użytkownik i pytanie.',
            ],
        ];

        return collect($definitions)->map(function (array $definition) use ($currentMetrics, $snapshots): array {
            $current = $currentMetrics[$definition['key']] ?? null;
            $baseline30 = $this->baselineMetricValue($snapshots, $definition['key'], 30);
            $dailyVelocity = $this->dailyVelocity($snapshots, $definition['key'], $current, 30);
            $forecast = $current !== null && $dailyVelocity !== null
                ? max(0, $current + ($dailyVelocity * 30))
                : null;
            $deltaForecast = $current !== null && $forecast !== null ? $forecast - $current : null;
            $readiness = $this->forecastReadiness($snapshots, $baseline30, $current);
            $tone = $this->forecastTone($current, $forecast, $readiness['level']);

            return [
                'label' => $definition['label'],
                'details' => $definition['details'],
                'current' => $this->formatMetricValue($definition['key'], $current),
                'daily_velocity' => $dailyVelocity === null
                    ? 'brak prognozy'
                    : $this->formatMetricValue($definition['key'], $dailyVelocity, true).' / dzień',
                'forecast' => $this->formatMetricValue($definition['key'], $forecast),
                'delta' => $deltaForecast === null
                    ? 'brak danych'
                    : $this->formatMetricValue($definition['key'], $deltaForecast, true),
                'tone' => $tone,
                'tone_label' => $this->forecastToneLabel($tone, $readiness['level']),
                'baseline' => $this->formatMetricValue($definition['key'], $baseline30),
                'readiness' => $readiness['label'],
                'readiness_note' => $readiness['note'],
                'score_penalty' => $this->forecastPenalty($tone, $readiness['level']),
            ];
        })->all();
    }

    /**
     * @param  array<string, int|null>  $currentMetrics
     * @return array<int, array<string, mixed>>
     */
    protected function buildGrowthRanking(array $currentMetrics, Collection $snapshots): array
    {
        $definitions = [
            ['key' => 'study_session_answers_count', 'label' => 'Odpowiedzi'],
            ['key' => 'study_sessions_count', 'label' => 'Sesje'],
            ['key' => 'question_daily_stats_count', 'label' => 'Dzienne agregaty'],
            ['key' => 'question_monthly_stats_count', 'label' => 'Archiwum miesięczne'],
            ['key' => 'review_trainer_daily_answers_count', 'label' => 'Odpowiedzi trenera'],
            ['key' => 'review_memory_progress_count', 'label' => 'Stan pamięci'],
            ['key' => 'review_trainer_events_count', 'label' => 'Eventy review'],
            ['key' => 'user_ip_histories_count', 'label' => 'Logi IP'],
        ];

        return collect($definitions)
            ->map(function (array $definition) use ($currentMetrics, $snapshots): array {
                $current = $currentMetrics[$definition['key']] ?? null;
                $baseline30 = $this->baselineMetricValue($snapshots, $definition['key'], 30);
                $delta = $current !== null && $baseline30 !== null ? $current - $baseline30 : null;
                $dailyVelocity = $this->dailyVelocity($snapshots, $definition['key'], $current, 30);

                return [
                    'label' => $definition['label'],
                    'current' => $this->formatMetricValue($definition['key'], $current),
                    'delta' => $delta === null ? 'brak danych' : $this->formatMetricValue($definition['key'], $delta, true),
                    'velocity' => $dailyVelocity === null ? 'brak danych' : $this->formatMetricValue($definition['key'], $dailyVelocity, true).' / dzień',
                    'delta_raw' => $delta ?? -1,
                    'tone' => $delta === null ? 'neutral' : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat')),
                ];
            })
            ->sortByDesc('delta_raw')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $latestRuns
     * @param  array<int, array<string, mixed>>  $forecast
     * @param  array<int, array<string, mixed>>  $growthRanking
     * @return array<int, array<string, string>>
     */
    protected function buildRiskItems(array $latestRuns, Collection $snapshots, array $forecast, array $growthRanking, array $infrastructure): array
    {
        $items = collect();

        foreach ($latestRuns as $run) {
            if (! in_array($run['status']['tone'], ['warning', 'danger'], true)) {
                continue;
            }

            $items->push([
                'tone' => $run['status']['tone'],
                'title' => $run['label'],
                'details' => $run['status']['details'],
                'recommendation' => $run['details'],
            ]);
        }

        $snapshotCoverage = $snapshots->count();
        if ($snapshotCoverage < 20) {
            $items->push([
                'tone' => $snapshotCoverage < 10 ? 'danger' : 'warning',
                'title' => 'Niepełna historia snapshotów',
                'details' => sprintf('W oknie 30 dni zapisano tylko %s snapshotów, więc delty i forecast są mniej wiarygodne.', $this->formatNumber($snapshotCoverage)),
                'recommendation' => 'Sprawdź scheduler snapshotów i upewnij się, że codzienny zapis działa bez przerw.',
            ]);
        }

        foreach (collect($forecast)->take(2) as $item) {
            if (! in_array($item['tone'], ['warning', 'danger'], true)) {
                continue;
            }

            $items->push([
                'tone' => $item['tone'],
                'title' => 'Przyspiesza '.$item['label'],
                'details' => sprintf('Przy obecnym tempie warstwa może dojść do %s w ciągu 30 dni.', $item['forecast']),
                'recommendation' => sprintf('Tempo z ostatnich 30 dni: %s. Warto obserwować retencję i skuteczność cleanupu.', $item['daily_velocity']),
            ]);
        }

        $fastestGrowth = collect($growthRanking)->first(fn (array $item): bool => $item['tone'] === 'up');
        if ($fastestGrowth !== null) {
            $items->push([
                'tone' => 'neutral',
                'title' => 'Najszybciej rośnie '.$fastestGrowth['label'],
                'details' => sprintf('Zmiana w ostatnich 30 dniach: %s, tempo: %s.', $fastestGrowth['delta'], $fastestGrowth['velocity']),
                'recommendation' => 'To dobry kandydat do obserwowania w krótkim terminie, jeśli zacznie przebijać resztę warstw.',
            ]);
        }

        if ($infrastructure['tone'] !== 'success') {
            $primaryInfrastructureRisk = collect($infrastructure['items'])
                ->first(fn (array $item): bool => in_array($item['tone'], ['warning', 'danger'], true));

            $items->push([
                'tone' => $primaryInfrastructureRisk['tone'] ?? $infrastructure['tone'],
                'title' => 'Presja infrastruktury',
                'details' => $infrastructure['headline'],
                'recommendation' => $primaryInfrastructureRisk['details'] ?? $infrastructure['summary'],
            ]);
        }

        if ($items->isEmpty()) {
            $items->push([
                'tone' => 'success',
                'title' => 'Brak otwartych ryzyk',
                'details' => 'Joby są świeże, snapshoty kompletne, a wzrost rozkłada się przewidywalnie.',
                'recommendation' => 'Na dziś nie ma sygnału, że retencja lub rollup nie nadążają za wzrostem danych.',
            ]);
        }

        return $items->take(4)->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $latestRuns
     * @return array<int, array<string, string>>
     */
    protected function buildAttentionItems(array $latestRuns): array
    {
        $attentionItems = collect($latestRuns)
            ->filter(fn (array $run): bool => in_array($run['status']['tone'], ['warning', 'danger'], true))
            ->values();

        if ($attentionItems->isEmpty()) {
            return collect($latestRuns)
                ->map(fn (array $run): array => [
                    'label' => $run['label'],
                    'tone' => 'success',
                    'headline' => 'Brak otwartych problemów',
                    'details' => $run['details'],
                    'meta' => $run['run_at'],
                ])
                ->take(2)
                ->all();
        }

        return $attentionItems
            ->map(fn (array $run): array => [
                'label' => $run['label'],
                'tone' => $run['status']['tone'],
                'headline' => $run['status']['label'],
                'details' => $run['status']['details'],
                'meta' => $run['run_at'],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildDataQuality(Collection $snapshots, mixed $latestDailyStatDate, mixed $latestMonthlyStatDate, array $thresholds): array
    {
        $expectedDays = 30;
        $latestSnapshot = $snapshots->last();
        $missingDays = max($expectedDays - $snapshots->count(), 0);
        $completeness = (int) round(($snapshots->count() / max($expectedDays, 1)) * 100);
        $snapshotTone = $completeness >= $thresholds['snapshot_completeness_warning_pct']
            ? 'success'
            : ($completeness >= $thresholds['snapshot_completeness_danger_pct'] ? 'warning' : 'danger');

        $latestDaily = $latestDailyStatDate ? Carbon::parse((string) $latestDailyStatDate) : null;
        $dailyLag = $latestDaily ? $latestDaily->diffInDays(now()->startOfDay()) : null;
        $dailyTone = $dailyLag === null
            ? 'danger'
            : ($dailyLag <= $thresholds['daily_stats_warning_lag_days']
                ? 'success'
                : ($dailyLag <= $thresholds['daily_stats_danger_lag_days'] ? 'warning' : 'danger'));

        $latestMonthly = $latestMonthlyStatDate ? Carbon::parse((string) $latestMonthlyStatDate) : null;
        $monthlyTone = $latestMonthly === null ? 'neutral' : 'success';

        return [
            'snapshot_completeness' => [
                'value' => $completeness.'%',
                'tone' => $snapshotTone,
                'details' => sprintf('%s z %s oczekiwanych snapshotów z ostatnich 30 dni.', $this->formatNumber($snapshots->count()), $this->formatNumber($expectedDays)),
                'foot' => $missingDays > 0 ? sprintf('Brakuje %s dni w serii.', $this->formatNumber($missingDays)) : 'Seria snapshotów jest kompletna.',
            ],
            'daily_stats_freshness' => [
                'value' => $latestDaily?->format('d.m.Y') ?? 'brak',
                'tone' => $dailyTone,
                'details' => $latestDaily
                    ? sprintf('Ostatni dzień dziennych agregatów jest opóźniony o %s dni.', $this->formatNumber($dailyLag ?? 0))
                    : 'Brak jeszcze dziennych agregatów do oceny.',
                'foot' => $dailyLag !== null && $dailyLag <= 1
                    ? 'Dzienny detal jest świeży.'
                    : 'Warto sprawdzić, czy agregacja dzienna nadąża.',
            ],
            'monthly_archive' => [
                'value' => $latestMonthly?->translatedFormat('m.Y') ?? 'brak',
                'tone' => $monthlyTone,
                'details' => $latestMonthly
                    ? 'Archiwum miesięczne istnieje i jest gotowe do trzymania starszych trendów.'
                    : 'Archiwum miesięczne jeszcze nie ma czego przejąć albo rollup nie uruchomił się na starszych danych.',
                'foot' => $latestSnapshot !== null
                    ? 'Ostatni snapshot: '.Carbon::parse((string) $latestSnapshot->snapshot_date)->format('d.m.Y')
                    : 'Brak ostatniego snapshotu.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function buildRetentionImpact(Collection $snapshots): array
    {
        $lastDeletedCompleted = (int) (Cache::get('ops.study_history_prune.last_deleted_completed') ?? 0);
        $lastDeletedAbandoned = (int) (Cache::get('ops.study_history_prune.last_deleted_abandoned') ?? 0);
        $lastRollupDeleted = (int) (Cache::get('ops.question_monthly_rollup.last_deleted_daily_rows') ?? 0);
        $lastRollupRows = (int) (Cache::get('ops.question_monthly_rollup.last_rows') ?? 0);

        $rawDelta30 = $this->metricDeltaFromSnapshots($snapshots, 'study_sessions_count', 30);
        $dailyDelta30 = $this->metricDeltaFromSnapshots($snapshots, 'question_daily_stats_count', 30);

        return [
            [
                'label' => 'Ostatni cleanup raw',
                'value' => $this->formatNumber($lastDeletedCompleted + $lastDeletedAbandoned),
                'details' => sprintf(
                    'Usunięto %s zakończonych i %s porzuconych sesji.',
                    $this->formatNumber($lastDeletedCompleted),
                    $this->formatNumber($lastDeletedAbandoned),
                ),
            ],
            [
                'label' => 'Ostatni rollup',
                'value' => $this->formatNumber($lastRollupDeleted),
                'details' => sprintf(
                    'Do archiwum miesięcznego trafiło %s rekordów i skasowano %s dziennych wpisów.',
                    $this->formatNumber($lastRollupRows),
                    $this->formatNumber($lastRollupDeleted),
                ),
            ],
            [
                'label' => 'Netto raw / 30 dni',
                'value' => $rawDelta30 === null ? 'brak' : $this->formatNumber($rawDelta30),
                'details' => 'Pokazuje, czy raw warstwa mimo retencji nadal szybko puchnie.',
            ],
            [
                'label' => 'Netto daily / 30 dni',
                'value' => $dailyDelta30 === null ? 'brak' : $this->formatNumber($dailyDelta30),
                'details' => 'To wzrost dziennych agregatów po uwzględnieniu bieżącego rollupu i retencji.',
            ],
        ];
    }

    /**
     * @param  array<string, int|null>  $currentMetrics
     * @return array<int, array<string, string>>
     */
    protected function buildTrendComparisons(array $currentMetrics, Collection $snapshots): array
    {
        $definitions = [
            ['key' => 'study_sessions_count', 'label' => 'Sesje'],
            ['key' => 'study_session_answers_count', 'label' => 'Odpowiedzi'],
            ['key' => 'question_daily_stats_count', 'label' => 'Dzienne agregaty'],
            ['key' => 'review_trainer_daily_answers_count', 'label' => 'Odpowiedzi trenera'],
            ['key' => 'review_memory_progress_count', 'label' => 'Stan pamięci'],
        ];

        return collect($definitions)->map(function (array $definition) use ($currentMetrics, $snapshots): array {
            $current = $currentMetrics[$definition['key']] ?? null;
            $baseline7 = $this->baselineMetricValue($snapshots, $definition['key'], 7);
            $baseline30 = $this->baselineMetricValue($snapshots, $definition['key'], 30);
            $weekDelta = $current !== null && $baseline7 !== null ? $current - $baseline7 : null;
            $monthDelta = $current !== null && $baseline30 !== null ? $current - $baseline30 : null;
            $tone = $this->trendTone($weekDelta, $monthDelta);

            return [
                'label' => $definition['label'],
                'week' => $weekDelta === null ? 'brak danych' : $this->formatMetricValue($definition['key'], $weekDelta, true),
                'month' => $monthDelta === null ? 'brak danych' : $this->formatMetricValue($definition['key'], $monthDelta, true),
                'signal' => $this->trendSignalLabel($tone),
                'tone' => $tone,
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $latestRuns
     * @param  array<string, mixed>  $dataQuality
     * @param  array<int, array<string, mixed>>  $forecast
     * @return array<string, mixed>
     */
    protected function buildHealthScore(array $latestRuns, array $dataQuality, array $forecast, Collection $snapshots, array $thresholds, array $infrastructure): array
    {
        $score = 100;
        $breakdown = [];

        foreach ($latestRuns as $run) {
            if ($run['status']['tone'] === 'danger') {
                $score -= 18;
                $breakdown[] = ['label' => $run['label'], 'impact' => '-18', 'tone' => 'danger'];
            } elseif ($run['status']['tone'] === 'warning') {
                $score -= 9;
                $breakdown[] = ['label' => $run['label'], 'impact' => '-9', 'tone' => 'warning'];
            }
        }

        if ($dataQuality['snapshot_completeness']['tone'] === 'danger') {
            $score -= 20;
            $breakdown[] = ['label' => 'Snapshoty', 'impact' => '-20', 'tone' => 'danger'];
        } elseif ($dataQuality['snapshot_completeness']['tone'] === 'warning') {
            $score -= 10;
            $breakdown[] = ['label' => 'Snapshoty', 'impact' => '-10', 'tone' => 'warning'];
        }

        if ($dataQuality['daily_stats_freshness']['tone'] === 'danger') {
            $score -= 15;
            $breakdown[] = ['label' => 'Dzienne agregaty', 'impact' => '-15', 'tone' => 'danger'];
        } elseif ($dataQuality['daily_stats_freshness']['tone'] === 'warning') {
            $score -= 8;
            $breakdown[] = ['label' => 'Dzienne agregaty', 'impact' => '-8', 'tone' => 'warning'];
        }

        $forecastPenalty = collect($forecast)->sum(function (array $item): int {
            return (int) ($item['score_penalty'] ?? 0);
        });

        if ($forecastPenalty > 0) {
            $score -= $forecastPenalty;
            $breakdown[] = ['label' => 'Forecast wzrostu', 'impact' => '-'.$forecastPenalty, 'tone' => $forecastPenalty >= 8 ? 'danger' : 'warning'];
        }

        foreach ($infrastructure['checks'] as $check) {
            if ($check['tone'] === 'danger') {
                $score -= match ($check['label']) {
                    'Presja dyskowa' => 18,
                    'Latencja bazy' => 12,
                    'Runway bazy' => 12,
                    'Load CPU' => 10,
                    'Pamięć' => 10,
                    'Backup' => 12,
                    default => 10,
                };
                $breakdown[] = [
                    'label' => $check['label'],
                    'impact' => '-'.match ($check['label']) {
                        'Presja dyskowa' => 18,
                        'Latencja bazy' => 12,
                        'Runway bazy' => 12,
                        'Load CPU' => 10,
                        'Pamięć' => 10,
                        'Backup' => 12,
                        default => 10,
                    },
                    'tone' => 'danger',
                ];
            } elseif ($check['tone'] === 'warning') {
                $score -= match ($check['label']) {
                    'Presja dyskowa' => 9,
                    'Latencja bazy' => 6,
                    'Runway bazy' => 6,
                    'Load CPU' => 5,
                    'Pamięć' => 5,
                    'Backup' => 6,
                    default => 5,
                };
                $breakdown[] = [
                    'label' => $check['label'],
                    'impact' => '-'.match ($check['label']) {
                        'Presja dyskowa' => 9,
                        'Latencja bazy' => 6,
                        'Runway bazy' => 6,
                        'Load CPU' => 5,
                        'Pamięć' => 5,
                        'Backup' => 6,
                        default => 5,
                    },
                    'tone' => 'warning',
                ];
            }
        }

        if ($snapshots->count() < 25) {
            $score -= 6;
            $breakdown[] = ['label' => 'Krótka historia trendów', 'impact' => '-6', 'tone' => 'warning'];
        }

        $score = max(0, min(100, $score));

        return [
            'value' => $score,
            'tone' => $score >= $thresholds['health_warning_score']
                ? 'success'
                : ($score >= $thresholds['health_danger_score'] ? 'warning' : 'danger'),
            'label' => $score >= $thresholds['health_warning_score']
                ? 'Zdrowy'
                : ($score >= $thresholds['health_danger_score'] ? 'Do obserwacji' : 'Ryzykowny'),
            'details' => $score >= $thresholds['health_warning_score']
                ? 'Retencja, agregacja i monitoring wyglądają stabilnie.'
                : ($score >= $thresholds['health_danger_score']
                    ? 'Są już sygnały, które warto regularnie obserwować.'
                    : 'Panel pokazuje wzorzec, który może doprowadzić do realnego problemu operacyjnego.'),
            'breakdown' => $breakdown === [] ? [['label' => 'Brak kar', 'impact' => '0', 'tone' => 'success']] : $breakdown,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $trendComparisons
     * @param  array<int, array<string, mixed>>  $latestRuns
     * @param  array<string, mixed>  $dataQuality
     * @return array<int, array<string, string>>
     */
    protected function buildAnomalies(array $trendComparisons, array $latestRuns, array $dataQuality, Collection $snapshots, array $infrastructure): array
    {
        $items = collect();

        foreach ($trendComparisons as $trend) {
            if (! in_array($trend['tone'], ['warning', 'danger'], true)) {
                continue;
            }

            $items->push([
                'label' => $trend['label'],
                'headline' => $trend['signal'],
                'details' => sprintf('7 dni: %s · 30 dni: %s', $trend['week'], $trend['month']),
                'tone' => $trend['tone'],
            ]);
        }

        if ($dataQuality['snapshot_completeness']['tone'] !== 'success') {
            $items->push([
                'label' => 'Snapshoty',
                'headline' => 'Luka w monitoringu',
                'details' => $dataQuality['snapshot_completeness']['foot'],
                'tone' => $dataQuality['snapshot_completeness']['tone'],
            ]);
        }

        $staleRun = collect($latestRuns)->first(fn (array $run): bool => $run['status']['tone'] === 'danger');
        if ($staleRun !== null) {
            $items->push([
                'label' => $staleRun['label'],
                'headline' => 'Job wyszedł poza bezpieczne okno',
                'details' => $staleRun['status']['details'],
                'tone' => 'danger',
            ]);
        }

        if ($infrastructure['tone'] !== 'success') {
            $items->push([
                'label' => 'Presja infrastruktury',
                'headline' => $infrastructure['headline'],
                'details' => $infrastructure['summary'],
                'tone' => $infrastructure['tone'],
            ]);
        }

        if ($items->isEmpty()) {
            $items->push([
                'label' => 'Brak anomalii',
                'headline' => 'Trend jest równy',
                'details' => sprintf('Na dziś seria %s snapshotów nie pokazuje skoku, który odbiega od ostatnich 30 dni.', $this->formatNumber($snapshots->count())),
                'tone' => 'success',
            ]);
        }

        return $items->take(4)->values()->all();
    }

    /**
     * @param  array<string, int|float>  $thresholds
     * @return array<int, array<string, string>>
     */
    protected function buildThresholdRows(array $thresholds): array
    {
        return [
            [
                'label' => 'Health score',
                'warning' => '< '.$thresholds['health_warning_score'],
                'danger' => '< '.$thresholds['health_danger_score'],
                'details' => 'Jeśli wynik spada poniżej tych wartości, panel powinien przestać być tylko obserwacją i wejść w tryb reakcji.',
            ],
            [
                'label' => 'Snapshot completeness',
                'warning' => '< '.$thresholds['snapshot_completeness_warning_pct'].'%',
                'danger' => '< '.$thresholds['snapshot_completeness_danger_pct'].'%',
                'details' => 'Przy lukach w snapshotach forecast i wykresy tracą wiarygodność.',
            ],
            [
                'label' => 'Lag daily stats',
                'warning' => '> '.$thresholds['daily_stats_warning_lag_days'].' dzień',
                'danger' => '> '.$thresholds['daily_stats_danger_lag_days'].' dni',
                'details' => 'To punkt, w którym dzienny detal przestaje nadążać za realną aktywnością systemu.',
            ],
            [
                'label' => 'Forecast growth',
                'warning' => '>= '.round(($thresholds['forecast_growth_warning_ratio'] - 1) * 100).'% / 30 dni',
                'danger' => '>= '.round(($thresholds['forecast_growth_danger_ratio'] - 1) * 100).'% / 30 dni',
                'details' => 'Te progi mówią, kiedy wzrost zaczyna wymagać reakcji po stronie cleanupu, retencji albo pojemności.',
            ],
            [
                'label' => 'Presja dyskowa',
                'warning' => '>= '.$thresholds['disk_usage_warning_pct'].'% lub < '.$thresholds['disk_free_warning_gb'].' GB',
                'danger' => '>= '.$thresholds['disk_usage_danger_pct'].'% lub < '.$thresholds['disk_free_danger_gb'].' GB',
                'details' => 'To granica, od której wolumen z bazą zaczyna realnie zbliżać się do limitu miejsca.',
            ],
            [
                'label' => 'Latencja DB',
                'warning' => '> '.$thresholds['database_latency_warning_ms'].' ms',
                'danger' => '> '.$thresholds['database_latency_danger_ms'].' ms',
                'details' => 'Lekki ping `SELECT 1` zaczyna wtedy sugerować, że baza nie odpowiada już tak lekko jak powinna.',
            ],
            [
                'label' => 'Runway bazy',
                'warning' => '< '.$thresholds['database_runway_warning_days'].' dni',
                'danger' => '< '.$thresholds['database_runway_danger_days'].' dni',
                'details' => 'Przy obecnym tempie wzrostu pliku bazy tyle czasu zostało do zjedzenia bieżącego wolnego miejsca.',
            ],
            [
                'label' => 'Load CPU',
                'warning' => '>= '.round($thresholds['system_load_warning_ratio'] * 100).'% rdzeni',
                'danger' => '>= '.round($thresholds['system_load_danger_ratio'] * 100).'% rdzeni',
                'details' => 'Normalizujemy load 1m do liczby rdzeni, żeby szybko zobaczyć, czy system zaczyna być stale zajęty.',
            ],
            [
                'label' => 'Pamięć',
                'warning' => '>= '.$thresholds['memory_usage_warning_pct'].'%',
                'danger' => '>= '.$thresholds['memory_usage_danger_pct'].'%',
                'details' => 'To punkt, w którym pamięć systemowa zaczyna schodzić do poziomu ryzyka dla procesów aplikacji.',
            ],
            [
                'label' => 'Backup freshness',
                'warning' => '> '.(int) config('health.backup_max_age_hours', 36).' h',
                'danger' => 'missing / error',
                'details' => 'Backup przestaje być tylko formalnością i staje się ryzykiem odzyskania, jeśli wyjdzie poza bezpieczne okno.',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $latestRuns
     * @param  array<int, array<string, mixed>>  $forecast
     * @param  array<string, mixed>  $healthScore
     * @param  array<int, array<string, string>>  $anomalies
     * @return array<int, array<string, string>>
     */
    protected function buildRecommendedActions(array $latestRuns, array $dataQuality, array $forecast, array $healthScore, array $anomalies, array $infrastructure): array
    {
        $actions = collect();

        if ($healthScore['tone'] === 'danger') {
            $actions->push([
                'priority' => 'P1',
                'title' => 'Sprawdź cały łańcuch jobów utrzymaniowych',
                'details' => 'Health score spadł do poziomu ryzyka. Trzeba zweryfikować scheduler, cleanup, agregację i rollup zanim panel zacznie tracić wiarygodność.',
                'owner' => 'Operacje / backend',
            ]);
        }

        $dangerRun = collect($latestRuns)->first(fn (array $run): bool => $run['status']['tone'] === 'danger');
        if ($dangerRun !== null) {
            $actions->push([
                'priority' => 'P1',
                'title' => 'Uruchom i zweryfikuj '.$dangerRun['label'],
                'details' => $dangerRun['status']['details'].' '.$dangerRun['details'],
                'owner' => 'Operacje',
            ]);
        }

        if ($dataQuality['snapshot_completeness']['tone'] !== 'success') {
            $actions->push([
                'priority' => 'P2',
                'title' => 'Uzupełnij luki w snapshotach monitoringu',
                'details' => $dataQuality['snapshot_completeness']['foot'].' Bez tego porównania i wykresy mogą prowadzić do złych decyzji.',
                'owner' => 'Backend / ops',
            ]);
        }

        if ($dataQuality['daily_stats_freshness']['tone'] !== 'success') {
            $actions->push([
                'priority' => 'P2',
                'title' => 'Zweryfikuj świeżość dziennych agregatów',
                'details' => $dataQuality['daily_stats_freshness']['details'],
                'owner' => 'Analityka',
            ]);
        }

        $forecastRisk = collect($forecast)->first(fn (array $item): bool => in_array($item['tone'], ['warning', 'danger'], true));
        if ($forecastRisk !== null) {
            $actions->push([
                'priority' => $forecastRisk['tone'] === 'danger' ? 'P1' : 'P3',
                'title' => 'Obserwuj wzrost warstwy '.$forecastRisk['label'],
                'details' => sprintf('Tempo %s sugeruje dojście do %s w ciągu 30 dni.', $forecastRisk['daily_velocity'], $forecastRisk['forecast']),
                'owner' => 'Operacje / DBA',
            ]);
        }

        $anomaly = collect($anomalies)->first(fn (array $item): bool => in_array($item['tone'], ['warning', 'danger'], true));
        if ($anomaly !== null) {
            $actions->push([
                'priority' => $anomaly['tone'] === 'danger' ? 'P2' : 'P3',
                'title' => 'Sprawdź anomalię: '.$anomaly['label'],
                'details' => $anomaly['details'],
                'owner' => 'Analityka / ops',
            ]);
        }

        $infrastructureCheck = collect($infrastructure['items'])
            ->first(fn (array $item): bool => in_array($item['tone'], ['warning', 'danger'], true));
        if ($infrastructureCheck !== null) {
            $actions->push([
                'priority' => $infrastructureCheck['tone'] === 'danger' ? 'P1' : 'P2',
                'title' => 'Zweryfikuj infrastrukturę: '.$infrastructureCheck['label'],
                'details' => trim($infrastructureCheck['details'].' '.$infrastructureCheck['foot']),
                'owner' => 'Operacje / DBA',
            ]);
        }

        if ($actions->isEmpty()) {
            $actions->push([
                'priority' => 'OK',
                'title' => 'Brak natychmiastowej akcji',
                'details' => 'Panel nie pokazuje dziś sygnału, który wymaga ręcznej interwencji. Wystarczy obserwacja i standardowy rytm jobów.',
                'owner' => 'Monitoruj',
            ]);
        }

        return $actions->take(5)->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $latestRuns
     * @return array<int, array<string, string>>
     */
    protected function buildEventTimeline(Collection $snapshots, array $latestRuns, mixed $latestDailyStatDate, mixed $latestMonthlyStatDate): array
    {
        $events = collect();

        $latestSnapshot = $snapshots->last();
        if ($latestSnapshot !== null) {
            $events->push([
                'time' => Carbon::parse((string) $latestSnapshot->snapshot_date)->endOfDay()->format('d.m.Y H:i'),
                'title' => 'Ostatni snapshot monitoringu',
                'details' => sprintf('Seria trendów kończy się na dniu %s.', Carbon::parse((string) $latestSnapshot->snapshot_date)->format('d.m.Y')),
                'tone' => 'success',
                'sort' => Carbon::parse((string) $latestSnapshot->snapshot_date)->endOfDay()->timestamp,
            ]);
        }

        if ($latestDailyStatDate) {
            $daily = Carbon::parse((string) $latestDailyStatDate)->endOfDay();
            $events->push([
                'time' => $daily->format('d.m.Y H:i'),
                'title' => 'Ostatni dzień dziennych agregatów',
                'details' => sprintf('Daily stats zostały zbudowane do %s.', $daily->format('d.m.Y')),
                'tone' => 'success',
                'sort' => $daily->timestamp,
            ]);
        }

        if ($latestMonthlyStatDate) {
            $monthly = Carbon::parse((string) $latestMonthlyStatDate)->endOfMonth();
            $events->push([
                'time' => $monthly->format('d.m.Y H:i'),
                'title' => 'Ostatni miesiąc archiwum',
                'details' => sprintf('Monthly archive obejmuje już miesiąc %s.', Carbon::parse((string) $latestMonthlyStatDate)->translatedFormat('m.Y')),
                'tone' => 'neutral',
                'sort' => $monthly->timestamp,
            ]);
        }

        foreach ($latestRuns as $run) {
            $cacheKey = is_array($run) ? ($run['cache_key'] ?? null) : null;
            $rawRunAt = filled($cacheKey) ? Cache::get((string) $cacheKey) : null;

            if (! filled($rawRunAt)) {
                continue;
            }

            $at = Carbon::parse((string) $rawRunAt);
            $events->push([
                'time' => $at->format('d.m.Y H:i'),
                'title' => $run['label'],
                'details' => $run['details'],
                'tone' => $run['status']['tone'],
                'sort' => $at->timestamp,
            ]);
        }

        return $events
            ->sortByDesc('sort')
            ->take(8)
            ->map(fn (array $event): array => [
                'time' => $event['time'],
                'title' => $event['title'],
                'details' => $event['details'],
                'tone' => $event['tone'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{label:string,run_at:string,details:string,status:array{tone:string,label:string,details:string},cache_key:?string}
     */
    protected function sqliteMaintenanceRunItem(): array
    {
        $driver = $this->currentDatabaseDriver();

        if ($driver !== 'sqlite') {
            $details = sprintf('Przy %s maintenance pliku SQLite nie jest uruchamiane.', strtoupper($driver));

            return [
                'label' => 'Maintenance lokalnej bazy',
                'run_at' => 'Nie dotyczy',
                'details' => $details,
                'status' => [
                    'tone' => 'neutral',
                    'label' => 'Nie dotyczy',
                    'details' => $details,
                ],
                'cache_key' => null,
            ];
        }

        return [
            'label' => 'Maintenance lokalnej bazy',
            'run_at' => $this->formatRunTimestamp(Cache::get('ops.sqlite_maintenance.last_run_at')),
            'details' => (string) (Cache::get('ops.sqlite_maintenance.last_details') ?? 'Brak zapisanego wyniku.'),
            'status' => $this->runStatus(Cache::get('ops.sqlite_maintenance.last_run_at'), 36),
            'cache_key' => 'ops.sqlite_maintenance.last_run_at',
        ];
    }

    protected function currentDatabaseDriver(): string
    {
        return (string) config('database.default', 'sqlite');
    }

    protected function latestAggregateDetails(mixed $latestDailyStatDate): string
    {
        $lastDate = $latestDailyStatDate instanceof CarbonInterface
            ? $latestDailyStatDate->format('d.m.Y')
            : (filled($latestDailyStatDate) ? (string) $latestDailyStatDate : null);
        $lastRows = (int) (Cache::get('ops.question_daily_stats.last_rows') ?? 0);

        if ($lastDate === null) {
            return 'Brak jeszcze zbudowanych agregatów.';
        }

        return sprintf(
            'Ostatni zbudowany dzień: %s · rekordów w ostatnim przebiegu: %s',
            $lastDate,
            $this->formatNumber($lastRows),
        );
    }

    protected function latestPruneDetails(): string
    {
        $completed = (int) (Cache::get('ops.study_history_prune.last_deleted_completed') ?? 0);
        $abandoned = (int) (Cache::get('ops.study_history_prune.last_deleted_abandoned') ?? 0);

        return sprintf(
            'Usunięto %s zakończonych sesji i %s porzuconych in_progress.',
            $this->formatNumber($completed),
            $this->formatNumber($abandoned),
        );
    }

    protected function latestMonthlyRollupDetails(): string
    {
        $months = (int) (Cache::get('ops.question_monthly_rollup.last_months') ?? 0);
        $rows = (int) (Cache::get('ops.question_monthly_rollup.last_rows') ?? 0);
        $deleted = (int) (Cache::get('ops.question_monthly_rollup.last_deleted_daily_rows') ?? 0);
        $lastMonth = Cache::get('ops.question_monthly_rollup.last_month');

        if (! filled(Cache::get('ops.question_monthly_rollup.last_run_at'))) {
            return 'Nie było jeszcze miesięcznego rollupu dziennych agregatów.';
        }

        $monthLabel = filled($lastMonth)
            ? Carbon::parse((string) $lastMonth)->translatedFormat('m.Y')
            : 'brak danych';

        return sprintf(
            'Zwinięto %s miesięcy do %s rekordów archiwalnych i usunięto %s dziennych rekordów. Ostatni miesiąc: %s.',
            $this->formatNumber($months),
            $this->formatNumber($rows),
            $this->formatNumber($deleted),
            $monthLabel,
        );
    }

    /**
     * @return array{tone:string,label:string,details:string}
     */
    protected function runStatus(mixed $value, int $freshHours): array
    {
        if (! filled($value)) {
            return [
                'tone' => 'neutral',
                'label' => 'Brak przebiegu',
                'details' => 'Nie ma jeszcze zapisanego wykonania.',
            ];
        }

        $timestamp = Carbon::parse((string) $value);
        $hours = $timestamp->diffInHours(now());

        if ($hours <= $freshHours) {
            return [
                'tone' => 'success',
                'label' => 'Aktualne',
                'details' => sprintf('Ostatni przebieg %s temu.', CarbonInterval::hours($hours)->cascade()->forHumans(['short' => true])),
            ];
        }

        if ($hours <= ($freshHours * 2)) {
            return [
                'tone' => 'warning',
                'label' => 'Do sprawdzenia',
                'details' => sprintf('Ostatni przebieg %s temu.', CarbonInterval::hours($hours)->cascade()->forHumans(['short' => true])),
            ];
        }

        return [
            'tone' => 'danger',
            'label' => 'Przestarzałe',
            'details' => sprintf('Brak świeżego przebiegu od %s.', CarbonInterval::hours($hours)->cascade()->forHumans(['short' => true])),
        ];
    }

    protected function formatRunTimestamp(mixed $value): string
    {
        if (! filled($value)) {
            return 'Jeszcze nie uruchomiono';
        }

        return Carbon::parse((string) $value)->format('d.m.Y H:i');
    }

    protected function diskPressureTone(?float $usedRatio, ?int $freeBytes, array $thresholds): string
    {
        if ($usedRatio === null && $freeBytes === null) {
            return 'neutral';
        }

        $usedPercent = $usedRatio !== null ? $usedRatio * 100 : null;
        $freeGigabytes = $freeBytes !== null ? ($freeBytes / 1024 / 1024 / 1024) : null;

        if (
            ($usedPercent !== null && $usedPercent >= $thresholds['disk_usage_danger_pct'])
            || ($freeGigabytes !== null && $freeGigabytes <= $thresholds['disk_free_danger_gb'])
        ) {
            return 'danger';
        }

        if (
            ($usedPercent !== null && $usedPercent >= $thresholds['disk_usage_warning_pct'])
            || ($freeGigabytes !== null && $freeGigabytes <= $thresholds['disk_free_warning_gb'])
        ) {
            return 'warning';
        }

        return 'success';
    }

    protected function databaseLatencyTone(?int $latencyMs, array $thresholds): string
    {
        if ($latencyMs === null) {
            return 'neutral';
        }

        if ($latencyMs > $thresholds['database_latency_danger_ms']) {
            return 'danger';
        }

        if ($latencyMs > $thresholds['database_latency_warning_ms']) {
            return 'warning';
        }

        return 'success';
    }

    protected function databaseRunwayTone(?int $days, string $readinessLevel, array $thresholds): string
    {
        if ($days === null || $readinessLevel === 'low') {
            return 'neutral';
        }

        if ($days <= $thresholds['database_runway_danger_days']) {
            return 'danger';
        }

        if ($days <= $thresholds['database_runway_warning_days']) {
            return 'warning';
        }

        return 'success';
    }

    protected function systemLoadTone(?float $loadRatio, array $thresholds): string
    {
        if ($loadRatio === null) {
            return 'neutral';
        }

        if ($loadRatio >= $thresholds['system_load_danger_ratio']) {
            return 'danger';
        }

        if ($loadRatio >= $thresholds['system_load_warning_ratio']) {
            return 'warning';
        }

        return 'success';
    }

    protected function memoryUsageTone(?float $usedRatio, array $thresholds): string
    {
        if ($usedRatio === null) {
            return 'neutral';
        }

        $usedPercent = $usedRatio * 100;

        if ($usedPercent >= $thresholds['memory_usage_danger_pct']) {
            return 'danger';
        }

        if ($usedPercent >= $thresholds['memory_usage_warning_pct']) {
            return 'warning';
        }

        return 'success';
    }

    protected function backupTone(?string $status): string
    {
        return match ($status) {
            'ok' => 'success',
            'stale' => 'warning',
            'missing', 'error' => 'danger',
            default => 'neutral',
        };
    }

    protected function databaseRunwayDays(?int $freeBytes, ?int $databaseGrowthPerDay, string $readinessLevel): ?int
    {
        if ($freeBytes === null || $databaseGrowthPerDay === null || $databaseGrowthPerDay <= 0 || $readinessLevel === 'low') {
            return null;
        }

        return (int) floor($freeBytes / max($databaseGrowthPerDay, 1));
    }

    protected function formatPercentValue(?float $ratio): string
    {
        if ($ratio === null) {
            return 'brak danych';
        }

        return number_format($ratio * 100, 1, ',', ' ').'%';
    }

    protected function formatLoad(?float $load): string
    {
        if ($load === null) {
            return 'brak danych';
        }

        return number_format($load, 2, ',', ' ');
    }

    protected function formatLatency(?int $latencyMs): string
    {
        if ($latencyMs === null) {
            return 'brak danych';
        }

        return $this->formatNumber($latencyMs).' ms';
    }

    protected function formatRunway(?int $days): string
    {
        if ($days === null) {
            return 'brak forecastu';
        }

        if ($days >= 365) {
            return '> 365 dni';
        }

        return $this->formatNumber($days).' dni';
    }

    protected function formatBackupStatusValue(?string $status, ?float $ageHours): string
    {
        return match ($status) {
            'ok' => $ageHours !== null ? number_format($ageHours, 1, ',', ' ').' h' : 'aktualny',
            'stale' => $ageHours !== null ? number_format($ageHours, 1, ',', ' ').' h' : 'nieświeży',
            'missing' => 'brak',
            'error' => 'błąd',
            default => 'brak danych',
        };
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $value = (float) $bytes;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return number_format($value, $unitIndex === 0 ? 0 : 2, ',', ' ').' '.$units[$unitIndex];
    }

    protected function formatNumber(int $value): string
    {
        return number_format($value, 0, ',', ' ');
    }

    protected function formatMetricValue(string $key, ?int $value, bool $signed = false): string
    {
        if ($value === null) {
            return 'brak danych';
        }

        if (in_array($key, ['database_size_bytes', 'database_volume_total_bytes', 'database_volume_free_bytes', 'database_volume_used_bytes'], true)) {
            $prefix = $signed && $value > 0 ? '+' : '';

            return $prefix.$this->formatBytes(abs($value));
        }

        $formatted = $this->formatNumber(abs($value));
        $prefix = '';

        if ($signed) {
            $prefix = $value > 0 ? '+' : ($value < 0 ? '-' : '');
        }

        return $prefix.$formatted;
    }

    /**
     * @return array{label:string,value:string,tone:string}
     */
    protected function buildDeltaBadge(string $label, string $key, ?int $baseline, ?int $current): array
    {
        if ($baseline === null || $current === null) {
            return [
                'label' => $label,
                'value' => 'brak',
                'tone' => 'neutral',
            ];
        }

        $delta = $current - $baseline;

        return [
            'label' => $label,
            'value' => $delta === 0 ? 'bez zmiany' : $this->formatMetricValue($key, $delta, true),
            'tone' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'),
        ];
    }

    protected function baselineMetricValue(Collection $snapshots, string $key, int $daysAgo): ?int
    {
        $targetDate = now()->subDays($daysAgo)->startOfDay();
        $baseline = $snapshots
            ->filter(fn (MonitoringSnapshot $snapshot): bool => $snapshot->{$key} !== null)
            ->filter(fn (MonitoringSnapshot $snapshot): bool => Carbon::parse($snapshot->snapshot_date)->lte($targetDate))
            ->sortBy('snapshot_date')
            ->last();

        if ($baseline === null) {
            $baseline = $snapshots->first(fn (MonitoringSnapshot $snapshot): bool => $snapshot->{$key} !== null);
        }

        return $baseline?->{$key};
    }

    protected function dailyVelocity(Collection $snapshots, string $key, ?int $current, int $days): ?int
    {
        if ($current === null) {
            return null;
        }

        $baseline = $this->baselineMetricValue($snapshots, $key, $days);
        if ($baseline === null) {
            return null;
        }

        return (int) round(($current - $baseline) / max($days, 1));
    }

    /**
     * @return array{level:string,label:string,note:string}
     */
    protected function forecastReadiness(Collection $snapshots, ?int $baseline, ?int $current): array
    {
        $snapshotCount = $snapshots->count();

        if ($current === null || $snapshotCount < 14) {
            return [
                'level' => 'low',
                'label' => 'niska',
                'note' => 'Za mało dni w serii, żeby traktować forecast jako sygnał obciążenia.',
            ];
        }

        if ($baseline === null || $baseline <= 0) {
            return [
                'level' => 'low',
                'label' => 'rozruch',
                'note' => 'Okno 30 dni startuje z zera, więc to nadal faza rozruchowa, a nie dojrzały trend.',
            ];
        }

        $baselineRatio = $current > 0 ? ($baseline / max($current, 1)) : 0;

        if ($snapshotCount < 28 || $baselineRatio < 0.2) {
            return [
                'level' => 'medium',
                'label' => 'średnia',
                'note' => 'Trend jest już użyteczny, ale baza odniesienia nadal jest dość mała.',
            ];
        }

        return [
            'level' => 'high',
            'label' => 'wysoka',
            'note' => 'Trend ma już wystarczająco dojrzałą bazę do oceny ryzyka wzrostu.',
        ];
    }

    protected function forecastTone(?int $current, ?int $forecast, string $readinessLevel): string
    {
        $thresholds = $this->thresholds();

        if ($current === null || $forecast === null || $readinessLevel === 'low') {
            return 'neutral';
        }

        if ($forecast <= $current) {
            return 'success';
        }

        $ratio = $current === 0 ? 0 : ($forecast / max($current, 1));

        if ($ratio >= $thresholds['forecast_growth_danger_ratio']) {
            return $readinessLevel === 'medium' ? 'warning' : 'danger';
        }

        if ($ratio >= $thresholds['forecast_growth_warning_ratio']) {
            return 'warning';
        }

        return 'neutral';
    }

    protected function forecastToneLabel(string $tone, string $readinessLevel): string
    {
        if ($readinessLevel === 'low') {
            return 'seria rozruchowa';
        }

        return match ($tone) {
            'danger' => 'szybki wzrost',
            'warning' => 'rośnie',
            'success' => 'stabilnie',
            default => 'prognoza wstępna',
        };
    }

    protected function forecastPenalty(string $tone, string $readinessLevel): int
    {
        if ($readinessLevel === 'low') {
            return 0;
        }

        if ($readinessLevel === 'medium') {
            return match ($tone) {
                'danger' => 4,
                'warning' => 2,
                default => 0,
            };
        }

        return match ($tone) {
            'danger' => 8,
            'warning' => 4,
            default => 0,
        };
    }

    protected function metricDeltaFromSnapshots(Collection $snapshots, string $key, int $days): ?int
    {
        $current = $snapshots->last()?->{$key};
        $baseline = $this->baselineMetricValue($snapshots, $key, $days);

        if ($current === null || $baseline === null) {
            return null;
        }

        return (int) $current - (int) $baseline;
    }

    protected function trendTone(?int $weekDelta, ?int $monthDelta): string
    {
        if ($weekDelta === null || $monthDelta === null) {
            return 'neutral';
        }

        if ($monthDelta <= 0 && $weekDelta <= 0) {
            return 'flat';
        }

        $projectedMonthFromWeek = $weekDelta * 4;
        if ($monthDelta > 0 && $projectedMonthFromWeek >= (int) round($monthDelta * 1.4)) {
            return 'danger';
        }

        if ($monthDelta > 0 && $projectedMonthFromWeek >= (int) round($monthDelta * 1.15)) {
            return 'warning';
        }

        return 'success';
    }

    protected function trendSignalLabel(string $tone): string
    {
        return match ($tone) {
            'danger' => 'Przyspiesza tydzień do tygodnia',
            'warning' => 'Tempo rośnie',
            'flat' => 'Bez nowego sygnału',
            default => 'Stabilny trend',
        };
    }

    /**
     * @param  array<int, array{key:string,label:string,color:string}>  $seriesDefinitions
     * @return array<string, mixed>
     */
    protected function buildLineChart(string $title, string $description, Collection $snapshots, array $seriesDefinitions): array
    {
        $labels = $snapshots
            ->pluck('snapshot_date')
            ->map(fn (mixed $value): string => Carbon::parse((string) $value)->format('d.m'))
            ->all();
        $pointsCount = $snapshots->count();

        $maxValue = max(
            1,
            collect($seriesDefinitions)
                ->flatMap(fn (array $definition) => $snapshots->pluck($definition['key']))
                ->filter(fn (mixed $value): bool => $value !== null)
                ->map(fn (mixed $value): int => (int) $value)
                ->max() ?? 1,
        );

        $series = collect($seriesDefinitions)->map(function (array $definition) use ($snapshots, $maxValue): array {
            $values = $snapshots
                ->pluck($definition['key'])
                ->map(fn (mixed $value): int => (int) ($value ?? 0))
                ->values();
            $first = (int) $values->first();
            $last = (int) $values->last();
            $delta = $last - $first;

            return [
                'label' => $definition['label'],
                'color' => $definition['color'],
                'value' => $this->formatNumber($last),
                'value_raw' => $last,
                'start_value' => $this->formatNumber($first),
                'delta' => $this->formatDelta($first, $last),
                'delta_raw' => $delta,
                'delta_tone' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'),
                'points' => $this->chartPoints($values, $maxValue),
            ];
        })->all();

        return [
            'title' => $title,
            'description' => $description,
            'empty' => $snapshots->isEmpty(),
            'start_label' => $labels[0] ?? null,
            'end_label' => $labels[count($labels) - 1] ?? null,
            'max_label' => $this->formatNumber($maxValue),
            'points_count' => $pointsCount,
            'series' => $series,
        ];
    }

    protected function chartPoints(Collection $values, int $maxValue): string
    {
        if ($values->isEmpty()) {
            return '';
        }

        $chartWidth = 100;
        $chartHeight = 44;
        $count = $values->count();

        return $values->values()->map(function (int $value, int $index) use ($chartWidth, $chartHeight, $count, $maxValue): string {
            $x = $count === 1 ? $chartWidth / 2 : ($index / ($count - 1)) * $chartWidth;
            $y = $chartHeight - (($value / max($maxValue, 1)) * ($chartHeight - 4)) - 2;

            return round($x, 2).','.round($y, 2);
        })->implode(' ');
    }

    protected function formatDelta(int $first, int $last): string
    {
        $delta = $last - $first;

        if ($delta === 0) {
            return 'bez zmiany';
        }

        return ($delta > 0 ? '+' : '').$this->formatNumber($delta);
    }
}
