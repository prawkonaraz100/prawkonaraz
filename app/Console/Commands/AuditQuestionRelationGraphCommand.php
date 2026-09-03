<?php

namespace App\Console\Commands;

use App\Models\QuestionRelation;
use App\Support\QuestionRelationGraphAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditQuestionRelationGraphCommand extends Command
{
    protected $signature = 'seo:audit-question-relation-graph
        {--report= : Optional JSON report path}
        {--sample-limit=25 : Maximum issue and diagnostic samples stored in the report}
        {--json : Print the complete report as JSON}
        {--fail-on-errors : Return a failing exit code when integrity errors are found}';

    protected $description = 'Build a read-only SEO baseline of question relations, topic coverage and fallback pressure.';

    public function handle(QuestionRelationGraphAuditor $auditor): int
    {
        $report = $auditor->audit(max(1, (int) $this->option('sample-limit')));
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Nie udało się zakodować raportu grafu relacji jako JSON.');

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

        $errorCount = (int) data_get($report, 'issue_counts.errors', 0);

        return $errorCount > 0 && (bool) $this->option('fail-on-errors')
            ? self::FAILURE
            : self::SUCCESS;
    }

    /** @param array<string, mixed> $report */
    protected function printSummary(array $report): void
    {
        $this->line(sprintf(
            'Graf pytań: relations=%d published=%d safe=%d candidates=%d',
            (int) data_get($report, 'counts.relations'),
            (int) data_get($report, 'counts.published_relations'),
            (int) data_get($report, 'counts.safe_published_relations'),
            (int) data_get($report, 'relations.by_status.'.QuestionRelation::STATUS_CANDIDATE, 0),
        ));
        $this->line(sprintf(
            'Pokrycie: public_explanations=%d with_topic=%d without_topic=%d below_%d_direct=%d',
            (int) data_get($report, 'counts.public_resolvable_explanations'),
            (int) data_get($report, 'coverage.with_topic'),
            (int) data_get($report, 'coverage.without_topic'),
            (int) data_get($report, 'configuration.minimum_links'),
            (int) data_get($report, 'coverage.below_minimum_direct_links'),
        ));
        $this->line(sprintf(
            'Fallback do minimum: direct=%d same_topic=%d same_primary=%d adjacent=%d category_required=%d',
            (int) data_get($report, 'fallback_pressure.reaches_minimum_at.direct'),
            (int) data_get($report, 'fallback_pressure.reaches_minimum_at.same_topic'),
            (int) data_get($report, 'fallback_pressure.reaches_minimum_at.same_primary'),
            (int) data_get($report, 'fallback_pressure.reaches_minimum_at.adjacent_topic'),
            (int) data_get($report, 'fallback_pressure.reaches_minimum_at.category_fallback_required'),
        ));
        $this->line(sprintf(
            'Powtarzalne zestawy: groups=%d questions=%d largest_group=%d',
            (int) data_get($report, 'duplicate_recommendation_sets.repeated_set_groups'),
            (int) data_get($report, 'duplicate_recommendation_sets.questions_in_repeated_sets'),
            (int) data_get($report, 'duplicate_recommendation_sets.largest_group'),
        ));

        $errors = (int) data_get($report, 'issue_counts.errors');
        $warnings = (int) data_get($report, 'issue_counts.warnings');
        if ($errors > 0) {
            $this->error("Błędy integralności: {$errors}");
            $this->printTopIssueTypes((array) data_get($report, 'issue_type_counts.errors', []), 'error');
        } else {
            $this->info('Brak blokujących błędów integralności grafu.');
        }

        if ($warnings > 0) {
            $this->warn("Ostrzeżenia jakościowe: {$warnings}");
            $this->printTopIssueTypes((array) data_get($report, 'issue_type_counts.warnings', []), 'warning');
        }
    }

    /** @param array<string, int> $issueTypes */
    protected function printTopIssueTypes(array $issueTypes, string $level): void
    {
        arsort($issueTypes);

        foreach (array_slice($issueTypes, 0, 5, true) as $type => $count) {
            $this->line(sprintf('[%s] %s=%d', $level, (string) $type, (int) $count));
        }
    }

    protected function writeReport(string $path, string $json): void
    {
        $directory = dirname($path);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, $json.PHP_EOL);
        $this->info('Raport zapisany: '.$path);
    }
}
