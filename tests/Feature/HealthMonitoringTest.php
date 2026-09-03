<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

if (! function_exists('putBackupManifest')) {
    function putBackupManifest(string $disk, string $path, string $createdAt): void
    {
        Storage::disk($disk)->put($path, json_encode([
            'connection' => 'sqlite',
            'driver' => 'sqlite',
            'disk' => $disk,
            'backup_path' => str_replace('database-manifests', 'database', str_replace('.json', '.sqlite.gz', $path)),
            'manifest_path' => $path,
            'created_at' => $createdAt,
            'bytes' => 1234,
        ], JSON_PRETTY_PRINT));
    }
}

test('health endpoint returns ok locally and reports database plus backup checks', function () {
    Storage::fake('backups');

    Config::set('backup.disk', 'backups');
    Config::set('health.monitor_backup', false);

    $this->getJson(route('api.v1.health'))
        ->assertOk()
        ->assertJsonPath('data.status', 'ok')
        ->assertJsonPath('data.checks.database.status', 'ok')
        ->assertJsonPath('data.checks.backup.status', 'missing')
        ->assertJsonPath('data.monitoring.backup_enforced', false);
});

test('health endpoint returns degraded when backup monitoring is enabled and backup is stale', function () {
    Storage::fake('backups');

    Config::set('backup.disk', 'backups');
    Config::set('health.monitor_backup', true);
    Config::set('health.backup_max_age_hours', 12);

    putBackupManifest(
        'backups',
        'backups/database-manifests/2026/03/stale.json',
        now()->subHours(30)->utc()->toIso8601String(),
    );

    $this->getJson(route('api.v1.health'))
        ->assertOk()
        ->assertJsonPath('data.status', 'degraded')
        ->assertJsonPath('data.checks.database.status', 'ok')
        ->assertJsonPath('data.checks.backup.status', 'stale')
        ->assertJsonPath('data.monitoring.backup_enforced', true);
});
