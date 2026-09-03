<?php

namespace App\Console\Commands;

use App\Support\QuestionMediaSitemapReadinessAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditQuestionMediaSitemapReadinessCommand extends Command
{
    protected $signature = 'seo:audit-question-media-readiness
        {--report= : Optional JSON report path}
        {--http : Check resolved public media URLs with HEAD/GET}
        {--http-limit=100 : Maximum public URLs to check over HTTP; 0 means all}
        {--http-timeout=5 : HTTP timeout in seconds}
        {--fail-on-errors : Return a failing exit code when blocking errors are found}';

    protected $description = 'Audit public question media readiness before expanding image/video sitemap coverage.';

    public function handle(QuestionMediaSitemapReadinessAuditor $auditor): int
    {
        $report = $auditor->audit(
            checkHttp: (bool) $this->option('http'),
            httpLimit: max(0, (int) $this->option('http-limit')),
            httpTimeout: max(1, (int) $this->option('http-timeout')),
        );

        $this->line(sprintf(
            'Publiczne pytania: rows=%d canonical=%d with_media=%d requires_media_without_media=%d',
            (int) data_get($report, 'questions.public_rows'),
            (int) data_get($report, 'questions.canonical_external_ids'),
            (int) data_get($report, 'questions.rows_with_question_media'),
            (int) data_get($report, 'questions.rows_requiring_primary_media_without_media'),
        ));

        $this->line(sprintf(
            'Question media: total=%d images=%d videos=%d image_candidates=%d video_candidates=%d',
            (int) data_get($report, 'question_media.total'),
            (int) data_get($report, 'question_media.by_kind_or_role.image'),
            (int) data_get($report, 'question_media.by_kind_or_role.video'),
            (int) data_get($report, 'question_media.image_sitemap_candidates'),
            (int) data_get($report, 'question_media.video_sitemap_candidates'),
        ));

        $this->line(sprintf(
            'PJM: total=%d | explanation_assets=%d | http_checked=%d',
            (int) data_get($report, 'pjm_sign_language.total'),
            (int) data_get($report, 'explanation_assets.total'),
            (int) data_get($report, 'checks.http_checked'),
        ));

        $errors = (array) data_get($report, 'issues.errors', []);
        $warnings = (array) data_get($report, 'issues.warnings', []);
        $errorCount = (int) data_get($report, 'issue_counts.errors', count($errors));
        $warningCount = (int) data_get($report, 'issue_counts.warnings', count($warnings));

        $this->line(sprintf(
            'Readiness: image_sitemap=%s video_sitemap=%s video_blockers=%d',
            (string) data_get($report, 'readiness.image_sitemap.status'),
            (string) data_get($report, 'readiness.video_sitemap.status'),
            (int) data_get($report, 'readiness.video_sitemap.blocking_errors'),
        ));

        if ($warningCount > 0) {
            $this->warn(sprintf('Warnings: %d total, %d sample(s).', $warningCount, count($warnings)));
            $this->printTopIssueTypes((array) data_get($report, 'issue_type_counts.warnings', []), 'warning');
            $this->printIssueSamples($warnings, 'warning');
        }

        if ($errorCount > 0) {
            $this->error(sprintf('Errors: %d total, %d sample(s).', $errorCount, count($errors)));
            $this->printTopIssueTypes((array) data_get($report, 'issue_type_counts.errors', []), 'error');
            $this->printIssueSamples($errors, 'error');
        } else {
            $this->info('Media sitemap readiness audit has no blocking errors.');
        }

        $reportPath = $this->option('report');

        if (is_string($reportPath) && trim($reportPath) !== '') {
            $this->writeReport(trim($reportPath), $report);
        }

        return $errorCount > 0 && (bool) $this->option('fail-on-errors')
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $issueTypes
     */
    protected function printTopIssueTypes(array $issueTypes, string $level): void
    {
        arsort($issueTypes);

        foreach (array_slice($issueTypes, 0, 5, true) as $type => $count) {
            $this->line(sprintf('[%s-type] %s=%d', $level, (string) $type, (int) $count));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    protected function printIssueSamples(array $issues, string $level): void
    {
        foreach (array_slice($issues, 0, 5) as $issue) {
            $this->line(sprintf(
                '[%s] %s: %s',
                $level,
                (string) ($issue['type'] ?? 'unknown'),
                (string) ($issue['message'] ?? ''),
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function writeReport(string $path, array $report): void
    {
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);

        $this->info('Report written: '.$path);
    }
}
