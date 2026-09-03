<?php

namespace App\Support;

use App\Filament\Pages\GovImportGuide;
use App\Filament\Pages\DataMonitoring;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\ContentImportRuns\ContentImportRunResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\ContentImportRun;
use App\Models\Question;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class AdminDashboardService
{
    public function __construct(
        protected HealthCheckService $healthCheckService,
        protected QuestionIntegrityAuditService $questionIntegrityAuditService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $latestImport = ContentImportRun::query()->latest('created_at')->first();
        $latestImportStatus = $this->resolveImportStatus($latestImport);
        $integrityAudit = $this->questionIntegrityAuditService->loadLatestReport();
        $integrityStatus = $this->questionIntegrityAuditService->resolveStatusMeta($integrityAudit);
        $health = $this->healthCheckService->report();

        $missingPrimaryMediaCount = Question::query()
            ->missingPrimaryMedia()
            ->count();
        $inactiveQuestionsCount = Question::query()
            ->where('is_active', false)
            ->count();
        $questionsReadyCount = Question::query()
            ->readyForDelivery()
            ->count();
        $questionsTotal = Question::query()->count();
        $auditTodayCount = AuditLog::query()
            ->whereDate('created_at', today())
            ->count();
        $importsTodayCount = ContentImportRun::query()
            ->whereDate('created_at', today())
            ->count();

        return [
            'quickLinks' => [
                [
                    'label' => 'Baza pytań',
                    'description' => 'Treść, odpowiedzi, publikacja i media w jednym miejscu.',
                    'url' => QuestionResource::getUrl(panel: 'admin'),
                ],
                [
                    'label' => 'Import gov.pl',
                    'description' => 'Instrukcja operatorska do stagingu, dry-runu i pełnego wsadu.',
                    'url' => GovImportGuide::getUrl(panel: 'admin'),
                ],
                [
                    'label' => 'Importy',
                    'description' => 'Status ostatnich przebiegów, błędy i wynik audytów.',
                    'url' => ContentImportRunResource::getUrl(panel: 'admin'),
                ],
                [
                    'label' => 'Monitoring danych',
                    'description' => 'Retencja, rozmiar historii, agregaty i ostatnie joby utrzymaniowe.',
                    'url' => DataMonitoring::getUrl(panel: 'admin'),
                ],
                [
                    'label' => 'Dziennik audytu',
                    'description' => 'Ostatnie działania operatorów i systemu.',
                    'url' => AuditLogResource::getUrl(panel: 'admin'),
                ],
                [
                    'label' => 'Użytkownicy',
                    'description' => 'Dostępy, weryfikacja kont i administracja użytkownikami.',
                    'url' => UserResource::getUrl(panel: 'admin'),
                ],
            ],
            'snapshot' => [
                'headline' => $missingPrimaryMediaCount > 0
                    ? "{$missingPrimaryMediaCount} pytań do poprawki"
                    : 'Publikacja bez blokad',
                'summary' => $missingPrimaryMediaCount > 0
                    ? 'Najpierw uzupełnij braki w publikacji i sprawdź wynik ostatniego importu.'
                    : 'Nie ma teraz krytycznych blokad publikacji. Możesz przejść do importów albo kontroli bazy pytań.',
                'metrics' => [
                    [
                        'label' => 'Pytania gotowe',
                        'value' => $this->formatNumber($questionsReadyCount),
                    ],
                    [
                        'label' => 'Braki publikacji',
                        'value' => $this->formatNumber($missingPrimaryMediaCount),
                    ],
                    [
                        'label' => 'Nieaktywne',
                        'value' => $this->formatNumber($inactiveQuestionsCount),
                    ],
                    [
                        'label' => 'Importy dziś',
                        'value' => $this->formatNumber($importsTodayCount),
                    ],
                    [
                        'label' => 'Akcje dziś',
                        'value' => $this->formatNumber($auditTodayCount),
                    ],
                    [
                        'label' => 'Użytkownicy',
                        'value' => $this->formatNumber(User::query()->count()),
                    ],
                ],
                'questions_total' => $this->formatNumber($questionsTotal),
            ],
            'attentionItems' => $this->buildAttentionItems(
                $latestImport,
                $latestImportStatus,
                $integrityStatus,
                $missingPrimaryMediaCount,
                $inactiveQuestionsCount,
            ),
            'latestImport' => [
                'label' => $latestImportStatus['label'],
                'tone' => $latestImportStatus['tone'],
                'description' => $latestImportStatus['description'],
                'meta' => $latestImport === null
                    ? 'Nie ma jeszcze żadnego przebiegu importu.'
                    : trim(implode(' · ', array_filter([
                        filled($latestImport->identifier) ? $latestImport->identifier : null,
                        $latestImport->created_at?->format('d.m.Y H:i'),
                    ]))),
                'details' => $latestImport === null
                    ? 'Uruchom pierwszy przebieg i sprawdź tu jego wynik.'
                    : sprintf(
                        '%s pytań, %s mediów, %s błędów, %s ostrzeżeń.',
                        $this->formatNumber($latestImport->questions_total ?? 0),
                        $this->formatNumber($latestImport->media_total ?? 0),
                        $this->formatNumber($latestImport->errors_count ?? 0),
                        $this->formatNumber($latestImport->warnings_count ?? 0),
                    ),
                'url' => $latestImport
                    ? ContentImportRunResource::getUrl('view', ['record' => $latestImport], panel: 'admin')
                    : ContentImportRunResource::getUrl(panel: 'admin'),
            ],
            'systemChecks' => [
                [
                    'label' => 'Baza danych',
                    'value' => $this->formatHealthStatus($health['checks']['database']['status'] ?? 'unknown'),
                    'details' => $this->formatDatabaseCheckDetails($health['checks']['database'] ?? []),
                ],
                [
                    'label' => 'Backup',
                    'value' => $this->formatBackupStatus($health['checks']['backup']['status'] ?? 'unknown'),
                    'details' => $this->formatBackupCheckDetails($health['checks']['backup'] ?? []),
                ],
                [
                    'label' => 'Audyt bazy',
                    'value' => $integrityStatus['label'],
                    'details' => $integrityStatus['description'],
                ],
            ],
            'recentImports' => ContentImportRun::query()
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function (ContentImportRun $importRun): array {
                    $status = $this->resolveImportStatus($importRun);

                    return [
                        'identifier' => filled($importRun->identifier) ? $importRun->identifier : 'Bez identyfikatora',
                        'kind' => $importRun->kind,
                        'status_label' => $status['label'],
                        'created_at' => $this->formatDate($importRun->created_at),
                        'summary' => sprintf(
                            '%s pytań · %s mediów',
                            $this->formatNumber($importRun->questions_total ?? 0),
                            $this->formatNumber($importRun->media_total ?? 0),
                        ),
                        'details' => sprintf(
                            '%s błędów · %s ostrzeżeń',
                            $this->formatNumber($importRun->errors_count ?? 0),
                            $this->formatNumber($importRun->warnings_count ?? 0),
                        ),
                        'url' => ContentImportRunResource::getUrl('view', ['record' => $importRun], panel: 'admin'),
                    ];
                })
                ->all(),
            'recentAuditLogs' => AuditLog::query()
                ->with('actorUser:id,name,email')
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->map(function (AuditLog $auditLog): array {
                    $actor = $auditLog->actorUser?->name
                        ?: $auditLog->actorUser?->email
                        ?: 'System';

                    return [
                        'action' => $auditLog->action,
                        'entity' => "{$auditLog->entity_type} #{$auditLog->entity_id}",
                        'actor' => $actor,
                        'created_at' => $this->formatDate($auditLog->created_at),
                        'url' => AuditLogResource::getUrl('view', ['record' => $auditLog], panel: 'admin'),
                    ];
                })
                ->all(),
        ];
    }

    /**
     * @param  array{label:string,tone:string,description:string}  $latestImportStatus
     * @param  array{state:string,label:string,color:string,description:string}  $integrityStatus
     * @return array<int, array<string, string>>
     */
    protected function buildAttentionItems(
        ?ContentImportRun $latestImport,
        array $latestImportStatus,
        array $integrityStatus,
        int $missingPrimaryMediaCount,
        int $inactiveQuestionsCount,
    ): array {
        $items = [];

        if ($missingPrimaryMediaCount > 0) {
            $items[] = [
                'label' => 'Brak głównego medium',
                'value' => $this->formatNumber($missingPrimaryMediaCount),
                'description' => 'Te pytania nie są gotowe do publikacji, dopóki nie dostaną pełnego assetu.',
                'url' => QuestionResource::getUrl(panel: 'admin'),
                'url_label' => 'Otwórz bazę pytań',
            ];
        }

        if ($latestImport !== null && ($latestImport->status === 'failed' || (int) $latestImport->errors_count > 0)) {
            $items[] = [
                'label' => 'Ostatni import wymaga sprawdzenia',
                'value' => $latestImportStatus['label'],
                'description' => $latestImportStatus['description'],
                'url' => ContentImportRunResource::getUrl('view', ['record' => $latestImport], panel: 'admin'),
                'url_label' => 'Pokaż przebieg',
            ];
        } elseif ($latestImport !== null && (int) $latestImport->warnings_count > 0) {
            $items[] = [
                'label' => 'Ostatni import ma ostrzeżenia',
                'value' => $this->formatNumber($latestImport->warnings_count),
                'description' => 'Przebieg zakończył się bez błędów krytycznych, ale warto przejrzeć warningi i podsumowanie.',
                'url' => ContentImportRunResource::getUrl('view', ['record' => $latestImport], panel: 'admin'),
                'url_label' => 'Sprawdź warningi',
            ];
        }

        if (in_array($integrityStatus['state'], ['critical', 'review'], true)) {
            $items[] = [
                'label' => 'Audyt integralności wymaga review',
                'value' => $integrityStatus['label'],
                'description' => $integrityStatus['description'],
                'url' => ContentImportRunResource::getUrl(panel: 'admin'),
                'url_label' => 'Przejdź do importów',
            ];
        }

        if ($inactiveQuestionsCount > 0) {
            $items[] = [
                'label' => 'Nieaktywne pytania',
                'value' => $this->formatNumber($inactiveQuestionsCount),
                'description' => 'Sprawdź, czy to świadome wyłączenia, czy rzeczy do odblokowania po imporcie.',
                'url' => QuestionResource::getUrl(panel: 'admin'),
                'url_label' => 'Przejdź do pytań',
            ];
        }

        if ($items === []) {
            $items[] = [
                'label' => 'Brak pilnych blokad',
                'value' => 'OK',
                'description' => 'Importy, publikacja i audyt bazy nie pokazują teraz niczego, co wymaga natychmiastowego działania.',
                'url' => QuestionResource::getUrl(panel: 'admin'),
                'url_label' => 'Przejdź do bazy pytań',
            ];
        }

        return $items;
    }

    /**
     * @return array{label:string,tone:string,description:string}
     */
    public function resolveImportStatus(?ContentImportRun $importRun): array
    {
        if ($importRun === null) {
            return [
                'label' => 'Brak danych',
                'tone' => 'neutral',
                'description' => 'Nie ma jeszcze żadnego przebiegu importu.',
            ];
        }

        if ($importRun->status === 'failed' || (int) $importRun->errors_count > 0) {
            return [
                'label' => 'Błąd',
                'tone' => 'critical',
                'description' => 'Ostatni przebieg zakończył się błędami i wymaga ręcznego sprawdzenia.',
            ];
        }

        if ((int) $importRun->warnings_count > 0) {
            return [
                'label' => 'Ostrzeżenia',
                'tone' => 'warning',
                'description' => 'Przebieg zakończył się bez błędów krytycznych, ale zostały warningi do review.',
            ];
        }

        if ($importRun->status === 'ok') {
            return [
                'label' => 'OK',
                'tone' => 'ok',
                'description' => 'Ostatni przebieg zakończył się poprawnie.',
            ];
        }

        return [
            'label' => ucfirst((string) $importRun->status),
            'tone' => 'neutral',
            'description' => 'Status przebiegu nie wymaga teraz dodatkowego oznaczenia.',
        ];
    }

    protected function formatNumber(int $value): string
    {
        return number_format($value, 0, ',', ' ');
    }

    protected function formatDate(?CarbonInterface $date): string
    {
        return $date?->format('d.m.Y H:i') ?? '-';
    }

    /**
     * @param  array<string, mixed>  $check
     */
    protected function formatDatabaseCheckDetails(array $check): string
    {
        $latency = isset($check['latency_ms']) ? (int) $check['latency_ms'] : null;

        if (($check['status'] ?? null) === 'ok') {
            return $latency !== null
                ? "Połączenie działa. Ostatni ping: {$latency} ms."
                : 'Połączenie z bazą działa poprawnie.';
        }

        return filled($check['message'] ?? null)
            ? (string) $check['message']
            : 'Sprawdź połączenie z bazą danych.';
    }

    /**
     * @param  array<string, mixed>  $check
     */
    protected function formatBackupCheckDetails(array $check): string
    {
        $status = (string) ($check['status'] ?? 'unknown');
        $lastBackupAt = isset($check['last_backup_at']) && filled($check['last_backup_at'])
            ? $this->formatDate(CarbonImmutable::parse((string) $check['last_backup_at']))
            : null;

        return match ($status) {
            'ok' => $lastBackupAt
                ? "Ostatni backup: {$lastBackupAt}."
                : 'Backup jest aktualny.',
            'stale' => $lastBackupAt
                ? "Backup jest przeterminowany. Ostatni: {$lastBackupAt}."
                : 'Backup jest przeterminowany.',
            'missing' => 'Nie znaleziono żadnego backupu.',
            'error' => (string) ($check['message'] ?? 'Nie udało się sprawdzić backupu.'),
            default => 'Status backupu jest nieznany.',
        };
    }

    protected function formatHealthStatus(string $status): string
    {
        return match ($status) {
            'ok' => 'OK',
            'failed', 'error' => 'Błąd',
            'degraded' => 'Ostrzeżenie',
            default => 'Brak danych',
        };
    }

    protected function formatBackupStatus(string $status): string
    {
        return match ($status) {
            'ok' => 'OK',
            'stale' => 'Przeterminowany',
            'missing' => 'Brak',
            'error' => 'Błąd',
            default => 'Brak danych',
        };
    }
}
