<?php

namespace App\Console\Commands;

use App\Support\QuestionRelationCanaryRolloutManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConfigureQuestionRelationCanaryRolloutCommand extends Command
{
    protected $signature = 'seo:configure-question-relation-canary
        {--topic-key=secondary:zawracanie : Topic key for the V2 canary rollout}
        {--run= : Validated ranking run ID}
        {--exposure=5 : Stable percentage of source question pages assigned to V2}
        {--cohort-seed= : Required immutable seed for the deterministic cohort}
        {--write : Persist a public mode=canary rollout after all gates pass}
        {--confirm-public-canary : Required together with --write; makes the public-output intent explicit}
        {--report= : Optional JSON report path}
        {--json : Print the complete report as JSON}';

    protected $description = 'Preview or explicitly configure a deterministic public V2 question-relation canary rollout.';

    public function handle(QuestionRelationCanaryRolloutManager $manager): int
    {
        $topicKey = trim((string) $this->option('topic-key'));
        $runId = (int) $this->option('run');
        $exposure = (int) $this->option('exposure');
        $cohortSeed = trim((string) $this->option('cohort-seed'));
        $write = (bool) $this->option('write');
        $confirmed = (bool) $this->option('confirm-public-canary');

        if ($topicKey === '' || $runId < 1 || $cohortSeed === '') {
            $this->error('Podaj poprawne --topic-key, --run oraz niepuste --cohort-seed.');

            return self::FAILURE;
        }

        $report = $manager->configure($topicKey, $runId, $exposure, $cohortSeed, $write, $confirmed);
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Nie udało się zakodować raportu canary rolloutu.');

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line($json);
        } else {
            $this->line(sprintf(
                'mode=%s topic=%s run=%s exposure=%s included_sources=%d rollout_change=%s public_output=planned',
                (string) data_get($report, 'mode'),
                (string) data_get($report, 'topic.key'),
                (string) data_get($report, 'run.id'),
                (string) data_get($report, 'cohort.exposure_percentage'),
                (int) data_get($report, 'cohort.included_sources'),
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
            $this->error('Canary rollout zablokowany przez quality gates danych.');

            return self::FAILURE;
        }

        if ($write && ! (bool) data_get($report, 'write_gates.passed', false)) {
            $this->error('Zapis canary wymaga obu flag runtime oraz --confirm-public-canary.');

            return self::FAILURE;
        }

        $this->info((bool) data_get($report, 'applied')
            ? 'Canary V2 został skonfigurowany. Natychmiastowy rollback: QUESTION_RELATIONS_V2_CANARY_ENABLED=false.'
            : 'Preview zakończony. Nie zmieniono rolloutów ani publicznego HTML.');

        return self::SUCCESS;
    }
}
