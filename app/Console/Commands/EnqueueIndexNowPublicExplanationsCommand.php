<?php

namespace App\Console\Commands;

use App\Models\IndexNowUrlSubmission;
use App\Models\QuestionPublicExplanation;
use App\Support\IndexNowQueueService;
use App\Support\PublicQuestionCatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class EnqueueIndexNowPublicExplanationsCommand extends Command
{
    protected $signature = 'seo:indexnow-enqueue-public-explanations
        {--updated-since= : Queue explanations updated at or after this date/time}
        {--limit= : Limit the number of explanations to inspect}
        {--dry-run : Resolve URLs without writing queue rows}
        {--report= : Write JSON report to a file}';

    protected $description = 'Queue public question explanation URLs for IndexNow submission.';

    public function handle(IndexNowQueueService $queue, PublicQuestionCatalogService $catalog): int
    {
        $updatedSince = $this->parseUpdatedSince();

        if (! $updatedSince instanceof Carbon) {
            return self::FAILURE;
        }

        $limit = $this->parseLimit();

        if ($limit === false) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $query = QuestionPublicExplanation::query()
            ->published()
            ->where('updated_at', '>=', $updatedSince)
            ->orderBy('updated_at')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $explanations = $query->get(['id', 'external_id', 'updated_at']);
        $queued = [];
        $skipped = [];

        foreach ($explanations as $explanation) {
            $externalId = trim((string) $explanation->external_id);
            $url = $catalog->findCanonicalUrlByExternalId($externalId);

            if (! is_string($url) || trim($url) === '') {
                $skipped[] = [
                    'id' => $explanation->getKey(),
                    'external_id' => $externalId,
                    'reason' => 'public_question_url_not_found',
                ];

                continue;
            }

            if ($dryRun) {
                $queued[] = [
                    'id' => $explanation->getKey(),
                    'external_id' => $externalId,
                    'url' => $url,
                    'queued' => false,
                    'dry_run' => true,
                ];

                continue;
            }

            $result = $queue->enqueueUrl(
                $url,
                'question_public_explanation_backfill',
                IndexNowUrlSubmission::EVENT_UPDATED,
                null,
                true,
            );

            if ((bool) $result['queued']) {
                $queued[] = [
                    'id' => $explanation->getKey(),
                    'external_id' => $externalId,
                    'url' => $result['url'],
                    'submission_id' => $result['submission_id'],
                ];
            } else {
                $skipped[] = [
                    'id' => $explanation->getKey(),
                    'external_id' => $externalId,
                    'url' => $url,
                    'reason' => $result['reason'],
                ];
            }
        }

        $this->line(sprintf(
            'IndexNow public explanations: matched=%d resolved=%d skipped=%d',
            $explanations->count(),
            count($queued),
            count($skipped),
        ));

        if ($dryRun) {
            $this->info('IndexNow public explanations dry-run finished. No queue rows were written.');
        } else {
            $this->info('IndexNow public explanation URLs queued.');
        }

        $report = [
            'ok' => true,
            'dry_run' => $dryRun,
            'updated_since' => $updatedSince->toIso8601String(),
            'matched' => $explanations->count(),
            'resolved' => count($queued),
            'skipped' => count($skipped),
            'queued' => $queued,
            'skipped_items' => $skipped,
        ];

        $reportPath = $this->option('report');

        if (is_string($reportPath) && trim($reportPath) !== '') {
            $this->writeReport(trim($reportPath), $report);
        }

        return self::SUCCESS;
    }

    private function parseUpdatedSince(): ?Carbon
    {
        $value = $this->option('updated-since');

        if (! is_string($value) || trim($value) === '') {
            $this->error('Provide --updated-since=YYYY-MM-DD to avoid queuing all public explanations accidentally.');

            return null;
        }

        try {
            return Carbon::parse(trim($value));
        } catch (\Throwable) {
            $this->error('--updated-since must be a valid date or datetime.');

            return null;
        }
    }

    private function parseLimit(): int|false|null
    {
        $limit = $this->option('limit');

        if ($limit === null || $limit === '') {
            return null;
        }

        if (! is_numeric($limit) || (int) $limit < 1) {
            $this->error('--limit must be a positive integer.');

            return false;
        }

        return (int) $limit;
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

        $this->info('IndexNow enqueue report written: '.$path);
    }
}
