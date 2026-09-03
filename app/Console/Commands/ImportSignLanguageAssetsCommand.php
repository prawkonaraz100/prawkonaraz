<?php

namespace App\Console\Commands;

use App\Support\PjmSignLanguageDryRunService;
use App\Support\PjmSignLanguageAssetImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportSignLanguageAssetsCommand extends Command
{
    protected $signature = 'pjm:import-sign-language-assets
        {source? : Folder with processed PJM video files}
        {--dry-run : Build a report without writing assets to the database}
        {--write : Import assets into storage and the database}
        {--limit= : Maximum number of parsed assets to import}
        {--force : Update existing records and overwrite stored files}
        {--disk= : Target filesystem disk for write mode}
        {--prefix= : Target storage prefix for write mode}
        {--variant=standard : Asset variant}
        {--processing-profile=safe-center-crop-80 : Processing profile stored on imported assets}
        {--review-report= : CSV report with assets requiring manual review}
        {--report= : Optional path for the JSON report}
        {--sample-limit=20 : Number of ids/files shown in report samples}';

    protected $description = 'Inspect or import PJM sign-language video files.';

    public function handle(
        PjmSignLanguageDryRunService $dryRunService,
        PjmSignLanguageAssetImportService $importService,
    ): int {
        if ((bool) $this->option('dry-run') === (bool) $this->option('write')) {
            $this->error('Choose exactly one mode: --dry-run or --write.');

            return self::FAILURE;
        }

        $source = $this->argument('source') ?: env('PJM_SIGN_LANGUAGE_SOURCE_PATH');

        if (! is_string($source) || trim($source) === '') {
            $this->error('Provide the source folder or set PJM_SIGN_LANGUAGE_SOURCE_PATH.');

            return self::FAILURE;
        }

        if (! File::isDirectory($source)) {
            $this->error("Source folder does not exist: {$source}");

            return self::FAILURE;
        }

        $sampleLimit = (int) $this->option('sample-limit');

        if ($this->option('dry-run')) {
            $report = $dryRunService->buildReport($source, $sampleLimit);
            $this->renderDryRunSummary($report);
        } else {
            $report = $importService->import($source, [
                'disk' => $this->option('disk') ?: config('media.pjm_sign_language_disk', 'media_local'),
                'prefix' => $this->option('prefix') ?: config('media.pjm_sign_language_prefix', 'pjm/sign-language'),
                'variant' => (string) $this->option('variant'),
                'processing_profile' => $this->option('processing-profile') ?: null,
                'limit' => filled($this->option('limit')) ? (int) $this->option('limit') : null,
                'force' => (bool) $this->option('force'),
                'review_report' => $this->option('review-report') ?: null,
                'sample_limit' => $sampleLimit,
            ]);
            $this->renderImportSummary($report);
        }

        if (is_string($this->option('report')) && $this->option('report') !== '') {
            $reportPath = (string) $this->option('report');
            File::ensureDirectoryExists(dirname($reportPath));
            File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
            $this->info("Report saved: {$reportPath}");
        }

        return (int) ($report['errors_count'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderDryRunSummary(array $report): void
    {
        $this->info('PJM dry-run completed. No database changes were made.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Source', Str::limit((string) $report['source_path'], 90)],
                ['Database available', $report['database_available'] ? 'yes' : 'no'],
                ['Files', $report['total_files']],
                ['Parsed files', $report['parsed_files']],
                ['Invalid files', $report['invalid_files_count']],
                ['Unique external ids', $report['unique_external_ids']],
                ['Question assets', $report['question_assets']],
                ['Answer assets', $report['answer_assets']],
                ['Complete A/B/C sets', $report['complete_answer_sets']],
                ['Question-only sets', $report['question_only_sets']],
                ['Duplicates', $report['duplicates_count']],
                ['Matched database ids', $report['matched_external_ids']],
                ['Orphaned ids', $report['orphaned_external_ids']],
            ],
        );

        if (! $report['database_available']) {
            $this->warn('Database comparison was skipped: '.$report['database_error']);
        }

        $coverageRows = collect($report['category_coverage'] ?? [])
            ->map(fn (array $row): array => [
                $row['code'],
                $row['total_questions'],
                $row['pjm_questions'],
                $row['missing_questions'],
                $row['coverage_percent'].'%',
            ])
            ->all();

        if ($coverageRows !== []) {
            $this->table(['Category', 'Questions', 'With PJM', 'Missing', 'Coverage'], $coverageRows);
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderImportSummary(array $report): void
    {
        $this->info('PJM import completed.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Source', Str::limit((string) $report['source_path'], 90)],
                ['Disk', $report['disk']],
                ['Prefix', $report['prefix']],
                ['Variant', $report['variant']],
                ['Limit', $report['limit'] ?? 'none'],
                ['Force', $report['force'] ? 'yes' : 'no'],
                ['Files seen', $report['total_files_seen']],
                ['Parsed files', $report['parsed_files']],
                ['Invalid files', $report['invalid_files_count']],
                ['Imported assets', $report['imported_assets']],
                ['Updated assets', $report['updated_assets']],
                ['Skipped existing assets', $report['skipped_existing_assets']],
                ['Linked existing files', $report['linked_existing_files']],
                ['Review required assets', $report['review_required_assets']],
                ['Errors', $report['errors_count']],
            ],
        );

        if ((int) $report['errors_count'] > 0) {
            $this->warn('Some assets failed. Save --report for details before retrying.');
        }
    }
}
