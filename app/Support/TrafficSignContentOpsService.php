<?php

namespace App\Support;

use App\Models\TrafficSign;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class TrafficSignContentOpsService
{
    /**
     * @param  Collection<int, TrafficSign>  $records
     */
    public function sendToReview(Collection $records): int
    {
        return $this->updateRecords($records, fn (TrafficSign $record): array => [
            'workflow_status' => TrafficSign::WORKFLOW_IN_REVIEW,
        ]);
    }

    /**
     * @param  Collection<int, TrafficSign>  $records
     */
    public function markNeedsReview(Collection $records): int
    {
        return $this->updateRecords($records, fn (TrafficSign $record): array => [
            'workflow_status' => TrafficSign::WORKFLOW_NEEDS_REVIEW,
        ]);
    }

    /**
     * @param  Collection<int, TrafficSign>  $records
     */
    public function confirmSources(Collection $records, ?CarbonInterface $checkedAt = null): int
    {
        $checkedAt ??= now();

        return $this->updateRecords($records, fn (TrafficSign $record): array => [
            'source_checked_at' => $checkedAt,
        ]);
    }

    /**
     * @param  Collection<int, TrafficSign>  $records
     */
    public function scheduleFreshnessReview(Collection $records, CarbonInterface $dueAt): int
    {
        return $this->updateRecords($records, fn (TrafficSign $record): array => [
            'freshness_review_due_at' => $dueAt,
        ]);
    }

    /**
     * @param  Collection<int, TrafficSign>  $records
     * @return array{updated: int, skipped: list<string>}
     */
    public function publishReady(Collection $records, ?CarbonInterface $publishedAt = null): array
    {
        $publishedAt ??= now();

        $updated = 0;
        $skipped = [];

        foreach ($records as $record) {
            if (! $record->hasCompletePublicationChecklist()) {
                $skipped[] = $record->code;

                continue;
            }

            $record->forceFill([
                'workflow_status' => TrafficSign::WORKFLOW_PUBLISHED,
                'is_published' => true,
                'published_at' => $record->published_at ?? $publishedAt,
            ])->save();

            $updated++;
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  Collection<int, TrafficSign>  $records
     */
    public function withdrawPublication(Collection $records): int
    {
        return $this->updateRecords($records, function (TrafficSign $record): array {
            $workflow = $record->workflow_status === TrafficSign::WORKFLOW_PUBLISHED
                ? TrafficSign::WORKFLOW_NEEDS_REVIEW
                : $record->workflow_status;

            return [
                'workflow_status' => $workflow,
                'is_published' => false,
                'published_at' => null,
            ];
        });
    }

    /**
     * @param  Collection<int, TrafficSign>  $records
     * @param  callable(TrafficSign): array<string, mixed>  $payload
     */
    protected function updateRecords(Collection $records, callable $payload): int
    {
        $updated = 0;

        foreach ($records as $record) {
            $record->forceFill($payload($record))->save();
            $updated++;
        }

        return $updated;
    }
}
