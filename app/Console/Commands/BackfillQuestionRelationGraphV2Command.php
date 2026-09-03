<?php

namespace App\Console\Commands;

use App\Support\QuestionRelationGraphV2Backfill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackfillQuestionRelationGraphV2Command extends Command
{
    protected $signature = 'seo:backfill-question-relation-graph-v2
        {--write : Persist the backfill; without this flag the command is strictly read-only}
        {--report= : Optional JSON report path}
        {--sample-limit=25 : Maximum number of diagnostic samples}
        {--json : Print the complete report as JSON}';

    protected $description = 'Preview or apply the idempotent V2 topic, membership, direction and evidence backfill.';

    public function handle(QuestionRelationGraphV2Backfill $backfill): int
    {
        $report = $backfill->run(
            write: (bool) $this->option('write'),
            sampleLimit: (int) $this->option('sample-limit'),
        );
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Nie udało się zakodować raportu backfillu V2.');

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line($json);
        } else {
            $this->printSummary($report);
        }

        $reportPath = $this->option('report');
        if (is_string($reportPath) && trim($reportPath) !== '') {
            $this->writeReport(trim($reportPath), $json);
        }

        if (! (bool) data_get($report, 'quality_gates.passed', false)) {
            $this->error('Backfill V2 zablokowany przez quality gates.');

            return self::FAILURE;
        }

        $this->info((bool) data_get($report, 'applied', false)
            ? 'Backfill V2 został zapisany.'
            : 'Preview zakończony. Dane aplikacji nie zostały zmienione.');

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $report */
    private function printSummary(array $report): void
    {
        $this->line(sprintf(
            'Tryb=%s topics=%d memberships=%d relations=%d evidences=%d',
            (string) data_get($report, 'mode'),
            (int) data_get($report, 'counts.topics'),
            (int) data_get($report, 'counts.memberships'),
            (int) data_get($report, 'counts.relations'),
            (int) data_get($report, 'counts.backfill_evidences'),
        ));
        $this->line(sprintf(
            'Plan: topics=%d memberships=%d directions=%d evidence_create=%d evidence_update=%d',
            (int) data_get($report, 'topics.would_update'),
            (int) data_get($report, 'memberships.would_update'),
            (int) data_get($report, 'directions.would_normalize'),
            (int) data_get($report, 'evidences.would_create'),
            (int) data_get($report, 'evidences.would_update'),
        ));

        if ((bool) data_get($report, 'quality_gates.passed', false)) {
            $this->info('Quality gates: PASS');

            return;
        }

        foreach ((array) data_get($report, 'quality_gates.blockers', []) as $key => $count) {
            if ((int) $count > 0) {
                $this->warn(sprintf('%s=%d', (string) $key, (int) $count));
            }
        }
    }

    private function writeReport(string $path, string $json): void
    {
        $directory = dirname($path);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, $json.PHP_EOL);
        $this->info('Raport zapisany: '.$path);
    }
}
