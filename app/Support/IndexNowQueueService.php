<?php

namespace App\Support;

use App\Models\IndexNowUrlSubmission;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IndexNowQueueService
{
    public function __construct(
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
    ) {}

    /**
     * @return array{queued: bool, reason: string|null, url: string|null, submission_id: int|null}
     */
    public function enqueueQuestionPublicExplanation(
        string $externalId,
        string $source = 'question_public_explanation',
        string $eventType = IndexNowUrlSubmission::EVENT_UPDATED,
        bool $force = false,
    ): array {
        $externalId = trim($externalId);

        if ($externalId === '') {
            return [
                'queued' => false,
                'reason' => 'missing_external_id',
                'url' => null,
                'submission_id' => null,
            ];
        }

        $url = $this->publicQuestionCatalogService->findCanonicalUrlByExternalId($externalId);

        if (! is_string($url) || trim($url) === '') {
            return [
                'queued' => false,
                'reason' => 'public_question_url_not_found',
                'url' => null,
                'submission_id' => null,
            ];
        }

        return $this->enqueueUrl($url, $source, $eventType, null, $force);
    }

    /**
     * @return array{queued: bool, reason: string|null, url: string|null, submission_id: int|null}
     */
    public function enqueueUrl(
        string $url,
        string $source = 'manual',
        string $eventType = IndexNowUrlSubmission::EVENT_UPDATED,
        ?Carbon $availableAt = null,
        bool $force = false,
    ): array {
        $url = trim($url);

        if ($url === '') {
            return [
                'queued' => false,
                'reason' => 'empty_url',
                'url' => null,
                'submission_id' => null,
            ];
        }

        if (! $force && ! $this->automationEnabled()) {
            return [
                'queued' => false,
                'reason' => 'automation_disabled',
                'url' => $url,
                'submission_id' => null,
            ];
        }

        if (! Schema::hasTable('indexnow_url_submissions')) {
            return [
                'queued' => false,
                'reason' => 'queue_table_missing',
                'url' => $url,
                'submission_id' => null,
            ];
        }

        $now = now();
        $availableAt ??= $now->copy()->addMinutes($this->debounceMinutes());
        $urlHash = IndexNowUrlSubmission::hashUrl($url);

        $submission = DB::transaction(function () use ($url, $urlHash, $source, $eventType, $availableAt, $now): IndexNowUrlSubmission {
            $submission = IndexNowUrlSubmission::query()
                ->where('url_hash', $urlHash)
                ->lockForUpdate()
                ->first();

            if (! $submission instanceof IndexNowUrlSubmission) {
                return IndexNowUrlSubmission::query()->create([
                    'url' => $url,
                    'url_hash' => $urlHash,
                    'status' => IndexNowUrlSubmission::STATUS_PENDING,
                    'source' => $source,
                    'event_type' => $eventType,
                    'available_at' => $availableAt,
                    'last_enqueued_at' => $now,
                    'enqueued_count' => 1,
                    'attempts' => 0,
                ]);
            }

            $submission->forceFill([
                'url' => $url,
                'status' => IndexNowUrlSubmission::STATUS_PENDING,
                'source' => $source,
                'event_type' => $eventType,
                'available_at' => $availableAt,
                'last_enqueued_at' => $now,
                'enqueued_count' => $submission->enqueued_count + 1,
                'attempts' => 0,
                'last_http_status' => null,
                'last_response_reason' => null,
                'last_error' => null,
            ])->save();

            return $submission;
        });

        return [
            'queued' => true,
            'reason' => null,
            'url' => $submission->url,
            'submission_id' => (int) $submission->getKey(),
        ];
    }

    /**
     * @return EloquentCollection<int, IndexNowUrlSubmission>
     */
    public function dueSubmissions(int $limit): EloquentCollection
    {
        return IndexNowUrlSubmission::query()
            ->whereIn('status', [
                IndexNowUrlSubmission::STATUS_PENDING,
                IndexNowUrlSubmission::STATUS_FAILED,
            ])
            ->where(function ($query): void {
                $query
                    ->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            })
            ->orderByRaw('available_at is null desc')
            ->orderBy('available_at')
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * @param  Collection<int, IndexNowUrlSubmission>|EloquentCollection<int, IndexNowUrlSubmission>  $submissions
     */
    public function markProcessing(Collection|EloquentCollection $submissions): void
    {
        $ids = $submissions->pluck('id')->filter()->all();

        if ($ids === []) {
            return;
        }

        IndexNowUrlSubmission::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => IndexNowUrlSubmission::STATUS_PROCESSING,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  Collection<int, IndexNowUrlSubmission>|EloquentCollection<int, IndexNowUrlSubmission>  $submissions
     */
    public function markSent(Collection|EloquentCollection $submissions, ?int $httpStatus, ?string $reason): void
    {
        $ids = $submissions->pluck('id')->filter()->all();

        if ($ids === []) {
            return;
        }

        IndexNowUrlSubmission::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => IndexNowUrlSubmission::STATUS_SENT,
                'attempts' => 0,
                'available_at' => null,
                'last_submitted_at' => now(),
                'last_http_status' => $httpStatus,
                'last_response_reason' => $reason,
                'last_error' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  Collection<int, IndexNowUrlSubmission>|EloquentCollection<int, IndexNowUrlSubmission>  $submissions
     */
    public function markFailed(Collection|EloquentCollection $submissions, ?int $httpStatus, ?string $reason, string $error): void
    {
        foreach ($submissions as $submission) {
            $attempts = $submission->attempts + 1;

            $submission->forceFill([
                'status' => IndexNowUrlSubmission::STATUS_FAILED,
                'attempts' => $attempts,
                'available_at' => now()->addMinutes($this->retryDelayMinutes($attempts)),
                'last_http_status' => $httpStatus,
                'last_response_reason' => $reason,
                'last_error' => mb_substr($error, 0, 2000),
            ])->save();
        }
    }

    public function automationEnabled(): bool
    {
        return (bool) config('indexnow.automation_enabled', false);
    }

    public function defaultBatchSize(): int
    {
        return max(1, (int) config('indexnow.queue_batch_size', 50));
    }

    protected function debounceMinutes(): int
    {
        return max(0, (int) config('indexnow.queue_debounce_minutes', 10));
    }

    protected function retryDelayMinutes(int $attempts): int
    {
        $baseMinutes = max(1, (int) config('indexnow.queue_retry_minutes', 60));
        $multiplier = 2 ** min(max($attempts - 1, 0), 4);

        return min(1440, $baseMinutes * $multiplier);
    }
}
