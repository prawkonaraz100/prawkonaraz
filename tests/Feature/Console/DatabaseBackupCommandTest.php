<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

function makeSqliteConnection(string $connectionName): string
{
    $databasePath = storage_path("framework/testing/{$connectionName}.sqlite");

    File::ensureDirectoryExists(dirname($databasePath));
    File::delete($databasePath);
    File::put($databasePath, '');

    Config::set("database.connections.{$connectionName}", [
        'driver' => 'sqlite',
        'database' => $databasePath,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => null,
        'journal_mode' => null,
        'synchronous' => null,
        'transaction_mode' => 'DEFERRED',
    ]);

    DB::purge($connectionName);
    DB::connection($connectionName)->statement('CREATE TABLE notes (id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT NOT NULL)');

    return $databasePath;
}

afterEach(function (): void {
    foreach (['ops_backup', 'ops_restore'] as $connectionName) {
        DB::purge($connectionName);

        $databasePath = storage_path("framework/testing/{$connectionName}.sqlite");

        if (File::exists($databasePath)) {
            File::delete($databasePath);
        }
    }
});

test('ops backup command creates a compressed backup manifest and prunes stale backups', function () {
    Storage::fake('backups');

    Config::set('backup.disk', 'backups');
    Config::set('backup.keep_daily', 1);
    Config::set('backup.keep_weekly', 0);
    Config::set('backup.keep_monthly', 0);

    $connectionName = 'ops_backup';
    makeSqliteConnection($connectionName);

    DB::connection($connectionName)->table('notes')->insert([
        ['label' => 'alpha'],
        ['label' => 'beta'],
    ]);

    $oldBackupPath = 'backups/database/2026/01/old-backup.sqlite.gz';
    $oldManifestPath = 'backups/database-manifests/2026/01/old-backup.json';

    Storage::disk('backups')->put($oldBackupPath, gzencode('obsolete backup'));
    Storage::disk('backups')->put($oldManifestPath, json_encode([
        'connection' => $connectionName,
        'driver' => 'sqlite',
        'disk' => 'backups',
        'backup_path' => $oldBackupPath,
        'manifest_path' => $oldManifestPath,
        'created_at' => now()->subDays(20)->toIso8601String(),
        'bytes' => 15,
    ], JSON_PRETTY_PRINT));

    $this->artisan('ops:backup-db', [
        '--connection' => $connectionName,
    ])
        ->expectsOutputToContain('Backup bazy zakonczony sukcesem.')
        ->assertSuccessful();

    $backupFiles = collect(Storage::disk('backups')->allFiles('backups/database'));
    $manifestFiles = collect(Storage::disk('backups')->allFiles('backups/database-manifests'));

    expect($backupFiles)->toHaveCount(1);
    expect($manifestFiles)->toHaveCount(1);
    expect(Storage::disk('backups')->exists($oldBackupPath))->toBeFalse();
    expect(Storage::disk('backups')->exists($oldManifestPath))->toBeFalse();

    $manifest = json_decode((string) Storage::disk('backups')->get($manifestFiles->first()), true);

    expect($manifest)->toBeArray();
    expect($manifest['connection'])->toBe($connectionName);
    expect($manifest['driver'])->toBe('sqlite');
    expect($manifest['bytes'])->toBeGreaterThan(0);
    expect($manifest['backup_path'])->toBe($backupFiles->first());
    expect(Storage::disk('backups')->size($manifest['backup_path']))->toBeGreaterThan(0);
});

test('ops restore command restores sqlite database from a backup and can snapshot the current state', function () {
    Storage::fake('backups');

    Config::set('backup.disk', 'backups');
    Config::set('backup.keep_daily', 7);
    Config::set('backup.keep_weekly', 4);
    Config::set('backup.keep_monthly', 3);

    $connectionName = 'ops_restore';
    makeSqliteConnection($connectionName);

    DB::connection($connectionName)->table('notes')->insert([
        ['label' => 'before restore'],
    ]);

    $this->artisan('ops:backup-db', [
        '--connection' => $connectionName,
    ])->assertSuccessful();

    $manifestPath = collect(Storage::disk('backups')->allFiles('backups/database-manifests'))
        ->first();

    DB::connection($connectionName)->table('notes')->truncate();
    DB::connection($connectionName)->table('notes')->insert([
        ['label' => 'after mutation'],
    ]);

    $this->artisan('ops:restore-db', [
        'reference' => $manifestPath,
        '--connection' => $connectionName,
        '--backup-current' => true,
        '--force' => true,
    ])
        ->expectsOutputToContain('Restore zakonczony sukcesem.')
        ->assertSuccessful();

    DB::purge($connectionName);

    $labels = DB::connection($connectionName)
        ->table('notes')
        ->orderBy('id')
        ->pluck('label')
        ->all();

    expect($labels)->toBe(['before restore']);

    $manifestFiles = collect(Storage::disk('backups')->allFiles('backups/database-manifests'));

    expect($manifestFiles->count())->toBe(2);
});

test('ops restore command requires explicit force flag', function () {
    Storage::fake('backups');

    Config::set('backup.disk', 'backups');

    $reference = 'backups/database-manifests/missing.json';
    Storage::disk('backups')->put($reference, json_encode([
        'backup_path' => 'backups/database/missing.sqlite.gz',
    ]));

    $this->artisan('ops:restore-db', [
        'reference' => $reference,
    ])
        ->expectsOutputToContain('Restore wymaga opcji --force.')
        ->assertFailed();
});
