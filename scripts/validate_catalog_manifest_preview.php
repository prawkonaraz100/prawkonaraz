<?php

declare(strict_types=1);

use App\Support\CatalogManifestImportService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$manifestArgument = $argv[1] ?? null;

if (! is_string($manifestArgument) || trim($manifestArgument) === '') {
    fwrite(STDERR, "Usage: php scripts/validate_catalog_manifest_preview.php <manifest.json> [report.json]\n");

    exit(2);
}

$manifestPath = realpath($manifestArgument);

if ($manifestPath === false || ! is_file($manifestPath)) {
    fwrite(STDERR, sprintf("Manifest not found: %s\n", $manifestArgument));

    exit(2);
}

$reportPath = $argv[2] ?? dirname($manifestPath).DIRECTORY_SEPARATOR.'preview-import-report.json';
$trackedTables = [
    'license_categories',
    'questions',
    'question_media',
    'question_topics',
    'question_collections',
    'question_modules',
    'question_module_question',
    'content_import_runs',
];

$counts = static function () use ($trackedTables): array {
    $result = [];

    foreach ($trackedTables as $table) {
        $result[$table] = DB::table($table)->count();
    }

    return $result;
};

$before = $counts();

/** @var CatalogManifestImportService $service */
$service = $app->make(CatalogManifestImportService::class);
$report = $service->import($manifestPath, true);
$after = $counts();
$stateChanges = [];

foreach ($trackedTables as $table) {
    if ($before[$table] !== $after[$table]) {
        $stateChanges[$table] = [
            'before' => $before[$table],
            'after' => $after[$table],
        ];
    }
}

$report['preview_state_check'] = [
    'status' => $stateChanges === [] ? 'unchanged' : 'changed',
    'tracked_tables' => $trackedTables,
    'changes' => $stateChanges,
];

File::ensureDirectoryExists(dirname($reportPath));
File::put(
    $reportPath,
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
);

fwrite(STDOUT, json_encode([
    'manifest' => $manifestPath,
    'report' => $reportPath,
    'rows_total' => (int) ($report['rows_total'] ?? 0),
    'questions_total' => (int) ($report['questions_total'] ?? 0),
    'media_total' => (int) ($report['media_total'] ?? 0),
    'collections_total' => (int) ($report['collections_total'] ?? 0),
    'modules_total' => (int) ($report['modules_total'] ?? 0),
    'module_assignments_total' => (int) ($report['module_assignments_total'] ?? 0),
    'errors_count' => (int) ($report['errors_count'] ?? 0),
    'database_state' => $report['preview_state_check']['status'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);

exit(
    (int) ($report['errors_count'] ?? 0) === 0 && $stateChanges === []
        ? 0
        : 1
);
