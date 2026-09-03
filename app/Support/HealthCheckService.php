<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class HealthCheckService
{
    public function __construct(
        protected DatabaseBackupService $databaseBackupService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $serverTime = now()->utc();
        $databaseCheck = $this->databaseCheck();
        $backupCheck = $this->databaseBackupService->backupHealth();
        $monitorBackup = (bool) config('health.monitor_backup', false);

        $status = match (true) {
            $databaseCheck['status'] !== 'ok' => 'failed',
            $monitorBackup && in_array($backupCheck['status'], ['missing', 'stale', 'error'], true) => 'degraded',
            default => 'ok',
        };

        return [
            'status' => $status,
            'server_time' => $serverTime->toIso8601String(),
            'checks' => [
                'database' => $databaseCheck,
                'backup' => $backupCheck,
            ],
            'monitoring' => [
                'backup_enforced' => $monitorBackup,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function databaseCheck(): array
    {
        $startedAt = microtime(true);

        try {
            DB::select('SELECT 1');

            return [
                'status' => 'ok',
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'message' => $exception->getMessage(),
            ];
        }
    }
}
