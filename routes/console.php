<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicOverride;
use App\Models\ReviewMemoryProgress;
use App\Models\ReviewTrainerDailyAnswer;
use App\Models\ReviewTrainerEvent;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserIpHistory;
use App\Support\BackupSettingsService;
use App\Support\CatalogManifestImportService;
use App\Support\CatalogManifestSeriesImportService;
use App\Support\ContentImportRunRecorder;
use App\Support\DatabaseBackupService;
use App\Support\FriendInvitationService;
use App\Support\GovernmentCatalogBatchBuilder;
use App\Support\GovernmentCatalogBatchSeriesBuilder;
use App\Support\HealthCheckService;
use App\Support\MonitoringSnapshotService;
use App\Support\PerformanceSmokeService;
use App\Support\QuestionAnswerDailyStatsAggregator;
use App\Support\QuestionCatalogImporter;
use App\Support\QuestionDailyStatsAggregator;
use App\Support\QuestionDeliveryReadinessService;
use App\Support\QuestionExplanationDraftApplyService;
use App\Support\QuestionExplanationDraftExceptionResolutionService;
use App\Support\QuestionExplanationDraftStagingService;
use App\Support\QuestionExplanationManualResolutionService;
use App\Support\QuestionExplanationPtOverlapBackfillService;
use App\Support\QuestionExplanationPtPromptMatchBackfillService;
use App\Support\QuestionIntegrityAuditService;
use App\Support\QuestionMonthlyStatsRollupService;
use App\Support\QuestionTopicAssigner;
use App\Support\QuestionTopicClassifier;
use App\Support\QuestionTopicOverridePackageBuilder;
use App\Support\QuestionTopicReclassifierAuditService;
use App\Support\SmokeDataProvisioner;
use App\Support\SmokeTestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:make-admin {email : Adres email administratora} {--name= : Imie i nazwisko administratora} {--password= : Haslo administratora}', function () {
    $email = strtolower((string) $this->argument('email'));
    $name = (string) ($this->option('name') ?: Str::of($email)->before('@')->replace(['.', '-', '_'], ' ')->headline());
    $generatedPassword = null;
    $password = (string) $this->option('password');

    if ($password === '') {
        $generatedPassword = Str::password(16);
        $password = $generatedPassword;
    }

    $user = User::firstOrNew(['email' => $email]);
    $user->fill([
        'name' => $name,
        // User model already casts "password" => "hashed", so passing a raw value
        // avoids double-hashing and keeps the generated admin account loggable.
        'password' => $password,
        'is_admin' => true,
        'role' => User::ROLE_ADMIN,
    ]);
    $user->email_verified_at ??= now();
    $user->save();

    $this->info(sprintf('Administrator %s jest gotowy.', $email));

    if ($generatedPassword !== null) {
        $this->warn(sprintf('Wygenerowane haslo: %s', $generatedPassword));
        $this->comment('Zmien je po pierwszym logowaniu lub uruchom komende ponownie z opcja --password.');
    }
})->purpose('Create or update an administrator account for the Filament panel.');

Artisan::command('ops:copy-sqlite-to-pgsql {path=database/database.sqlite : Sciezka do zrodlowej bazy SQLite}', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->error('Ta komenda zaklada aktywna docelowa baze PostgreSQL.');

        return self::FAILURE;
    }

    $inputPath = (string) $this->argument('path');
    $resolvedPath = File::exists($inputPath) ? $inputPath : base_path($inputPath);

    if (! File::exists($resolvedPath)) {
        $this->error(sprintf('Nie znaleziono bazy SQLite: %s', $inputPath));

        return self::FAILURE;
    }

    config([
        'database.connections.sqlite_import' => [
            'driver' => 'sqlite',
            'database' => $resolvedPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    DB::purge('sqlite_import');

    $source = DB::connection('sqlite_import');
    $target = DB::connection();

    $tables = [
        'users',
        'license_categories',
        'question_topics',
        'questions',
        'question_media',
        'study_sessions',
        'study_session_answers',
        'review_trainer_daily_answers',
        'review_trainer_events',
        'review_memory_progress',
        'user_profiles',
        'user_question_progress',
        'user_ip_histories',
        'audit_logs',
        'content_import_runs',
        'question_daily_stats',
        'question_monthly_stats',
        'monitoring_snapshots',
        'system_settings',
    ];

    $tablesWithIdentity = [
        'users',
        'license_categories',
        'question_topics',
        'questions',
        'question_media',
        'study_sessions',
        'study_session_answers',
        'review_trainer_daily_answers',
        'review_trainer_events',
        'review_memory_progress',
        'user_ip_histories',
        'audit_logs',
        'content_import_runs',
        'question_daily_stats',
        'question_monthly_stats',
        'monitoring_snapshots',
        'system_settings',
    ];

    $this->warn('Czyszcze docelowe tabele aplikacyjne i kopiuję dane ze SQLite do PostgreSQL.');

    $quotedTables = implode(', ', array_map(
        static fn (string $table): string => sprintf('"%s"', str_replace('"', '""', $table)),
        $tables,
    ));

    $target->statement(sprintf('TRUNCATE TABLE %s RESTART IDENTITY CASCADE', $quotedTables));

    $columnTypesFor = static function (string $table) use ($target): array {
        $rows = $target->select(
            <<<'SQL'
                SELECT column_name, data_type
                FROM information_schema.columns
                WHERE table_schema = current_schema()
                  AND table_name = ?
            SQL,
            [$table],
        );

        $map = [];

        foreach ($rows as $row) {
            $map[$row->column_name] = $row->data_type;
        }

        return $map;
    };

    $normalizeRow = static function (array $row, array $columnTypes): array {
        foreach ($row as $column => $value) {
            if ($value === null) {
                continue;
            }

            $type = $columnTypes[$column] ?? null;

            if ($type === 'boolean') {
                $row[$column] = match (true) {
                    is_bool($value) => $value,
                    is_int($value) => $value === 1,
                    is_string($value) => in_array(strtolower($value), ['1', 'true', 't', 'yes', 'y'], true),
                    default => (bool) $value,
                };

                continue;
            }

            if (in_array($type, ['json', 'jsonb'], true) && (is_array($value) || is_object($value))) {
                $row[$column] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $row;
    };

    foreach ($tables as $table) {
        if (! $source->getSchemaBuilder()->hasTable($table) || ! Schema::hasTable($table)) {
            $this->comment(sprintf('Pomijam %s - tabela nie istnieje po jednej ze stron.', $table));

            continue;
        }

        $columnTypes = $columnTypesFor($table);
        $rows = $source->table($table)->get()->map(static fn (object $row): array => (array) $row)->all();

        if ($rows === []) {
            $this->line(sprintf('%s: 0', $table));

            continue;
        }

        $normalized = array_map(static fn (array $row): array => $normalizeRow($row, $columnTypes), $rows);

        foreach (array_chunk($normalized, 500) as $chunk) {
            $target->table($table)->insert($chunk);
        }

        $this->info(sprintf('%s: %d', $table, count($normalized)));
    }

    foreach ($tablesWithIdentity as $table) {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            continue;
        }

        $target->statement(
            sprintf(
                <<<'SQL'
                    SELECT setval(
                        pg_get_serial_sequence('%1$s', 'id'),
                        COALESCE((SELECT MAX(id) FROM %1$s), 1),
                        EXISTS(SELECT 1 FROM %1$s)
                    )
                SQL,
                $table,
            ),
        );
    }

    DB::purge('sqlite_import');

    $this->info('Kopiowanie SQLite -> PostgreSQL zakonczone.');
})->purpose('Copy local application data from SQLite into the active PostgreSQL connection.');

Artisan::command('catalog:import-json {path : Sciezka do pliku JSON z katalogiem pytan} {--dry-run : Zweryfikuj wsad bez zapisu do bazy} {--skip-sitemap : Pomin odswiezanie sitemap po niepublicznym lub lokalnym imporcie} {--report= : Sciezka do pliku raportu JSON}', function (QuestionCatalogImporter $importer, ContentImportRunRecorder $contentImportRunRecorder) {
    $inputPath = (string) $this->argument('path');
    $resolvedPath = File::exists($inputPath) ? $inputPath : base_path($inputPath);
    $dryRun = (bool) $this->option('dry-run');

    if (! File::exists($resolvedPath)) {
        $this->error(sprintf('Nie znaleziono pliku: %s', $inputPath));

        return self::FAILURE;
    }

    $payload = json_decode((string) File::get($resolvedPath), true);

    if (! is_array($payload)) {
        $this->error('Plik JSON ma niepoprawny format.');

        return self::FAILURE;
    }

    $report = $importer->import($payload, $dryRun);
    $reportOption = (string) ($this->option('report') ?? '');
    $defaultReportPath = storage_path(sprintf(
        'app/import-reports/%s-%s.json',
        (string) $report['batch_id'],
        $dryRun ? 'dry-run' : 'import',
    ));
    $resolvedReportPath = $reportOption !== ''
        ? (
            Str::startsWith($reportOption, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $reportOption)
                ? $reportOption
                : base_path($reportOption)
        )
        : $defaultReportPath;

    File::ensureDirectoryExists(dirname($resolvedReportPath));
    File::put($resolvedReportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $contentImportRunRecorder->record(
        kind: 'catalog_json_import',
        report: $report,
        reportPath: $resolvedReportPath,
        sourcePath: $resolvedPath,
    );

    $this->info(sprintf(
        '%s zakonczony. Kategorie: %d (%d created / %d updated), pytania: %d (%d created / %d updated), media: %d, bledy: %d',
        $dryRun ? 'Dry-run' : 'Import',
        $report['categories_total'],
        $report['categories_created'],
        $report['categories_updated'],
        $report['questions_total'],
        $report['questions_created'],
        $report['questions_updated'],
        $report['media_created'],
        $report['errors_count'],
    ));
    $this->comment(sprintf('Raport zapisano do: %s', $resolvedReportPath));

    if ($report['errors_count'] > 0) {
        $firstError = $report['errors'][0] ?? null;

        if (is_array($firstError)) {
            $this->warn(sprintf(
                'Pierwszy blad: %s - %s',
                (string) ($firstError['path'] ?? 'unknown'),
                (string) ($firstError['message'] ?? 'unknown error'),
            ));
        }

        return self::FAILURE;
    }

    if (! $dryRun && ! (bool) $this->option('skip-sitemap')) {
        $this->line('Odswiezam statyczne sitemap SEO po imporcie katalogu...');
        $sitemapStatus = Artisan::call('seo:refresh-sitemaps');
        $sitemapOutput = trim((string) Artisan::output());

        if ($sitemapOutput !== '') {
            $this->line($sitemapOutput);
        }

        if ($sitemapStatus !== self::SUCCESS) {
            return self::FAILURE;
        }
    }

    return self::SUCCESS;
})->purpose('Import question catalog data from a JSON file.');

Artisan::command('catalog:import-manifest {path : Sciezka do manifestu importu} {--dry-run : Zweryfikuj batch bez uploadu i bez zapisu do bazy} {--skip-sitemap : Pomin odswiezanie sitemap po niepublicznym lub lokalnym imporcie} {--report= : Sciezka do pliku raportu JSON}', function (CatalogManifestImportService $catalogManifestImportService, ContentImportRunRecorder $contentImportRunRecorder) {
    $inputPath = (string) $this->argument('path');
    $resolvedPath = File::exists($inputPath) ? $inputPath : base_path($inputPath);
    $dryRun = (bool) $this->option('dry-run');

    if (! File::exists($resolvedPath)) {
        $this->error(sprintf('Nie znaleziono pliku: %s', $inputPath));

        return self::FAILURE;
    }

    $report = $catalogManifestImportService->import($resolvedPath, $dryRun);
    $reportOption = (string) ($this->option('report') ?? '');
    $defaultReportPath = storage_path(sprintf(
        'app/import-reports/%s-%s.json',
        (string) ($report['batch_id'] ?? 'manifest-import'),
        $dryRun ? 'manifest-dry-run' : 'manifest-import',
    ));
    $resolvedReportPath = $reportOption !== ''
        ? (
            Str::startsWith($reportOption, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $reportOption)
                ? $reportOption
                : base_path($reportOption)
        )
        : $defaultReportPath;

    File::ensureDirectoryExists(dirname($resolvedReportPath));
    File::put($resolvedReportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $contentImportRunRecorder->record(
        kind: 'manifest_import',
        report: $report,
        reportPath: $resolvedReportPath,
        sourcePath: $resolvedPath,
    );

    $this->info(sprintf(
        '%s zakonczony. Wiersze: %d, pytania: %d, media: %d, upload plan: %d, uploaded: %d, bledy: %d',
        $dryRun ? 'Manifest dry-run' : 'Manifest import',
        (int) ($report['rows_total'] ?? 0),
        (int) ($report['questions_total'] ?? 0),
        (int) ($report['media_total'] ?? 0),
        (int) ($report['asset_plan_total'] ?? 0),
        (int) ($report['uploaded_assets_total'] ?? 0),
        (int) ($report['errors_count'] ?? 0),
    ));
    $this->comment(sprintf('Raport zapisano do: %s', $resolvedReportPath));

    if ((int) ($report['errors_count'] ?? 0) > 0) {
        $firstError = $report['errors'][0] ?? null;

        if (is_array($firstError)) {
            $this->warn(sprintf(
                'Pierwszy blad: %s - %s',
                (string) ($firstError['path'] ?? 'unknown'),
                (string) ($firstError['message'] ?? 'unknown error'),
            ));
        }

        return self::FAILURE;
    }

    if (! $dryRun && ! (bool) $this->option('skip-sitemap')) {
        $this->line('Odswiezam statyczne sitemap SEO po imporcie manifestu...');
        $sitemapStatus = Artisan::call('seo:refresh-sitemaps');
        $sitemapOutput = trim((string) Artisan::output());

        if ($sitemapOutput !== '') {
            $this->line($sitemapOutput);
        }

        if ($sitemapStatus !== self::SUCCESS) {
            return self::FAILURE;
        }
    }

    return self::SUCCESS;
})->purpose('Import a staged manifest batch with ready media assets into storage and the catalog.');

Artisan::command('catalog:import-manifest-series {path : Sciezka do katalogu serii chunkow albo series-report.json} {--dry-run : Zweryfikuj serie bez uploadu i bez zapisu do bazy} {--skip-sitemap : Pomin odswiezanie sitemap po niepublicznym lub lokalnym imporcie} {--from-chunk= : Zacznij import od wskazanego numeru chunku} {--to-chunk= : Zakoncz import na wskazanym numerze chunku} {--continue-on-error : Kontynuuj kolejne chunki mimo bledu w jednym z nich} {--resume-from-report= : Pomin chunki oznaczone jako poprawne w poprzednim raporcie series importu} {--report= : Sciezka do pliku raportu JSON}', function (CatalogManifestSeriesImportService $catalogManifestSeriesImportService, ContentImportRunRecorder $contentImportRunRecorder, QuestionIntegrityAuditService $questionIntegrityAuditService) {
    $fromChunkOption = $this->option('from-chunk');
    $toChunkOption = $this->option('to-chunk');
    $fromChunk = null;
    $toChunk = null;

    if ($fromChunkOption !== null && trim((string) $fromChunkOption) !== '') {
        $fromChunk = max((int) $fromChunkOption, 1);
    }

    if ($toChunkOption !== null && trim((string) $toChunkOption) !== '') {
        $toChunk = max((int) $toChunkOption, 1);
    }

    $summary = $catalogManifestSeriesImportService->import(
        path: (string) $this->argument('path'),
        dryRun: (bool) $this->option('dry-run'),
        fromChunk: $fromChunk,
        toChunk: $toChunk,
        continueOnError: (bool) $this->option('continue-on-error'),
        resumeReportPath: ($this->option('resume-from-report') !== null && trim((string) $this->option('resume-from-report')) !== '')
            ? (string) $this->option('resume-from-report')
            : null,
    );

    $reportOption = (string) ($this->option('report') ?? '');
    $defaultReportPath = storage_path(sprintf(
        'app/import-reports/%s-series-import.json',
        (string) ($summary['series_id'] ?? 'manifest-series-import'),
    ));
    $resolvedReportPath = $reportOption !== ''
        ? (
            Str::startsWith($reportOption, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $reportOption)
                ? $reportOption
                : base_path($reportOption)
        )
        : $defaultReportPath;

    File::ensureDirectoryExists(dirname($resolvedReportPath));
    File::put($resolvedReportPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $importRun = $contentImportRunRecorder->record(
        kind: 'manifest_series_import',
        report: $summary,
        reportPath: $resolvedReportPath,
        sourcePath: (string) $this->argument('path'),
    );

    $shouldRunIntegrityAudit = ! (bool) $summary['dry_run']
        && (int) ($summary['errors_count'] ?? 0) === 0
        && $fromChunk === null
        && $toChunk === null;

    if ($shouldRunIntegrityAudit) {
        $integrityAudit = $questionIntegrityAuditService->auditCurrentCatalog('gov_full_catalog');
        $summary['integrity_audit'] = $integrityAudit;
        $importRun->forceFill([
            'summary' => array_merge($importRun->summary ?? [], [
                'integrity_audit' => [
                    'baseline' => (bool) ($integrityAudit['baseline'] ?? false),
                    'report_path' => $integrityAudit['report_path'] ?? null,
                    'changes_path' => $integrityAudit['changes_path'] ?? null,
                    'summary' => $integrityAudit['summary'] ?? [],
                ],
            ]),
        ])->save();
    }

    $this->info(sprintf(
        'Manifest series %s. Chunki: %d/%d, pytania: %d, media: %d, upload plan: %d, uploaded: %d, bledy: %d',
        (bool) $this->option('dry-run') ? 'dry-run zakonczony' : 'import zakonczony',
        (int) ($summary['chunks_selected'] ?? 0),
        (int) ($summary['chunks_discovered'] ?? 0),
        (int) ($summary['questions_total'] ?? 0),
        (int) ($summary['media_total'] ?? 0),
        (int) ($summary['asset_plan_total'] ?? 0),
        (int) ($summary['uploaded_assets_total'] ?? 0),
        (int) ($summary['errors_count'] ?? 0),
    ));
    $this->comment(sprintf('Raport zapisano do: %s', $resolvedReportPath));
    $this->comment(sprintf('Powod zatrzymania: %s', (string) ($summary['stopped_reason'] ?? 'completed')));

    if (isset($summary['integrity_audit']) && is_array($summary['integrity_audit'])) {
        $integritySummary = $summary['integrity_audit']['summary'] ?? [];

        $this->comment(sprintf(
            'Audyt integralnosci: added %d, removed %d, changed %d, critical %d',
            (int) ($integritySummary['added_total'] ?? 0),
            (int) ($integritySummary['removed_total'] ?? 0),
            (int) ($integritySummary['changed_total'] ?? 0),
            (int) ($integritySummary['critical_total'] ?? 0),
        ));

        if (filled($summary['integrity_audit']['report_path'] ?? null)) {
            $this->comment(sprintf('Raport integralnosci: %s', (string) $summary['integrity_audit']['report_path']));
        }
    }

    if ((int) ($summary['errors_count'] ?? 0) > 0) {
        $firstError = $summary['errors'][0] ?? null;

        if (is_array($firstError)) {
            $this->warn(sprintf(
                'Pierwszy blad: %s - %s',
                (string) ($firstError['path'] ?? 'unknown'),
                (string) ($firstError['message'] ?? 'unknown error'),
            ));
        }

        return self::FAILURE;
    }

    if (! (bool) $this->option('dry-run') && ! (bool) $this->option('skip-sitemap')) {
        $this->line('Odswiezam statyczne sitemap SEO po imporcie serii manifestow...');
        $sitemapStatus = Artisan::call('seo:refresh-sitemaps');
        $sitemapOutput = trim((string) Artisan::output());

        if ($sitemapOutput !== '') {
            $this->line($sitemapOutput);
        }

        if ($sitemapStatus !== self::SUCCESS) {
            return self::FAILURE;
        }
    }

    return self::SUCCESS;
})->purpose('Import a whole directory of staged manifest chunks and aggregate the outcome.');

Artisan::command('content:audit-question-integrity {--channel=gov_full_catalog : Kanal snapshotow integralnosci} {--report= : Dodatkowa sciezka do kopii raportu JSON}', function (QuestionIntegrityAuditService $questionIntegrityAuditService) {
    $result = $questionIntegrityAuditService->auditCurrentCatalog((string) $this->option('channel'));
    $reportPath = (string) ($result['report_path'] ?? '');
    $reportOption = trim((string) ($this->option('report') ?? ''));

    if ($reportOption !== '' && $reportPath !== '') {
        $targetPath = Str::startsWith($reportOption, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $reportOption)
            ? $reportOption
            : base_path($reportOption);
        File::ensureDirectoryExists(dirname($targetPath));
        File::copy($reportPath, $targetPath);
        $reportPath = $targetPath;
    }

    $summary = is_array($result['summary'] ?? null) ? $result['summary'] : [];

    $this->info(sprintf(
        'Audyt integralnosci zakonczony. current=%d, added=%d, removed=%d, changed=%d, critical=%d',
        (int) ($summary['current_total'] ?? 0),
        (int) ($summary['added_total'] ?? 0),
        (int) ($summary['removed_total'] ?? 0),
        (int) ($summary['changed_total'] ?? 0),
        (int) ($summary['critical_total'] ?? 0),
    ));
    $this->comment(sprintf('Baseline: %s', (bool) ($result['baseline'] ?? false) ? 'tak' : 'nie'));
    $this->comment(sprintf('Snapshot: %s', (string) ($result['snapshot_path'] ?? 'n/a')));
    $this->comment(sprintf('Raport: %s', $reportPath !== '' ? $reportPath : 'n/a'));
    $this->comment(sprintf('Changes: %s', (string) ($result['changes_path'] ?? 'n/a')));

    return self::SUCCESS;
})->purpose('Compare the current official catalog snapshot with the previous one and produce a change report.');

Artisan::command('catalog:prepare-gov-batch {xlsx : Sciezka do oficjalnego XLSX z gov.pl} {output : Katalog wyjsciowy dla batcha stagingowego} {mediaSources* : Archiwa ZIP lub katalogi z oficjalnymi mediami} {--include-verification : Dolacz arkusz "W trakcie weryfikacji"} {--materialize-media : Skopiuj obrazy i przetworz filmy do stagingu} {--categories= : Lista kategorii do dolaczenia, np. B,A} {--ready-only : Zachowaj tylko rekordy gotowe do importu przy aktualnej konfiguracji mediow} {--allow-missing-media : Nie failuj batcha przez brakujace glowne media, tylko raportuj je jako ostrzezenia} {--source-offset=0 : Pomin pierwsze N wierszy z oficjalnego XLSX przed stagingiem} {--source-limit= : Przetworz maksymalnie N kolejnych wierszy z oficjalnego XLSX}', function (GovernmentCatalogBatchBuilder $governmentCatalogBatchBuilder, ContentImportRunRecorder $contentImportRunRecorder) {
    $xlsx = (string) $this->argument('xlsx');
    $output = (string) $this->argument('output');
    $mediaSources = array_map(
        static fn (mixed $value): string => (string) $value,
        (array) $this->argument('mediaSources'),
    );
    $categoriesOption = trim((string) ($this->option('categories') ?? ''));
    $categoryFilter = $categoriesOption === ''
        ? []
        : array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            preg_split('/[,\s;]+/', $categoriesOption) ?: [],
        )));
    $sourceOffset = max((int) $this->option('source-offset'), 0);
    $sourceLimitOption = $this->option('source-limit');
    $sourceLimit = null;

    if ($sourceLimitOption !== null && trim((string) $sourceLimitOption) !== '') {
        $sourceLimit = max((int) $sourceLimitOption, 0);
    }

    $report = $governmentCatalogBatchBuilder->build(
        xlsxPath: $xlsx,
        mediaSources: $mediaSources,
        outputDirectory: $output,
        includeVerification: (bool) $this->option('include-verification'),
        materializeMedia: (bool) $this->option('materialize-media'),
        categoryFilter: $categoryFilter,
        readyOnly: (bool) $this->option('ready-only'),
        sourceOffset: $sourceOffset,
        sourceLimit: $sourceLimit,
        allowMissingMedia: (bool) $this->option('allow-missing-media'),
    );

    $reportPath = (str_starts_with($output, '/') || str_starts_with($output, '\\') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $output))
        ? $output.DIRECTORY_SEPARATOR.'report.json'
        : base_path($output).DIRECTORY_SEPARATOR.'report.json';
    File::ensureDirectoryExists(dirname($reportPath));
    File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $contentImportRunRecorder->record(
        kind: 'gov_batch_prepare',
        report: $report,
        reportPath: $reportPath,
        sourcePath: $xlsx,
        outputPath: (string) ($report['output_directory'] ?? null),
    );

    $this->info(sprintf(
        'Gov batch przygotowany. Wiersze: %d, pytania po rozbiciu na kategorie: %d, obrazy: %d, wideo: %d, bledy: %d',
        (int) ($report['rows_prepared'] ?? 0),
        (int) ($report['question_rows_total'] ?? 0),
        (int) ($report['images_materialized'] ?? 0),
        (int) ($report['videos_materialized'] ?? 0),
        (int) ($report['errors_count'] ?? 0),
    ));
    $this->comment(sprintf('Manifest: %s', (string) ($report['manifest_path'] ?? '')));
    $this->comment(sprintf('CSV: %s', (string) ($report['questions_file'] ?? '')));
    $this->comment(sprintf('Raport: %s', $reportPath));
    $this->comment(sprintf(
        'Okno zrodla: offset=%d, limit=%s, rozwazone wiersze=%d',
        (int) ($report['source_offset'] ?? 0),
        array_key_exists('source_limit', $report) && $report['source_limit'] !== null
            ? (string) $report['source_limit']
            : 'all',
        (int) ($report['source_rows_considered'] ?? 0),
    ));

    if (($report['warnings'] ?? []) !== []) {
        $this->warn(sprintf('Ostrzezenia: %d', count((array) $report['warnings'])));
    }

    return (string) ($report['status'] ?? 'ok') === 'failed'
        ? self::FAILURE
        : self::SUCCESS;
})->purpose('Prepare a staged manifest batch from the official gov.pl XLSX and media packages.');

Artisan::command('catalog:prepare-gov-batch-series {xlsx : Sciezka do oficjalnego XLSX z gov.pl} {output : Katalog wyjsciowy dla serii chunkow stagingowych} {mediaSources* : Archiwa ZIP lub katalogi z oficjalnymi mediami} {--include-verification : Dolacz arkusz "W trakcie weryfikacji"} {--materialize-media : Skopiuj obrazy i przetworz filmy do stagingu} {--categories= : Lista kategorii do dolaczenia, np. B,A} {--ready-only : Zachowaj tylko rekordy gotowe do importu przy aktualnej konfiguracji mediow} {--allow-missing-media : Nie zatrzymuj calej serii przez pojedyncze brakujace glowne media} {--chunk-size=100 : Liczba wierszy zrodlowych na jeden chunk} {--source-offset=0 : Startowy offset wierszy zrodla} {--max-chunks= : Maksymalna liczba chunkow do wygenerowania} {--skip-existing : Wznow serie na istniejacych poprawnych chunkach zamiast budowac je ponownie}', function (GovernmentCatalogBatchSeriesBuilder $governmentCatalogBatchSeriesBuilder, ContentImportRunRecorder $contentImportRunRecorder) {
    $xlsx = (string) $this->argument('xlsx');
    $output = (string) $this->argument('output');
    $mediaSources = array_map(
        static fn (mixed $value): string => (string) $value,
        (array) $this->argument('mediaSources'),
    );
    $categoriesOption = trim((string) ($this->option('categories') ?? ''));
    $categoryFilter = $categoriesOption === ''
        ? []
        : array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            preg_split('/[,\s;]+/', $categoriesOption) ?: [],
        )));
    $chunkSize = max((int) $this->option('chunk-size'), 1);
    $sourceOffset = max((int) $this->option('source-offset'), 0);
    $maxChunksOption = $this->option('max-chunks');
    $maxChunks = null;

    if ($maxChunksOption !== null && trim((string) $maxChunksOption) !== '') {
        $maxChunks = max((int) $maxChunksOption, 1);
    }

    $summary = $governmentCatalogBatchSeriesBuilder->build(
        xlsxPath: $xlsx,
        mediaSources: $mediaSources,
        outputDirectory: $output,
        includeVerification: (bool) $this->option('include-verification'),
        materializeMedia: (bool) $this->option('materialize-media'),
        categoryFilter: $categoryFilter,
        readyOnly: (bool) $this->option('ready-only'),
        chunkSize: $chunkSize,
        sourceOffset: $sourceOffset,
        maxChunks: $maxChunks,
        skipExisting: (bool) $this->option('skip-existing'),
        allowMissingMedia: (bool) $this->option('allow-missing-media'),
    );

    $seriesReportPath = (str_starts_with($output, '/') || str_starts_with($output, '\\') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $output))
        ? $output.DIRECTORY_SEPARATOR.'series-report.json'
        : base_path($output).DIRECTORY_SEPARATOR.'series-report.json';
    $contentImportRunRecorder->record(
        kind: 'gov_batch_series_prepare',
        report: $summary,
        reportPath: $seriesReportPath,
        sourcePath: $xlsx,
        outputPath: (string) ($summary['output_directory'] ?? null),
    );

    $this->info(sprintf(
        'Gov batch series zakonczona. Chunki: %d, pytania: %d, obrazy: %d, wideo: %d, bledy: %d',
        (int) ($summary['chunks_total'] ?? 0),
        (int) ($summary['question_rows_total'] ?? 0),
        (int) ($summary['images_materialized_total'] ?? 0),
        (int) ($summary['videos_materialized_total'] ?? 0),
        (int) ($summary['errors_total'] ?? 0),
    ));
    $this->comment(sprintf('Series report: %s', $seriesReportPath));
    $this->comment(sprintf('Powod zatrzymania: %s', (string) ($summary['stopped_reason'] ?? 'completed')));

    return (string) ($summary['status'] ?? 'ok') === 'failed'
        ? self::FAILURE
        : self::SUCCESS;
})->purpose('Prepare multiple staged gov.pl manifest chunks in one run for large imports.');

Artisan::command('content:audit-delivery-readiness {--category= : Kod kategorii lub lista kodow kategorii, np. B,A} {--deactivate-missing-primary-media : Ustaw is_active=false dla pytan z brakujacym glownym medium}', function (QuestionDeliveryReadinessService $questionDeliveryReadinessService) {
    $categoriesOption = trim((string) ($this->option('category') ?? ''));
    $categoryFilter = $categoriesOption === ''
        ? []
        : array_values(array_filter(array_map(
            static fn (string $value): string => strtoupper(trim($value)),
            preg_split('/[,\s;]+/', $categoriesOption) ?: [],
        )));

    $query = Question::query()
        ->with('media', 'licenseCategory')
        ->when($categoryFilter !== [], function ($query) use ($categoryFilter): void {
            $query->whereHas('licenseCategory', fn ($categoryQuery) => $categoryQuery->whereIn('code', $categoryFilter));
        })
        ->orderBy('id');

    $processed = 0;
    $updated = 0;
    $issues = 0;
    $deactivated = 0;

    $query->chunkById(200, function ($questions) use (
        $questionDeliveryReadinessService,
        &$processed,
        &$updated,
        &$issues,
        &$deactivated,
    ): void {
        $result = $questionDeliveryReadinessService->syncMany($questions);

        $processed += (int) $result['processed'];
        $updated += (int) $result['updated'];
        $issues += (int) $result['issues'];

        if (! (bool) $this->option('deactivate-missing-primary-media')) {
            return;
        }

        foreach ($questions as $question) {
            if (
                $question->delivery_issue === Question::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA
                && $question->is_active
            ) {
                $question->forceFill([
                    'is_active' => false,
                ])->save();
                $deactivated++;
            }
        }
    });

    $this->info(sprintf(
        'Audit delivery zakonczony. Przetworzono: %d, zaktualizowano status: %d, wykryte problemy: %d, deaktywowane: %d',
        $processed,
        $updated,
        $issues,
        $deactivated,
    ));

    if ($categoryFilter !== []) {
        $this->comment(sprintf('Zakres kategorii: %s', implode(', ', $categoryFilter)));
    }

    return self::SUCCESS;
})->purpose('Audit question delivery readiness and optionally deactivate records with missing primary media.');

Artisan::command('content:classify-question-topics {--refresh : Przelicz tematy rowniez dla juz sklasyfikowanych pytan} {--category= : Kod kategorii lub lista kodow kategorii, np. B,A}', function (QuestionTopicAssigner $questionTopicAssigner) {
    $categoriesOption = trim((string) ($this->option('category') ?? ''));
    $categoryFilter = $categoriesOption === ''
        ? []
        : array_values(array_filter(array_map(
            static fn (string $value): string => strtoupper(trim($value)),
            preg_split('/[,\s;]+/', $categoriesOption) ?: [],
        )));
    $refresh = (bool) $this->option('refresh');

    $questionTopicAssigner->seedTopics();

    $query = Question::query()
        ->with('questionTopic')
        ->when(! $refresh, fn ($questionQuery) => $questionQuery->whereNull('question_topic_id'))
        ->when($categoryFilter !== [], function ($questionQuery) use ($categoryFilter): void {
            $questionQuery->whereHas('licenseCategory', fn ($categoryQuery) => $categoryQuery->whereIn('code', $categoryFilter));
        })
        ->orderBy('id');

    $processed = 0;
    $updated = 0;
    $topicCounts = [];

    $query->chunkById(200, function ($questions) use ($questionTopicAssigner, &$processed, &$updated, &$topicCounts): void {
        foreach ($questions as $question) {
            $previousTopicId = $question->question_topic_id;
            $topic = $questionTopicAssigner->assign($question);

            $processed++;

            if ($topic !== null) {
                $topicCounts[$topic->name] = (int) ($topicCounts[$topic->name] ?? 0) + 1;
            }

            if ($previousTopicId !== $question->question_topic_id) {
                $updated++;
            }
        }
    });

    ksort($topicCounts);

    $this->info(sprintf(
        'Klasyfikacja tematow zakonczona. Przetworzono: %d, zaktualizowano: %d, tematow: %d',
        $processed,
        $updated,
        count($topicCounts),
    ));

    if ($categoryFilter !== []) {
        $this->comment(sprintf('Zakres kategorii: %s', implode(', ', $categoryFilter)));
    }

    foreach ($topicCounts as $topicName => $count) {
        $this->line(sprintf('- %s: %d', $topicName, $count));
    }

    return self::SUCCESS;
})->purpose('Classify questions into thematic topics such as warning signs, prohibition signs and traffic rules.');

Artisan::command('content:audit-question-topic-reclassifier {--category= : Kod kategorii lub lista kodow kategorii, np. B,A} {--scope=active_ready : active_ready, active albo all} {--limit= : Ogranicz liczbe pytan w raporcie} {--include-unchanged : Dodaj do raportu takze pytania bez zmiany tematu} {--json= : Sciezka do pliku raportu JSON}', function (QuestionTopicReclassifierAuditService $auditService) {
    $categoriesOption = trim((string) ($this->option('category') ?? ''));
    $categoryFilter = $categoriesOption === ''
        ? []
        : array_values(array_filter(array_map(
            static fn (string $value): string => strtoupper(trim($value)),
            preg_split('/[,\s;]+/', $categoriesOption) ?: [],
        )));

    $limitOption = trim((string) ($this->option('limit') ?? ''));
    $limit = $limitOption !== '' ? max((int) $limitOption, 1) : null;
    $scope = trim((string) ($this->option('scope') ?? QuestionTopicReclassifierAuditService::SCOPE_ACTIVE_READY));

    try {
        $report = $auditService->audit(
            categoryFilter: $categoryFilter,
            scope: $scope,
            limit: $limit,
            includeUnchanged: (bool) $this->option('include-unchanged'),
        );
    } catch (InvalidArgumentException $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }

    $reportOption = trim((string) ($this->option('json') ?? ''));
    $defaultReportPath = base_path(sprintf(
        'output/analysis/pj360-category-consistency/reclassifier-audit-%s.json',
        str_replace('_', '-', (string) $report['scope']),
    ));
    $resolvedReportPath = $reportOption !== ''
        ? (
            Str::startsWith($reportOption, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $reportOption)
                ? $reportOption
                : base_path($reportOption)
        )
        : $defaultReportPath;

    File::ensureDirectoryExists(dirname($resolvedReportPath));
    File::put($resolvedReportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->info(sprintf(
        'Audit reclassifiera zakonczony. Przetworzono: %d, zmiana tematu: %d, bez zmiany: %d.',
        (int) $report['processed_questions'],
        (int) $report['changed_questions'],
        (int) $report['unchanged_questions'],
    ));
    $this->comment(sprintf('Scope: %s', (string) $report['scope']));

    if ($categoryFilter !== []) {
        $this->comment(sprintf('Zakres kategorii: %s', implode(', ', $categoryFilter)));
    }

    if ($limit !== null) {
        $this->comment(sprintf('Limit pytan: %d', $limit));
    }

    foreach ($report['category_summaries'] as $categoryCode => $summary) {
        $this->line(sprintf(
            '- %s: processed=%d, changed=%d, unchanged=%d',
            $categoryCode,
            (int) ($summary['processed_questions'] ?? 0),
            (int) ($summary['changed_questions'] ?? 0),
            (int) ($summary['unchanged_questions'] ?? 0),
        ));
    }

    $this->comment(sprintf('Raport zapisano do: %s', $resolvedReportPath));

    return self::SUCCESS;
})->purpose('Generate an audit-only diff between current topics, classifier output, overrides and effective topic assignment without mutating the database.');

Artisan::command('content:build-question-topic-override-package {path : Sciezka do pliku JSON ze specyfikacja paczki override} {--json= : Sciezka do pliku wynikowego JSON}', function (QuestionTopicOverridePackageBuilder $questionTopicOverridePackageBuilder) {
    $inputPath = (string) $this->argument('path');
    $resolvedPath = File::exists($inputPath) ? $inputPath : base_path($inputPath);

    if (! File::exists($resolvedPath)) {
        $this->error(sprintf('Nie znaleziono pliku specyfikacji: %s', $inputPath));

        return self::FAILURE;
    }

    $payload = json_decode((string) File::get($resolvedPath), true);

    if (! is_array($payload)) {
        $this->error('Plik specyfikacji ma niepoprawny format JSON.');

        return self::FAILURE;
    }

    try {
        $package = $questionTopicOverridePackageBuilder->build($payload);
    } catch (InvalidArgumentException $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }

    $package['source_spec_path'] = $resolvedPath;

    $reportOption = trim((string) ($this->option('json') ?? ''));
    $defaultReportPath = base_path(sprintf(
        'output/analysis/pj360-category-consistency/override-package-%s-%s.json',
        Str::lower((string) ($package['category'] ?? 'unknown')),
        str_replace('_', '-', (string) ($package['scope'] ?? 'active-ready')),
    ));
    $resolvedReportPath = $reportOption !== ''
        ? (
            Str::startsWith($reportOption, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $reportOption)
                ? $reportOption
                : base_path($reportOption)
        )
        : $defaultReportPath;

    File::ensureDirectoryExists(dirname($resolvedReportPath));
    File::put($resolvedReportPath, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->info(sprintf(
        'Build paczki override zakonczony. Category=%s, scope=%s, rules=%d, exported=%d.',
        (string) ($package['category'] ?? 'unknown'),
        (string) ($package['scope'] ?? 'unknown'),
        (int) ($package['rules_count'] ?? 0),
        (int) ($package['exported_candidates'] ?? 0),
    ));

    foreach ($package['rule_summaries'] ?? [] as $summary) {
        if (! is_array($summary)) {
            continue;
        }

        $this->line(sprintf(
            '- %s -> %s: matched=%d',
            (string) ($summary['name'] ?? 'rule'),
            (string) ($summary['target_topic_key'] ?? 'unknown'),
            (int) ($summary['matched_questions'] ?? 0),
        ));
    }

    $this->comment(sprintf('Plik specyfikacji: %s', $resolvedPath));
    $this->comment(sprintf('Plik paczki override: %s', $resolvedReportPath));
    $this->comment('Paczka jest review-only. Po review mozna ja podac do: content:sync-question-topic-overrides <path>');

    return self::SUCCESS;
})->purpose('Build a review-only topic override package from a curated JSON specification without mutating question assignments.');

Artisan::command('content:sync-question-topic-overrides {path : Sciezka do pliku JSON z overrideami tematow} {--dry-run : Zweryfikuj wsad bez zapisu do bazy}', function (QuestionTopicAssigner $questionTopicAssigner) {
    $inputPath = (string) $this->argument('path');
    $resolvedPath = File::exists($inputPath) ? $inputPath : base_path($inputPath);
    $dryRun = (bool) $this->option('dry-run');

    if (! File::exists($resolvedPath)) {
        $this->error(sprintf('Nie znaleziono pliku: %s', $inputPath));

        return self::FAILURE;
    }

    $payload = json_decode((string) File::get($resolvedPath), true);

    if (! is_array($payload)) {
        $this->error('Plik JSON ma niepoprawny format.');

        return self::FAILURE;
    }

    $rows = array_is_list($payload) ? $payload : ($payload['overrides'] ?? null);

    if (! is_array($rows)) {
        $this->error('JSON musi byc tablica overrideow albo obiektem z kluczem "overrides".');

        return self::FAILURE;
    }

    $questionTopicAssigner->seedTopics();

    $validTopicKeys = QuestionTopic::query()
        ->where('is_active', true)
        ->pluck('key')
        ->map(static fn (mixed $value): string => (string) $value)
        ->all();
    $validCategoryCodes = LicenseCategory::query()
        ->pluck('code')
        ->map(static fn (mixed $value): string => strtoupper(trim((string) $value)))
        ->all();

    $normalizedRows = [];
    $errors = [];
    $created = 0;
    $updated = 0;
    $deactivated = 0;
    $seenLookupKeys = [];

    foreach ($rows as $index => $row) {
        if (! is_array($row)) {
            $errors[] = sprintf('override[%d]: rekord musi byc obiektem.', $index);

            continue;
        }

        $validator = Validator::make($row, [
            'license_category_code' => ['required', 'string', 'max:16'],
            'source' => ['required', 'string', 'max:255'],
            'external_id' => ['required', 'string', 'max:255'],
            'question_topic_key' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $errors[] = sprintf('override[%d]: %s', $index, $message);
            }

            continue;
        }

        $validated = $validator->validated();
        $normalized = [
            'license_category_code' => strtoupper(trim((string) $validated['license_category_code'])),
            'source' => Str::lower(trim((string) $validated['source'])),
            'external_id' => trim((string) $validated['external_id']),
            'question_topic_key' => trim((string) $validated['question_topic_key']),
            'reason' => isset($validated['reason']) ? trim((string) $validated['reason']) : null,
            'metadata' => isset($validated['metadata'])
                ? json_encode($validated['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($normalized['license_category_code'] === '' || $normalized['source'] === '' || $normalized['external_id'] === '' || $normalized['question_topic_key'] === '') {
            $errors[] = sprintf('override[%d]: pola kluczowe nie moga byc puste po normalizacji.', $index);

            continue;
        }

        if (! in_array($normalized['license_category_code'], $validCategoryCodes, true)) {
            $errors[] = sprintf(
                'override[%d]: nieznana kategoria "%s".',
                $index,
                $normalized['license_category_code'],
            );

            continue;
        }

        if (! in_array($normalized['question_topic_key'], $validTopicKeys, true)) {
            $errors[] = sprintf(
                'override[%d]: nieznany topic key "%s".',
                $index,
                $normalized['question_topic_key'],
            );

            continue;
        }

        $lookupKey = implode('|', [
            $normalized['license_category_code'],
            $normalized['source'],
            $normalized['external_id'],
        ]);

        if (isset($seenLookupKeys[$lookupKey])) {
            $errors[] = sprintf(
                'override[%d]: zduplikowany rekord dla %s / %s / %s.',
                $index,
                $normalized['license_category_code'],
                $normalized['source'],
                $normalized['external_id'],
            );

            continue;
        }

        $seenLookupKeys[$lookupKey] = true;

        $existing = QuestionTopicOverride::query()
            ->where('license_category_code', $normalized['license_category_code'])
            ->where('source', $normalized['source'])
            ->where('external_id', $normalized['external_id'])
            ->first();

        if ($existing === null) {
            $created++;
        } else {
            $updated++;

            if ($existing->is_active && ! $normalized['is_active']) {
                $deactivated++;
            }
        }

        $normalizedRows[] = $normalized;
    }

    if ($errors !== []) {
        foreach ($errors as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }

    if (! $dryRun && $normalizedRows !== []) {
        QuestionTopicOverride::query()->upsert(
            $normalizedRows,
            ['license_category_code', 'source', 'external_id'],
            ['question_topic_key', 'reason', 'metadata', 'is_active', 'updated_at'],
        );
    }

    $this->info(sprintf(
        '%s synchronizacji overrideow zakonczony. Rekordy: %d, created: %d, updated: %d, deactivated: %d.',
        $dryRun ? 'Dry-run' : 'Import',
        count($normalizedRows),
        $created,
        $updated,
        $deactivated,
    ));
    $this->comment('Zmiany overrideow zaczna dzialac po ponownym przeliczeniu tematow: content:classify-question-topics --refresh');

    return self::SUCCESS;
})->purpose('Validate and upsert audited question topic overrides keyed by category, source and external_id.');

Artisan::command('content:export-question-topic-override-candidates {--category= : Kod kategorii lub lista kodow kategorii, np. B,A} {--scope=active_ready : active_ready, active albo all} {--limit= : Ogranicz liczbe pytan w raporcie} {--json= : Sciezka do pliku raportu JSON} {--reason= : Nadpisz domyslny reason dla kandydatow} {--include-fallbacks : Dolacz takze kandydatow, ktorzy wynikaja tylko z fallback classifiera} {--matched-by= : Ogranicz do wybranych classifier_matched_by, np. keyword:traffic_signal,keyword:road_markings_reflectors} {--topic= : Ogranicz do wybranych proposed topic keys, np. road_markings,lane_change_and_turning}', function (QuestionTopicReclassifierAuditService $auditService) {
    $categoriesOption = trim((string) ($this->option('category') ?? ''));
    $categoryFilter = $categoriesOption === ''
        ? []
        : array_values(array_filter(array_map(
            static fn (string $value): string => strtoupper(trim($value)),
            preg_split('/[,\s;]+/', $categoriesOption) ?: [],
        )));

    $limitOption = trim((string) ($this->option('limit') ?? ''));
    $limit = $limitOption !== '' ? max((int) $limitOption, 1) : null;
    $scope = trim((string) ($this->option('scope') ?? QuestionTopicReclassifierAuditService::SCOPE_ACTIVE_READY));
    $reasonOverride = trim((string) ($this->option('reason') ?? ''));
    $includeFallbacks = (bool) $this->option('include-fallbacks');
    $matchedByFilter = collect(preg_split('/[,\s;]+/', trim((string) ($this->option('matched-by') ?? ''))) ?: [])
        ->map(static fn (string $value): string => trim($value))
        ->filter()
        ->values()
        ->all();
    $topicFilter = collect(preg_split('/[,\s;]+/', trim((string) ($this->option('topic') ?? ''))) ?: [])
        ->map(static fn (string $value): string => trim($value))
        ->filter()
        ->values()
        ->all();

    try {
        $report = $auditService->audit(
            categoryFilter: $categoryFilter,
            scope: $scope,
            limit: $limit,
            includeUnchanged: false,
        );
    } catch (InvalidArgumentException $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }

    $skippedWithoutIdentity = 0;
    $skippedLowConfidence = 0;
    $skippedByMatchedByFilter = 0;
    $skippedByTopicFilter = 0;
    $auditGeneratedAt = $report['generated_at'] ?? null;
    $candidates = collect($report['entries'])
        ->filter(function (array $entry) use (&$skippedWithoutIdentity, &$skippedLowConfidence, &$skippedByMatchedByFilter, &$skippedByTopicFilter, $includeFallbacks, $matchedByFilter, $topicFilter): bool {
            if (($entry['resolution_source'] ?? null) !== 'classifier') {
                return false;
            }

            if (! ($entry['changed'] ?? false)) {
                return false;
            }

            if (blank($entry['source'] ?? null) || blank($entry['external_id'] ?? null) || blank($entry['category_code'] ?? null)) {
                $skippedWithoutIdentity++;

                return false;
            }

            if (! $includeFallbacks && Str::startsWith((string) ($entry['classifier_matched_by'] ?? ''), 'fallback:')) {
                $skippedLowConfidence++;

                return false;
            }

            if ($matchedByFilter !== [] && ! in_array((string) ($entry['classifier_matched_by'] ?? ''), $matchedByFilter, true)) {
                $skippedByMatchedByFilter++;

                return false;
            }

            if ($topicFilter !== [] && ! in_array((string) ($entry['proposed_topic_key'] ?? ''), $topicFilter, true)) {
                $skippedByTopicFilter++;

                return false;
            }

            return true;
        })
        ->map(function (array $entry) use ($reasonOverride, $auditGeneratedAt): array {
            $reason = $reasonOverride !== ''
                ? $reasonOverride
                : sprintf(
                    'Kandydat z audytu PJ360: %s -> %s',
                    (string) ($entry['current_topic_key'] ?? 'unassigned'),
                    (string) ($entry['proposed_topic_key'] ?? 'unknown'),
                );

            return [
                'license_category_code' => (string) $entry['category_code'],
                'source' => (string) $entry['source'],
                'external_id' => (string) $entry['external_id'],
                'question_topic_key' => (string) $entry['proposed_topic_key'],
                'reason' => $reason,
                'metadata' => [
                    'current_topic_key' => $entry['current_topic_key'] ?? null,
                    'classifier_topic_key' => $entry['classifier_topic_key'] ?? null,
                    'classifier_matched_by' => $entry['classifier_matched_by'] ?? null,
                    'prompt_excerpt' => $entry['prompt_excerpt'] ?? null,
                    'audit_generated_at' => $auditGeneratedAt,
                ],
            ];
        })
        ->values()
        ->all();

    $payload = [
        'generated_at' => now()->toIso8601String(),
        'scope' => $report['scope'],
        'category_filter' => $report['category_filter'],
        'limit' => $report['limit'],
        'source_report_generated_at' => $report['generated_at'],
        'changed_questions' => $report['changed_questions'],
        'exported_candidates' => count($candidates),
        'skipped_without_identity' => $skippedWithoutIdentity,
        'skipped_low_confidence' => $skippedLowConfidence,
        'skipped_by_matched_by_filter' => $skippedByMatchedByFilter,
        'skipped_by_topic_filter' => $skippedByTopicFilter,
        'include_fallbacks' => $includeFallbacks,
        'matched_by_filter' => $matchedByFilter,
        'topic_filter' => $topicFilter,
        'overrides' => $candidates,
    ];

    $reportOption = trim((string) ($this->option('json') ?? ''));
    $defaultReportPath = base_path(sprintf(
        'output/analysis/pj360-category-consistency/override-candidates-%s.json',
        str_replace('_', '-', (string) $report['scope']),
    ));
    $resolvedReportPath = $reportOption !== ''
        ? (
            Str::startsWith($reportOption, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $reportOption)
                ? $reportOption
                : base_path($reportOption)
        )
        : $defaultReportPath;

    File::ensureDirectoryExists(dirname($resolvedReportPath));
    File::put($resolvedReportPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->info(sprintf(
        'Eksport kandydatow override zakonczony. Changed=%d, exported=%d, skipped_without_identity=%d, skipped_low_confidence=%d, skipped_by_matched_by_filter=%d, skipped_by_topic_filter=%d.',
        (int) $report['changed_questions'],
        count($candidates),
        $skippedWithoutIdentity,
        $skippedLowConfidence,
        $skippedByMatchedByFilter,
        $skippedByTopicFilter,
    ));

    if ($categoryFilter !== []) {
        $this->comment(sprintf('Zakres kategorii: %s', implode(', ', $categoryFilter)));
    }

    if ($matchedByFilter !== []) {
        $this->comment(sprintf('Filtr matched_by: %s', implode(', ', $matchedByFilter)));
    }

    if ($topicFilter !== []) {
        $this->comment(sprintf('Filtr topic: %s', implode(', ', $topicFilter)));
    }

    $this->comment(sprintf('Plik kandydatow: %s', $resolvedReportPath));
    $this->comment('Plik jest review-only. Po review mozna go podac do: content:sync-question-topic-overrides <path>');

    return self::SUCCESS;
})->purpose('Export review-only override candidates from the reclassifier audit without mutating question assignments.');

Artisan::command('content:report-question-topic-assignment-delta {--category= : Kod kategorii lub lista kodow kategorii, np. B,A} {--scope=active_ready : active_ready, active albo all} {--limit= : Ogranicz liczbe pytan w raporcie} {--json= : Sciezka do pliku raportu JSON}', function (QuestionTopicReclassifierAuditService $auditService, QuestionTopicClassifier $questionTopicClassifier) {
    $categoriesOption = trim((string) ($this->option('category') ?? ''));
    $categoryFilter = $categoriesOption === ''
        ? []
        : array_values(array_filter(array_map(
            static fn (string $value): string => strtoupper(trim($value)),
            preg_split('/[,\s;]+/', $categoriesOption) ?: [],
        )));

    $limitOption = trim((string) ($this->option('limit') ?? ''));
    $limit = $limitOption !== '' ? max((int) $limitOption, 1) : null;
    $scope = trim((string) ($this->option('scope') ?? QuestionTopicReclassifierAuditService::SCOPE_ACTIVE_READY));

    try {
        $report = $auditService->audit(
            categoryFilter: $categoryFilter,
            scope: $scope,
            limit: $limit,
            includeUnchanged: false,
        );
    } catch (InvalidArgumentException $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }

    $categories = array_values(array_unique(array_merge(
        array_keys($report['current_topic_counts'] ?? []),
        array_keys($report['proposed_topic_counts'] ?? []),
    )));
    sort($categories);

    $deltaByCategory = [];
    $totalChangedTopics = 0;

    foreach ($categories as $categoryCode) {
        $currentCounts = $report['current_topic_counts'][$categoryCode] ?? [];
        $proposedCounts = $report['proposed_topic_counts'][$categoryCode] ?? [];
        $topicKeys = array_values(array_unique(array_merge(array_keys($currentCounts), array_keys($proposedCounts))));
        sort($topicKeys);

        foreach ($topicKeys as $topicKey) {
            $currentCount = (int) ($currentCounts[$topicKey] ?? 0);
            $proposedCount = (int) ($proposedCounts[$topicKey] ?? 0);
            $delta = $proposedCount - $currentCount;

            if ($delta === 0) {
                continue;
            }

            $deltaByCategory[$categoryCode][] = [
                'topic_key' => $topicKey,
                'topic_label' => $topicKey === 'unassigned'
                    ? 'Nieprzypisany'
                    : $questionTopicClassifier->displayLabelForKey($topicKey),
                'current_count' => $currentCount,
                'proposed_count' => $proposedCount,
                'delta' => $delta,
            ];
            $totalChangedTopics++;
        }
    }

    $payload = [
        'generated_at' => now()->toIso8601String(),
        'scope' => $report['scope'],
        'category_filter' => $report['category_filter'],
        'limit' => $report['limit'],
        'processed_questions' => $report['processed_questions'],
        'changed_questions' => $report['changed_questions'],
        'changed_topic_rows' => $totalChangedTopics,
        'delta_by_category' => $deltaByCategory,
    ];

    $reportOption = trim((string) ($this->option('json') ?? ''));
    $defaultReportPath = base_path(sprintf(
        'output/analysis/pj360-category-consistency/topic-assignment-delta-%s.json',
        str_replace('_', '-', (string) $report['scope']),
    ));
    $resolvedReportPath = $reportOption !== ''
        ? (
            Str::startsWith($reportOption, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $reportOption)
                ? $reportOption
                : base_path($reportOption)
        )
        : $defaultReportPath;

    File::ensureDirectoryExists(dirname($resolvedReportPath));
    File::put($resolvedReportPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->info(sprintf(
        'Raport delty tematow zakonczony. Przetworzono=%d, changed_questions=%d, changed_topic_rows=%d.',
        (int) $report['processed_questions'],
        (int) $report['changed_questions'],
        $totalChangedTopics,
    ));

    if ($categoryFilter !== []) {
        $this->comment(sprintf('Zakres kategorii: %s', implode(', ', $categoryFilter)));
    }

    $this->comment(sprintf('Raport delty: %s', $resolvedReportPath));

    return self::SUCCESS;
})->purpose('Generate a before/after topic-count delta report from the audit-only assignment flow without mutating question assignments.');

Artisan::command('ops:backup-db {--connection= : Nazwa polaczenia bazy danych} {--no-prune : Pomin retencje po wykonaniu backupu} {--label= : Dodatkowa etykieta dla backupu}', function (DatabaseBackupService $databaseBackupService) {
    $manifest = $databaseBackupService->backup(
        connectionName: $this->option('connection') ?: null,
        prune: ! (bool) $this->option('no-prune'),
        label: $this->option('label') ?: null,
    );

    $this->info(sprintf(
        'Backup bazy zakonczony sukcesem. Driver: %s, path: %s, bytes: %d',
        (string) $manifest['driver'],
        (string) $manifest['backup_path'],
        (int) $manifest['bytes'],
    ));
    $this->comment(sprintf('Manifest: %s', (string) $manifest['manifest_path']));
})->purpose('Create a compressed database backup and upload it to the configured backup disk.');

Artisan::command('ops:list-db-backups {--limit=10 : Maksymalna liczba backupow do pokazania}', function (DatabaseBackupService $databaseBackupService) {
    $limit = max((int) $this->option('limit'), 1);
    $backups = $databaseBackupService->recentBackups($limit);

    if ($backups->isEmpty()) {
        $this->warn('Brak backupow na skonfigurowanym dysku.');

        return self::SUCCESS;
    }

    $this->table(
        ['Created At', 'Connection', 'Driver', 'Bytes', 'Backup Path', 'Manifest'],
        $backups->map(fn (array $backup): array => [
            (string) ($backup['created_at'] ?? '-'),
            (string) ($backup['connection'] ?? '-'),
            (string) ($backup['driver'] ?? '-'),
            number_format((int) ($backup['bytes'] ?? 0), 0, ',', ' '),
            (string) ($backup['backup_path'] ?? '-'),
            (string) ($backup['manifest_path'] ?? '-'),
        ])->all(),
    );

    return self::SUCCESS;
})->purpose('List recent database backups from the configured backup disk.');

Artisan::command('ops:restore-db {reference : Sciezka backupu albo manifestu na skonfigurowanym dysku} {--connection= : Nazwa polaczenia bazy danych} {--backup-current : Zrob snapshot obecnego stanu przed restore} {--force : Potwierdz destrukcyjna operacje restore}', function (DatabaseBackupService $databaseBackupService) {
    if (! (bool) $this->option('force')) {
        $this->error('Restore wymaga opcji --force.');

        return self::FAILURE;
    }

    $result = $databaseBackupService->restore(
        reference: (string) $this->argument('reference'),
        connectionName: $this->option('connection') ?: null,
        backupCurrent: (bool) $this->option('backup-current'),
    );

    $this->info(sprintf(
        'Restore zakonczony sukcesem. Connection: %s, source: %s',
        (string) $result['connection'],
        (string) ($result['restored_from']['backup_path'] ?? ''),
    ));

    if (is_array($result['snapshot'] ?? null)) {
        $this->comment(sprintf(
            'Snapshot obecnego stanu zapisano jako: %s',
            (string) ($result['snapshot']['backup_path'] ?? ''),
        ));
    }

    return self::SUCCESS;
})->purpose('Restore the configured database from a backup stored on the configured backup disk.');

Artisan::command('ops:health-report {--json : Zwracaj wynik jako JSON}', function (HealthCheckService $healthCheckService) {
    $report = $healthCheckService->report();

    if ((bool) $this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $report['status'] === 'failed' ? self::FAILURE : self::SUCCESS;
    }

    $this->info(sprintf('Overall status: %s', strtoupper((string) $report['status'])));
    $this->line(sprintf(
        'Database: %s (%d ms)',
        strtoupper((string) ($report['checks']['database']['status'] ?? 'unknown')),
        (int) ($report['checks']['database']['latency_ms'] ?? 0),
    ));
    $this->line(sprintf(
        'Backup: %s (last: %s, age_hours: %s)',
        strtoupper((string) ($report['checks']['backup']['status'] ?? 'unknown')),
        (string) ($report['checks']['backup']['last_backup_at'] ?? 'none'),
        (string) (($report['checks']['backup']['age_hours'] ?? 'n/a')),
    ));

    return $report['status'] === 'failed' ? self::FAILURE : self::SUCCESS;
})->purpose('Show a compact health report for monitoring and operational checks.');

Artisan::command('ops:test-mail {to : Adres odbiorcy testowego maila} {--subject= : Opcjonalny temat maila}', function () {
    $to = trim((string) $this->argument('to'));
    $validator = Validator::make(['to' => $to], [
        'to' => ['required', 'email'],
    ]);

    if ($validator->fails()) {
        $this->error('Podaj poprawny adres email odbiorcy.');

        return self::FAILURE;
    }

    $mailer = (string) config('mail.default');

    if (in_array($mailer, ['log', 'array'], true)) {
        $this->error(sprintf('MAIL_MAILER=%s nie wysyla prawdziwych maili.', $mailer));
        $this->comment('Ustaw produkcyjny mailer SMTP, a potem uruchom te komende ponownie.');

        return self::FAILURE;
    }

    $subject = trim((string) ($this->option('subject') ?: 'Test mail z prawkonaraz.pl'));
    $fromAddress = (string) config('mail.from.address');
    $fromName = (string) config('mail.from.name');
    $smtpConfig = config('mail.mailers.smtp', []);

    $this->line(sprintf('Mailer: %s', $mailer));
    $this->line(sprintf('SMTP host: %s', (string) ($smtpConfig['host'] ?? '')));
    $this->line(sprintf('SMTP port: %s', (string) ($smtpConfig['port'] ?? '')));
    $this->line(sprintf('SMTP scheme: %s', (string) ($smtpConfig['scheme'] ?? '')));
    $this->line(sprintf('From: %s <%s>', $fromName, $fromAddress));

    try {
        Mail::raw(
            implode(PHP_EOL, [
                'To jest testowa wiadomosc operatorska z aplikacji prawkonaraz.pl.',
                '',
                'Jesli widzisz tego maila, aplikacja potrafi wysylac poczte przez skonfigurowany mailer.',
                'Czas wysylki: '.now()->toIso8601String(),
                'APP_URL: '.config('app.url'),
            ]),
            function ($message) use ($to, $subject): void {
                $message
                    ->to($to)
                    ->subject($subject);
            },
        );
    } catch (Throwable $exception) {
        $this->error('Nie udalo sie wyslac maila testowego.');
        $this->line($exception->getMessage());

        return self::FAILURE;
    }

    $this->info(sprintf('Wyslano mail testowy do %s.', $to));

    return self::SUCCESS;
})->purpose('Send a real test email through the configured application mailer.');

Artisan::command('ops:assert-backup-fresh {--max-age-hours= : Nadpisz domyslny prog swiezosci backupu}', function (DatabaseBackupService $databaseBackupService) {
    $override = $this->option('max-age-hours');
    $health = $databaseBackupService->backupHealth($override !== null ? (int) $override : null);

    if ($health['status'] !== 'ok') {
        $this->error(sprintf(
            'Backup nie jest swiezy. Status: %s, last_backup_at: %s, age_hours: %s',
            (string) $health['status'],
            (string) ($health['last_backup_at'] ?? 'none'),
            (string) ($health['age_hours'] ?? 'n/a'),
        ));

        return self::FAILURE;
    }

    $this->info(sprintf(
        'Backup jest swiezy. Last backup: %s, age_hours: %s',
        (string) ($health['last_backup_at'] ?? 'unknown'),
        (string) ($health['age_hours'] ?? '0'),
    ));

    return self::SUCCESS;
})->purpose('Fail when the latest database backup is missing, stale or unreadable.');

Artisan::command('ops:seed-smoke-data', function (SmokeDataProvisioner $smokeDataProvisioner) {
    $report = $smokeDataProvisioner->provision();

    $this->info('Smoke dataset gotowy.');
    $this->line(sprintf(
        'Kategoria: %s (%s) | Disk: %s',
        (string) ($report['category']['code'] ?? 'B'),
        (string) ($report['category']['action'] ?? 'updated'),
        (string) ($report['disk'] ?? 'public'),
    ));
    $this->line(sprintf(
        'Pytania: %d | Media: %d',
        count((array) ($report['questions'] ?? [])),
        count((array) ($report['media'] ?? [])),
    ));

    foreach ((array) ($report['assets'] ?? []) as $asset) {
        if (! is_array($asset)) {
            continue;
        }

        $this->comment(sprintf(
            'Asset: %s (%d bytes)',
            (string) ($asset['path'] ?? 'unknown'),
            (int) ($asset['bytes'] ?? 0),
        ));
    }

    return self::SUCCESS;
})->purpose('Provision deterministic smoke data and placeholder assets for CI and local verification.');

Artisan::command('ops:perf-smoke {--json : Zwracaj wynik jako JSON} {--assert : Failuj, gdy benchmark przekroczy prog} {--max-session-start-ms= : Nadpisz prog dla tworzenia sesji} {--max-first-answer-ms= : Nadpisz prog dla pierwszej odpowiedzi} {--max-session-complete-ms= : Nadpisz prog dla finalizacji sesji} {--max-dashboard-ms= : Nadpisz prog dla dashboardu}', function (PerformanceSmokeService $performanceSmokeService) {
    $thresholds = array_filter([
        'session_start_ms' => $this->option('max-session-start-ms'),
        'first_answer_ms' => $this->option('max-first-answer-ms'),
        'session_complete_ms' => $this->option('max-session-complete-ms'),
        'dashboard_ms' => $this->option('max-dashboard-ms'),
    ], fn ($value) => $value !== null);

    $report = $performanceSmokeService->run(
        thresholds: $thresholds,
        assertThresholds: (bool) $this->option('assert'),
    );

    if ((bool) $this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $report['status'] === 'failed' ? self::FAILURE : self::SUCCESS;
    }

    $this->info(sprintf(
        'Performance smoke status: %s',
        strtoupper((string) $report['status']),
    ));

    foreach ((array) ($report['benchmarks'] ?? []) as $benchmark) {
        if (! is_array($benchmark)) {
            continue;
        }

        $statusLabel = match ($benchmark['status'] ?? null) {
            'ok' => 'OK',
            default => 'FAIL',
        };

        $line = sprintf(
            '[%s] %s - %s',
            $statusLabel,
            (string) ($benchmark['name'] ?? 'unknown'),
            (string) ($benchmark['message'] ?? ''),
        );

        match ($benchmark['status'] ?? null) {
            'ok' => $this->info($line),
            default => $this->error($line),
        };

        if (isset($benchmark['duration_ms'])) {
            $this->comment(sprintf(
                '  duration=%.2fms threshold=%.2fms queries=%d memory_delta_kb=%.2f',
                (float) ($benchmark['duration_ms'] ?? 0),
                (float) ($benchmark['threshold_ms'] ?? 0),
                (int) ($benchmark['query_count'] ?? 0),
                (float) ($benchmark['memory_delta_kb'] ?? 0),
            ));
        }
    }

    return $report['status'] === 'failed' ? self::FAILURE : self::SUCCESS;
})->purpose('Run a lightweight performance smoke benchmark for session and dashboard flows.');

Artisan::command('ops:smoke-test {--require-media : Wymagaj co najmniej jednego obrazu i jednego wideo w smoke tescie} {--json : Zwracaj wynik jako JSON}', function (SmokeTestService $smokeTestService) {
    $report = $smokeTestService->run(
        requireMedia: (bool) $this->option('require-media'),
    );

    if ((bool) $this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $report['status'] === 'failed' ? self::FAILURE : self::SUCCESS;
    }

    $this->info(sprintf(
        'Smoke test status: %s',
        strtoupper((string) $report['status']),
    ));

    foreach ($report['checks'] as $check) {
        $statusLabel = match ($check['status']) {
            'ok' => 'OK',
            'skipped' => 'SKIP',
            default => 'FAIL',
        };

        $line = sprintf(
            '[%s] %s - %s',
            $statusLabel,
            (string) $check['name'],
            (string) $check['message'],
        );

        match ($check['status']) {
            'ok' => $this->info($line),
            'skipped' => $this->warn($line),
            default => $this->error($line),
        };
    }

    return $report['status'] === 'failed' ? self::FAILURE : self::SUCCESS;
})->purpose('Run a compact post-deploy smoke test for public APIs, session flow and optional media assets.');

Artisan::command('ops:expire-friend-invitations {--json : Zwracaj wynik jako JSON}', function (FriendInvitationService $friendInvitationService) {
    $result = $friendInvitationService->expireStale();

    if ((bool) $this->option('json')) {
        $this->line(json_encode($result, JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    $this->info('Friend invitation expiry completed.');
    $this->line("Pending expired: {$result['pending_expired']}");
    $this->line("Guest access expired: {$result['guest_access_expired']}");

    return self::SUCCESS;
})->purpose('Expire stale friend invitations and guest access grants.');

$backupSettings = app(BackupSettingsService::class)->apply();
$backupScheduleFrequency = (string) ($backupSettings['schedule']['frequency'] ?? 'weekly');
$backupScheduleTime = (string) ($backupSettings['schedule']['at'] ?? '02:15');
$backupScheduleDay = (int) ($backupSettings['schedule']['day_of_week'] ?? 0);

$backupSchedule = Schedule::command('ops:backup-db')
    ->environments(['production'])
    ->withoutOverlapping();

if ($backupScheduleFrequency === 'weekly') {
    $backupSchedule->weeklyOn($backupScheduleDay, $backupScheduleTime);
} else {
    $backupSchedule->dailyAt($backupScheduleTime);
}

Schedule::command('ops:assert-backup-fresh')
    ->hourly()
    ->environments(['production'])
    ->withoutOverlapping();

Artisan::command('ops:prune-user-ip-history {--days=90 : Zachowaj logi z ostatnich N dni}', function () {
    $days = max((int) $this->option('days'), 1);
    $cutoff = now()->subDays($days);

    $deleted = UserIpHistory::query()
        ->where('last_seen_at', '<', $cutoff)
        ->delete();

    $this->info(sprintf(
        'Usunieto %d wpisow historii IP starszych niz %d dni.',
        $deleted,
        $days,
    ));

    return self::SUCCESS;
})->purpose('Prune stale user IP history entries.');

Artisan::command('ops:prune-study-history {--days=90 : Zachowaj zakonczone sesje z ostatnich N dni} {--abandoned-days=7 : Usun porzucone sesje in_progress starsze niz N dni}', function () {
    $completedDays = max((int) ($this->option('days') ?: config('study.completed_session_retention_days', 90)), 1);
    $abandonedDays = max((int) ($this->option('abandoned-days') ?: config('study.abandoned_session_retention_days', 7)), 1);

    $completedCutoff = now()->subDays($completedDays);
    $abandonedCutoff = now()->subDays($abandonedDays);

    $deleteInChunks = function (Builder $query): int {
        $deleted = 0;

        do {
            $ids = (clone $query)
                ->orderBy('id')
                ->limit(500)
                ->pluck('id')
                ->all();

            if ($ids === []) {
                break;
            }

            $answerIds = StudySessionAnswer::query()
                ->whereIn('study_session_id', $ids)
                ->pluck('id')
                ->all();

            if ($answerIds !== []) {
                ReviewMemoryProgress::query()
                    ->whereIn('last_study_session_answer_id', $answerIds)
                    ->update(['last_study_session_answer_id' => null]);

                ReviewTrainerDailyAnswer::query()
                    ->whereIn('study_session_answer_id', $answerIds)
                    ->delete();

                StudySessionAnswer::query()
                    ->whereKey($answerIds)
                    ->delete();
            }

            ReviewTrainerEvent::query()
                ->whereIn('study_session_id', $ids)
                ->update(['study_session_id' => null]);

            $deleted += StudySession::query()
                ->whereKey($ids)
                ->delete();
        } while (true);

        return $deleted;
    };

    $completedDeleted = $deleteInChunks(
        StudySession::query()
            ->where('status', 'completed')
            ->where(function ($query) use ($completedCutoff): void {
                $query
                    ->where('completed_at', '<', $completedCutoff)
                    ->orWhere(function ($innerQuery) use ($completedCutoff): void {
                        $innerQuery
                            ->whereNull('completed_at')
                            ->where('created_at', '<', $completedCutoff);
                    });
            })
    );

    $abandonedDeleted = $deleteInChunks(
        StudySession::query()
            ->where('status', 'in_progress')
            ->where(function ($query) use ($abandonedCutoff): void {
                $query
                    ->where('updated_at', '<', $abandonedCutoff)
                    ->orWhere(function ($innerQuery) use ($abandonedCutoff): void {
                        $innerQuery
                            ->whereNull('updated_at')
                            ->where('started_at', '<', $abandonedCutoff);
                    })
                    ->orWhere(function ($innerQuery) use ($abandonedCutoff): void {
                        $innerQuery
                            ->whereNull('updated_at')
                            ->whereNull('started_at')
                            ->where('created_at', '<', $abandonedCutoff);
                    });
            })
    );

    $this->info(sprintf(
        'Usunieto %d zakonczonych sesji starszych niz %d dni oraz %d porzuconych sesji in_progress starszych niz %d dni.',
        $completedDeleted,
        $completedDays,
        $abandonedDeleted,
        $abandonedDays,
    ));

    Cache::forever('ops.study_history_prune.last_run_at', now()->toIso8601String());
    Cache::forever('ops.study_history_prune.last_deleted_completed', $completedDeleted);
    Cache::forever('ops.study_history_prune.last_deleted_abandoned', $abandonedDeleted);

    return self::SUCCESS;
})->purpose('Prune old completed study sessions and abandoned in-progress sessions.');

Artisan::command('ops:aggregate-question-daily-stats {--date= : Zbuduj agregaty dla konkretnego dnia YYYY-MM-DD} {--days=3 : Gdy nie podano daty, przebuduj ostatnie N dni}', function (QuestionDailyStatsAggregator $aggregator) {
    $dateOption = trim((string) ($this->option('date') ?? ''));
    $days = max((int) ($this->option('days') ?: 3), 1);
    $dates = collect();

    if ($dateOption !== '') {
        $dates = collect([Carbon::parse($dateOption)->startOfDay()]);
    } else {
        $dates = collect(range(0, $days - 1))
            ->map(fn (int $offset) => now()->subDays($offset)->startOfDay())
            ->sort();
    }

    $totalRows = 0;
    $lastAggregatedDate = null;

    foreach ($dates as $date) {
        $result = $aggregator->aggregateDay($date);
        $totalRows += $result['rows'];
        $lastAggregatedDate = $result['date'];

        $this->info(sprintf(
            'Zbudowano %d rekordow dziennych statystyk dla %s.',
            $result['rows'],
            $result['date'],
        ));
    }

    Cache::forever('ops.question_daily_stats.last_run_at', now()->toIso8601String());
    Cache::forever('ops.question_daily_stats.last_rows', $totalRows);
    Cache::forever('ops.question_daily_stats.last_aggregated_date', $lastAggregatedDate);

    return self::SUCCESS;
})->purpose('Build daily question analytics aggregates from raw study history.');

Artisan::command('ops:aggregate-question-answer-daily-stats {--date= : Zbuduj agregaty odpowiedzi dla konkretnego dnia YYYY-MM-DD} {--days=3 : Gdy nie podano daty, przebuduj ostatnie N dni}', function (QuestionAnswerDailyStatsAggregator $aggregator) {
    $startedAt = microtime(true);
    $dateOption = trim((string) ($this->option('date') ?? ''));
    $days = max((int) ($this->option('days') ?: 3), 1);
    $dates = collect();

    if ($dateOption !== '') {
        $dates = collect([Carbon::parse($dateOption)->startOfDay()]);
    } else {
        $dates = collect(range(0, $days - 1))
            ->map(fn (int $offset) => now()->subDays($offset)->startOfDay())
            ->sort();
    }

    $totalRows = 0;
    $lastAggregatedDate = null;

    try {
        foreach ($dates as $date) {
            $result = $aggregator->aggregateDay($date);
            $totalRows += $result['rows'];
            $lastAggregatedDate = $result['date'];

            $this->info(sprintf(
                'Zbudowano %d rekordow dziennych statystyk odpowiedzi dla %s.',
                $result['rows'],
                $result['date'],
            ));
        }

        Cache::forever('ops.question_answer_daily_stats.last_run_at', now()->toIso8601String());
        Cache::forever('ops.question_answer_daily_stats.last_rows', $totalRows);
        Cache::forever('ops.question_answer_daily_stats.last_aggregated_date', $lastAggregatedDate);
        Cache::forever('ops.question_answer_daily_stats.last_duration_ms', (int) round((microtime(true) - $startedAt) * 1000));
        Cache::forget('ops.question_answer_daily_stats.last_error');
    } catch (Throwable $exception) {
        Cache::forever('ops.question_answer_daily_stats.last_error', $exception->getMessage());

        throw $exception;
    }

    return self::SUCCESS;
})->purpose('Build daily selected-answer aggregates for public question statistics.');

Artisan::command('ops:rollup-question-monthly-stats {--before= : Zwiń dzienne agregaty starsze niż ta data YYYY-MM-DD do archiwum miesięcznego}', function (QuestionMonthlyStatsRollupService $rollupService) {
    $cutoffDate = trim((string) ($this->option('before') ?? '')) !== ''
        ? Carbon::parse((string) $this->option('before'))->startOfDay()
        : now()->subDays(max((int) config('study.question_daily_stats_retention_days', 365), 1))->startOfDay();

    $result = $rollupService->rollupBefore($cutoffDate);

    $this->info(sprintf(
        'Zwinięto %d miesięcy do %d rekordów miesięcznych i usunięto %d dziennych agregatów starszych niż %s.',
        $result['months'],
        $result['rows'],
        $result['deleted_daily_rows'],
        $cutoffDate->toDateString(),
    ));

    Cache::forever('ops.question_monthly_rollup.last_run_at', now()->toIso8601String());
    Cache::forever('ops.question_monthly_rollup.last_months', $result['months']);
    Cache::forever('ops.question_monthly_rollup.last_rows', $result['rows']);
    Cache::forever('ops.question_monthly_rollup.last_deleted_daily_rows', $result['deleted_daily_rows']);
    Cache::forever('ops.question_monthly_rollup.last_month', $result['last_month']);
    Cache::forever('ops.question_monthly_rollup.last_cutoff_date', $cutoffDate->toDateString());

    return self::SUCCESS;
})->purpose('Compact old daily question aggregates into monthly archive rows.');

Artisan::command('ops:capture-monitoring-snapshot {--date= : Zapisz snapshot dla konkretnego dnia YYYY-MM-DD} {--days=1 : Gdy nie podano daty, zapisz ostatnie N dni snapshotów}', function (MonitoringSnapshotService $snapshotService) {
    $dateOption = trim((string) ($this->option('date') ?? ''));

    if ($dateOption !== '') {
        $snapshot = $snapshotService->captureDay(Carbon::parse($dateOption)->startOfDay());

        $this->info(sprintf(
            'Zapisano snapshot monitoringu dla %s.',
            (string) $snapshot['snapshot_date'],
        ));

        return self::SUCCESS;
    }

    $days = max((int) ($this->option('days') ?: 1), 1);
    $result = $snapshotService->captureRecentDays($days);

    $this->info(sprintf(
        'Zapisano %d snapshotów monitoringu. Ostatni dzień: %s.',
        $result['rows'],
        (string) ($result['last_date'] ?? 'brak'),
    ));

    return self::SUCCESS;
})->purpose('Store cumulative monitoring snapshots for charts and growth tracking.');

Artisan::command('ops:sqlite-maintenance {--vacuum : Run full VACUUM after optimize}', function () {
    if (config('database.default') !== 'sqlite') {
        $this->comment('Pomijam maintenance SQLite, bo aktywne polaczenie nie uzywa sqlite.');

        return self::SUCCESS;
    }

    DB::statement('PRAGMA optimize');
    $this->info('Wykonano PRAGMA optimize.');

    if ((bool) $this->option('vacuum')) {
        DB::unprepared('VACUUM');
        $this->info('Wykonano VACUUM.');
    }

    Cache::forever('ops.sqlite_maintenance.last_run_at', now()->toIso8601String());
    Cache::forever(
        'ops.sqlite_maintenance.last_details',
        (bool) $this->option('vacuum')
            ? 'Wykonano PRAGMA optimize oraz VACUUM.'
            : 'Wykonano PRAGMA optimize.',
    );

    return self::SUCCESS;
})->purpose('Optimize SQLite and optionally reclaim free space with VACUUM.');

Artisan::command('pj360:stage-explanation-drafts {path=output/analysis/pj360-compare/publish-candidates/publish-candidates.json : Sciezka do pliku JSON z kandydatami publikacji PJ360} {--source=pj360 : Nazwa zrodla stagingu} {--reset : Usun istniejace wpisy stagingu dla tego zrodla przed zaladowaniem}', function (QuestionExplanationDraftStagingService $stagingService) {
    $inputPath = (string) $this->argument('path');
    $resolvedPath = File::exists($inputPath) ? $inputPath : base_path($inputPath);

    if (! File::exists($resolvedPath)) {
        $this->error(sprintf('Nie znaleziono pliku z kandydatami: %s', $inputPath));

        return self::FAILURE;
    }

    $payload = json_decode((string) File::get($resolvedPath), true);

    if (! is_array($payload)) {
        $this->error('Plik kandydatow PJ360 ma niepoprawny format JSON.');

        return self::FAILURE;
    }

    $summary = $stagingService->stage(
        $payload,
        (string) $this->option('source'),
        (bool) $this->option('reset'),
    );

    $this->info(sprintf('Zaladowano kandydatow: %d', $summary['candidate_total']));
    $this->line(sprintf('Publish-ready: %d', $summary['publish_ready_total']));
    $this->line(sprintf('Staged: %d', $summary['staged_count']));
    $this->line(sprintf('Konflikty stagingu: %d', $summary['conflict_count']));
    $this->line(sprintf('Pominiete (publish_ready=false): %d', $summary['skipped_not_publish_ready_count']));
    $this->line(sprintf('Wpisy z istniejacymi wyjasnieniami lokalnymi: %d', $summary['entries_with_existing_explanations']));
    $this->line(sprintf('Docelowe lokalne rekordy pytan: %d', $summary['target_question_count']));
    $this->line(sprintf('Docelowe rekordy z juz wypelnionym explanation: %d', $summary['targeted_questions_with_existing_explanations']));

    return self::SUCCESS;
})->purpose('Load PJ360 publish candidates into a safe staging table without touching questions.explanation.');

Artisan::command('pj360:explanation-draft-summary {--source=pj360 : Nazwa zrodla stagingu} {--json= : Opcjonalna sciezka do pliku JSON z podsumowaniem}', function (QuestionExplanationDraftStagingService $stagingService) {
    $summary = $stagingService->summarize((string) $this->option('source'));

    $this->info(sprintf('Staging source: %s', $summary['source']));
    $this->line(sprintf('Liczba wpisow: %d', $summary['total_entries']));
    $this->line(sprintf('Rekordy lokalne w zasiegu: %d', $summary['target_question_count']));
    $this->line(sprintf('Rekordy lokalne z istniejacym explanation: %d', $summary['targeted_questions_with_existing_explanations']));
    $this->line('Statusy: '.json_encode($summary['status_counts'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $this->line('Kolejki: '.json_encode($summary['source_queue_counts'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $outputPath = trim((string) $this->option('json'));

    if ($outputPath !== '') {
        $resolvedPath = str_starts_with($outputPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $outputPath) === 1
            ? $outputPath
            : base_path($outputPath);

        File::ensureDirectoryExists(dirname($resolvedPath));
        File::put($resolvedPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line(sprintf('Zapisano summary: %s', $resolvedPath));
    }

    return self::SUCCESS;
})->purpose('Show a summary of the PJ360 explanation draft staging table.');

Artisan::command('pj360:apply-explanation-drafts {--source=pj360 : Nazwa zrodla stagingu} {--external-id=* : Ogranicz publikacje do wybranych external_id} {--limit= : Ogranicz liczbe wpisow stagingu w tym przebiegu} {--overwrite-existing : Nadpisz istniejace questions.explanation} {--write : Faktycznie zapisz drafty do questions.explanation; bez tej opcji komenda robi tylko preview} {--json= : Opcjonalna sciezka do pliku JSON z raportem}', function (QuestionExplanationDraftApplyService $applyService) {
    $limitOption = trim((string) ($this->option('limit') ?? ''));
    $limit = $limitOption !== '' ? max((int) $limitOption, 1) : null;

    $summary = $applyService->apply(
        source: (string) $this->option('source'),
        write: (bool) $this->option('write'),
        overwriteExisting: (bool) $this->option('overwrite-existing'),
        limit: $limit,
        externalIds: array_values(array_filter((array) $this->option('external-id'))),
    );

    $modeLabel = $summary['write'] ? 'WRITE' : 'PREVIEW';

    $this->info(sprintf('Tryb: %s', $modeLabel));
    $this->line(sprintf('Kandydaci: %d', $summary['candidate_entries']));
    $this->line(sprintf('Wpisy w pelni stosowalne: %d', $summary['entries_fully_applicable']));
    $this->line(sprintf('Wpisy czesciowo stosowalne: %d', $summary['entries_partially_applicable']));
    $this->line(sprintf('Wpisy pominiete przez istniejace explanation: %d', $summary['entries_skipped_existing_only']));
    $this->line(sprintf('Wpisy bez targetow: %d', $summary['entries_without_targets']));
    $this->line(sprintf('Rekordy pytan do aktualizacji: %d', $summary['question_updates_planned']));
    $this->line(sprintf('Rekordy pytan pominiete przez istniejace explanation: %d', $summary['questions_skipped_existing']));
    $this->line(sprintf('Wyjatki na koncu raportu: %d', $summary['exception_count']));

    if ($summary['write']) {
        $this->line(sprintf('Rekordy pytan zaktualizowane: %d', $summary['question_updates_performed']));
    }

    if ($summary['exception_external_ids'] !== []) {
        $this->warn('External ID wyjatkow: '.implode(', ', $summary['exception_external_ids']));
    }

    $outputPath = trim((string) $this->option('json'));

    if ($outputPath !== '') {
        $resolvedPath = str_starts_with($outputPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $outputPath) === 1
            ? $outputPath
            : base_path($outputPath);

        File::ensureDirectoryExists(dirname($resolvedPath));
        File::put($resolvedPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line(sprintf('Zapisano raport: %s', $resolvedPath));
    }

    if (! $summary['write']) {
        $this->warn('To byl tylko preview. Dodaj --write, jesli chcesz faktycznie zapisac drafty do questions.explanation.');
    }

    return self::SUCCESS;
})->purpose('Preview or apply staged PJ360 explanation drafts into questions.explanation with overwrite protection.');

Artisan::command('pj360:apply-exception-resolutions {path=storage/app/manual/pj360-exception-resolutions.json : Sciezka do pliku JSON z recznymi rozstrzygnieciami wyjatkow} {--source=pj360 : Nazwa zrodla stagingu} {--json= : Opcjonalna sciezka do pliku JSON z raportem}', function (QuestionExplanationDraftExceptionResolutionService $resolutionService) {
    $inputPath = (string) $this->argument('path');
    $resolvedPath = File::exists($inputPath) ? $inputPath : base_path($inputPath);

    if (! File::exists($resolvedPath)) {
        $this->error(sprintf('Nie znaleziono pliku z recznymi rozstrzygnieciami: %s', $inputPath));

        return self::FAILURE;
    }

    $payload = json_decode((string) File::get($resolvedPath), true);

    if (! is_array($payload)) {
        $this->error('Plik recznych rozstrzygniec ma niepoprawny format JSON.');

        return self::FAILURE;
    }

    $summary = $resolutionService->apply(
        $payload,
        (string) $this->option('source'),
    );

    $this->info(sprintf('Ręczne rozstrzygniecia: %d', $summary['resolution_count']));
    $this->line(sprintf('Zaktualizowane drafty: %d', $summary['updated_count']));

    if ($summary['updated_external_ids'] !== []) {
        $this->line('External ID zaktualizowane: '.implode(', ', $summary['updated_external_ids']));
    }

    if ($summary['missing_drafts'] !== []) {
        $this->warn('Brakujace drafty: '.implode(', ', $summary['missing_drafts']));
    }

    $outputPath = trim((string) $this->option('json'));

    if ($outputPath !== '') {
        $resolvedOutputPath = str_starts_with($outputPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $outputPath) === 1
            ? $outputPath
            : base_path($outputPath);

        File::ensureDirectoryExists(dirname($resolvedOutputPath));
        File::put($resolvedOutputPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line(sprintf('Zapisano raport: %s', $resolvedOutputPath));
    }

    return self::SUCCESS;
})->purpose('Apply curated manual resolutions for exceptional PJ360 draft entries before final publication.');

Artisan::command('content:backfill-pt-overlap-explanations {--external-id=* : Ogranicz backfill do wskazanych external_id} {--limit= : Ogranicz liczbe external_id w tym przebiegu} {--write : Faktycznie skopiuj wyjasnienia do PT; bez tej opcji komenda robi tylko preview} {--json= : Opcjonalna sciezka do pliku JSON z raportem}', function (QuestionExplanationPtOverlapBackfillService $backfillService) {
    $limitOption = trim((string) ($this->option('limit') ?? ''));
    $limit = $limitOption !== '' ? max((int) $limitOption, 1) : null;

    $summary = $backfillService->apply(
        write: (bool) $this->option('write'),
        limit: $limit,
        externalIds: array_values(array_filter((array) $this->option('external-id'))),
    );

    $this->info(sprintf('Tryb: %s', $summary['write'] ? 'WRITE' : 'PREVIEW'));
    $this->line(sprintf('Kandydaci external_id: %d', $summary['candidate_external_ids']));
    $this->line(sprintf('Kandydaci wierszy PT: %d', $summary['candidate_question_rows']));
    $this->line(sprintf('Zaktualizowane wiersze PT: %d', $summary['updated_question_rows']));
    $this->line(sprintf('Pominiete przez niespojnego dawce: %d', $summary['skipped_inconsistent_donor_count']));
    $this->line(sprintf('Pominiete przez mismatch promptu: %d', $summary['skipped_prompt_mismatch_count']));
    $this->line(sprintf('Pominiete przez mismatch poprawnej odpowiedzi: %d', $summary['skipped_answer_mismatch_count']));

    $outputPath = trim((string) $this->option('json'));

    if ($outputPath !== '') {
        $resolvedPath = str_starts_with($outputPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $outputPath) === 1
            ? $outputPath
            : base_path($outputPath);

        File::ensureDirectoryExists(dirname($resolvedPath));
        File::put($resolvedPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line(sprintf('Zapisano raport: %s', $resolvedPath));
    }

    if (! $summary['write']) {
        $this->warn('To byl tylko preview. Dodaj --write, jesli chcesz skopiowac wyjasnienia do kategorii PT.');
    }

    return self::SUCCESS;
})->purpose('Preview or copy existing shared explanations into PT rows for the same external_id when the donor text is consistent.');

Artisan::command('content:backfill-pt-prompt-match-explanations {--external-id=* : Ogranicz backfill do wskazanych external_id} {--limit= : Ogranicz liczbe external_id w tym przebiegu} {--write : Faktycznie skopiuj wyjasnienia do PT; bez tej opcji komenda robi tylko preview} {--json= : Opcjonalna sciezka do pliku JSON z raportem}', function (QuestionExplanationPtPromptMatchBackfillService $backfillService) {
    $limitOption = trim((string) ($this->option('limit') ?? ''));
    $limit = $limitOption !== '' ? max((int) $limitOption, 1) : null;

    $summary = $backfillService->apply(
        write: (bool) $this->option('write'),
        limit: $limit,
        externalIds: array_values(array_filter((array) $this->option('external-id'))),
    );

    $this->info(sprintf('Tryb: %s', $summary['write'] ? 'WRITE' : 'PREVIEW'));
    $this->line(sprintf('Kandydaci external_id: %d', $summary['candidate_external_ids']));
    $this->line(sprintf('Kandydaci wierszy PT: %d', $summary['candidate_question_rows']));
    $this->line(sprintf('Zaktualizowane wiersze PT: %d', $summary['updated_question_rows']));
    $this->line(sprintf('Pominiete przez brak zgodnego dawcy: %d', $summary['skipped_no_matching_donor_count']));
    $this->line(sprintf('Pominiete przez niespojnego dawce: %d', $summary['skipped_inconsistent_donor_count']));

    $outputPath = trim((string) $this->option('json'));

    if ($outputPath !== '') {
        $resolvedPath = str_starts_with($outputPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $outputPath) === 1
            ? $outputPath
            : base_path($outputPath);

        File::ensureDirectoryExists(dirname($resolvedPath));
        File::put($resolvedPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line(sprintf('Zapisano raport: %s', $resolvedPath));
    }

    if (! $summary['write']) {
        $this->warn('To byl tylko preview. Dodaj --write, jesli chcesz skopiowac wyjasnienia do PT po exact match promptu, odpowiedzi i medium.');
    }

    return self::SUCCESS;
})->purpose('Preview or copy shared explanations into PT rows by exact prompt, answer and primary media kind when the donor text is consistent.');

Artisan::command('content:apply-manual-explanation-resolutions {path=storage/app/manual/question-explanation-manual-resolutions.json : Sciezka do pliku JSON z recznymi wyjasnieniami} {--overwrite-existing : Nadpisz istniejace wyjasnienia} {--json= : Opcjonalna sciezka do pliku JSON z raportem}', function (QuestionExplanationManualResolutionService $manualResolutionService) {
    $inputPath = (string) $this->argument('path');
    $resolvedPath = File::exists($inputPath) ? $inputPath : base_path($inputPath);

    if (! File::exists($resolvedPath)) {
        $this->error(sprintf('Nie znaleziono pliku z recznymi wyjasnieniami: %s', $inputPath));

        return self::FAILURE;
    }

    $payload = json_decode((string) File::get($resolvedPath), true);

    if (! is_array($payload)) {
        $this->error('Plik recznych wyjasnien ma niepoprawny format JSON.');

        return self::FAILURE;
    }

    $summary = $manualResolutionService->apply(
        $payload,
        (bool) $this->option('overwrite-existing'),
    );

    $this->info(sprintf('Przetworzone wpisy: %d', $summary['resolution_count']));
    $this->line(sprintf('Zaktualizowane external_id: %d', $summary['updated_external_id_count']));
    $this->line(sprintf('Zaktualizowane wiersze pytan: %d', $summary['updated_question_row_count']));
    $this->line(sprintf('Brakujace external_id: %d', $summary['missing_external_id_count']));

    if ($summary['updated_external_ids'] !== []) {
        $this->line('Zaktualizowane external_id: '.implode(', ', $summary['updated_external_ids']));
    }

    if ($summary['missing_external_ids'] !== []) {
        $this->warn('Brakujace external_id: '.implode(', ', $summary['missing_external_ids']));
    }

    $outputPath = trim((string) $this->option('json'));

    if ($outputPath !== '') {
        $resolvedOutputPath = str_starts_with($outputPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $outputPath) === 1
            ? $outputPath
            : base_path($outputPath);

        File::ensureDirectoryExists(dirname($resolvedOutputPath));
        File::put($resolvedOutputPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line(sprintf('Zapisano raport: %s', $resolvedOutputPath));
    }

    return self::SUCCESS;
})->purpose('Apply curated manual explanations to questions by external_id.');

Schedule::command('ops:prune-user-ip-history')
    ->dailyAt((string) env('USER_IP_HISTORY_PRUNE_AT', '03:05'))
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('ops:aggregate-question-daily-stats')
    ->dailyAt((string) env('QUESTION_DAILY_STATS_AGGREGATE_AT', '02:55'))
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('ops:aggregate-question-answer-daily-stats')
    ->dailyAt((string) env('QUESTION_ANSWER_DAILY_STATS_AGGREGATE_AT', '02:57'))
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('ops:capture-monitoring-snapshot')
    ->dailyAt((string) env('MONITORING_SNAPSHOT_AT', '03:00'))
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('ops:rollup-question-monthly-stats')
    ->dailyAt((string) env('QUESTION_MONTHLY_STATS_ROLLUP_AT', '03:10'))
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('ops:prune-study-history')
    ->dailyAt((string) env('STUDY_HISTORY_PRUNE_AT', '03:20'))
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('seo:refresh-sitemaps')
    ->dailyAt((string) env('SEO_SITEMAP_REFRESH_AT', '03:30'))
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('seo:monitor-question-relation-v2-shadow --report=storage/app/seo-audits/question-relation-v2-shadow-latest.json --fail-on-errors')
    ->dailyAt((string) config('question_relations.v2_shadow_monitor_at', '04:00'))
    ->environments(['production'])
    ->when(fn (): bool => (bool) config('question_relations.v2_enabled', false)
        && (bool) config('question_relations.v2_shadow_enabled', false))
    ->withoutOverlapping();

Schedule::command('seo:monitor-question-relation-v2-canary --report=storage/app/seo-audits/question-relation-v2-canary-latest.json --fail-on-errors')
    ->dailyAt((string) config('question_relations.v2_canary_monitor_at', '04:15'))
    ->environments(['production'])
    ->when(fn (): bool => (bool) config('question_relations.v2_enabled', false)
        && (bool) config('question_relations.v2_canary_enabled', false))
    ->withoutOverlapping();

Schedule::command('newsroom:publish-due')
    ->everyMinute()
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('seo:indexnow-drain-queue --limit='.(int) config('indexnow.queue_batch_size', 50))
    ->everyTenMinutes()
    ->environments(['production'])
    ->when(fn (): bool => (bool) config('indexnow.automation_enabled', false))
    ->withoutOverlapping();

Schedule::command('ops:expire-friend-invitations')
    ->hourly()
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('ops:sqlite-maintenance')
    ->dailyAt((string) env('SQLITE_OPTIMIZE_AT', '03:35'))
    ->environments(['production'])
    ->withoutOverlapping();

Schedule::command('ops:sqlite-maintenance --vacuum')
    ->weeklyOn(0, (string) env('SQLITE_VACUUM_AT', '04:10'))
    ->environments(['production'])
    ->withoutOverlapping();
