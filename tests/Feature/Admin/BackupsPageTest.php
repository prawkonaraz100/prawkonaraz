<?php

use App\Filament\Pages\Backups;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\AdminBackupConsoleService;
use App\Support\BackupSettingsService;
use App\Support\DatabaseBackupService;
use Livewire\Livewire;

test('admin users can access the backups page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(Backups::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Backupy', false)
        ->assertSee('Stan backupów', false)
        ->assertSee('Konfiguracja backupów', false)
        ->assertSee('Tryb backupów', false)
        ->assertSee('Lokalny dysk', false)
        ->assertSee('Ostatnia kopia', false)
        ->assertSee('Ostatnie backupy', false)
        ->assertSee('Komendy operatorskie', false)
        ->assertSee('Retencja i harmonogram', false)
        ->assertDontSee('@js(', false);
});

test('non admin users cannot access the backups page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(Backups::getUrl(panel: 'admin'))
        ->assertForbidden();
});

test('admin users see fallback on backups page when backup service fails', function () {
    $admin = User::factory()->admin()->create();

    app()->bind(AdminBackupConsoleService::class, fn () => new class
    {
        public function build(): array
        {
            throw new RuntimeException('Testowa awaria backupów.');
        }
    });

    $this->actingAs($admin)
        ->get(Backups::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Panel backupów jest chwilowo niedostępny', false)
        ->assertSee('Awaria odczytu', false)
        ->assertSee('Testowa awaria backupów.', false);
});

test('admin users can trigger a manual backup from the backups page', function () {
    $admin = User::factory()->admin()->create();
    $console = new class
    {
        public int $calls = 0;

        public function build(): array
        {
            $this->calls++;

            return [
                'status' => [
                    'tone' => 'success',
                    'headline' => 'Backupy są aktualne',
                    'summary' => 'Test.',
                ],
                'mode' => [
                    'tone' => 'warning',
                    'label' => 'Lokalny dysk',
                    'headline' => 'Test',
                    'summary' => 'Test',
                    'details' => [
                        ['label' => 'Dysk', 'value' => 'backup_local'],
                    ],
                ],
                'meta' => [],
                'latest' => [
                    'status' => 'ok',
                    'tone' => 'success',
                    'age' => '0,0 h',
                    'created_at' => '03.04.2026 20:10',
                    'bytes' => '1 MB',
                    'connection' => 'sqlite',
                    'driver' => 'sqlite',
                    'path' => 'backup.gz',
                    'manifest' => 'manifest.json',
                    'label' => 'manual',
                    'reference' => 'manifest.json',
                ],
                'storage' => [],
                'retention' => [],
                'recent' => [],
                'commands' => [],
                'issues' => [],
            ];
        }
    };

    $mock = \Mockery::mock(DatabaseBackupService::class);
    $mock->shouldReceive('backup')
        ->once()
        ->with(null, true, 'manual-admin-panel')
        ->andReturn([
            'disk' => 'backup_local',
        ]);

    app()->instance(DatabaseBackupService::class, $mock);
    app()->instance(AdminBackupConsoleService::class, $console);

    $this->actingAs($admin);

    $component = Livewire::test(Backups::class)
        ->assertActionExists('runBackup')
        ->callAction('runBackup');

    $component->assertRedirect();

    expect($console->calls)->toBeGreaterThanOrEqual(2);
});

test('admin users can save backup runtime settings from the backups page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(Backups::class)
        ->set('settingsData.disk', 'r2')
        ->set('settingsData.r2.bucket', 'panel-backups')
        ->set('settingsData.r2.endpoint', 'https://example.r2.cloudflarestorage.com')
        ->set('settingsData.r2.region', 'auto')
        ->set('settingsData.r2.url', 'https://cdn.example.com/backups')
        ->set('settingsData.r2.use_path_style_endpoint', true)
        ->set('settingsData.schedule.frequency', 'daily')
        ->set('settingsData.schedule.day_of_week', 3)
        ->set('settingsData.schedule.at', '03:45')
        ->set('settingsData.retention.daily', 14)
        ->set('settingsData.retention.weekly', 8)
        ->set('settingsData.retention.monthly', 6)
        ->call('saveSettings')
        ->assertHasNoErrors();

    $settings = app(BackupSettingsService::class)->current();

    expect($settings['disk'])->toBe('r2')
        ->and($settings['r2']['bucket'])->toBe('panel-backups')
        ->and($settings['r2']['endpoint'])->toBe('https://example.r2.cloudflarestorage.com')
        ->and($settings['schedule']['frequency'])->toBe('daily')
        ->and($settings['schedule']['at'])->toBe('03:45')
        ->and($settings['retention']['daily'])->toBe(14)
        ->and(SystemSetting::query()->where('key', BackupSettingsService::STORE_KEY)->exists())->toBeTrue();
});

test('admin users can reset backup runtime settings to defaults', function () {
    $admin = User::factory()->admin()->create();

    app(BackupSettingsService::class)->save([
        'disk' => 'r2',
        'r2' => [
            'bucket' => 'panel-backups',
            'endpoint' => 'https://example.r2.cloudflarestorage.com',
            'region' => 'auto',
            'url' => '',
            'use_path_style_endpoint' => true,
        ],
        'schedule' => [
            'frequency' => 'daily',
            'day_of_week' => 2,
            'at' => '04:20',
        ],
        'retention' => [
            'daily' => 9,
            'weekly' => 5,
            'monthly' => 4,
        ],
    ]);

    $this->actingAs($admin);

    Livewire::test(Backups::class)
        ->call('resetSettings')
        ->assertHasNoErrors();

    $settings = app(BackupSettingsService::class)->current();

    expect($settings['disk'])->toBe(config('backup.disk'))
        ->and(SystemSetting::query()->where('key', BackupSettingsService::STORE_KEY)->exists())->toBeFalse();
});

test('admin users can delete a backup from the backups page', function () {
    $admin = User::factory()->admin()->create();
    $console = new class
    {
        public int $calls = 0;

        public function build(): array
        {
            $this->calls++;

            return [
                'status' => [
                    'tone' => 'success',
                    'headline' => 'Backupy są aktualne',
                    'summary' => 'Test.',
                ],
                'mode' => [
                    'tone' => 'warning',
                    'label' => 'Lokalny dysk',
                    'headline' => 'Test',
                    'summary' => 'Test',
                    'details' => [
                        ['label' => 'Dysk', 'value' => 'backup_local'],
                    ],
                ],
                'meta' => [],
                'latest' => [
                    'status' => 'ok',
                    'tone' => 'success',
                    'age' => '0,0 h',
                    'created_at' => '03.04.2026 20:10',
                    'bytes' => '1 MB',
                    'connection' => 'sqlite',
                    'driver' => 'sqlite',
                    'path' => 'backup.gz',
                    'manifest' => 'manifest.json',
                    'label' => 'manual',
                    'reference' => 'manifest.json',
                ],
                'storage' => [],
                'retention' => [],
                'recent' => [
                    [
                        'created_at' => '03.04.2026 20:10',
                        'age' => '0,0 h',
                        'bytes' => '1 MB',
                        'connection' => 'sqlite',
                        'driver' => 'sqlite',
                        'label' => 'manual',
                        'path' => 'backup.gz',
                        'manifest' => 'manifest.json',
                        'reference' => 'manifest.json',
                    ],
                ],
                'commands' => [],
                'issues' => [],
            ];
        }
    };

    $mock = \Mockery::mock(DatabaseBackupService::class);
    $mock->shouldReceive('deleteBackup')
        ->once()
        ->with('manifest.json')
        ->andReturn([
            'disk' => 'backup_local',
            'deleted' => ['backup.gz', 'manifest.json'],
            'missing' => [],
            'backup_path' => 'backup.gz',
            'manifest_path' => 'manifest.json',
        ]);

    app()->instance(DatabaseBackupService::class, $mock);
    app()->instance(AdminBackupConsoleService::class, $console);

    $this->actingAs($admin);

    Livewire::test(Backups::class)
        ->call('deleteBackup', 'manifest.json');

    expect($console->calls)->toBeGreaterThanOrEqual(2);
});

test('admin users can download a backup artifact', function () {
    $admin = User::factory()->admin()->create();
    $stream = fopen('php://temp', 'rb+');
    fwrite($stream, 'backup-body');
    rewind($stream);

    $mock = \Mockery::mock(DatabaseBackupService::class);
    $mock->shouldReceive('downloadPayload')
        ->once()
        ->with('manifest.json')
        ->andReturn([
            'stream' => $stream,
            'filename' => 'backup.sqlite.gz',
            'content_type' => 'application/gzip',
            'content_length' => 11,
            'disk' => 'backup_local',
            'backup_path' => 'backups/database/backup.sqlite.gz',
        ]);

    app()->instance(DatabaseBackupService::class, $mock);

    $this->actingAs($admin)
        ->get(route('admin.backups.download', ['reference' => 'manifest.json']))
        ->assertOk()
        ->assertHeader('content-type', 'application/gzip')
        ->assertHeader('content-disposition');
});
