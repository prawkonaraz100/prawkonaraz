<?php

namespace App\Console\Commands;

use App\Support\QuestionCollectionSessionContextBackfillService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BackfillQuestionCollectionSessionContextCommand extends Command
{
    protected $signature = 'questions:backfill-collection-session-context
        {collection=qualification-c-accelerated : Collection code or slug}
        {--dry-run : Explicitly run only the read-only preview}
        {--apply : Persist a backfill only after a clean complete preview}
        {--chunk=500 : Number of historical sessions inspected per batch}
        {--sample-limit=25 : Maximum blocking samples in the report}
        {--json : Print the complete report as JSON}
        {--report= : Optional JSON report path}';

    protected $description = 'Preview or safely backfill relational course context for historical study sessions.';

    public function handle(QuestionCollectionSessionContextBackfillService $backfillService): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Użyj tylko jednej opcji: --dry-run albo --apply.');

            return self::INVALID;
        }

        try {
            $report = $this->option('apply')
                ? $backfillService->apply(
                    identifier: (string) $this->argument('collection'),
                    chunkSize: (int) $this->option('chunk'),
                    sampleLimit: (int) $this->option('sample-limit'),
                )
                : $backfillService->preview(
                    identifier: (string) $this->argument('collection'),
                    chunkSize: (int) $this->option('chunk'),
                    sampleLimit: (int) $this->option('sample-limit'),
                );
        } catch (ModelNotFoundException) {
            $this->error(sprintf('Nie znaleziono kolekcji pytań: %s', (string) $this->argument('collection')));

            return self::FAILURE;
        }

        $reportPath = $this->writeReport($report);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderSummary($report, $reportPath);
        }

        return $this->option('apply') && ! (bool) ($report['applied'] ?? false)
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function renderSummary(array $report, ?string $reportPath): void
    {
        $collection = (array) $report['collection'];

        $this->info(sprintf('%s (%s)', (string) $collection['name'], (string) $collection['code']));
        $this->table(
            ['Obszar', 'Wartość'],
            [
                ['Przeskanowane sesje', (string) $report['scanned_sessions']],
                ['Sesje z kontekstem kursu', (string) $report['course_context_sessions']],
                ['Bezpieczne do uzupełnienia', (string) $report['ready_sessions']],
                ['Pominięte poza kursem', (string) $report['ignored_sessions']],
                ['Rozbieżności blokujące', (string) $report['blocking_sessions']],
                ['Zaktualizowane', (string) ($report['updated_sessions'] ?? 0)],
            ],
        );

        if ((int) $report['blocking_sessions'] > 0) {
            $this->error('Backfill nie został wykonany: raport zawiera rozbieżności wymagające ręcznej kontroli.');
        } elseif (($report['mode'] ?? null) === 'apply') {
            $this->info('Backfill zakończony bezpiecznie.');
        } else {
            $this->comment('To jest tylko podgląd. Do zapisu użyj --apply po sprawdzeniu raportu.');
        }

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
