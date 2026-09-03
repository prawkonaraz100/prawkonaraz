<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Throwable;

class AdminBackupConsoleService
{
    public function __construct(
        protected DatabaseBackupService $databaseBackupService,
        protected BackupSettingsService $backupSettingsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $settings = $this->backupSettingsService->apply();
        $health = $this->safeHealth();
        $latest = $this->safeLatestBackup();
        $recent = $this->safeRecentBackups(12);
        $tone = $this->statusTone($health['status']);
        $panelState = $this->backupSettingsService->panelState();

        return [
            'status' => [
                'tone' => $tone,
                'headline' => $this->statusHeadline($health['status']),
                'summary' => $this->statusSummary($health, $latest, $recent),
            ],
            'mode' => $this->backupMode(),
            'settings_panel' => $panelState,
            'meta' => [
                ['label' => 'Dysk backupów', 'value' => (string) ($settings['disk'] ?? Config::get('backup.disk', 'local'))],
                ['label' => 'Harmonogram', 'value' => $this->scheduleLabel()],
                ['label' => 'Świeżość', 'value' => $this->formatNumber((int) Config::get('health.backup_max_age_hours', 192)).' h'],
                ['label' => 'Retencja', 'value' => sprintf(
                    'D:%s / T:%s / M:%s',
                    $this->formatNumber((int) Config::get('backup.keep_daily', 7)),
                    $this->formatNumber((int) Config::get('backup.keep_weekly', 4)),
                    $this->formatNumber((int) Config::get('backup.keep_monthly', 3)),
                )],
            ],
            'latest' => [
                'status' => $health['status'],
                'tone' => $tone,
                'age' => $this->formatAgeHours(isset($health['age_hours']) ? (float) $health['age_hours'] : null),
                'created_at' => $this->formatTimestamp($latest['created_at'] ?? $health['last_backup_at'] ?? null),
                'bytes' => isset($latest['bytes']) ? $this->formatBytes((int) $latest['bytes']) : 'brak danych',
                'connection' => (string) ($latest['connection'] ?? 'brak danych'),
                'driver' => (string) ($latest['driver'] ?? 'brak danych'),
                'path' => (string) ($latest['backup_path'] ?? 'brak danych'),
                'manifest' => (string) ($latest['manifest_path'] ?? 'brak danych'),
                'label' => filled($latest['label'] ?? null) ? (string) $latest['label'] : 'brak etykiety',
                'reference' => (string) ($latest['manifest_path'] ?? $latest['backup_path'] ?? ''),
            ],
            'storage' => [
                [
                    'label' => 'Katalog backupów',
                    'value' => (string) Config::get('backup.directory', 'backups/database'),
                    'details' => 'Tu trafiają skompresowane pliki `.gz` z pełnym snapshotem bazy.',
                ],
                [
                    'label' => 'Katalog manifestów',
                    'value' => (string) Config::get('backup.manifest_directory', 'backups/database-manifests'),
                    'details' => 'Manifest trzyma metadane kopii: driver, ścieżkę, checksum i rozmiar.',
                ],
                [
                    'label' => 'Temp lokalny',
                    'value' => (string) Config::get('backup.temporary_directory', storage_path('app/backup-tmp')),
                    'details' => 'To lokalna strefa robocza przed uploadem backupu na właściwy dysk.',
                ],
            ],
            'retention' => [
                [
                    'label' => 'Daily',
                    'window' => $this->formatNumber((int) Config::get('backup.keep_daily', 7)),
                    'details' => 'Najświeższe kopie trzymamy dzień po dniu, żeby łatwo wrócić do ostatnich zmian.',
                ],
                [
                    'label' => 'Weekly',
                    'window' => $this->formatNumber((int) Config::get('backup.keep_weekly', 4)),
                    'details' => 'Starsze tygodniowe punkty odzyskania pozwalają cofnąć się bez trzymania wszystkich kopii.',
                ],
                [
                    'label' => 'Monthly',
                    'window' => $this->formatNumber((int) Config::get('backup.keep_monthly', 3)),
                    'details' => 'Najstarsze backupy zostają jako rzadsze checkpointy długiego okresu.',
                ],
            ],
            'recent' => $recent->map(fn (array $backup): array => [
                'created_at' => $this->formatTimestamp($backup['created_at'] ?? null),
                'age' => $this->formatAgeHours($this->ageHours($backup['created_at'] ?? null)),
                'bytes' => isset($backup['bytes']) ? $this->formatBytes((int) $backup['bytes']) : 'brak danych',
                'connection' => (string) ($backup['connection'] ?? '-'),
                'driver' => (string) ($backup['driver'] ?? '-'),
                'label' => filled($backup['label'] ?? null) ? (string) $backup['label'] : '-',
                'path' => (string) ($backup['backup_path'] ?? '-'),
                'manifest' => (string) ($backup['manifest_path'] ?? '-'),
                'reference' => (string) ($backup['manifest_path'] ?? $backup['backup_path'] ?? ''),
            ])->all(),
            'commands' => [
                [
                    'label' => 'Wymuś backup teraz',
                    'command' => 'php artisan ops:backup-db --label=manual',
                    'details' => 'Tworzy dodatkową kopię poza harmonogramem tygodniowym.',
                ],
                [
                    'label' => 'Sprawdź ostatnie backupy',
                    'command' => 'php artisan ops:list-db-backups --limit=10',
                    'details' => 'Pokazuje ostatnie manifesty i ścieżki do kopii na skonfigurowanym dysku.',
                ],
                [
                    'label' => 'Zweryfikuj świeżość',
                    'command' => 'php artisan ops:assert-backup-fresh',
                    'details' => 'Sprawdza, czy ostatni backup mieści się w bezpiecznym oknie czasu.',
                ],
                [
                    'label' => 'Przywróć backup',
                    'command' => 'php artisan ops:restore-db {reference} --force --backup-current',
                    'details' => 'Restore wymaga jawnego `--force` i robi snapshot aktualnego stanu przed odtworzeniem.',
                ],
            ],
            'issues' => $this->issues($health, $latest, $recent),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function safeHealth(): array
    {
        try {
            return $this->databaseBackupService->backupHealth();
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'last_backup_at' => null,
                'age_hours' => null,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function safeLatestBackup(): ?array
    {
        try {
            return $this->databaseBackupService->latestBackup();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function safeRecentBackups(int $limit): Collection
    {
        try {
            return $this->databaseBackupService->recentBackups($limit);
        } catch (Throwable) {
            return collect();
        }
    }

    protected function statusTone(string $status): string
    {
        return match ($status) {
            'ok' => 'success',
            'stale' => 'warning',
            'missing', 'error' => 'danger',
            default => 'neutral',
        };
    }

    protected function statusHeadline(string $status): string
    {
        return match ($status) {
            'ok' => 'Backupy są aktualne',
            'stale' => 'Backup wymaga odświeżenia',
            'missing' => 'Brakuje aktywnej kopii bazy',
            'error' => 'Repozytorium backupów nie odpowiada',
            default => 'Stan backupów wymaga weryfikacji',
        };
    }

    /**
     * @param  array<string, mixed>  $health
     * @param  array<string, mixed>|null  $latest
     * @param  Collection<int, array<string, mixed>>  $recent
     */
    protected function statusSummary(array $health, ?array $latest, Collection $recent): string
    {
        return match ((string) ($health['status'] ?? 'unknown')) {
            'ok' => sprintf(
                'Ostatnia kopia ma %s, a w retencji widzimy %s backupów.',
                $this->formatAgeHours(isset($health['age_hours']) ? (float) $health['age_hours'] : null),
                $this->formatNumber($recent->count()),
            ),
            'stale' => sprintf(
                'Ostatnia kopia jest starsza niż bezpieczne okno %s h. Trzeba sprawdzić harmonogram albo storage.',
                $this->formatNumber((int) Config::get('health.backup_max_age_hours', 192)),
            ),
            'missing' => 'Nie znaleziono żadnej poprawnej kopii na skonfigurowanym dysku backupów.',
            'error' => sprintf(
                'Panel nie mógł odczytać repozytorium backupów. %s',
                (string) ($health['message'] ?? 'Brak szczegółów błędu.'),
            ),
            default => $latest !== null
                ? 'Backup istnieje, ale stan nie został sklasyfikowany automatycznie.'
                : 'Brak jednoznacznej odpowiedzi z warstwy backupów.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function backupMode(): array
    {
        $panelState = $this->backupSettingsService->panelState();
        $disk = (string) Config::get('backup.disk', 'local');

        return match ($disk) {
            'r2' => [
                'tone' => $panelState['secrets_ready'] ? 'success' : 'warning',
                'label' => 'Cloudflare R2',
                'headline' => 'Backup wychodzi poza serwer aplikacji',
                'summary' => $panelState['secrets_ready']
                    ? 'To jest docelowy tryb produkcyjny. Kopie lecą do zewnętrznego storage zgodnego z S3, więc awaria samego serwera nie zabiera backupów.'
                    : 'Panel jest ustawiony na Cloudflare R2, ale środowisko nadal nie ma kompletu sekretów dostępowych albo konfiguracja bucketu jest niepełna.',
                'details' => [
                    ['label' => 'Endpoint', 'value' => (string) (Config::get('filesystems.disks.r2.endpoint') ?: 'brak konfiguracji')],
                    ['label' => 'Bucket', 'value' => (string) (Config::get('filesystems.disks.r2.bucket') ?: 'brak konfiguracji')],
                    ['label' => 'Zastosowanie', 'value' => 'Rekomendowany tryb na produkcji i na Hetznerze.'],
                ],
            ],
            default => [
                'tone' => 'warning',
                'label' => 'Lokalny dysk',
                'headline' => 'Backup jest zapisywany lokalnie na serwerze',
                'summary' => 'To jest wygodny tryb developerski i testowy. Kopie działają od ręki, ale siedzą na tej samej maszynie co aplikacja, więc nie zastępują zewnętrznego backupu produkcyjnego.',
                'details' => [
                    ['label' => 'Dysk', 'value' => $disk],
                    ['label' => 'Root', 'value' => (string) (Config::get("filesystems.disks.{$disk}.root") ?: storage_path('app/backup-local'))],
                    ['label' => 'Zastosowanie', 'value' => 'Dobry lokalnie. Na produkcji lepiej przełączyć na R2.'],
                ],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $health
     * @param  array<string, mixed>|null  $latest
     * @param  Collection<int, array<string, mixed>>  $recent
     * @return array<int, array{tone:string,title:string,details:string}>
     */
    protected function issues(array $health, ?array $latest, Collection $recent): array
    {
        $items = [];
        $status = (string) ($health['status'] ?? 'unknown');

        if ($status === 'error') {
            $items[] = [
                'tone' => 'danger',
                'title' => 'Storage backupów nie działa',
                'details' => (string) ($health['message'] ?? 'Monitoring nie dostał odpowiedzi z repozytorium backupów.'),
            ];
        }

        if ($status === 'missing') {
            $items[] = [
                'tone' => 'danger',
                'title' => 'Brakuje pierwszej kopii bazy',
                'details' => 'Zanim system przejdzie na produkcyjny rytm tygodniowy, trzeba mieć przynajmniej jeden poprawny backup bazowy.',
            ];
        }

        if ($status === 'stale') {
            $items[] = [
                'tone' => 'warning',
                'title' => 'Ostatni backup jest nieświeży',
                'details' => sprintf(
                    'Ostatnia kopia ma %s i przekroczyła bezpieczne okno %s h.',
                    $this->formatAgeHours(isset($health['age_hours']) ? (float) $health['age_hours'] : null),
                    $this->formatNumber((int) Config::get('health.backup_max_age_hours', 192)),
                ),
            ];
        }

        if ($latest !== null && blank($latest['checksum_sha256'] ?? null)) {
            $items[] = [
                'tone' => 'warning',
                'title' => 'Brakuje checksumy ostatniej kopii',
                'details' => 'Warto pilnować checksumy, bo bez niej trudniej potwierdzić integralność artefaktu backupu.',
            ];
        }

        if ($recent->count() < 2 && $status === 'ok') {
            $items[] = [
                'tone' => 'neutral',
                'title' => 'Retencja jest jeszcze płytka',
                'details' => 'Backup jest świeży, ale historia kopii jest jeszcze krótka. Po kilku cyklach panel pokaże pełniejszy obraz retencji.',
            ];
        }

        if ($items === []) {
            $items[] = [
                'tone' => 'success',
                'title' => 'Nie ma otwartych problemów z backupami',
                'details' => 'Harmonogram, retencja i ostatnia kopia wyglądają spójnie z tygodniowym modelem zabezpieczenia.',
            ];
        }

        return $items;
    }

    protected function scheduleLabel(): string
    {
        $frequency = (string) Config::get('backup.schedule.frequency', 'weekly');
        $time = (string) Config::get('backup.schedule.at', '02:15');

        if ($frequency === 'weekly') {
            $day = $this->dayOfWeekLabel((int) Config::get('backup.schedule.day_of_week', 0));

            return sprintf('co tydzień · %s · %s', $day, $time);
        }

        return sprintf('codziennie · %s', $time);
    }

    protected function dayOfWeekLabel(int $day): string
    {
        return match ($day) {
            1 => 'poniedziałek',
            2 => 'wtorek',
            3 => 'środa',
            4 => 'czwartek',
            5 => 'piątek',
            6 => 'sobota',
            default => 'niedziela',
        };
    }

    protected function formatTimestamp(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return 'brak danych';
        }

        return CarbonImmutable::parse($value)->setTimezone(config('app.timezone', 'Europe/Warsaw'))->format('d.m.Y H:i');
    }

    protected function ageHours(mixed $value): ?float
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return round(CarbonImmutable::parse($value)->diffInSeconds(now()->utc()) / 3600, 1);
    }

    protected function formatAgeHours(?float $hours): string
    {
        if ($hours === null) {
            return 'brak danych';
        }

        return number_format($hours, 1, ',', ' ').' h';
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $value = (float) $bytes;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return number_format($value, $unitIndex === 0 ? 0 : 2, ',', ' ').' '.$units[$unitIndex];
    }

    protected function formatNumber(int $value): string
    {
        return number_format($value, 0, ',', ' ');
    }
}
