<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class BackupSettingsService
{
    public const STORE_KEY = 'backup.runtime';

    public function __construct(
        protected SystemSettingsStore $systemSettingsStore,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        $stored = $this->systemSettingsStore->get(self::STORE_KEY, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        return $this->normalize(array_replace_recursive($this->defaults(), $stored));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(array $input): array
    {
        $settings = $this->normalize(array_replace_recursive($this->current(), $input));

        $this->systemSettingsStore->put(self::STORE_KEY, $settings);
        $this->apply();

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function reset(): array
    {
        $this->systemSettingsStore->forget(self::STORE_KEY);

        return $this->apply();
    }

    /**
     * @param  array<string, mixed>|null  $override
     * @return array<string, mixed>
     */
    public function apply(?array $override = null): array
    {
        $settings = $override === null
            ? $this->current()
            : $this->normalize(array_replace_recursive($this->current(), $override));

        Config::set('backup.disk', $settings['disk']);
        Config::set('backup.schedule.frequency', $settings['schedule']['frequency']);
        Config::set('backup.schedule.day_of_week', $settings['schedule']['day_of_week']);
        Config::set('backup.schedule.at', $settings['schedule']['at']);
        Config::set('backup.keep_daily', $settings['retention']['daily']);
        Config::set('backup.keep_weekly', $settings['retention']['weekly']);
        Config::set('backup.keep_monthly', $settings['retention']['monthly']);

        Config::set('filesystems.disks.r2.bucket', $settings['r2']['bucket']);
        Config::set('filesystems.disks.r2.endpoint', $settings['r2']['endpoint']);
        Config::set('filesystems.disks.r2.region', $settings['r2']['region']);
        Config::set('filesystems.disks.r2.url', $settings['r2']['url']);
        Config::set('filesystems.disks.r2.use_path_style_endpoint', $settings['r2']['use_path_style_endpoint']);

        app('filesystem')->forgetDisk(['r2', 'backup_local', 'local']);

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function panelState(): array
    {
        $settings = $this->current();
        $secrets = $this->secretsState();
        $hasOverrides = $this->systemSettingsStore->has(self::STORE_KEY);

        return [
            'settings' => $settings,
            'has_overrides' => $hasOverrides,
            'source_label' => $hasOverrides ? 'Panel admina' : 'Domyślna konfiguracja',
            'source_copy' => $hasOverrides
                ? 'Te ustawienia są zapisane w bazie i od razu sterują backupami, schedulerem oraz monitoringiem.'
                : 'Panel korzysta teraz z konfiguracji domyślnej z `.env` i `config/backup.php`.',
            'secrets' => $secrets,
            'secrets_ready' => $secrets['access_key']['configured'] && $secrets['secret_key']['configured'],
            'notes' => [
                'Sekrety R2 nie są trzymane w panelu admina. Nadal wchodzą z `.env` na serwerze.',
                'Zmiana harmonogramu i retencji działa bez ręcznego restartu scheduler odpala aplikację na świeżo przy każdym cyklu.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $override
     * @return array{tone:string,headline:string,message:string}
     */
    public function probe(?array $override = null): array
    {
        return $this->withTemporaryConfig($override, function (array $settings): array {
            $disk = (string) $settings['disk'];

            if ($disk === 'r2') {
                $secrets = $this->secretsState();

                if (! $secrets['access_key']['configured'] || ! $secrets['secret_key']['configured']) {
                    return [
                        'tone' => 'danger',
                        'headline' => 'Brakuje sekretów R2 w środowisku',
                        'message' => 'Ustaw `R2_ACCESS_KEY_ID` i `R2_SECRET_ACCESS_KEY` w `.env` produkcji, zanim przełączysz backupy na Cloudflare R2.',
                    ];
                }

                if ($settings['r2']['bucket'] === '' || $settings['r2']['endpoint'] === '') {
                    return [
                        'tone' => 'danger',
                        'headline' => 'Konfiguracja R2 jest niepełna',
                        'message' => 'Bucket i endpoint są wymagane, żeby panel mógł połączyć się z Cloudflare R2.',
                    ];
                }
            }

            $probePath = trim((string) config('backup.manifest_directory', 'backups/database-manifests'), '/')
                .'/.probe/'.now()->utc()->format('YmdHis').'-'.Str::lower((string) Str::ulid()).'.json';

            try {
                $diskInstance = Storage::disk($disk);
                $diskInstance->put($probePath, json_encode([
                    'probe' => true,
                    'at' => now()->utc()->toIso8601String(),
                ], JSON_UNESCAPED_SLASHES));

                if (! $diskInstance->exists($probePath)) {
                    return [
                        'tone' => 'danger',
                        'headline' => 'Storage nie potwierdził zapisu',
                        'message' => 'Połączenie odpowiedziało, ale plik testowy nie jest widoczny na skonfigurowanym dysku backupów.',
                    ];
                }

                $diskInstance->delete($probePath);

                return [
                    'tone' => 'success',
                    'headline' => 'Połączenie ze storage działa',
                    'message' => $disk === 'r2'
                        ? 'Panel poprawnie zapisał i usunął plik testowy w Cloudflare R2.'
                        : 'Panel poprawnie zapisał i usunął lokalny plik testowy na dysku backupów.',
                ];
            } catch (Throwable $exception) {
                return [
                    'tone' => 'danger',
                    'headline' => 'Test połączenia nie przeszedł',
                    'message' => $exception->getMessage(),
                ];
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'disk' => (string) config('backup.disk', app()->environment('production') ? 'r2' : 'backup_local'),
            'r2' => [
                'bucket' => (string) config('filesystems.disks.r2.bucket', ''),
                'endpoint' => (string) config('filesystems.disks.r2.endpoint', ''),
                'region' => (string) config('filesystems.disks.r2.region', 'auto'),
                'url' => (string) config('filesystems.disks.r2.url', ''),
                'use_path_style_endpoint' => (bool) config('filesystems.disks.r2.use_path_style_endpoint', true),
            ],
            'schedule' => [
                'frequency' => (string) config('backup.schedule.frequency', 'weekly'),
                'day_of_week' => (int) config('backup.schedule.day_of_week', 0),
                'at' => (string) config('backup.schedule.at', '02:15'),
            ],
            'retention' => [
                'daily' => (int) config('backup.keep_daily', 7),
                'weekly' => (int) config('backup.keep_weekly', 4),
                'monthly' => (int) config('backup.keep_monthly', 3),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function normalize(array $settings): array
    {
        $disk = in_array(($settings['disk'] ?? null), ['backup_local', 'r2'], true)
            ? (string) $settings['disk']
            : (string) $this->defaults()['disk'];
        $frequency = in_array(($settings['schedule']['frequency'] ?? null), ['daily', 'weekly'], true)
            ? (string) $settings['schedule']['frequency']
            : 'weekly';
        $time = trim((string) ($settings['schedule']['at'] ?? '02:15'));

        if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
            $time = '02:15';
        }

        return [
            'disk' => $disk,
            'r2' => [
                'bucket' => trim((string) ($settings['r2']['bucket'] ?? '')),
                'endpoint' => trim((string) ($settings['r2']['endpoint'] ?? '')),
                'region' => trim((string) ($settings['r2']['region'] ?? 'auto')) ?: 'auto',
                'url' => trim((string) ($settings['r2']['url'] ?? '')),
                'use_path_style_endpoint' => (bool) ($settings['r2']['use_path_style_endpoint'] ?? true),
            ],
            'schedule' => [
                'frequency' => $frequency,
                'day_of_week' => min(max((int) ($settings['schedule']['day_of_week'] ?? 0), 0), 6),
                'at' => $time,
            ],
            'retention' => [
                'daily' => max((int) ($settings['retention']['daily'] ?? 7), 0),
                'weekly' => max((int) ($settings['retention']['weekly'] ?? 4), 0),
                'monthly' => max((int) ($settings['retention']['monthly'] ?? 3), 0),
            ],
        ];
    }

    /**
     * @return array<string, array{configured:bool,label:string,value:string}>
     */
    protected function secretsState(): array
    {
        $accessKey = (string) config('filesystems.disks.r2.key', '');
        $secretKey = (string) config('filesystems.disks.r2.secret', '');

        return [
            'access_key' => [
                'configured' => $accessKey !== '',
                'label' => 'R2 access key',
                'value' => $accessKey !== '' ? 'ustawiony w środowisku' : 'brak w `.env`',
            ],
            'secret_key' => [
                'configured' => $secretKey !== '',
                'label' => 'R2 secret key',
                'value' => $secretKey !== '' ? 'ustawiony w środowisku' : 'brak w `.env`',
            ],
        ];
    }

    /**
     * @template TReturn
     *
     * @param  array<string, mixed>|null  $override
     * @param  callable(array<string, mixed>): TReturn  $callback
     * @return TReturn
     */
    protected function withTemporaryConfig(?array $override, callable $callback): mixed
    {
        $originalBackup = config('backup');
        $originalR2 = config('filesystems.disks.r2');

        try {
            $settings = $this->apply($override);

            return $callback($settings);
        } finally {
            Config::set('backup', $originalBackup);
            Config::set('filesystems.disks.r2', $originalR2);
            app('filesystem')->forgetDisk(['r2', 'backup_local', 'local']);
        }
    }
}
