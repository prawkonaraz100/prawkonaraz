<?php

use App\Support\DatabaseBackupService;
use App\Support\InfrastructureTelemetryService;

test('infrastructure telemetry falls back to error status when backup storage is unavailable', function () {
    $backupService = \Mockery::mock(DatabaseBackupService::class);
    $backupService
        ->shouldReceive('backupHealth')
        ->once()
        ->andThrow(new RuntimeException('Missing storage adapter for backup disk.'));

    app()->instance(DatabaseBackupService::class, $backupService);

    $metrics = app(InfrastructureTelemetryService::class)->current();

    expect($metrics['backup_status'])->toBe('error')
        ->and($metrics['backup_message'])->toContain('Missing storage adapter')
        ->and($metrics['backup_total_bytes'])->toBe(0)
        ->and($metrics['backup_retained_count'])->toBe(0);
});

test('infrastructure telemetry reports configured pgsql target without sqlite file metrics', function () {
    config()->set('database.default', 'pgsql');
    config()->set('database.connections.pgsql.host', 'db.internal');
    config()->set('database.connections.pgsql.port', '5432');
    config()->set('database.connections.pgsql.database', 'prawkobit');

    $backupService = \Mockery::mock(DatabaseBackupService::class);
    $backupService
        ->shouldReceive('backupHealth')
        ->once()
        ->andThrow(new RuntimeException('Missing storage adapter for backup disk.'));

    app()->instance(DatabaseBackupService::class, $backupService);

    $metrics = app(InfrastructureTelemetryService::class)->current();

    expect($metrics['database_driver'])->toBe('pgsql')
        ->and($metrics['database_path'])->toBe('pgsql://db.internal:5432/prawkobit')
        ->and($metrics['database_volume_root'])->toBeNull()
        ->and($metrics['database_size_bytes'])->toBeNull();
});
