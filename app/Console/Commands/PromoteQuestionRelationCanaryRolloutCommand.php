<?php

namespace App\Console\Commands;

use App\Support\QuestionRelationCanaryRolloutManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PromoteQuestionRelationCanaryRolloutCommand extends Command
{
    protected $signature = 'seo:promote-question-relation-canary
        {--topic-key=secondary:zawracanie : Topic key for the existing V2 canary rollout}
        {--run= : Active validated ranking run ID; must match the existing canary}
        {--from-exposure= : Exact current canary exposure percentage, for example 5}
        {--cohort-seed= : Exact existing deterministic cohort seed}
        {--write : Persist the promotion to 100% after all gates pass}
        {--confirm-full-topic-rollout : Required together with --write; makes the 100% public-output intent explicit}
        {--report= : Optional JSON report path}
        {--json : Print the complete report as JSON}';

    protected $description = 'Preview or explicitly promote one unchanged public V2 canary to 100% of its topic.';

    public function handle(QuestionRelationCanaryRolloutManager $manager): int
    {
        $topicKey = trim((string) $this->option('topic-key'));
        $runId = (int) $this->option('run');
        $fromExposure = (int) $this->option('from-exposure');
        $cohortSeed = trim((string) $this->option('cohort-seed'));
        $write = (bool) $this->option('write');
        $confirmed = (bool) $this->option('confirm-full-topic-rollout');

        if ($topicKey === '' || $runId < 1 || $fromExposure < 1 || $fromExposure >= 100 || $cohortSeed === '') {
            $this->error('Podaj poprawne --topic-key, --run, --from-exposure (1–99) oraz niepuste --cohort-seed.');

            return self::FAILURE;
        }

        $report = $manager->promoteToFullTopic(
            $topicKey,
            $runId,
            $fromExposure,
            $cohortSeed,
            $write,
            $confirmed,
        );
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Nie udało się zakodować raportu promocji canary.');

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
            $this->error('Promocja canary zablokowana przez quality gates danych lub zgodności istniejącego rolloutu.');

            return self::FAILURE;
        }

        if ($write && ! (bool) data_get($report, 'write_gates.passed', false)) {
            $this->error('Zapis promocji wymaga obu flag runtime oraz --confirm-full-topic-rollout.');

            return self::FAILURE;
        }

        $this->info((bool) data_get($report, 'applied')
            ? 'Canary V2 został promowany do 100% topicu. Natychmiastowy rollback: QUESTION_RELATIONS_V2_CANARY_ENABLED=false.'
            : 'Preview promocji zakończony. Nie zmieniono rolloutów ani publicznego HTML.');

        return self::SUCCESS;
    }
}
