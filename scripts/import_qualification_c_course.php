<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$rootArgument = $argv[1] ?? 'storage/app/qualification-c-full';
$dryRun = in_array('--dry-run', $argv, true);
$root = realpath($rootArgument);

if ($root === false || ! is_dir($root)) {
    fwrite(STDERR, sprintf("Course package not found: %s\n", $rootArgument));

    exit(2);
}

$collectionReportPath = $root.DIRECTORY_SEPARATOR.'collection-report.json';

if (! is_file($collectionReportPath)) {
    fwrite(STDERR, "Collection report is missing.\n");

    exit(2);
}

$collectionReport = json_decode((string) File::get($collectionReportPath), true);

if (
    ! is_array($collectionReport)
    || (int) ($collectionReport['modules'] ?? 0) !== 14
    || (int) ($collectionReport['questionsTotal'] ?? 0) !== 1323
    || (int) ($collectionReport['uniqueExternalIds'] ?? 0) !== 1323
) {
    fwrite(STDERR, "Collection report does not match the accepted 14-module / 1323-question scope.\n");

    exit(2);
}

$moduleIds = [100, 101, 102, 103, 126, 104, 105, 106, 107, 108, 109, 110, 111, 112];
$summary = [
    'dry_run' => $dryRun,
    'modules_total' => count($moduleIds),
    'modules_completed' => 0,
    'questions_total' => 0,
    'questions_created' => 0,
    'questions_updated' => 0,
    'media_total' => 0,
    'media_created' => 0,
    'media_unchanged' => 0,
    'module_assignments_created' => 0,
    'module_assignments_unchanged' => 0,
    'asset_plan_total' => 0,
    'assets_uploaded' => 0,
    'assets_skipped' => 0,
    'errors_count' => 0,
    'modules' => [],
];

foreach ($moduleIds as $moduleId) {
    $manifestPath = $root.DIRECTORY_SEPARATOR."manifest-module-{$moduleId}.json";
    $reportPath = $root.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR."module-{$moduleId}".DIRECTORY_SEPARATOR.($dryRun ? 'dry-run-report.json' : 'import-report.json');

    if (! is_file($manifestPath)) {
        fwrite(STDERR, sprintf("Manifest is missing for module %d.\n", $moduleId));

        exit(2);
    }

    $arguments = [
        'path' => $manifestPath,
        '--report' => $reportPath,
        '--skip-sitemap' => true,
    ];

    if ($dryRun) {
        $arguments['--dry-run'] = true;
    }

    $status = Artisan::call('catalog:import-manifest', $arguments);
    $output = trim((string) Artisan::output());

    if ($output !== '') {
        fwrite(STDOUT, $output.PHP_EOL);
    }

    if ($status !== 0 || ! is_file($reportPath)) {
        fwrite(STDERR, sprintf("Import failed for module %d with status %d.\n", $moduleId, $status));

        exit(1);
    }

    $report = json_decode((string) File::get($reportPath), true);

    if (! is_array($report) || (int) ($report['errors_count'] ?? 0) !== 0) {
        fwrite(STDERR, sprintf("Invalid or unsuccessful report for module %d.\n", $moduleId));

        exit(1);
    }

    $assets = (array) ($report['uploaded_assets'] ?? []);
    $moduleSummary = [
        'module_id' => $moduleId,
        'questions_total' => (int) ($report['questions_total'] ?? 0),
        'questions_created' => (int) ($report['questions_created'] ?? 0),
        'questions_updated' => (int) ($report['questions_updated'] ?? 0),
        'media_total' => (int) ($report['media_total'] ?? 0),
        'media_created' => (int) ($report['media_created'] ?? 0),
        'media_unchanged' => (int) ($report['media_unchanged'] ?? 0),
        'module_assignments_created' => (int) ($report['module_assignments_created'] ?? 0),
        'module_assignments_unchanged' => (int) ($report['module_assignments_unchanged'] ?? 0),
        'asset_plan_total' => (int) ($report['asset_plan_total'] ?? 0),
        'assets_uploaded' => count(array_filter($assets, fn (array $asset): bool => ($asset['status'] ?? null) === 'uploaded')),
        'assets_skipped' => count(array_filter($assets, fn (array $asset): bool => ($asset['status'] ?? null) === 'skipped')),
        'errors_count' => (int) ($report['errors_count'] ?? 0),
    ];

    $summary['modules'][] = $moduleSummary;
    $summary['modules_completed']++;
    $summary['questions_total'] += $moduleSummary['questions_total'];
    $summary['questions_created'] += $moduleSummary['questions_created'];
    $summary['questions_updated'] += $moduleSummary['questions_updated'];
    $summary['media_total'] += $moduleSummary['media_total'];
    $summary['media_created'] += $moduleSummary['media_created'];
    $summary['media_unchanged'] += $moduleSummary['media_unchanged'];
    $summary['module_assignments_created'] += $moduleSummary['module_assignments_created'];
    $summary['module_assignments_unchanged'] += $moduleSummary['module_assignments_unchanged'];
    $summary['asset_plan_total'] += $moduleSummary['asset_plan_total'];
    $summary['assets_uploaded'] += $moduleSummary['assets_uploaded'];
    $summary['assets_skipped'] += $moduleSummary['assets_skipped'];
    $summary['errors_count'] += $moduleSummary['errors_count'];
}

$summaryPath = $root.DIRECTORY_SEPARATOR.($dryRun ? 'full-dry-run-summary.json' : 'full-import-summary.json');
File::put(
    $summaryPath,
    json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
);

fwrite(
    STDOUT,
    json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
);

exit($summary['errors_count'] === 0 && $summary['modules_completed'] === $summary['modules_total'] ? 0 : 1);
