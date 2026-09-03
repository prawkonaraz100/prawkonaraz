<?php

namespace App\Console\Commands;

use App\Support\IndexNowSubmissionService;
use App\Support\IndexNowUrlCollector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SubmitIndexNowUrlsCommand extends Command
{
    protected $signature = 'seo:indexnow-submit
        {url?* : Absolute URLs or paths to submit}
        {--from-sitemap : Collect current public page URLs from sitemap sources}
        {--key-source= : Read the IndexNow key from a local text file instead of INDEXNOW_KEY}
        {--dry-run : Build and validate the payload without sending HTTP requests}
        {--limit= : Limit the number of accepted URLs}
        {--report= : Write JSON report to a file}';

    protected $description = 'Submit public canonical URLs to IndexNow.';

    public function handle(IndexNowUrlCollector $collector, IndexNowSubmissionService $submitter): int
    {
        $urls = $this->argument('url');
        $urls = is_array($urls) ? $urls : [];

        if ((bool) $this->option('from-sitemap')) {
            $collectedUrls = $collector->publicPageUrls();
            $urls = [...$urls, ...$collectedUrls];
            $this->line(sprintf('Collected %d URLs from sitemap sources.', count($collectedUrls)));
        }

        if ($urls === []) {
            $this->error('Provide at least one URL/path or use --from-sitemap.');

            return self::FAILURE;
        }

        $limit = $this->parseLimit();

        if ($limit === false) {
            return self::FAILURE;
        }

        $keyOverride = $this->keyFromSource();

        if ($keyOverride === false) {
            return self::FAILURE;
        }

        $report = $submitter->submit(
            $urls,
            (bool) $this->option('dry-run'),
            $limit,
            $keyOverride,
        );

        $this->printReportSummary($report);

        $reportPath = $this->option('report');

        if (is_string($reportPath) && trim($reportPath) !== '') {
            $this->writeReport(trim($reportPath), $report);
        }

        return (bool) ($report['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    private function parseLimit(): int|false|null
    {
        $limit = $this->option('limit');

        if ($limit === null || $limit === '') {
            return null;
        }

        if (! is_numeric($limit)) {
            $this->error('--limit must be a non-negative integer.');

            return false;
        }

        return max(0, (int) $limit);
    }

    private function keyFromSource(): string|false|null
    {
        $source = $this->option('key-source');

        if (! is_string($source) || trim($source) === '') {
            return null;
        }

        $path = $this->resolveSourcePath(trim($source));

        if (! File::isFile($path)) {
            $this->error('IndexNow key source file does not exist: '.$path);

            return false;
        }

        return trim(File::get($path));
    }

    private function resolveSourcePath(string $source): string
    {
        if (
            str_starts_with($source, '/')
            || str_starts_with($source, '\\\\')
            || preg_match('/\A[A-Za-z]:[\\\\\\/]/', $source) === 1
        ) {
            return $source;
        }

        return base_path($source);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function printReportSummary(array $report): void
    {
        $summary = (array) ($report['summary'] ?? []);

        $this->line(sprintf(
            'IndexNow URLs: input=%d accepted=%d rejected=%d',
            (int) ($summary['input_urls'] ?? 0),
            (int) ($summary['accepted_urls'] ?? 0),
            (int) ($summary['rejected_urls'] ?? 0),
        ));

        $this->line(sprintf(
            'IndexNow requests: planned=%d sent=%d accepted=%d failed=%d',
            (int) ($summary['planned_requests'] ?? 0),
            (int) ($summary['sent_requests'] ?? 0),
            (int) ($summary['accepted_requests'] ?? 0),
            (int) ($summary['failed_requests'] ?? 0),
        ));

        foreach ((array) ($report['warnings'] ?? []) as $warning) {
            $this->warn((string) data_get($warning, 'message', 'IndexNow warning.'));
        }

        foreach ((array) ($report['errors'] ?? []) as $error) {
            $this->error((string) data_get($error, 'message', 'IndexNow error.'));
        }

        foreach (array_slice((array) ($report['rejected_urls'] ?? []), 0, 5) as $rejected) {
            $this->warn(sprintf(
                'Rejected URL: %s (%s)',
                (string) data_get($rejected, 'url', ''),
                (string) data_get($rejected, 'reason', 'unknown'),
            ));
        }

        foreach ((array) ($report['requests'] ?? []) as $request) {
            $status = data_get($request, 'status');
            $reason = data_get($request, 'reason');

            if ($status === 'dry-run') {
                continue;
            }

            $this->line(sprintf(
                'Request #%d urls=%d status=%s accepted=%s%s',
                (int) data_get($request, 'index', 0),
                (int) data_get($request, 'url_count', 0),
                $status === null ? 'exception' : (string) $status,
                ((bool) data_get($request, 'accepted', false)) ? 'yes' : 'no',
                $reason !== null ? ' reason='.$reason : '',
            ));
        }

        if ((bool) ($report['dry_run'] ?? false)) {
            $this->info('IndexNow submit dry-run finished. No HTTP requests were sent.');
        } elseif ((bool) ($report['ok'] ?? false)) {
            $this->info('IndexNow submit finished.');
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeReport(string $path, array $report): void
    {
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put(
            $path,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );

        $this->info('IndexNow report written: '.$path);
    }
}
