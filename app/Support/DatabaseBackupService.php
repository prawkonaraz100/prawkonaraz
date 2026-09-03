<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public function __construct(
        protected BackupSettingsService $backupSettingsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function backup(?string $connectionName = null, bool $prune = true, ?string $label = null): array
    {
        $this->backupSettingsService->apply();
        $connectionName = $this->resolveConnectionName($connectionName);
        $driver = $this->connectionDriver($connectionName);
        $createdAt = CarbonImmutable::now('UTC');
        $tmpDirectory = $this->ensureTempDirectory();
        $baseFilename = $this->buildBaseFilename($createdAt, $connectionName, $driver, $label);
        $rawPath = $tmpDirectory.DIRECTORY_SEPARATOR.$baseFilename.'.'.$this->rawExtension($driver);
        $compressedPath = $rawPath.'.gz';

        try {
            $this->createRawBackup($connectionName, $driver, $rawPath);

            if (! File::exists($rawPath) || (int) File::size($rawPath) <= 0) {
                throw new RuntimeException('Backup file was not created or is empty.');
            }

            $this->gzipFile($rawPath, $compressedPath);

            if (! File::exists($compressedPath) || (int) File::size($compressedPath) <= 0) {
                throw new RuntimeException('Compressed backup file was not created or is empty.');
            }

            $backupPath = $this->backupStoragePath($createdAt, basename($compressedPath));
            $manifestPath = $this->manifestStoragePath($createdAt, $baseFilename.'.json');

            $manifest = [
                'id' => (string) Str::ulid(),
                'connection' => $connectionName,
                'driver' => $driver,
                'disk' => $this->diskName(),
                'backup_path' => $backupPath,
                'manifest_path' => $manifestPath,
                'compression' => 'gzip',
                'created_at' => $createdAt->toIso8601String(),
                'bytes' => (int) File::size($compressedPath),
                'checksum_sha256' => hash_file('sha256', $compressedPath) ?: null,
                'label' => $label,
            ];

            $this->putFile($this->diskName(), $backupPath, $compressedPath);
            $this->putJson($this->diskName(), $manifestPath, $manifest);

            if ($prune) {
                $this->pruneBackups($this->diskName());
            }

            return $manifest;
        } finally {
            File::delete([$rawPath, $compressedPath]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function restore(string $reference, ?string $connectionName = null, bool $backupCurrent = false): array
    {
        $this->backupSettingsService->apply();
        $manifest = $this->resolveReference($reference);
        $connectionName = $this->resolveConnectionName($connectionName ?? ($manifest['connection'] ?? null));
        $driver = $this->connectionDriver($connectionName);

        if (($manifest['driver'] ?? $driver) !== $driver) {
            throw new RuntimeException('Backup driver does not match the selected database connection.');
        }

        $snapshot = null;

        if ($backupCurrent) {
            $snapshot = $this->backup($connectionName, prune: false, label: 'pre-restore');
        }

        $tmpDirectory = $this->ensureTempDirectory();
        $rawPath = $tmpDirectory.DIRECTORY_SEPARATOR.'restore-'.Str::ulid().'.'.$this->rawExtension($driver);
        $compressedPath = $rawPath.'.gz';

        try {
            $this->downloadBackup($manifest, $compressedPath);
            $this->gunzipFile($compressedPath, $rawPath);
            $this->restoreRawBackup($connectionName, $driver, $rawPath);

            return [
                'connection' => $connectionName,
                'driver' => $driver,
                'restored_at' => now()->utc()->toIso8601String(),
                'restored_from' => $manifest,
                'snapshot' => $snapshot,
            ];
        } finally {
            File::delete([$rawPath, $compressedPath]);
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recentBackups(int $limit = 10): Collection
    {
        $this->backupSettingsService->apply();

        return $this->loadManifests($this->diskName())
            ->take(max($limit, 1))
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestBackup(): ?array
    {
        $this->backupSettingsService->apply();

        return $this->loadManifests($this->diskName())->first();
    }

    /**
     * @return array{stream:resource,filename:string,content_type:string|null,content_length:int|null,disk:string,backup_path:string}
     */
    public function downloadPayload(string $reference): array
    {
        $this->backupSettingsService->apply();
        $manifest = $this->resolveReference($reference);
        $diskName = (string) ($manifest['disk'] ?? $this->diskName());
        $backupPath = (string) ($manifest['backup_path'] ?? '');

        if ($backupPath === '') {
            throw new RuntimeException('Backup reference does not point to a readable artifact.');
        }

        $disk = Storage::disk($diskName);
        $stream = $disk->readStream($backupPath);

        if ($stream === false) {
            throw new RuntimeException('Unable to open backup stream from storage.');
        }

        return [
            'stream' => $stream,
            'filename' => basename($backupPath),
            'content_type' => $this->safeMimeType($diskName, $backupPath),
            'content_length' => $this->safeSize($diskName, $backupPath),
            'disk' => $diskName,
            'backup_path' => $backupPath,
        ];
    }

    /**
     * @return array{disk:string,deleted:array<int,string>,missing:array<int,string>,backup_path:string|null,manifest_path:string|null}
     */
    public function deleteBackup(string $reference): array
    {
        $this->backupSettingsService->apply();
        $manifest = $this->resolveReference($reference);
        $diskName = (string) ($manifest['disk'] ?? $this->diskName());
        $disk = Storage::disk($diskName);
        $deleted = [];
        $missing = [];

        $paths = collect([
            (string) ($manifest['backup_path'] ?? ''),
            (string) ($manifest['manifest_path'] ?? ''),
        ])->filter()->unique()->values();

        if ($paths->isEmpty()) {
            throw new RuntimeException('Backup reference does not contain removable paths.');
        }

        foreach ($paths as $path) {
            if (! $disk->exists($path)) {
                $missing[] = $path;

                continue;
            }

            $result = $disk->delete($path);

            if ($result === false || $disk->exists($path)) {
                throw new RuntimeException(sprintf(
                    'Could not delete backup artifact [%s] from disk [%s].',
                    $path,
                    $diskName,
                ));
            }

            $deleted[] = $path;
        }

        return [
            'disk' => $diskName,
            'deleted' => $deleted,
            'missing' => $missing,
            'backup_path' => $manifest['backup_path'] ?? null,
            'manifest_path' => $manifest['manifest_path'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function backupHealth(?int $maxAgeHours = null): array
    {
        $this->backupSettingsService->apply();
        $maxAgeHours ??= (int) Config::get('health.backup_max_age_hours', 36);

        try {
            $backup = $this->latestBackup();

            if ($backup === null || blank($backup['created_at'] ?? null)) {
                return [
                    'status' => 'missing',
                    'max_age_hours' => $maxAgeHours,
                    'last_backup_at' => null,
                    'age_hours' => null,
                ];
            }

            $createdAt = CarbonImmutable::parse((string) $backup['created_at']);
            $ageHours = round($createdAt->diffInSeconds(now()->utc()) / 3600, 1);

            return [
                'status' => $ageHours <= $maxAgeHours ? 'ok' : 'stale',
                'max_age_hours' => $maxAgeHours,
                'last_backup_at' => $createdAt->toIso8601String(),
                'age_hours' => $ageHours,
                'backup_path' => $backup['backup_path'] ?? null,
                'manifest_path' => $backup['manifest_path'] ?? null,
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'max_age_hours' => $maxAgeHours,
                'last_backup_at' => null,
                'age_hours' => null,
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function resolveConnectionName(?string $connectionName): string
    {
        return $connectionName ?: (string) Config::get('database.default');
    }

    protected function connectionDriver(string $connectionName): string
    {
        $driver = (string) Config::get("database.connections.{$connectionName}.driver", '');

        if (! in_array($driver, ['sqlite', 'pgsql'], true)) {
            throw new RuntimeException("Database driver [{$driver}] is not supported for backup/restore.");
        }

        return $driver;
    }

    protected function diskName(): string
    {
        return (string) Config::get('backup.disk', 'local');
    }

    protected function ensureTempDirectory(): string
    {
        $directory = (string) Config::get('backup.temporary_directory', storage_path('app/backup-tmp'));
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    protected function buildBaseFilename(
        CarbonImmutable $createdAt,
        string $connectionName,
        string $driver,
        ?string $label,
    ): string {
        $appName = Str::slug((string) Config::get('app.name', 'drive-iq'));
        $parts = [
            $createdAt->format('Ymd-His'),
            $appName,
            Str::slug($connectionName),
            $driver,
        ];

        if (filled($label)) {
            $parts[] = Str::slug((string) $label);
        }

        return implode('-', array_filter($parts));
    }

    protected function rawExtension(string $driver): string
    {
        return $driver === 'sqlite' ? 'sqlite' : 'sql';
    }

    protected function backupStoragePath(CarbonImmutable $createdAt, string $filename): string
    {
        return trim((string) Config::get('backup.directory', 'backups/database'), '/')
            .'/'.$createdAt->format('Y/m').'/'.$filename;
    }

    protected function manifestStoragePath(CarbonImmutable $createdAt, string $filename): string
    {
        return trim((string) Config::get('backup.manifest_directory', 'backups/database-manifests'), '/')
            .'/'.$createdAt->format('Y/m').'/'.$filename;
    }

    protected function createRawBackup(string $connectionName, string $driver, string $rawPath): void
    {
        match ($driver) {
            'sqlite' => $this->createSqliteBackup($connectionName, $rawPath),
            'pgsql' => $this->createPgsqlBackup($connectionName, $rawPath),
        };
    }

    protected function restoreRawBackup(string $connectionName, string $driver, string $rawPath): void
    {
        match ($driver) {
            'sqlite' => $this->restoreSqliteBackup($connectionName, $rawPath),
            'pgsql' => $this->restorePgsqlBackup($connectionName, $rawPath),
        };
    }

    protected function createSqliteBackup(string $connectionName, string $rawPath): void
    {
        File::delete($rawPath);

        DB::connection($connectionName)->statement(
            sprintf("VACUUM main INTO '%s'", str_replace("'", "''", $rawPath))
        );
    }

    protected function restoreSqliteBackup(string $connectionName, string $rawPath): void
    {
        $database = (string) Config::get("database.connections.{$connectionName}.database", '');

        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('SQLite restore requires a file-based database path.');
        }

        DB::purge($connectionName);
        File::ensureDirectoryExists(dirname($database));
        File::copy($rawPath, $database);
        DB::reconnect($connectionName);
    }

    protected function createPgsqlBackup(string $connectionName, string $rawPath): void
    {
        $config = (array) Config::get("database.connections.{$connectionName}", []);

        $command = array_values(array_filter([
            (string) Config::get('backup.pgsql.pg_dump_binary', 'pg_dump'),
            filled($config['host'] ?? null) ? '--host='.$config['host'] : null,
            filled($config['port'] ?? null) ? '--port='.$config['port'] : null,
            filled($config['username'] ?? null) ? '--username='.$config['username'] : null,
            '--dbname='.(string) ($config['database'] ?? ''),
            '--clean',
            '--if-exists',
            '--no-owner',
            '--no-privileges',
            '--encoding=UTF8',
            '--file='.$rawPath,
        ]));

        $this->runProcess($command, $this->pgsqlEnv($config));
    }

    protected function restorePgsqlBackup(string $connectionName, string $rawPath): void
    {
        $config = (array) Config::get("database.connections.{$connectionName}", []);

        DB::purge($connectionName);

        $command = array_values(array_filter([
            (string) Config::get('backup.pgsql.psql_binary', 'psql'),
            filled($config['host'] ?? null) ? '--host='.$config['host'] : null,
            filled($config['port'] ?? null) ? '--port='.$config['port'] : null,
            filled($config['username'] ?? null) ? '--username='.$config['username'] : null,
            '--dbname='.(string) ($config['database'] ?? ''),
            '--single-transaction',
            '-v',
            'ON_ERROR_STOP=1',
            '--file='.$rawPath,
        ]));

        $this->runProcess($command, $this->pgsqlEnv($config));

        DB::reconnect($connectionName);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    protected function pgsqlEnv(array $config): array
    {
        $env = [];

        if (array_key_exists('password', $config) && $config['password'] !== null) {
            $env['PGPASSWORD'] = (string) $config['password'];
        }

        if (filled($config['sslmode'] ?? null)) {
            $env['PGSSLMODE'] = (string) $config['sslmode'];
        }

        return $env;
    }

    /**
     * @param  array<int, string>  $command
     * @param  array<string, string>  $env
     */
    protected function runProcess(array $command, array $env = []): void
    {
        $process = new Process($command, base_path(), array_replace($_ENV, $env));
        $process->setTimeout((int) Config::get('backup.pgsql.timeout_seconds', 300));
        $process->run();

        if (! $process->isSuccessful()) {
            $message = trim($process->getErrorOutput() ?: $process->getOutput());

            throw new RuntimeException($message !== '' ? $message : 'Database backup process failed.');
        }
    }

    protected function gzipFile(string $source, string $target): void
    {
        $input = fopen($source, 'rb');
        $output = gzopen($target, 'wb9');

        if ($input === false || $output === false) {
            throw new RuntimeException('Unable to open files for gzip compression.');
        }

        try {
            while (! feof($input)) {
                $chunk = fread($input, 8192);

                if ($chunk === false) {
                    throw new RuntimeException('Unable to read raw backup during compression.');
                }

                gzwrite($output, $chunk);
            }
        } finally {
            fclose($input);
            gzclose($output);
        }
    }

    protected function gunzipFile(string $source, string $target): void
    {
        $input = gzopen($source, 'rb');
        $output = fopen($target, 'wb');

        if ($input === false || $output === false) {
            throw new RuntimeException('Unable to open files for gzip extraction.');
        }

        try {
            while (! gzeof($input)) {
                $chunk = gzread($input, 8192);

                if ($chunk === false) {
                    throw new RuntimeException('Unable to read compressed backup during extraction.');
                }

                fwrite($output, $chunk);
            }
        } finally {
            gzclose($input);
            fclose($output);
        }
    }

    protected function putFile(string $diskName, string $path, string $localPath): void
    {
        $stream = fopen($localPath, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Unable to open backup file for upload.');
        }

        try {
            $stored = Storage::disk($diskName)->put($path, $stream);
        } finally {
            fclose($stream);
        }

        if ($stored !== true || ! Storage::disk($diskName)->exists($path)) {
            throw new RuntimeException(sprintf(
                'Backup file could not be written to disk [%s] at path [%s].',
                $diskName,
                $path,
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function putJson(string $diskName, string $path, array $payload): void
    {
        $stored = Storage::disk($diskName)->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($stored !== true || ! Storage::disk($diskName)->exists($path)) {
            throw new RuntimeException(sprintf(
                'Backup manifest could not be written to disk [%s] at path [%s].',
                $diskName,
                $path,
            ));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveReference(string $reference): array
    {
        $disk = Storage::disk($this->diskName());

        if (! $disk->exists($reference)) {
            throw new RuntimeException("Backup reference [{$reference}] was not found on disk [{$this->diskName()}].");
        }

        if (str_ends_with(strtolower($reference), '.json')) {
            $payload = json_decode((string) $disk->get($reference), true);

            if (! is_array($payload) || blank($payload['backup_path'] ?? null)) {
                throw new RuntimeException('Backup manifest is invalid.');
            }

            return $payload;
        }

        return [
            'connection' => $this->resolveConnectionName(null),
            'driver' => $this->connectionDriver($this->resolveConnectionName(null)),
            'disk' => $this->diskName(),
            'backup_path' => $reference,
            'manifest_path' => null,
            'created_at' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function downloadBackup(array $manifest, string $targetPath): void
    {
        $diskName = (string) ($manifest['disk'] ?? $this->diskName());
        $stream = Storage::disk($diskName)->readStream((string) $manifest['backup_path']);

        if ($stream === false) {
            throw new RuntimeException('Unable to read backup file from storage.');
        }

        $output = fopen($targetPath, 'wb');

        if ($output === false) {
            fclose($stream);

            throw new RuntimeException('Unable to create temporary restore file.');
        }

        try {
            stream_copy_to_stream($stream, $output);
        } finally {
            fclose($stream);
            fclose($output);
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function loadManifests(string $diskName): Collection
    {
        $directory = trim((string) Config::get('backup.manifest_directory', 'backups/database-manifests'), '/');
        $disk = Storage::disk($diskName);

        return collect($disk->allFiles($directory))
            ->filter(fn (string $path): bool => str_ends_with(strtolower($path), '.json'))
            ->map(function (string $path) use ($disk): ?array {
                $payload = json_decode((string) $disk->get($path), true);

                if (! is_array($payload)) {
                    return null;
                }

                $payload['manifest_path'] ??= $path;

                return $payload;
            })
            ->filter()
            ->sortByDesc(fn (array $manifest) => (string) ($manifest['created_at'] ?? ''))
            ->values();
    }

    protected function safeMimeType(string $diskName, string $path): ?string
    {
        try {
            return Storage::disk($diskName)->mimeType($path) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function safeSize(string $diskName, string $path): ?int
    {
        try {
            $size = Storage::disk($diskName)->size($path);

            return is_numeric($size) ? (int) $size : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function pruneBackups(string $diskName): void
    {
        $keepDaily = max((int) Config::get('backup.keep_daily', 7), 0);
        $keepWeekly = max((int) Config::get('backup.keep_weekly', 4), 0);
        $keepMonthly = max((int) Config::get('backup.keep_monthly', 3), 0);

        $manifests = $this->loadManifests($diskName);
        $keepManifestPaths = [];
        $keepBackupPaths = [];
        $daily = [];
        $weekly = [];
        $monthly = [];

        foreach ($manifests as $manifest) {
            if (blank($manifest['created_at'] ?? null) || blank($manifest['manifest_path'] ?? null)) {
                continue;
            }

            $createdAt = CarbonImmutable::parse((string) $manifest['created_at']);
            $dayKey = $createdAt->format('Y-m-d');
            $weekKey = $createdAt->format('o-W');
            $monthKey = $createdAt->format('Y-m');

            $shouldKeep = false;

            if (count($daily) < $keepDaily && ! isset($daily[$dayKey])) {
                $daily[$dayKey] = true;
                $shouldKeep = true;
            } elseif (count($weekly) < $keepWeekly && ! isset($weekly[$weekKey])) {
                $weekly[$weekKey] = true;
                $shouldKeep = true;
            } elseif (count($monthly) < $keepMonthly && ! isset($monthly[$monthKey])) {
                $monthly[$monthKey] = true;
                $shouldKeep = true;
            }

            if ($shouldKeep) {
                $keepManifestPaths[] = (string) $manifest['manifest_path'];
                $keepBackupPaths[] = (string) ($manifest['backup_path'] ?? '');
            }
        }

        $disk = Storage::disk($diskName);

        foreach ($manifests as $manifest) {
            $manifestPath = (string) ($manifest['manifest_path'] ?? '');
            $backupPath = (string) ($manifest['backup_path'] ?? '');

            if (in_array($manifestPath, $keepManifestPaths, true)) {
                continue;
            }

            if ($manifestPath !== '' && $disk->exists($manifestPath)) {
                $disk->delete($manifestPath);
            }

            if ($backupPath !== '' && ! in_array($backupPath, $keepBackupPaths, true) && $disk->exists($backupPath)) {
                $disk->delete($backupPath);
            }
        }
    }
}
