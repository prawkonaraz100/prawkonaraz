<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class InfrastructureTelemetryService
{
    public function __construct(
        protected DatabaseBackupService $databaseBackupService,
        protected BackupSettingsService $backupSettingsService,
    ) {}

    /**
     * @return array<string, int|float|string|null>
     */
    public function current(): array
    {
        $databaseDriver = $this->databaseDriver();
        $databasePath = $this->databasePath($databaseDriver);
        $databaseFilePath = $this->databaseFilePath($databaseDriver);
        $databaseSizeBytes = $this->databaseSizeBytes($databaseFilePath);
        $volumeRoot = $this->databaseVolumeRoot($databaseFilePath);
        $volumeTotalBytes = $this->diskSpace($volumeRoot, 'total');
        $volumeFreeBytes = $this->diskSpace($volumeRoot, 'free');
        $volumeUsedBytes = $volumeTotalBytes !== null && $volumeFreeBytes !== null
            ? max($volumeTotalBytes - $volumeFreeBytes, 0)
            : null;
        $volumeUsedRatio = $volumeTotalBytes !== null && $volumeUsedBytes !== null && $volumeTotalBytes > 0
            ? ($volumeUsedBytes / $volumeTotalBytes)
            : null;
        $databaseShareRatio = $volumeTotalBytes !== null && $databaseSizeBytes !== null && $volumeTotalBytes > 0
            ? ($databaseSizeBytes / $volumeTotalBytes)
            : null;
        $cpuCores = $this->cpuCores();
        $cpuLoad = $this->cpuLoad();
        $memory = $this->memory();
        $backup = $this->backup();

        return [
            'database_driver' => $databaseDriver,
            'database_path' => $databasePath,
            'database_volume_root' => $volumeRoot,
            'database_size_bytes' => $databaseSizeBytes,
            'database_volume_total_bytes' => $volumeTotalBytes,
            'database_volume_free_bytes' => $volumeFreeBytes,
            'database_volume_used_bytes' => $volumeUsedBytes,
            'database_volume_used_ratio' => $volumeUsedRatio,
            'database_share_ratio' => $databaseShareRatio,
            'database_latency_ms' => $this->databaseLatencyMs(),
            'cpu_cores' => $cpuCores,
            'cpu_load_1m' => $cpuLoad['load_1m'],
            'cpu_load_5m' => $cpuLoad['load_5m'],
            'cpu_load_ratio_1m' => $cpuLoad['load_1m'] !== null && $cpuCores !== null && $cpuCores > 0
                ? ($cpuLoad['load_1m'] / $cpuCores)
                : null,
            'memory_total_bytes' => $memory['total_bytes'],
            'memory_available_bytes' => $memory['available_bytes'],
            'memory_used_bytes' => $memory['used_bytes'],
            'memory_used_ratio' => $memory['used_ratio'],
            'backup_status' => $backup['status'],
            'backup_last_at' => $backup['last_backup_at'],
            'backup_age_hours' => $backup['age_hours'],
            'backup_latest_bytes' => $backup['latest_bytes'],
            'backup_total_bytes' => $backup['total_bytes'],
            'backup_retained_count' => $backup['retained_count'],
            'backup_disk' => $backup['disk'],
            'backup_message' => $backup['message'],
        ];
    }

    protected function databaseDriver(): string
    {
        return (string) config('database.default', 'sqlite');
    }

    protected function databasePath(string $databaseDriver): ?string
    {
        if ($databaseDriver === 'sqlite') {
            return $this->databaseFilePath($databaseDriver);
        }

        $connection = config("database.connections.{$databaseDriver}");

        if (! is_array($connection)) {
            return null;
        }

        $host = trim((string) ($connection['host'] ?? ''));
        $port = trim((string) ($connection['port'] ?? ''));
        $database = trim((string) ($connection['database'] ?? ''));

        if ($host === '' && $database === '') {
            return $databaseDriver;
        }

        $authority = $host !== ''
            ? ($port !== '' ? "{$host}:{$port}" : $host)
            : 'configured-host';
        $databaseLabel = $database !== '' ? $database : 'configured-database';

        return sprintf('%s://%s/%s', $databaseDriver, $authority, $databaseLabel);
    }

    protected function databaseFilePath(string $databaseDriver): ?string
    {
        if ($databaseDriver !== 'sqlite') {
            return null;
        }

        $path = (string) config('database.connections.sqlite.database');

        if ($path === '' || $path === ':memory:') {
            return null;
        }

        return $path;
    }

    protected function databaseVolumeRoot(?string $databasePath): ?string
    {
        if ($databasePath === null) {
            return null;
        }

        $directory = dirname($databasePath);

        if ($directory === '' || ! is_dir($directory)) {
            return null;
        }

        return $directory;
    }

    protected function databaseSizeBytes(?string $databasePath): ?int
    {
        if ($databasePath === null || ! is_file($databasePath)) {
            return null;
        }

        $bytes = filesize($databasePath);

        return $bytes === false ? null : (int) $bytes;
    }

    protected function diskSpace(?string $path, string $kind): ?int
    {
        if ($path === null) {
            return null;
        }

        $value = match ($kind) {
            'total' => @disk_total_space($path),
            'free' => @disk_free_space($path),
            default => false,
        };

        if ($value === false) {
            return null;
        }

        return (int) round($value);
    }

    protected function databaseLatencyMs(): ?int
    {
        $startedAt = microtime(true);

        try {
            DB::select('SELECT 1');

            return (int) round((microtime(true) - $startedAt) * 1000);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function cpuCores(): ?int
    {
        $cpuInfoPath = '/proc/cpuinfo';

        if (is_file($cpuInfoPath)) {
            $content = @file_get_contents($cpuInfoPath);

            if ($content !== false) {
                preg_match_all('/^processor\s*:/m', $content, $matches);
                $count = count($matches[0]);

                if ($count > 0) {
                    return $count;
                }
            }
        }

        return null;
    }

    /**
     * @return array{load_1m:?float,load_5m:?float}
     */
    protected function cpuLoad(): array
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;

        if (! is_array($load) || $load === []) {
            return [
                'load_1m' => null,
                'load_5m' => null,
            ];
        }

        return [
            'load_1m' => isset($load[0]) ? (float) $load[0] : null,
            'load_5m' => isset($load[1]) ? (float) $load[1] : null,
        ];
    }

    /**
     * @return array{total_bytes:?int,available_bytes:?int,used_bytes:?int,used_ratio:?float}
     */
    protected function memory(): array
    {
        $meminfoPath = '/proc/meminfo';

        if (! is_file($meminfoPath)) {
            return [
                'total_bytes' => null,
                'available_bytes' => null,
                'used_bytes' => null,
                'used_ratio' => null,
            ];
        }

        $content = @file_get_contents($meminfoPath);

        if ($content === false) {
            return [
                'total_bytes' => null,
                'available_bytes' => null,
                'used_bytes' => null,
                'used_ratio' => null,
            ];
        }

        preg_match('/^MemTotal:\s+(\d+)\s+kB/m', $content, $totalMatch);
        preg_match('/^MemAvailable:\s+(\d+)\s+kB/m', $content, $availableMatch);

        $totalBytes = isset($totalMatch[1]) ? ((int) $totalMatch[1] * 1024) : null;
        $availableBytes = isset($availableMatch[1]) ? ((int) $availableMatch[1] * 1024) : null;
        $usedBytes = $totalBytes !== null && $availableBytes !== null
            ? max($totalBytes - $availableBytes, 0)
            : null;
        $usedRatio = $totalBytes !== null && $usedBytes !== null && $totalBytes > 0
            ? ($usedBytes / $totalBytes)
            : null;

        return [
            'total_bytes' => $totalBytes,
            'available_bytes' => $availableBytes,
            'used_bytes' => $usedBytes,
            'used_ratio' => $usedRatio,
        ];
    }

    /**
     * @return array{status:string,last_backup_at:?string,age_hours:mixed,latest_bytes:?int,total_bytes:int,retained_count:int,disk:string,message:?string}
     */
    protected function backup(): array
    {
        $this->backupSettingsService->apply();
        $disk = (string) config('backup.disk', 'local');

        try {
            $health = $this->databaseBackupService->backupHealth();
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'last_backup_at' => null,
                'age_hours' => null,
                'latest_bytes' => null,
                'total_bytes' => 0,
                'retained_count' => 0,
                'disk' => $disk,
                'message' => $exception->getMessage(),
            ];
        }

        if (($health['status'] ?? null) === 'error') {
            return [
                'status' => 'error',
                'last_backup_at' => null,
                'age_hours' => null,
                'latest_bytes' => null,
                'total_bytes' => 0,
                'retained_count' => 0,
                'disk' => $disk,
                'message' => isset($health['message']) ? (string) $health['message'] : 'Backup health check failed.',
            ];
        }

        try {
            $latest = $this->databaseBackupService->latestBackup();
            $recent = $this->databaseBackupService->recentBackups(100);
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'last_backup_at' => isset($health['last_backup_at']) ? (string) $health['last_backup_at'] : null,
                'age_hours' => $health['age_hours'] ?? null,
                'latest_bytes' => null,
                'total_bytes' => 0,
                'retained_count' => 0,
                'disk' => $disk,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'status' => (string) ($health['status'] ?? 'missing'),
            'last_backup_at' => isset($health['last_backup_at']) ? (string) $health['last_backup_at'] : null,
            'age_hours' => $health['age_hours'] ?? null,
            'latest_bytes' => isset($latest['bytes']) ? (int) $latest['bytes'] : null,
            'total_bytes' => (int) $recent->sum(fn (array $backup): int => (int) ($backup['bytes'] ?? 0)),
            'retained_count' => $recent->count(),
            'disk' => $disk,
            'message' => null,
        ];
    }
}
