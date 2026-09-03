<?php

namespace App\Console\Commands;

use App\Support\QuestionRelationShadowRolloutManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConfigureQuestionRelationShadowRolloutCommand extends Command
{
    protected $signature = 'seo:configure-question-relation-shadow
        {--topic-key=secondary:zawracanie : Topic key for the V2 shadow rollout}
        {--run= : Validated ranking run ID}
        {--write : Persist mode=shadow with zero exposure; without this flag the command is read-only}
        {--report= : Optional JSON report path}
        {--json : Print the complete report as JSON}';

    protected $description = 'Preview or configure a non-public V2 question-relation shadow rollout.';

    public function handle(QuestionRelationShadowRolloutManager $manager): int
    {
        $topicKey = trim((string) $this->option('topic-key'));
        $runId = (int) $this->option('run');

        if ($topicKey === '' || $runId < 1) {
            $this->error('Podaj poprawne --topic-key i --run.');

            return self::FAILURE;
        }

        $report = $manager->configure($topicKey, $runId, (bool) $this->option('write'));
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Nie udało się zakodować raportu shadow rolloutu.');

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line($json);
        } else {
            $this->line(sprintf(
                'mode=%s topic=%s run=%s selected=%d rollout_change=%s public_output=no',
                (string) data_get($report, 'mode'),
                (string) data_get($report, 'topic.key'),
                (string) data_get($report, 'run.id'),
                (int) data_get($report, 'recommendations.selected'),
                (bool) data_get($report, 'applied') ? 'yes' : 'no',
            ));
        }

        $reportPath = trim((string) $this->option('report'));

        if ($reportPath !== '') {
            File::ensureDirectoryExists(dirname($reportPath));
            File::put($reportPath, $json."\n");
            $this->info('Raport zapisany: '.$reportPath);
        }

        if (! (bool) data_get($report, 'quality_gates.passed', false)) {
            $this->error('Shadow rollout zablokowany przez quality gates.');

            return self::FAILURE;
        }

        $this->info((bool) data_get($report, 'applied')
            ? 'Niepubliczny mode=shadow został skonfigurowany z ekspozycją 0%. '
                .'Publiczny HTML nadal korzysta z V1.'
            : 'Preview zakończony. Nie zmieniono rolloutów ani publicznego HTML.');

        return self::SUCCESS;
    }
}
