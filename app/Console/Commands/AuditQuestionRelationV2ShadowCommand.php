<?php

namespace App\Console\Commands;

use App\Support\QuestionRelationV2ShadowAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditQuestionRelationV2ShadowCommand extends Command
{
    protected $signature = 'seo:audit-question-relation-v2-shadow
        {--topic-key=secondary:zawracanie : Topic key to compare}
        {--run= : Optional validated ranking run ID; defaults to the newest run for the topic}
        {--report= : Optional JSON report path}
        {--sample-limit=25 : Maximum comparison and issue samples}
        {--json : Print the complete report as JSON}
        {--fail-on-errors : Return a failing exit code when the audit has errors}';

    protected $description = 'Compare a non-public V2 relation run with the current V1 selector without changing public output.';

    public function handle(QuestionRelationV2ShadowAuditor $auditor): int
    {
        $topicKey = trim((string) $this->option('topic-key'));
        $run = trim((string) $this->option('run'));

        if ($topicKey === '' || ($run !== '' && (! ctype_digit($run) || (int) $run < 1))) {
            $this->error('Podaj poprawne --topic-key oraz opcjonalne dodatnie --run.');

            return self::FAILURE;
        }

        $report = $auditor->audit(
            $topicKey,
            $run === '' ? null : (int) $run,
            max(1, (int) $this->option('sample-limit')),
        );
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Nie udało się zakodować raportu shadow V2.');

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line($json);
        } else {
            $this->line(sprintf(
                'status=%s topic=%s run=%s compared=%d v1=%d v2=%d shared=%d public_output=no',
                (string) data_get($report, 'status'),
                (string) data_get($report, 'topic.key'),
                (string) data_get($report, 'run.id'),
                (int) data_get($report, 'comparison.sources_compared'),
                (int) data_get($report, 'comparison.v1_links_total'),
                (int) data_get($report, 'comparison.v2_links_total'),
                (int) data_get($report, 'comparison.shared_links_total'),
            ));
        }

        $reportPath = trim((string) $this->option('report'));

        if ($reportPath !== '') {
            File::ensureDirectoryExists(dirname($reportPath));
            File::put($reportPath, $json."\n");
            $this->info('Raport zapisany: '.$reportPath);
        }

        if ((bool) $this->option('fail-on-errors')
            && (int) data_get($report, 'issue_counts.errors', 0) > 0) {
            $this->error('Audyt shadow V2 wykrył błędy.');

            return self::FAILURE;
        }

        $this->info('Audyt shadow V2 nie zmienił rolloutu ani publicznego HTML.');

        return self::SUCCESS;
    }
}
