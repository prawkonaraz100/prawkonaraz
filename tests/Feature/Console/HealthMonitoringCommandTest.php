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

test('ops assert backup fresh passes for a fresh manifest', function () {
    Storage::fake('backups');

    Config::set('backup.disk', 'backups');
    Config::set('health.backup_max_age_hours', 24);

    putBackupManifest(
        'backups',
        'backups/database-manifests/2026/03/fresh.json',
        now()->subHours(2)->utc()->toIso8601String(),
    );

    $this->artisan('ops:assert-backup-fresh')
        ->expectsOutputToContain('Backup jest swiezy.')
        ->assertSuccessful();
});

test('ops assert backup fresh fails for a stale manifest', function () {
    Storage::fake('backups');

    Config::set('backup.disk', 'backups');
    Config::set('health.backup_max_age_hours', 12);

    putBackupManifest(
        'backups',
        'backups/database-manifests/2026/03/stale.json',
        now()->subHours(30)->utc()->toIso8601String(),
    );

    $this->artisan('ops:assert-backup-fresh')
        ->expectsOutputToContain('Backup nie jest swiezy.')
        ->assertFailed();
});

test('ops health report can emit json output', function () {
    Storage::fake('backups');

    Config::set('backup.disk', 'backups');
    Config::set('health.monitor_backup', true);
    Config::set('health.backup_max_age_hours', 24);

    putBackupManifest(
        'backups',
        'backups/database-manifests/2026/03/fresh.json',
        now()->subHours(3)->utc()->toIso8601String(),
    );

    $this->artisan('ops:health-report', ['--json' => true])
        ->expectsOutputToContain('"status": "ok"')
        ->assertSuccessful();
});
