<?php

namespace App\Console\Commands;

use App\Support\QuestionCollectionPreflightService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PreflightQuestionCollectionCommand extends Command
{
    protected $signature = 'questions:preflight-collection
        {collection=qualification-c-accelerated : Collection code or slug}
        {--sample-limit=20 : Maximum number of sample rows per finding}
        {--json : Print the full report as JSON}
        {--report= : Optional JSON report path}
        {--fail-on-errors : Return a failing exit code when blocking findings are found}';

    protected $description = 'Run a read-only integrity preflight for a question collection.';

    public function handle(QuestionCollectionPreflightService $preflightService): int
    {
        try {
            $report = $preflightService->audit(
                identifier: (string) $this->argument('collection'),
                sampleLimit: (int) $this->option('sample-limit'),
            );
        } catch (ModelNotFoundException) {
            $this->error(sprintf('Nie znaleziono kolekcji pytan: %s', (string) $this->argument('collection')));

            return self::FAILURE;
        }

        $reportPath = $this->writeReport($report);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderSummary($report, $reportPath);
        }

        return (int) data_get($report, 'summary.error_total', 0) > 0 && (bool) $this->option('fail-on-errors')
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function renderSummary(array $report, ?string $reportPath): void
    {
        $collection = (array) $report['collection'];
        $totals = (array) $report['totals'];
        $questions = (array) $report['questions'];
        $summary = (array) $report['summary'];

        $this->info('Preflight kolekcji pytan');
        $this->line(sprintf('%s (%s)', (string) $collection['name'], (string) $collection['code']));
        $this->table(
            ['Obszar', 'Wartosc'],
            [
                ['Moduly', (string) $totals['modules']],
                ['Przypisania modul-pytanie', (string) $totals['module_assignments']],
                ['Unikalne pytania', (string) $totals['unique_questions']],
                ['Powtorzenia miedzy modulami', (string) $totals['duplicate_assignments_between_modules']],
                ['Media', (string) $totals['media']],
                ['Pytania aktywne', (string) $questions['active']],
                ['Brak wymaganych mediow', (string) $questions['missing_required_primary_media']],
                ['Delivery issues', (string) array_sum((array) $questions['delivery_issues'])],
            ],
        );

        $this->line(sprintf('Bledy blokujace: %d', (int) $summary['error_total']));
        $this->line(sprintf('Ostrzezenia: %d', (int) $summary['warning_total']));
        $this->line(sprintf(
            'Gotowe do nastepnego etapu: %s',
            (bool) $summary['is_ready_for_next_stage'] ? 'tak' : 'nie',
        ));

        if ($reportPath !== null) {
            $this->comment('Raport zapisano do: '.$reportPath);
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function writeReport(array $report): ?string
    {
        $reportOption = trim((string) $this->option('report'));

        if ($reportOption === '') {
            return null;
        }

        $path = Str::startsWith($reportOption, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $reportOption)
            ? $reportOption
            : base_path($reportOption);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }
}
