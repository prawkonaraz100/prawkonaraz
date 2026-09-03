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
use Carbon\CarbonInterface;

class MonitoringSnapshotService
{
    public function __construct(
        protected InfrastructureTelemetryService $infrastructureTelemetryService,
    ) {}

    /**
     * @return array<string, int|string|null>
     */
    public function captureDay(CarbonInterface $date): array
    {
        $snapshotDate = $date->copy()->startOfDay();
        $endOfDay = $snapshotDate->copy()->endOfDay();
        $isToday = $snapshotDate->isSameDay(now());
        $telemetry = $isToday ? $this->infrastructureTelemetryService->current() : [];

        $payload = [
            'snapshot_date' => $snapshotDate->toDateString(),
            'database_size_bytes' => $telemetry['database_size_bytes'] ?? null,
            'database_volume_total_bytes' => $telemetry['database_volume_total_bytes'] ?? null,
            'database_volume_free_bytes' => $telemetry['database_volume_free_bytes'] ?? null,
            'database_latency_ms' => $telemetry['database_latency_ms'] ?? null,
            'study_sessions_count' => StudySession::query()
                ->where('created_at', '<=', $endOfDay)
                ->count(),
            'study_session_answers_count' => StudySessionAnswer::query()
                ->where('created_at', '<=', $endOfDay)
                ->count(),
            'question_daily_stats_count' => QuestionDailyStat::query()
                ->whereDate('stats_date', '<=', $snapshotDate->toDateString())
                ->count(),
            'question_monthly_stats_count' => QuestionMonthlyStat::query()
                ->whereDate('stats_month', '<=', $snapshotDate->copy()->startOfMonth()->toDateString())
                ->count(),
            'user_ip_histories_count' => UserIpHistory::query()
                ->where('last_seen_at', '<=', $endOfDay)
                ->count(),
            'content_import_runs_count' => ContentImportRun::query()
                ->where(function ($query) use ($endOfDay): void {
                    $query
                        ->where('completed_at', '<=', $endOfDay)
                        ->orWhere(function ($innerQuery) use ($endOfDay): void {
                            $innerQuery
                                ->whereNull('completed_at')
                                ->where('started_at', '<=', $endOfDay);
                        })
                        ->orWhere(function ($innerQuery) use ($endOfDay): void {
                            $innerQuery
                                ->whereNull('completed_at')
                                ->whereNull('started_at')
                                ->where('created_at', '<=', $endOfDay);
                        });
                })
                ->count(),
            'review_trainer_events_count' => ReviewTrainerEvent::query()
                ->where('occurred_at', '<=', $endOfDay)
                ->count(),
            'review_memory_progress_count' => ReviewMemoryProgress::query()
                ->where('created_at', '<=', $endOfDay)
                ->count(),
            'review_trainer_daily_answers_count' => ReviewTrainerDailyAnswer::query()
                ->where('answered_at', '<=', $endOfDay)
                ->count(),
        ];

        $snapshot = MonitoringSnapshot::query()
            ->whereDate('snapshot_date', $payload['snapshot_date'])
            ->first() ?? new MonitoringSnapshot;

        $snapshot->fill($payload);
        $snapshot->save();

        return $payload;
    }

    /**
     * @return array{days:int, rows:int, last_date:?string}
     */
    public function captureRecentDays(int $days): array
    {
        $days = max($days, 1);
        $rows = 0;
        $lastDate = null;

        foreach (collect(range($days - 1, 0)) as $offset) {
            $snapshot = $this->captureDay(now()->subDays($offset));
            $rows++;
            $lastDate = (string) $snapshot['snapshot_date'];
        }

        return [
            'days' => $days,
            'rows' => $rows,
            'last_date' => $lastDate,
        ];
    }
}
