<?php

namespace App\Console\Commands;

use App\Support\QuestionRelationShadowRunImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportQuestionRelationShadowRunCommand extends Command
{
    protected $signature = 'seo:import-question-relation-shadow-run
        {--ranking= : Frozen shadow-ranking.jsonl}
        {--manifest= : Frozen shadow-manifest.json}
        {--evaluation= : Frozen shadow-evaluation.json}
        {--topic-key=secondary:zawracanie : Root topic key for the immutable ranking run}
        {--write : Persist a validated non-public run; without this flag the command is read-only}
        {--report= : Optional JSON report path}
        {--sample-limit=25 : Maximum number of diagnostic samples}
        {--json : Print the complete report as JSON}';

    protected $description = 'Preview or import a frozen shadow artifact as an immutable, non-public V2 ranking run.';

    public function handle(QuestionRelationShadowRunImporter $importer): int
    {
        $ranking = trim((string) $this->option('ranking'));
        $manifest = trim((string) $this->option('manifest'));
        $evaluation = trim((string) $this->option('evaluation'));
        $topicKey = trim((string) $this->option('topic-key'));
        if ($ranking === '' || $manifest === '' || $evaluation === '' || $topicKey === '') {
            $this->error('Podaj --ranking, --manifest, --evaluation i --topic-key.');

            return self::FAILURE;
        }

        try {
            $report = $importer->run(
                rankingPath: $ranking,
                manifestPath: $manifest,
                evaluationPath: $evaluation,
                topicKey: $topicKey,
                write: (bool) $this->option('write'),
                sampleLimit: (int) $this->option('sample-limit'),
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $json = json_encode($this->publicReport($report), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $this->error('Nie udało się zakodować raportu importu shadow.');

            return self::FAILURE;
        }
        if ((bool) $this->option('json')) {
            $this->line($json);
        } else {
            $this->line(sprintf(
                'mode=%s core=%d recommendations=%d direct=%d fallback=%d run=%s rollout_change=no',
                (string) data_get($report, 'mode'),
                (int) data_get($report, 'artifact.core_sources'),
                (int) data_get($report, 'recommendations.planned'),
                (int) data_get($report, 'recommendations.direct'),
                (int) data_get($report, 'recommendations.hub_fallback'),
                data_get($report, 'run.already_imported') ? 'existing' : 'planned',
            ));
        }

        $reportPath = trim((string) $this->option('report'));
        if ($reportPath !== '') {
            File::ensureDirectoryExists(dirname($reportPath));
            File::put($reportPath, $json."\n");
            $this->info('Raport zapisany: '.$reportPath);
        }
        if (! (bool) data_get($report, 'quality_gates.passed', false)) {
            $this->error('Import runu shadow zablokowany przez quality gates.');

            return self::FAILURE;
        }

        $this->info((bool) data_get($report, 'applied', false)
            ? 'Niepubliczny run V2 i rekomendacje zostały zapisane.'
            : 'Preview/idempotentny przebieg zakończony. Publiczny rollout nie został zmieniony.');

        return self::SUCCESS;
    }

    private function publicReport(array $report): array
    {
        unset($report['_planned_records']);

        return $report;
    }
}
