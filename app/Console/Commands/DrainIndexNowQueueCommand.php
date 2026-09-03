<?php

namespace App\Console\Commands;

use App\Support\IndexNowQueueService;
use App\Support\IndexNowSubmissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DrainIndexNowQueueCommand extends Command
{
    protected $signature = 'seo:indexnow-drain-queue
        {--limit= : Maximum queued URLs to submit in this run}
        {--key-source= : Read the IndexNow key from a local text file instead of INDEXNOW_KEY}
        {--dry-run : Validate the queued payload without sending HTTP requests or updating rows}
        {--report= : Write JSON report to a file}';

    protected $description = 'Submit a small batch of queued IndexNow URLs.';

    public function handle(IndexNowQueueService $queue, IndexNowSubmissionService $submitter): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->parseLimit($queue->defaultBatchSize());

        if ($limit === false) {
            return self::FAILURE;
        }

        if (! $dryRun && ! $queue->automationEnabled()) {
            $this->warn('IndexNow automation is disabled. No queued URLs were submitted.');

            return self::SUCCESS;
        }

        $submissions = $queue->dueSubmissions($limit);

        $this->line(sprintf('IndexNow queue: selected=%d limit=%d', $submissions->count(), $limit));

        if ($submissions->isEmpty()) {
            $this->info('IndexNow queue is empty. No HTTP requests were sent.');

            return self::SUCCESS;
        }

        $keyOverride = $this->keyFromSource();

        if ($keyOverride === false) {
            return self::FAILURE;
        }

        $urls = $submissions
            ->pluck('url')
            ->map(fn (mixed $url): string => trim((string) $url))
            ->filter()
            ->values()
            ->all();

        if (! $dryRun) {
            $queue->markProcessing($submissions);
        }

        $submissionReport = $submitter->submit($urls, $dryRun, null, $keyOverride);
        $this->printSubmissionReport($submissionReport);

        if ($dryRun) {
            $this->info('IndexNow queue dry-run finished. No rows were updated and no HTTP requests were sent.');
        } elseif ((bool) ($submissionReport['ok'] ?? false)) {
            [$httpStatus, $reason] = $this->acceptedRequestMeta($submissionReport);
            $queue->markSent($submissions, $httpStatus, $reason);
            $this->info('IndexNow queue batch marked as sent.');
        } else {
            [$httpStatus, $reason, $error] = $this->failureMeta($submissionReport);
            $queue->markFailed($submissions, $httpStatus, $reason, $error);
            $this->error('IndexNow queue batch failed and was scheduled for retry.');
        }

        $report = [
            'ok' => (bool) ($submissionReport['ok'] ?? false),
            'dry_run' => $dryRun,
            'automation_enabled' => $queue->automationEnabled(),
            'selected_submission_ids' => $submissions->pluck('id')->values()->all(),
            'selected_urls' => $urls,
            'submission_report' => $submissionReport,
        ];

        $reportPath = $this->option('report');

        if (is_string($reportPath) && trim($reportPath) !== '') {
            $this->writeReport(trim($reportPath), $report);
        }

        return (bool) ($submissionReport['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    private function parseLimit(int $default): int|false
    {
        $limit = $this->option('limit');

        if ($limit === null || $limit === '') {
            return $default;
        }

        if (! is_numeric($limit) || (int) $limit < 1) {
            $this->error('--limit must be a positive integer.');

            return false;
        }

        return (int) $limit;
    }

    private function keyFromSource(): string|false|null
    {
        $source = $this->option('key-source');

        if (! is_string($source) || trim($source) === '') {
            $source = config('indexnow.key_source');
        }

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
    private function printSubmissionReport(array $report): void
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

        foreach ((array) ($report['requests'] ?? []) as $request) {
            $status = data_get($request, 'status');

            if ($status === 'dry-run') {
                continue;
            }

            $reason = data_get($request, 'reason');

            $this->line(sprintf(
                'Request #%d urls=%d status=%s accepted=%s%s',
                (int) data_get($request, 'index', 0),
                (int) data_get($request, 'url_count', 0),
                $status === null ? 'exception' : (string) $status,
                ((bool) data_get($request, 'accepted', false)) ? 'yes' : 'no',
                $reason !== null ? ' reason='.$reason : '',
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: int|null, 1: string|null}
     */
    private function acceptedRequestMeta(array $report): array
    {
        $request = collect((array) ($report['requests'] ?? []))
            ->first(fn (mixed $request): bool => (bool) data_get($request, 'accepted', false));

        return [
            is_numeric(data_get($request, 'status')) ? (int) data_get($request, 'status') : null,
            is_scalar(data_get($request, 'reason')) ? (string) data_get($request, 'reason') : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: int|null, 1: string|null, 2: string}
     */
    private function failureMeta(array $report): array
    {
        $request = collect((array) ($report['requests'] ?? []))
            ->first(fn (mixed $request): bool => ! (bool) data_get($request, 'accepted', false));
        $error = data_get($report, 'errors.0.message');

        return [
            is_numeric(data_get($request, 'status')) ? (int) data_get($request, 'status') : null,
            is_scalar(data_get($request, 'reason')) ? (string) data_get($request, 'reason') : null,
            is_scalar($error) ? (string) $error : 'IndexNow submission failed.',
        ];
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

        $this->info('IndexNow queue report written: '.$path);
    }
}
