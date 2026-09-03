<?php

namespace App\Console\Commands;

use App\Support\QuestionAudioImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class QuestionAudioImportCommand extends Command
{
    protected $signature = 'questions:audio-import
        {--manifest= : Path to generator result manifest}
        {--input= : Base directory with generated MP3 files}
        {--disk= : Target filesystem disk}
        {--prefix= : Target storage prefix}
        {--force : Overwrite existing audio asset files}
        {--dry-run : Validate manifest without writing files or database rows}
        {--report= : Optional JSON report path}
        {--sample-limit=20 : Number of errors shown in report samples}';

    protected $description = 'Import generated question audio files into storage and question_audio_assets.';

    public function handle(QuestionAudioImportService $importService): int
    {
        $manifestPath = trim((string) ($this->option('manifest') ?? ''));

        if ($manifestPath === '') {
            $this->error('Provide --manifest path.');

            return self::FAILURE;
        }

        try {
            $report = $importService->import($manifestPath, [
                'input' => $this->option('input') ?: null,
                'disk' => $this->option('disk') ?: null,
                'prefix' => $this->option('prefix') ?: null,
                'force' => (bool) $this->option('force'),
                'dry_run' => (bool) $this->option('dry-run'),
                'sample_limit' => (int) $this->option('sample-limit'),
            ]);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info((bool) $report['dry_run'] ? 'Audio import dry-run completed.' : 'Audio import completed.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Manifest', $report['manifest_path']],
                ['Input', $report['input'] ?: '-'],
                ['Disk', $report['disk']],
                ['Prefix', $report['prefix']],
                ['Records', $report['records_total']],
                ['Valid dry-run records', $report['valid_records']],
                ['Imported assets', $report['imported_assets']],
                ['Updated assets', $report['updated_assets']],
                ['Skipped existing assets', $report['skipped_existing_assets']],
                ['Failed generator records', $report['failed_records']],
                ['Errors', $report['errors_count']],
            ],
        );

        $reportPath = trim((string) ($this->option('report') ?? ''));

        if ($reportPath !== '') {
            File::ensureDirectoryExists(dirname($reportPath));
            File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
            $this->line("Report saved: {$reportPath}");
        }

        if ((int) $report['errors_count'] > 0) {
            $this->warn('Some audio records failed validation. Save --report for details before retrying.');
        }

        return (int) $report['errors_count'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
