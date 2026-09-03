<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\QuestionMedia;
use App\Support\MediaUrlResolver;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AuditQuestionVideoPublicUrlsCommand extends Command
{
    protected const ISSUE_SAMPLE_LIMIT = 100;

    protected $signature = 'seo:audit-question-video-public-urls
        {--limit= : Maximum video records to check}
        {--concurrency=25 : Number of HTTP HEAD checks per batch}
        {--timeout=5 : HTTP timeout in seconds}
        {--retries=1 : Serial retry attempts for timeout, 5xx, 408 or 429 responses}
        {--retry-timeout=20 : HTTP timeout in seconds for retry attempts}
        {--report= : Optional JSON report path}
        {--fail-on-errors : Return a failing exit code when errors are found}';

    protected $description = 'Audit public video and poster URLs for question video SEO readiness.';

    /**
     * @var array<string, mixed>
     */
    protected array $report = [];

    public function handle(MediaUrlResolver $mediaUrlResolver): int
    {
        $limit = $this->option('limit');
        $limit = is_numeric($limit) ? max(0, (int) $limit) : null;
        $concurrency = max(1, (int) $this->option('concurrency'));
        $timeout = max(1, (int) $this->option('timeout'));
        $retries = max(0, (int) $this->option('retries'));
        $retryTimeout = max($timeout, (int) $this->option('retry-timeout'));

        $this->report = [
            'generated_at' => now()->toIso8601String(),
            'scope' => 'public_question_videos',
            'options' => [
                'limit' => $limit,
                'concurrency' => $concurrency,
                'timeout_seconds' => $timeout,
                'retries' => $retries,
                'retry_timeout_seconds' => $retryTimeout,
            ],
            'summary' => [
                'videos' => 0,
                'checks' => 0,
                'ok' => 0,
                'retried' => 0,
                'video_urls' => 0,
                'poster_urls' => 0,
                'storage_errors' => 0,
                'http_errors' => 0,
                'warnings' => 0,
            ],
            'http_statuses' => [
                'video' => [],
                'poster' => [],
            ],
            'issues' => [
                'errors' => [],
                'warnings' => [],
            ],
            'issue_counts' => [
                'errors' => 0,
                'warnings' => 0,
            ],
            'issue_type_counts' => [
                'errors' => [],
                'warnings' => [],
            ],
        ];

        $checks = $this->buildChecks($mediaUrlResolver, $limit);
        $this->report['summary']['checks'] = count($checks);

        $this->runHttpChecks($checks, $concurrency, $timeout, $retries, $retryTimeout);

        $errors = (int) $this->report['issue_counts']['errors'];
        $warnings = (int) $this->report['issue_counts']['warnings'];

        $this->line(sprintf(
            'Question videos: total=%d checks=%d ok=%d retried=%d errors=%d warnings=%d',
            (int) $this->report['summary']['videos'],
            (int) $this->report['summary']['checks'],
            (int) $this->report['summary']['ok'],
            (int) $this->report['summary']['retried'],
            $errors,
            $warnings,
        ));

        $this->line(sprintf(
            'URL roles: video=%d poster=%d | storage_errors=%d http_errors=%d',
            (int) $this->report['summary']['video_urls'],
            (int) $this->report['summary']['poster_urls'],
            (int) $this->report['summary']['storage_errors'],
            (int) $this->report['summary']['http_errors'],
        ));

        $this->printStatuses('video');
        $this->printStatuses('poster');

        if ($warnings > 0) {
            $this->warn(sprintf('Warnings: %d total.', $warnings));
            $this->printTopIssueTypes((array) $this->report['issue_type_counts']['warnings'], 'warning');
        }

        if ($errors > 0) {
            $this->error(sprintf('Errors: %d total.', $errors));
            $this->printTopIssueTypes((array) $this->report['issue_type_counts']['errors'], 'error');
            $this->printIssueSamples((array) $this->report['issues']['errors'], 'error');
        } else {
            $this->info('Question video public URL audit has no blocking errors.');
        }

        $reportPath = $this->option('report');

        if (is_string($reportPath) && trim($reportPath) !== '') {
            $this->writeReport(trim($reportPath), $this->report);
        }

        return $errors > 0 && (bool) $this->option('fail-on-errors')
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @return array<string, array{role:string,url:string,context:array<string, mixed>,allowed_statuses:list<int>}>
     */
    protected function buildChecks(MediaUrlResolver $mediaUrlResolver, ?int $limit): array
    {
        $checks = [];

        $query = QuestionMedia::query()
            ->with([
                'question:id,license_category_id,external_id,prompt,explanation,is_active,delivery_issue,requires_primary_media',
                'question.licenseCategory:id,code,slug,is_active',
            ])
            ->where('kind', 'video')
            ->whereHas('question', fn (Builder $query) => $this->applyPublicQuestionScope($query))
            ->orderBy('id');

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $query->get()->each(function (QuestionMedia $media) use (&$checks, $mediaUrlResolver): void {
            $this->report['summary']['videos']++;

            $context = [
                'id' => $media->getKey(),
                'question_id' => $media->question_id,
                'external_id' => $media->question?->external_id,
                'category' => $media->question?->licenseCategory?->code,
                'disk' => $media->disk,
            ];

            $video = $this->prepareCheck(
                mediaUrlResolver: $mediaUrlResolver,
                role: 'video',
                path: $media->path,
                disk: $media->disk,
                context: $context + ['path' => $media->path],
                allowedStatuses: [200, 206],
            );

            if ($video !== null) {
                $checks['video:'.$media->getKey()] = $video;
                $this->report['summary']['video_urls']++;
            }

            $poster = $this->prepareCheck(
                mediaUrlResolver: $mediaUrlResolver,
                role: 'poster',
                path: $media->poster_path,
                disk: $media->disk,
                context: $context + ['poster_path' => $media->poster_path],
                allowedStatuses: [200],
            );

            if ($poster !== null) {
                $checks['poster:'.$media->getKey()] = $poster;
                $this->report['summary']['poster_urls']++;
            }
        });

        return $checks;
    }

    /**
     * @return array{role:string,url:string,context:array<string, mixed>,allowed_statuses:list<int>}|null
     */
    protected function prepareCheck(
        MediaUrlResolver $mediaUrlResolver,
        string $role,
        ?string $path,
        ?string $disk,
        array $context,
        array $allowedStatuses,
    ): ?array {
        if (blank($path)) {
            $this->issue('errors', $role.'_missing_path', ucfirst($role).' path is blank.', $context);
            $this->report['summary']['storage_errors']++;

            return null;
        }

        try {
            $url = $mediaUrlResolver->resolve($path, $disk);
        } catch (Throwable $exception) {
            $this->issue('errors', $role.'_url_resolution_failed', $exception->getMessage(), $context);
            $this->report['summary']['storage_errors']++;

            return null;
        }

        if (blank($url)) {
            $this->issue('errors', $role.'_url_empty', ucfirst($role).' URL did not resolve.', $context);
            $this->report['summary']['storage_errors']++;

            return null;
        }

        if (! str_starts_with((string) $url, 'https://')) {
            $this->issue('warnings', $role.'_url_not_https', ucfirst($role).' URL is not HTTPS.', $context + ['url' => $url]);
        }

        if (! $this->isExternalUrl((string) $path)) {
            $this->assertStorageExists((string) $path, $disk, $role, $context + ['url' => $url]);
        }

        return [
            'role' => $role,
            'url' => (string) $url,
            'context' => $context + ['url' => $url],
            'allowed_statuses' => $allowedStatuses,
        ];
    }

    /**
     * @param  array<string, array{role:string,url:string,context:array<string, mixed>,allowed_statuses:list<int>}>  $checks
     */
    protected function runHttpChecks(array $checks, int $concurrency, int $timeout, int $retries, int $retryTimeout): void
    {
        foreach (array_chunk($checks, $concurrency, true) as $chunk) {
            try {
                /** @var array<string, Response|Throwable> $responses */
                $responses = Http::pool(function (Pool $pool) use ($chunk, $timeout): array {
                    $requests = [];

                    foreach ($chunk as $key => $check) {
                        $requests[] = $pool
                            ->as((string) $key)
                            ->timeout($timeout)
                            ->withHeaders(['User-Agent' => 'PrawkoNaRazVideoPublicUrlAudit/1.0'])
                            ->head($check['url']);
                    }

                    return $requests;
                });
            } catch (Throwable $exception) {
                foreach ($chunk as $key => $check) {
                    $this->recordHttpResult($check, $exception, $retries, $retryTimeout, (string) $key);
                }

                continue;
            }

            foreach ($chunk as $key => $check) {
                $response = $responses[$key] ?? null;

                $this->recordHttpResult(
                    check: $check,
                    result: $response instanceof Response || $response instanceof Throwable
                        ? $response
                        : new \RuntimeException('HTTP response is missing.'),
                    retries: $retries,
                    retryTimeout: $retryTimeout,
                    checkKey: (string) $key,
                );
            }
        }
    }

    /**
     * @param  array{role:string,url:string,context:array<string, mixed>,allowed_statuses:list<int>}  $check
     */
    protected function recordHttpResult(array $check, Response|Throwable $result, int $retries, int $retryTimeout, string $checkKey): void
    {
        $current = $result;

        for ($attempt = 0; $attempt < $retries && $this->shouldRetryHttpResult($current); $attempt++) {
            $this->report['summary']['retried']++;
            $current = $this->headWithTimeout($check['url'], $retryTimeout);
        }

        if (! $current instanceof Response) {
            $this->issue('errors', 'media_http_check_failed', $current->getMessage(), $check['context'] + ['check_key' => $checkKey]);
            $this->report['summary']['http_errors']++;

            return;
        }

        $this->recordHttpResponse($check, $current);
    }

    protected function shouldRetryHttpResult(Response|Throwable $result): bool
    {
        if ($result instanceof Throwable) {
            return true;
        }

        $status = $result->status();

        return $status === 408 || $status === 429 || $status >= 500;
    }

    protected function headWithTimeout(string $url, int $timeout): Response|Throwable
    {
        try {
            return Http::timeout($timeout)
                ->withHeaders(['User-Agent' => 'PrawkoNaRazVideoPublicUrlAudit/1.0'])
                ->head($url);
        } catch (Throwable $exception) {
            return $exception;
        }
    }

    /**
     * @param  array{role:string,url:string,context:array<string, mixed>,allowed_statuses:list<int>}  $check
     */
    protected function recordHttpResponse(array $check, Response $response): void
    {
        $status = $response->status();
        $role = $check['role'];
        $this->incrementKey($this->report['http_statuses'][$role], (string) $status);

        if (! in_array($status, $check['allowed_statuses'], true)) {
            $this->issue(
                'errors',
                $role.'_http_unexpected_status',
                ucfirst($role).' URL returned HTTP '.$status.'.',
                $check['context'] + ['status' => $status, 'allowed_statuses' => $check['allowed_statuses']],
            );
            $this->report['summary']['http_errors']++;

            return;
        }

        $this->report['summary']['ok']++;
    }

    protected function assertStorageExists(string $path, ?string $disk, string $role, array $context): void
    {
        if (blank($disk)) {
            $this->issue('errors', $role.'_blank_disk', ucfirst($role).' disk is blank.', $context);
            $this->report['summary']['storage_errors']++;

            return;
        }

        if (! is_array(config("filesystems.disks.{$disk}"))) {
            $this->issue('errors', $role.'_unconfigured_disk', ucfirst($role).' disk is not configured.', $context);
            $this->report['summary']['storage_errors']++;

            return;
        }

        try {
            if (! Storage::disk((string) $disk)->exists($path)) {
                $this->issue('errors', $role.'_missing_file', ucfirst($role).' file does not exist on configured storage disk.', $context);
                $this->report['summary']['storage_errors']++;
            }
        } catch (Throwable $exception) {
            $this->issue('errors', $role.'_storage_check_failed', $exception->getMessage(), $context);
            $this->report['summary']['storage_errors']++;
        }
    }

    /**
     * @param  Builder<Question>  $query
     * @return Builder<Question>
     */
    protected function applyPublicQuestionScope(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereHas('licenseCategory', fn (Builder $categoryQuery) => $categoryQuery->where('is_active', true));
    }

    protected function printStatuses(string $role): void
    {
        $statuses = (array) ($this->report['http_statuses'][$role] ?? []);
        ksort($statuses);

        $summary = collect($statuses)
            ->map(fn (int $count, string $status): string => $status.'='.$count)
            ->implode(', ');

        $this->line(sprintf('[http-status:%s] %s', $role, $summary !== '' ? $summary : 'none'));
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

    protected function issue(string $severity, string $type, string $message, array $context): void
    {
        if (isset($this->report['issue_counts'][$severity])) {
            $this->report['issue_counts'][$severity]++;
        }

        if (isset($this->report['issue_type_counts'][$severity])) {
            $this->incrementKey($this->report['issue_type_counts'][$severity], $type);
        }

        if ($severity === 'warnings') {
            $this->report['summary']['warnings']++;
        }

        if (! isset($this->report['issues'][$severity]) || count($this->report['issues'][$severity]) >= self::ISSUE_SAMPLE_LIMIT) {
            return;
        }

        $this->report['issues'][$severity][] = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
        ];
    }

    protected function incrementKey(array &$bucket, string $key): void
    {
        $bucket[$key] = (int) ($bucket[$key] ?? 0) + 1;
    }

    protected function isExternalUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
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
