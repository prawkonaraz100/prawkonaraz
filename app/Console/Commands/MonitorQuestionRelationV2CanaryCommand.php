<?php

namespace App\Console\Commands;

use App\Support\QuestionRelationV2CanaryMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MonitorQuestionRelationV2CanaryCommand extends Command
{
    protected $signature = 'seo:monitor-question-relation-v2-canary
        {--topic-key= : Optional topic key; by default inspect every active canary rollout}
        {--report= : Optional JSON report path}
        {--sample-limit=10 : Maximum V1 ↔ V2 samples per topic}
        {--json : Print the complete monitor report as JSON}
        {--fail-on-errors : Return a failing exit code when monitoring finds errors}';

    protected $description = 'Monitor active deterministic public V2 relation canaries without changing rollout data.';

    public function handle(QuestionRelationV2CanaryMonitor $monitor): int
    {
        $topicKey = trim((string) $this->option('topic-key'));
        $report = $monitor->inspect(
            $topicKey === '' ? null : $topicKey,
            max(1, (int) $this->option('sample-limit')),
        );
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Nie udało się zakodować raportu monitoringu canary V2.');

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line($json);
        } else {
            $this->line(sprintf(
                'status=%s rollouts=%d topics=%d errors=%d warnings=%d duration_ms=%s public_output=yes',
                (string) data_get($report, 'status'),
                (int) data_get($report, 'summary.active_canary_rollouts', 0),
                (int) data_get($report, 'summary.topics_checked', 0),
                (int) data_get($report, 'summary.errors', 0),
                (int) data_get($report, 'summary.warnings', 0),
                (string) data_get($report, 'summary.duration_ms', '0'),
            ));
        }

        $reportPath = trim((string) $this->option('report'));

        if ($reportPath !== '') {
            File::ensureDirectoryExists(dirname($reportPath));
            File::put($reportPath, $json."\n");
            $this->info('Raport zapisany: '.$reportPath);
        }

        if ((bool) $this->option('fail-on-errors')
            && (int) data_get($report, 'summary.errors', 0) > 0) {
            $this->error('Monitoring canary V2 wykrył błędy.');

            return self::FAILURE;
        }

        $this->info('Monitoring nie zmienił rolloutu ani przydziału canary.');

        return self::SUCCESS;
    }
}
