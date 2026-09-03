<?php

namespace App\Console\Commands;

use App\Models\ContentImportRun;
use App\Support\LearningExplanationRuleSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class RollbackPublicMemoryRulesCommand extends Command
{
    protected $signature = 'questions:rollback-public-memory-rules
        {run : Synchronization run ID to inspect or roll back}
        {--write : Restore exact snapshots after a rollback preview}
        {--confirm= : Manifest checksum required with --write}
        {--report= : Optional JSON report path}';

    protected $description = 'Preview or safely roll back a public memory-rule synchronization run';

    public function handle(LearningExplanationRuleSyncService $service): int
    {
        try {
            $write = (bool) $this->option('write');
            $confirmation = trim((string) $this->option('confirm'));

            if ($write && $confirmation === '') {
                $this->error('Rollback zapisu wymaga --confirm=<checksum>.');

                return self::INVALID;
            }

            $result = $service->rollback((int) $this->argument('run'), $confirmation, $write);

            if ($result['run'] instanceof ContentImportRun) {
                $this->writeReport($result['run'], $result['report']);
            } elseif (filled($this->option('report'))) {
                $this->writePreviewReport($result['report']);
            }

            $this->info($write ? 'Tryb: ROLLBACK WRITE' : 'Tryb: ROLLBACK PREVIEW');
            $this->line('Rekordy do odtworzenia: '.$result['report']['question_rows_to_restore']);
            $this->line('Rekordy z konfliktem: '.$result['report']['question_rows_with_conflicts']);
            $this->line('Checksum: '.$result['report']['manifest_checksum']);

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function writeReport(ContentImportRun $run, array $report): void
    {
        $path = $this->resolvedReportPath();

        if ($path === null) {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $run->forceFill(['report_path' => $path])->save();
        $this->line('Raport: '.$path);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function writePreviewReport(array $report): void
    {
        $path = $this->resolvedReportPath();

        if ($path === null) {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $this->line('Raport: '.$path);
    }

    protected function resolvedReportPath(): ?string
    {
        $option = trim((string) $this->option('report'));

        if ($option === '') {
            return null;
        }

        return str_starts_with($option, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $option) === 1
            ? $option
            : storage_path('app/'.$option);
    }
}
